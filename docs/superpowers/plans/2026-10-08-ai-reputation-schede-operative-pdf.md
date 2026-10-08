# AI Reputation Radar — Export PDF interventi + scheda operativa — Piano di implementazione

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Nel report di un run del modulo `ai-reputation`: (1) un link "Esporta PDF" che scarica gli interventi del piano d'azione così come si vedono nel report; (2) per ogni intervento, un pulsante "Genera scheda" che chiama Claude Opus 5.5 e salva una scheda operativa (canale, testate, brief o richiesta), visibile nella riga dell'intervento e nel PDF.

**Architecture:** Nessuna tabella nuova: 7 colonne su `ar_actions`. Due servizi nuovi nel modulo (`ActionPlanPdfService` con mPDF, `ActionBriefService` via `AiService`), un partial condiviso tra report e risposta AJAX (`views/partials/action-brief.php`), un template PDF (`views/pdf/action-plan.php`), due route nuove in `routes.php` → `RunController`. Il PDF rende sempre lo stato corrente delle azioni; la generazione è per singolo intervento, AJAX lungo (pattern Golden Rules 15/17/23).

**Tech Stack:** PHP 8.3, MySQL 8, Alpine.js (già nel report), mPDF 8.2 (già in `composer.json`), Claude API via `services/AiService.php`.

Spec di riferimento: `docs/superpowers/specs/2026-10-08-ai-reputation-schede-operative-pdf-design.md`.

## Global Constraints

- Branch di lavoro: `claude/ai-reputation-radar-dd9004` nel worktree `C:\laragon\www\seo-toolkit\.claude\worktrees\ai-reputation-m0-3-69e6ee`. Commit atomici, push su `origin` del branch; **non** mergiare in `main` da questo piano (deploy nel Task 9).
- Il worktree non ha `vendor/`; il checkout principale `C:\laragon\www\seo-toolkit` ce l'ha (con mPDF) ed è quello servito da Laragon su `http://localhost/seo-toolkit` (login test `admin@seo-toolkit.local` / `admin123`). Task 0 crea una junction `vendor` nel worktree e definisce come copiare i file nel checkout principale per i test browser.
- PHP CLI: `/c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe` (in Git Bash). MySQL locale: `/c/laragon/bin/mysql/mysql-8.0.30-winx64/bin/mysql.exe -u root seo_toolkit`.
- Golden Rules sempre: testi UI in italiano; icone solo Heroicons SVG; prepared statements; CSRF `_csrf_token`; AJAX lungo = `ignore_user_abort(true)` + `set_time_limit(300)` + `ob_start()` + `session_write_close()` + `ob_end_clean()` prima di **ogni** `echo json_encode` (anche early return); frontend `response.ok` prima di `response.json()`; `Database::reconnect()` dopo ogni chiamata AI; nuove chiamate AI solo via `new AiService('ai-reputation')`.
- Regole commerciali del modulo nei testi generati e nel PDF: mai esporre il metodo, mai costi delle testate, tutto in italiano.
- Modello schede: `claude-opus-5-5` di default (impostazione `brief_model` del modulo); il judge non cambia.
- Ogni task finisce con `php -l` sui file PHP toccati e un commit con trailer `Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>`.

---

### Task 0: Ambiente di lavoro (vendor nel worktree + copia nel checkout principale)

**Files:**
- Create (junction, non tracciata): `vendor` → `C:\laragon\www\seo-toolkit\vendor`
- Create: `modules/ai-reputation/scripts/sync-to-main.sh`

**Interfaces:**
- Produces: comando `bash modules/ai-reputation/scripts/sync-to-main.sh` che copia nel checkout principale i file del branch committati, per il test nel browser.

- [ ] **Step 1: Junction vendor**

Run (Git Bash, dalla root del worktree):
```bash
cmd //c "mklink /J vendor ..\..\..\vendor" && /c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe -r "require 'vendor/autoload.php'; echo class_exists('Mpdf\\Mpdf') ? \"mpdf ok\n\" : \"mpdf NO\n\";"
```
Expected: `Junction created for vendor <<===>> ..\..\..\vendor` poi `mpdf ok`.

- [ ] **Step 2: Verifica che vendor sia ignorato da git**

Run: `git status --short | head -5`
Expected: `vendor` **non** compare. Se compare, aggiungere la riga `vendor/` a `.gitignore` nello stesso commit del Task 1.

- [ ] **Step 3: Script di copia verso il checkout principale**

Create `modules/ai-reputation/scripts/sync-to-main.sh`:
```bash
#!/bin/bash
# Copia nel checkout principale (servito da Laragon) i file di questo branch già COMMITTATI.
# Uso: bash modules/ai-reputation/scripts/sync-to-main.sh   (dalla root del worktree)
set -euo pipefail
MAIN="C:/laragon/www/seo-toolkit"
BRANCH="claude/ai-reputation-radar-dd9004"
git -C "$MAIN" fetch -q . 2>/dev/null || true
git -C "$MAIN" restore --source="$BRANCH" --worktree -- \
  modules/ai-reputation \
  services/AiService.php \
  core/Models/GlobalProject.php \
  shared/views/components/nav-items.php
echo "copiati nel checkout principale: modules/ai-reputation, services/AiService.php"
```
Nota: `git restore --source=<branch>` legge gli oggetti del repo condiviso, quindi copia solo ciò che è committato nel worktree. Ricordarsi di committare prima di ogni test browser.

- [ ] **Step 4: Prova lo script**

Run: `bash modules/ai-reputation/scripts/sync-to-main.sh && git -C C:/laragon/www/seo-toolkit status --short modules/ai-reputation | head -3`
Expected: la riga `copiati…`; lo status del checkout principale mostra `?? modules/ai-reputation/` (file non tracciati su quel branch: normale).

- [ ] **Step 5: Commit**

```bash
git add modules/ai-reputation/scripts/sync-to-main.sh
git commit -m "chore(ai-reputation): script di copia verso il checkout principale per i test browser

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>"
```

---

### Task 1: Migrazione `ar_actions` + impostazioni del modulo

**Files:**
- Create: `modules/ai-reputation/database/2026-10-08-actions-brief.sql`
- Modify: `modules/ai-reputation/module.json` (chiave `settings`: aggiungere `brief_model` e `cost_action_brief`)

**Interfaces:**
- Produces: colonne `ar_actions.channel`, `channel_rationale`, `suggested_outlets`, `brief`, `brief_model`, `brief_generated_at`, `brief_error`; setting `brief_model` (default `claude-opus-5-5`), setting `cost_action_brief` (default 1) letti con `\Core\ModuleLoader::getSetting('ai-reputation', 'brief_model', 'claude-opus-5-5')` e `\Core\Credits::getCost('action_brief', 'ai-reputation', 1)`.

- [ ] **Step 1: Scrivi la migrazione**

Create `modules/ai-reputation/database/2026-10-08-actions-brief.sql`:
```sql
-- AI Reputation Radar - scheda operativa per intervento (canale, testate, brief) generata su richiesta
ALTER TABLE ar_actions
    ADD COLUMN channel ENUM('own_site', 'external', 'both') DEFAULT NULL AFTER status,
    ADD COLUMN channel_rationale TEXT DEFAULT NULL AFTER channel,
    ADD COLUMN suggested_outlets JSON DEFAULT NULL AFTER channel_rationale,
    ADD COLUMN brief JSON DEFAULT NULL AFTER suggested_outlets,
    ADD COLUMN brief_model VARCHAR(80) DEFAULT NULL AFTER brief,
    ADD COLUMN brief_generated_at DATETIME DEFAULT NULL AFTER brief_model,
    ADD COLUMN brief_error VARCHAR(500) DEFAULT NULL AFTER brief_generated_at;
```

- [ ] **Step 2: Applica in locale e verifica**

Run:
```bash
/c/laragon/bin/mysql/mysql-8.0.30-winx64/bin/mysql.exe -u root seo_toolkit < modules/ai-reputation/database/2026-10-08-actions-brief.sql && /c/laragon/bin/mysql/mysql-8.0.30-winx64/bin/mysql.exe -u root seo_toolkit -N -e "SHOW COLUMNS FROM ar_actions" | awk '{print $1}' | tr '\n' ' '
```
Expected: l'elenco contiene `channel channel_rationale suggested_outlets brief brief_model brief_generated_at brief_error`.

- [ ] **Step 3: Aggiungi le impostazioni in module.json**

