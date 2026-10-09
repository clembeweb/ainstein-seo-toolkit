# Raggruppamento AI degli "articoli gap" — Piano di implementazione

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** A fine run, una sola chiamata AI trasforma le domande scoperte (soggetto non citato) in pochi articoli veri più le domande "in sospeso" che i fatti confermati non sostengono; report e PDF le mostrano; le schede già generate sopravvivono al ricalcolo.

**Architecture:** Nuovo `GapGroupingService` (prompt + validazione pura + chiamata via `AiService`, fallback `null`). `ReportBuilderService` estrae `gapQuestions()`, `actions()` accetta i gruppi opzionali, `persist()` chiama il raggruppamento e conserva stato+scheda per chiave stabile. Nuovo tipo `gap_pending` e colonna `covered_prompts` su `ar_actions`. Report, PDF, scheda e controller imparano il nuovo tipo. Script CLI `rebuild-plan.php` ricalcola il piano senza judge.

**Tech Stack:** PHP 8.3, MySQL (XAMPP), `AiService` (Claude Opus 5.5 via setting `brief_model`), mPDF, Alpine.js. Nessun framework di test: script in `modules/ai-reputation/scripts/` con `PASS/FAIL` ed exit code.

**Spec:** `docs/superpowers/specs/2026-10-09-ai-reputation-raggruppamento-articoli-gap-design.md` · ADR-014 in `modules/ai-reputation/docs/decisions.md`.

**Regole del repo da rispettare:** si lavora nel checkout principale `C:\xampp\htdocs\seo-toolkit` sul branch `claude/ai-reputation-radar-dd9004` (niente worktree). MySQL: `C:/xampp/mysql/bin/mysql -u root seo_toolkit`. PHP: `C:/xampp/php/php.exe` (in Git Bash `/c/xampp/php/php.exe`). Testi UI in italiano, icone Heroicons, prepared statements, `php -l` dopo ogni file PHP toccato. Commit con il trailer `Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>`.

---

### Task 1: Migrazione DB (`gap_pending` + `covered_prompts`)

**Files:**
- Create: `modules/ai-reputation/database/2026-10-09-actions-gap-grouping.sql`

**Step 1: Scrivi la migrazione**

```sql
-- AI Reputation Radar - raggruppamento AI degli articoli gap (ADR-014):
-- gap_pending = domanda scoperta non sostenuta dai fatti confermati; covered_prompts = domande coperte da un intervento
ALTER TABLE ar_actions
    MODIFY COLUMN type ENUM('removal','gap_article','correction','counter_content','gap_pending') NOT NULL,
    ADD COLUMN covered_prompts JSON DEFAULT NULL AFTER target_domain;
```

**Step 2: Applica in locale e verifica**

Run:
```bash
/c/xampp/mysql/bin/mysql -u root seo_toolkit < modules/ai-reputation/database/2026-10-09-actions-gap-grouping.sql && /c/xampp/mysql/bin/mysql -u root seo_toolkit -e "SHOW COLUMNS FROM ar_actions WHERE Field IN ('type','covered_prompts')"
```
Expected: `type` con `'gap_pending'` nell'enum, `covered_prompts` di tipo `longtext` (MariaDB mostra JSON così).

**Step 3: Commit**

```bash
git add modules/ai-reputation/database/2026-10-09-actions-gap-grouping.sql
git commit -m "feat(ai-reputation): migrazione gap_pending + covered_prompts su ar_actions (ADR-014)

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>"
```

---

### Task 2: `GapGroupingService::validate()` (puro, TDD)

**Files:**
- Create: `modules/ai-reputation/scripts/test-gap-grouping-validate.php`
- Create: `modules/ai-reputation/services/GapGroupingService.php`

**Step 1: Scrivi il test che fallisce**

`modules/ai-reputation/scripts/test-gap-grouping-validate.php`:
```php
<?php
// Validazione dell'output AI del raggruppamento gap, senza rete. Run: php modules/ai-reputation/scripts/test-gap-grouping-validate.php
declare(strict_types=1);
define('BASE_PATH', dirname(__DIR__, 3));
require BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/services/ScraperService.php';
require_once BASE_PATH . '/modules/ai-reputation/services/EngineCollectorService.php';
require_once BASE_PATH . '/modules/ai-reputation/services/ReportBuilderService.php';
require_once BASE_PATH . '/modules/ai-reputation/services/ActionPlanPdfService.php';
require_once BASE_PATH . '/modules/ai-reputation/services/ActionBriefService.php';
require_once BASE_PATH . '/modules/ai-reputation/services/GapGroupingService.php';

use Modules\AiReputation\Services\GapGroupingService as G;

$fail = 0;
$check = function (string $name, bool $ok) use (&$fail): void { echo ($ok ? 'PASS' : 'FAIL') . " {$name}\n"; if (!$ok) { $fail++; } };
$ids = [10, 11, 12, 13]; // numero n ↔ prompt_id $ids[n-1]

// Caso buono: 2 articoli + 1 pending, tutte le domande assegnate
$r = G::validate(['articles' => [['title' => 'Roma', 'why' => 'Perché.', 'questions' => [1, 2]], ['title' => 'Italia', 'why' => 'Perché.', 'questions' => [3]]], 'pending' => [['question' => 4, 'needed' => 'un hotel documentato']]], $ids);
$check('2 articoli con prompt_id reali', count($r['articles']) === 2 && $r['articles'][0]['questions'] === [10, 11] && $r['articles'][1]['questions'] === [12]);
$check('pending con prova richiesta', $r['pending'] === [['prompt_id' => 13, 'needed' => 'un hotel documentato']]);

// Numero inventato (9) e doppione (1 in due articoli): scartati, prima occorrenza vince
$r = G::validate(['articles' => [['title' => 'A', 'why' => 'w', 'questions' => [1, 9]], ['title' => 'B', 'why' => 'w', 'questions' => [1, 2]]], 'pending' => []], $ids);
$check('numero inventato scartato', $r['articles'][0]['questions'] === [10]);
$check('doppione: prima occorrenza vince', $r['articles'][1]['questions'] === [11]);
$check('domande dimenticate → pending "da rivedere"', count($r['pending']) === 2 && $r['pending'][0] === ['prompt_id' => 12, 'needed' => G::PENDING_REVIEW] && $r['pending'][1]['prompt_id'] === 13);

// Domanda sia in articolo sia in pending: resta nell'articolo
$r = G::validate(['articles' => [['title' => 'A', 'why' => 'w', 'questions' => [1]]], 'pending' => [['question' => 1, 'needed' => 'x'], ['question' => 2, 'needed' => '']]], $ids);
$check('pending duplicato dell\'articolo ignorato', count(array_filter($r['pending'], fn($p) => $p['prompt_id'] === 10)) === 0);
$check('pending senza needed → "da rivedere"', in_array(['prompt_id' => 11, 'needed' => G::PENDING_REVIEW], $r['pending'], true));

// Articolo senza titolo o senza domande: scartato, le domande vanno in pending
$r = G::validate(['articles' => [['title' => '', 'why' => 'w', 'questions' => [1]], ['title' => 'B', 'why' => 'w', 'questions' => []]], 'pending' => []], $ids);
$check('articolo senza titolo scartato', $r['articles'] === []);
$check('le sue domande in pending', $r['pending'][0] === ['prompt_id' => 10, 'needed' => G::PENDING_REVIEW]);

// Oltre il tetto: i primi MAX_ARTICLES restano, gli altri vanno in pending
$many = range(1, 8);
$arts = array_map(fn($n) => ['title' => "T{$n}", 'why' => 'w', 'questions' => [$n]], $many);
$r = G::validate(['articles' => $arts, 'pending' => []], range(100, 107));
$check('tetto MAX_ARTICLES', count($r['articles']) === G::MAX_ARTICLES && count($r['pending']) === 8 - G::MAX_ARTICLES);

// Numeri come stringhe accettati, tipi strani ignorati
$r = G::validate(['articles' => [['title' => 'A', 'why' => 'w', 'questions' => ['1', 'due', null, 2.5]]], 'pending' => 'no'], $ids);
$check('numeri come stringhe ok, altro ignorato', $r['articles'][0]['questions'] === [10]);

// Troncamenti
$r = G::validate(['articles' => [['title' => str_repeat('t', 600), 'why' => 'w', 'questions' => [1]]], 'pending' => []], $ids);
$check('titolo troncato a 500', mb_strlen($r['articles'][0]['title']) === 500);

// parseJson riusato: fence e testo intorno
$check('parseJson con fence', \Modules\AiReputation\Services\ActionBriefService::parseJson("ecco:\n```json\n{\"articles\":[]}\n```") === ['articles' => []]);

