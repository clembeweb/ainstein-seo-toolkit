# AI Content Bulk Creator - Specifiche Tecniche

## OVERVIEW

Modulo per generazione massiva di contenuti SEO (Meta Title, Meta Description, Page Description) tramite AI per e-commerce.

---

## DATABASE SCHEMA

```sql
-- Progetti Content Creator
CREATE TABLE cc_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    base_url VARCHAR(500) NULL,
    content_type ENUM('product','category','article','custom') NOT NULL DEFAULT 'product',
    css_selector VARCHAR(255) NULL COMMENT 'Selettore per estrazione contenuto',
    ai_settings JSON NULL COMMENT 'Impostazioni AI: lunghezze, tono, lingua',
    status ENUM('active','paused','archived') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- URL da processare
CREATE TABLE cc_urls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    url VARCHAR(700) NOT NULL,
    slug VARCHAR(500) NULL COMMENT 'Estratto automaticamente da URL',
    
    -- Dati scraping
    scraped_title VARCHAR(500) NULL,
    scraped_h1 VARCHAR(500) NULL,
    scraped_content LONGTEXT NULL COMMENT 'Contenuto estratto da css_selector',
    scraped_price VARCHAR(50) NULL,
    scraped_meta_title VARCHAR(500) NULL,
    scraped_meta_description TEXT NULL,
    scraped_at DATETIME NULL,
    scrape_status ENUM('pending','completed','error') DEFAULT 'pending',
    scrape_error TEXT NULL,
    
    -- Contenuti generati AI
    ai_meta_title VARCHAR(500) NULL,
    ai_meta_description TEXT NULL,
    ai_page_description LONGTEXT NULL,
    ai_generated_at DATETIME NULL,
    ai_status ENUM('pending','generated','error','approved','rejected') DEFAULT 'pending',
    ai_error TEXT NULL,
    
    -- Sync CMS
    cms_synced_at DATETIME NULL,
    cms_sync_status ENUM('pending','synced','error') DEFAULT 'pending',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_project (project_id),
    INDEX idx_scrape_status (scrape_status),
    INDEX idx_ai_status (ai_status),
    FOREIGN KEY (project_id) REFERENCES cc_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Connettori CMS
CREATE TABLE cc_connectors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    type ENUM('wordpress','shopify','prestashop','magento','custom_api') NOT NULL,
    config JSON NOT NULL COMMENT 'Credenziali e endpoint',
    is_active BOOLEAN DEFAULT TRUE,
    last_sync_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_type (type)
) ENGINE=InnoDB;

-- Log operazioni per tracking crediti
CREATE TABLE cc_operations_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    project_id INT NOT NULL,
    url_id INT NULL,
    operation ENUM('scrape','ai_generate','cms_sync') NOT NULL,
    credits_used INT NOT NULL DEFAULT 0,
    status ENUM('success','error') NOT NULL,
    details JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_project (project_id),
    INDEX idx_operation (operation)
) ENGINE=InnoDB;
```

---

## STRUTTURA MODULO

```
modules/content-creator/
├── module.json
├── routes.php
├── controllers/
│   ├── ProjectController.php
│   ├── UrlController.php
│   ├── GeneratorController.php
│   └── ConnectorController.php
├── models/
│   ├── Project.php
│   ├── Url.php
│   ├── Connector.php
│   └── OperationLog.php
├── services/
│   ├── ContentScraper.php
│   ├── AiContentGenerator.php
│   ├── SlugExtractor.php
│   └── connectors/
│       ├── WordPressConnector.php
│       ├── ShopifyConnector.php
│       └── ConnectorInterface.php
├── views/
│   ├── projects/
│   │   ├── index.php
│   │   ├── create.php
│   │   └── show.php
│   ├── urls/
│   │   ├── index.php
│   │   ├── import.php
│   │   └── preview.php
│   ├── generator/
│   │   ├── index.php
│   │   ├── settings.php
│   │   └── results.php
│   └── connectors/
│       ├── index.php
│       └── configure.php
└── assets/
    ├── js/
    │   └── content-creator.js
    └── css/
        └── content-creator.css
```

