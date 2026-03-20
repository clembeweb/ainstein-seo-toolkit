# Piano: Import Immagini da URL — Content Creator

> Data: 2026-03-20
> Modulo: content-creator
> Stima: 4 task, ~1 sessione

---

## Obiettivo

Aggiungere una quarta modalità di import nella pagina `images/import`: **Import da URL**.

**Flusso utente:**
1. Utente inserisce URL pagina prodotto (es. `https://shop.com/scarpa-x`)
2. Click "Analizza" → scraping con `ScraperService::fetchRaw()` + `extractImages()`
3. Vengono mostrate tutte le immagini trovate in una griglia di thumbnail
4. L'utente seleziona quale immagine è il prodotto, inserisce nome e categoria
5. Click "Importa" → l'immagine viene scaricata e salvata come `cc_images` con `source_type = 'url'`

---

## Task 1: Endpoint AJAX — Scrape immagini da URL

**File**: `modules/content-creator/controllers/ImageController.php`

Nuovo metodo `scrapeImages(int $id)`:

```php
// POST /content-creator/projects/{id}/images/scrape-url
public function scrapeImages(int $id): void
```

**Logica:**
1. Auth + CSRF + access check
2. Riceve `$_POST['url']`
3. Usa `ScraperService::fetchRaw($url)` per ottenere l'HTML
4. Usa `ScraperService::extractImages($html)` per estrarre le immagini
5. Filtra: solo immagini con dimensioni ragionevoli (escludi icone, tracking pixel):
   - Escludi URL con pattern: `favicon`, `logo`, `icon`, `pixel`, `tracking`, `badge`, `button`, `banner`, `sprite`
   - Escludi estensioni `.svg`, `.gif` (di solito non sono foto prodotto)
   - Escludi immagini con dimensioni note < 100px (se `width`/`height` nell'attributo)
6. Risolvi URL relativi → assoluti usando il dominio della pagina
7. Estrai anche `og:image` e `product:image` dai meta tag (priorità alta)
8. Estrai `title` della pagina come suggerimento per il nome prodotto
9. Ritorna JSON:
   ```json
   {
     "success": true,
     "page_title": "Giacca in pelle nera - Shop.com",
     "images": [
       {"src": "https://...", "alt": "Giacca pelle", "priority": "high"},
       {"src": "https://...", "alt": "", "priority": "normal"}
     ]
   }
   ```
   Le immagini `og:image` hanno `priority: "high"` e vengono mostrate per prime.

**Pattern rispettato**: `ScraperService` per tutto lo scraping (GR #12), no DOMDocument diretto.

---

## Task 2: Route

**File**: `modules/content-creator/routes.php`

Aggiungere:
```php
Router::post('/content-creator/projects/{id}/images/scrape-url',
    fn($id) => (new ImageController())->scrapeImages((int)$id));
```

Posizionare vicino alle altre route images (dopo `fetch-cms`).

---

## Task 3: Case 'url' nell'importStore

**File**: `modules/content-creator/controllers/ImageController.php`

Nel metodo `importStore()`, aggiungere `case 'url':` nello switch:

```php
case 'url':
    $result = $this->importFromUrl($id, $user['id'], $service);
    $inserted = $result['inserted'];
    $errors = $result['errors'];
    break;
```

Nuovo metodo privato `importFromUrl()`:

```php
private function importFromUrl(int $projectId, int $userId, ImageGenerationService $service): array
```

**Logica:**
1. Legge `$_POST['product_url']`, `$_POST['image_url']`, `$_POST['product_name']`, `$_POST['sku']`, `$_POST['category']`
2. Crea record in `cc_images` con `source_type = 'url'`, `product_url = $productUrl`
3. Scarica l'immagine selezionata con `$service->downloadSourceImage($imageUrl, $imageId)`
4. Aggiorna status a `source_acquired` se download OK
5. Pattern identico a `importFromCsv()` per un singolo item

---

## Task 4: Tab "Da URL" nella view import

**File**: `modules/content-creator/views/images/import.php`

### 4a. Aggiungere tab button nella nav (dopo CSV, prima di Manual):

```html
<button @click="activeTab = 'url'"
        :class="activeTab === 'url' ? '...' : '...'"
        class="pb-3 px-1 border-b-2 text-sm font-medium transition-colors">
    <!-- Heroicon: globe-alt -->
    Da URL
</button>
```

### 4b. Aggiungere pannello tab:

**Step 1 — Input URL + bottone Analizza:**
- Campo input URL (text, placeholder "https://shop.com/prodotto")
- Bottone "Analizza Pagina"
- Spinner durante lo scraping

**Step 2 — Griglia immagini (dopo scraping):**
- Griglia `grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3`
- Ogni thumbnail: immagine con `object-cover`, bordo selezionabile al click
- Immagini `priority: high` (og:image) in prima posizione con badge "Consigliata"
- Una sola selezionabile alla volta (radio behavior)
- Sotto la griglia: nome prodotto (pre-compilato dal `page_title`), SKU, categoria

**Step 3 — Conferma import:**
- Bottone "Importa Prodotto" (disabilitato se nessuna immagine selezionata)
- Chiama `importUrl` con `source: 'url'`
- Redirect alla lista dopo successo

### 4c. Alpine.js state:

Aggiungere al componente `importImageWizard()`:
```javascript
// URL import
urlInput: '',
urlLoading: false,
urlImages: [],
urlSelectedImage: null,
urlProductName: '',
urlSku: '',
urlCategory: 'fashion',
urlPageTitle: '',

async scrapeUrl() { ... },  // POST /scrape-url
async importUrl() { ... },  // POST /import con source='url'
```

---

## File impattati (riepilogo)

| File | Modifica |
|------|----------|
| `controllers/ImageController.php` | +`scrapeImages()`, +`importFromUrl()`, case 'url' in switch |
| `routes.php` | +1 route POST scrape-url |
| `views/images/import.php` | +tab button, +pannello URL, +Alpine state |

**File NON toccati:**
- `ScraperService.php` — `fetchRaw()` + `extractImages()` già esistono
- `ImageGenerationService.php` — `downloadSourceImage()` già esiste
- `Image.php` model — `SOURCE_URL` già definito, `create()` già supporta tutti i campi
- Tabelle DB — nessuna migrazione

---

## Checklist pre-commit

- [ ] `php -l` su ImageController.php
- [ ] Testi UI in italiano
- [ ] `response.ok` check nel JS prima di `response.json()`
- [ ] CSRF token su entrambi i POST (scrape-url e import)
- [ ] Icone solo Heroicons SVG
- [ ] `rounded-xl`, classi tabella standard
- [ ] Immagini thumbnail con `loading="lazy"` e `onerror` fallback
- [ ] URL relativi risolti in assoluti (no immagini rotte)