In `modules/ai-reputation/module.json`, dentro `"settings"`, subito dopo l'oggetto `"ai_model"` aggiungi:
```json
"brief_model": {
    "type": "select",
    "label": "Modello per le schede operative",
    "description": "Modello usato da \"Genera scheda\" (canale, testate, brief). Il judge non cambia.",
    "default": "claude-opus-5-5",
    "admin_only": true,
    "group": "ai_config",
    "options": [
        {"value": "claude-opus-5-5", "label": "Claude Opus 5.5 (consigliato)"},
        {"value": "claude-sonnet-5-5", "label": "Claude Sonnet 5.5"},
        {"value": "global", "label": "Usa il modello del modulo/globale"}
    ]
},
```
e, subito dopo l'oggetto `"cost_collect_response"`:
```json
"cost_action_brief": {
    "type": "number",
    "label": "Costo scheda operativa",
    "description": "Crediti per ogni scheda generata con \"Genera scheda\" (un intervento)",
    "default": 1,
    "min": 0,
    "step": 0.5,
    "admin_only": true,
    "group": "costs"
},
```
Attenzione alle virgole JSON (l'ultimo oggetto di `settings` non ha virgola finale).

- [ ] **Step 4: Verifica JSON e lettura setting**

Run:
```bash
/c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe -r '$m=json_decode(file_get_contents("modules/ai-reputation/module.json"),true); echo json_last_error_msg(), " | ", $m["settings"]["brief_model"]["default"], " | ", $m["settings"]["cost_action_brief"]["default"], "\n";'
```
Expected: `No error | claude-opus-5-5 | 1`.

- [ ] **Step 5: Commit**

```bash
git add modules/ai-reputation/database/2026-10-08-actions-brief.sql modules/ai-reputation/module.json
git commit -m "feat(ai-reputation): colonne scheda operativa su ar_actions + setting brief_model e cost_action_brief

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>"
```

---

### Task 2: AiService — modelli Claude 5.5 + opzioni `effort` e `timeout`

**Files:**
- Modify: `services/AiService.php` (`MODELS` riga ~20; `complete()` riga ~206; `callAnthropic()` riga ~426)
- Test: `modules/ai-reputation/scripts/test-aiservice-options.php` (CLI, senza chiamate API)

**Interfaces:**
- Produces: `AiService::complete($userId, $messages, ['model' => 'claude-opus-5-5', 'max_tokens' => 8192, 'effort' => 'high', 'timeout' => 240, 'system' => $sys], 'ai-reputation')`; `AiService::MODELS['anthropic']['claude-opus-5-5']` esiste; `AiService::buildAnthropicPayload(...)` (nuovo metodo pubblico statico, testabile) produce il body della richiesta.

- [ ] **Step 1: Scrivi il test CLI che fallisce**

Create `modules/ai-reputation/scripts/test-aiservice-options.php`:
```php
<?php
// Test senza rete: i modelli 5.5 sono in listino e il payload Anthropic porta output_config.effort.
// Run: php modules/ai-reputation/scripts/test-aiservice-options.php
declare(strict_types=1);
require __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../services/AiService.php';

$fail = 0;
$check = function (string $name, bool $ok) use (&$fail): void {
    echo ($ok ? 'PASS' : 'FAIL') . " {$name}\n";
    if (!$ok) { $fail++; }
};

$models = \Services\AiService::MODELS['anthropic'];
$check('claude-opus-5-5 in listino', isset($models['claude-opus-5-5']) && $models['claude-opus-5-5']['input'] === 0.004 && $models['claude-opus-5-5']['output'] === 0.020);
$check('claude-sonnet-5-5 in listino', isset($models['claude-sonnet-5-5']) && $models['claude-sonnet-5-5']['input'] === 0.002);
$check('claude-haiku-5-5 in listino', isset($models['claude-haiku-5-5']) && $models['claude-haiku-5-5']['output'] === 0.0005);

$p = \Services\AiService::buildAnthropicPayload('claude-opus-5-5', [['role' => 'user', 'content' => 'ciao']], 8192, 'sys', 'high');
$check('payload con effort', ($p['output_config']['effort'] ?? null) === 'high' && $p['model'] === 'claude-opus-5-5' && $p['max_tokens'] === 8192 && $p['system'] === 'sys');
$p2 = \Services\AiService::buildAnthropicPayload('claude-sonnet-4-20250514', [['role' => 'user', 'content' => 'ciao']], 4096, null, null);
$check('payload senza effort e senza system', !isset($p2['output_config']) && !isset($p2['system']));

exit($fail ? 1 : 0);
```
Nota: se `AiService.php` non è caricato dall'autoload di composer, il `require_once` sopra lo include direttamente; se il namespace del file è diverso da `Services`, adeguare il test al namespace reale (controllare la riga `namespace` in cima a `services/AiService.php`).

- [ ] **Step 2: Esegui il test, deve fallire**

Run: `/c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe modules/ai-reputation/scripts/test-aiservice-options.php`
Expected: `FAIL claude-opus-5-5 in listino` … e un errore `Call to undefined method … buildAnthropicPayload`.

- [ ] **Step 3: Aggiungi i modelli al listino**

In `services/AiService.php`, dentro `MODELS['anthropic']`, come prime tre righe:
```php
'claude-opus-5-5' => ['name' => 'Claude Opus 5.5', 'input' => 0.004, 'output' => 0.020],
'claude-sonnet-5-5' => ['name' => 'Claude Sonnet 5.5', 'input' => 0.002, 'output' => 0.010],
'claude-haiku-5-5' => ['name' => 'Claude Haiku 5.5', 'input' => 0.0001, 'output' => 0.0005],
```

- [ ] **Step 4: Opzioni effort/timeout in complete() e payload testabile**

Nella classe aggiungi due proprietà (vicino alle altre proprietà private):
```php
/** Opzioni per-chiamata (solo Anthropic): sforzo di ragionamento e timeout curl */
private ?string $requestEffort = null;
private int $requestTimeout = 120;
```
In `complete()`, subito dopo `$system = $options['system'] ?? null;`:
```php
$this->requestEffort = $options['effort'] ?? null;
$this->requestTimeout = (int) ($options['timeout'] ?? 120);
```
Aggiungi il metodo statico:
```php
/**
 * Body della richiesta Messages API (testabile senza rete).
 * $effort: low|medium|high|xhigh|max → output_config.effort (modelli Claude 4.6+); null = default del modello.
 */
public static function buildAnthropicPayload(string $model, array $messages, int $maxTokens, ?string $system, ?string $effort): array
{
    $data = ['model' => $model, 'max_tokens' => $maxTokens, 'messages' => $messages];
    if ($system) {
        $data['system'] = $system;
    }
    if ($effort && in_array($effort, ['low', 'medium', 'high', 'xhigh', 'max'], true)) {
        $data['output_config'] = ['effort' => $effort];
    }
    return $data;
}
```
In `callAnthropic()` sostituisci la costruzione di `$data` (il blocco `$data = [...]; if ($system) {...}`) con:
```php
$data = self::buildAnthropicPayload($model, $messages, $maxTokens, $system ? $this->sanitizeStringUtf8($system) : null, $this->requestEffort);
```
e `CURLOPT_TIMEOUT => 120,` con `CURLOPT_TIMEOUT => max(120, $this->requestTimeout),`.

- [ ] **Step 5: Test verde + lint**

Run: `/c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe -l services/AiService.php && /c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe modules/ai-reputation/scripts/test-aiservice-options.php`
Expected: `No syntax errors` e 5 righe `PASS`.

- [ ] **Step 6: Commit**

```bash
git add services/AiService.php modules/ai-reputation/scripts/test-aiservice-options.php
git commit -m "feat(ai): modelli Claude 5.5 in AiService + opzioni effort/timeout per chiamata

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>"
```

---

### Task 3: Export PDF degli interventi (consegna 1)

**Files:**
- Create: `modules/ai-reputation/services/ActionPlanPdfService.php`
- Create: `modules/ai-reputation/views/pdf/action-plan.php`
- Modify: `modules/ai-reputation/controllers/RunController.php` (nuovo metodo `exportPlanPdf`, dopo `show()`)
- Modify: `modules/ai-reputation/routes.php` (nuova route GET prima di `…/runs/{runId}`)
- Modify: `modules/ai-reputation/views/runs/show.php` (link "Esporta PDF" nella testata, riga ~61)
- Test: `modules/ai-reputation/scripts/test-action-plan-pdf.php` (CLI, genera un PDF da dati finti)

**Interfaces:**
- Produces: `ActionPlanPdfService::render(array $project, array $run, array $actions, array $metrics): string` (binario PDF); `ActionPlanPdfService::filename(array $project, array $run): string`; `ActionPlanPdfService::html(...)` (stesso input, ritorna l'HTML del template, usato dal test); route `GET /ai-reputation/project/{id}/runs/{runId}/export/plan.pdf`.
- Consumes: `ReportBuilderService::siteTitle(string $domain, string $title): string`; `EngineCollectorService::domainOf(string $url): ?string`; `$metrics['risk']`, `$metrics['risk_label']` da `ReportBuilderService::metrics()`.

- [ ] **Step 1: Test CLI che fallisce**

Create `modules/ai-reputation/scripts/test-action-plan-pdf.php`:
```php
<?php
// Genera un PDF di prova da dati finti e verifica l'HTML. Run: php modules/ai-reputation/scripts/test-action-plan-pdf.php
declare(strict_types=1);
define('BASE_PATH', dirname(__DIR__, 3));
require BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/services/ScraperService.php';
require_once BASE_PATH . '/modules/ai-reputation/services/EngineCollectorService.php';
require_once BASE_PATH . '/modules/ai-reputation/services/ReportBuilderService.php';
require_once BASE_PATH . '/modules/ai-reputation/services/ActionPlanPdfService.php';

$project = ['id' => 1, 'name' => 'Test', 'subject_name' => 'Mario Rossi', 'website' => 'https://mariorossi.it'];
$run = ['id' => 4, 'created_at' => '2026-10-08 10:00:00'];
$metrics = ['risk' => 47, 'risk_label' => 'Alto'];
$actions = [
    ['id' => 1, 'type' => 'counter_content', 'status' => 'proposed', 'title' => 'Pagina autorevole che risponda a "Mario Rossi è affidabile?"', 'rationale' => 'Le AI rispondono in negativo a 3 domande.', 'target_url' => null, 'target_urls' => null, 'target_domain' => 'wired.it', 'channel' => null, 'brief' => null, 'brief_error' => null],
    ['id' => 2, 'type' => 'removal', 'status' => 'proposed', 'title' => 'Fonte negativa: giornale-x.it (2 pagine)', 'rationale' => 'Citata 5 volte da ChatGPT, Gemini.', 'target_url' => 'https://giornale-x.it/a', 'target_urls' => json_encode(['https://giornale-x.it/a', 'https://giornale-x.it/b']), 'target_domain' => 'giornale-x.it', 'channel' => null, 'brief' => null, 'brief_error' => null],
    ['id' => 3, 'type' => 'gap_article', 'status' => 'dismissed', 'title' => 'SCARTATO: non deve comparire', 'rationale' => '', 'target_url' => null, 'target_urls' => null, 'target_domain' => null, 'channel' => null, 'brief' => null, 'brief_error' => null],
];

$svc = new \Modules\AiReputation\Services\ActionPlanPdfService();
$html = $svc->html($project, $run, $actions, $metrics);
$fail = 0;
$check = function (string $name, bool $ok) use (&$fail): void { echo ($ok ? 'PASS' : 'FAIL') . " {$name}\n"; if (!$ok) { $fail++; } };
$check('intestazione con soggetto e rischio', str_contains($html, 'Mario Rossi') && str_contains($html, 'Alto') && str_contains($html, '47%'));
$check('conteggi', str_contains($html, '1 contenut') && str_contains($html, '1 sit'));
$check('contenuti prima delle rimozioni', strpos($html, 'Pagina autorevole') < strpos($html, 'Fonte negativa'));
$check('pagine della rimozione come link', substr_count($html, 'href="https://giornale-x.it/') === 2);
$check('scartati esclusi', !str_contains($html, 'SCARTATO'));
$check('scheda non generata', substr_count($html, 'Scheda operativa non generata') === 2);
$check('niente costi', !preg_match('/€|\$\s?\d/', $html));

$pdf = $svc->render($project, $run, $actions, $metrics);
$check('pdf binario', str_starts_with($pdf, '%PDF-') && strlen($pdf) > 2000);
$check('filename', $svc->filename($project, $run) === 'piano-interventi-mario-rossi-run4-2026-10-08.pdf');
file_put_contents(BASE_PATH . '/storage/cache/test-action-plan.pdf', $pdf);
echo "PDF scritto in storage/cache/test-action-plan.pdf\n";
exit($fail ? 1 : 0);
```
Nota: se `ReportBuilderService` richiede altri `require_once` (controllare i `use` in cima al file), aggiungerli nello stesso ordine; in alternativa usare il bootstrap dell'app se esiste un file `bootstrap.php` nella root (controllare `public/index.php` righe 20-60 per vedere come carica core e servizi).

- [ ] **Step 2: Esegui, deve fallire**

Run: `/c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe modules/ai-reputation/scripts/test-action-plan-pdf.php`
Expected: errore `Failed opening required …/ActionPlanPdfService.php`.

- [ ] **Step 3: Servizio PDF**

Create `modules/ai-reputation/services/ActionPlanPdfService.php`:
```php
<?php

namespace Modules\AiReputation\Services;

use Mpdf\Mpdf;
use Mpdf\Output\Destination;

/**
 * PDF del piano d'azione di un run: gli interventi come si vedono nel report, con la scheda operativa se esiste.
 * Nessun metodo, nessun costo di testate (regole commerciali del modulo).
 */
class ActionPlanPdfService
{
    public const TYPE_LABELS = ['removal' => 'Rimozione', 'counter_content' => 'Contro-contenuto', 'gap_article' => 'Articolo gap', 'correction' => 'Correzione'];
    public const STATUS_LABELS = ['proposed' => 'Proposto', 'accepted' => 'Accettato', 'done' => 'Fatto', 'dismissed' => 'Scartato'];
    public const CHANNEL_LABELS = ['own_site' => 'Sito proprietario', 'external' => 'Siti esterni', 'both' => 'Sito proprietario + siti esterni'];

    /** HTML del template (testabile senza mPDF). */
    public function html(array $project, array $run, array $actions, array $metrics): string
    {
        $actions = array_values(array_filter($actions, fn($a) => ($a['status'] ?? 'proposed') !== 'dismissed'));
        $contents = array_values(array_filter($actions, fn($a) => $a['type'] !== 'removal'));
        $removals = array_values(array_filter($actions, fn($a) => $a['type'] === 'removal'));
        foreach ([&$contents, &$removals] as &$list) {
            foreach ($list as &$a) {
                $a['pages'] = self::pages($a);
                $a['brief_data'] = is_string($a['brief'] ?? null) ? (json_decode($a['brief'], true) ?: null) : ($a['brief'] ?? null);
                $a['outlets'] = is_string($a['suggested_outlets'] ?? null) ? (json_decode($a['suggested_outlets'], true) ?: []) : ($a['suggested_outlets'] ?? []);
            }
            unset($a);
        }
        unset($list);
        ob_start();
        $typeLabels = self::TYPE_LABELS;
        $statusLabels = self::STATUS_LABELS;
        $channelLabels = self::CHANNEL_LABELS;
        include __DIR__ . '/../views/pdf/action-plan.php';
        return (string) ob_get_clean();
    }

    /** PDF binario. */
    public function render(array $project, array $run, array $actions, array $metrics): string
    {
        $tmp = (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3)) . '/storage/cache/mpdf';
        if (!is_dir($tmp)) {
            @mkdir($tmp, 0775, true);
        }
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15, 'margin_right' => 15, 'margin_top' => 16, 'margin_bottom' => 18,
            'tempDir' => $tmp,
            'default_font' => 'dejavusans',
        ]);
        $mpdf->SetTitle('Piano interventi - ' . $project['subject_name']);
        $mpdf->SetAuthor('Ainstein');
        $mpdf->SetHTMLFooter('<div style="font-size:8.5pt;color:#64748b;text-align:center;border-top:0.5pt solid #e2e8f0;padding-top:4pt;">Ainstein · AI Reputation Radar · pagina {PAGENO} di {nbpg}</div>');
        $mpdf->WriteHTML($this->html($project, $run, $actions, $metrics));
        return $mpdf->Output('', Destination::STRING_RETURN);
    }

    public function filename(array $project, array $run): string
    {
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', iconv('UTF-8', 'ASCII//TRANSLIT', (string) $project['subject_name'])) ?? 'soggetto', '-')) ?: 'soggetto';
        return "piano-interventi-{$slug}-run{$run['id']}-" . date('Y-m-d', strtotime((string) $run['created_at'])) . '.pdf';
    }

    /** Pagine dell'azione (target_urls JSON, fallback target_url), solo http(s), con titolo leggibile. */
    public static function pages(array $a): array
    {
        $urls = json_decode((string) ($a['target_urls'] ?? ''), true);
        if (!is_array($urls) || !$urls) {
            $urls = !empty($a['target_url']) ? [$a['target_url']] : [];
        }
        $out = [];
        foreach ($urls as $u) {
            if (!is_string($u) || !preg_match('#^https?://#i', $u)) {
                continue;
            }
            $domain = EngineCollectorService::domainOf($u) ?? $u;
            $out[] = ['url' => $u, 'label' => ReportBuilderService::siteTitle($domain, '')];
        }
        return $out;
    }
}
```

- [ ] **Step 4: Template PDF**

Create `modules/ai-reputation/views/pdf/action-plan.php` (variabili: `$project`, `$run`, `$metrics`, `$contents`, `$removals`, `$typeLabels`, `$statusLabels`, `$channelLabels`; `$a['brief_data']` è usato nel Task 6, qui mostra solo "non generata"):
```php
<?php
$h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$riskColor = match ($metrics['risk_label'] ?? '') { 'Alto' => '#dc2626', 'Medio' => '#d97706', 'Basso' => '#059669', default => '#64748b' };
$badge = fn(string $text, string $bg, string $fg) => '<span style="display:inline-block;padding:1pt 6pt;border-radius:8pt;font-size:8pt;font-weight:bold;background:' . $bg . ';color:' . $fg . ';">' . $h($text) . '</span>';
$typeBadge = fn(string $type) => $badge($typeLabels[$type] ?? $type, $type === 'removal' ? '#fee2e2' : '#e0e7ff', $type === 'removal' ? '#b91c1c' : '#3730a3');
$statusBadge = fn(string $status) => $badge($statusLabels[$status] ?? $status, '#f1f5f9', '#334155');
$renderAction = function (array $a) use ($h, $typeBadge, $statusBadge, $channelLabels): void {
    ?>
    <div style="border:0.5pt solid #e2e8f0;border-radius:6pt;padding:8pt 10pt;margin-bottom:9pt;page-break-inside:avoid;">
        <div style="margin-bottom:4pt;"><?= $typeBadge($a['type']) ?> &nbsp;<?= $statusBadge($a['status'] ?? 'proposed') ?></div>
        <div style="font-size:11.5pt;font-weight:bold;color:#0f172a;margin-bottom:4pt;"><?= $h($a['title']) ?></div>
        <?php if (!empty($a['rationale'])): ?><div style="font-size:9.5pt;color:#334155;margin-bottom:4pt;"><?= $h($a['rationale']) ?></div><?php endif; ?>
        <?php if ($a['pages']): ?>
        <div style="font-size:9pt;color:#475569;">
            <?php foreach ($a['pages'] as $p): ?>
            <div>• <a href="<?= $h($p['url']) ?>" style="color:#4f46e5;text-decoration:none;"><?= $h($p['label']) ?></a> <span style="color:#94a3b8;"><?= $h(mb_strimwidth($p['url'], 0, 90, '…')) ?></span></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if (empty($a['brief_data'])): ?>
        <div style="margin-top:6pt;font-size:9pt;color:#94a3b8;border-top:0.5pt dashed #e2e8f0;padding-top:4pt;">Scheda operativa non generata</div>
        <?php else: ?>
        <?php include __DIR__ . '/action-brief.php'; ?>
        <?php endif; ?>
    </div>
    <?php
};
?>
<div style="font-family:dejavusans,sans-serif;color:#0f172a;">
    <div style="border-bottom:1.5pt solid #4f46e5;padding-bottom:8pt;margin-bottom:12pt;">
        <div style="font-size:9pt;color:#4f46e5;font-weight:bold;letter-spacing:1pt;">AI REPUTATION RADAR · PIANO DEGLI INTERVENTI</div>
        <div style="font-size:18pt;font-weight:bold;margin-top:2pt;"><?= $h($project['subject_name']) ?></div>
        <div style="font-size:9.5pt;color:#475569;margin-top:3pt;">
            Run #<?= (int) $run['id'] ?> del <?= date('d/m/Y', strtotime((string) $run['created_at'])) ?>
            · Rischio reputazione: <span style="color:<?= $riskColor ?>;font-weight:bold;"><?= $h($metrics['risk_label'] ?? '–') ?><?= isset($metrics['risk']) ? ' ' . (int) $metrics['risk'] . '%' : '' ?></span>
            · <?= count($contents) ?> contenut<?= count($contents) === 1 ? 'o' : 'i' ?> da pubblicare · <?= count($removals) ?> sit<?= count($removals) === 1 ? 'o' : 'i' ?> da contattare
        </div>
    </div>

    <?php if (!$contents && !$removals): ?>
    <p style="font-size:10pt;color:#475569;">Nessun intervento necessario per questo run.</p>
    <?php endif; ?>

    <?php if ($contents): ?>
    <div style="font-size:10pt;font-weight:bold;color:#4f46e5;text-transform:uppercase;letter-spacing:0.8pt;margin:6pt 0;">Da pubblicare</div>
    <?php foreach ($contents as $a) { $renderAction($a); } ?>
    <?php endif; ?>

    <?php if ($removals): ?>
    <div style="font-size:10pt;font-weight:bold;color:#4f46e5;text-transform:uppercase;letter-spacing:0.8pt;margin:10pt 0 6pt;">Da far rimuovere o aggiornare</div>
    <?php foreach ($removals as $a) { $renderAction($a); } ?>
    <?php endif; ?>
</div>
```
Crea anche il file `modules/ai-reputation/views/pdf/action-brief.php` **vuoto per ora** con solo `<?php // Scheda operativa nel PDF: compilato nel Task 6 ?>` (il template lo include quando `brief_data` esiste).

- [ ] **Step 5: Test verde + lint**

Run: `for f in modules/ai-reputation/services/ActionPlanPdfService.php modules/ai-reputation/views/pdf/action-plan.php modules/ai-reputation/views/pdf/action-brief.php; do /c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe -l $f; done && /c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe modules/ai-reputation/scripts/test-action-plan-pdf.php`
Expected: 3 × `No syntax errors`, 9 righe `PASS`, `PDF scritto in storage/cache/test-action-plan.pdf`. Aprire il PDF (`start storage/cache/test-action-plan.pdf`) e controllare a occhio intestazione, badge, link, piè di pagina.

- [ ] **Step 6: Controller e route**

In `modules/ai-reputation/controllers/RunController.php`, dopo il metodo `show()`, aggiungi:
```php
/**
 * Export PDF del piano d'azione del run (interventi come nel report, con le schede se generate).
 */
public function exportPlanPdf(int $projectId, int $runId): void
{
    $user = Auth::user();
    $project = $this->project->findAccessible($projectId, $user['id']);
    $run = $project ? $this->run->find($runId, $projectId) : null;
    if (!$project || !$run) {
        $_SESSION['_flash']['error'] = 'Run non trovato';
        Router::redirect('/ai-reputation');
        exit;
    }
    $responses = $this->run->responses($runId);
    $analyses = $this->analysis->byRun($runId);
    $metrics = (new ReportBuilderService())->metrics($responses, $analyses, $run['engines'], $project);
    $actions = Database::fetchAll("SELECT * FROM ar_actions WHERE run_id = ? ORDER BY FIELD(type, 'removal', 'counter_content', 'gap_article', 'correction'), id", [$runId]);
    try {
        $pdfService = new ActionPlanPdfService();
        $pdf = $pdfService->render($project, $run, $actions, $metrics);
    } catch (\Throwable $e) {
        \Core\Logger::channel('ai-reputation')->error('Export PDF fallito', ['run_id' => $runId, 'error' => $e->getMessage()]);
        $_SESSION['_flash']['error'] = 'Export PDF non riuscito, riprova tra poco.';
        Router::redirect("/ai-reputation/project/{$projectId}/runs/{$runId}");
        exit;
    }
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $pdfService->filename($project, $run) . '"');
    header('Content-Length: ' . strlen($pdf));
    header('Cache-Control: private, no-store');
    echo $pdf;
    exit;
}
```
Aggiungi `use Modules\AiReputation\Services\ActionPlanPdfService;` tra gli `use` in cima al controller (accanto a `ReportBuilderService`). Se `\Core\Logger::channel()` non esiste con quella firma, usare `error_log('[ai-reputation] Export PDF fallito: ' . $e->getMessage());`.

In `modules/ai-reputation/routes.php`, **prima** della route `GET /ai-reputation/project/{id}/runs/{runId}`:
```php
Router::get('/ai-reputation/project/{id}/runs/{runId}/export/plan.pdf', function ($id, $runId) {
    Middleware::auth();
    return (new RunController())->exportPlanPdf((int) $id, (int) $runId);
});
```

- [ ] **Step 7: Link nel report**

In `modules/ai-reputation/views/runs/show.php`, nella testata, subito **prima** del blocco `<?php if ($canEdit && !$isActive): ?>` del pulsante Rianalizza, aggiungi:
```php
<?php if ($hasAnalyses && !empty($actions)): ?>
<a href="<?= url("{$basePath}/runs/{$run['id']}/export/plan.pdf") ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700 text-sm font-medium transition-colors" title="Scarica il piano degli interventi in PDF">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
    Esporta PDF
</a>
<?php endif; ?>
```

- [ ] **Step 8: Lint, commit, copia nel checkout principale, test browser**

Run:
```bash
for f in modules/ai-reputation/controllers/RunController.php modules/ai-reputation/routes.php modules/ai-reputation/views/runs/show.php; do /c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe -l $f; done
git add modules/ai-reputation && git commit -m "feat(ai-reputation): export PDF del piano degli interventi (consegna 1)

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>"
bash modules/ai-reputation/scripts/sync-to-main.sh
```
Poi nel browser (`http://localhost/seo-toolkit`, login `admin@seo-toolkit.local` / `admin123`): aprire un run analizzato di un progetto Radar (se non esiste, crearne uno: Progetti → Nuovo → attiva AI Reputation Radar → Profilo → domande → Avvia run; serve almeno una domanda e una API key OpenAI configurata; costa pochi centesimi). Cliccare "Esporta PDF": il file si scarica, si apre, mostra gli stessi interventi del report, nessun intervento scartato, piè di pagina con numero pagina. Controllare `storage/logs` del checkout principale: nessun errore.
Expected: PDF corretto. Se mPDF segnala `tempDir` non scrivibile, dare permessi a `storage/cache/mpdf`.

---

### Task 4: ActionBriefService — dossier, prompt, validazione, generazione

**Files:**
- Create: `modules/ai-reputation/services/ActionBriefService.php`
- Test: `modules/ai-reputation/scripts/test-action-brief-validate.php` (CLI, senza rete)

**Interfaces:**
- Produces: `ActionBriefService::generate(int $actionId, int $userId): array` → `['success' => true, 'action' => array]` oppure `['success' => false, 'error' => string]`; `ActionBriefService::validate(array $data, array $action, array $okDomains): array|string` (statico); `ActionBriefService::parseJson(string $text): ?array` (statico); `ActionBriefService::model(): string`.
- Consumes: `AiService::complete()` del Task 2; colonne del Task 1; `ModuleLoader::getSetting`; modelli `Run::responses()`, `Analysis::byRun()`, `ProfileFact::truth()`, `ReportBuilderService::sources()`.

- [ ] **Step 1: Test di validazione che fallisce**

Create `modules/ai-reputation/scripts/test-action-brief-validate.php`:
```php
<?php
// Validazione dell'output AI per le schede, senza rete. Run: php modules/ai-reputation/scripts/test-action-brief-validate.php
declare(strict_types=1);
define('BASE_PATH', dirname(__DIR__, 3));
require BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/modules/ai-reputation/services/ActionBriefService.php';

use Modules\AiReputation\Services\ActionBriefService as S;

$fail = 0;
$check = function (string $name, bool $ok) use (&$fail): void { echo ($ok ? 'PASS' : 'FAIL') . " {$name}\n"; if (!$ok) { $fail++; } };
$content = ['type' => 'counter_content', 'target_urls' => null, 'target_url' => null];
$removal = ['type' => 'removal', 'target_urls' => json_encode(['https://x.it/a', 'https://x.it/b']), 'target_url' => 'https://x.it/a'];
$ok = ['wired.it', 'ilsole24ore.com'];

$good = ['channel' => 'both', 'channel_rationale' => 'perché', 'suggested_outlets' => ['wired.it', 'inventata.com'],
    'brief' => ['kind' => 'content', 'title' => 'T', 'angle' => 'A', 'points' => ['1', '2', '3'], 'facts' => [['fact' => 'f', 'source' => 'wired.it']], 'avoid' => ['x'], 'length_words' => 900, 'language' => 'it', 'own_site_note' => '']];
$r = S::validate($good, $content, $ok);
$check('contenuto valido', is_array($r) && $r['channel'] === 'both');
$check('testata inventata scartata', is_array($r) && $r['suggested_outlets'] === ['wired.it']);

$r = S::validate(['channel' => 'ovunque'] + $good, $content, $ok);
$check('canale fuori enum → errore', is_string($r));

$few = $good; $few['brief']['points'] = ['solo uno'];
$check('meno di 3 punti → errore', is_string(S::validate($few, $content, $ok)));

$rem = ['channel' => 'external', 'channel_rationale' => '', 'suggested_outlets' => [],
    'brief' => ['kind' => 'removal', 'recipient' => 'redazione', 'request' => 'update', 'basis' => 'notizia superata', 'pages' => ['https://x.it/a', 'https://altro.it/z'], 'fallback' => 'contro-contenuto']];
$r = S::validate($rem, $removal, $ok);
$check('rimozione: canale forzato a null', is_array($r) && $r['channel'] === null);
$check('rimozione: pagine fuori target scartate', is_array($r) && $r['brief']['pages'] === ['https://x.it/a']);

$wrongKind = $good; $wrongKind['brief']['kind'] = 'removal';
$check('kind incoerente → errore', is_string(S::validate($wrongKind, $content, $ok)));

$check('parseJson con fence', (S::parseJson("```json\n{\"a\":1}\n```")['a'] ?? null) === 1);
$check('parseJson senza oggetto → null', S::parseJson('niente') === null);
exit($fail ? 1 : 0);
```

- [ ] **Step 2: Esegui, deve fallire**

Run: `/c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe modules/ai-reputation/scripts/test-action-brief-validate.php`
Expected: errore `Failed opening required …/ActionBriefService.php`.

- [ ] **Step 3: Servizio**

Create `modules/ai-reputation/services/ActionBriefService.php`:
```php
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
```
Note per chi implementa:
- `ActionPlanPdfService::pages()` è statico e definito nel Task 3: il test di validazione lo usa, quindi nel test aggiungere `require_once BASE_PATH . '/modules/ai-reputation/services/ActionPlanPdfService.php';` e i `require_once` di `EngineCollectorService.php` e `ReportBuilderService.php` (stesso schema del test del Task 3).
- Metodi di `core/Database.php` verificati: `fetch()` (una riga o null), `fetchAll()`, `execute()`.
- `$res['model']`: `AiService::complete()` ritorna `'model' => $result['model_used'] ?? $model` (riga ~256): è il modello realmente usato, anche in fallback.

- [ ] **Step 4: Test verde + lint**

Run: `/c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe -l modules/ai-reputation/services/ActionBriefService.php && /c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe modules/ai-reputation/scripts/test-action-brief-validate.php`
Expected: `No syntax errors` e 9 righe `PASS`.

- [ ] **Step 5: Commit**

```bash
git add modules/ai-reputation/services/ActionBriefService.php modules/ai-reputation/scripts/test-action-brief-validate.php
git commit -m "feat(ai-reputation): ActionBriefService — dossier, prompt, validazione e salvataggio della scheda operativa

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>"
```

---

### Task 5: Route, controller, partial e pulsante "Genera scheda" nel report (consegna 2, parte web)

**Files:**
- Create: `modules/ai-reputation/views/partials/action-brief.php`
- Modify: `modules/ai-reputation/controllers/RunController.php` (nuovo metodo `generateBrief`)
- Modify: `modules/ai-reputation/routes.php` (nuova route POST)
- Modify: `modules/ai-reputation/views/runs/show.php` (`$actionRow` include il partial; JS `generateBrief`)

**Interfaces:**
- Produces: `POST /ai-reputation/project/{id}/runs/{runId}/actions/{actionId}/brief` → JSON `{success:true, html}` oppure `{success:false, error}`; partial `ai-reputation::partials/action-brief` con variabili `$a` (riga di `ar_actions`), `$basePath`, `$run`, `$csrf`, `$canEdit`, `$briefCost`.
- Consumes: `ActionBriefService::generate()` (Task 4); `Credits::getCost('action_brief', 'ai-reputation', 1)`, `Credits::hasEnough`, `Credits::consume`, `Credits::getBalance`; `ProjectAccessService::getCreditUserId($project, $userId)`.

- [ ] **Step 1: Partial della scheda**

Create `modules/ai-reputation/views/partials/action-brief.php`:
```php
<?php
/**
 * Scheda operativa di un intervento (report web). Variabili: $a (riga ar_actions), $basePath, $run, $csrf, $canEdit, $briefCost.
 * Usato nel report e come HTML di risposta dell'AJAX "Genera scheda".
 */
