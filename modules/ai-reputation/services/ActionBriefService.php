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
    public const PROFILE_SOURCE = 'profilo confermato dal cliente';
    private const JSON_FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE;
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
        // charge_credits=false: l'unico addebito della scheda e' cost_action_brief, applicato dal controller dopo il salvataggio
        // max_tokens 16000: con il ragionamento esteso i token di thinking contano nel limite.
        // timeout 180: primaria + eventuale fallback (120s) restano sotto i ~300s della richiesta AJAX
        $options = ['max_tokens' => 16000, 'effort' => 'high', 'timeout' => 180, 'system' => self::systemPrompt(), 'charge_credits' => false];
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
        $valid = self::validate($data, $action, $dossier['ok_domains'], $dossier['allowed_sources']);
        if (is_string($valid)) {
            return $this->fail($actionId, $valid);
        }
        Database::execute(
            "UPDATE ar_actions SET channel = ?, channel_rationale = ?, suggested_outlets = ?, brief = ?, brief_model = ?, brief_generated_at = NOW(), brief_error = '' WHERE id = ?",
            [
                $valid['channel'],
                $valid['channel_rationale'],
                json_encode($valid['suggested_outlets'], self::JSON_FLAGS),
                json_encode($valid['brief'], self::JSON_FLAGS),
                (string) ($res['model'] ?? $options['model'] ?? 'sconosciuto'),
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
     * Dossier: tutto ciò che il modello deve sapere, solo da dati già nel DB. Ritorna ['prompt' => string, 'ok_domains' => string[], 'allowed_sources' => string[]].
     */
    public function dossier(array $action, array $run, array $project): array
    {
        $runId = (int) $run['id'];
        $responses = (new Run())->responses($runId);
        $analyses = (new Analysis())->byRun($runId);
        $sources = (new ReportBuilderService())->sources($responses, $analyses);
        $website = trim((string) ($project['website'] ?? ''));
        $siteDomain = $website ? EngineCollectorService::domainOf($website) : null;
        $okDomains = [];
        $negDomains = [];
        $negDomainNames = [];
        foreach ($sources as $s) {
            // il sito del soggetto e' il canale "own_site", non una testata esterna
            if ($s['status'] === 'ok' && count($okDomains) < 8 && !($siteDomain && $s['domain'] === $siteDomain)) {
                $okDomains[] = $s['domain'];
            }
            if ($s['status'] === 'negative' && count($negDomains) < 8) {
                $negDomains[] = $s['domain'] . ' (' . $s['negative'] . ' negative)';
                $negDomainNames[] = $s['domain'];
            }
        }
        $siteCited = 0;
        foreach ($sources as $s) {
            if ($siteDomain && $s['domain'] === $siteDomain) {
                $siteCited = (int) $s['count'];
            }
        }
        // Domande collegate all'intervento
        $pages = array_map(fn($p) => $p['url'], ActionPlanPdfService::pages($action));
        $pageDomain = $action['target_domain'] ? strtolower((string) $action['target_domain']) : null;
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
                    if (in_array($u, $pages, true) || ($pageDomain && self::domainMatches((string) $u, $pageDomain))) {
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
                $q['id'] = (int) $r['prompt_id'];
                $q['text'] = $r['prompt_text'];
                $q['verdicts'][] = $r['engine'] . ': ' . $a['verdict'] . ($a['summary'] ? ' — ' . mb_strimwidth((string) $a['summary'], 0, 200, '…') : '');
                unset($q);
            }
        }
        $questions = self::orderQuestions(array_values($questions), (string) ($action['title'] ?? ''), array_column(ReportBuilderService::coveredPrompts($action), 'id'));
        // Fatti del profilo: citabili, omonimi e fatti negativi noti in blocchi separati (le fonti-URL, categoria 'source', non sono fatti)
        $factRows = Database::fetchAll(
            "SELECT category, status, text, corrected_text, source_url FROM ar_profile_facts WHERE project_id = ? AND status IN ('confirmed','corrected') AND category <> 'source' ORDER BY category, sort_order, id",
            [(int) $project['id']]
        );
        $blocks = self::factBlocks($factRows, $action['type'] === 'removal');
        $facts = $blocks['citable'];
        $allowedSources = array_merge($blocks['sources'], $okDomains, $negDomainNames, $pages);
        if ($pageDomain) {
            $allowedSources[] = $pageDomain;
        }
        if ($siteDomain) {
            $allowedSources[] = $siteDomain;
        }
        if ($website !== '') {
            $allowedSources[] = $website;
        }
        $allowedSources = array_values(array_unique(array_filter(array_map(fn($x) => strtolower(trim((string) $x)), $allowedSources))));
        $competitors = Database::fetchAll("SELECT name, mentions_count FROM ar_competitors WHERE project_id = ? ORDER BY mentions_count DESC LIMIT 8", [(int) $project['id']]);

        $lines = [];
        $lines[] = "SOGGETTO: {$project['subject_name']} (" . ($project['subject_type'] ?? 'persona') . ($project['city'] ? ", {$project['city']}" : '') . ')';
        $lines[] = 'SITO UFFICIALE: ' . ($website ?: 'non indicato') . ($siteDomain ? ' — citato dalle AI in questo run: ' . ($siteCited ? "{$siteCited} volte" : 'mai') : '');
        if (!empty($project['disambiguation_notes'])) {
            $lines[] = 'NOTE DI DISAMBIGUAZIONE: ' . $project['disambiguation_notes'];
        }
        $lines[] = 'FATTI CONFERMATI DEL PROFILO (gli unici fatti citabili):';
        $lines[] = $facts ? '- ' . implode("\n- ", array_slice($facts, 0, 40)) : '- (nessuno confermato)';
        if ($blocks['homonyms']) {
            $lines[] = 'NON È IL SOGGETTO (omonimi da non confondere, non citarli): ' . implode('; ', array_slice($blocks['homonyms'], 0, 15));
        }
        if ($blocks['risks']) {
            $lines[] = 'FATTI NEGATIVI NOTI (contesto: non citarli nei contenuti; usali solo come base per le richieste di rimozione): ' . implode('; ', array_slice($blocks['risks'], 0, 15));
        }
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
        return ['prompt' => implode("\n", $lines), 'ok_domains' => $okDomains, 'allowed_sources' => $allowedSources];
    }

    /** Categorie del profilo che non sono fatti citabili: fonti-URL, omonimi, temi di rischio. */
    public const NON_CITABLE_CATEGORIES = ['source', 'homonym', 'risk'];

    /**
     * Divide i fatti confermati/corretti del profilo in: citabili (con fonte), omonimi, fatti negativi noti.
     * 'sources': URL/domini ammessi come fonte dei fatti. Le fonti di omonimi e rischi valgono solo
     * per le richieste di rimozione ($isRemoval), mai come fonte di un contenuto.
     *
     * @return array{citable: string[], homonyms: string[], risks: string[], sources: string[]}
     */
    public static function factBlocks(array $factRows, bool $isRemoval): array
    {
        $out = ['citable' => [], 'homonyms' => [], 'risks' => [], 'sources' => []];
        foreach ($factRows as $f) {
            $category = (string) ($f['category'] ?? '');
            $status = (string) ($f['status'] ?? '');
            if ($category === 'source' || !in_array($status, ['confirmed', 'corrected'], true)) {
                continue;
            }
            $text = ($status === 'corrected' && ($f['corrected_text'] ?? null) !== null) ? (string) $f['corrected_text'] : (string) ($f['text'] ?? '');
            $src = trim((string) ($f['source_url'] ?? ''));
            $citable = !in_array($category, self::NON_CITABLE_CATEGORIES, true);
            if ($src !== '' && ($citable || $isRemoval)) {
                $out['sources'][] = $src;
                $d = EngineCollectorService::domainOf($src);
                if ($d) {
                    $out['sources'][] = $d;
                }
            }
            if ($category === 'homonym') {
                $out['homonyms'][] = $text;
            } elseif ($category === 'risk') {
                $out['risks'][] = $text . ($src !== '' ? " (fonte: {$src})" : '');
            } else {
                $out['citable'][] = "[{$category}] {$text} (fonte: " . ($src !== '' ? $src : self::PROFILE_SOURCE) . ')';
            }
        }
        return $out;
    }

    /**
     * Domande collegate: prima quelle coperte dall'intervento (covered_prompts, ADR-014) o il cui testo compare nel
     * titolo (per i gap non raggruppati il titolo contiene la domanda tra virgolette), poi le altre; massimo 10.
     */
    public static function orderQuestions(array $questions, string $title, array $coveredIds = []): array
    {
        $title = mb_strtolower($title);
        $covered = array_flip(array_map('intval', $coveredIds));
        $first = [];
        $rest = [];
        foreach ($questions as $q) {
            $text = mb_strtolower(trim((string) ($q['text'] ?? '')));
            if (isset($covered[(int) ($q['id'] ?? 0)]) || ($text !== '' && $title !== '' && str_contains($title, $text))) {
                $first[] = $q;
            } else {
                $rest[] = $q;
            }
        }
        return array_slice(array_merge($first, $rest), 0, 10);
    }

    public static function systemPrompt(): string
    {
        return "Sei un consulente senior di reputazione digitale e un copywriter esperto. Lavori per un'agenzia che deve eseguire interventi concreti per un cliente.\n"
            . "Regole assolute:\n"
            . "- Rispondi SOLO con un oggetto JSON valido, senza testo prima o dopo, senza markdown.\n"
            . "- Scrivi in italiano.\n"
            . "- Non inventare fatti: cita solo i FATTI CONFERMATI DEL PROFILO e i dati del dossier, indicando la fonte.\n"
            . "- Non attribuire al soggetto i fatti degli omonimi e non citare nei contenuti i fatti negativi noti: servono solo come contesto.\n"
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
            . '"facts":[{"fact":"fatto da citare","source":"la fonte indicata tra parentesi nel dossier (URL o dominio), oppure profilo confermato dal cliente"}],"avoid":["cosa evitare"],"length_words":900,"language":"it",'
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

    /** True se $u (URL o dominio nudo) appartiene al dominio $domain (uguale o sottodominio). */
    private static function domainMatches(string $u, string $domain): bool
    {
        $d = EngineCollectorService::domainOf($u) ?? preg_replace('/^www\./', '', strtolower(trim($u)));
        return $d === $domain || str_ends_with($d, '.' . $domain);
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
     * $allowedSources (opzionale): fonti ammesse per i fatti; se vuoto si controlla solo che la fonte non sia vuota.
     */
    public static function validate(array $data, array $action, array $okDomains, array $allowedSources = []): array|string
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
        $outlets = $channel === 'own_site' ? [] : array_slice($outlets, 0, 3);
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
                if (!is_array($f) || $str($f['fact'] ?? '') === '') {
                    continue;
                }
                $src = $str($f['source'] ?? '');
                if ($src === '') {
                    continue;
                }
                if ($allowedSources) {
                    $srcLow = strtolower($src);
                    $known = str_contains($srcLow, self::PROFILE_SOURCE);
                    foreach ($allowedSources as $al) {
                        if ($al !== '' && str_contains($srcLow, strtolower((string) $al))) {
                            $known = true;
                            break;
                        }
                    }
                    if (!$known) {
                        continue;
                    }
                }
                $facts[] = ['fact' => $str($f['fact']), 'source' => $src];
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
        if (!$pages) {
            $pages = $allowed;
        }
        if (!$pages) {
            return 'Nessuna pagina valida per la richiesta di rimozione';
        }
        $request = $str($brief['request'] ?? '');
        if (!in_array($request, ['removal', 'deindex', 'update'], true)) {
            return 'Tipo di richiesta non valido';
        }
        if ($str($brief['recipient'] ?? '') === '' || $str($brief['basis'] ?? '') === '') {
            return 'Richiesta incompleta: mancano destinatario o motivazione';
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