---

## module.json

```json
{
    "name": "AI Content Bulk Creator",
    "slug": "content-creator",
    "version": "1.0.0",
    "description": "Genera massivamente contenuti SEO con AI per e-commerce",
    "icon": "file-text",
    "menu_order": 20,
    "requires": {
        "php": ">=8.0",
        "credits": true,
        "services": ["ai", "scraper"]
    },
    "credits": {
        "scrape_url": 1,
        "ai_generate": 2
    }
}
```

---

## FLOW OPERATIVO

### 1. Creazione Progetto
```
User → Crea progetto → Imposta:
  - Nome progetto
  - Content Type (product/category/article/custom)
  - Base URL (opzionale, per validazione)
  - CSS Selector (default per tipo)
  - AI Settings:
    - Lingua output
    - Tono (professionale/informale/tecnico)
    - Meta Title: min/max chars (default 50-60)
    - Meta Description: min/max chars (default 140-160)
    - Page Description: min chars (default 300)
```

### 2. Import URL
```
Tre modalità:
A) Upload CSV
   - Colonne: url (required), keyword (optional), category (optional)
   - Parsing + validazione URL
   - Estrazione automatica slug da URL

B) Import da Sitemap
   - URL sitemap.xml
   - Filtro pattern URL (es. /product/*)
   - Limit max URL

C) Import da CMS (se connettore configurato)
   - Fetch lista prodotti/categorie da WordPress/Shopify
   - Filtro per categoria/stato
   - Sincronizzazione bidirezionale
```

### 3. Scraping
```
Per ogni URL selezionata:
1. Fetch HTML pagina
2. Estrai:
   - <title>
   - <h1>
   - meta[name="description"]
   - Contenuto da css_selector
   - Prezzo (se product, pattern €/$/£)
3. Salva in cc_urls
4. Scala 1 credito
5. Update status
```

### 4. Generazione AI
```
Per ogni URL selezionata (con scrape completato):
1. Costruisci prompt con:
   - Tipo contenuto
   - Slug
   - Dati scrapati (title, h1, content, price)
   - Keyword (se presente)
   - Impostazioni lunghezza
2. Chiama AI Service
3. Parse response (meta_title, meta_description, page_description)
4. Salva in cc_urls
5. Scala 2 crediti
6. Update status
```

### 5. Review & Export
```
User può:
- Preview risultati generati
- Edit manuale singolo contenuto
- Approve/Reject singolo o bulk
- Rigenerare (consuma altri 2 crediti)
- Export CSV
- Push a CMS (se connettore configurato)
```

---

## AI PROMPT TEMPLATE

```php
$promptTemplate = <<<PROMPT
Sei un esperto SEO copywriter. Genera contenuti ottimizzati per un {content_type} e-commerce.

DATI PAGINA:
- URL: {url}
- Slug: {slug}
- Titolo attuale: {scraped_title}
- H1: {scraped_h1}
- Prezzo: {scraped_price}
- Contenuto esistente: {scraped_content}
- Keyword target: {keyword}

REQUISITI:
- Lingua: {language}
- Tono: {tone}
- Meta Title: {meta_title_min}-{meta_title_max} caratteri
- Meta Description: {meta_desc_min}-{meta_desc_max} caratteri
- Page Description: minimo {page_desc_min} caratteri

ISTRUZIONI:
1. Meta Title: includi keyword principale, brand se rilevante, max 60 chars per evitare troncamento SERP
2. Meta Description: call-to-action, benefit principale, keyword naturale
3. Page Description: testo SEO-friendly, informativo, con keyword semantiche correlate

OUTPUT FORMAT (JSON):
{
    "meta_title": "...",
    "meta_description": "...",
    "page_description": "..."
}
PROMPT;
```

---

## API ENDPOINTS

