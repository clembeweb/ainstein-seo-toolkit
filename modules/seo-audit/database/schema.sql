-- =============================================
-- SEO AUDIT MODULE - Database Schema
-- Prefisso tabelle: sa_
-- =============================================

-- Progetti audit
CREATE TABLE IF NOT EXISTS sa_projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    base_url VARCHAR(500) NOT NULL,
    crawl_mode ENUM('sitemap', 'spider', 'both') DEFAULT 'both',
    max_pages INT DEFAULT 500,
    status ENUM('pending', 'crawling', 'analyzing', 'completed', 'failed') DEFAULT 'pending',
    pages_found INT DEFAULT 0,
    pages_crawled INT DEFAULT 0,
    issues_count INT DEFAULT 0,
    health_score INT DEFAULT NULL,

    -- GSC link
    gsc_connected BOOLEAN DEFAULT FALSE,
    gsc_property VARCHAR(500) NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_status (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pagine crawlate
CREATE TABLE IF NOT EXISTS sa_pages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    url VARCHAR(2000) NOT NULL,
    status_code INT,
    load_time_ms INT,
    content_length INT,

    -- Meta
    title VARCHAR(500),
    title_length INT,
    meta_description TEXT,
    meta_description_length INT,
    meta_robots VARCHAR(255),
    canonical_url VARCHAR(2000),

    -- OG Tags
    og_title VARCHAR(500),
    og_description TEXT,
    og_image VARCHAR(2000),

    -- Content
    h1_count INT DEFAULT 0,
    h1_texts JSON,
    h2_count INT DEFAULT 0,
    h3_count INT DEFAULT 0,
    h4_count INT DEFAULT 0,
    h5_count INT DEFAULT 0,
    h6_count INT DEFAULT 0,
    word_count INT DEFAULT 0,

    -- Images
    images_count INT DEFAULT 0,
    images_without_alt INT DEFAULT 0,
    images_data JSON,

    -- Links
    internal_links_count INT DEFAULT 0,
    external_links_count INT DEFAULT 0,
    broken_links_count INT DEFAULT 0,
    nofollow_links_count INT DEFAULT 0,
    links_data JSON,

    -- Technical
    has_schema BOOLEAN DEFAULT FALSE,
    schema_types JSON,
    hreflang_tags JSON,
    is_indexable BOOLEAN DEFAULT TRUE,
    indexability_reason VARCHAR(255),

    -- Raw data
    html_content LONGTEXT,
    crawled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES sa_projects(id) ON DELETE CASCADE,
    INDEX idx_project_url (project_id, url(255)),
    INDEX idx_project_status (project_id, status_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Issues rilevate
CREATE TABLE IF NOT EXISTS sa_issues (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    page_id INT NULL,
    category VARCHAR(50) NOT NULL,
    issue_type VARCHAR(100) NOT NULL,
    severity ENUM('critical', 'warning', 'notice', 'info') NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    affected_element TEXT,
    recommendation TEXT,

    -- Source: 'crawler' o 'gsc'
    source ENUM('crawler', 'gsc') DEFAULT 'crawler',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES sa_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (page_id) REFERENCES sa_pages(id) ON DELETE CASCADE,
    INDEX idx_project_category (project_id, category),
    INDEX idx_project_severity (project_id, severity),
    INDEX idx_project_type (project_id, issue_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Analisi AI
CREATE TABLE IF NOT EXISTS sa_ai_analyses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    type ENUM('overview', 'category') NOT NULL,
    category VARCHAR(50) NULL,
    content LONGTEXT NOT NULL,
    credits_used INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES sa_projects(id) ON DELETE CASCADE,
    INDEX idx_project_type (project_id, type),
    UNIQUE KEY unique_project_category (project_id, type, category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Configurazione robots.txt e sitemap
CREATE TABLE IF NOT EXISTS sa_site_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL UNIQUE,
    robots_txt TEXT,
    robots_allows JSON,
    robots_disallows JSON,
    sitemap_urls JSON,
    discovered_urls LONGTEXT,
    has_sitemap BOOLEAN DEFAULT FALSE,
    has_robots BOOLEAN DEFAULT FALSE,
    is_https BOOLEAN DEFAULT FALSE,
    ssl_valid BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES sa_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABELLE GOOGLE SEARCH CONSOLE
-- =============================================

-- Connessioni OAuth GSC (per progetto)
CREATE TABLE IF NOT EXISTS sa_gsc_connections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL UNIQUE,
    user_id INT NOT NULL,

    -- OAuth tokens (CRIPTATI con openssl_encrypt)
    access_token TEXT NOT NULL,
    refresh_token TEXT NOT NULL,
    token_expires_at TIMESTAMP NOT NULL,

    -- Proprietà selezionata
    property_url VARCHAR(500) NOT NULL,
    property_type ENUM('URL_PREFIX', 'DOMAIN') NOT NULL,

    -- Stato
    is_active BOOLEAN DEFAULT TRUE,
    last_sync_at TIMESTAMP NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES sa_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Performance data (query/pagine)
CREATE TABLE IF NOT EXISTS sa_gsc_performance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,

    -- Dimensioni
    date DATE NOT NULL,
    query VARCHAR(500) NULL,
    page VARCHAR(2000) NULL,
    device ENUM('DESKTOP', 'MOBILE', 'TABLET') NULL,
    country VARCHAR(10) NULL,

    -- Metriche
    clicks INT DEFAULT 0,
    impressions INT DEFAULT 0,
    ctr DECIMAL(5,4) DEFAULT 0,
    position DECIMAL(5,2) DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES sa_projects(id) ON DELETE CASCADE,
    INDEX idx_project_date (project_id, date),
    INDEX idx_project_page (project_id, page(255)),
    INDEX idx_project_query (project_id, query(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copertura indice
CREATE TABLE IF NOT EXISTS sa_gsc_coverage (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,

    url VARCHAR(2000) NOT NULL,

    -- Stato indicizzazione
    coverage_state ENUM(
        'SUBMITTED_AND_INDEXED',
        'DUPLICATE_WITHOUT_CANONICAL',
        'DUPLICATE_GOOGLE_CHOSE_DIFFERENT_CANONICAL',
        'NOT_FOUND_404',
        'SOFT_404',
        'REDIRECT',
        'BLOCKED_BY_ROBOTS_TXT',
        'BLOCKED_BY_TAG',
        'CRAWLED_NOT_INDEXED',
        'DISCOVERED_NOT_INDEXED',
        'OTHER'
    ) NOT NULL,

    -- Dettagli
    verdict ENUM('PASS', 'NEUTRAL', 'FAIL') NOT NULL,
    robots_txt_state VARCHAR(50),
    indexing_state VARCHAR(50),
    page_fetch_state VARCHAR(50),
    google_canonical VARCHAR(2000),
    user_canonical VARCHAR(2000),

    last_crawl_time TIMESTAMP NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES sa_projects(id) ON DELETE CASCADE,
    INDEX idx_project_state (project_id, coverage_state),
    INDEX idx_project_url (project_id, url(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Core Web Vitals
CREATE TABLE IF NOT EXISTS sa_gsc_core_web_vitals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,

    -- Tipo
    form_factor ENUM('PHONE', 'DESKTOP') NOT NULL,
    metric_type ENUM('LCP', 'INP', 'CLS') NOT NULL,

    -- Valori aggregati
    good_percent DECIMAL(5,2) DEFAULT 0,
    needs_improvement_percent DECIMAL(5,2) DEFAULT 0,
    poor_percent DECIMAL(5,2) DEFAULT 0,

    -- Percentile 75
    p75_value DECIMAL(10,3),
    p75_unit VARCHAR(20),

    -- Periodo
    date_range_start DATE,
    date_range_end DATE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES sa_projects(id) ON DELETE CASCADE,
    INDEX idx_project_metric (project_id, form_factor, metric_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mobile Usability Issues
CREATE TABLE IF NOT EXISTS sa_gsc_mobile_usability (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,

    url VARCHAR(2000) NOT NULL,

    -- Issue type
    issue_type ENUM(
        'MOBILE_FRIENDLY',
        'NOT_MOBILE_FRIENDLY',
        'USES_INCOMPATIBLE_PLUGINS',
        'CONFIGURE_VIEWPORT',
        'FIXED_WIDTH_VIEWPORT',
        'TEXT_TOO_SMALL_TO_READ',
        'CONTENT_WIDER_THAN_SCREEN',
        'CLICKABLE_ELEMENTS_TOO_CLOSE_TOGETHER'
    ) NOT NULL,

    severity ENUM('warning', 'critical') NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES sa_projects(id) ON DELETE CASCADE,
    INDEX idx_project_issue (project_id, issue_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sync log
CREATE TABLE IF NOT EXISTS sa_gsc_sync_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,

    sync_type ENUM('performance', 'coverage', 'cwv', 'mobile') NOT NULL,
    date_range_start DATE,
    date_range_end DATE,
    records_imported INT DEFAULT 0,
    status ENUM('success', 'failed', 'partial') NOT NULL,
    error_message TEXT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES sa_projects(id) ON DELETE CASCADE,
    INDEX idx_project_type (project_id, sync_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity logs
CREATE TABLE IF NOT EXISTS sa_activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    details JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES sa_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_project_action (project_id, action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
