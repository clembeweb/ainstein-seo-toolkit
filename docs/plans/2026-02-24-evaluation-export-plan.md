# Evaluation Export (PDF + CSV) Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add PDF report export and per-section CSV Google Ads Editor export to campaign evaluation page.

**Architecture:** mpdf for PDF generation from a dedicated HTML template (table-based layout). EvaluationGeneratorService modified to return JSON instead of text. New EvaluationCsvService reuses CampaignCreatorService CSV helpers. View updated with export buttons and structured preview.

**Tech Stack:** mpdf/mpdf (composer), PHP 8+, Alpine.js, existing CampaignCreatorService CSV format

---

### Task 1: Install mpdf

**Files:**
- Modify: `composer.json`

**Step 1: Add mpdf dependency**

```bash
composer require mpdf/mpdf
```

**Step 2: Verify installation**

```bash
php -r "require 'vendor/autoload.php'; new \Mpdf\Mpdf(); echo 'OK';"
```

Expected: `OK`

**Step 3: Commit**

```bash
git add composer.json composer.lock vendor/
git commit -m "chore: add mpdf/mpdf for PDF report generation"
```

---

### Task 2: Create EvaluationPdfService

**Files:**
- Create: `modules/ads-analyzer/services/EvaluationPdfService.php`

**Context:** This service receives evaluation data (same `$aiResponse` array and `$evaluation` record used in `evaluation.php` view) and generates a PDF using mpdf. Layout is table-based (mpdf doesn't support flexbox/grid). Colors match the app theme (slate/rose for ads-analyzer).

**Step 1: Create the service**

Create `modules/ads-analyzer/services/EvaluationPdfService.php`:

```php
<?php

namespace Modules\AdsAnalyzer\Services;

/**
 * Genera PDF report dalla valutazione campagne.
 * Usa mpdf con template HTML table-based.
 */
class EvaluationPdfService
{
    /**
     * Genera PDF e lo restituisce come stringa binaria.
     *
     * @param array $evaluation  Record da ga_campaign_evaluations
     * @param array $aiResponse  JSON decodificato da ai_response
     * @param array $project     Record progetto (id, name)
     * @return string PDF binary content
     */
    public function generate(array $evaluation, array $aiResponse, array $project): string
    {
        $html = $this->buildHtml($evaluation, $aiResponse, $project);

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 25,
            'margin_bottom' => 20,
            'margin_header' => 10,
            'margin_footer' => 10,
            'default_font' => 'dejavusans',
            'tempDir' => sys_get_temp_dir() . '/mpdf',
        ]);

        // Header
        $mpdf->SetHTMLHeader($this->buildHeader($project));
        // Footer
        $mpdf->SetHTMLFooter($this->buildFooter());

        $mpdf->WriteHTML($html);

        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    private function buildHeader(array $project): string
    {
        $name = htmlspecialchars($project['name'] ?? 'Progetto');
        return '<table width="100%" style="border-bottom:1px solid #cbd5e1;margin-bottom:5mm;font-size:9pt;color:#64748b;">
            <tr>
                <td style="width:33%"><strong style="color:#e11d48;">Ainstein</strong></td>
                <td style="width:34%;text-align:center;">' . $name . '</td>
                <td style="width:33%;text-align:right;">{DATE d/m/Y}</td>
            </tr>
        </table>';
    }

    private function buildFooter(): string
    {
        return '<table width="100%" style="border-top:1px solid #cbd5e1;font-size:8pt;color:#94a3b8;">
            <tr>
                <td style="width:50%">Generato da Ainstein - ainstein.it</td>
                <td style="width:50%;text-align:right;">Pagina {PAGENO} di {nbpg}</td>
            </tr>
        </table>';
    }

    /**
     * Costruisce l'HTML completo del report.
     * NOTA: Usare solo table, div, p, h1-h4, span, strong, br — NO flexbox, NO grid.
     */
    private function buildHtml(array $evaluation, array $ai, array $project): string
    {
        $html = $this->getStyles();

        // 1. Copertina
        $html .= $this->buildCoverPage($evaluation, $ai, $project);

        // 2. Executive Summary + Metriche
        $html .= $this->buildSummarySection($evaluation, $ai);

        // 3. Raccomandazioni top
        $html .= $this->buildRecommendations($ai);

        // 4. Analisi per campagna
        $html .= $this->buildCampaignSections($ai);

        // 5. Estensioni
        $html .= $this->buildExtensionsSection($ai);

        // 6. Landing pages
        if (($evaluation['landing_pages_analyzed'] ?? 0) > 0) {
            $html .= $this->buildLandingSection($ai);
        }

        // 7. Suggerimenti miglioramento
        $html .= $this->buildSuggestionsSection($ai);

        return $html;
    }

    // ... Implementare ogni metodo build*() con HTML table-based
    // Vedi evaluation-pdf.php per il template completo
}
```

Nota: il corpo completo dei metodi `build*()` sara implementato nel template dedicato (Task 3). Qui il service fa da orchestratore. **L'approccio finale sara**: il service include il template PHP che restituisce l'HTML, cosi il template e separato e manutenibile.

**Approccio definitivo** — il service fa:
1. `ob_start()` + `include` del template `evaluation-pdf.php` + `ob_get_clean()` = HTML
2. Passa l'HTML a mpdf
3. Restituisce il PDF binary

Cosi manteniamo la separazione template/logica identica al pattern View::render().

**Step 2: Verify syntax**

```bash
php -l modules/ads-analyzer/services/EvaluationPdfService.php
```

Expected: No syntax errors

---

### Task 3: Create PDF HTML template

**Files:**
- Create: `modules/ads-analyzer/views/campaigns/evaluation-pdf.php`

**Context:** Template PHP che riceve `$evaluation`, `$ai`, `$project` e genera HTML table-based per mpdf. Lo stile e inline CSS (mpdf non supporta classi Tailwind). Il layout replica le stesse sezioni di `evaluation.php`:

**Variabili disponibili:** `$evaluation` (record DB), `$ai` (array da `ai_response`), `$project` (record progetto)

**Sezioni (ordine):**

1. **Copertina** — Logo testuale "AINSTEIN", nome progetto grande, data valutazione, punteggio overall con cerchio colorato, trend badge, sommario

2. **Metriche delta** (se presenti) — Tabella 2-3 colonne: metrica, variazione %, valore attuale. Colori: verde up, rosso down, grigio stabile.

3. **Raccomandazioni principali** — Lista numerata 1-7, sfondo giallo chiaro per ogni item

4. **Analisi campagne** — Per ogni campagna:
   - Header con score + nome + tipo (SEARCH/PMAX/etc)
   - Punti di forza (bullet list verde)
   - Problemi (tabella: Priorita | Area | Problema | Raccomandazione)
   - Ad Groups (sotto-tabella nested con score, metriche, problemi)

5. **Estensioni** — Score, tabella mancanti vs presenti, suggerimenti

6. **Landing pages** (solo se `landing_pages_analyzed > 0`) — Score, tabella URL + issue + raccomandazione

7. **Suggerimenti** — Tabella: Priorita | Area | Suggerimento | Impatto atteso

**Palette colori CSS inline:**
- Score < 5: `#ef4444` (rosso)
- Score 5-7: `#f59e0b` (ambra)
- Score > 7: `#10b981` (verde)
- Header: `#1e293b` (slate-800)
- Body text: `#334155` (slate-700)
- Accent: `#e11d48` (rose-600, colore modulo ads-analyzer)
- Bg alternato righe: `#f8fafc` (slate-50)

**Step 1: Creare il template**

File: `modules/ads-analyzer/views/campaigns/evaluation-pdf.php`

Il template usa `<?php ?>` standard con variabili `$evaluation`, `$ai`, `$project`. No Tailwind, no Alpine — solo HTML+CSS inline table-based compatibile mpdf.

**Step 2: Aggiornare EvaluationPdfService per usare il template**

In `EvaluationPdfService::buildHtml()`:
```php
private function buildHtml(array $evaluation, array $ai, array $project): string
{
    ob_start();
    include __DIR__ . '/../views/campaigns/evaluation-pdf.php';
    return ob_get_clean();
}
```

Rimuovere i metodi `build*()` stub e spostare la logica nel template.

**Step 3: Verify syntax**

```bash
php -l modules/ads-analyzer/views/campaigns/evaluation-pdf.php
php -l modules/ads-analyzer/services/EvaluationPdfService.php
```

**Step 4: Commit**

```bash
git add modules/ads-analyzer/services/EvaluationPdfService.php modules/ads-analyzer/views/campaigns/evaluation-pdf.php
git commit -m "feat: add EvaluationPdfService and PDF template for campaign reports"
```

---

### Task 4: Add PDF export route and controller method

**Files:**
- Modify: `modules/ads-analyzer/routes.php:225` (dopo la route generate)
- Modify: `modules/ads-analyzer/controllers/CampaignController.php`

**Step 1: Add route**

In `modules/ads-analyzer/routes.php`, dopo la riga 225 (route generate), aggiungere:

```php
// Export PDF valutazione
Router::get('/ads-analyzer/projects/{id}/campaigns/evaluations/{evalId}/export-pdf', function ($id, $evalId) {
    Middleware::auth();
    $controller = new CampaignController();
    return $controller->exportPdf((int) $id, (int) $evalId);
});
```

**Step 2: Add controller method**

In `CampaignController.php`, aggiungere metodo `exportPdf()`:

```php
/**
 * Export PDF del report valutazione
 */
public function exportPdf(int $projectId, int $evalId): void
{
    $user = Auth::user();
    $project = Project::findByUserAndId($user['id'], $projectId);

    if (!$project || ($project['type'] ?? 'negative-kw') !== 'campaign') {
        $_SESSION['flash_error'] = 'Progetto non valido';
        header('Location: ' . url('/ads-analyzer/projects/' . $projectId . '/campaigns'));
        exit;
    }

    $evaluation = CampaignEvaluation::find($evalId);
    if (!$evaluation || $evaluation['project_id'] != $projectId || $evaluation['status'] !== 'completed') {
        $_SESSION['flash_error'] = 'Valutazione non trovata';
        header('Location: ' . url('/ads-analyzer/projects/' . $projectId . '/campaigns'));
        exit;
    }

    $aiResponse = json_decode($evaluation['ai_response'] ?? '{}', true) ?: [];
    if (empty($aiResponse)) {
        $_SESSION['flash_error'] = 'Nessun dato da esportare';
        header('Location: ' . url("/ads-analyzer/projects/{$projectId}/campaigns/evaluations/{$evalId}"));
        exit;
    }

    require_once __DIR__ . '/../services/EvaluationPdfService.php';
    $service = new \Modules\AdsAnalyzer\Services\EvaluationPdfService();
    $pdf = $service->generate($evaluation, $aiResponse, $project);

    $filename = 'report-' . preg_replace('/[^a-z0-9-]/', '', strtolower($project['name'])) . '-' . date('Y-m-d') . '.pdf';

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($pdf));
    echo $pdf;
    exit;
}
```

**Step 3: Add require per il model CampaignEvaluation** (verificare che sia gia importato — lo e, usato in `evaluationShow()`)

**Step 4: Verify syntax**

```bash
php -l modules/ads-analyzer/controllers/CampaignController.php
php -l modules/ads-analyzer/routes.php
```

**Step 5: Commit**

```bash
git add modules/ads-analyzer/routes.php modules/ads-analyzer/controllers/CampaignController.php
git commit -m "feat: add PDF export route and controller method for evaluations"
```

---

### Task 5: Add PDF export button to evaluation view

**Files:**
- Modify: `modules/ads-analyzer/views/campaigns/evaluation.php`

**Step 1: Add export button**

In `evaluation.php`, nella sezione header del punteggio overall (circa riga 280-290, dove c'e il badge "Auto"/"Manuale" e il trend), aggiungere un bottone "Esporta PDF" accanto:

Trovare la sezione dopo il metadata (data, campagne valutate, annunci, crediti) e prima delle sezioni analisi. Aggiungere:

```php
<?php if ($hasResults): ?>
<div class="flex justify-end mt-4">
    <a href="<?= url("/ads-analyzer/projects/{$project['id']}/campaigns/evaluations/{$evaluation['id']}/export-pdf") ?>"
       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-lg text-rose-700 bg-rose-50 hover:bg-rose-100 dark:text-rose-300 dark:bg-rose-900/30 dark:hover:bg-rose-900/50 transition-colors"
       target="_blank">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        Esporta PDF
    </a>
</div>
<?php endif; ?>
```

**Step 2: Verify syntax**

```bash
php -l modules/ads-analyzer/views/campaigns/evaluation.php
```

**Step 3: Commit**

```bash
git add modules/ads-analyzer/views/campaigns/evaluation.php
git commit -m "feat: add PDF export button to evaluation view"
```

---

### Task 6: Modify EvaluationGeneratorService for JSON output

**Files:**
- Modify: `modules/ads-analyzer/services/EvaluationGeneratorService.php`

**Context:** Attualmente `generate()` restituisce testo formattato. Deve restituire un array PHP (JSON-decodificabile) con struttura specifica per tipo. La view formattera il JSON. Il controller serializzera in JSON per il frontend.

**Step 1: Modify `generate()` return type and prompts**

Cambiare signature: `public function generate(...): array` (era `string`)

Cambiare `cleanResponse()` in `parseJsonResponse()`:
```php
private function parseJsonResponse(string $text): array
{
    $text = preg_replace('/^```(?:json)?\s*/i', '', trim($text));
    $text = preg_replace('/\n?```$/', '', $text);
    $text = trim($text);

    $result = json_decode($text, true);
    if ($result === null && json_last_error() !== JSON_ERROR_NONE) {
        throw new \Exception('Risposta AI non valida (JSON): ' . json_last_error_msg());
    }
    return $result;
}
```

Aggiornare la riga 57: `return $this->parseJsonResponse($response['result']);`

**Step 2: Modify prompts to request JSON**

**buildCopyPrompt** — cambiare le istruzioni finali:
```
GENERA in formato JSON esatto (NESSUN testo fuori dal JSON):
{
  "headlines": ["Headline 1 (max 30 char)", "Headline 2", ...],
  "descriptions": ["Description 1 (max 90 char)", "Description 2", ...],
  "paths": {"path1": "percorso1", "path2": "percorso2"}
}

Regole:
- Esattamente 5 headlines, ciascuna max 30 caratteri
- Esattamente 3 descriptions, ciascuna max 90 caratteri
- Paths opzionali, max 15 caratteri ciascuno
- SOLO JSON valido, nessun commento o testo aggiuntivo
```

**buildExtensionsPrompt** — cambiare le istruzioni finali:
```
GENERA in formato JSON esatto (NESSUN testo fuori dal JSON):
{
  "sitelinks": [
    {"title": "Titolo (max 25)", "desc1": "Desc 1 (max 35)", "desc2": "Desc 2 (max 35)", "url": "/percorso"}
  ],
  "callouts": ["Callout 1 (max 25)", "Callout 2", ...],
  "structured_snippets": [
    {"header": "Servizi", "values": ["Valore 1 (max 25)", "Valore 2", ...]}
  ]
}

Regole:
- 4 sitelink se SITELINK e tra le mancanti
- 6 callout se CALLOUT e tra le mancanti
- 1-2 structured snippet se STRUCTURED SNIPPET e tra le mancanti
- Genera SOLO i tipi elencati in ESTENSIONI MANCANTI
- SOLO JSON valido, nessun commento o testo aggiuntivo
```

**buildKeywordsPrompt** — cambiare le istruzioni finali:
```
GENERA in formato JSON esatto (NESSUN testo fuori dal JSON):
{
  "keywords": [
    {"keyword": "keyword text", "match_type": "phrase", "is_negative": true, "reason": "Motivazione breve"},
    ...
  ]
}

Regole:
- 15-20 keyword negative
- match_type: "exact" o "phrase"
- is_negative: sempre true
- SOLO JSON valido, nessun commento o testo aggiuntivo
```

**Step 3: Verify syntax**

```bash
php -l modules/ads-analyzer/services/EvaluationGeneratorService.php
```

**Step 4: Commit**

```bash
git add modules/ads-analyzer/services/EvaluationGeneratorService.php
git commit -m "refactor: EvaluationGeneratorService returns structured JSON instead of text"
```

---

### Task 7: Update controller and view for JSON responses + CSV export button

**Files:**
- Modify: `modules/ads-analyzer/controllers/CampaignController.php:540-545` (generateFix response)
- Modify: `modules/ads-analyzer/views/campaigns/evaluation.php` (renderAiGenerator function + Alpine.js)

**Context:** Il controller `generateFix()` ora riceve un array da `generate()`. Deve restituirlo come JSON strutturato. La view deve mostrare l'anteprima formattata e un bottone "Esporta CSV per Ads Editor".

**Step 1: Update controller generateFix()**

In `CampaignController.php`, riga 544-545, cambiare:

```php
// PRIMA:
// $result = $service->generate($user['id'], $type, $context, $campaignData);
// echo json_encode(['success' => true, 'content' => $result]);

// DOPO:
$result = $service->generate($user['id'], $type, $context, $campaignData);

Database::reconnect();

ob_end_clean();
echo json_encode([
    'success' => true,
    'type' => $type,
    'data' => $result,
    'content' => $this->formatFixForDisplay($type, $result),
]);
exit;
```

Aggiungere metodo helper `formatFixForDisplay()` al controller:

```php
/**
 * Formatta i dati JSON del fix per visualizzazione testo leggibile (fallback)
 */
private function formatFixForDisplay(string $type, array $data): string
{
    $lines = [];

    if ($type === 'copy' || str_contains($type, 'copy')) {
        $lines[] = "HEADLINES:";
        foreach (($data['headlines'] ?? []) as $i => $h) {
            $lines[] = ($i + 1) . ". " . $h . " (" . mb_strlen($h) . " car.)";
        }
        $lines[] = "";
        $lines[] = "DESCRIPTIONS:";
        foreach (($data['descriptions'] ?? []) as $i => $d) {
            $lines[] = ($i + 1) . ". " . $d . " (" . mb_strlen($d) . " car.)";
        }
        if (!empty($data['paths'])) {
            $lines[] = "";
            $lines[] = "PATHS: /" . ($data['paths']['path1'] ?? '') . " / " . ($data['paths']['path2'] ?? '');
        }
    } elseif ($type === 'extensions' || str_contains($type, 'extensions')) {
        if (!empty($data['sitelinks'])) {
            $lines[] = "SITELINKS:";
            foreach ($data['sitelinks'] as $i => $sl) {
                $lines[] = ($i + 1) . ". " . ($sl['title'] ?? '') . " (" . mb_strlen($sl['title'] ?? '') . " car.)";
                $lines[] = "   Desc 1: " . ($sl['desc1'] ?? '');
                $lines[] = "   Desc 2: " . ($sl['desc2'] ?? '');
                $lines[] = "   URL: " . ($sl['url'] ?? '');
            }
        }
        if (!empty($data['callouts'])) {
            $lines[] = "";
            $lines[] = "CALLOUTS:";
            foreach ($data['callouts'] as $i => $c) {
                $lines[] = ($i + 1) . ". " . $c . " (" . mb_strlen($c) . " car.)";
            }
        }
        if (!empty($data['structured_snippets'])) {
            $lines[] = "";
            $lines[] = "STRUCTURED SNIPPETS:";
            foreach ($data['structured_snippets'] as $ss) {
                $lines[] = "Header: " . ($ss['header'] ?? '');
                $lines[] = "Valori: " . implode(', ', $ss['values'] ?? []);
            }
        }
    } elseif ($type === 'keywords' || str_contains($type, 'keywords')) {
        $lines[] = "KEYWORD NEGATIVE:";
        foreach (($data['keywords'] ?? []) as $i => $kw) {
            $match = $kw['match_type'] ?? 'phrase';
            $lines[] = ($i + 1) . ". " . ($kw['keyword'] ?? '') . " [{$match}]" . (!empty($kw['reason']) ? " - " . $kw['reason'] : '');
        }
    }

    return implode("\n", $lines);
}
```

**Step 2: Update Alpine.js `generateFix()` and `renderAiGenerator()` in view**

In `evaluation.php`, aggiornare la funzione `generateFix()` nell'Alpine component per salvare anche `type` e `data`:

```javascript
// Riga ~1114, cambiare:
this.generators[key] = { loading: false, result: data.content || '', error: null, copied: false };
// In:
this.generators[key] = { loading: false, result: data.content || '', type: data.type || '', data: data.data || null, error: null, copied: false };
```

Aggiungere metodo `exportCsv(key)` all'Alpine component:

```javascript
exportCsv(key) {
    const gen = this.generators[key];
    if (!gen || !gen.data) return;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = this.generateUrl.replace('/generate', '/export-csv');
    form.target = '_blank';

    const fields = {
        '_csrf_token': this.csrfToken,
        'type': gen.type,
        'data': JSON.stringify(gen.data),
        'campaign_name': '<?= e($project['name'] ?? '') ?>',
    };

    for (const [name, value] of Object.entries(fields)) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        form.appendChild(input);
    }

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
},
```

**Step 3: Update `renderAiGenerator()` function**

Aggiornare la funzione PHP `renderAiGenerator()` (riga 70-98) per aggiungere il bottone CSV accanto a "Copia":

Dopo il bottone Copia (riga ~91), aggiungere:

```html
<button x-show="generators['{$key}']?.data && (generators['{$key}']?.type === 'copy' || generators['{$key}']?.type === 'extensions' || generators['{$key}']?.type === 'keywords')"
    @click="exportCsv('{$key}')"
    class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium text-rose-700 bg-rose-100 hover:bg-rose-200 dark:text-rose-300 dark:bg-rose-900/40 dark:hover:bg-rose-900/60 transition-colors">
    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
    </svg>
    Esporta CSV Ads Editor
