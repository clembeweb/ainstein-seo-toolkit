# Piano: Import Bulk Immagini — Sitemap + Multi-URL

> Data: 2026-03-20
> Modulo: content-creator (images)
> Pattern reference: `UrlController` (content-creator content mode)
> Stima: 6 task, ~1.5 sessioni

---

## Contesto

L'import immagini attuale ha 4 tab (CMS, CSV, Da URL, Upload Manuale) ma la tab "Da URL" importa **una sola URL** alla volta — inaccettabile per un tool bulk. Mancano le stesse modalita disponibili nell'import contenuti:

1. **Sitemap** — scopri sitemap, seleziona URL, importa in batch
2. **Multi-URL** — textarea con N url, scraping batch per estrarre immagini

### Pattern di riferimento

Il `UrlController` del content-creator (modalita contenuti) ha gia:
- `discover()` → `SitemapService::discoverFromRobotsTxt()`
- `importSitemap()` → `SitemapService::previewMultiple()` → bulk insert
- `importManual()` → textarea multi-riga → bulk insert

La differenza per le immagini: dopo aver raccolto le URL, per ciascuna serve lo **scraping dell'immagine prodotto** (JSON-LD/OG/img). Questo aggiunge uno step intermedio.

---

## Flusso UX

### Tab "Da URL" (rinominata "URL Prodotti")

```
Step 1: Input multi-riga
┌──────────────────────────────────────────────────┐
│ Inserisci gli URL delle pagine prodotto          │
│ (uno per riga)                                   │
│ ┌──────────────────────────────────────────────┐ │
│ │ https://shop.com/prodotto-1                  │ │
│ │ https://shop.com/prodotto-2                  │ │
│ │ https://shop.com/prodotto-3                  │ │
│ │ ...                                          │ │
│ └──────────────────────────────────────────────┘ │
│ 3 URL valide           [Analizza Pagine]         │
└──────────────────────────────────────────────────┘

Step 2: Risultati scraping (tabella)
┌───┬──────────────────────┬──────────────┬──────────┬──────────┐
│ ✓ │ URL                  │ Immagine     │ Nome     │ Categoria│
├───┼──────────────────────┼──────────────┼──────────┼──────────┤
│ ☑ │ shop.com/prodotto-1  │ [thumb] [→]  │ Giacca   │ Fashion ▼│
│ ☑ │ shop.com/prodotto-2  │ [thumb] [→]  │ Scarpa   │ Fashion ▼│
│ ☐ │ shop.com/prodotto-3  │ ⚠ Non trovata│ Borsa    │ Fashion ▼│
└───┴──────────────────────┴──────────────┴──────────┴──────────┘
  [→] = click per aprire picker alternativo (mostra tutte le immagini)
  ⚠ = nessuna immagine trovata, possibilita di upload manuale

  Categoria per tutti: [Fashion ▼]
  [Importa 2 prodotti selezionati]
```

### Tab "Sitemap"

```
Step 1: Discovery
┌──────────────────────────────────────────────────┐
│ URL del sito: [www.amevista.com    ] [Trova]     │
│                                                  │
│ Sitemap trovate:                                 │
│ ☑ sitemap-products.xml (234 URL) [robots.txt]    │
│ ☐ sitemap-posts.xml (56 URL) [robots.txt]        │
│ ☐ sitemap.xml (1240 URL) [common_path]           │
│                                                  │
│ Filtro URL: [*prodott*]          Max: [100]      │
│                                                  │
│ [Analizza Prodotti]                              │
└──────────────────────────────────────────────────┘

Step 2: Scraping batch → stessa tabella di "URL Prodotti" step 2
```

---

## Architettura

### Endpoint nuovo: scraping batch

```
POST /content-creator/projects/{id}/images/scrape-batch
Body: { urls: ["url1", "url2", ...], _csrf_token }
Response: {
  success: true,
  results: [
    {
      url: "https://...",
      page_title: "Prodotto X",
      best_image: { src, thumb, source, alt },   // auto-selezionata (priority high)
      all_images: [ {src, thumb, source, priority}, ... ],  // max 5 per URL
      error: null
    },
    {
      url: "https://...",
      page_title: "",
      best_image: null,
      all_images: [],
      error: "Pagina non raggiungibile"
    }
  ]
}
```

Questo endpoint riusa la logica di `scrapeImages()` gia implementata, ma per N url in batch. Per evitare timeout: max 20 URL per batch, timeout 10s per URL, `ignore_user_abort(true)`.

### Endpoint esistente riusato: sitemap discovery

Il `UrlController::discover()` e il `SitemapService` sono gia pronti. Li chiamiamo direttamente dal frontend con le route gia esistenti:
- `POST /content-creator/projects/{id}/discover` → scopre sitemap
- `SitemapService::previewMultiple()` → estrae URL dalla sitemap

Ma serve un **nuovo endpoint** per importare da sitemap nel contesto immagini:

```
POST /content-creator/projects/{id}/images/import-sitemap-urls
Body: { sitemaps: [...], filter: "...", max_urls: 100 }
Response: { success: true, urls: ["url1", "url2", ...], total: 234 }
```

Questo restituisce le URL (non le importa ancora). Poi il frontend chiama `scrape-batch` su quelle URL per ottenere le immagini, e infine `import` con `source='bulk'` per importarle.

### Endpoint nuovo: import bulk

```
POST /content-creator/projects/{id}/images/import
Body: { source: 'bulk', items: [ {url, image_url, name, sku, category}, ... ] }
```

Nuovo `case 'bulk'` nello switch di `importStore()`, che chiama `importBulk()` — loop su items, per ciascuno crea record + download immagine (pattern identico a importFromCms ma senza connettore).