$brief = is_string($a['brief'] ?? null) ? (json_decode($a['brief'], true) ?: null) : null;
$outlets = is_string($a['suggested_outlets'] ?? null) ? (json_decode($a['suggested_outlets'], true) ?: []) : [];
$channelLabel = ['own_site' => 'Sito proprietario', 'external' => 'Siti esterni', 'both' => 'Sito proprietario + esterni'];
$channelClass = ['own_site' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300', 'external' => 'bg-teal-100 text-teal-700 dark:bg-teal-900/50 dark:text-teal-300', 'both' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300'];
$requestLabel = ['removal' => 'Rimozione della pagina', 'deindex' => 'Deindicizzazione', 'update' => 'Aggiornamento del contenuto'];
$briefUrl = url("{$basePath}/runs/{$run['id']}/actions/{$a['id']}/brief");
$btn = fn(string $label, bool $primary) => '<button type="button" @click="generateBrief(' . (int) $a['id'] . ', $event, ' . ($brief ? 'true' : 'false') . ')" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium transition-colors '
    . ($primary ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'border border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700') . '">'
    . '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>' . $label . '</button>';
?>
<div class="mt-2 pt-2 border-t border-dashed border-slate-200 dark:border-slate-700" data-brief-url="<?= e($briefUrl) ?>" id="brief-<?= (int) $a['id'] ?>">
<?php if (!$brief): ?>
    <?php if (!empty($a['brief_error'])): ?>
    <p class="text-xs text-red-600 dark:text-red-400 mb-1">Generazione non riuscita: <?= e($a['brief_error']) ?></p>
    <?php endif; ?>
    <?php if ($canEdit): ?>
    <div class="flex items-center gap-2">
        <?= $btn(!empty($a['brief_error']) ? 'Riprova' : 'Genera scheda', true) ?>
        <span class="text-xs text-slate-400"><?= rtrim(rtrim(number_format((float) $briefCost, 1, ',', ''), '0'), ',') ?> credit<?= (float) $briefCost == 1 ? 'o' : 'i' ?> · 20-40 s · decide dove pubblicare e scrive il brief</span>
    </div>
    <?php else: ?>
    <p class="text-xs text-slate-400">Scheda operativa non ancora generata.</p>
    <?php endif; ?>
