<?php

namespace Modules\AiReputation\Services;

use Core\Database;
use Services\AiService;
use Services\ScraperService;
use Modules\AiReputation\Models\ProfileFact;

/**
 * OnboardingService - dal solo nome costruisce la bozza di profilo (design §3.1, ADR-003).
 *
 * 1. una chiamata Perplexity "Chi è X?" → lista fonti con snippet (l'unico engine che li dà)
 * 2. ScraperService::scrape() sui top URL (sito ufficiale per primo)
 * 3. una chiamata AiService → righe JSON per categoria, salvate come `proposed`
 */
class OnboardingService
{
    private const SLUG = 'ai-reputation';
    private const MAX_FETCH = 5;

    /**
     * @return array ['success' => bool, 'facts' => int, 'sources' => int, 'error' => ?string]
     */
    public function buildProfile(int $creditUserId, array $project): array
    {
        $subject = trim($project['subject_name']);
        $type = ($project['subject_type'] ?? 'person') === 'company' ? 'azienda' : 'persona';

        // 1. Fonti via Perplexity (fallback: nessuna fonte, si usa solo il sito)
        $sources = [];
        $overview = '';
        if (EngineCollectorService::isConfigured('perplexity')) {
            $where = !empty($project['city']) ? " ({$project['city']})" : '';
            // Due ricerche: chi è (profilo) + cosa si dice di negativo (rischi). Costano 0,0014 $ l'una.
            $queries = [
                "Chi è {$subject}{$where}? Attività, ruolo, azienda, storia, notizie recenti, eventuali omonimi.",
                "{$subject}{$where}: notizie negative, controversie, procedimenti, inchieste, recensioni negative, sequestri o condanne. Riporta solo fatti con fonte.",
            ];
            $collector = new EngineCollectorService();
            foreach ($queries as $i => $q) {
                $r = $collector->ask('perplexity', $q, ['cluster' => 'comm', 'user_id' => $creditUserId, 'context' => 'onboarding']);
                Database::reconnect();
                if ($r['status'] !== 'ok') {
                    continue;
                }
                $overview .= ($i === 0 ? "PROFILO:\n" : "\n\nRISCHI E CONTROVERSIE:\n") . (string) $r['text'];
                foreach ($r['sources_read'] as $s) {
                    $title = (string) ($s['title'] ?? '');
                    if (preg_match('/^(403|404|401)\b|access denied|not found|forbidden/i', $title)) {
                        continue;
                    }
                    $sources[$s['url']] ??= ['url' => $s['url'], 'title' => $title, 'snippet' => $s['snippet'] ?? '', 'date' => $s['date'] ?? null];
                }
            }
        }

        // 2. Testi: sito ufficiale + top fonti
        $toFetch = [];
        if (!empty($project['website']) && \Modules\AiReputation\Controllers\ProjectController::isPublicWebUrl($project['website'])) {
            $toFetch[] = $project['website'];
        }
        foreach (array_keys($sources) as $u) {
            if (count($toFetch) >= self::MAX_FETCH) {
                break;
            }
            if (!in_array($u, $toFetch, true) && !preg_match('#linkedin\.com|facebook\.com|instagram\.com|youtube\.com|\.pdf($|\?)#i', $u)) {
                $toFetch[] = $u;
            }
        }
        $scraper = new ScraperService();
        $texts = [];
        foreach ($toFetch as $u) {
            try {
                $res = $scraper->scrape($u);
                if (!empty($res['content']) && mb_strlen((string) $res['content']) > 200) {
                    $texts[$u] = ['title' => $res['title'] ?? '', 'content' => mb_substr(preg_replace('/\s+/', ' ', (string) $res['content']), 0, 2500)];
                }
            } catch (\Throwable $e) {
                // una fonte che non si legge non blocca l'onboarding: resta lo snippet
            }
        }
        Database::reconnect();

        // 3. AiService → righe profilo
        $ai = new AiService(self::SLUG);
        $result = $ai->complete($creditUserId, [
            ['role' => 'user', 'content' => $this->userPrompt($subject, $type, $project, $overview, $sources, $texts)],
        ], ['system' => $this->systemPrompt(), 'max_tokens' => 4000], self::SLUG);
        Database::reconnect();

        if (!empty($result['error']) || empty($result['result'])) {
            return ['success' => false, 'error' => $result['message'] ?? $result['error'] ?? 'Risposta AI vuota'];
        }
        try {
            $data = $this->parseJson((string) $result['result']);
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'JSON profilo non valido: ' . $e->getMessage()];
        }

