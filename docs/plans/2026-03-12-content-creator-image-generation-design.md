# Content Creator — Generazione Massiva Immagini (Design Spec)

> **Data:** 2026-03-12
> **Modulo:** content-creator
> **Stato:** Design approvato

## Obiettivo

Aggiungere al modulo Content Creator una modalità "Immagini" indipendente dal flusso testo, per generare immagini prodotto a partire dalla foto originale presente nell'e-commerce dell'utente. Due scenari principali:

- **Fashion try-on**: modello (uomo/donna) che indossa il prodotto (abiti, accessori, scarpe, gioielli)
- **Home/Lifestyle staging**: prodotto posizionato in ambientazione realistica (soggiorno, cucina, ufficio, esterno)

## Architettura Generale

Sottoprogetti modulari dentro il progetto content-creator esistente. L'utente vede due tab nel progetto: "Contenuti" (flusso testo attuale) e "Immagini" (nuovo flusso). Ognuno ha le proprie tabelle ma condivide progetto, connettore CMS e impostazioni comuni.

**Provider AI v1:** Google Gemini (Imagen) con architettura multi-provider per swap futuro.

**Rischio strategico:** la qualità dell'output Gemini per virtual try-on potrebbe non essere sufficiente per uso e-commerce. Mitigazione: provider interface per swap, review manuale obbligatorio, Task 0 proof-of-concept prima dell'implementazione completa.

---

## 1. Database Schema

### Tabella `cc_images`