<?php else: ?>
    <?php if ($brief['kind'] === 'content'): ?>
    <div class="flex flex-wrap items-center gap-2 mb-1">
        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Dove:</span>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $channelClass[$a['channel']] ?? '' ?>"><?= e($channelLabel[$a['channel']] ?? $a['channel']) ?></span>
        <?php foreach ($outlets as $o): ?><a href="https://<?= e($o) ?>" target="_blank" rel="noopener" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline"><?= e($o) ?></a><?php endforeach; ?>
    </div>
    <?php if ($a['channel_rationale']): ?><p class="text-xs text-slate-600 dark:text-slate-300 mb-2"><?= e($a['channel_rationale']) ?></p><?php endif; ?>
    <div class="text-xs space-y-1.5 text-slate-700 dark:text-slate-200">
        <p><span class="font-medium">Titolo proposto:</span> <?= e($brief['title']) ?></p>
        <?php if ($brief['angle']): ?><p><span class="font-medium">Taglio:</span> <?= e($brief['angle']) ?></p><?php endif; ?>
        <div><span class="font-medium">Punti da coprire:</span><ul class="list-disc ml-4 mt-0.5"><?php foreach ($brief['points'] as $p): ?><li><?= e($p) ?></li><?php endforeach; ?></ul></div>
        <?php if ($brief['facts']): ?><div><span class="font-medium">Fatti da citare:</span><ul class="list-disc ml-4 mt-0.5"><?php foreach ($brief['facts'] as $f): ?><li><?= e($f['fact']) ?><?php if ($f['source']): ?> <span class="text-slate-400">(fonte: <?= e($f['source']) ?>)</span><?php endif; ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <?php if ($brief['avoid']): ?><div><span class="font-medium">Da evitare:</span><ul class="list-disc ml-4 mt-0.5"><?php foreach ($brief['avoid'] as $v): ?><li><?= e($v) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <p><span class="font-medium">Lunghezza:</span> <?= $brief['length_words'] ? (int) $brief['length_words'] . ' parole' : 'a discrezione' ?> · <span class="font-medium">Lingua:</span> <?= e(strtoupper($brief['language'])) ?></p>
        <?php if ($brief['own_site_note']): ?><p><span class="font-medium">Sul sito ufficiale:</span> <?= e($brief['own_site_note']) ?></p><?php endif; ?>
    </div>
    <?php else: ?>
    <div class="text-xs space-y-1.5 text-slate-700 dark:text-slate-200">
        <p><span class="font-medium">A chi scrivere:</span> <?= e($brief['recipient']) ?></p>
        <p><span class="font-medium">Cosa chiedere:</span> <?= e($requestLabel[$brief['request']] ?? $brief['request']) ?></p>
        <p><span class="font-medium">Su quale base:</span> <?= e($brief['basis']) ?></p>
        <?php if ($brief['pages']): ?><div><span class="font-medium">Pagine:</span><ul class="ml-4 mt-0.5 list-disc"><?php foreach ($brief['pages'] as $u): ?><li><a href="<?= e($u) ?>" target="_blank" rel="noopener" class="text-indigo-600 dark:text-indigo-400 hover:underline break-all"><?= e(mb_strimwidth($u, 0, 90, '…')) ?></a></li><?php endforeach; ?></ul></div><?php endif; ?>
        <?php if ($brief['fallback']): ?><p><span class="font-medium">Se rifiutano:</span> <?= e($brief['fallback']) ?></p><?php endif; ?>
    </div>
    <?php endif; ?>
    <div class="mt-2 flex items-center gap-2 text-[11px] text-slate-400">
        Scheda generata il <?= date('d/m/Y H:i', strtotime((string) $a['brief_generated_at'])) ?> con <?= e((string) $a['brief_model']) ?>
        <?php if ($canEdit): ?>· <?= $btn('Rigenera', false) ?><?php endif; ?>
    </div>
