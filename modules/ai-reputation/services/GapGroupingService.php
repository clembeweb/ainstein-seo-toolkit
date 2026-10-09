<?php

namespace Modules\AiReputation\Services;

use Core\Database;
use Core\Logger;
use Core\ModuleLoader;
use Services\AiService;

/**
 * Raggruppa le domande scoperte (il soggetto non e' citato, i competitor si') in pochi articoli veri e mette
 * in sospeso quelle che i fatti confermati non sostengono (ADR-014). Una sola chiamata AI a fine run, via AiService
 * (Golden Rule 1). Se la chiamata fallisce, group() ritorna null e il piano torna a una riga per domanda.
 */
class GapGroupingService
{
    public const SLUG = 'ai-reputation';
    /** Tetto duro sugli articoli: oltre, le domande in eccesso vanno in sospeso "da rivedere". */
    public const MAX_ARTICLES = 6;
    public const PENDING_REVIEW = 'da rivedere';
    private AiService $ai;

    public function __construct()
    {
        $this->ai = new AiService(self::SLUG);
    }

    /** Stesso modello delle schede (setting brief_model); 'global' = quello di AiService. */
    public function model(): string
    {
        return (string) ModuleLoader::getSetting(self::SLUG, 'brief_model', 'claude-opus-5-5');
    }

    /**
     * Una chiamata per tutte le domande scoperte del run. $gapByPrompt: prompt_id => ['prompt' => testo, 'competitors' => [nome => true], ...].
     * Ritorna ['articles' => [...], 'pending' => [...]] con prompt_id reali, oppure null se l'AI fallisce (il chiamante usa una riga per domanda).
     * charge_credits=false: il costo (~0,05 $) e' incluso nella run, nessun credito a parte.
     */
    public function group(array $project, array $gapByPrompt, int $userId): ?array
    {
        if (!$gapByPrompt) {
            return ['articles' => [], 'pending' => []];
        }
        $ids = array_map('intval', array_keys($gapByPrompt));
        $factRows = Database::fetchAll(
            "SELECT category, status, text, corrected_text, source_url FROM ar_profile_facts WHERE project_id = ? AND status IN ('confirmed','corrected') AND category <> 'source' ORDER BY category, sort_order, id",
            [(int) $project['id']]
        );
        $blocks = ActionBriefService::factBlocks($factRows, false);
        $options = ['max_tokens' => 4000, 'effort' => 'medium', 'timeout' => 120, 'system' => self::systemPrompt(), 'charge_credits' => false];
        if ($this->model() !== 'global') {
            $options['model'] = $this->model();
        }
        $log = Logger::channel(self::SLUG);
        try {
            $res = $this->ai->complete($userId, [['role' => 'user', 'content' => self::userPrompt($project, $gapByPrompt, $blocks)]], $options, self::SLUG);
        } catch (\Throwable $e) {
            Database::reconnect();
            $log->warning('Raggruppamento gap fallito', ['project_id' => $project['id'], 'error' => $e->getMessage()]);
            return null;
        }
        Database::reconnect();
        if (!empty($res['error']) || empty($res['success'])) {
            $log->warning('Raggruppamento gap fallito', ['project_id' => $project['id'], 'error' => (string) ($res['message'] ?? $res['error'] ?? 'Chiamata AI fallita')]);
            return null;
        }
        $data = ActionBriefService::parseJson((string) $res['result']);
        if ($data === null) {
            $log->warning('Raggruppamento gap fallito', ['project_id' => $project['id'], 'error' => 'Risposta AI non in formato JSON']);
            return null;
        }
        return self::validate($data, $ids);
    }

    public static function systemPrompt(): string
    {
        return "Sei un consulente senior di contenuti e reputazione digitale. Devi trasformare un elenco di domande in un piano editoriale essenziale.\n"
            . "Regole assolute:\n"
            . "- Rispondi SOLO con un oggetto JSON valido, senza testo prima o dopo, senza markdown.\n"
            . "- Scrivi in italiano.\n"
            . "- Usa solo i FATTI CONFERMATI DEL PROFILO: non attribuire al soggetto competenze, attività o risultati che non vi compaiono.\n"
            . "- Non attribuire al soggetto i fatti degli omonimi.\n"
            . "- Non citare costi, tariffe o prezzi di testate o servizi.\n"
            . "- Non spiegare come sono stati raccolti i dati né come lavorano le AI.";
    }

