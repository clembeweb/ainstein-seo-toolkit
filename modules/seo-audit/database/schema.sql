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

    source VARCHAR(20) DEFAULT 'crawler',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES sa_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (page_id) REFERENCES sa_pages(id) ON DELETE CASCADE,
    INDEX idx_project_category (project_id, category),
    INDEX idx_project_severity (project_id, severity),
    INDEX idx_project_type (project_id, issue_type)
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
-- PIANO D'AZIONE AI
-- =============================================

-- Piano d'Azione
CREATE TABLE IF NOT EXISTS sa_action_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    session_id INT NULL,

    -- Metriche piano
    total_pages INT DEFAULT 0,
    total_fixes INT DEFAULT 0,
    fixes_completed INT DEFAULT 0,
    health_current INT DEFAULT 0,
    health_expected INT DEFAULT 0,
    estimated_time_minutes INT DEFAULT 0,

    -- Stato
    status ENUM('generating', 'ready', 'in_progress', 'completed') DEFAULT 'generating',

    -- Meta
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES sa_projects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_project_session (project_id, session_id),
    INDEX idx_project_status (project_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fix per Pagina
CREATE TABLE IF NOT EXISTS sa_page_fixes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_id INT NOT NULL,
    project_id INT NOT NULL,
    page_id INT NOT NULL,
    issue_id INT NOT NULL,

    -- Fix generato
    fix_code TEXT NULL,
    fix_explanation TEXT NOT NULL,

    -- Metriche
    priority TINYINT DEFAULT 5,
    difficulty ENUM('facile', 'medio', 'difficile') DEFAULT 'medio',
    time_estimate_minutes INT DEFAULT 5,
    impact_points INT DEFAULT 1,
    step_order TINYINT DEFAULT 1,

    -- Stato
    is_completed BOOLEAN DEFAULT FALSE,
    completed_at TIMESTAMP NULL,

    -- Meta
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (plan_id) REFERENCES sa_action_plans(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES sa_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (page_id) REFERENCES sa_pages(id) ON DELETE CASCADE,
    FOREIGN KEY (issue_id) REFERENCES sa_issues(id) ON DELETE CASCADE,
    INDEX idx_plan_page (plan_id, page_id),
    INDEX idx_completed (plan_id, is_completed),
    INDEX idx_priority (plan_id, priority DESC)
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