---

## Task

### Task 1: Endpoint `scrapeBatch()` nel controller

**File**: `ImageController.php`

Nuovo metodo che:
1. Riceve array di URL da `$_POST['urls']` (max 20)
2. Per ciascuna URL, riusa la logica gia in `scrapeImages()`:
   - `fetchRaw()` → estrai JSON-LD/OG/img → `resolveUrl()` → `fetchThumbnailBase64()`
3. Per ciascuna, restituisce `best_image` (prima high-priority) e `all_images` (max 5)
4. `ignore_user_abort(true)`, `set_time_limit(300)`, `ob_start()`

**Refactor necessario**: estrarre la logica di scraping da `scrapeImages()` in un metodo privato `extractImagesFromHtml(string $html, string $baseUrl): array` riusabile sia per singolo che per batch.

### Task 2: Endpoint `extractSitemapUrls()` nel controller

**File**: `ImageController.php`

Nuovo metodo:
1. Riceve `sitemaps[]` e `filter` e `max_urls`
2. Usa `SitemapService::previewMultiple($sitemaps, $filter, $maxUrls)`
3. Ritorna le URL trovate (non importa, solo lista)

### Task 3: Endpoint `discover()` per immagini

**File**: `ImageController.php`

Copia esatta di `UrlController::discover()`:
1. Riceve `site_url`
2. Usa `SitemapService::discoverFromRobotsTxt($siteUrl, true)`
3. Ritorna lista sitemap con conteggi

(Oppure: riusare direttamente la route esistente `/content-creator/projects/{id}/discover` se il frontend puo chiamarla — verificare se l'access check e compatibile.)

### Task 4: Case `'bulk'` nell'importStore

**File**: `ImageController.php`

Nuovo `case 'bulk'` + metodo `importBulk()`:
1. Riceve `$_POST['items']` come JSON array
2. Per ciascun item: crea `cc_images` + `downloadSourceImage()`
3. Pattern identico a `importFromCms()` ma senza connettore
4. `Database::reconnect()` dopo ogni download

### Task 5: Route

**File**: `routes.php`

Aggiungere:
```php
Router::post('.../images/scrape-batch', ...);
Router::post('.../images/sitemap-urls', ...);
Router::post('.../images/discover-sitemap', ...);
```

### Task 6: View — Rifare le tab URL e Sitemap

**File**: `views/images/import.php`

#### Tab "Da URL" → rinominata "URL Prodotti":
- Step 1: textarea multi-riga con contatore URL + bottone "Analizza Pagine"
- Step 2: tabella risultati con:
  - Checkbox selezione
  - URL (troncata)
  - Thumbnail immagine auto-selezionata (best_image)
  - Bottone [cambio] per picker alternativo (mostra all_images in mini-modal)
  - Nome prodotto (editabile, pre-compilato da page_title)
  - Categoria dropdown
  - Stato: ✓ immagine trovata / ⚠ non trovata
- "Categoria per tutti" selector globale
- Bottone "Importa N prodotti selezionati"

#### Nuova tab "Sitemap":
- Step 1: input sito URL + "Trova Sitemap" (chiama discover)
- Step 2: lista sitemap con checkbox + conteggi + badge fonte (robots.txt/common)
- Filtro URL (wildcard) + campo Max URL
- Bottone "Analizza Prodotti" → chiama sitemap-urls per ottenere le URL, poi scrape-batch
- Step 3: stessa tabella risultati di "URL Prodotti"

#### Ordine tab finale:
```
CMS | CSV | Sitemap | URL Prodotti | Upload Manuale
```

**Alpine.js**: estendere `importImageWizard()` con:
- `sitemapSiteUrl`, `sitemapLoading`, `sitemaps[]`, `sitemapSelected[]`, `sitemapFilter`, `sitemapMaxUrls`
- `bulkUrls` (textarea), `bulkResults[]`, `bulkSelected[]`, `bulkAnalyzing`
- `scrapeUrlBatch()`, `discoverSitemaps()`, `extractSitemapUrls()`, `importBulk()`

---

## Ordine esecuzione

```
Task 1 (refactor + scrapeBatch) — base per tutto
    ↓
Task 2 + Task 3 (sitemap endpoints) — indipendenti da Task 1
    ↓
Task 4 (case bulk import)
    ↓
Task 5 (route)
    ↓
Task 6 (view — tab URL Prodotti + Sitemap)
```

---

## Limiti e sicurezza

- **Max 20 URL per batch scrape** — evita timeout
- **Timeout 10s per URL** in scrapeBatch — totale max ~200s
- **Max 5 immagini per URL** nella risposta — limita payload JSON
- **Max 100 URL da sitemap** — configurabile
- **`ignore_user_abort(true)` + `set_time_limit(300)`** per batch
- **`Database::reconnect()`** dopo ogni batch di 5 download

---

## File impattati

| File | Modifica |
|------|----------|
| `controllers/ImageController.php` | +`scrapeBatch()`, +`extractSitemapUrls()`, +`discoverSitemap()`, +`importBulk()`, refactor `extractImagesFromHtml()` |
| `routes.php` | +3 route POST |
| `views/images/import.php` | Nuova tab Sitemap, rifare tab URL come multi-URL, Alpine state |

**File NON toccati:**
- `SitemapService.php` — riusato cosi com'e
- `ScraperService.php` — riusato cosi com'e
- `ImageGenerationService.php` — `downloadSourceImage()` riusato
- Tabelle DB — nessuna migrazione
- `UrlController.php` — non toccato, pattern solo copiato
