# Piano di Sviluppo — UX/UI Audit Fix

> Data: 2026-03-11
> Riferimento: `docs/plans/2026-03-11-ux-audit-design.md`
> Approccio: Sprint incrementali, dal più impattante al meno urgente

---

## Sprint 1 — Quick Wins (effort basso, impatto alto)

Obiettivo: correzioni che migliorano immediatamente la percezione di qualità senza toccare logica backend.

### Task 1.1: Rimuovere Meta Tags da AI Content [A1]

**Cosa fare**: Nascondere la sezione Meta Tags dalla sidebar e dalle route di ai-content. NON cancellare il codice — disabilitare la navigazione in attesa di spostamento in seo-onpage.

**File da modificare**:
- `shared/views/components/nav-items.php` — rimuovere link "Meta Tags" dall'accordion ai-content
- `modules/ai-content/views/entry-dashboard.php` — rimuovere card/CTA "Meta Tags" dalla dashboard entry

**File da NON toccare** (preservare per futuro seo-onpage):
- `modules/ai-content/controllers/MetaTagController.php`
- `modules/ai-content/views/meta-tags/*.php`
- `modules/ai-content/models/MetaTag.php`
- Route in `modules/ai-content/routes.php` (linee 702-928) — commentare con `// TODO: migrazione a seo-onpage`

**Verifica**: navigare in ai-content → la voce Meta Tags non appare nella sidebar né nella dashboard.

---

### Task 1.2: Nascondere Nomi Provider nel Rank Check [B5]

**Cosa fare**: Sostituire i badge "Serper.dev" e "SERP API (fallback)" con un unico indicatore "Verifica posizioni — Attivo".

**File da modificare**:
- `modules/seo-tracking/views/rank-check/index.php` (linee 43-49) — sostituire i badge provider con:
  ```html
  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">
      <!-- Heroicon check-circle -->
      <svg class="w-3.5 h-3.5 mr-1" ...></svg>
      Provider configurato
  </span>
  ```
- Se nessun provider configurato: badge rosso "Provider non configurato — Configura nelle impostazioni"

**Verifica**: pagina rank check mostra solo stato attivo/non attivo, nessun nome tecnico.

---

### Task 1.3: Rimuovere Search "Coming Soon" nelle Docs [C13]

**File da modificare**:
- `shared/views/docs/index.php` — rimuovere il campo input search disabilitato

**Verifica**: pagina /docs non mostra più un campo inutilizzabile.

---

### Task 1.4: Credit Cost Visibile Prima dell'Azione [C12]

**Cosa fare**: Aggiungere badge costo crediti accanto ai bottoni azione dove manca.

**File da modificare**:
- `modules/keyword-research/views/keywords/wizard.php` — aggiungere badge costo nello step di conferma generazione
- `modules/keyword-research/views/editorial/wizard.php` — aggiungere badge costo prima del submit
- `modules/ai-content/views/keywords/wizard.php` — verificare che il costo sia visibile (potrebbe già esserci)
- `modules/content-creator/views/urls/dashboard.php` — badge costo accanto a "Genera" e "Scrape"

**Pattern badge** (componente `credit-badge.php` già esistente):
```php
<?= View::partial('components/credit-badge', ['cost' => Credits::getCost('operazione', 'modulo')]) ?>
```

**Verifica**: ogni bottone che consuma crediti ha il badge costo visibile.

---

### Task 1.5: Sort Indicator Standardizzato [C7]

**Cosa fare**: Verificare che tutte le tabelle usino `table_sort_header()` da `table-helpers.php` e mostrino freccia attiva.

**File da verificare/aggiornare**:
- `modules/ads-analyzer/views/campaigns/index.php` — campaign list headers
- `modules/ads-analyzer/views/campaigns/search-terms.php` — search terms headers
- `modules/content-creator/views/urls/dashboard.php` — results table headers
- `modules/seo-tracking/views/keywords/index.php` — keywords table headers (potrebbe già averlo)