```php
// routes.php
$router->group('/content-creator', function($router) {
    
    // Progetti
    $router->get('/', 'ProjectController@index');
    $router->get('/create', 'ProjectController@create');
    $router->post('/store', 'ProjectController@store');
    $router->get('/{id}', 'ProjectController@show');
    $router->post('/{id}/settings', 'ProjectController@updateSettings');
    $router->delete('/{id}', 'ProjectController@delete');
    
    // URL
    $router->get('/{id}/urls', 'UrlController@index');
    $router->get('/{id}/urls/import', 'UrlController@import');
    $router->post('/{id}/urls/import/csv', 'UrlController@importCsv');
    $router->post('/{id}/urls/import/sitemap', 'UrlController@importSitemap');
    $router->post('/{id}/urls/import/cms', 'UrlController@importFromCms');
    $router->delete('/{id}/urls/{urlId}', 'UrlController@delete');
    $router->post('/{id}/urls/bulk-delete', 'UrlController@bulkDelete');
    
    // Scraping
    $router->post('/{id}/scrape', 'GeneratorController@scrape');
    $router->post('/{id}/scrape/selected', 'GeneratorController@scrapeSelected');
    
    // AI Generation
    $router->get('/{id}/generate', 'GeneratorController@index');
    $router->post('/{id}/generate', 'GeneratorController@generate');
    $router->post('/{id}/generate/selected', 'GeneratorController@generateSelected');
    $router->post('/{id}/regenerate/{urlId}', 'GeneratorController@regenerate');
    
    // Review & Actions
    $router->get('/{id}/results', 'GeneratorController@results');
    $router->post('/{id}/urls/{urlId}/approve', 'UrlController@approve');
    $router->post('/{id}/urls/{urlId}/reject', 'UrlController@reject');
    $router->post('/{id}/urls/{urlId}/edit', 'UrlController@editContent');
    $router->post('/{id}/bulk-approve', 'UrlController@bulkApprove');
    
    // Export
    $router->get('/{id}/export', 'GeneratorController@export');
    $router->post('/{id}/export/csv', 'GeneratorController@exportCsv');
    $router->post('/{id}/push-cms', 'GeneratorController@pushToCms');
    
    // Connectors
    $router->get('/connectors', 'ConnectorController@index');
    $router->get('/connectors/create', 'ConnectorController@create');
    $router->post('/connectors/store', 'ConnectorController@store');
    $router->post('/connectors/{id}/test', 'ConnectorController@test');
    $router->delete('/connectors/{id}', 'ConnectorController@delete');
});

// API Ajax
$router->group('/api/content-creator', function($router) {
    $router->post('/scrape-single', 'GeneratorController@scrapeSingle');
    $router->post('/generate-single', 'GeneratorController@generateSingle');
    $router->get('/progress/{jobId}', 'GeneratorController@progress');
    $router->post('/preview-selector', 'UrlController@previewSelector');
});
```

---

## WORDPRESS CONNECTOR