exit($fail ? 1 : 0);
```

**Step 2: Esegui il test per vederlo fallire**

Run: `/c/xampp/php/php.exe modules/ai-reputation/scripts/test-gap-grouping-validate.php`
Expected: errore fatale "Failed opening required .../GapGroupingService.php".

**Step 3: Scrivi il servizio con `validate()` e le costanti**

`modules/ai-reputation/services/GapGroupingService.php`:
```php
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
        foreach (array_values($ids) as $i => $id) {
            if (!isset($used[$i + 1])) {
                $pending[] = ['prompt_id' => (int) $id, 'needed' => self::PENDING_REVIEW];
            }
        }
        return ['articles' => $articles, 'pending' => $pending];
    }
}
```

Nota sull'ordine dei `pending`: prima quelli dichiarati dall'AI, poi l'overflow, poi le dimenticate. Nel test "articolo senza titolo" il primo pending e' l'overflow (prompt 10) perche' l'AI non ne ha dichiarati: coerente.

**Step 4: Esegui il test**

Run: `/c/xampp/php/php.exe -l modules/ai-reputation/services/GapGroupingService.php && /c/xampp/php/php.exe modules/ai-reputation/scripts/test-gap-grouping-validate.php`
Expected: tutte le righe `PASS`, exit code 0. Se "doppione: prima occorrenza vince" fallisce, controlla che `$toId` segni `$used` anche quando il numero viene da un articolo poi scartato (e' voluto: la domanda finisce in overflow).

**Step 5: Commit**

```bash
git add modules/ai-reputation/services/GapGroupingService.php modules/ai-reputation/scripts/test-gap-grouping-validate.php
git commit -m "feat(ai-reputation): GapGroupingService::validate — numeri→prompt_id, doppioni, tetto, domande dimenticate in sospeso

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>"
```

---

### Task 3: Prompt e chiamata AI (`userPrompt()`, `systemPrompt()`, `group()`)

**Files:**
- Modify: `modules/ai-reputation/services/GapGroupingService.php`
- Modify: `modules/ai-reputation/scripts/test-gap-grouping-validate.php` (aggiungi check sul prompt)

**Step 1: Aggiungi il test del prompt (fallisce)**

Appendi prima di `exit(...)` nel test:
```php
// Prompt: domande numerate nell'ordine di $gapByPrompt, competitor, fatti, omonimi, niente prompt_id reali
$gap = [10 => ['prompt' => 'Chi sono i migliori a Roma?', 'competitors' => ['Tizio' => true, 'Caio' => true]], 11 => ['prompt' => 'Who are the best in Rome?', 'competitors' => []]];
$blocks = ['citable' => ['[bio] Imprenditore immobiliare romano (fonte: profilo confermato dal cliente)'], 'homonyms' => ['Uno sciatore con lo stesso nome'], 'risks' => [], 'sources' => []];
$p = G::userPrompt(['subject_name' => 'Mario Rossi', 'subject_type' => 'persona', 'city' => 'Roma', 'disambiguation_notes' => ''], $gap, $blocks);
$check('prompt: domande numerate 1 e 2', str_contains($p, '1. "Chi sono i migliori a Roma?" → citano: Tizio, Caio') && str_contains($p, '2. "Who are the best in Rome?"'));
$check('prompt: fatti e omonimi', str_contains($p, 'Imprenditore immobiliare romano') && str_contains($p, 'Uno sciatore'));
$check('prompt: nessun prompt_id reale', !preg_match('/\b1[01]\b/', $p));
$check('prompt: formato JSON richiesto', str_contains($p, '"articles"') && str_contains($p, '"pending"'));
$check('system: solo JSON, italiano, niente costi', str_contains(G::systemPrompt(), 'JSON') && str_contains(G::systemPrompt(), 'italiano') && str_contains(G::systemPrompt(), 'costi'));
```

**Step 2: Esegui → fallisce** con "Call to undefined method userPrompt".

**Step 3: Implementa prompt e `group()`**

Aggiungi alla classe (dopo `model()`):
```php
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
```

**Step 4: Esegui il test**

Run: `/c/xampp/php/php.exe -l modules/ai-reputation/services/GapGroupingService.php && /c/xampp/php/php.exe modules/ai-reputation/scripts/test-gap-grouping-validate.php`
Expected: tutto `PASS`. Il check "nessun prompt_id reale" controlla che i numeri 10/11 non compaiano da soli: se fallisce, verifica che il prompt usi `$n` e non le chiavi.

**Step 5: Commit**

```bash
git add modules/ai-reputation/services/GapGroupingService.php modules/ai-reputation/scripts/test-gap-grouping-validate.php
git commit -m "feat(ai-reputation): GapGroupingService — prompt numerato, chiamata via AiService (brief_model, effort medium), fallback null

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>"
```

---

### Task 4: `ReportBuilderService` — `gapQuestions()`, `coveredPrompts()`, `actions()` con gruppi (TDD)

**Files:**
- Create: `modules/ai-reputation/scripts/test-gap-grouping-actions.php`
- Modify: `modules/ai-reputation/services/ReportBuilderService.php:249-390` (`actions()`), aggiungi metodi statici

**Step 1: Scrivi il test che fallisce**

`modules/ai-reputation/scripts/test-gap-grouping-actions.php`:
```php
<?php
// Piano d'azione con e senza raggruppamento AI, su dati finti e senza DB. Run: php modules/ai-reputation/scripts/test-gap-grouping-actions.php
declare(strict_types=1);
define('BASE_PATH', dirname(__DIR__, 3));
require BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/services/ScraperService.php';
require_once BASE_PATH . '/modules/ai-reputation/services/EngineCollectorService.php';
require_once BASE_PATH . '/modules/ai-reputation/services/ReportBuilderService.php';