</button>
```

**Step 4: Verify syntax**

```bash
php -l modules/ads-analyzer/controllers/CampaignController.php
php -l modules/ads-analyzer/views/campaigns/evaluation.php
```

**Step 5: Commit**

```bash
git add modules/ads-analyzer/controllers/CampaignController.php modules/ads-analyzer/views/campaigns/evaluation.php
git commit -m "feat: structured JSON response from AI + CSV export button in evaluation view"
```

---

### Task 8: Create EvaluationCsvService

**Files:**
- Create: `modules/ads-analyzer/services/EvaluationCsvService.php`

**Context:** Genera CSV compatibile Google Ads Editor dai dati JSON strutturati (output di `generateFix`). Riusa lo stesso formato colonne di `CampaignCreatorService::generateSearchCsv()`.

**Step 1: Create the service**

```php
<?php

namespace Modules\AdsAnalyzer\Services;

/**
 * Genera CSV compatibile Google Ads Editor dai fix AI della valutazione.
 * Formato colonne identico a CampaignCreatorService.
 */
class EvaluationCsvService
{
    /**
     * Genera CSV da dati fix strutturati
     *
     * @param string $type       extensions|copy|keywords
     * @param array  $data       Dati JSON strutturati dal fix AI
     * @param string $campaignName Nome campagna per la colonna Campaign
     * @return string CSV content con BOM UTF-8
     */
    public function generate(string $type, array $data, string $campaignName = ''): string
    {
        $rows = match (true) {
            str_contains($type, 'copy')       => $this->generateCopyCsv($data, $campaignName),
            str_contains($type, 'extensions') => $this->generateExtensionsCsv($data, $campaignName),
            str_contains($type, 'keywords')   => $this->generateKeywordsCsv($data, $campaignName),
            default => throw new \Exception('Tipo CSV non supportato: ' . $type),
        };

        // BOM UTF-8 + righe CSV
        $output = "\xEF\xBB\xBF";
        foreach ($rows as $row) {
            $output .= $this->csvLine($row);
        }
        return $output;
    }