<?php endif; ?>
</div>
```

- [ ] **Step 2: Includi il partial nella riga intervento e passa il costo**

In `modules/ai-reputation/views/runs/show.php`:
1. Nel closure `$actionRow`, cambia `function (array $a) use ($actionClass, $actionLabel): string {` in `function (array $a) use ($actionClass, $actionLabel, $basePath, $run, $csrf, $canEdit, $briefCost): string {`.
2. Dentro il `<div x-show="open" …>` della riga, dopo il blocco `<?php if (count($urls) > 8): ?>…<?php endif; ?>`, aggiungi:
```php
<?= \Core\View::partial('ai-reputation::partials/action-brief', ['a' => $a, 'basePath' => $basePath, 'run' => $run, 'csrf' => $csrf, 'canEdit' => $canEdit, 'briefCost' => $briefCost]) ?>
```
3. In cima al file, dopo `$isActive = …;`, aggiungi `$briefCost = $briefCost ?? 1;` (il controller lo passa; il fallback evita notice).
4. In `RunController::show()`, nell'array di `View::render`, aggiungi `'briefCost' => Credits::getCost('action_brief', Project::SLUG, 1),`.

- [ ] **Step 3: JS nel report**

In `modules/ai-reputation/views/runs/show.php`, dentro `arReport()` (oggetto ritornato), dopo il metodo `open(id) {…},` aggiungi:
```js
generatingBrief: {},
async generateBrief(actionId, ev, regenerate) {
    const box = document.getElementById('brief-' + actionId);
    if (!box || this.generatingBrief[actionId]) return;
    if (!confirm(regenerate ? 'Rigenerare la scheda? Sovrascrive quella attuale e costa <?= e((string) $briefCost) ?> crediti.' : 'Generare la scheda operativa? Costa <?= e((string) $briefCost) ?> crediti e richiede 20-40 secondi.')) return;
    this.generatingBrief[actionId] = true;
    const btn = ev.currentTarget; const oldHtml = btn.innerHTML;
    btn.disabled = true; btn.innerHTML = '<svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg> Genero la scheda…';
    try {
        const fd = new FormData(); fd.append('_csrf_token', csrf); if (regenerate) fd.append('force', '1');
        const resp = await fetch(box.dataset.briefUrl, { method: 'POST', body: fd });
        if (!resp.ok) throw new Error('Errore server (' + resp.status + ')');
        const data = await resp.json();
        if (!data.success) throw new Error(data.error || 'Generazione non riuscita');
        box.outerHTML = data.html;
    } catch (e) {
        alert(e.message); btn.disabled = false; btn.innerHTML = oldHtml;
    } finally { delete this.generatingBrief[actionId]; }
},
```
Nota: `box.outerHTML = data.html` sostituisce il contenitore con il nuovo partial; il pulsante nel nuovo HTML usa `@click` Alpine, che funziona perché il nodo resta dentro `x-data="arReport()"` (Alpine inizializza i nodi inseriti nel DOM tramite MutationObserver).

- [ ] **Step 4: Controller**

In `modules/ai-reputation/controllers/RunController.php`, dopo `exportPlanPdf()`, aggiungi (aggiungere `use Modules\AiReputation\Services\ActionBriefService;` in cima):
```php
/**
 * Genera (o rigenera con force=1) la scheda operativa di un intervento. AJAX lungo (GR 15/17/23).
 */
public function generateBrief(int $projectId, int $runId, int $actionId): void
{
    ignore_user_abort(true);
    set_time_limit(300);
    ob_start();
    header('Content-Type: application/json');
    $user = Auth::user();
    $project = $this->project->findAccessible($projectId, $user['id']);
    $run = $project ? $this->run->find($runId, $projectId) : null;
    $action = $run ? Database::fetch("SELECT * FROM ar_actions WHERE id = ? AND run_id = ? AND project_id = ?", [$actionId, $runId, $projectId]) : null;
    if (!$project || !$run || !$action) {
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'Intervento non trovato']);
        exit;
    }
    if (($project['access_role'] ?? 'owner') === 'viewer') {
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'Non autorizzato']);
        exit;
    }
    $force = !empty($_POST['force']);
    // Doppio clic: scheda appena generata → ritorna quella senza richiamare l'AI
    if (!$force && !empty($action['brief']) && !empty($action['brief_generated_at']) && strtotime($action['brief_generated_at']) > time() - 120) {
        ob_end_clean();
        echo json_encode(['success' => true, 'html' => $this->briefHtml($action, $project, $run)]);
        exit;
    }
    $creditUserId = \Services\ProjectAccessService::getCreditUserId($project, $user['id']);
    $cost = Credits::getCost('action_brief', Project::SLUG, 1);
    if (!Credits::hasEnough($creditUserId, $cost)) {
        ob_end_clean();
        http_response_code(402);
        echo json_encode(['success' => false, 'error' => 'Crediti insufficienti. Necessari: ' . $cost . ', disponibili: ' . Credits::getBalance($creditUserId)]);
        exit;
    }
    session_write_close();
    try {
        $res = (new ActionBriefService())->generate($actionId, $creditUserId);
    } catch (\Throwable $e) {
        Database::reconnect();
        Database::execute("UPDATE ar_actions SET brief_error = ? WHERE id = ?", [mb_strimwidth('Errore: ' . $e->getMessage(), 0, 490, '…'), $actionId]);
        $res = ['success' => false, 'error' => 'Errore imprevisto durante la generazione'];
    }
    if (empty($res['success'])) {
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => $res['error'] ?? 'Generazione non riuscita']);
        exit;
    }
    Credits::consume($creditUserId, $cost, 'action_brief', Project::SLUG, ['action_id' => $actionId, 'run_id' => $runId]);
    ob_end_clean();
    echo json_encode(['success' => true, 'html' => $this->briefHtml($res['action'], $project, $run)]);
    exit;
}