**Pattern**: sostituire `<th>Nome</th>` con `<?= table_sort_header('Nome', 'name', $sort, $direction, $baseUrl) ?>`

**Verifica**: ogni tabella sortabile mostra ↑/↓ sulla colonna attiva.

---

### Task 1.6: Status Badge Palette Standard [D7]

**Cosa fare**: Creare componente shared e documentare la palette.

**Nuovo file**: `shared/views/components/status-badge.php`
```php
<?php
// Palette: success=emerald, error=red, warning=amber, info=blue, pending=slate, paused=gray
$palettes = [
    'success'  => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
    'error'    => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    'warning'  => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
    'info'     => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    'pending'  => 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300',
    'paused'   => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
];
$classes = $palettes[$type ?? 'info'] ?? $palettes['info'];
?>
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $classes ?>">
    <?= e($label) ?>
</span>
```

**Migrazione graduale**: NON sostituire tutto subito. Usare il nuovo componente nei nuovi sviluppi e nelle modifiche Sprint 2+.

---

## Sprint 2 — Leggibilità Dashboard (effort medio, impatto critico)

Obiettivo: ridurre information overload nei 2 dashboard più densi e chiarire la gerarchia informativa.

### Task 2.1: Redesign SEO Audit Dashboard [C1]

**Stato attuale**: 12+ card senza gerarchia. L'utente non sa dove guardare.

**Nuovo layout** (3 sezioni chiare):

**Sezione 1 — Above the fold** (hero):
- Score gauge (già presente) — GRANDE, centrato
- Delta vs precedente: "+5 punti rispetto all'ultima analisi"
- **"3 cose da fare subito"**: le 3 issue critical più impattanti con link diretto
- Bottone "Lancia nuova analisi" (se non in corso)

**Sezione 2 — Categorie** (sotto):
- Grid 3x2 di category card (meta, headings, images, links, content, technical)
- Ogni card: nome + icona + contatore issue per severità (rosso/amber/grigio)
- Click → pagina issues filtrata per categoria

**Sezione 3 — Dettagli** (tab opzionali):
- Tab "Tutte le issue" (top 20 con paginazione)
- Tab "Crawl Budget" (integrato, non sezione separata) [A4]
- Tab "Link interni" (overview + orphans)
- Tab "Storico" (trend score)

**File da modificare**:
- `modules/seo-audit/views/audit/dashboard.php` — redesign completo layout
- `shared/views/components/nav-items.php` — rimuovere "Crawl Budget" come sezione separata nella sidebar, integrare come tab
- `modules/seo-audit/controllers/AuditController.php` — passare dati per "3 azioni prioritarie" (top 3 critical issues)

**File reference per crawl budget merge**:
- `modules/seo-audit/views/budget/overview.php` — contenuto da integrare come tab
- `modules/seo-audit/views/budget/waste.php`
- `modules/seo-audit/views/budget/redirects.php`
- `modules/seo-audit/views/budget/indexability.php`

**Verifica**: dashboard mostra score + azioni + categorie above the fold. Crawl Budget accessibile come tab, non sezione sidebar.

---

### Task 2.2: Redesign SEO Tracking Dashboard [C2]

**Stato attuale**: 4 KPI + chart + AI summary + 3 mini-tabelle + gainers/losers = 50+ data point.

**Nuovo layout** (focus progressivo):

**Sezione 1 — Hero KPI** (above the fold):
- 3 KPI grandi: Posizione Media (+ delta), Keywords in Top 10 (+ delta), Visibilità (+ delta)
- Grafico sparkline piccolo sotto ogni KPI (trend 30gg inline)
- Se ci sono alert attive: banner amber "3 keyword sono scese — Vedi dettagli"

**Sezione 2 — Trend** (grafico principale):
- Grafico posizione media ultimi 30gg (Chart.js, già presente — spostare in primo piano)
- Toggle: "Posizione media" / "Visibilità" / "Click stimati"

