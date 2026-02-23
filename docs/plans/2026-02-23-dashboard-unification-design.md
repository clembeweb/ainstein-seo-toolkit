# Design: Unificazione Dashboard Moduli UI/UX

> Data: 2026-02-23
> Stato: Approvato
> Approccio: Component Library + Refactor progressivo

---

## Problema

Le dashboard dei singoli moduli hanno stili, layout e pattern inconsistenti:

| Inconsistenza | Dettaglio |
|---------------|-----------|
| Padding celle | p-4 vs p-5 vs p-6 (dovrebbe essere p-5) |
| Border radius | rounded-2xl in keyword-research vs rounded-xl ovunque |
| Dimensioni icone | w-10 h-10 vs w-12 h-12 |
| Layout KPI | Da 2 a 6 colonne, label in posizioni diverse |
| Empty states | Dimensioni e stili diversi per modulo |
| Sezione "Come funziona" | Solo meta-tags ce l'ha, gli altri no |
| Hero banner landing | Solo ai-content e keyword-research |
| Tour guidato | Config esiste per tutti ma non tutti i moduli lo includono nella view |

---

## Soluzione

Creare componenti shared riutilizzabili e rifactorare tutte le dashboard per usarli. Aggiungere hero banner alle landing mancanti e sezione "Come funziona" a tutte le dashboard progetto.

---

## 1. Componenti Shared

### 1.1 `dashboard-kpi-card.php`

Card metrica singola. Parametri:

```php
View::partial('components/dashboard-kpi-card', [
    'label' => 'Keyword monitorate',
    'value' => 28,
    'icon' => '<path .../>',           // SVG path Heroicons
    'color' => 'blue',                 // blue|emerald|amber|purple|rose|cyan|orange
    'url' => '/seo-tracking/...',      // opzionale: rende la card cliccabile
    'suffix' => '%',                   // opzionale: suffisso dopo il valore
    'subtitle' => 'Ultima: 5 ore fa', // opzionale: testo sotto il valore
])
```

HTML output:

```html
<a href="{url}" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200
   dark:border-slate-700 p-5 hover:shadow-md hover:border-{color}-300
   dark:hover:border-{color}-700 transition-all block">
    <div class="flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-{color}-100 dark:bg-{color}-900/30
             flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-{color}-600 dark:text-{color}-400">...</svg>
        </div>
        <div>
            <p class="text-2xl font-bold text-slate-900 dark:text-white">{value}{suffix}</p>
            <p class="text-sm text-slate-500 dark:text-slate-400">{label}</p>
        </div>
    </div>
</a>
```

### 1.2 `dashboard-stats-row.php`

Wrapper grid per KPI cards. Parametri:

```php
View::partial('components/dashboard-stats-row', [
    'cards' => [
        ['label' => '...', 'value' => 28, 'icon' => '...', 'color' => 'blue'],
        ['label' => '...', 'value' => 41, 'icon' => '...', 'color' => 'amber'],
        // ... 2-6 cards
    ],
])
```

HTML: `<div class="grid grid-cols-2 md:grid-cols-{n} gap-4">` dove n = min(count, 4) su desktop.

### 1.3 `dashboard-hero-banner.php`

Banner informativo gradient per le landing page. Parametri:

```php
View::partial('components/dashboard-hero-banner', [
    'title' => 'AI SEO Content Generator',
    'description' => 'Genera articoli SEO ottimizzati...',
    'gradient' => 'from-amber-500 to-orange-600',  // colore modulo
    'steps' => [
        ['number' => '1', 'label' => 'Keyword'],
        ['number' => '2', 'label' => 'Analisi'],
        ['number' => '3', 'label' => 'Genera'],
        ['number' => '4', 'label' => 'Pubblica'],
    ],
    'dismissible' => true,              // opzionale: localStorage dismiss
    'storageKey' => 'aic_hero_hidden',   // chiave localStorage
])
```

HTML: Card con gradient bg, testo bianco, step circolari a destra. Chiudibile con X se dismissible=true.

### 1.4 `dashboard-how-it-works.php`

Sezione "Come funziona" con step numerati. Parametri:

```php
View::partial('components/dashboard-how-it-works', [
    'steps' => [
        ['number' => '1', 'title' => 'Importa', 'description' => 'URL da WP/Sitemap/CSV'],
        ['number' => '2', 'title' => 'Analizza', 'description' => 'Scraping contenuti'],
        ['number' => '3', 'title' => 'Genera', 'description' => 'AI crea contenuti'],
        ['number' => '4', 'title' => 'Pubblica', 'description' => 'Su WordPress'],
    ],
    'color' => 'amber',  // colore accent per i numeri
])
```

HTML: Card con heading "Come funziona", grid di step con numero circolare + titolo + descrizione.

### 1.5 `dashboard-mode-card.php`

Card selezione modalita' (pattern keyword-research). Parametri:

```php
View::partial('components/dashboard-mode-card', [
    'title' => 'Articoli Manuali',
    'description' => 'Scrivi articoli guidato dall\'AI...',
    'icon' => '<path .../>',
    'gradient' => 'from-blue-500 to-indigo-600',
    'url' => '/ai-content?type=manual',
    'cost' => '10 cr',                   // opzionale: badge costo
    'costColor' => 'amber',              // opzionale
    'badge' => '3 progetti',             // opzionale: conteggio
    'dataTour' => 'aic-manual',          // opzionale: tour attribute
])
```

HTML: Card con icona gradient, titolo, descrizione, badge costo, footer con link CTA. Stile identico a keyword-research mode cards ma con `rounded-xl` (non rounded-2xl).

---

## 2. Layout Standard Dashboard Progetto

Struttura a 4 righe:

```
ROW 1: dashboard-stats-row (KPI cards, grid 2 md:4)
ROW 2: Contenuto specifico modulo (charts, quick actions, etc.)
ROW 3: Attivita' recente / Lista dati recenti
ROW 4: dashboard-how-it-works + Info crediti
```

### KPI per modulo

| Modulo | KPI 1 | KPI 2 | KPI 3 | KPI 4 |
|--------|-------|-------|-------|-------|
| ai-content manual | Keywords | Articoli | Pubblicati | Parole generate |
| ai-content auto | In coda | Completate oggi | Prossima schedulazione | Articoli totali |
| ai-content meta-tag | URL totali | Scrappate | Generate | Pubblicate |
| seo-audit | Health Score | Issues critiche | Warning | Pagine analizzate |
| seo-tracking | Keywords tracciate | Posizione media | Top 10 | Variazioni 7gg |
| internal-links | Pagine analizzate | Link interni | Link esterni | Relevance media |
| content-creator | URL totali | Scrappate | Generate | Approvate |

### "Come funziona" per modulo

| Modulo | Step 1 | Step 2 | Step 3 | Step 4 |
|--------|--------|--------|--------|--------|
| ai-content manual | Aggiungi Keyword | Analisi SERP | Genera articolo | Pubblica |
| ai-content auto | Importa keyword | Schedula | AI genera | Pubblica |
| ai-content meta-tag | Importa URL | Scrape pagine | Genera meta tag | Pubblica |
| seo-audit | Inserisci URL | Crawl sito | Analisi AI | Piano d'azione |
| seo-tracking | Aggiungi keyword | Rank check | Monitora trend | Report AI |
| internal-links | Importa URL | Scrape links | Analisi struttura | Ottimizza |
| content-creator | Configura | Importa URL | Genera contenuti | Pubblica |

---

## 3. Layout Standard Landing Page Modulo

### Moduli tipizzati (ai-content, keyword-research, ads-analyzer)

```
ROW 1: dashboard-hero-banner (gradient colore modulo, dismissibile)
ROW 2: Mode cards (dashboard-mode-card, grid responsivo)
ROW 3: Progetti recenti (se esistono)
```

- ai-content: gia' ha questo layout, allineare CSS ai componenti shared
- keyword-research: gia' ha questo layout, allineare rounded-2xl a rounded-xl
- ads-analyzer: ha hero banner, allineare ai componenti shared

### Moduli senza tipi (seo-audit, seo-tracking, internal-links, content-creator)

```
ROW 1: dashboard-hero-banner (gradient colore modulo, dismissibile)
ROW 2: Lista progetti (card grid 1 md:2 lg:3)
ROW 3: Empty state se nessun progetto
```

