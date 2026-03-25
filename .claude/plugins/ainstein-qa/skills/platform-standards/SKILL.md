---
name: platform-standards
description: "Pattern UI/UX e coding standard verificati della piattaforma Ainstein. Usata dagli agenti QA per verificare conformita. Include credenziali test e rubrica scoring."
---

## Credenziali Test

- **URL**: https://ainstein.it
- **Email**: admin@seo-toolkit.local
- **Password**: admin123

## Standard CSS Tabelle

### Classi obbligatorie

| Elemento | Classe corretta | Errori comuni | Note |
|----------|----------------|---------------|------|
| Container tabella/card | `rounded-xl` | rounded-2xl | SOLO per wrapper principali (vedi regola sotto) |
| Table tag | `w-full` | min-w-full | Violazioni in seo-onpage, ai-optimizer (vedi Drift Report) |
| Celle th/td | `px-4 py-3` | px-6 py-3, px-6 py-4 | Violazioni in 4 file seo-tracking (vedi Drift Report) |
| Header th | `text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider` | font-semibold, font-bold | Obbligatorio per consistency |
| Thead dark | `dark:bg-slate-700/50` | dark:bg-slate-800, dark:bg-slate-900/50, dark:bg-slate-700/80, dark:bg-slate-700/30 | Violazioni in 15 file (vedi Drift Report) |
| Hover riga | `hover:bg-slate-50 dark:hover:bg-slate-700/50` | dark:hover:bg-slate-700/30, dark:hover:bg-slate-600 | Per interactive rows |
| Badge status | `inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium` | px-3 py-1, rounded-lg | Sempre usare da `table-helpers.php` |

### Regola rounded-lg vs rounded-xl (IMPORTANTE per QA)

**`rounded-xl`** (12px) — SOLO per container wrapper principali:
- Card principali con `bg-white dark:bg-slate-800 border shadow-sm`
- Wrapper attorno a `<table>` con `overflow-hidden`
- Sezioni top-level della pagina

**`rounded-lg`** (8px) — CORRETTO per tutti gli altri elementi:
- Bottoni (`<button>`, `<a>` con stile bottone)
- Input, select, textarea
- Alert/banner (bg-red-50, bg-amber-50, etc.)
- Badge e tag (non full-round)
- Modal dialog interni
- Card secondarie/nested (stat card, info box dentro una card principale)
- Toggle/switch group

**NON contare `rounded-lg` come violazione** su bottoni, input, alert, modal e card nested. Contare SOLO su container wrapper principali che dovrebbero essere `rounded-xl`.

### Componenti obbligatori per tabelle

```php
// Sempre usare i componenti shared:
View::partial('components/table-pagination', [
    'pagination' => Pagination::make($total, $page, $perPage),
    'baseUrl' => '/modulo/projects/' . $project['id'] . '/section',
    'filters' => ['status' => $status, 'sort' => $sort]
]);

View::partial('components/table-empty-state', [
    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M..."/>',
    'heading' => 'Nessun risultato',
    'message' => 'Non ci sono item da mostrare',
    'ctaText' => 'Crea nuovo',
    'ctaUrl' => '/url/per/azione'
]);

View::partial('components/table-bulk-bar', [
    'actions' => [
        ['label' => 'Approva', 'action' => 'bulkApprove()', 'color' => 'emerald'],
        ['label' => 'Elimina', 'action' => 'bulkDelete()', 'color' => 'red']
    ]
]);
```

### Helper functions per intestazioni

```php
// Nel template:
<?php include __DIR__ . '/../../../shared/views/components/table-helpers.php'; ?>

<thead class="bg-slate-50 dark:bg-slate-700/50">
    <tr>
        <?= table_sort_header('Keyword', 'keyword', $sort, $dir, $baseUrl, $filters) ?>
        <?= table_header('Azioni', 'right') ?>
    </tr>
</thead>
```

---

## Componenti Shared