    /**
     * CSV per copy (headlines + descriptions) — formato RSA Google Ads Editor
     */
    private function generateCopyCsv(array $data, string $campaignName): array
    {
        $rows = [];
        $totalCols = 27; // Campaign, Ad Group, H1-H15, D1-D4, Path1, Path2, Final URL

        $rows[] = ['Campaign', 'Ad Group',
            'Headline 1', 'Headline 2', 'Headline 3', 'Headline 4', 'Headline 5',
            'Headline 6', 'Headline 7', 'Headline 8', 'Headline 9', 'Headline 10',
            'Headline 11', 'Headline 12', 'Headline 13', 'Headline 14', 'Headline 15',
            'Description 1', 'Description 2', 'Description 3', 'Description 4',
            'Path 1', 'Path 2', 'Final URL'];

        $rsaData = array_fill(0, $totalCols, '');
        $rsaData[0] = $campaignName;
        // Headlines
        foreach (($data['headlines'] ?? []) as $i => $h) {
            if ($i < 15) $rsaData[2 + $i] = $h;
        }
        // Descriptions
        foreach (($data['descriptions'] ?? []) as $i => $d) {
            if ($i < 4) $rsaData[17 + $i] = $d;
        }
        // Paths
        $rsaData[21] = $data['paths']['path1'] ?? '';
        $rsaData[22] = $data['paths']['path2'] ?? '';

        $rows[] = $rsaData;
        return $rows;
    }