        $facts = new ProfileFact();
        $facts->deleteProposedAi((int) $project['id']);
        $added = 0;
        foreach ((array) ($data['facts'] ?? []) as $f) {
            if (!is_array($f) || empty($f['text'])) {
                continue;
            }
            $text = trim((string) $f['text']);
            if ($facts->exists((int) $project['id'], $text)) {
                continue;
            }
            $facts->create((int) $project['id'], (string) ($f['category'] ?? 'fact'), $text, !empty($f['source_url']) ? (string) $f['source_url'] : null, 'ai');
            $added++;
        }
        // Le fonti lette diventano righe "source" (dominio + titolo), così si vede da dove viene il profilo
        foreach (array_slice($sources, 0, 12) as $s) {
            $domain = EngineCollectorService::domainOf($s['url']);
            $text = ($domain ?: $s['url']) . ($s['title'] ? ' — ' . mb_substr($s['title'], 0, 120) : '');
            if (!$facts->exists((int) $project['id'], $text)) {
                $facts->create((int) $project['id'], 'source', $text, $s['url'], 'ai');
                $added++;
            }
        }

        return ['success' => true, 'facts' => $added, 'sources' => count($sources), 'fetched' => count($texts)];
    }

    private function systemPrompt(): string
    {
        return <<<'TXT'
Sei l'agente di onboarding di un sistema di monitoraggio della reputazione nelle AI. Ricevi il nome di un soggetto (persona o azienda), una panoramica trovata online, le fonti con i loro snippet e il testo di alcune pagine. Devi produrre la BOZZA DEL PROFILO come elenco di affermazioni brevi, verificabili una per una da chi conosce il soggetto.
Rispondi SOLO con JSON valido, senza markdown.

Categorie ammesse (campo "category"):
- identity: chi è (ruolo, città, età se nota, titolo)
- activity: cosa fa (settori, servizi, aziende, progetti, mercati)
- alias: altri nomi, sigle, nomi di aziende riconducibili, handle social, sito ufficiale
- person: persone collegate (soci, collaboratori, familiari se pubblici)
- fact: fatti chiave con data (operazioni, pubblicazioni, interventi, premi)
- risk: temi di rischio reputazionale emersi dalle fonti (procedimenti, controversie, articoli critici, recensioni negative) — riportali come li dicono le fonti, senza giudicare
- homonym: altre persone/aziende con lo stesso nome trovate nelle fonti (una riga per ciascuna, con cosa fanno)

Regole:
1. Una riga = una affermazione sola, 8-30 parole, in italiano, terza persona.
2. Ogni riga ha "source_url" (l'URL da cui viene) oppure null se dedotta.
3. 15-35 righe in tutto. Niente ripetizioni. Niente righe vaghe ("è una persona nota").
4. Se le fonti si riferiscono chiaramente a persone diverse con lo stesso nome, NON fonderle: metti il profilo principale (quello coerente col sito ufficiale/città) nelle categorie normali e gli altri in "homonym".
5. I temi di rischio vanno SEMPRE riportati se presenti nelle fonti: è il motivo per cui esiste il sistema.

Formato:
{"facts":[{"category":"identity","text":"...","source_url":"https://..."}, ...]}
TXT;
    }

    private function userPrompt(string $subject, string $type, array $project, string $overview, array $sources, array $texts): string
    {
        $lines = ["SOGGETTO: {$subject} ({$type})"];
        if (!empty($project['city'])) {
            $lines[] = "Città: {$project['city']}";
        }
        if (!empty($project['website'])) {
            $lines[] = "Sito ufficiale: {$project['website']}";
        }
        if (!empty($project['disambiguation_notes'])) {
            $lines[] = 'Note di disambiguazione fornite dal cliente: ' . trim($project['disambiguation_notes']);
        }
        if ($overview !== '') {
            $lines[] = "\nPANORAMICA (risposta di un motore AI con ricerca web):\n" . mb_substr($overview, 0, 3000);
        }
        if ($sources) {
            $lines[] = "\nFONTI TROVATE (url | titolo | data | snippet):";
            foreach (array_slice($sources, 0, 15) as $s) {
                $lines[] = '- ' . $s['url'] . ' | ' . $s['title'] . ' | ' . ($s['date'] ?? '') . ' | ' . mb_substr((string) $s['snippet'], 0, 400);
            }
        }
        if ($texts) {
            $lines[] = "\nTESTI DELLE PAGINE:";
            foreach ($texts as $u => $t) {
                $lines[] = "### {$u} — {$t['title']}\n{$t['content']}";
            }
        }
        return implode("\n", $lines);
    }

    private function parseJson(string $text): array
    {
        $s = preg_replace('/```json\s*/i', '', $text);
        $s = preg_replace('/```\s*/', '', $s);
        $a = strpos($s, '{');
        $b = strrpos($s, '}');
        if ($a === false || $b === false) {
            throw new \RuntimeException('nessun oggetto JSON');
        }
        $data = json_decode(substr($s, $a, $b - $a + 1), true);
        if (!is_array($data)) {
            throw new \RuntimeException(json_last_error_msg());
        }
        return $data;
    }
}