**Sezione 3 — Movimenti** (tabella singola):
- Tab "Top Gainers" / "Top Losers" / "Nuove keyword"
- 10 righe per tab (non 3 mini-tabelle separate)
- Colonne: Keyword | Posizione | Delta | Volume | Trend

**Sezione 4 — AI Summary** (collapsibile):
- Spostare sotto, collapsato di default
- "Analisi AI" → click per espandere

**File da modificare**:
- `modules/seo-tracking/views/dashboard/index.php` — redesign completo
- `modules/seo-tracking/controllers/DashboardController.php` — consolidare dati gainers/losers in un unico array

**Verifica**: dashboard mostra 3 KPI + trend + movimenti. Non più di 15 data point above the fold.

---

### Task 2.3: Rinominare Module Dashboard vs Project Dashboard [D2]

**Cosa fare**: Eliminare la confusione "Dashboard" x2 nella sidebar.

**Regola**:
- Entry point modulo (senza progetto): "Home [NomeModulo]" oppure solo il nome del modulo come header accordion
- Project-level: "Dashboard" (invariato)
- Breadcrumb sempre: `NomeModulo > NomeProgetto > Sezione`

**File da modificare**:
- `shared/views/components/nav-items.php` — rinominare i link "Dashboard" module-level
- Per ogni modulo, verificare che il breadcrumb sia presente nelle view project-scoped

**Verifica**: sidebar non mostra mai due volte "Dashboard" per lo stesso modulo.

---

### Task 2.4: KPI Delta con Contesto Periodo [C3]

**Cosa fare**: Ogni delta (↑/↓ percentuale) deve mostrare il periodo di confronto.

**Pattern**:
```html
<span class="text-emerald-500">↑ 12%</span>
<span class="text-xs text-slate-400 ml-1">vs 7gg fa</span>
```

**File da modificare** (tutti i dashboard con KPI):
- `modules/ads-analyzer/views/campaigns/dashboard.php`
- `modules/seo-tracking/views/dashboard/index.php` (dopo redesign 2.2)
- `modules/seo-audit/views/audit/dashboard.php` (dopo redesign 2.1)
- `shared/views/components/dashboard-kpi-card.php` — aggiungere parametro `$periodLabel`

**Verifica**: ogni delta ha il contesto "vs X giorni fa" o "vs analisi precedente".

---

### Task 2.5: Componente Period Selector Shared [D6]

**Cosa fare**: Creare componente riutilizzabile per selezionare periodi.

**Nuovo file**: `shared/views/components/period-selector.php`

**Parametri**: `$periods` (array preset), `$selected`, `$baseUrl`, `$showCustom` (bool)

**Preset standard**: 7d, 14d, 30d, 90d + opzionale "Personalizzato" con date picker

**Stile**: pill buttons (come ads-analyzer attuale), posizione standard in alto a destra

**File da aggiornare per adozione**:
- `modules/ads-analyzer/views/campaigns/dashboard.php` — sostituire pill hardcoded con partial
- `modules/seo-tracking/views/trend/index.php` — sostituire date picker custom con partial

**Verifica**: il componente funziona in entrambi i moduli con lo stesso look.

---

## Sprint 3 — Integrazione Internal Links + Semplificazione Flussi

Obiettivo: rendere i link interni parte del flusso di generazione e semplificare i flussi più complessi.

### Task 3.1: Integrare Internal Links nel Wizard AI Content [A2]

**Stato attuale**: I link interni vengono caricati silenziosamente in `WizardController::generateBrief()` (linea 318-325) e iniettati nel prompt AI tramite `ArticleGeneratorService::buildInternalLinksSection()`. L'utente non vede nulla nel wizard.

**Cosa fare**:

**A) Nel wizard manuale** — aggiungere step visuale:
- Dopo la selezione keyword e prima della generazione, mostrare un pannello:
  ```
  Link Interni Disponibili: 12
  [x] Usa link interni nella generazione (raccomandato)
  [Gestisci link interni →]  (link alla pagina di import/gestione)
  ```