    /**
     * CSV per estensioni — formato Google Ads Editor
     */
    private function generateExtensionsCsv(array $data, string $campaignName): array
    {
        $rows = [];
        $totalCols = 7;

        $rows[] = ['Row Type', 'Campaign',
            'Sitelink Text', 'Sitelink Description Line 1', 'Sitelink Description Line 2', 'Sitelink Final URL',
            'Callout Text'];

        // Sitelinks
        foreach (($data['sitelinks'] ?? []) as $sl) {
            $row = array_fill(0, $totalCols, '');
            $row[0] = 'Sitelink';
            $row[1] = $campaignName;
            $row[2] = $sl['title'] ?? '';
            $row[3] = $sl['desc1'] ?? '';
            $row[4] = $sl['desc2'] ?? '';
            $row[5] = $sl['url'] ?? '';
            $rows[] = $row;
        }

        // Callouts
        foreach (($data['callouts'] ?? []) as $callout) {
            $row = array_fill(0, $totalCols, '');
            $row[0] = 'Callout';
            $row[1] = $campaignName;
            $row[6] = $callout;
            $rows[] = $row;
        }

        // Structured Snippets — colonne dedicate
        if (!empty($data['structured_snippets'])) {
            // Aggiungi header extra se servono
            foreach ($data['structured_snippets'] as $ss) {
                $row = array_fill(0, $totalCols + 2, '');
                $row[0] = 'Structured Snippet';
                $row[1] = $campaignName;
                $row[$totalCols] = $ss['header'] ?? '';
                $row[$totalCols + 1] = implode(';', $ss['values'] ?? []);
                $rows[] = $row;
            }
            // Aggiorna header
            $rows[0][] = 'Structured Snippet Header';
            $rows[0][] = 'Structured Snippet Values';
        }

        return $rows;
    }

