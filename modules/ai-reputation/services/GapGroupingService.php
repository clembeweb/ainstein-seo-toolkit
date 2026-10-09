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
