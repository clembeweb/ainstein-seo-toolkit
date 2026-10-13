<?php

namespace Modules\AiContent\Services;

use Core\Database;

/**
 * Contesto "cliente + piano editoriale" per gli Articoli Automatici.
 *
 * Una keyword arrivata in coda dal Piano Editoriale (Keyword Research) ritrova qui la sua riga
 * del piano: idea di titolo, perché l'articolo serve, gap SERP e la pagina del sito da linkare
 * (dall'Architettura Sito). Nessun campo nuovo in aic_queue: il collegamento passa per
 * progetto globale + keyword + sent_to_content.
 */
class PlanContextService
{
    /**
     * @return array|null [
     *   client_name, client_url,
     *   plan_title, content_type, notes, serp_gap, secondary_keywords[],
     *   target_pages[] => [url, title]   (pagine del sito da linkare, URL completi)
     * ]
     */
    public static function forQueueItem(int $aicProjectId, string $keyword): ?array
    {
        try {
            $project = Database::fetch(
                "SELECT gp.id AS gp_id, gp.name AS gp_name, gp.domain AS gp_domain
                 FROM aic_projects p
                 JOIN projects gp ON gp.id = p.global_project_id
                 WHERE p.id = ?",
                [$aicProjectId]
            );
            if (!$project) {
                return null;
            }

            $baseUrl = self::baseUrl((string) ($project['gp_domain'] ?? ''));
            $context = [
                'client_name' => trim((string) $project['gp_name']),
                'client_url' => $baseUrl,
                'plan_title' => null,
                'content_type' => null,
                'notes' => null,
                'serp_gap' => null,
                'secondary_keywords' => [],
                'target_pages' => [],
            ];

            $item = Database::fetch(
                "SELECT ei.*, r.brief AS research_brief
                 FROM kr_editorial_items ei
                 JOIN kr_researches r ON r.id = ei.research_id
                 JOIN kr_projects kp ON kp.id = r.project_id
                 WHERE kp.global_project_id = ? AND ei.main_keyword = ? AND ei.sent_to_content = 1
                 ORDER BY ei.id DESC LIMIT 1",
                [(int) $project['gp_id'], $keyword]
            );

            if ($item) {
                $context['plan_title'] = $item['title'] ?: null;
                $context['content_type'] = $item['content_type'] ?: null;
                $context['notes'] = $item['notes'] ?: null;
                $context['serp_gap'] = $item['serp_gap'] ?: null;
                $secondary = json_decode((string) ($item['secondary_keywords'] ?? ''), true);
                if (is_array($secondary)) {
                    foreach ($secondary as $s) {
                        $text = is_array($s) ? ($s['text'] ?? '') : (string) $s;
                        if ($text !== '') {
                            $context['secondary_keywords'][] = $text;
                        }
                    }
                }

                // Pagine del sito dall'Architettura collegata al piano: quelle citate nelle note sono le destinazioni
                $brief = json_decode((string) ($item['research_brief'] ?? ''), true) ?: [];
                $archId = (int) ($brief['architecture_research_id'] ?? 0);
                if ($archId > 0 && $baseUrl !== '') {
                    $pages = Database::fetchAll(
                        "SELECT name, suggested_url FROM kr_clusters WHERE research_id = ? ORDER BY sort_order ASC, total_volume DESC",
                        [$archId]
                    );
                    $notes = (string) ($item['notes'] ?? '');
                    foreach ($pages as $page) {
                        $path = trim((string) ($page['suggested_url'] ?? ''));
                        if ($path === '') {
                            continue;
                        }
                        if (self::notesMentionPath($notes, $path)) {
                            $context['target_pages'][] = [
                                'url' => $baseUrl . ($path === '/' ? '/' : '/' . ltrim($path, '/')),
                                'title' => $page['name'],
                            ];
                        }
                    }
                }
            }

            // Linko solo pagine che esistono davvero sul sito (l'Architettura propone pagine che spesso
            // non sono ancora state create: prima l'articolo linkava URL in 404)
            if (!empty($context['target_pages'])) {
                $checked = self::checkUrls(array_column($context['target_pages'], 'url'));
                $existing = [];
                $context['missing_pages'] = [];
                foreach ($context['target_pages'] as $page) {
                    $final = $checked[$page['url']] ?? null;
                    if ($final) {
                        $existing[] = ['url' => $final, 'title' => $page['title']];
                    } else {
                        $context['missing_pages'][] = $page['url'];
                    }
                }
                $context['target_pages'] = $existing;
            }
            // Nessuna pagina del piano esiste ancora: rimando alla homepage del cliente
            if (empty($context['target_pages']) && $baseUrl !== '') {
                $home = self::checkUrls([$baseUrl . '/'])[$baseUrl . '/'] ?? null;
                if ($home) {
                    $context['target_pages'][] = ['url' => $home, 'title' => 'Homepage di ' . ($context['client_name'] ?: 'del cliente')];
                }
            }

            return $context['client_name'] !== '' || $context['plan_title'] ? $context : null;
        } catch (\Throwable $e) {
            // Il contesto è un di più: se manca, l'articolo si scrive come prima
            return null;
        }
    }