    /**
     * CSV per keyword negative — formato Google Ads Editor
     */
    private function generateKeywordsCsv(array $data, string $campaignName): array
    {
        $rows = [];
        $rows[] = ['Campaign', 'Keyword', 'Criterion Type'];

        foreach (($data['keywords'] ?? []) as $kw) {
            $rows[] = [
                $campaignName,
                $kw['keyword'] ?? '',
                ucfirst($kw['match_type'] ?? 'phrase'),
            ];
        }

        return $rows;
    }

    /**
     * Escape e formattazione riga CSV (identica a CampaignCreatorService)
     */
    private function csvLine(array $fields): string
    {
        $escaped = array_map(function ($field) {
            $field = (string) $field;
            if (strpos($field, ',') !== false || strpos($field, '"') !== false || strpos($field, "\n") !== false) {
                return '"' . str_replace('"', '""', $field) . '"';
            }
            return $field;
        }, $fields);
        return implode(',', $escaped) . "\r\n";
    }
}
```

**Step 2: Verify syntax**

```bash
php -l modules/ads-analyzer/services/EvaluationCsvService.php
```

**Step 3: Commit**

```bash
git add modules/ads-analyzer/services/EvaluationCsvService.php
git commit -m "feat: add EvaluationCsvService for Google Ads Editor CSV export from AI fixes"
```

---

### Task 9: Add CSV export route and controller method

**Files:**
- Modify: `modules/ads-analyzer/routes.php` (dopo la route export-pdf)
- Modify: `modules/ads-analyzer/controllers/CampaignController.php`

**Step 1: Add route**

```php
// Export CSV fix AI per Google Ads Editor
Router::post('/ads-analyzer/projects/{id}/campaigns/evaluations/{evalId}/export-csv', function ($id, $evalId) {
    Middleware::auth();
    Middleware::csrf();
    $controller = new CampaignController();
    return $controller->exportCsv((int) $id, (int) $evalId);
});
```

**Step 2: Add controller method**

```php
/**
 * Export CSV Google Ads Editor da fix AI generato
 */