### table-pagination.php
**Scopo**: Paginazione standard riutilizzabile con preservazione filtri, versione mobile/desktop responsive
**Uso**: `View::partial('components/table-pagination', [...])`
**Parametri**:
- `$pagination` (array) — Da `Pagination::make($total, $page, $perPage)` con keys: current_page, last_page, total, per_page, from, to
- `$baseUrl` (string) — URL base della pagina senza query string paginazione (es. `/modulo/projects/1/sezione`)
- `$filters` (array) — Filtri attivi da preservare (sort, dir, status, q, etc.)

### table-empty-state.php
**Scopo**: Empty state per tabelle con icona, heading, messaggio e CTA opzionale
**Uso**: `View::partial('components/table-empty-state', [...])`
**Parametri**:
- `$icon` (string) — SVG path(s) da infilare in `<path stroke-linecap="..."/>`
- `$heading` (string) — Titolo tipo "Nessun risultato", "Nessuna pagina trovata"
- `$message` (string) — Descrizione breve
- `$ctaText` (string, opzionale) — Testo bottone CTA (es. "Aggiungi nuovo")
- `$ctaUrl` (string, opzionale) — URL bottone CTA

### table-helpers.php
**Scopo**: Helper functions per componenti tabella standard
**Funzioni definite**:
- `table_sort_header($label, $field, $currentSort, $currentDir, $baseUrl, $filters)` — Genera `<th>` sortabile con freccia direzionale SVG
- `table_header($label, $align='left')` — Genera `<th>` statico (non sortabile)
- `table_status_badge($status, $colorMap, $label=null)` — Badge colorato per stato (vedi credit_badge)
- `table_bulk_init($allIds)` — JSON per x-data Alpine con selectedIds, allIds, toggleAll()
- `table_checkbox_header()` — Genera `<th>` con checkbox "seleziona tutti"
- `table_checkbox_cell($id)` — Genera `<td>` con checkbox singolo per riga

### table-bulk-bar.php
**Scopo**: Barra azioni bulk che appare quando selezionati > 0 item (Alpine.js x-show)
**Uso**: `View::partial('components/table-bulk-bar', [...])`
**Parametri**:
- `$actions` (array) — Array di azioni con keys: label, action, color (emerald|red|blue|amber), confirm (bool)
  - `label` — Testo bottone (es. "Approva", "Elimina")
  - `action` — Metodo Alpine da invocare (es. "bulkApprove()")
  - `color` — Classe colore Tailwind bottone
  - `confirm` — Se true, metodo gestisce la conferma
- `$countVar` (string, opzionale) — Variabile Alpine per conteggio (default: "selectedIds.length")

### dashboard-hero-banner.php
**Scopo**: Hero banner dark gradient con titolo, step visivi e CTA per sezioni educative moduli
**Uso**: `View::partial('components/dashboard-hero-banner', [...])`
**Parametri**:
- `$title` (string) — Titolo banner (es. "Come funziona il Tool")
- `$description` (string) — Sottotitolo descrittivo
- `$color` (string) — Colore modulo: amber|emerald|blue|purple|rose|cyan|orange
- `$steps` (array) — Array di `['icon' => '...', 'title' => '...', 'subtitle' => '...']` (3-4 step)
- `$ctaText` (string, opzionale) — Testo bottone CTA
- `$ctaUrl` (string, opzionale) — URL bottone CTA (default: `url('/projects/create')`)
- `$storageKey` (string, opzionale) — Se impostato, banner dismissibile via localStorage
- `$badge` (string, opzionale) — Badge sopra titolo (es. "Novita")

### dashboard-kpi-card.php
**Scopo**: Card singola per metrica KPI (numero grande, icona, delta %, subtitle, link)
**Uso**: Di norma usato dentro `dashboard-stats-row.php`, ma usabile standalone
**Parametri**:
- `$label` (string) — Etichetta metrica (es. "Keyword monitorate")
- `$value` (numeric|string) — Valore principale (auto-formattato con number_format)
- `$icon` (string) — SVG path(s) Heroicons
- `$color` (string) — blue|emerald|amber|purple|rose|cyan|orange
- `$url` (string, opzionale) — Se presente, card diventa link
- `$suffix` (string, opzionale) — Suffisso dopo valore (es. "%", "/10")
- `$subtitle` (string, opzionale) — Testo piccolo sotto valore
- `$delta` (float, opzionale) — Delta percentuale (es. 12.5, -3.2) con freccia colorata
- `$invertDelta` (bool, opzionale) — Se true, colori delta invertiti (rosso=bene, verde=male, per costi)
- `$periodLabel` (string, opzionale) — Contesto periodo (es. "vs 7gg fa")