```sql
CREATE TABLE cc_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    url VARCHAR(2048) DEFAULT NULL,
    sku VARCHAR(100) DEFAULT NULL,
    product_name VARCHAR(500) NOT NULL,
    category ENUM('fashion', 'home', 'custom') NOT NULL DEFAULT 'fashion',
    source_image_path VARCHAR(500) DEFAULT NULL,
    source_image_url VARCHAR(2048) DEFAULT NULL,
    source_type ENUM('cms', 'upload', 'url') NOT NULL DEFAULT 'upload',
    connector_id INT UNSIGNED DEFAULT NULL,
    cms_entity_id VARCHAR(100) DEFAULT NULL,
    cms_entity_type VARCHAR(50) DEFAULT NULL,
    generation_settings JSON DEFAULT NULL COMMENT 'Override preset per-item, null = usa default progetto',
    status ENUM('pending', 'source_acquired', 'generated', 'approved', 'published', 'error') NOT NULL DEFAULT 'pending',
    error_message TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_project_status (project_id, status),
    FOREIGN KEY (project_id) REFERENCES cc_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Tabella `cc_image_variants`

```sql
CREATE TABLE cc_image_variants (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    image_id INT UNSIGNED NOT NULL,
    variant_number TINYINT UNSIGNED NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    prompt_used TEXT DEFAULT NULL,
    revised_prompt TEXT DEFAULT NULL,
    is_approved TINYINT(1) NOT NULL DEFAULT 0,
    is_pushed TINYINT(1) NOT NULL DEFAULT 0,
    cms_sync_error TEXT DEFAULT NULL,
    file_size_bytes INT UNSIGNED DEFAULT NULL,
    generation_time_ms INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_image_id (image_id),
    FOREIGN KEY (image_id) REFERENCES cc_images(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Estensione `cc_projects.ai_settings` JSON

Aggiungere chiavi per i default immagini:

```json
{
    "image_defaults": {
        "scene_type": "fashion",
        "gender": "woman",
        "background": "studio_white",
        "environment": "living_room",
        "photo_style": "professional",
        "variants_count": 3,
        "custom_prompt": ""
    }
}
```

Il campo `cc_images.generation_settings` si popola SOLO se l'utente fa override per singolo item. Se null, il service usa i default del progetto.

### Migrazione `cc_jobs`

Riusa `cc_jobs` con nuovi tipi. Migrazione obbligatoria:

```sql
ALTER TABLE cc_jobs MODIFY COLUMN type ENUM('scrape','generate','cms_push','image_generate','image_push') NOT NULL DEFAULT 'scrape';
```

Aggiornare anche le costanti in `Job.php` per i nuovi tipi.

Non serve `image_acquire`: il download foto sorgente avviene inline durante l'import.

### Indice aggiuntivo `cc_images`

```sql
ALTER TABLE cc_images ADD INDEX idx_user (user_id);
```

### Colonna `updated_at` su `cc_image_variants`

```sql
ALTER TABLE cc_image_variants ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
```

### Accesso condiviso e crediti

`ImageController` usa `Project::findAccessible($id, $userId)` (NON `findByUser()`) per supportare progetti condivisi. I crediti sono sempre scalati dall'owner del progetto via `ProjectAccessService::getOwnerId()`, non dall'user_id dell'item.

### Campo canonico per immagine sorgente

`source_image_path` (locale) è OBBLIGATORIO per la generazione — il provider legge solo file locali. `source_image_url` è metadata (URL originale nel CMS), usato solo per riferimento e re-download se necessario.

---

## 2. Architettura File

### Nuovi file

```
modules/content-creator/
├── controllers/
│   ├── ImageController.php              # CRUD, import, preview, approve, bulk, export ZIP, push SSE
│   └── ImageGeneratorController.php     # SSE generazione immagini
├── models/
│   ├── Image.php                        # cc_images
│   └── ImageVariant.php                 # cc_image_variants
├── services/
│   ├── ImageGenerationService.php       # Orchestrazione: prompt building + provider call
│   └── providers/
│       ├── ImageProviderInterface.php   # Interfaccia provider
│       └── GeminiImageProvider.php      # Implementazione Gemini v1
├── views/
│   ├── images/
│   │   ├── index.php                    # Lista prodotti con thumbnail + toolbar
│   │   ├── import.php                   # Import da CMS / CSV / manuale
│   │   └── preview.php                  # Preview varianti con approve/reject
│   └── partials/
│       └── image-nav.php                # Tab navigazione immagini
├── cron/
│   └── image-cleanup.php               # Cleanup varianti rifiutate e sorgenti orfane
```

### File modificati

```
modules/content-creator/
├── routes.php                           # Nuove route /images/*
├── module.json                          # Gruppo image_config + costo
├── views/
│   └── projects/
│       ├── show.php                     # Tab Contenuti/Immagini
│       └── settings.php                 # Tab preset immagini
├── services/
│   └── connectors/
│       ├── ConnectorInterface.php       # Invariato
│       ├── ImageCapableConnectorInterface.php  # NUOVO - estende ConnectorInterface
│       ├── WordPressConnector.php       # Implementa ImageCapableConnectorInterface
│       ├── ShopifyConnector.php         # Implementa ImageCapableConnectorInterface
│       ├── PrestaShopConnector.php      # Implementa ImageCapableConnectorInterface
│       └── MagentoConnector.php         # Implementa ImageCapableConnectorInterface
```

---

## 3. Provider Architecture

### Interface

```php
interface ImageProviderInterface {
    /**
     * Genera un'immagine a partire da un'immagine sorgente e un prompt.
     * @return array ['success' => bool, 'image_data' => string (binary), 'mime' => string, 'revised_prompt' => ?string, 'error' => ?string]
     */
    public function generate(string $sourceImagePath, string $prompt): array;

    public function getProviderName(): string;

    public function getMaxImageSize(): int; // bytes

    public function getSupportedFormats(): array; // ['png', 'jpg', 'webp']
}
```

### GeminiImageProvider

**Nota GR1:** questo provider bypassa `AiService` intenzionalmente. `AiService` è text-only (Claude/OpenAI). La generazione immagini richiede API multimodale (Gemini) con input/output binario, incompatibile con `AiService::analyze()`. Il bypass è giustificato e isolato dietro la provider interface.

**API Logger provider:** `google_gemini` (nuovo provider da aggiungere alla lista nota in `ApiLoggerService`).

**API Key:** `Settings::get('google_gemini_api_key')` — nuova chiave da aggiungere nel pannello admin settings (sezione API Keys). Segue GR5 (in database, mai in .env).

```php
class GeminiImageProvider implements ImageProviderInterface {
    private string $apiKey;
    private string $model;

    public function __construct() {
        $this->apiKey = Settings::get('google_gemini_api_key');
        $this->model = ModuleLoader::getSetting('content-creator', 'image_model', 'gemini-2.0-flash-exp');
    }

    public function generate(string $sourceImagePath, string $prompt): array {
        // 1. Leggi immagine e converti in base64
        // 2. POST a Gemini API con parts: [inlineData (immagine), text (prompt)]
        // 3. Estrai immagine dalla risposta
        // 4. Log con ApiLoggerService (provider: 'google_gemini')
        // 5. Return result array
    }
}
```

### ImageGenerationService (orchestratore)

```php
class ImageGenerationService {
    private ImageProviderInterface $provider;

    public function __construct() {
        $providerName = ModuleLoader::getSetting('content-creator', 'image_provider', 'gemini');
        $this->provider = match($providerName) {
            'gemini' => new GeminiImageProvider(),
            default => throw new \Exception("Provider immagini non supportato: {$providerName}")
        };
    }

    // Costruisce il prompt completo dai preset + override
    public function buildPrompt(array $image, array $projectDefaults): string

    // Genera N varianti per un item
    public function generateVariants(array $image, array $projectDefaults, int $count): array

    // Salva immagine generata su disco
    public function saveImage(string $imageData, int $imageId, int $variantNumber): string

    // Converti formato per export (PNG → WebP/JPEG)
    public function convertFormat(string $sourcePath, string $format, int $quality): string
}
```

---

## 4. Prompt Engineering

### Fashion try-on template

```
Using the product shown in the attached image, generate a professional
e-commerce photo of a {gender} model wearing/using this exact product.
The product must be faithfully reproduced with accurate colors, textures,
patterns and details — it must be clearly recognizable as the same item.
Setting: {background}. Photography style: {photo_style}.
The model should have a natural, confident pose suitable for e-commerce catalog.
No text, no watermarks, no logos, no overlays.
High resolution, commercial quality, clean composition.
```

### Home/Lifestyle staging template

```
Using the product shown in the attached image, generate a professional
interior design / lifestyle photo showing this exact product placed naturally
in a {environment} setting. The product must be faithfully reproduced with
accurate colors, proportions, materials and details — it must be clearly
recognizable as the same item. Photography style: {photo_style}.
Natural lighting, realistic perspective and shadows.
No text, no watermarks, no logos.
High resolution, commercial quality, inviting atmosphere.
```

### Variabili preset

| Variabile | Opzioni Fashion | Opzioni Home |
|-----------|----------------|--------------|
| `{gender}` | woman, man, neutral | — |
| `{background}` | white studio, urban street, lifestyle indoor, nature outdoor | — |
| `{environment}` | — | modern living room, rustic kitchen, minimal bedroom, contemporary office, garden/terrace |
| `{photo_style}` | professional catalog, editorial magazine, minimal clean | stessa lista |

### Custom prompt

Se l'utente inserisce istruzioni aggiuntive, vengono appese:

```
Additional user instructions: {custom_prompt}
```

### Nota sulla fedeltà

Il prompt enfatizza 3 volte la fedeltà al prodotto originale ("faithfully reproduced", "clearly recognizable", "accurate colors/textures"). Questo è intenzionale per massimizzare la probabilità che il modello AI preservi l'aspetto del prodotto. La review manuale resta obbligatoria.

---

## 5. Connector Extension

### Nuova interfaccia

```php
interface ImageCapableConnectorInterface extends ConnectorInterface {
    /**
     * Recupera prodotti con le loro immagini.
     * @return array [['id' => string, 'name' => string, 'sku' => string, 'url' => string, 'image_url' => string, 'category' => string, 'price' => string]]
     */
    public function fetchProductImages(string $entityType, int $limit = 100): array;

    /**
     * Carica un'immagine su un prodotto nel CMS.
     * @param array $meta ['alt' => string, 'position' => int, 'filename' => string]
     * @return array ['success' => bool, 'cms_image_id' => ?string, 'error' => ?string]
     */
    public function uploadImage(string $entityId, string $entityType, string $imagePath, array $meta): array;

    /**
     * Verifica se il connettore supporta upload immagini.
     */
    public function supportsImageUpload(): bool;
}
```

### Implementazione per CMS

| CMS | fetchProductImages | uploadImage | Note |
|-----|-------------------|-------------|------|
| WordPress | GET `/wp-json/wc/v3/products` (featured_image) | POST `/wp-json/wp/v2/media` + assign | Richiede WooCommerce |
| Shopify | GET `/products.json` (images[0]) | POST `/products/{id}/images.json` | Supporta position |
| PrestaShop | GET `/api/products` (id_default_image) | POST `/api/images/products/{id}` | Multipart upload |
| Magento | GET `/V1/products` (media_gallery) | POST `/V1/products/{sku}/media` | Base64 nel body |

### Push mode configurabile

Opzione nel settings progetto: `image_push_mode`
- `add_as_gallery` (default) — aggiunge come immagine aggiuntiva del prodotto
- `replace_main` — sostituisce l'immagine principale
- `add` — aggiunge senza posizionamento specifico

La UI mostra il pulsante "Push CMS" solo se il connettore implementa `ImageCapableConnectorInterface`.

---

## 6. Flusso Utente

### Step 1: Import

Pagina `/content-creator/projects/{id}/images/import` con 3 tab:

**Tab CMS:** il connettore fetcha i prodotti con immagini. L'utente seleziona quali importare. Durante l'import, le foto vengono scaricate in locale → status `source_acquired`. Se il download fallisce → status `pending` con errore.

**Tab CSV:** upload CSV con colonne: URL immagine, nome prodotto, SKU, categoria. Le immagini vengono scaricate da URL durante l'import.

**Tab Manuale:** form con upload file + campi nome, SKU, categoria.

In tutti i casi, l'utente può assegnare la categoria (fashion/home) per-item durante l'import. Il default viene dal preset progetto.

### Step 2: Genera immagini

Dalla lista immagini, click "Genera Immagini". SSE job tipo `image_generate`.

Per ogni item con status `source_acquired`:
1. Carica immagine sorgente
2. Risolvi preset (override item > default progetto)
3. Costruisci prompt dal template + preset
4. Chiama provider N volte (variants_count)
5. Salva ogni variante in `cc_image_variants`
6. `Database::reconnect()` dopo ogni chiamata
7. Status → `generated`
8. SSE event `item_completed` con thumbnail preview

Nessun limite batch artificiale. L'utente può annullare in qualsiasi momento. Job resume: riprende da dove si è interrotto.

Rate limiting: delay di 2 secondi tra ogni chiamata API per evitare 429 (Gemini ha quota per-minuto). Il delay è configurabile nel provider.

Error handling:
- Errori transitori (429, 500, 503) → retry con backoff esponenziale (2s, 5s — max 2 tentativi)
- Content policy violation → salva errore specifico, skip item, continua batch (NON retry)
- Errore download/format → status `error` con messaggio

### Step 3: Revisione e approvazione

**Lista** (`images/index.php`): tabella con thumbnail sorgente, nome, SKU, categoria, conteggio varianti (generate/approvate), status, azioni.

Tab per stato: Tutti, In attesa, Foto acquisita, Generate, Approvate, Pubblicate, Errore.

**Preview** (`images/preview.php`): foto sorgente + griglia varianti side-by-side. Per ogni variante: bottone approva/rifiuta. Preset editabili per-item (sovrascrivono il default). Campo prompt personalizzato. Opzioni: rigenera tutte, rigenera singola, scarica approvate.

**Bulk operations**: approva (solo items con almeno 1 variante selezionata nella preview), rifiuta, elimina. Operano su items selezionati con checkbox.

### Step 4: Export / Push CMS

**Export ZIP:** per <50 immagini approvate → generazione on-the-fly. Per >50 → job background, notifica quando pronto, download file pre-generato (cleanup dopo 24h).

Struttura ZIP:
```
{SKU}_{slug-prodotto}/
├── variante-1.webp
└── variante-2.webp
manifest.csv  (SKU, nome, URL prodotto, filename, dimensioni)
```

**Push CMS:** SSE job tipo `image_push`. Upload ogni variante approvata sul CMS secondo il push mode configurato. Progresso in tempo reale con annullamento.

---

## 7. Routes

```
# Lista e gestione immagini
GET    /content-creator/projects/{id}/images                    # Lista immagini
GET    /content-creator/projects/{id}/images/import             # Pagina import
POST   /content-creator/projects/{id}/images/import             # Esegui import
GET    /content-creator/projects/{id}/images/{imgId}            # Preview varianti

# Operazioni su varianti (variant_id nel POST body)
POST   /content-creator/projects/{id}/images/{imgId}/approve    # Approva variante (body: variant_id)
POST   /content-creator/projects/{id}/images/{imgId}/reject     # Rifiuta variante (body: variant_id)
POST   /content-creator/projects/{id}/images/{imgId}/regenerate # Rigenera (body: variant_id opzionale, se assente rigenera tutte)
POST   /content-creator/projects/{id}/images/approve-bulk       # Bulk approve items
POST   /content-creator/projects/{id}/images/delete-bulk        # Bulk delete items

# SSE generazione
POST   /content-creator/projects/{id}/images/start-generate-job
GET    /content-creator/projects/{id}/images/generate-stream
GET    /content-creator/projects/{id}/images/generate-job-status
POST   /content-creator/projects/{id}/images/cancel-job

# Export e Push
GET    /content-creator/projects/{id}/images/export/zip         # Download ZIP
POST   /content-creator/projects/{id}/images/start-push-job
GET    /content-creator/projects/{id}/images/push-stream
GET    /content-creator/projects/{id}/images/push-job-status
```

---

## 8. Storage

```
storage/images/
├── generated/                     # Varianti generate
│   └── {YYYY}/{MM}/
│       ├── {image_id}_v{N}_{timestamp}.png     # Originale generata
│       └── {image_id}_v{N}_{timestamp}.webp    # Convertita per export
└── sources/                       # Foto sorgente dal CMS/upload
    └── {YYYY}/{MM}/
        └── {image_id}_{timestamp}.{ext}
```

Conversione: generazione in PNG (massima qualità) → conversione WebP/JPEG per export/push via PHP GD (`imagecreatefrompng()` + `imagewebp()`). Formato e qualità configurabili (default: WebP 85%).

Cleanup cron: varianti rifiutate eliminate dopo 30 giorni. Sorgenti orfane (item eliminato) eliminate con l'item (CASCADE).

---

## 9. Costi Crediti

| Operazione | Crediti | Note |
|------------|---------|------|
| Import + download foto CMS | 0 | Nessuna AI coinvolta |
| Generazione immagine (per variante) | 2 | Costo API Gemini ~€0.02-0.04 |
| Rigenerazione singola variante | 2 | Stessa chiamata API |
| Export ZIP | 0 | Download locale |
| Push CMS immagini | 0 | Come per contenuti testo |

**Esempio:** 100 prodotti × 3 varianti = 600 crediti (~€6-12 costo API reale).

---

## 10. Settings module.json

Nuovo gruppo `image_config` (order: 3):

```json
{
    "image_provider": { "type": "select", "options": ["gemini"], "default": "gemini", "group": "image_config" },
    "image_model": { "type": "text", "default": "gemini-2.0-flash-exp", "group": "image_config" },
    "default_variants_count": { "type": "number", "default": 3, "min": 1, "max": 4, "group": "image_config" },
    "default_scene_type": { "type": "select", "options": ["fashion", "home"], "default": "fashion", "group": "image_config" },
    "default_gender": { "type": "select", "options": ["woman", "man", "neutral"], "default": "woman", "group": "image_config" },
    "default_background": { "type": "select", "options": ["studio_white", "urban", "lifestyle", "nature"], "default": "studio_white", "group": "image_config" },
    "default_environment": { "type": "select", "options": ["living_room", "kitchen", "bedroom", "office", "outdoor"], "default": "living_room", "group": "image_config" },
    "default_photo_style": { "type": "select", "options": ["professional", "editorial", "minimal"], "default": "professional", "group": "image_config" },
    "image_output_format": { "type": "select", "options": ["webp", "jpeg", "png"], "default": "webp", "group": "image_config" },
    "image_output_quality": { "type": "number", "default": 85, "min": 60, "max": 100, "group": "image_config" },
    "image_cleanup_days": { "type": "number", "default": 30, "group": "image_config" }
}
```

Nuovo costo nel gruppo `costs`:

```json
{
    "cost_image_generate": { "type": "number", "default": 2, "group": "costs" }
}
```

---

## 11. Limiti e Guardrail

| Limite | Valore | Motivazione |
|--------|--------|-------------|
| Varianti per prodotto | max 4 | Costo API e storage |
| Dimensione foto sorgente | max 10MB | Limite upload Gemini |
| Formati sorgente | PNG, JPG, WebP | Supportati da Gemini |
| Retry per errore transitorio | max 2 | 429, 500, 503 |
| Content policy violation | skip + errore | Non ritentare con stesso prompt |
| Retention varianti rifiutate | 30 giorni | Cleanup cron |
| Storage warning | 1GB per progetto | Notifica, non blocco |
| ZIP on-the-fly | max 50 immagini | Oltre → job background |

---

## 12. Proof of Concept (Task 0)

Prima di implementare l'infrastruttura completa, testare la qualità di Gemini con:

1. 5 prodotti fashion (abiti diversi, accessori, scarpe)
2. 5 prodotti home (mobili, oggetti decorativi, elettrodomestici)
3. Prompt template proposto
4. Valutare: fedeltà al prodotto, qualità fotografica, naturalezza del risultato

Se la qualità non è sufficiente → valutare provider alternativi (Fashn.ai per try-on, Stability AI per staging) prima di procedere con l'implementazione.

---

## 13. Fuori scope (v2)

- Link tra `cc_images` e `cc_urls` per correlazione testo-immagini stesso prodotto
- Tool di editing/cropping post-generazione
- Batch change preset (seleziona N items, cambia categoria a tutti)
- Provider specializzati (Fashn.ai, Kolors) per try-on di alta qualità
- CDN caching per immagini generate
- A/B comparison tool tra varianti