```php
// services/connectors/WordPressConnector.php

class WordPressConnector implements ConnectorInterface
{
    private string $siteUrl;
    private string $username;
    private string $appPassword;
    
    public function __construct(array $config)
    {
        $this->siteUrl = rtrim($config['site_url'], '/');
        $this->username = $config['username'];
        $this->appPassword = $config['app_password'];
    }
    
    /**
     * Test connessione
     */
    public function test(): bool
    {
        $response = $this->request('GET', '/wp-json/wp/v2/users/me');
        return isset($response['id']);
    }
    
    /**
     * Fetch prodotti WooCommerce
     */
    public function fetchProducts(array $filters = []): array
    {
        $params = [
            'per_page' => $filters['limit'] ?? 100,
            'status' => $filters['status'] ?? 'publish',
        ];
        
        if (!empty($filters['category'])) {
            $params['category'] = $filters['category'];
        }
        
        return $this->request('GET', '/wp-json/wc/v3/products', $params);
    }
    
    /**
     * Fetch categorie WooCommerce
     */
    public function fetchCategories(array $filters = []): array
    {
        $params = [
            'per_page' => $filters['limit'] ?? 100,
        ];
        
        return $this->request('GET', '/wp-json/wc/v3/products/categories', $params);
    }
    
    /**
     * Update prodotto con nuovi meta
     */
    public function updateProduct(int $productId, array $data): array
    {
        $payload = [];
        
        if (isset($data['meta_title'])) {
            $payload['meta_data'][] = [
                'key' => '_yoast_wpseo_title',
                'value' => $data['meta_title']
            ];
        }
        
        if (isset($data['meta_description'])) {
            $payload['meta_data'][] = [
                'key' => '_yoast_wpseo_metadesc',
                'value' => $data['meta_description']
            ];
        }
        
        if (isset($data['page_description'])) {
            $payload['description'] = $data['page_description'];
        }
        
        return $this->request('PUT', "/wp-json/wc/v3/products/{$productId}", $payload);
    }
    
    /**
     * Update categoria con nuovi meta
     */
    public function updateCategory(int $categoryId, array $data): array
    {
        $payload = [];
        
        if (isset($data['page_description'])) {
            $payload['description'] = $data['page_description'];
        }
        
        // Yoast meta per categorie
        if (isset($data['meta_title']) || isset($data['meta_description'])) {
            // Richiede endpoint custom o update term_meta diretto
        }
        
        return $this->request('PUT', "/wp-json/wc/v3/products/categories/{$categoryId}", $payload);
    }
    
    private function request(string $method, string $endpoint, array $data = []): array
    {
        $url = $this->siteUrl . $endpoint;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $method === 'GET' && $data ? $url . '?' . http_build_query($data) : $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($this->username . ':' . $this->appPassword)
            ],
        ]);
        
        if ($method !== 'GET' && $data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true) ?? [];
    }
}
```

---

## UI - VISTE PRINCIPALI

### Dashboard Progetto (views/projects/show.php)
```
┌─────────────────────────────────────────────────────────────┐
│ [Breadcrumb] Content Creator > Progetto XYZ                 │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐        │
│  │   125   │  │    98   │  │    45   │  │    23   │        │
│  │ URL Tot │  │ Scraped │  │Generated│  │Approved │        │
│  └─────────┘  └─────────┘  └─────────┘  └─────────┘        │
│                                                             │
│  [Import URL ▼]  [Scrape Selected]  [Generate AI]  [Export]│
│                                                             │
│  ┌─────────────────────────────────────────────────────────┐│
│  │☑│ URL           │ Status   │ Meta Title │ Actions      ││
│  ├─┼───────────────┼──────────┼────────────┼──────────────┤│
│  │☑│ /product/xyz  │ ✓ Ready  │ Titolo... │ 👁 ✏️ 🔄 ✓ ✗  ││
│  │☐│ /category/abc │ ⏳ Pending│ -         │ 👁 ✏️         ││
│  └─────────────────────────────────────────────────────────┘│
│                                                             │
│  [Select All] [Deselect All]    Showing 1-25 of 125  [< >] │
└─────────────────────────────────────────────────────────────┘
```

### Import URL (views/urls/import.php)
```
┌─────────────────────────────────────────────────────────────┐
│ Import URL                                          [Close] │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────┐  ┌─────────┐  ┌─────────┐                     │
│  │   CSV   │  │ Sitemap │  │   CMS   │  ← Tab selection    │
│  └─────────┘  └─────────┘  └─────────┘                     │
│                                                             │
│  ─────────── CSV Upload ───────────                        │
│                                                             │
│  [Drag & Drop CSV or Click to Upload]                      │
│                                                             │
│  Formato richiesto:                                        │
│  url (required), keyword (optional), category (optional)   │
│                                                             │
│  [Download Template CSV]                                    │
│                                                             │
│                                    [Cancel] [Import →]     │
└─────────────────────────────────────────────────────────────┘
```