    /**
     * Elenco link per internal_links_pool (formato atteso da ArticleGeneratorService).
     */
    public static function asInternalLinks(?array $context): array
    {
        $links = [];
        foreach (($context['target_pages'] ?? []) as $page) {
            $links[] = ['url' => $page['url'], 'title' => $page['title'], 'description' => 'Pagina del sito del cliente (dal piano editoriale)'];
        }
        return $links;
    }

    /**
     * Verifica in parallelo che le URL rispondano 2xx (seguendo i redirect).
     * @return array url => URL finale (dopo redirect) se esiste, null se no
     */
    public static function checkUrls(array $urls, int $timeout = 6): array
    {
        $urls = array_values(array_unique(array_filter($urls)));
        $result = array_fill_keys($urls, null);
        if (empty($urls) || !function_exists('curl_multi_init')) {
            return $result;
        }

        $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36';
        $makeHandle = function (string $url, bool $headOnly) use ($ua, $timeout) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_NOBODY => $headOnly,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_USERAGENT => $ua,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            return $ch;
        };

        $run = function (array $list, bool $headOnly) use ($makeHandle): array {
            $mh = curl_multi_init();
            $handles = [];
            foreach ($list as $url) {
                $handles[$url] = $makeHandle($url, $headOnly);
                curl_multi_add_handle($mh, $handles[$url]);
            }
            do {
                $status = curl_multi_exec($mh, $active);
                if ($active) {
                    curl_multi_select($mh, 1.0);
                }
            } while ($active && $status === CURLM_OK);
            $out = [];
            foreach ($handles as $url => $ch) {
                $out[$url] = [
                    'code' => (int) curl_getinfo($ch, CURLINFO_HTTP_CODE),
                    'final' => (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL),
                ];
                curl_multi_remove_handle($mh, $ch);
                curl_close($ch);
            }
            curl_multi_close($mh);
            return $out;
        };

        $first = $run($urls, true);
        $retry = [];
        foreach ($first as $url => $r) {
            if ($r['code'] >= 200 && $r['code'] < 300) {
                $result[$url] = $r['final'] ?: $url;
            } elseif (in_array($r['code'], [0, 403, 405], true)) {
                $retry[] = $url; // alcuni server non gestiscono HEAD: riprovo con GET
            }
        }
        if (!empty($retry)) {
            foreach ($run($retry, false) as $url => $r) {
                if ($r['code'] >= 200 && $r['code'] < 300) {
                    $result[$url] = $r['final'] ?: $url;
                }
            }
        }

        return $result;
    }

    private static function baseUrl(string $domain): string
    {
        $domain = trim($domain);
        if ($domain === '') {
            return '';
        }
        if (!preg_match('#^https?://#i', $domain)) {
            $domain = 'https://' . $domain;
        }
        return rtrim($domain, '/');
    }

    /**
     * Le note del piano citano i percorsi così: "verso /giro-in-barca-capri" o "homepage /".
     */
    private static function notesMentionPath(string $notes, string $path): bool
    {
        if ($notes === '') {
            return false;
        }
        if ($path === '/') {
            return (bool) preg_match('#(homepage\s*/|verso la homepage|\s/(\s|,|\.|$))#iu', $notes);
        }
        $quoted = preg_quote('/' . ltrim($path, '/'), '#');
        // il percorso non deve proseguire con altri segmenti (es. /giro-in-barca-capri vs /giro-in-barca-capri/tour-privato)
        return (bool) preg_match('#' . $quoted . '(?![a-z0-9\-/])#iu', $notes);
    }
}