use Modules\AiReputation\Services\ReportBuilderService as R;

$fail = 0;
$check = function (string $name, bool $ok) use (&$fail): void { echo ($ok ? 'PASS' : 'FAIL') . " {$name}\n"; if (!$ok) { $fail++; } };

$project = ['id' => 1, 'subject_name' => 'Mario Rossi', 'website' => 'https://mariorossi.it'];
$mk = fn(int $id, int $pid, string $text, string $cluster) => ['id' => $id, 'engine' => 'openai', 'prompt_id' => $pid, 'prompt_text' => $text, 'prompt_cluster' => $cluster, 'citations' => []];
$responses = [
    $mk(1, 10, 'Chi sono i migliori a Roma?', 'comm'),
    $mk(2, 11, 'Who are the best in Rome?', 'comm'),
    $mk(3, 12, 'Esperti di hotel di lusso?', 'comp'),
    $mk(4, 13, 'Mario Rossi è affidabile?', 'rep'),
    $mk(5, 14, 'Chi è il leader del settore?', 'comm'),
];
$an = fn(array $o = []) => $o + ['negative_urls' => [], 'negative' => 0, 'is_homonym' => 'no', 'brand_mentioned' => 0, 'competitors' => ['Tizio', 'Caio'], 'verdict' => 'not_mentioned', 'summary' => ''];
$analyses = [1 => $an(), 2 => $an(), 3 => $an(['competitors' => ['Sempronio']]), 4 => $an(['brand_mentioned' => 1, 'competitors' => []]), 5 => $an(['competitors' => []])];
$sources = [['status' => 'ok', 'domain' => 'italiaoggi.it', 'count' => 3]];
$labels = ['openai' => 'ChatGPT (API)'];
$svc = new R();

$gap = $svc->gapQuestions($responses, $analyses, $labels);
$check('gapQuestions: solo comm/comp non citate con competitor', array_keys($gap) === [10, 11, 12]);
$check('gapQuestions: testo e competitor', $gap[10]['prompt'] === 'Chi sono i migliori a Roma?' && array_keys($gap[12]['competitors']) === ['Sempronio']);

// Senza gruppi: una riga per domanda (comportamento attuale) + covered_prompts della singola domanda
$acts = $svc->actions($project, $responses, $analyses, $sources, $labels);
$gaps = array_values(array_filter($acts, fn($a) => $a['type'] === 'gap_article'));
$check('fallback: 3 articoli "Non citato"', count($gaps) === 3 && str_starts_with($gaps[0]['title'], 'Non citato: "Chi sono i migliori a Roma?"'));
$check('fallback: covered_prompts della domanda', R::coveredPrompts($gaps[0]) === [['id' => 10, 'text' => 'Chi sono i migliori a Roma?']]);
$check('fallback: nessun gap_pending', !array_filter($acts, fn($a) => $a['type'] === 'gap_pending'));

// Con gruppi: articoli con titolo AI e domande coperte, pending con prova richiesta, id sconosciuti ignorati
$groups = ['articles' => [['title' => 'Il real estate a Roma', 'why' => 'Le AI citano altri.', 'questions' => [10, 11, 99]], ['title' => 'Vuoto', 'why' => 'w', 'questions' => [99]]],
    'pending' => [['prompt_id' => 12, 'needed' => 'un hotel documentato'], ['prompt_id' => 98, 'needed' => 'x']]];
$acts = $svc->actions($project, $responses, $analyses, $sources, $labels, $groups);
$gaps = array_values(array_filter($acts, fn($a) => $a['type'] === 'gap_article'));
$pend = array_values(array_filter($acts, fn($a) => $a['type'] === 'gap_pending'));
$check('gruppi: 1 articolo (quello con solo id sconosciuti sparisce)', count($gaps) === 1 && $gaps[0]['title'] === 'Il real estate a Roma');
$check('gruppi: rationale con "Copre 2 domande"', str_contains($gaps[0]['rationale'], 'Le AI citano altri.') && str_contains($gaps[0]['rationale'], 'Copre 2 domande: "Chi sono i migliori a Roma?", "Who are the best in Rome?"'));
$check('gruppi: covered_prompts con 2 domande', array_column(R::coveredPrompts($gaps[0]), 'id') === [10, 11]);
$check('gruppi: testata suggerita', $gaps[0]['target_domain'] === 'italiaoggi.it');
$check('gruppi: 1 pending (id sconosciuto ignorato)', count($pend) === 1 && $pend[0]['title'] === 'Esperti di hotel di lusso?');
$check('gruppi: rationale del pending', $pend[0]['rationale'] === 'Serve una prova dal cliente: un hotel documentato');
$check('gruppi: pending con covered_prompts', R::coveredPrompts($pend[0]) === [['id' => 12, 'text' => 'Esperti di hotel di lusso?']]);
$check('gruppi: articoli prima dei pending', array_search($gaps[0], $acts, true) < array_search($pend[0], $acts, true));

// Chiave stabile tra ricalcoli
$check('actionKey: gap per insieme di domande, ordine indifferente', R::actionKey(['type' => 'gap_article', 'covered_prompts' => json_encode([['id' => 11], ['id' => 10]])]) === 'gap_article|10,11');
$check('actionKey: pending', R::actionKey(['type' => 'gap_pending', 'covered_prompts' => json_encode([['id' => 12]])]) === 'gap_pending|12');
$check('actionKey: rimozione per tipo|url|titolo', R::actionKey(['type' => 'removal', 'target_url' => 'https://x.it/a', 'title' => 'T']) === 'removal|https://x.it/a|T');