- Se 0 link disponibili: "Nessun link interno configurato — Importa URL per migliorare il linking"
- File: `modules/ai-content/views/keywords/wizard.php` — aggiungere pannello informativo nello step pre-generazione

**B) Nel flusso auto** — mostrare nelle settings:
- `modules/ai-content/views/auto/settings.php` — aggiungere sezione:
  ```
  Link Interni
  Pool attivo: 12 URL | Ultimo aggiornamento: 10/03/2026
  [✓] Inserisci link interni negli articoli automatici
  [Gestisci pool →]
  ```

**C) Nella pagina gestione link interni** — aggiungere feedback:
- `modules/ai-content/views/internal-links/index.php` — aggiungere colonna "Usato in" con contatore articoli che hanno usato quel link
- Query: contare occorrenze del link URL nel campo `content` degli articoli generati

**D) Sidebar** — spostare "Link Interni" sotto le impostazioni, non come sezione primaria:
- `shared/views/components/nav-items.php` — muovere il link sotto "Impostazioni" o renderlo sotto-voce di "Articoli"

**Verifica**: nel wizard l'utente vede quanti link interni verranno usati. Nel flusso auto, l'utente sa che i link vengono inseriti.

---

### Task 3.2: CSV Import con Preview [B2]

**Stato attuale**: `modules/content-creator/views/urls/import.php` chiede indici colonna numerici.

**Cosa fare**:
1. Upload CSV → PHP legge prime 5 righe → ritorna JSON
2. Frontend mostra tabella preview con header e dropdown per mapping
3. L'utente seleziona: "Colonna A = URL", "Colonna B = Keyword", etc.
4. Submit con mapping → import standard

**File da modificare**:
- `modules/content-creator/views/urls/import.php` — redesign tab CSV con preview table + dropdown mapping
- `modules/content-creator/controllers/UrlController.php` — nuovo endpoint `POST /preview-csv` che ritorna prime 5 righe come JSON
- `modules/content-creator/routes.php` — aggiungere route preview

**Verifica**: upload CSV mostra preview prima dell'import, mapping per dropdown (non per indice).

---

### Task 3.3: Piano Editoriale Form Semplificato [B3]

**Stato attuale**: `modules/keyword-research/views/editorial/wizard.php` mostra 5+ controlli nel primo step.

**Cosa fare**:
- Step 1 mostra solo: **Tema** (textarea) + **Keyword seed** (input)
- Toggle "Opzioni avanzate" (collapsed) per: periodo, articoli/mese, geografia, target
- Default smart: periodo=3 mesi, articoli=4/mese, target=generico, geografia=Italia

**File da modificare**:
- `modules/keyword-research/views/editorial/wizard.php` — riorganizzare step 0
- `modules/keyword-research/controllers/EditorialController.php` — default values se non specificati

**Verifica**: step 1 mostra 2 campi. Le opzioni avanzate sono nascoste ma accessibili.

---

### ~~Task 3.4: Publish WordPress Semplificato~~ — RIMOSSO

**Motivo**: Verifica codice mostra che il modal è già single-step (sito + categoria + stato in un unico form). Non necessita modifiche.

---

### Task 3.5: SSE Progress Unificata Content Creator [B7]

**Stato attuale**: 2 barre separate (scraping + generation).

**Cosa fare**: Unificare in una pipeline visuale:
```
[Scraping 3/10] ——→ [Generazione 0/10] ——→ [Completato]
```
Step attivo: colorato. Step futuro: grigio. Step completato: emerald con check.

**File da modificare**:
- `modules/content-creator/views/urls/dashboard.php` — sostituire 2 barre con pipeline unificata

**Verifica**: una sola visualizzazione progresso che mostra entrambe le fasi.

---

### Task 3.6: Evaluation Ads — GIÀ IMPLEMENTATO, SOLO VERIFICA UX [B1 — aggiornato]

**Stato attuale**: La funzionalità "Applica su Google Ads" è GIÀ presente. `renderAiGenerator()` mostra il bottone "Applica su Google Ads" con modal doppia conferma. Si attiva quando `$canEdit && !empty($project['google_ads_customer_id'])`.

