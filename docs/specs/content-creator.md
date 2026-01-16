# AI CONTENT BULK CREATOR - Specifiche Tecniche

## Overview

| Aspetto | Dettaglio |
|---------|-----------|
| **Slug** | `content-creator` |
| **Prefisso DB** | `cc_` |
| **Files** | 0 |
| **Stato** | ❌ Da implementare |
| **Ultimo update** | 2026-01-02 |

Modulo per generazione massiva di contenuti SEO (Meta Title, Meta Description, Page Description) tramite AI per e-commerce.

---

## Architettura Prevista

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
│       └── ConnectorInterface.php
└── views/
    ├── projects/
    ├── urls/
    ├── generator/
    └── connectors/
```

---

## Database Schema

```sql
-- Progetti
CREATE TABLE cc_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    base_url VARCHAR(500) NULL,
    content_type ENUM('product','category','article','custom') DEFAULT 'product',
    css_selector VARCHAR(255) NULL,
    ai_settings JSON NULL,
    status ENUM('active','paused','archived') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- URL da processare
CREATE TABLE cc_urls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    url VARCHAR(700) NOT NULL,
    slug VARCHAR(500) NULL,
    
    -- Dati scraping
    scraped_title VARCHAR(500) NULL,
    scraped_h1 VARCHAR(500) NULL,
    scraped_content LONGTEXT NULL,
    scraped_at DATETIME NULL,
    scrape_status ENUM('pending','completed','error') DEFAULT 'pending',
    
    -- Contenuti generati AI
    ai_meta_title VARCHAR(500) NULL,
    ai_meta_description TEXT NULL,
    ai_page_description LONGTEXT NULL,
    ai_status ENUM('pending','generated','approved','rejected') DEFAULT 'pending',
    
    -- Sync CMS
    cms_synced_at DATETIME NULL,
    cms_sync_status ENUM('pending','synced','error') DEFAULT 'pending',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES cc_projects(id) ON DELETE CASCADE
);

-- Connettori CMS
CREATE TABLE cc_connectors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    type ENUM('wordpress','shopify','prestashop','custom_api') NOT NULL,
    config JSON NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Log operazioni
CREATE TABLE cc_operations_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    project_id INT NOT NULL,
    operation ENUM('scrape','ai_generate','cms_sync') NOT NULL,
    credits_used INT DEFAULT 0,
    status ENUM('success','error') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## Flow Operativo

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   IMPORT    │ ──▶ │  SCRAPING   │ ──▶ │  AI GEN     │ ──▶ │   REVIEW    │
│    URL      │     │  CONTENUTI  │     │  CONTENUTI  │     │  + EXPORT   │
└─────────────┘     └─────────────┘     └─────────────┘     └─────────────┘
```

### Import URL (3 modalità)
- CSV upload (url, keyword, category)
- Sitemap XML con filtro pattern
- Import da CMS (WordPress/WooCommerce)

### Scraping
- Estrazione: title, H1, meta description, contenuto (css_selector)
- Prezzo (se product)
- 1 credito per URL

### AI Generation
- Meta Title (50-60 chars)
- Meta Description (140-160 chars)
- Page Description (min 300 chars)
- 2 crediti per URL

### Review & Export
- Preview risultati
- Edit manuale
- Approve/Reject
- Export CSV
- Push a CMS

---

## Crediti

| Azione | Costo |
|--------|-------|
| Scrape URL | 1 |
| AI generate | 2 |

---

## Routes Previste

```php
// Progetti
GET  /content-creator                              # Lista
GET  /content-creator/projects/create              # Form
GET  /content-creator/projects/{id}                # Dashboard

// URL
GET  /content-creator/projects/{id}/urls/import    # Form import
POST /content-creator/projects/{id}/urls/import/csv
POST /content-creator/projects/{id}/urls/import/sitemap

// Scraping & Generation
POST /content-creator/projects/{id}/scrape
POST /content-creator/projects/{id}/generate
GET  /content-creator/projects/{id}/results

// Export
GET  /content-creator/projects/{id}/export/csv
POST /content-creator/projects/{id}/push-cms

// Connectors
GET  /content-creator/connectors
POST /content-creator/connectors/store
POST /content-creator/connectors/{id}/test
```

---

## AI Prompt Template

```
Sei un esperto SEO copywriter. Genera contenuti ottimizzati per un {content_type}.

DATI PAGINA:
- URL: {url}
- Slug: {slug}
- Titolo attuale: {scraped_title}
- H1: {scraped_h1}
- Contenuto: {scraped_content}
- Keyword target: {keyword}

REQUISITI:
- Lingua: {language}
- Tono: {tone}
- Meta Title: {min}-{max} caratteri
- Meta Description: {min}-{max} caratteri

OUTPUT JSON:
{
    "meta_title": "...",
    "meta_description": "...",
    "page_description": "..."
}
```

---

## WordPress Connector

```php
class WordPressConnector implements ConnectorInterface
{
    // Autenticazione: Application Passwords
    
    public function fetchProducts(array $filters): array;
    public function fetchCategories(array $filters): array;
    public function updateProduct(int $productId, array $data): array;
    public function updateCategory(int $categoryId, array $data): array;
}
```

---

## Checklist Implementazione

- [ ] Database schema (4 tabelle)
- [ ] Models CRUD
- [ ] ProjectController
- [ ] UrlController + import
- [ ] GeneratorController + AI
- [ ] Views complete
- [ ] WordPress Connector
- [ ] Export CSV
- [ ] Test end-to-end

---

## Note Implementazione

1. **AiContentGenerator** deve usare AiService centralizzato
2. **Scraper** deve usare ScraperService condiviso
3. **Progress** real-time con SSE per operazioni bulk
4. **Yoast SEO** meta keys: `_yoast_wpseo_title`, `_yoast_wpseo_metadesc`

---

*Spec pronta per implementazione - 2026-01-02*