exit($fail ? 1 : 0);
```

**Step 2: Esegui → fallisce** con "Call to undefined method gapQuestions".

**Step 3: Implementa**

In `ReportBuilderService.php`:

(a) Cambia la firma di `actions()` e togli il blocco gap dal loop (righe ~289-295, il blocco `if (in_array($r['prompt_cluster'], ['comm', 'comp'], true) && ...)`), sostituendolo con una chiamata subito dopo il loop:

```php
    public function actions(array $project, array $responses, array $analyses, array $sources, array $engineLabels, ?array $gapGroups = null): array
    {
        ...
        $seenUrl = [];
        $repNegative = [];
        foreach ($responses as $r) {
            ... (removal + repNegative invariati; il blocco gap NON c'e' piu')
        }
        $gapByPrompt = $this->gapQuestions($responses, $analyses, $engineLabels);
```

(b) Sostituisci la sezione `// 3. gap_article` con:

```php
        // 3. gap_article: domande comm/comp senza menzione ma con competitor.
        //    Con $gapGroups (ADR-014) l'AI le ha gia' raggruppate in articoli + domande in sospeso (gap_pending);
        //    senza (chiamata fallita o non prevista) resta una riga per domanda.
        $covered = fn(array $list) => json_encode($list, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($gapGroups !== null) {
            foreach ($gapGroups['articles'] as $art) {
                $qs = [];
                foreach ($art['questions'] as $pid) {
                    if (isset($gapByPrompt[$pid])) {
                        $qs[] = ['id' => (int) $pid, 'text' => $gapByPrompt[$pid]['prompt']];
                    }
                }
                if (!$qs) {
                    continue;
                }
                $texts = array_column($qs, 'text');
                $examples = array_slice($texts, 0, 3);
                $more = count($texts) - count($examples);
                $actions[] = [
                    'type' => 'gap_article',
                    'target_url' => null,
                    'target_domain' => $suggested,
                    'title' => $art['title'],
                    'rationale' => $art['why'] . ' Copre ' . count($texts) . ' domand' . (count($texts) === 1 ? 'a' : 'e') . ': "' . implode('", "', $examples) . '"' . ($more > 0 ? " e altre {$more}" : '') . '.',
                    'covered_prompts' => $covered($qs),
                ];
            }
            foreach ($gapGroups['pending'] as $p) {
                $pid = (int) $p['prompt_id'];
                if (!isset($gapByPrompt[$pid])) {
                    continue;
                }
                $actions[] = [
                    'type' => 'gap_pending',
                    'target_url' => null,
                    'target_domain' => null,
                    'title' => $gapByPrompt[$pid]['prompt'],
                    'rationale' => 'Serve una prova dal cliente: ' . $p['needed'],
                    'covered_prompts' => $covered([['id' => $pid, 'text' => $gapByPrompt[$pid]['prompt']]]),
                ];
            }
        } else {
            foreach ($gapByPrompt as $pid => $info) {
                $names = array_slice(array_keys($info['competitors']), 0, 6);
                $actions[] = [
                    'type' => 'gap_article',
                    'target_url' => null,
                    'target_domain' => $suggested,
                    'title' => 'Non citato: "' . $info['prompt'] . '"',
                    'rationale' => 'Le AI citano ' . implode(', ', $names) . ' e non il soggetto. Serve un contenuto che posizioni il soggetto su questa domanda'
                        . ($suggested ? ", su {$suggested} o testata equivalente." : '.'),
                    'covered_prompts' => $covered([['id' => (int) $pid, 'text' => $info['prompt']]]),
                ];
            }
        }
        return $actions;
    }
```

(c) Aggiungi i metodi (prima di `engineNames`):

```php
    /**
     * Domande commerciali/di settore in cui il soggetto non e' citato ma lo sono dei competitor (ADR-014: l'AI le
     * raggruppa in articoli). prompt_id => ['prompt' => testo, 'competitors' => [nome => true], 'engines' => [label => true]]
     */
    public function gapQuestions(array $responses, array $analyses, array $engineLabels): array
    {
        $gap = [];
        foreach ($responses as $r) {
            $a = $analyses[(int) $r['id']] ?? null;
            if (!$a || !in_array($r['prompt_cluster'], ['comm', 'comp'], true) || (int) $a['brand_mentioned'] !== 0 || empty($a['competitors'])) {
                continue;
            }
            $pid = (int) $r['prompt_id'];
            $gap[$pid]['prompt'] = $r['prompt_text'];
            foreach ($a['competitors'] as $name) {
                $gap[$pid]['competitors'][$name] = true;
            }
            $gap[$pid]['engines'][$engineLabels[$r['engine']] ?? $r['engine']] = true;
        }
        return $gap;
    }

    /** Domande coperte da un intervento (colonna covered_prompts, JSON): [['id' => int, 'text' => string], ...]. */
    public static function coveredPrompts(array $a): array
    {
        $raw = $a['covered_prompts'] ?? null;
        $list = is_string($raw) ? json_decode($raw, true) : $raw;
        $out = [];
        foreach ((array) $list as $c) {
            if (is_array($c) && isset($c['id'])) {
                $out[] = ['id' => (int) $c['id'], 'text' => (string) ($c['text'] ?? '')];
            }
        }
        return $out;
    }

    /**
     * Chiave di un intervento tra un ricalcolo e l'altro (conserva stato e scheda): per gli articoli gap e le domande
     * in sospeso l'insieme delle domande coperte (il titolo lo inventa l'AI e cambia), per gli altri tipo|url|titolo.
     */
    public static function actionKey(array $a): string
    {
        if (in_array($a['type'] ?? '', ['gap_article', 'gap_pending'], true)) {
            $ids = array_column(self::coveredPrompts($a), 'id');
            sort($ids);
            return $a['type'] . '|' . implode(',', $ids);
        }
        return ($a['type'] ?? '') . '|' . ($a['target_url'] ?? '') . '|' . ($a['title'] ?? '');
    }
```

**Step 4: Esegui test nuovi e vecchi**

Run:
```bash
/c/xampp/php/php.exe -l modules/ai-reputation/services/ReportBuilderService.php && /c/xampp/php/php.exe modules/ai-reputation/scripts/test-gap-grouping-actions.php && /c/xampp/php/php.exe modules/ai-reputation/scripts/test-action-brief-validate.php && /c/xampp/php/php.exe modules/ai-reputation/scripts/test-action-plan-pdf.php
```
Expected: tutto `PASS`, exit 0 per ciascuno. Nota: `gapQuestions()` ora salta anche le domande il cui `prompt_id` ha engines diversi? No: accumula per `prompt_id` come prima. Se "gapQuestions: solo comm/comp" fallisce con chiavi stringa, e' perche' `prompt_id` nel fixture e' int: ok cosi'.

**Step 5: Commit**

```bash
git add modules/ai-reputation/services/ReportBuilderService.php modules/ai-reputation/scripts/test-gap-grouping-actions.php
git commit -m "feat(ai-reputation): actions() con gruppi AI — gap_article raggruppati, gap_pending, covered_prompts, chiave stabile

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>"
```

---

### Task 5: `persist()` — raggruppamento a fine run e conservazione di stato + scheda

**Files:**
- Modify: `modules/ai-reputation/services/ReportBuilderService.php:470-492` (`persist()`)

**Step 1: Modifica `persist()`**

Sostituisci l'inizio e il blocco azioni:

```php
    public function persist(int $runId, int $projectId, array $project, array $responses, array $analyses, array $engineLabels): array
    {
        $sources = $this->sources($responses, $analyses);
        $competitors = $this->competitors($analyses);
        // ADR-014: le domande scoperte le raggruppa l'AI (una chiamata); se fallisce, actions() mette una riga per domanda
        $gapByPrompt = $this->gapQuestions($responses, $analyses, $engineLabels);
        $gapGroups = $gapByPrompt ? (new GapGroupingService())->group($project, $gapByPrompt, (int) $project['user_id']) : null;
        $actions = $this->actions($project, $responses, $analyses, $sources, $engineLabels, $gapGroups);

        // Fonti e competitor del progetto: ricalcolati da tutti i run (idempotente: rianalizzare non raddoppia)
        $this->rebuildProjectAggregates($projectId);

        // Azioni: si rigenerano, ma lo stato deciso dall'utente (accettata, fatta, scartata) E la scheda operativa
        // gia' generata si conservano, per chiave stabile (actionKey)
        $keep = ['status', 'channel', 'channel_rationale', 'suggested_outlets', 'brief', 'brief_model', 'brief_generated_at', 'brief_error'];
        $previous = [];
        foreach (Database::fetchAll("SELECT type, target_url, title, covered_prompts, " . implode(', ', $keep) . " FROM ar_actions WHERE run_id = ?", [$runId]) as $old) {
            $previous[self::actionKey($old)] = array_intersect_key($old, array_flip($keep));
        }
        Database::delete('ar_actions', 'run_id = ?', [$runId]);
        foreach ($actions as $a) {
            $a['title'] = mb_substr((string) $a['title'], 0, 500);
            $a['target_url'] = $a['target_url'] !== null ? mb_substr((string) $a['target_url'], 0, 2000) : null;
            $a['target_urls'] = $a['target_urls'] ?? null;
            $a['covered_prompts'] = $a['covered_prompts'] ?? null;
            $kept = $previous[self::actionKey($a)] ?? ['status' => 'proposed'];
            Database::insert('ar_actions', array_merge($a, $kept, ['project_id' => $projectId, 'run_id' => $runId]));
        }
```
Il resto (omonimi, return) resta uguale. `GapGroupingService` e' nello stesso namespace: nessun `use` da aggiungere.

**Step 2: Sintassi**

Run: `/c/xampp/php/php.exe -l modules/ai-reputation/services/ReportBuilderService.php`
Expected: `No syntax errors`.

**Step 3: Prova di conservazione senza AI (DB locale, nessuna chiamata)**

Il run 4 ha 2 schede. Verifica che un `persist()` con raggruppamento fallito (es. modello inesistente) le conservi. Script usa-e-getta nello scratchpad (NON nel repo):

```php
<?php
require 'C:/xampp/htdocs/seo-toolkit/cron/bootstrap.php';
use Core\Database; use Modules\AiReputation\Models\{Analysis, Project, Run}; use Modules\AiReputation\Services\ReportBuilderService;
$before = Database::fetchColumn("SELECT COUNT(*) FROM ar_actions WHERE run_id = 4 AND brief IS NOT NULL");
Database::execute("UPDATE module_settings SET value = 'modello-inesistente' WHERE module_slug = 'ai-reputation' AND setting_key = 'brief_model'"); // se la tabella/colonne hanno nomi diversi, cerca con: SHOW TABLES LIKE '%module%setting%'
$p = Database::fetch("SELECT * FROM ar_projects WHERE id = 1");
(new ReportBuilderService())->persist(4, 1, $p, (new Run())->responses(4), (new Analysis())->byRun(4), Project::ENGINE_LABELS);
Database::execute("UPDATE module_settings SET value = 'claude-opus-5-5' WHERE module_slug = 'ai-reputation' AND setting_key = 'brief_model'");
$after = Database::fetchColumn("SELECT COUNT(*) FROM ar_actions WHERE run_id = 4 AND brief IS NOT NULL");
echo "schede prima: {$before}, dopo: {$after}\n";
```
Run: `/c/xampp/php/php.exe <scratchpad>/persist-keep.php`
Expected: `schede prima: 2, dopo: 2` (fallback a una riga per domanda: la chiave `gap_article|<id>` coincide con la precedente perche' anche il fallback scrive `covered_prompts`). Se prima della modifica la riga gap non aveva `covered_prompts` (NULL, righe create prima di oggi), la chiave vecchia e' `gap_article|` e la scheda dell'articolo gap si perde una volta sola: atteso `dopo: 1`. Riporta il numero reale a Clemente. Controlla anche il log: `tail -5 storage/logs/ai-reputation*.log` (o dove scrive `Logger::channel`) deve contenere "Raggruppamento gap fallito".
**Ripristina sempre `brief_model`** anche se lo script esplode (controlla con `SELECT ... WHERE setting_key='brief_model'`).

**Step 4: Commit**

```bash
git add modules/ai-reputation/services/ReportBuilderService.php
git commit -m "feat(ai-reputation): persist() raggruppa le domande gap via AI e conserva stato e scheda per chiave stabile

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>"
```

---

### Task 6: Scheda operativa — domande coperte per prime, niente scheda per `gap_pending`

**Files:**
- Modify: `modules/ai-reputation/services/ActionBriefService.php:143-150` (loop domande) e `:250-265` (`orderQuestions`)
- Modify: `modules/ai-reputation/controllers/RunController.php:661-666` (dopo il check removal)
- Modify: `modules/ai-reputation/scripts/test-action-brief-validate.php` (check su `orderQuestions`)

**Step 1: Test che fallisce** — appendi prima di `exit` in `test-action-brief-validate.php`:
```php
$qs = [['id' => 1, 'text' => 'alfa'], ['id' => 2, 'text' => 'beta'], ['id' => 3, 'text' => 'gamma']];
$o = S::orderQuestions($qs, 'Titolo AI qualsiasi', [3, 2]);
$check('orderQuestions: domande coperte per prime', array_column($o, 'id') === [2, 3, 1]);
$o = S::orderQuestions($qs, 'Non citato: "gamma"');
$check('orderQuestions: senza ids resta il match sul titolo', array_column($o, 'id') === [3, 1, 2]);
```
Run: `/c/xampp/php/php.exe modules/ai-reputation/scripts/test-action-brief-validate.php` → FAIL (il primo check: ids ignorati).

**Step 2: Implementa**

In `dossier()`, nel loop che riempie `$questions`, aggiungi l'id:
```php
            if ($hit) {
                $q = &$questions[(int) $r['prompt_id']];
                $q['id'] = (int) $r['prompt_id'];
                $q['text'] = $r['prompt_text'];
```
e la chiamata diventa:
```php
        $questions = self::orderQuestions(array_values($questions), (string) ($action['title'] ?? ''), array_column(ReportBuilderService::coveredPrompts($action), 'id'));
```
`orderQuestions`:
```php
    /**
     * Domande collegate: prima quelle coperte dall'intervento (covered_prompts) o il cui testo compare nel titolo
     * (per i gap non raggruppati il titolo contiene la domanda), poi le altre; massimo 10.
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
```

Nel controller `generateBrief()`, dopo il blocco `if ($action['type'] === 'removal' && !ActionPlanPdfService::pages($action))`:
```php
        if ($action['type'] === 'gap_pending') {
            ob_end_clean();
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'Domanda in sospeso: serve prima una prova dal cliente']);
            exit;
        }
```

**Step 3: Verifica**

Run: `/c/xampp/php/php.exe -l modules/ai-reputation/services/ActionBriefService.php && /c/xampp/php/php.exe -l modules/ai-reputation/controllers/RunController.php && /c/xampp/php/php.exe modules/ai-reputation/scripts/test-action-brief-validate.php`
Expected: tutto `PASS`.

**Step 4: Commit**

```bash
git add modules/ai-reputation/services/ActionBriefService.php modules/ai-reputation/controllers/RunController.php modules/ai-reputation/scripts/test-action-brief-validate.php
git commit -m "feat(ai-reputation): scheda — domande coperte per prime nel dossier, 422 per le domande in sospeso

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>"
```

---

### Task 7: Report web — blocco "Da valutare", domande coperte, niente pulsante per `gap_pending`

**Files:**
- Modify: `modules/ai-reputation/views/runs/show.php:41-42` (etichette), `:125-127` (liste), `:145-166` (`$actionRow`), `:177-186` (colonna "Da pubblicare")
- Modify: `modules/ai-reputation/controllers/RunController.php:542,609` (ORDER BY)

**Step 1: Etichette e liste**

Riga 41-42: aggiungi `'gap_pending' => 'da valutare'` a `$actionLabel` e `'gap_pending' => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'` a `$actionClass`.

Righe 125-126:
```php
    $removals = array_values(array_filter($actions, fn($a) => $a['type'] === 'removal'));
    $pendings = array_values(array_filter($actions, fn($a) => $a['type'] === 'gap_pending'));
    $writes = array_values(array_filter($actions, fn($a) => !in_array($a['type'], ['removal', 'gap_pending'], true)));
```

**Step 2: `$actionRow`** — dopo il `<p>` della rationale e prima del loop `$urls`:
```php
                <?php $covered = \Modules\AiReputation\Services\ReportBuilderService::coveredPrompts($a); ?>
                <?php if (count($covered) > 1): ?>
                <div><span class="font-medium text-slate-500 dark:text-slate-400">Domande coperte:</span>
                    <ul class="list-disc ml-4 mt-0.5 text-slate-600 dark:text-slate-300"><?php foreach ($covered as $c): ?><li><?= e($c['text']) ?></li><?php endforeach; ?></ul>
                </div>
                <?php endif; ?>
```
e la riga del partial diventa:
```php
                <?php if ($a['type'] !== 'gap_pending'): ?>
                <?= \Core\View::partial('ai-reputation::partials/action-brief', [...invariato...]) ?>
                <?php endif; ?>
```

**Step 3: Blocco "Da valutare"** — nella colonna "Da pubblicare", dopo la `</ul>` dei `$writes`:
```php
                <?php if ($pendings): ?>
                <p class="px-5 pt-4 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Da valutare: serve una prova dal cliente</p>
                <ul class="divide-y divide-slate-100 dark:divide-slate-700/60">
                    <?php foreach ($pendings as $a): ?><?= $actionRow($a) ?><?php endforeach; ?>
                </ul>
                <?php endif; ?>
```

**Step 4: ORDER BY** — in `RunController.php` righe 542 e 609, `FIELD(type, 'removal', 'counter_content', 'gap_article', 'correction', 'gap_pending')`.

**Step 5: Verifica**

Run: `/c/xampp/php/php.exe -l modules/ai-reputation/views/runs/show.php && /c/xampp/php/php.exe -l modules/ai-reputation/controllers/RunController.php`
Poi apri `http://localhost/seo-toolkit/ai-reputation/project/1/runs/4` nel browser (login `admin@seo-toolkit.local` / `admin123`): la pagina deve renderizzare come prima (nessun pending ancora: il blocco non compare). Verifica in console che non ci siano errori 500.

**Step 6: Commit**

```bash
git add modules/ai-reputation/views/runs/show.php modules/ai-reputation/controllers/RunController.php
git commit -m "feat(ai-reputation): report — blocco 'Da valutare', domande coperte nel dettaglio, niente scheda per gap_pending

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>"
```

---

### Task 8: PDF — sezione "Da valutare", domande coperte, conteggi

**Files:**
- Modify: `modules/ai-reputation/services/ActionPlanPdfService.php:14,20-38`
- Modify: `modules/ai-reputation/views/pdf/action-plan.php`
- Modify: `modules/ai-reputation/scripts/test-action-plan-pdf.php`

**Step 1: Test che fallisce** — in `test-action-plan-pdf.php` aggiungi al fixture `$actions`:
```php
    ['id' => 6, 'type' => 'gap_article', 'status' => 'proposed', 'title' => 'Il real estate a Roma', 'rationale' => 'Copre 2 domande.', 'target_url' => null, 'target_urls' => null, 'target_domain' => 'wired.it', 'channel' => null, 'brief' => null, 'brief_error' => null,
     'covered_prompts' => json_encode([['id' => 10, 'text' => 'Chi sono i migliori a Roma?'], ['id' => 11, 'text' => 'Who are the best in Rome?']])],
    ['id' => 7, 'type' => 'gap_pending', 'status' => 'proposed', 'title' => 'Esperti di hotel di lusso?', 'rationale' => 'Serve una prova dal cliente: un hotel documentato', 'target_url' => null, 'target_urls' => null, 'target_domain' => null, 'channel' => null, 'brief' => null, 'brief_error' => null,
     'covered_prompts' => json_encode([['id' => 12, 'text' => 'Esperti di hotel di lusso?']])],
```
e i check (aggiorna anche i due esistenti):
```php
$check('conteggi', str_contains($html, '3 contenut') && str_contains($html, '2 sit'));           // era '2 contenut': il pending non conta
$check('scheda non generata', substr_count($html, 'Scheda operativa non generata') === 3);        // era 2: +1 per l'articolo raggruppato, 0 per il pending
$check('domande coperte elencate', str_contains($html, 'Who are the best in Rome?'));
$check('sezione Da valutare dopo i contenuti e prima delle rimozioni', strpos($html, 'Da valutare') > strpos($html, 'Il real estate a Roma') && strpos($html, 'Da valutare') < strpos($html, 'Fonte negativa'));
$check('pending: prova richiesta, nessuna scheda', str_contains($html, 'un hotel documentato') && substr_count($html, 'Esperti di hotel di lusso?') === 1);
```
Run: `/c/xampp/php/php.exe modules/ai-reputation/scripts/test-action-plan-pdf.php` → FAIL sui nuovi check e su "conteggi".

**Step 2: Implementa**

`ActionPlanPdfService.php`:
```php
    public const TYPE_LABELS = ['removal' => 'Rimozione', 'counter_content' => 'Contro-contenuto', 'gap_article' => 'Articolo gap', 'correction' => 'Correzione', 'gap_pending' => 'Da valutare'];
```
in `html()`:
```php
        $contents = array_values(array_filter($actions, fn($a) => !in_array($a['type'], ['removal', 'gap_pending'], true)));
        $pendings = array_values(array_filter($actions, fn($a) => $a['type'] === 'gap_pending'));
        $removals = array_values(array_filter($actions, fn($a) => $a['type'] === 'removal'));
        foreach ([&$contents, &$pendings, &$removals] as &$list) {
            foreach ($list as &$a) {
                $a['pages'] = self::pages($a);
                $a['covered'] = ReportBuilderService::coveredPrompts($a);
                ...
```
`$pendings` e' visibile nel template perche' `include` condivide lo scope.

`views/pdf/action-plan.php`:
- `$typeBadge`: `$type === 'gap_pending'` → sfondo `#f1f5f9`, testo `#475569`.
- in `$renderAction`, dopo la rationale:
```php
        <?php if (count($a['covered'] ?? []) > 1): ?>
        <div style="font-size:9pt;color:#475569;margin-bottom:4pt;"><span style="font-weight:bold;">Domande coperte:</span>
            <?php foreach ($a['covered'] as $c): ?><div>• <?= $h($c['text']) ?></div><?php endforeach; ?>
        </div>
        <?php endif; ?>
```
- la riga "Scheda operativa non generata" solo se `$a['type'] !== 'gap_pending'`:
```php
        <?php if ($a['type'] === 'gap_pending'): ?>
        <?php elseif (empty($a['brief_data'])): ?>
        <div ...>Scheda operativa non generata</div>
        <?php else: ?>
```
- tra contenuti e rimozioni:
```php
    <?php if ($pendings): ?>
    <?php foreach ($pendings as $i => $a) { $renderAction($a, $i === 0 ? $sectionTitle('Da valutare: serve una prova dal cliente', '10pt 0 6pt') : null); } ?>
    <?php endif; ?>
```
- il check "nessun intervento": `if (!$contents && !$pendings && !$removals)`.

**Step 3: Verifica**

Run: `/c/xampp/php/php.exe -l modules/ai-reputation/services/ActionPlanPdfService.php && /c/xampp/php/php.exe -l modules/ai-reputation/views/pdf/action-plan.php && /c/xampp/php/php.exe modules/ai-reputation/scripts/test-action-plan-pdf.php`
Expected: tutto `PASS`. Apri `storage/cache/test-action-plan.pdf` (Read) e controlla a occhio il blocco "Da valutare".

**Step 4: Commit**

```bash
git add modules/ai-reputation/services/ActionPlanPdfService.php modules/ai-reputation/views/pdf/action-plan.php modules/ai-reputation/scripts/test-action-plan-pdf.php
git commit -m "feat(ai-reputation): PDF — sezione 'Da valutare', domande coperte, pending fuori dal conteggio contenuti

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>"
```

---

### Task 9: SSE — evento di progresso prima del raggruppamento

**Files:**
- Modify: `modules/ai-reputation/controllers/RunController.php:357-361` (blocco `// ---------- REPORT`)

**Step 1: Aggiungi l'evento**

Prima di `$built = (new ReportBuilderService())->persist(...)`:
```php
        // ADR-014: il raggruppamento AI degli articoli dura 20-40 s; la UI mostra "engine · prompt" dell'evento progress
        $sendEvent('progress', ['run_id' => $runId, 'engine' => 'Piano', 'prompt' => 'Raggruppamento degli articoli…']);
```
(In `views/dashboard/index.php:123` l'engine passa da `labels[engine] || engine`: "Piano" si legge bene.)

**Step 2: Verifica** — `/c/xampp/php/php.exe -l modules/ai-reputation/controllers/RunController.php`. Nessun run in diretta (costa ~2,7 $): basta la sintassi; il flusso completo si vedra' al prossimo run vero.

**Step 3: Commit**

```bash
git add modules/ai-reputation/controllers/RunController.php
git commit -m "feat(ai-reputation): evento progress 'Raggruppamento degli articoli' prima del piano

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>"
```

---

### Task 10: `scripts/rebuild-plan.php` + prova reale sul run 4

**Files:**
- Create: `modules/ai-reputation/scripts/rebuild-plan.php`

**Step 1: Scrivi lo script**

```php
<?php
// Ricalcola il piano degli interventi di un run SENZA rifare il judge (ADR-014): usa risposte e verdetti gia' salvati,
// rifa' rimozioni, contro-contenuti e il raggruppamento AI degli articoli gap (una chiamata, ~0,05 $).
// Stato e schede gia' generate si conservano (chiave stabile). Run: php modules/ai-reputation/scripts/rebuild-plan.php <runId>
if (php_sapi_name() !== 'cli') {
    die('Solo CLI');
}
require_once dirname(__DIR__, 3) . '/cron/bootstrap.php';

use Core\Database;
use Modules\AiReputation\Models\Analysis;
use Modules\AiReputation\Models\Project;
use Modules\AiReputation\Models\Run;
use Modules\AiReputation\Services\ReportBuilderService;

$runId = (int) ($argv[1] ?? 0);
if ($runId <= 0) {
    fwrite(STDERR, "Uso: php modules/ai-reputation/scripts/rebuild-plan.php <runId>\n");
    exit(1);
}
$run = (new Run())->find($runId);
if (!$run) {
    fwrite(STDERR, "Run {$runId} non trovato\n");
    exit(1);
}
$project = Database::fetch("SELECT * FROM ar_projects WHERE id = ?", [(int) $run['project_id']]);
$responses = (new Run())->responses($runId);
$analyses = (new Analysis())->byRun($runId);
$briefsBefore = (int) Database::fetchColumn("SELECT COUNT(*) FROM ar_actions WHERE run_id = ? AND brief IS NOT NULL", [$runId]);
$t = microtime(true);
$built = (new ReportBuilderService())->persist($runId, (int) $run['project_id'], $project, $responses, $analyses, Project::ENGINE_LABELS);
$briefsAfter = (int) Database::fetchColumn("SELECT COUNT(*) FROM ar_actions WHERE run_id = ? AND brief IS NOT NULL", [$runId]);
$counts = [];
foreach ($built['actions'] as $a) {
    $counts[$a['type']] = ($counts[$a['type']] ?? 0) + 1;
}
printf("Run %d: piano ricalcolato in %.1fs — %s — schede conservate: %d su %d\n", $runId, microtime(true) - $t, json_encode($counts), $briefsAfter, $briefsBefore);
foreach ($built['actions'] as $a) {
    if (in_array($a['type'], ['gap_article', 'gap_pending'], true)) {
        echo "  [{$a['type']}] {$a['title']}\n";
    }
}
```

**Step 2: Esegui sul run 4** (chiamata AI vera, ~0,05 $; MySQL acceso)

Run: `/c/xampp/php/php.exe -l modules/ai-reputation/scripts/rebuild-plan.php && /c/xampp/php/php.exe modules/ai-reputation/scripts/rebuild-plan.php 4`
Expected: righe tipo `[gap_article] <titolo AI>` (2-3) e `[gap_pending] <domanda sugli hotel>` (1-2), `schede conservate: 1 su 2` (la scheda "è affidabile?" resta; quella del vecchio gap singolo resta solo se il suo gruppo e' identico). Se esce `gap_article` x7 con "Non citato:", il raggruppamento e' fallito: leggi `Logger` (`storage/logs/`) e `SELECT error_message FROM ai_logs WHERE module_slug='ai-reputation' ORDER BY id DESC LIMIT 1`.

Costo reale: `SELECT model, tokens_input, tokens_output, estimated_cost, duration_ms FROM ai_logs WHERE module_slug='ai-reputation' ORDER BY id DESC LIMIT 1` → riportalo a Clemente.

**Step 3: Verifica nel browser e nel PDF**

- Apri `http://localhost/seo-toolkit/ai-reputation/project/1/runs/4`: in "Da pubblicare" gli articoli con titolo AI, sotto il blocco "Da valutare: serve una prova dal cliente"; aprendo un articolo si vedono le domande coperte; aprendo un pending non c'e' "Genera scheda"; la scheda "è affidabile?" e' ancora li'.
- PDF: usa lo script scratchpad `pdf.php` della sessione (render via `ActionPlanPdfService`) o scarica da "Esporta PDF"; controlla con Read che ci sia la sezione "Da valutare".
- Screenshot del blocco interventi per Clemente.

**Step 4: Commit**

```bash
git add modules/ai-reputation/scripts/rebuild-plan.php
git commit -m "feat(ai-reputation): rebuild-plan.php — ricalcola il piano di un run senza rifare il judge

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>"
```

---

### Task 11: Docs e impostazione

**Files:**
- Modify: `modules/ai-reputation/module.json:90-93` (descrizione `brief_model`)
- Modify: `modules/ai-reputation/docs/TASKS.md` ("Dove siamo", "Prossimo passo", togli il punto 5 "7 articoli gap")
- Modify: `modules/ai-reputation/CLAUDE.md` (ADR-001..014, data, riga su `rebuild-plan.php` in "Ambiente locale")
- Modify: `docs/superpowers/specs/2026-10-08-ai-reputation-schede-operative-pdf-design.md` §5: stima costo scheda → "~0,08 $ misurato il 2026-10-09"

**Step 1: `module.json`** — descrizione di `brief_model`: `"Modello usato da \"Genera scheda\" e dal raggruppamento AI degli articoli a fine run. Il judge non cambia."`. Verifica JSON: `/c/xampp/php/php.exe -r "json_decode(file_get_contents('modules/ai-reputation/module.json'), false, 512, JSON_THROW_ON_ERROR); echo 'ok';"`.

**Step 2: TASKS.md** — in "Dove siamo" aggiungi: `**Raggruppamento AI degli articoli gap (ADR-014): fatto il 2026-10-09** — a fine run una chiamata (brief_model) raggruppa le domande scoperte in pochi articoli e mette in sospeso quelle senza fatti ('Da valutare'); script rebuild-plan.php per ricalcolare il piano senza judge; run 4 ricalcolato.` In "Prossimo passo" scrivi il prossimo passo vero (deploy in produzione: `git push` + `git pull` + migrazione `2026-10-09-actions-gap-grouping.sql`, oppure la revisione del prompt del canale/blog: chiedi a Clemente). Togli dal punto 5 "7 'articoli gap' ancora uno per domanda". Aggiorna la data in testa.

**Step 3: CLAUDE.md del modulo** — `docs/decisions.md | ADR-001..014`; data "Ultimo aggiornamento: 2026-10-09"; in "Ambiente locale" aggiungi: `- Ricalcolare il piano di un run senza rifare il judge: php modules/ai-reputation/scripts/rebuild-plan.php <runId> (una chiamata AI, ~0,05 $).`

**Step 4: Commit**

```bash
git add modules/ai-reputation/module.json modules/ai-reputation/docs/TASKS.md modules/ai-reputation/CLAUDE.md docs/superpowers/specs/2026-10-08-ai-reputation-schede-operative-pdf-design.md
git commit -m "docs(ai-reputation): ADR-014 in TASKS/CLAUDE.md, brief_model anche per il raggruppamento, costo scheda misurato

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>"
```

**Step 5: Chiusura** — `git status` pulito (a parte `.cervello/`, `public/landing3.php`, `token-form-filled.png` che erano gia' non tracciati). Riporta a Clemente: cosa e' uscito sul run 4 (titoli degli articoli, domande in sospeso), costo del raggruppamento, schede conservate. Non fare `git push`: lo decide lui.