**Cosa fare (solo UX polish)**:
- Verificare che il bottone "Applica" sia visibile in TUTTI i tab dove ci sono suggerimenti (non solo in "Azioni")
- Verificare che il feedback post-applicazione ("Applicato") sia chiaro e persistente
- Se suggerimento non applicabile via API (es. "migliora landing page"): assicurarsi che sia etichettato come "Azione manuale"

**File da verificare**:
- `modules/ads-analyzer/views/campaigns/evaluation.php` — controllare che `renderAiGenerator($key, true)` sia chiamato in tutti i tab con suggerimenti

**Verifica**: in ogni tab con suggerimenti, il bottone "Applica su GAds" è visibile e funzionante.

---

### ~~Task 3.7: Campaign Creator — Import su Account GAds~~ — RIMOSSO

**Motivo**: Verifica codice mostra che il Campaign Creator ha GIÀ sia export CSV per Google Ads Editor che pubblicazione diretta su Google Ads via API (`CampaignCreatorController::publishToGoogleAds()`). Il wizard include bottone "Esporta CSV" e sezione "Pubblica su Google Ads".

---

## Sprint 4 — Coerenza Cross-Modulo

Obiettivo: standardizzare pattern ricorrenti per dare sensazione di prodotto coeso.

### Task 4.1: Bulk Actions Standardizzate [D3]

**Cosa fare**: Applicare il pattern `table-bulk-bar` dove manca.

**File da modificare**:
- `modules/seo-audit/views/audit/issues.php` — aggiungere checkbox + bulk bar (mark as reviewed, export selection)
- `modules/ai-content/views/articles/index.php` — aggiungere checkbox + bulk bar (bulk publish, bulk delete, bulk regenerate)
- Verificare che `table-helpers.php` (`table_bulk_init()`, `table_checkbox_*()`) sia incluso

**Pattern da seguire**: `modules/seo-tracking/views/keywords/index.php` (reference)

**Verifica**: tutte le tabelle principali hanno checkbox + barra bulk azioni.

---

### Task 4.2: Export CSV — Aggiungere Bottoni UI Dove Mancano [D4]

**Cosa fare**: I backend export esistono già in più moduli. Aggiungere solo i bottoni UI dove mancano.

**File da modificare** (solo view — backend già presente):
- `modules/ads-analyzer/views/campaigns/index.php` — aggiungere bottone export (backend esiste in CampaignController)
- `modules/seo-tracking/views/keywords/index.php` — aggiungere bottone export (backend esiste in ExportController)

**NON serve per content-creator** — export già presente nelle view.

**Posizione standard**: bottone in alto a destra, accanto ai filtri, icona Heroicon `arrow-down-tray`.

**Verifica**: le tabelle campaigns e keywords hanno bottone export CSV che punta agli endpoint esistenti.

---

### Task 4.3: Empty State Standardizzati [C9]

**Cosa fare**: Verificare che tutte le tabelle usino `table-empty-state` e non mostrino tabella vuota senza guida.

**File da verificare**:
- Tutti i file view con `<table>` — grep per verificare che abbiano `if (empty($items))` con `View::partial('components/table-empty-state', ...)`

**Verifica**: nessuna tabella mostra solo header senza righe.

---

### Task 4.4: Pagination Sopra + Sotto [C8]

**Cosa fare**: Aggiungere contatore "Pagina X di Y — N risultati" sopra le tabelle.

**File**: `shared/views/components/table-pagination.php` — creare variante `table-pagination-top.php` compatta (solo contatore + per-page selector)

**Adozione**: includere in tutte le tabelle principali accanto ai filtri.

**Verifica**: l'utente sa quanti risultati ci sono e su che pagina è senza scrollare.

---

### ~~Task 4.5: Landing Page Educative Mancanti~~ — RIMOSSO

**Motivo**: Verifica codice mostra che TUTTI e 6 i moduli hanno già landing educative in `views/projects/index.php` con pattern "Scopri cosa puoi fare", "Come funziona", FAQ.