### dashboard-stats-row.php
**Scopo**: Grid responsive di KPI card (auto-adatta colonne 1-4)
**Uso**: `View::partial('components/dashboard-stats-row', ['cards' => [...], 'dataTour' => '...'])`
**Parametri**:
- `$cards` (array) — Array di param per dashboard-kpi-card (ogni elemento è un card)
- `$dataTour` (string, opzionale) — Attributo data-tour per onboarding spotlight

### dashboard-how-it-works.php
**Scopo**: Card "Come funziona" con step numerati 1-5 in grid
**Uso**: `View::partial('components/dashboard-how-it-works', [...])`
**Parametri**:
- `$steps` (array) — Array di `['title' => '...', 'description' => '...']`
- `$color` (string) — Colore numeri: blue|emerald|amber|purple|rose|cyan|orange

### dashboard-mode-card.php
**Scopo**: Card selezionabile per modalità/tipo modulo (es. keyword-research: Research Guidata, Architettura, etc.)
**Uso**: `View::partial('components/dashboard-mode-card', [...])`
**Parametri**:
- `$title` (string) — Nome modalità
- `$description` (string) — Descrizione modalità
- `$icon` (string) — SVG path Heroicons
- `$gradient` (string) — Classe gradient (es. "from-emerald-500 to-teal-600")
- `$url` (string) — Link URL (default: CTA va a progetti)
- `$ctaText` (string, opzionale) — Testo CTA (default: "Vai ai progetti")
- `$cost` (string, opzionale) — Badge costo (es. "10 cr")
- `$costColor` (string, opzionale) — Colore badge: amber|purple|emerald
- `$badge` (string, opzionale) — Badge extra (es. "3 progetti")
- `$dataTour` (string, opzionale) — Attributo data-tour

### dashboard-module-block.php
**Scopo**: Blocco "Cosa puoi fare" per un modulo con lista capabilities e costi. Usato nella dashboard globale /projects.
**Uso**: `View::partial('components/dashboard-module-block', ['block' => $block])`
**Parametri**:
- `$block` (array) — Array con keys:
  - `slug` (string) — Slug modulo (es. 'seo-audit')
  - `name` (string) — Nome modulo visualizzato
  - `tagline` (string) — Sottotitolo breve
  - `capabilities` (array) — Array di `['text' => '...', 'cost' => '...']`
  - `color` (string) — amber|emerald|blue|purple|rose|cyan|orange
  - `iconPath` (string) — SVG path Heroicons

### dashboard-project-card.php
**Scopo**: Card progetto globale con health indicator, KPI moduli attivi, azione primaria suggerita
**Uso**: `View::partial('components/dashboard-project-card', ['project' => $project])`
**Parametri**:
- `$project` (array) — Da `GlobalProject::allWithDashboardData()` con keys:
  - `id`, `name`, `website_url`, `color`, `health_status` (red|amber|green|gray)
  - `active_modules` (array di slug), `active_modules_count` (int)
  - `modules_data` (assoc array slug → dati KPI per modulo)
  - `primary_action` (array con keys: text, url, cta, severity) — nullable

### skeleton-loader.php
**Scopo**: Placeholder animato per stati di caricamento (animate-pulse)
**Uso**: `View::partial('components/skeleton-loader', ['variant' => 'kpi-card', 'count' => 4])`
**Parametri**:
- `$variant` (string) — 'card' | 'table-row' | 'kpi-card' | 'text' (default: 'card')
- `$count` (int) — Numero di elementi da renderizzare (default: 3)
- `$cols` (int) — Numero colonne per 'table-row' (default: 5)