private function briefHtml(array $action, array $project, array $run): string
{
    return View::partial('ai-reputation::partials/action-brief', [
        'a' => $action,
        'basePath' => '/ai-reputation/project/' . $project['id'],
        'run' => $run,
        'csrf' => csrf_token(),
        'canEdit' => ($project['access_role'] ?? 'owner') !== 'viewer',
        'briefCost' => Credits::getCost('action_brief', Project::SLUG, 1),
    ]);
}
```
Nota: `AiService::complete()` scala già i crediti della propria tabella costi (`ai_analysis_*`) all'utente passato; qui si scala in più `cost_action_brief`. Se si vuole evitare il doppio addebito, passare a `generate()` lo stesso `$creditUserId` e lasciare solo l'addebito di AiService impostando `cost_action_brief` a 0 in admin: documentarlo in TASKS come scelta aperta per Clemente.

In `modules/ai-reputation/routes.php`, prima della route `GET …/runs/{runId}`:
```php
Router::post('/ai-reputation/project/{id}/runs/{runId}/actions/{actionId}/brief', function ($id, $runId, $actionId) {
    Middleware::auth();
    Middleware::csrf();
    return (new RunController())->generateBrief((int) $id, (int) $runId, (int) $actionId);
});
```

- [ ] **Step 5: Lint, commit, copia, test browser con una scheda vera**

Run:
```bash
for f in modules/ai-reputation/controllers/RunController.php modules/ai-reputation/routes.php modules/ai-reputation/views/runs/show.php modules/ai-reputation/views/partials/action-brief.php; do /c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe -l $f; done
git add modules/ai-reputation && git commit -m "feat(ai-reputation): pulsante 'Genera scheda' per intervento, route AJAX e partial della scheda operativa

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>"
bash modules/ai-reputation/scripts/sync-to-main.sh
```
Nel browser, sul run analizzato: aprire un intervento "Da pubblicare", cliccare "Genera scheda", confermare. Expected: dopo 20-40 s la riga mostra badge canale, testate (solo domini della Source Map), titolo, punti, fatti con fonte; riga "Scheda generata il … con claude-opus-5-5". Ripetere su una rimozione: destinatario, cosa chiedere, base, pagine (solo quelle dell'intervento). Controllare `storage/logs` (nessun errore) e in Admin → Log AI la chiamata con modello `claude-opus-5-5`.
Caso d'errore: in Admin → Impostazioni salvare temporaneamente una chiave Anthropic sbagliata, cliccare "Genera scheda" su un terzo intervento → riga rossa "Generazione non riuscita: …" e pulsante "Riprova"; i crediti non calano. Ripristinare la chiave, cliccare "Riprova" → scheda generata.

---

### Task 6: La scheda nel PDF

**Files:**
- Modify: `modules/ai-reputation/views/pdf/action-brief.php` (sostituisce il placeholder del Task 3)
- Modify: `modules/ai-reputation/scripts/test-action-plan-pdf.php` (aggiunge un'azione con scheda)

**Interfaces:**
- Consumes: `$a['brief_data']`, `$a['outlets']`, `$a['channel']`, `$a['channel_rationale']` preparati da `ActionPlanPdfService::html()`; `$channelLabels`, `$h` dal template padre.

- [ ] **Step 1: Estendi il test (fallisce)**

In `modules/ai-reputation/scripts/test-action-plan-pdf.php`, aggiungi all'array `$actions` (prima dell'azione scartata):
```php
['id' => 4, 'type' => 'gap_article', 'status' => 'accepted', 'title' => 'Non citato: "migliori consulenti a Napoli"', 'rationale' => 'Le AI citano altri.', 'target_url' => null, 'target_urls' => null, 'target_domain' => null,
 'channel' => 'both', 'channel_rationale' => 'Il sito ufficiale non è mai citato.', 'suggested_outlets' => json_encode(['wired.it']),
 'brief' => json_encode(['kind' => 'content', 'title' => 'Titolo di prova', 'angle' => 'Taglio', 'points' => ['p1', 'p2', 'p3'], 'facts' => [['fact' => 'Fatto', 'source' => 'wired.it']], 'avoid' => ['a1'], 'length_words' => 900, 'language' => 'it', 'own_site_note' => 'Pagina chi siamo']),
 'brief_model' => 'claude-opus-5-5', 'brief_generated_at' => '2026-10-08 11:00:00', 'brief_error' => ''],