---

### Task 4.6: Tooltip Metriche GSC [C10]

**Cosa fare**: Aggiungere icona (?) con tooltip accanto a CTR, Impressioni, Posizione media, Click.

**File da modificare**:
- `modules/seo-tracking/views/gsc/index.php`
- `modules/seo-tracking/views/dashboard/index.php`
- `modules/seo-tracking/views/keywords/index.php`

**Pattern**: `<span class="cursor-help" title="CTR — Percentuale di persone che cliccano dopo aver visto il risultato in SERP">(?)</span>`

**Verifica**: hover su (?) mostra la definizione della metrica.

---

## Sprint 5 — Cluster UX + Auto-Save + Validation (effort medio, impatto medio)

### Task 5.1: Cluster View Tabellare con Sort [C5, B8]

**Cosa fare**: Aggiungere vista "Sommario" ai risultati cluster di keyword-research.

**Nuovo layout**:
- Toggle: "Vista Card" (attuale) / "Vista Tabella" (nuova)
- Vista tabella: Cluster Name | Keywords count | Volume totale | Intent | Azioni (espandi, esporta)
- Sort: per volume (default), per keyword count, per nome
- Search: filtra cluster per nome o keyword contenuta

**File da modificare**:
- `modules/keyword-research/views/keywords/results.php` — aggiungere toggle + vista tabellare
- `modules/keyword-research/controllers/KeywordController.php` — passare dati aggregati per tabella

**Verifica**: l'utente può vedere tutti i cluster in una tabella ordinabile + cercare keyword specifiche.

---

### Task 5.2: Wizard Auto-Save [B4]

**Cosa fare**: Salvare lo stato dei wizard in localStorage e ripristinare al rientro.

**File da modificare**:
- `modules/keyword-research/views/keywords/wizard.php` — aggiungere `x-effect` Alpine.js che salva in localStorage
- `modules/keyword-research/views/editorial/wizard.php` — idem
- Pattern: `localStorage.setItem('kr_wizard_draft_' + projectId, JSON.stringify(formData))`
- Al mount: `const draft = localStorage.getItem(...)` → pre-popola

**Verifica**: compilare step 1 → refresh pagina → i dati sono ancora lì. Toast: "Bozza ripristinata".

---

### Task 5.3: Form Validation Client-Side [D8]

**Cosa fare**: Aggiungere validazione HTML5 inline per campi obbligatori.

**Pattern minimo**: `required`, `type="email"`, `type="url"`, `minlength`, `pattern`

**File prioritari**:
- `shared/views/projects/create.php` — name required
- `modules/keyword-research/views/keywords/wizard.php` — seed keywords min 1
- `modules/keyword-research/views/editorial/wizard.php` — tema required
- `modules/content-creator/views/urls/import.php` — URL validation

**Verifica**: submit con campo vuoto mostra errore inline senza POST.

---

### ~~Task 5.4: GSC Auto-Sync al Primo Accesso~~ — RIMOSSO

**Motivo**: Banner "Sincronizza GSC per scoprire keyword" già presente in `seo-tracking/views/keywords/index.php` con funzionalità sync integrata.

---

## Sprint 6 — Polish e Responsive (effort basso, impatto basso)

### Task 6.1: Skeleton Loaders per AJAX [D9]

**Cosa fare**: Aggiungere skeleton loader dove i dati si caricano via AJAX.

**Nuovo componente**: `shared/views/components/skeleton-loader.php`
- Varianti: `card`, `table-row`, `kpi-card`
- Pattern: barre grigie animate con `animate-pulse`

**File prioritari**:
- `modules/ads-analyzer/views/campaigns/dashboard.php` — period switch AJAX
- `modules/seo-tracking/views/dashboard/index.php` — dopo redesign

**Verifica**: durante il caricamento AJAX si vedono placeholder animati, non contenuto che scompare.

---

### Task 6.2: Mobile Table Responsive [D10]

**Cosa fare**: Aggiungere `overflow-x-auto` wrapper a tutte le tabelle con 5+ colonne.