### credit-badge.php
**Scopo**: Helper per badge crediti colorati a 4 tier (Gratis, Base, Standard, Premium)
**Funzioni**:
- `credit_badge($cost, $large=false, $extraClass='')` — Genera badge HTML (0=verde, 1=blu, 3=ambra, 10+=viola)
- `credit_tier($cost)` — Ritorna 'free', 'base', 'standard', 'premium'
- `credit_format($cost)` — Formatta numero (10.0 → "10", 1.5 → "1.5")
- `credit_tier_label($cost)` — Ritorna nome tier in italiano
- `credit_cost_row($operation, $cost, $note='')` — Genera riga `<tr>` per tabella costi

### orphaned-project-notice.php
**Scopo**: Notice ambra per progetti modulo non collegati a progetto globale
**Uso**: `View::partial('components/orphaned-project-notice', ['project' => $project])`
**Parametri**:
- `$project` (array) — Deve avere key 'global_project_id' (se null/empty, mostra notice)

### status-badge.php
**Scopo**: Badge stato con icone opzionali (success, error, warning, info, pending, paused)
**Uso**: `View::partial('components/status-badge', ['type' => 'success', 'label' => 'Completato'])`
**Parametri**:
- `$type` (string) — success|error|warning|info|pending|paused
- `$label` (string) — Testo badge
- `$icon` (bool, opzionale) — Mostrare SVG icon accanto (default: false)
- `$size` (string, opzionale) — 'sm' o 'md' (default: 'sm')
- `$extra` (string, opzionale) — Classi CSS extra

### period-selector.php
**Scopo**: Bottoni periodo (7gg, 30gg, 90gg, custom) riutilizzabili con supporto link e Alpine.js
**Uso**: `View::partial('components/period-selector', [...])`
**Parametri**:
- `$periods` (array) — Assoc array [days => label] (es. [7 => '7gg', 30 => '30gg'])
- `$selected` (int) — Periodo attuale (es. 30)
- `$baseUrl` (string, opzionale) — Genera link mode se impostato
- `$alpineVar` (string, opzionale) — Nome var Alpine per bind
- `$alpineClick` (string, opzionale) — Nome funzione Alpine onclick
- `$showCustom` (bool, opzionale) — Mostra bottone "Personalizzato" (default: false)
- `$color` (string, opzionale) — Colore bottone attivo: blue|rose|emerald|purple|amber

---

## Pattern Landing Page

Ogni modulo ha sezione educativa in fondo alla pagina landing con 7 sezioni in ordine fisso.

**Riferimento**: `modules/keyword-research/views/dashboard.php` (da linea ~150)

### 7 Sezioni Ordinate

1. **Separator divider** — `<hr class="my-12">` semplice
2. **Hero educativo** (grid 2 colonne) — Titolo + descrizione + mockup immagine/HTML
3. **Come funziona** (3 step card) — Usa `dashboard-how-it-works.php` con 3 step
4. **Feature blocks** (3 alternanze) — Sezioni bianche/slate con icon, titolo, lista punti
5. **Cosa puoi fare** (grid 6 card) — Usa `dashboard-mode-card.php` x 6 modalità/operazioni
6. **FAQ accordion** (Alpine.js) — x-data accordion con 5-8 domande, dettagli su demand
7. **CTA finale** (gradient card) — Hero finale con bottone grande verso projects/create

### Colori Modulo

| Modulo | Colore | Classe | RGB |
|--------|--------|--------|-----|
| ai-content | Amber | from-amber-950 | #78350f |
| seo-audit | Emerald | from-emerald-950 | #064e3b |
| seo-tracking | Blue | from-blue-950 | #0c2340 |
| keyword-research | Purple | from-purple-950 | #3f0f5c |
| ads-analyzer | Rose | from-rose-950 | #500724 |
| internal-links | Cyan | from-cyan-950 | #083344 |

### Regole CSS Pattern

