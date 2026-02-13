-- Content Creator Module - Database Schema
-- Prefix: cc_ (content creator)
-- Genera contenuti completi di pagina (body HTML) con AI
-- Data: 2026-02-13 (v2 - pivot to page content)

-- 1. Progetti
CREATE TABLE IF NOT EXISTS cc_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    base_url VARCHAR(500) NULL,
    content_type ENUM('product','category','article','service','custom') NOT NULL DEFAULT 'product',
    language VARCHAR(10) DEFAULT 'it',
    tone VARCHAR(50) DEFAULT 'professionale',
    ai_settings JSON NULL COMMENT 'Min words, custom prompt overrides',
    connector_id INT NULL COMMENT 'Default CMS connector for this project',
    status ENUM('active','paused','archived') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. URL/Pagine da processare
CREATE TABLE IF NOT EXISTS cc_urls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    url VARCHAR(700) NOT NULL,
    slug VARCHAR(500) NULL,
    keyword VARCHAR(500) NULL COMMENT 'Keyword target principale',
    secondary_keywords JSON DEFAULT NULL COMMENT 'Keywords secondarie (da KR)',
    intent VARCHAR(50) DEFAULT NULL COMMENT 'Search intent (informational/commercial/transactional)',
    source_type ENUM('manual','csv','sitemap','cms','keyword_research') DEFAULT 'manual',
    category VARCHAR(255) NULL COMMENT 'Categoria (da CSV, CMS o KR)',

    -- Dati scraping (opzionale, per contesto)
    scraped_title VARCHAR(500) NULL,
    scraped_h1 VARCHAR(500) NULL,
    scraped_meta_title VARCHAR(500) NULL,
    scraped_meta_description TEXT NULL,
    scraped_content LONGTEXT NULL,
    scraped_price VARCHAR(50) NULL,
    scraped_word_count INT NULL,
    scraped_at DATETIME NULL,
    scrape_error TEXT NULL,

    -- Contenuto generato AI (body HTML completo)
    ai_content LONGTEXT NULL COMMENT 'Contenuto HTML completo della pagina',
    ai_h1 VARCHAR(500) NULL COMMENT 'H1 generato',
    ai_word_count INT DEFAULT 0 COMMENT 'Conteggio parole contenuto generato',
    ai_generated_at DATETIME NULL,
    ai_error TEXT NULL,

    -- Status unificato
    status ENUM('pending','scraped','generated','approved','rejected','published','error') DEFAULT 'pending',

    -- CMS sync
    connector_id INT NULL,
    cms_entity_id VARCHAR(255) NULL COMMENT 'Product/page/category ID in CMS',
    cms_entity_type VARCHAR(50) NULL COMMENT 'product/category/page',
    cms_synced_at DATETIME NULL,
    cms_sync_error TEXT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES cc_projects(id) ON DELETE CASCADE,
    UNIQUE KEY uk_project_url (project_id, url(500)),
    INDEX idx_project (project_id),
    INDEX idx_status (status),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Connettori CMS
CREATE TABLE IF NOT EXISTS cc_connectors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    type ENUM('wordpress','shopify','prestashop','magento','custom_api') NOT NULL,
    config JSON NOT NULL COMMENT 'Credenziali e endpoint specifici per tipo',
    api_key VARCHAR(100) DEFAULT NULL COMMENT 'API key dal plugin installato sul CMS',
    is_active BOOLEAN DEFAULT TRUE,
    last_test_at DATETIME NULL,
    last_test_status ENUM('success','error') NULL,
    last_sync_at DATETIME NULL,
    categories_cache JSON DEFAULT NULL COMMENT 'Cache categorie CMS',
    seo_plugin VARCHAR(50) DEFAULT NULL COMMENT 'Plugin SEO rilevato (yoast/rankmath/aioseo/none)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Log operazioni
CREATE TABLE IF NOT EXISTS cc_operations_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    project_id INT NOT NULL,
    url_id INT NULL,
    operation ENUM('scrape','ai_generate','cms_push','export') NOT NULL,
    credits_used DECIMAL(8,2) NOT NULL DEFAULT 0,
    status ENUM('success','error') NOT NULL,
    details JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_project (project_id),
    INDEX idx_operation (operation),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Background jobs (SSE tracking)
CREATE TABLE IF NOT EXISTS cc_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    type ENUM('scrape','generate','cms_push') NOT NULL DEFAULT 'scrape',
    status ENUM('pending','running','completed','error','cancelled') DEFAULT 'pending',
    items_requested INT DEFAULT 0,
    items_completed INT DEFAULT 0,
    items_failed INT DEFAULT 0,
    credits_used DECIMAL(8,2) DEFAULT 0,
    current_item VARCHAR(700) DEFAULT NULL,
    current_item_id INT DEFAULT NULL,
    error_message TEXT NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES cc_projects(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_project (project_id),
    INDEX idx_user (user_id),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