    /** Dossier + compito. Le domande hanno un numero 1..N nell'ordine di $gapByPrompt (mai il prompt_id reale). */
    public static function userPrompt(array $project, array $gapByPrompt, array $blocks): string
    {
        $lines = [];
        $lines[] = "SOGGETTO: {$project['subject_name']} (" . ($project['subject_type'] ?? 'persona') . (!empty($project['city']) ? ", {$project['city']}" : '') . ')';
        if (!empty($project['disambiguation_notes'])) {
            $lines[] = 'NOTE DI DISAMBIGUAZIONE: ' . $project['disambiguation_notes'];
        }
        $lines[] = 'FATTI CONFERMATI DEL PROFILO (le uniche competenze e attività attribuibili al soggetto):';
        $lines[] = !empty($blocks['citable']) ? '- ' . implode("\n- ", array_slice($blocks['citable'], 0, 40)) : '- (nessuno confermato)';
        if (!empty($blocks['homonyms'])) {
            $lines[] = 'NON È IL SOGGETTO (omonimi da non confondere): ' . implode('; ', array_slice($blocks['homonyms'], 0, 15));
        }
        $lines[] = 'DOMANDE A CUI LE AI RISPONDONO SENZA CITARE IL SOGGETTO (numero. "domanda" → chi citano al suo posto):';
        $n = 0;
        foreach ($gapByPrompt as $info) {
            $n++;
            $names = array_slice(array_keys((array) ($info['competitors'] ?? [])), 0, 6);
            $lines[] = "{$n}. \"{$info['prompt']}\"" . ($names ? ' → citano: ' . implode(', ', $names) : '');
        }
        $lines[] = '';
        $lines[] = 'COMPITO: raggruppa le domande per tema in pochi articoli (di norma 2-4, mai più di ' . self::MAX_ARTICLES . '). Lo stesso tema in lingue diverse va nello stesso articolo. Ogni domanda sta in un solo posto.' . "\n"
            . 'Una domanda va in "pending" se i FATTI CONFERMATI non sostengono una competenza o un\'attività credibile del soggetto su quel tema: indica la prova che servirebbe dal cliente (es. un\'operazione documentata, un incarico, una pubblicazione).' . "\n"
            . 'Per ogni articolo: titolo concreto (non la domanda ripetuta), perché serve in 1-2 frasi, numeri delle domande coperte.' . "\n"
            . "Rispondi con questo JSON:\n"
            . '{"articles":[{"title":"titolo","why":"1-2 frasi","questions":[1,2]}],"pending":[{"question":3,"needed":"quale prova serve"}]}';
        return implode("\n", $lines);
    }

    /**
     * Normalizza l'output AI. Pura: testabile senza rete.
     * Nel prompt le domande hanno un numero 1..N; $ids[n-1] e' il prompt_id reale del numero n.
     * Regole: numero inventato o gia' usato → scartato (prima occorrenza vince); articolo senza titolo/perche'/domande
     * → scartato e domande in sospeso; oltre MAX_ARTICLES → domande in sospeso; domande dimenticate → in sospeso.
     *
     * @param int[] $ids
     * @return array{articles: array<int, array{title: string, why: string, questions: int[]}>, pending: array<int, array{prompt_id: int, needed: string}>}
     */
    public static function validate(array $data, array $ids): array
    {
        $ids = array_values($ids);
        $used = [];
        $toId = function ($n) use ($ids, &$used): ?int {
            if (!is_int($n) && !(is_string($n) && ctype_digit($n))) {
                return null;
            }
            $n = (int) $n;
            if ($n < 1 || $n > count($ids) || isset($used[$n])) {
                return null;
            }
            $used[$n] = true;
            return (int) $ids[$n - 1];
        };
        $str = fn($v, int $max): string => mb_strimwidth(trim(is_scalar($v) ? (string) $v : ''), 0, $max, '…');

        $articles = [];
        $overflow = []; // domande di articoli scartati o oltre il tetto
        foreach ((array) ($data['articles'] ?? []) as $art) {
            if (!is_array($art)) {
                continue;
            }
            $qs = [];
            foreach ((array) ($art['questions'] ?? []) as $n) {
                $id = $toId($n);
                if ($id !== null) {
                    $qs[] = $id;
                }
            }
            if (!$qs) {
                continue;
            }
            $title = $str($art['title'] ?? '', 500);
            $why = $str($art['why'] ?? '', 2000);
            if ($title === '' || $why === '' || count($articles) >= self::MAX_ARTICLES) {
                array_push($overflow, ...$qs);
                continue;
            }
            $articles[] = ['title' => $title, 'why' => $why, 'questions' => $qs];
        }

        $pending = [];
        foreach ((array) ($data['pending'] ?? []) as $p) {
            if (!is_array($p)) {
                continue;
            }
            $id = $toId($p['question'] ?? null);
            if ($id === null) {
                continue;
            }
            $needed = $str($p['needed'] ?? '', 500);
            $pending[] = ['prompt_id' => $id, 'needed' => $needed !== '' ? $needed : self::PENDING_REVIEW];
        }
        foreach ($overflow as $id) {
            $pending[] = ['prompt_id' => $id, 'needed' => self::PENDING_REVIEW];
        }
        foreach ($ids as $i => $id) {
            if (!isset($used[$i + 1])) {
                $pending[] = ['prompt_id' => (int) $id, 'needed' => self::PENDING_REVIEW];
            }
        }
        return ['articles' => $articles, 'pending' => $pending];
    }
}