- Icone: SOLO Heroicons SVG, classe `w-6 h-6` o `w-8 h-8`
- Visual: HTML/CSS mockup (NON screenshot PNG)
- Dark mode: su TUTTO (`:dark` tailwind classes)
- CTA finale: link a `url('/projects/create')`
- Posizionamento: SEMPRE dopo sezione operativa modulo (non all'inizio)

---

## Pattern Controller

### Signature obbligatoria (Golden Rule #21, #22)

```php
<?php

namespace Modules\NomeModulo\Controllers;

use Core\Auth;
use Core\View;
use Core\Middleware;

class DashboardController
{
    /**
     * Controller DEVE ritornare string.
     * SEMPRE passare $user a View::render() per sidebar/header.
     */
    public function index(): string
    {
        Middleware::auth();  // Verifica login

        $user = Auth::user();  // Recupera utente corrente

        // ... logica controller ...

        return View::render('nome-modulo::dashboard', [
            'title' => 'Titolo Pagina',
            'user' => $user,                              // GR #22: OBBLIGATORIO
            'data' => $data,
            'modules' => \Core\ModuleLoader::getActiveModules()
        ]);
    }
}

// In routes/routes.php:
Router::get('/nome-modulo', fn() => (new DashboardController())->index());  // return implicito
```

### View::render() — Non usare View::make()

```php
// CORRETTO:
return View::render('modulo::vista', ['user' => $user, ...]);

// SBAGLIATO:
echo View::make('modulo::vista', [...]);  // Non esiste, vista non è visible
return null;                                // Pagina bianca!
```

### CSRF in Form HTML

```html
<!-- Sempre incluso in <form> POST/PUT/DELETE/PATCH: -->
<?= csrf_field() ?>
<!-- Genera: <input name="_csrf_token" value="..." type="hidden"> -->

<!-- In header X-CSRF-TOKEN (se middleware check manuale): -->
<script>
const csrfToken = document.querySelector('input[name="_csrf_token"]').value;
fetch('/api/endpoint', {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': csrfToken },
    body: JSON.stringify({...})
});
</script>
```

### AJAX Lungo (30-300s, senza SSE)

Pattern obbligatorio da `ai-content/WizardController::generateBrief()`:

```php
// Controller method
public function generateBrief(): string
{
    Middleware::auth();
    $userId = Auth::user()['id'];

    // GR #15 + #17: output buffering OBBLIGATORIO
    ignore_user_abort(true);
    set_time_limit(300);
    ob_start();
    header('Content-Type: application/json');
    session_write_close();  // Libera sessione per altre request

    try {
        // ... operazione lunga ...
        $ai = new AiService('modulo');
        $result = $ai->analyze($userId, $prompt, $content, 'modulo');
        Database::reconnect();  // GR #10: dopo ogni AI call

        // GR #23: ob_end_clean() PRIMA di echo json_encode
        ob_end_clean();
        echo json_encode(['success' => true, 'data' => $result]);
        exit;
    } catch (Exception $e) {
        // GR #23: ob_end_clean() ANCHE su error path
        ob_end_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}
```

### Frontend AJAX — GR #24: response.ok check

```javascript
// CORRETTO:
fetch('/api/endpoint', { method: 'POST', body: ... })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        return response.json();  // Safe: body esiste, non corrupto
    })
    .then(data => { /* usa data */ })
    .catch(err => { /* log errore */ });

// SBAGLIATO:
fetch('/api/endpoint')
    .then(res => res.json())  // Se res.status=500, body=error, json() fallisce silente
    .then(data => { /* non eseguito, non error logged */ });
```

### Database::reconnect() — GR #10

```php
// Sempre dopo operazioni lunghe/AI calls:
$ai = new AiService('modulo');
$result = $ai->analyze($userId, $prompt, $content);
Database::reconnect();  // Riconnette al DB

// In loop:
foreach ($items as $item) {
    $result = $ai->analyze($item);
    Database::reconnect();  // Non una sola volta alla fine!
    save($result);
}
```

### Crediti — Sempre passati dal controller

```php
// NON hardcodare in view:
$cost = Credits::getCost('operazione', 'nome-modulo');  // Nel controller
if (!Credits::hasEnough($userId, $cost)) {
    return View::render('error', ['message' => 'Crediti insufficienti']);
}

// Poi passa al view:
return View::render('modulo::form', [
    'cost' => $cost,        // Dalla query, non hardcoded
    'userCredits' => Credits::getBalance($userId),
    'user' => $user
]);
```

### Scraping — GR #12: Sempre ScraperService

```php
// CORRETTO:
$service = new ScraperService();
$result = $service->scrape($url);  // Ritorna: success, title, content, headings, word_count, internal_links

// SBAGLIATO:
$html = file_get_contents($url);
$dom = new DOMDocument();
libxml_use_internal_errors(true);
$dom->loadHTML($html);  // Scraping manuale vietato!
```

---

## Icone

### Regola d'oro: SOLO Heroicons SVG inline

**Consentito**:
```html
<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M..."/>
</svg>
```

**Vietato assoluto**:
```html
<!-- NO Lucide: -->
<svg class="lucide lucide-search">...</svg>

<!-- NO FontAwesome: -->
<i class="fa fa-search"></i>

<!-- NO icon font: -->
<span class="icon-search"></span>

<!-- NO icomoon/customfont: -->
<span class="icon icon-123"></span>
```

### Dimensioni standard

- Intestazione, sidebar: `w-5 h-5`
- Hero, banner: `w-6 h-6` o `w-8 h-8`
- KPI card: `w-6 h-6`
- Bottone inline: `w-4 h-4` (dopo testo)
- Modal close: `w-5 h-5`

### Come trovarli

Visita [https://heroicons.com](https://heroicons.com), cerca l'icona, copia il `<path d="M...">` completo.

**Esempio:**
```html
<!-- Cerchi: -->
<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
</svg>
```

---

## Lingua UI

### Regola assoluta: ITALIANO SEMPRE

**Visibile all'utente** (must be Italian):
- Titoli pagina, heading
- Label form, placeholder
- Testo bottone (Salva, Cancella, Invia, Analizza, etc.)
- Messaggi errore, success, warning
- Empty state heading/message
- Tooltip, popover
- Breadcrumb

**Esempi corretti**:
```html
<input placeholder="Inserisci URL sito..." />
<button>Salva Impostazioni</button>
<div>Nessun risultato trovato</div>
<span class="error">Errore: crediti insufficienti</span>
```

**Esempi errati**:
```html
<input placeholder="Enter your URL" />           <!-- NO -->
<button>Save</button>                            <!-- NO -->
<div>No results found</div>                      <!-- NO -->
```

### Eccezioni (testo non visibile):
- Classi CSS (Tailwind): `rounded-xl`, `text-slate-600` — rimangono inglesi
- Nomi variabili PHP/JS: `$userId`, `selectedIds` — rimangono inglesi
- Attributi data: `data-tour`, `data-period` — rimangono inglesi
- JSON keys nelle API: `"user_id"`, `"keyword"` — rimangono inglesi
- Commenti codice: possono rimanere inglesi (preferibilmente italiano per coerenza)

### Helper per UI tradotta

Se una label è usata più volte, metterla in un config:

```php
// config/ui-strings.php
return [
    'ai-content' => [
        'btn_generate' => 'Genera Contenuto',
        'btn_publish' => 'Pubblica',
        'error_credits' => 'Crediti insufficienti'
    ]
];

// Uso in view:
<?php $strings = include BASE_PATH . '/config/ui-strings.php'; ?>
<button><?= $strings['ai-content']['btn_generate'] ?></button>
```

---

## Drift Report

*Aggiornato: 2026-03-25*

### Violazioni trovate

#### px-6 nelle celle tabella (dovrebbe essere px-4)

- `modules/seo-tracking/views/groups/create.php` — Multiple `<th class="px-6">` e `<td class="px-6">`
- `modules/seo-tracking/views/groups/edit.php` — Multiple `<th class="px-6">` e `<td class="px-6">`
- `modules/seo-tracking/views/groups/index.php` — 6 match (linee 156-182)
- `modules/seo-tracking/views/groups/show.php` — 10 match (linee 272-326)

**Impatto**: Spacing celle non uniforme, violazione Golden Rule #20

#### min-w-full nelle tabelle (dovrebbe essere w-full)

- `modules/seo-onpage/views/pages/index.php:46` — `<table class="min-w-full divide-y...">`
- `modules/ai-optimizer/views/projects/dashboard.php:103` — `<table class="min-w-full divide-y...">`

**Impatto**: Tabelle non responsive, overflow su mobile

#### dark:bg-slate-900/50 (dovrebbe essere dark:bg-slate-700/50)

- `modules/ads-analyzer/views/campaigns/dashboard.php` — `<thead class="bg-slate-50 dark:bg-slate-900/50">`
- `modules/ads-analyzer/views/campaigns/evaluation-v2.php` — Multiple `dark:bg-slate-9`
- `modules/ads-analyzer/views/campaigns/index.php` — `dark:bg-slate-9`
- `modules/ads-analyzer/views/campaigns/partials/report-pmax-section.php` — `dark:bg-slate-9`
- `modules/ads-analyzer/views/campaigns/partials/report-search-section.php` — `dark:bg-slate-9`
- `modules/ads-analyzer/views/campaigns/partials/report-extensions-section.php` — `dark:bg-slate-9`
- `modules/ads-analyzer/views/campaigns/partials/report-shopping-section.php` — `dark:bg-slate-9`
- `modules/ads-analyzer/views/campaigns/partials/report-campaign-table.php` — `dark:bg-slate-9`
- `modules/ads-analyzer/views/campaigns/partials/report-optimizations.php` — `dark:bg-slate-9`
- `modules/ads-analyzer/views/campaigns/partials/report-campaign-filter.php` — `dark:bg-slate-9`
- `modules/ads-analyzer/views/campaigns/partials/report-product-analysis.php` — `dark:bg-slate-9`
- `modules/ads-analyzer/views/campaigns/evaluation.php` — Multiple
- `modules/content-creator/views/images/import.php` — `dark:bg-slate-9`
- `modules/content-creator/views/images/preview.php` — `dark:bg-slate-9`
- `modules/seo-onpage/views/ai/suggestions.php` — `dark:bg-slate-900/50`

**Impatto**: Thead troppo scuro in dark mode, violazione Golden Rule #20. Questi sono casi dove la regola è stata ignorata sistematicamente nel refactor ads-analyzer (v2.x).

#### rounded-lg container (dovrebbe essere rounded-xl)

**NOTA**: Contare SOLO container wrapper principali (`bg-white dark:bg-slate-800 rounded-lg border shadow-sm` o wrapper `<table>` con `overflow-hidden`). NON contare rounded-lg su bottoni, input, alert, modal, card nested — quelli sono corretti.

File con container principali che usano rounded-lg invece di rounded-xl:
- `modules/seo-onpage/views/pages/index.php:44` — Table container
- `modules/ai-optimizer/views/projects/dashboard.php` — Table container
- `admin/views/users/index.php` — Table container
- `admin/views/users/show.php` — Card container

**Impatto**: Basso — micro differenza visiva (8px vs 12px corner). Fix progressivo, non critico.

#### Librerie icone non autorizzate (Lucide/FontAwesome)

**Risultato**: Nessuna violazione trovata

#### Pagination inline (dovrebbe usare View::partial)

**Risultato**: Nessuna violazione trovata — tutti i moduli usano correttamente `table-pagination.php`

#### CSRF field name errato (senza underscore)

**Risultato**: Nessuna violazione trovata — tutti usano `_csrf_token` correttamente

#### UI non in italiano

**Risultato**: Nessuna violazione trovata di testo visibile

#### getModuleSetting() non esiste

**Risultato**: Falso positivo — `getModuleSetting()` è un helper definito in `core/View.php` e `modules/ai-content/cron/dispatcher.php`. È un alias intenzionale per `ModuleLoader::getSetting()`. NON è un'errata dichiarazione.

### Sommario Drift

| Tipo violazione | N. file | N. occorrenze | Severity |
|----------------|---------|---------------|----------|
| px-6 celle | 4 | ~20 | ALTA |
| min-w-full | 2 | 2 | MEDIA |
| dark:bg-slate-900/50 | 15 | ~30 | ALTA |
| rounded-lg container | 17+ | ~50 | MEDIA |
| Icone non autorizzate | 0 | 0 | — |
| Pagination inline | 0 | 0 | — |
| CSRF errato | 0 | 0 | — |
| getModuleSetting | 0 | 0 | — |
| UI non italiano | 0 | 0 | — |

**TOTALE**: 38 file con 102 occorrenze di drift CSS

### Priorita Fix

1. **[CRITICO]** dark:bg-slate-900/50 → dark:bg-slate-700/50 (ads-analyzer + content-creator + seo-onpage) — 15 file, ~30 match
2. **[CRITICO]** px-6 → px-4 in seo-tracking (4 file, ~20 match)
3. **[ALTO]** rounded-lg → rounded-xl per table container (17+ file)
4. **[MEDIO]** min-w-full → w-full (2 file, 2 match)

### Pattern Recorrente

Gli errori sono concentrati in 3 moduli specifici:
- **ads-analyzer**: Refactor v2 usò dark:bg-slate-900/50 invece di 700/50 (copia-incolla da template errato)
- **seo-tracking**: Tabelle in groups/ usano px-6 py-4 (legacy pattern pre-standardizzazione)
- **keyword-research + content-creator + seo-onpage**: Misto rounded-lg e rounded-xl (progressivo consolidamento)

---

## Rubrica Scoring

Ogni agente valuta l'area con 5 criteri (scala 1-10):

| Criterio | Peso | Descrizione |
|----------|------|-------------|
| UX Flow | 25% | Flussi intuitivi, l'utente sa cosa fare |
| UI Polish | 20% | Estetica, coerenza, responsive |
| Pattern Compliance | 20% | Conformita standard piattaforma |
| Funzionalita | 20% | I workflow completano senza errori |
| Valore Aggiunto | 15% | Il tool da davvero valore vs alternative |

Score finale = media pesata. Scala interpretativa:
- 9-10: Eccellente, pronto per il mercato
- 7-8: Buono, issue minori
- 5-6: Sufficiente, serve lavoro
- 3-4: Insufficiente, problemi gravi
- 1-2: Inutilizzabile

## Output Format

Ogni agente QA deve produrre un file con questa struttura esatta:

# QA Review — {Area}
Persona: {Nome}, {Ruolo} | Data: {YYYY-MM-DD}
Ambiente: https://ainstein.it

## Scoring

| Criterio | Voto | Note |
|----------|------|------|
| UX Flow | X/10 | ... |
| UI Polish | X/10 | ... |
| Pattern Compliance | X/10 | ... |
| Funzionalita | X/10 | ... |
| Valore Aggiunto | X/10 | ... |
| **Score Finale** | **X.X/10** | |

## Sommario
Critici: N | Alti: N | Medi: N | Bassi: N

## Giudizio Professionale
{2-3 paragrafi dal punto di vista della persona}

## Issues

### [CRITICO] #1 — {Titolo}
- **Tipo**: UX | UI | Funzionale | Pattern | Performance
- **Pagina**: {URL relativo}
- **Screenshot**: screenshots/{area}-{pagina}.png
- **Problema**: {descrizione dal punto di vista dell'utente}
- **File coinvolti**: `{path}:{righe}`
- **Fix proposto**: {cosa fare concretamente}
- **Pattern violato**: {Golden Rule / standard, oppure "Nessuno"}
- [ ] Da eseguire

### [ALTO] #2 — ...

## Sezioni Non Testate
{Lista di pagine/workflow non testati con motivo}

## Nota per l'Esecuzione
{Dipendenze tra fix, ordine suggerito, rischi}

## Politica Dati Produzione

| Azione | Permessa |
|--------|----------|
| Navigare pagine esistenti | Si |
| Aprire progetti esistenti | Si |
| Cliccare bottoni di navigazione | Si |
| Compilare form senza submit | Si |
| Testare export (CSV/PDF) | Si |
| Creare nuovi progetti | NO |
| Lanciare crawl/sync/analisi | NO |
| Modificare dati esistenti | NO |
| Cancellare dati | NO |

## Error Handling

Se un agente incontra un errore:
1. Logga l'errore con contesto (step, pagina, azione tentata)
2. Continua con lo step/pagina/workflow successivo
3. Marca le sezioni non testate come [NON TESTATO] — {motivo}
4. Scrivi il report comunque (anche se parziale)

Se non ci sono progetti con dati per un modulo:
1. Segnala "Nessun progetto con dati trovato"
2. Valuta solo pagine raggiungibili (landing, empty states, form UX)
3. Scoring parziale con nota "Score basato su analisi limitata"
