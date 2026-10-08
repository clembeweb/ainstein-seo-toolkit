<?php

namespace Modules\AiReputation\Services;

use Core\Database;
use Core\ModuleLoader;
use Modules\AiReputation\Models\Analysis;
use Modules\AiReputation\Models\ProfileFact;
use Modules\AiReputation\Models\Run;
use Services\AiService;

/**
 * Scheda operativa di un intervento del piano d'azione, generata su richiesta (un intervento per volta).
 * Decide il canale (sito proprietario / esterno / entrambi), suggerisce testate tra quelle che le AI citano,
 * scrive il brief del contenuto o la traccia della richiesta di rimozione. Via AiService (Golden Rule 1).
 */
class ActionBriefService
{
    public const SLUG = 'ai-reputation';
    public const CHANNELS = ['own_site', 'external', 'both'];
    private AiService $ai;

    public function __construct()
    {
        $this->ai = new AiService(self::SLUG);
    }

    /** Modello per le schede: setting del modulo; 'global' = quello di AiService. */
    public function model(): string
    {
        return (string) ModuleLoader::getSetting(self::SLUG, 'brief_model', 'claude-opus-5-5');
    }

    public function generate(int $actionId, int $userId): array
    {
        $action = Database::fetch("SELECT * FROM ar_actions WHERE id = ?", [$actionId]);
        if (!$action) {
            return ['success' => false, 'error' => 'Intervento non trovato'];
        }
        $run = (new Run())->find((int) $action['run_id']);
        $project = Database::fetch("SELECT * FROM ar_projects WHERE id = ?", [(int) $action['project_id']]);
        if (!$run || !$project) {
            return ['success' => false, 'error' => 'Run o progetto non trovato'];
        }
        $dossier = $this->dossier($action, $run, $project);
        $options = ['max_tokens' => 8192, 'effort' => 'high', 'timeout' => 240, 'system' => self::systemPrompt()];
        if ($this->model() !== 'global') {
            $options['model'] = $this->model();
        }
        $res = $this->ai->complete($userId, [['role' => 'user', 'content' => $dossier['prompt']]], $options, self::SLUG);
        Database::reconnect();
        if (!empty($res['error']) || empty($res['success'])) {
            return $this->fail($actionId, (string) ($res['message'] ?? $res['error'] ?? 'Chiamata AI fallita'));
        }
        $data = self::parseJson((string) $res['result']);
        if ($data === null) {
            return $this->fail($actionId, 'Risposta AI non in formato JSON');
        }
        $valid = self::validate($data, $action, $dossier['ok_domains']);
        if (is_string($valid)) {
            return $this->fail($actionId, $valid);
        }
        Database::execute(
            "UPDATE ar_actions SET channel = ?, channel_rationale = ?, suggested_outlets = ?, brief = ?, brief_model = ?, brief_generated_at = NOW(), brief_error = '' WHERE id = ?",
            [
                $valid['channel'],
                $valid['channel_rationale'],
                json_encode($valid['suggested_outlets'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                json_encode($valid['brief'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                (string) ($res['model'] ?? $options['model'] ?? 'global'),
                $actionId,
            ]
        );
        return ['success' => true, 'action' => Database::fetch("SELECT * FROM ar_actions WHERE id = ?", [$actionId])];
    }

    private function fail(int $actionId, string $error): array
    {
        $error = mb_strimwidth($error, 0, 490, '…');
        Database::execute("UPDATE ar_actions SET brief_error = ? WHERE id = ?", [$error, $actionId]);
        return ['success' => false, 'error' => $error];
    }

    /**
     * Dossier: tutto ciò che il modello deve sapere, solo da dati già nel DB. Ritorna ['prompt' => string, 'ok_domains' => string[]].
     */
    public function dossier(array $action, array $run, array $project): array
    {
        $runId = (int) $run['id'];
        $responses = (new Run())->responses($runId);
        $analyses = (new Analysis())->byRun($runId);
        $sources = (new ReportBuilderService())->sources($responses, $analyses);
        $okDomains = [];
        $negDomains = [];
        foreach ($sources as $s) {
            if ($s['status'] === 'ok' && count($okDomains) < 8) {
                $okDomains[] = $s['domain'];
            }
            if ($s['status'] === 'negative' && count($negDomains) < 8) {
                $negDomains[] = $s['domain'] . ' (' . $s['negative'] . ' negative)';
            }
        }
        $website = trim((string) ($project['website'] ?? ''));
        $siteDomain = $website ? EngineCollectorService::domainOf($website) : null;
        $siteCited = 0;
        foreach ($sources as $s) {
            if ($siteDomain && $s['domain'] === $siteDomain) {
                $siteCited = (int) $s['count'];
            }
        }
        // Domande collegate all'intervento
        $pages = array_map(fn($p) => $p['url'], ActionPlanPdfService::pages($action));
        $pageDomain = $action['target_domain'] ?: null;
        $questions = [];
        foreach ($responses as $r) {
            if ($r['status'] !== 'ok') {
                continue;
            }
            $a = $analyses[(int) $r['id']] ?? null;
            if (!$a) {
                continue;
            }
            $hit = false;
            if ($action['type'] === 'removal') {
                foreach (array_merge($a['negative_urls'], $a['cited_domains']) as $u) {
                    if (($pageDomain && str_contains((string) $u, $pageDomain)) || in_array($u, $pages, true)) {
                        $hit = true;
                        break;
                    }
                }
            } else {
                $hit = in_array($a['verdict'], ['negative', 'mixed', 'not_mentioned'], true)
                    && in_array($r['prompt_cluster'], ['rep', 'comm', 'comp'], true);
            }
            if ($hit) {
                $q = &$questions[(int) $r['prompt_id']];
                $q['text'] = $r['prompt_text'];
                $q['verdicts'][] = $r['engine'] . ': ' . $a['verdict'] . ($a['summary'] ? ' — ' . mb_strimwidth((string) $a['summary'], 0, 200, '…') : '');
                unset($q);
            }
        }
        $questions = array_slice($questions, 0, 10);
        $facts = [];
        foreach ((new ProfileFact())->truth((int) $project['id']) as $cat => $items) {
            foreach ($items as $t) {
                $facts[] = "[{$cat}] {$t}";
            }
        }
        $competitors = Database::fetchAll("SELECT name, mentions_count FROM ar_competitors WHERE project_id = ? ORDER BY mentions_count DESC LIMIT 8", [(int) $project['id']]);

        $lines = [];
        $lines[] = "SOGGETTO: {$project['subject_name']} (" . ($project['subject_type'] ?? 'persona') . ($project['city'] ? ", {$project['city']}" : '') . ')';
        $lines[] = 'SITO UFFICIALE: ' . ($website ?: 'non indicato') . ($siteDomain ? ' — citato dalle AI in questo run: ' . ($siteCited ? "{$siteCited} volte" : 'mai') : '');
        if (!empty($project['disambiguation_notes'])) {
            $lines[] = 'NOTE DI DISAMBIGUAZIONE: ' . $project['disambiguation_notes'];
        }
        $lines[] = 'FATTI CONFERMATI DEL PROFILO (gli unici fatti citabili):';
        $lines[] = $facts ? '- ' . implode("\n- ", array_slice($facts, 0, 40)) : '- (nessuno confermato)';
        $lines[] = "INTERVENTO: tipo={$action['type']}; titolo=\"{$action['title']}\"; motivazione=\"{$action['rationale']}\"" . ($pageDomain ? "; sito={$pageDomain}" : '');
        if ($pages) {
            $lines[] = "PAGINE DELL'INTERVENTO:\n- " . implode("\n- ", array_slice($pages, 0, 15));
        }
        $lines[] = 'DOMANDE COLLEGATE E VERDETTI DELLE AI:';
        foreach ($questions as $q) {
            $lines[] = '- "' . $q['text'] . '" → ' . implode(' | ', $q['verdicts']);
        }
        if (!$questions) {
            $lines[] = '- (nessuna)';
        }
        $lines[] = 'TESTATE CHE LE AI CITANO COME FONTI AFFIDABILI (uniche ammesse in suggested_outlets): ' . ($okDomains ? implode(', ', $okDomains) : 'nessuna');
        $lines[] = 'FONTI NEGATIVE CITATE: ' . ($negDomains ? implode(', ', $negDomains) : 'nessuna');
        if ($competitors) {
            $lines[] = 'COMPETITOR CITATI AL POSTO DEL SOGGETTO: ' . implode(', ', array_map(fn($c) => "{$c['name']} ({$c['mentions_count']})", $competitors));
        }
        $lines[] = '';
        $lines[] = $action['type'] === 'removal' ? self::removalInstructions() : self::contentInstructions();
        return ['prompt' => implode("\n", $lines), 'ok_domains' => $okDomains];
    }

    public static function systemPrompt(): string
    {
        return "Sei un consulente senior di reputazione digitale e un copywriter esperto. Lavori per un'agenzia che deve eseguire interventi concreti per un cliente.\n"
            . "Regole assolute:\n"
            . "- Rispondi SOLO con un oggetto JSON valido, senza testo prima o dopo, senza markdown.\n"
            . "- Scrivi in italiano.\n"
            . "- Non inventare fatti: cita solo i FATTI CONFERMATI DEL PROFILO e i dati del dossier, indicando la fonte.\n"
            . "- Non citare costi, tariffe o prezzi di testate o servizi.\n"
            . "- Non spiegare come sono stati raccolti i dati né come lavorano le AI: concentrati su cosa fare.\n"
            . "- suggested_outlets può contenere solo domini presenti nell'elenco TESTATE CHE LE AI CITANO.";
    }

    private static function contentInstructions(): string
    {
        return "COMPITO: decidi dove pubblicare questo contenuto e scrivi il brief per chi lo scriverà.\n"
            . "Canale: \"own_site\" se conviene soprattutto il sito ufficiale del soggetto, \"external\" se serve una testata terza che le AI già citano, \"both\" se servono entrambi. Pesa: se il sito ufficiale non è mai citato dalle AI va rafforzato; se le AI citano sempre le stesse testate, quelle contano.\n"
            . "Rispondi con questo JSON:\n"
            . '{"channel":"own_site|external|both","channel_rationale":"1-3 frasi","suggested_outlets":["dominio",...],'
            . '"brief":{"kind":"content","title":"titolo proposto","angle":"taglio in 1-2 frasi","points":["almeno 4 punti da coprire"],'
            . '"facts":[{"fact":"fatto da citare","source":"fonte dal dossier"}],"avoid":["cosa evitare"],"length_words":900,"language":"it",'
            . '"own_site_note":"cosa pubblicare sul sito ufficiale se channel è own_site o both, altrimenti stringa vuota"}}';
    }

    private static function removalInstructions(): string
    {
        return "COMPITO: prepara la traccia della richiesta al sito per le PAGINE DELL'INTERVENTO.\n"
            . "Rispondi con questo JSON:\n"
            . '{"channel":null,"channel_rationale":"","suggested_outlets":[],'
            . '"brief":{"kind":"removal","recipient":"a chi scrivere (redazione, webmaster, ufficio stampa)","request":"removal|deindex|update",'
            . '"basis":"su quale base chiedere (es. notizia superata, esito del procedimento, diritto all\'oblio), riferita ai fatti confermati",'
            . '"pages":["solo URL tra le PAGINE DELL\'INTERVENTO"],"fallback":"cosa fare se rifiutano"}}';
    }

    /** Pulizia della risposta: via i fence ```, ritaglio dal primo { all'ultimo }. */
    public static function parseJson(string $text): ?array
    {
        $s = preg_replace('/```json\s*/i', '', $text) ?? $text;
        $s = preg_replace('/```\s*/', '', $s) ?? $s;
        $a = strpos($s, '{');
        $b = strrpos($s, '}');
        if ($a === false || $b === false || $b < $a) {
            return null;
        }
        $data = json_decode(substr($s, $a, $b - $a + 1), true);
        return is_array($data) ? $data : null;
    }

    /**
     * Normalizza e valida l'output AI. Ritorna i dati pronti da salvare oppure una stringa di errore.
     */
    public static function validate(array $data, array $action, array $okDomains): array|string
    {
        $isRemoval = ($action['type'] ?? '') === 'removal';
        $brief = $data['brief'] ?? null;
        if (!is_array($brief)) {
            return 'Manca il campo brief';
        }
        $kind = $brief['kind'] ?? null;
        if ($kind !== ($isRemoval ? 'removal' : 'content')) {
            return 'Tipo di scheda incoerente con l\'intervento';
        }
        $channel = $data['channel'] ?? null;
        if ($isRemoval) {
            $channel = null;
        } elseif (!in_array($channel, self::CHANNELS, true)) {
            return 'Canale non valido: ' . (is_scalar($channel) ? (string) $channel : 'assente');
        }
        $okSet = array_flip(array_map('strtolower', $okDomains));
        $outlets = [];
        foreach ((array) ($data['suggested_outlets'] ?? []) as $o) {
            $o = strtolower(trim((string) $o));
            $o = preg_replace('#^https?://(www\.)?#', '', $o) ?? $o;
            $o = rtrim($o, '/');
            if ($o !== '' && isset($okSet[$o]) && !in_array($o, $outlets, true)) {
                $outlets[] = $o;
            }
        }
        $outlets = array_slice($outlets, 0, 3);
        $clean = ['channel' => $channel, 'channel_rationale' => trim((string) ($data['channel_rationale'] ?? '')), 'suggested_outlets' => $isRemoval ? [] : $outlets];
        $str = fn($v) => trim((string) (is_scalar($v) ? $v : ''));
        $list = fn($v) => array_values(array_filter(array_map($str, is_array($v) ? $v : []), fn($x) => $x !== ''));
        if (!$isRemoval) {
            $points = $list($brief['points'] ?? []);
            if (count($points) < 3) {
                return 'Brief troppo povero: servono almeno 3 punti da coprire';
            }
            $facts = [];
            foreach ((array) ($brief['facts'] ?? []) as $f) {
                if (is_array($f) && $str($f['fact'] ?? '') !== '') {
                    $facts[] = ['fact' => $str($f['fact']), 'source' => $str($f['source'] ?? '')];
                }
            }
            $clean['brief'] = [
                'kind' => 'content',
                'title' => $str($brief['title'] ?? ''),
                'angle' => $str($brief['angle'] ?? ''),
                'points' => $points,
                'facts' => $facts,
                'avoid' => $list($brief['avoid'] ?? []),
                'length_words' => max(0, (int) ($brief['length_words'] ?? 0)),
                'language' => $str($brief['language'] ?? 'it') ?: 'it',
                'own_site_note' => $str($brief['own_site_note'] ?? ''),
            ];
            if ($clean['brief']['title'] === '') {
                return 'Brief senza titolo';
            }
            return $clean;
        }
        $allowed = array_map(fn($p) => $p['url'], ActionPlanPdfService::pages($action));
        $pages = array_values(array_filter($list($brief['pages'] ?? []), fn($u) => in_array($u, $allowed, true)));
        $request = $str($brief['request'] ?? '');
        if (!in_array($request, ['removal', 'deindex', 'update'], true)) {
            return 'Tipo di richiesta non valido';
        }
        $clean['brief'] = [
            'kind' => 'removal',
            'recipient' => $str($brief['recipient'] ?? ''),
            'request' => $request,
            'basis' => $str($brief['basis'] ?? ''),
            'pages' => $pages,
            'fallback' => $str($brief['fallback'] ?? ''),
        ];
        return $clean;
    }
}