public function exportCsv(int $projectId, int $evalId): void
{
    $user = Auth::user();
    $project = Project::findByUserAndId($user['id'], $projectId);

    if (!$project || ($project['type'] ?? 'negative-kw') !== 'campaign') {
        http_response_code(400);
        echo json_encode(['error' => 'Progetto non valido']);
        exit;
    }

    $type = $_POST['type'] ?? '';
    $data = json_decode($_POST['data'] ?? '{}', true) ?: [];
    $campaignName = $_POST['campaign_name'] ?? $project['name'] ?? 'Campagna';

    if (empty($type) || empty($data)) {
        http_response_code(400);
        echo json_encode(['error' => 'Dati mancanti']);
        exit;
    }

    require_once __DIR__ . '/../services/EvaluationCsvService.php';
    $service = new \Modules\AdsAnalyzer\Services\EvaluationCsvService();
    $csv = $service->generate($type, $data, $campaignName);

    $typeLabel = match (true) {
        str_contains($type, 'copy') => 'copy',
        str_contains($type, 'ext') => 'extensions',
        str_contains($type, 'key') => 'keywords',
        default => 'export',
    };
    $filename = "ads-editor-{$typeLabel}-" . date('Y-m-d') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($csv));
    echo $csv;
    exit;
}
```

**Step 3: Verify syntax**

```bash
php -l modules/ads-analyzer/controllers/CampaignController.php
php -l modules/ads-analyzer/routes.php
```

**Step 4: Commit**

```bash
git add modules/ads-analyzer/routes.php modules/ads-analyzer/controllers/CampaignController.php
git commit -m "feat: add CSV export route and controller for AI fix results"
```

---

### Task 10: Test and deploy

**Step 1: Test PDF export locally**

Navigare a una valutazione completata nel browser e cliccare "Esporta PDF". Verificare:
- PDF si scarica
- Tutte le sezioni presenti
- Score colorati correttamente
- Header/footer con pagine

**Step 2: Test CSV export locally**

1. Cliccare "Genera con AI" su un issue/suggerimento
2. Verificare che l'anteprima mostri i dati formattati
3. Cliccare "Esporta CSV Ads Editor"
4. Aprire il CSV e verificare colonne Google Ads Editor

**Step 3: Deploy**

```bash
git push origin main
ssh -i ~/.ssh/siteground_key -p 18765 u1608-ykgnd3z1twn4@ssh.ainstein.it "cd ~/www/ainstein.it/public_html && git pull origin main && composer install --no-dev"
```

Nota: `composer install` necessario per mpdf su produzione.

**Step 4: Test in produzione**

Navigare a Anbiformazione eval #15 e testare:
1. PDF export
2. Genera un fix + CSV export

**Step 5: Commit finale (se fix necessari)**

```bash
git add -A && git commit -m "fix: adjustments after testing PDF/CSV export"
```