Hero banner da aggiungere a: seo-audit, seo-tracking, internal-links, content-creator.

---

## 4. Tour Guidato

Il sistema esiste gia' completo:
- Config: `config/onboarding.php` con step per tutti i moduli
- Component: `shared/views/components/onboarding-spotlight.php`
- Service: `core/OnboardingService.php`
- Sidebar: bottone "?" con `navTourButton()` in `nav-items.php`

**Da fare:** verificare che ogni dashboard includa il componente `onboarding-spotlight` e che gli elementi abbiano gli attributi `data-tour` corretti. Aggiungere `data-tour` ai nuovi componenti shared dove necessario.

---

## 5. Colori per Modulo

| Modulo | Colore Primario | Gradient Banner |
|--------|----------------|-----------------|
| ai-content | amber | from-amber-500 to-orange-600 |
| seo-audit | emerald | from-emerald-500 to-teal-600 |
| seo-tracking | blue | from-blue-500 to-indigo-600 |
| keyword-research | purple | from-violet-500 to-purple-600 |
| internal-links | cyan | from-cyan-500 to-blue-600 |
| ads-analyzer | rose | from-rose-500 to-pink-600 |
| content-creator | orange | from-orange-500 to-amber-600 |

---

## 6. File da Modificare

### Nuovi componenti (creare)

| File | Descrizione |
|------|-------------|
| `shared/views/components/dashboard-kpi-card.php` | Card KPI singola |
| `shared/views/components/dashboard-stats-row.php` | Grid wrapper KPI |
| `shared/views/components/dashboard-hero-banner.php` | Banner gradient landing |
| `shared/views/components/dashboard-how-it-works.php` | Sezione "Come funziona" |
| `shared/views/components/dashboard-mode-card.php` | Card selezione modalita' |

### Dashboard progetto (modificare)

| File | Modifica |
|------|----------|
| `modules/ai-content/views/dashboard.php` | Sostituire KPI inline con componenti shared, aggiungere "Come funziona" |
| `modules/ai-content/views/auto/dashboard.php` | Allineare KPI, aggiungere "Come funziona" |
| `modules/ai-content/views/meta-tags/dashboard.php` | Allineare KPI a componenti shared |
| `modules/seo-audit/views/audit/dashboard.php` | Allineare padding/radius, aggiungere "Come funziona" |
| `modules/seo-tracking/views/dashboard/index.php` | Sostituire KPI con componenti shared, aggiungere "Come funziona" |
| `modules/internal-links/views/analyzer/index.php` | Allineare KPI, aggiungere "Come funziona" |
| `modules/content-creator/views/projects/show.php` | Allineare KPI, aggiungere "Come funziona" |

### Landing page (modificare)

| File | Modifica |
|------|----------|
| `modules/ai-content/views/projects/index.php` | Allineare CSS ai componenti shared |
| `modules/keyword-research/views/dashboard.php` | Allineare rounded-2xl a rounded-xl |
| `modules/ads-analyzer/views/dashboard.php` | Allineare hero banner a componente shared |
| `modules/seo-audit/views/projects/index.php` | Aggiungere hero banner |
| `modules/seo-tracking/views/projects/index.php` | Aggiungere hero banner |
| `modules/internal-links/views/projects/index.php` | Aggiungere hero banner |
| `modules/content-creator/views/projects/index.php` | Aggiungere hero banner |

---

## 7. Non in Scope

- Ristrutturazione delle route
- Modifiche ai controller (solo alle view)
- Aggiunta di nuovi dati/KPI (usiamo quelli gia' disponibili)
- Redesign del layout principale (sidebar, header, etc.)
- Modifiche al sistema di tour guidato (gia' completo)

---

## 8. Rischi e Mitigazioni

| Rischio | Mitigazione |
|---------|-------------|
| View rotte dopo refactor | php -l su ogni file + test manuale in browser |
| Variabili mancanti nei componenti shared | Usare `??` e default sicuri in ogni componente |
| Dark mode rotto | Testare light e dark su ogni modulo |
| Tour guidato non trova elementi | Verificare data-tour attributes dopo refactor |
| Scope creep | Rispettare "non in scope": solo view, no controller changes |