### AI Settings (views/generator/settings.php)
```
┌─────────────────────────────────────────────────────────────┐
│ Impostazioni Generazione AI                                 │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Tipo Contenuto: [Product ▼]                               │
│                                                             │
│  Lingua Output:  [Italiano ▼]                              │
│                                                             │
│  Tono:          ○ Professionale                            │
│                 ● Informativo                               │
│                 ○ Tecnico                                   │
│                 ○ Commerciale                               │
│                                                             │
│  ─────────── Lunghezze ───────────                         │
│                                                             │
│  Meta Title:       Min [50] - Max [60] caratteri           │
│  Meta Description: Min [140] - Max [160] caratteri         │
│  Page Description: Min [300] caratteri                      │
│                                                             │
│  ─────────── CSS Selector ───────────                      │
│                                                             │
│  Selettore: [.product-description    ]                     │
│  [Test Selector]  Preview: "Lorem ipsum dolor..."          │
│                                                             │
│                              [Cancel] [Save Settings]      │
└─────────────────────────────────────────────────────────────┘
```

---

## CREDITI INTEGRATION

```php
// Nel GeneratorController

public function scrapeSelected(Request $request, int $projectId)
{
    $urlIds = $request->input('url_ids', []);
    $urlCount = count($urlIds);
    
    // Verifica crediti disponibili
    $creditsNeeded = $urlCount * 1; // 1 credito per scrape
    if (!$this->creditService->hasEnough($creditsNeeded)) {
        return $this->error('Crediti insufficienti. Necessari: ' . $creditsNeeded);
    }
    
    // Avvia job scraping
    $jobId = $this->queueService->dispatch('scrape', [
        'project_id' => $projectId,
        'url_ids' => $urlIds,
        'user_id' => auth()->id()
    ]);
    
    return $this->json(['job_id' => $jobId, 'credits_reserved' => $creditsNeeded]);
}

public function generateSelected(Request $request, int $projectId)
{
    $urlIds = $request->input('url_ids', []);
    $urlCount = count($urlIds);
    
    // Verifica crediti disponibili
    $creditsNeeded = $urlCount * 2; // 2 crediti per generazione
    if (!$this->creditService->hasEnough($creditsNeeded)) {
        return $this->error('Crediti insufficienti. Necessari: ' . $creditsNeeded);
    }
    
    // Avvia job generazione
    $jobId = $this->queueService->dispatch('ai_generate', [
        'project_id' => $projectId,
        'url_ids' => $urlIds,
        'user_id' => auth()->id()
    ]);
    
    return $this->json(['job_id' => $jobId, 'credits_reserved' => $creditsNeeded]);
}
```

---

## COMANDI SVILUPPO CLAUDE CODE

```bash
# Step 1 - Crea struttura modulo
Crea il modulo content-creator in C:\laragon\www\seo-toolkit\modules\content-creator\ seguendo la struttura specificata. Inizia da module.json e routes.php.

# Step 2 - Database
Esegui le query SQL per creare le tabelle cc_projects, cc_urls, cc_connectors, cc_operations_log nel database seo_toolkit.

# Step 3 - Models
Crea i Models: Project.php, Url.php, Connector.php con CRUD base e relazioni.

# Step 4 - Controllers base
Crea ProjectController con index, create, store, show, delete.

# Step 5 - Views progetti
Crea le views per gestione progetti: index.php, create.php, show.php.

# Step 6 - Import URL
Implementa UrlController con import CSV, sitemap, lista manuale. Crea views import.

# Step 7 - Scraper
Implementa ContentScraper service e integra in GeneratorController.

# Step 8 - AI Generator
Implementa AiContentGenerator service con prompt template e parsing response.

# Step 9 - Results & Export
Crea views results, implementa export CSV.

# Step 10 - WordPress Connector
Implementa WordPressConnector per import/export da WooCommerce.
```

---

## CHECKLIST COMPLETAMENTO

- [ ] module.json configurato
- [ ] Tabelle DB create
- [ ] CRUD Progetti funzionante
- [ ] Import CSV funzionante
- [ ] Import Sitemap funzionante
- [ ] Scraper con progress real-time
- [ ] AI Generation con progress
- [ ] Preview/Edit contenuti generati
- [ ] Approve/Reject workflow
- [ ] Export CSV
- [ ] WordPress Connector base
- [ ] Integrazione crediti
- [ ] Test end-to-end