```
e dopo i `$check` esistenti:
```php
$check('scheda nel pdf: canale e testata', str_contains($html, 'Sito proprietario + siti esterni') && str_contains($html, 'wired.it'));
$check('scheda nel pdf: brief', str_contains($html, 'Titolo di prova') && str_contains($html, 'p3') && str_contains($html, 'Pagina chi siamo'));
```
Aggiorna anche `$check('conteggi', …)` in `str_contains($html, '2 contenut')` e `$check('scheda non generata', …)` resta `=== 2`.

Run: `/c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe modules/ai-reputation/scripts/test-action-plan-pdf.php`
Expected: `FAIL scheda nel pdf: canale e testata`, `FAIL scheda nel pdf: brief`.

- [ ] **Step 2: Template della scheda nel PDF**

Sostituisci il contenuto di `modules/ai-reputation/views/pdf/action-brief.php` con:
```php
<?php
/** Scheda operativa nel PDF. Variabili dal padre: $a (con brief_data, outlets), $h, $channelLabels. */
$b = $a['brief_data'];
$requestLabel = ['removal' => 'Rimozione della pagina', 'deindex' => 'Deindicizzazione', 'update' => 'Aggiornamento del contenuto'];
$label = fn(string $t) => '<span style="font-weight:bold;color:#3730a3;">' . $h($t) . ':</span> ';
$ul = function (array $items) use ($h): string {
    if (!$items) { return ''; }
    $out = '<ul style="margin:1pt 0 3pt 12pt;padding:0;">';
    foreach ($items as $i) { $out .= '<li style="margin:0 0 1pt;">' . $h($i) . '</li>'; }
    return $out . '</ul>';
};
?>
<div style="margin-top:6pt;border-top:0.5pt dashed #c7d2fe;padding-top:5pt;font-size:9.5pt;color:#1e293b;">
    <div style="font-size:9pt;font-weight:bold;color:#4f46e5;margin-bottom:3pt;">SCHEDA OPERATIVA</div>
    <?php if ($b['kind'] === 'content'): ?>
    <div><?= $label('Dove') ?><?= $h($channelLabels[$a['channel']] ?? ($a['channel'] ?? '')) ?><?php if ($a['outlets']): ?> — testate: <?= $h(implode(', ', $a['outlets'])) ?><?php endif; ?></div>
    <?php if (!empty($a['channel_rationale'])): ?><div style="color:#475569;margin-bottom:3pt;"><?= $h($a['channel_rationale']) ?></div><?php endif; ?>
    <div><?= $label('Titolo proposto') ?><?= $h($b['title']) ?></div>
    <?php if ($b['angle']): ?><div><?= $label('Taglio') ?><?= $h($b['angle']) ?></div><?php endif; ?>
    <div><?= $label('Punti da coprire') ?><?= $ul($b['points']) ?></div>
    <?php if ($b['facts']): ?><div><?= $label('Fatti da citare') ?><?= $ul(array_map(fn($f) => $f['fact'] . ($f['source'] ? ' (fonte: ' . $f['source'] . ')' : ''), $b['facts'])) ?></div><?php endif; ?>
    <?php if ($b['avoid']): ?><div><?= $label('Da evitare') ?><?= $ul($b['avoid']) ?></div><?php endif; ?>
    <div><?= $label('Lunghezza') ?><?= $b['length_words'] ? (int) $b['length_words'] . ' parole' : 'a discrezione' ?> · <?= $label('Lingua') ?><?= $h(strtoupper($b['language'])) ?></div>
    <?php if ($b['own_site_note']): ?><div><?= $label('Sul sito ufficiale') ?><?= $h($b['own_site_note']) ?></div><?php endif; ?>
    <?php else: ?>
    <div><?= $label('A chi scrivere') ?><?= $h($b['recipient']) ?></div>
    <div><?= $label('Cosa chiedere') ?><?= $h($requestLabel[$b['request']] ?? $b['request']) ?></div>
    <div><?= $label('Su quale base') ?><?= $h($b['basis']) ?></div>
    <?php if ($b['pages']): ?><div><?= $label('Pagine') ?><ul style="margin:1pt 0 3pt 12pt;padding:0;"><?php foreach ($b['pages'] as $u): ?><li><a href="<?= $h($u) ?>" style="color:#4f46e5;text-decoration:none;"><?= $h(mb_strimwidth($u, 0, 95, '…')) ?></a></li><?php endforeach; ?></ul></div><?php endif; ?>
    <?php if ($b['fallback']): ?><div><?= $label('Se rifiutano') ?><?= $h($b['fallback']) ?></div><?php endif; ?>
    <?php endif; ?>
    <div style="margin-top:3pt;font-size:8pt;color:#94a3b8;">Scheda generata il <?= date('d/m/Y', strtotime((string) $a['brief_generated_at'])) ?></div>