**Pattern**:
```html
<div class="overflow-x-auto -mx-4 sm:mx-0">
    <table class="w-full min-w-[640px]">...</table>
</div>
```

**File**: tutti i file view con `<table>` e 5+ colonne `<th>`.

**Verifica**: su viewport stretto le tabelle scrollano orizzontalmente senza rompere il layout.

---

### Task 6.3: Quick Check Salvabile [A3]

**Cosa fare**: Aggiungere bottone "Salva nel progetto" nei risultati Quick Check.

**File da modificare**:
- `modules/keyword-research/views/keywords/quick-check.php` — bottone "Salva" nei risultati
- `modules/keyword-research/controllers/KeywordController.php` — endpoint `POST /save-quick-check` che inserisce in `st_keywords` o `kr_collections`

**Verifica**: dopo Quick Check, l'utente può salvare la keyword in un progetto.

---

### Task 6.4: Notifiche Dropdown Ampliato [C11]

**Cosa fare**: Portare da 5 a 10 le notifiche nel dropdown + aggiungere filtro.

**File da modificare**:
- `shared/views/layout.php` — sezione notification bell dropdown, aumentare limite query
- `controllers/NotificationController.php` — `unreadRecent()` → limit 10

**Verifica**: dropdown mostra 10 notifiche.

---

### Task 6.5: Progress Bar Content Creator Segmenti Minimi [C4]

**Cosa fare**: Garantire visibilità minima per ogni segmento della barra stacked.

**File da modificare**:
- `modules/content-creator/views/urls/dashboard.php` — aggiungere logica JS:
  ```javascript
  // Segmento minimo 3% se > 0 items
  const minWidth = count > 0 ? Math.max(percentage, 3) : 0;
  ```

**Verifica**: anche con 1 errore su 100, il segmento rosso è visibile.

---

## Riepilogo Sprint (aggiornato dopo verifica codice)

**Task rimossi dopo verifica**: B1 (evaluation tabs), B6 (WP publish), B9 (GSC sync), A5 (campaign creator GAds), D1 (landing educative), D4 parziale (content-creator export). Task 3.6 ridotto a solo verifica UX.

| Sprint | Task attivi | Effort | Focus |
|--------|-------------|--------|-------|
| **1** | 1.1-1.6 | 1-2 giorni | Quick wins — nascondere/rimuovere, standardizzare |
| **2** | 2.1-2.5 | 3-4 giorni | Dashboard redesign — gerarchia, leggibilità |
| **3** | 3.1, 3.2, 3.3, 3.5, 3.6(verifica) | 4-5 giorni | Internal links, CSV preview, form semplificato, SSE unificata |
| **4** | 4.1, 4.2, 4.3, 4.4, 4.6 | 2-3 giorni | Bulk actions, export bottoni UI, empty state, tooltip |
| **5** | 5.1, 5.2, 5.3 | 2-3 giorni | Cluster UX, auto-save, validation |
| **6** | 6.1-6.5 | 2-3 giorni | Polish — skeleton, mobile, quick check, notifiche |

**Totale stimato**: ~14-20 giorni di sviluppo

---

## Dipendenze

- Sprint 2 (dashboard redesign) è indipendente da Sprint 1 ma va fatto dopo per beneficiare delle correzioni
- Sprint 4+ può essere eseguito in parallelo con Sprint 3
- I componenti shared creati in Sprint 1 (status-badge) e Sprint 2 (period-selector) vengono adottati negli sprint successivi

## Note Tecniche

- Tutte le modifiche rispettano le Golden Rules (CSRF, prepared statements, return View::render, user passato, Heroicons, italiano)
- I componenti shared nuovi (`status-badge.php`, `period-selector.php`, `skeleton-loader.php`) seguono il pattern esistente in `shared/views/components/`
- Nessun cambio di backend per Sprint 1 (solo view)
- Sprint 3 e 4: backend minimale (endpoint preview CSV, export bottoni puntano a controller esistenti)