</div>
```
Nota: nel PDF non si stampa il nome del modello (regola "mai esporre il metodo").

- [ ] **Step 3: Test verde, lint, commit, test browser**

Run: `/c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe -l modules/ai-reputation/views/pdf/action-brief.php && /c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe modules/ai-reputation/scripts/test-action-plan-pdf.php`
Expected: `No syntax errors`, 11 `PASS`. Aprire `storage/cache/test-action-plan.pdf` e controllare la scheda.
```bash
git add modules/ai-reputation && git commit -m "feat(ai-reputation): scheda operativa nel PDF del piano

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>"
bash modules/ai-reputation/scripts/sync-to-main.sh
```
Nel browser: "Esporta PDF" sul run con le schede generate nel Task 5 → nel PDF le schede compaiono sotto i rispettivi interventi; gli altri hanno "Scheda operativa non generata".

---

### Task 7: Documentazione del modulo

**Files:**
- Modify: `modules/ai-reputation/docs/decisions.md` (append ADR-012, ADR-013)
- Modify: `modules/ai-reputation/docs/TASKS.md` (sezione "Dove siamo" / "Prossimo passo" / "Fatto")
- Modify: `modules/ai-reputation/docs/roadmap.md` (voce M1.9)
- Modify: `modules/ai-reputation/CLAUDE.md` (tabella documenti: ADR fino a 013; riga "Ambiente locale" → aggiungere il worktree + sync script)
- Modify: `docs/superpowers/specs/2026-10-08-ai-reputation-schede-operative-pdf-design.md` (§7: la guida utente del modulo non esiste ancora → rimandata al task GR18 già aperto in TASKS)

- [ ] **Step 1: ADR**

Append a `modules/ai-reputation/docs/decisions.md` (formato degli ADR esistenti: `## ADR-NNN: titolo`, `**Date**`, `**Status**`, `**Context**`, `**Decision**`):
```markdown
## ADR-012: Il canale di pubblicazione lo decide l'AI, e il sito proprietario è un canale

**Date**: 2026-10-08 · **Status**: Accepted (richiesta di Clemente)

**Context**: il piano d'azione suggeriva una sola "testata suggerita" (il dominio ok più citato) e non
considerava mai il sito ufficiale del soggetto. Chi esegue gli interventi deve sapere dove pubblicare e perché.

**Decision**: per ogni intervento di contenuto, su richiesta ("Genera scheda"), un modello AI decide il canale
(`own_site`, `external`, `both`) leggendo il dossier del run (profilo confermato, verdetti, Source Map, se il sito
ufficiale è mai citato, competitor) e suggerisce fino a 3 testate **solo tra i domini che le AI già citano come
fonti affidabili** (validazione lato server: le altre vengono scartate). Per le rimozioni il canale non si applica.
Il "sito ufficiale mai citato dalle AI" è un segnale esplicito a favore di `own_site`/`both`.

## ADR-013: Schede operative su Claude Opus 5.5, per singolo intervento, judge invariato

**Date**: 2026-10-08 · **Status**: Accepted

**Context**: scegliere canale e scrivere un brief corretto sui fatti richiede ragionamento; il judge gira sul modello
del modulo (Sonnet 4) e deve restare stabile tra run (regola 6). I modelli Claude 5.5 non erano nel listino di AiService.

**Decision**: setting `brief_model` (default `claude-opus-5-5`, effort `high`) usato solo da `ActionBriefService`;
il judge non cambia. Generazione su richiesta, un intervento per volta (niente batch, niente lock), 1 credito a scheda
(`cost_action_brief`), salvata in `ar_actions` e mostrata identica nel report web e nel PDF. Il PDF è sempre la
stampa dello stato corrente degli interventi. `AiService::MODELS` include ora Opus/Sonnet/Haiku 5.5 e `complete()`
accetta `effort` e `timeout`. Il modello salvato è quello realmente usato (fallback incluso). Nel PDF non si stampa
il nome del modello.
```

- [ ] **Step 2: TASKS, roadmap, CLAUDE.md, spec**

In `modules/ai-reputation/docs/TASKS.md`:
- "Ultimo aggiornamento": `2026-10-08`.
- In "Dove siamo", dopo la riga sul run di riferimento, aggiungi: `**Export PDF del piano e "Genera scheda" (Opus 5.5) per intervento: fatti il 2026-10-08** (spec in docs/superpowers/specs/2026-10-08-…, ADR-012/013). Deploy in produzione: vedi Prossimo passo.`
- In "Prossimo passo": sostituisci il contenuto con: `Deploy su ainstein.it di PDF + schede: git pull, migrazione 2026-10-08-actions-brief.sql, prova su un run reale (Task 9 del piano). Poi la prova online del Radar con un progetto di test.`
- In "Dopo (in ordine)", aggiungi in cima: `0. Scelta aperta: oggi "Genera scheda" scala sia il costo AiService (ai_analysis_*) sia cost_action_brief (1 credito): decidere se tenere entrambi o azzerare cost_action_brief.` e `0b. Fase C: PDF per il cliente finale (sintesi, meno dettaglio operativo), stesso motore ActionPlanPdfService.`
- Alla voce 4 (Golden Rule 18) aggiungi: `(deve includere la sezione "Schede operative e PDF")`.

In `modules/ai-reputation/docs/roadmap.md`, nella sezione M1 aggiungi la riga: `- Export PDF del piano + scheda operativa per intervento (Opus 5.5) — fatto 2026-10-08.`

In `modules/ai-reputation/CLAUDE.md`: tabella documenti → `docs/decisions.md | ADR-001..013`; sezione "Ambiente locale" → aggiungi: `Postazione LaptopoClem (Laragon): si lavora nel worktree .claude/worktrees/ai-reputation-m0-3-69e6ee sul branch del modulo; per vedere le modifiche su http://localhost/seo-toolkit si lancia bash modules/ai-reputation/scripts/sync-to-main.sh dopo il commit (il checkout principale è sul branch Editorial).`

Nello spec, §7, sostituisci la riga sulla guida utente con: `- Guida utente: la pagina shared/views/docs/ai-reputation.php non esiste ancora; è il task GR18 già aperto in TASKS (voce 4), che includerà la sezione "Schede operative e PDF". data-model.html non documenta ancora le tabelle ar_*: stesso task.`

- [ ] **Step 3: Commit e push**

```bash
git add modules/ai-reputation/docs modules/ai-reputation/CLAUDE.md docs/superpowers/specs/2026-10-08-ai-reputation-schede-operative-pdf-design.md
git commit -m "docs(ai-reputation): ADR-012/013, TASKS, roadmap e CLAUDE.md per PDF e schede operative

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>"
git push origin claude/ai-reputation-radar-dd9004
```

---

### Task 8: Revisione finale in locale (checklist pre-commit del progetto)

**Files:** nessuno nuovo.

- [ ] **Step 1: Checklist**

Verificare, leggendo il diff `git diff 353c39d..HEAD -- modules/ai-reputation services/AiService.php`:
- nessun `curl` diretto verso API AI (solo `AiService`);
- icone solo Heroicons SVG; testi UI in italiano; CSRF `_csrf_token`;
- `ob_end_clean()` prima di ogni `echo json_encode` in `generateBrief()` (contare: devono essere 6 echo, 6 `ob_end_clean`);
- `response.ok` prima di `response.json()` nel JS;
- prepared statements ovunque (`?` nei `Database::`);
- nessuna API key o segreto nei file.

Run: `grep -n "echo json_encode" modules/ai-reputation/controllers/RunController.php | wc -l; grep -n "ob_end_clean" modules/ai-reputation/controllers/RunController.php | wc -l`
Expected: il secondo numero ≥ numero di `echo json_encode` dentro `generateBrief()` (gli altri metodi del controller hanno il proprio pattern già esistente).

- [ ] **Step 2: Tutti i test CLI**

Run: `for t in test-aiservice-options test-action-plan-pdf test-action-brief-validate; do /c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe modules/ai-reputation/scripts/$t.php | grep -c FAIL; done`
Expected: `0` tre volte.

- [ ] **Step 3: Pulizia**

Run: `rm -f storage/cache/test-action-plan.pdf; git status --short`
Expected: working tree pulito (solo `.cervello/` se già presente come non tracciato).

---

### Task 9: Deploy in produzione (ainstein.it) — solo dopo l'ok di Clemente

**Files:** nessuno (operazioni sul server `184.174.32.213`, utente `ainstein`, chiave `~/.ssh/ainstein_hetzner`).

- [ ] **Step 1: Chiedere l'ok a Clemente** (azione esterna: aggiorna il sito pubblico). Senza ok, fermarsi qui.

- [ ] **Step 2: Merge fast-forward in main e push**

Run: `git fetch -q origin && git merge-base --is-ancestor origin/main claude/ai-reputation-radar-dd9004 && git push origin claude/ai-reputation-radar-dd9004:main`
Expected: `… claude/ai-reputation-radar-dd9004 -> main`. Se il primo comando fallisce (main è andato avanti), fermarsi e riferire: serve un merge vero.

- [ ] **Step 3: Pull + migrazione + backup prima**

Run:
```bash
ssh -o BatchMode=yes -i ~/.ssh/ainstein_hetzner ainstein@184.174.32.213 "/home/ainstein/backup-db.sh 2>/dev/null; cd /var/www/ainstein.it/public_html && git pull -q origin main && mysql -u ainstein -p'Ainstein_DB_2026!Secure' ainstein_seo < modules/ai-reputation/database/2026-10-08-actions-brief.sql 2>&1 | grep -v 'Using a password'; git log --oneline -1; mysql -u ainstein -p'Ainstein_DB_2026!Secure' -N ainstein_seo -e 'SHOW COLUMNS FROM ar_actions LIKE \"brief\"' 2>&1 | grep -v 'Using a password'; mkdir -p storage/cache/mpdf && sudo chgrp www-data storage/cache/mpdf && chmod 2775 storage/cache/mpdf"
```
Expected: ultimo commit = quello del Task 7; riga `brief json …`.

- [ ] **Step 4: Verifica online**

Nel browser su `https://ainstein.it`, con l'account di test, su un run analizzato: "Esporta PDF" scarica il PDF; "Genera scheda" su un intervento produce la scheda (verifica in Admin → Log AI il modello `claude-opus-5-5`). Controllare `sudo tail -20 /var/log/apache2/ainstein-error.log` e `storage/logs`: nessun errore.

- [ ] **Step 5: Aggiornare TASKS e memoria**

In `modules/ai-reputation/docs/TASKS.md` segnare il deploy fatto con la data; commit + push su branch e `main`; `git pull` sul server. Aggiornare la memoria di progetto (`project_prod_down_hetzner.md`) con una riga: "PDF + schede in produzione dal <data>".
