-- =============================================
-- CRAWL BUDGET OPTIMIZER - Database Schema
-- Prefisso tabelle: cb_
-- =============================================

CREATE TABLE IF NOT EXISTS cb_projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    global_project_id INT NULL,
    name VARCHAR(255) NOT NULL,
    domain VARCHAR(255) NOT NULL,
    status ENUM('idle','pending','crawling','completed','failed') DEFAULT 'idle',
    last_crawl_at DATETIME NULL,
    crawl_budget_score INT NULL,
    settings JSON,
    current_session_id INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (global_project_id) REFERENCES projects(id) ON DELETE SET NULL,
    INDEX idx_user_status (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cb_crawl_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    status ENUM('pending','running','paused','stopping','stopped','completed','failed') DEFAULT 'pending',
    pages_found INT DEFAULT 0,
    pages_crawled INT DEFAULT 0,
    issues_found INT DEFAULT 0,
    current_url VARCHAR(2048) NULL,
    config JSON,
    started_at DATETIME NULL,
    completed_at DATETIME NULL,
    error_message TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES cb_projects(id) ON DELETE CASCADE,
    INDEX idx_project_status (project_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cb_crawl_jobs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    session_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('pending','running','completed','error','cancelled') DEFAULT 'pending',
    items_total INT DEFAULT 0,
    items_completed INT DEFAULT 0,
    items_failed INT DEFAULT 0,
    current_item VARCHAR(2048) NULL,
    config JSON,
    error_message TEXT NULL,
    started_at DATETIME NULL,
    completed_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES cb_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES cb_crawl_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_project_session (project_id, session_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cb_pages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    session_id INT NOT NULL,
    url VARCHAR(2048) NOT NULL,
    status ENUM('pending','crawling','crawled','error') DEFAULT 'pending',
    http_status SMALLINT NULL,
    content_type VARCHAR(128) NULL,
    response_time_ms INT NULL,
    content_length INT NULL,
    word_count INT NULL,
    title VARCHAR(512) NULL,
    meta_robots VARCHAR(255) NULL,
    canonical_url VARCHAR(2048) NULL,
    canonical_matches TINYINT(1) DEFAULT 0,
    is_indexable TINYINT(1) DEFAULT 1,
    indexability_reason VARCHAR(255) NULL,
    redirect_target VARCHAR(2048) NULL,
    redirect_chain JSON NULL,
    redirect_hops TINYINT DEFAULT 0,
    in_sitemap TINYINT(1) DEFAULT 0,
    in_robots_allowed TINYINT(1) DEFAULT 1,
    internal_links_in INT DEFAULT 0,
    internal_links_out INT DEFAULT 0,
    has_parameters TINYINT(1) DEFAULT 0,
    depth TINYINT DEFAULT 0,
    discovered_from VARCHAR(2048) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES cb_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES cb_crawl_sessions(id) ON DELETE CASCADE,
    INDEX idx_project_session_status (project_id, session_id, status),
    INDEX idx_session_status (session_id, status),
    INDEX idx_session_http_status (session_id, http_status),
    UNIQUE KEY uk_session_url (session_id, url(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cb_issues (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    session_id INT NOT NULL,
    page_id INT NULL,
    category ENUM('redirect','waste','indexability') NOT NULL,
    type VARCHAR(100) NOT NULL,
    severity ENUM('critical','warning','notice') NOT NULL,
    title VARCHAR(255) NOT NULL,
    details JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES cb_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES cb_crawl_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (page_id) REFERENCES cb_pages(id) ON DELETE CASCADE,
    INDEX idx_session_category_severity (session_id, category, severity),
    INDEX idx_page (page_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cb_site_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL UNIQUE,
    robots_txt TEXT NULL,
    robots_rules JSON NULL,
    sitemaps JSON NULL,
    sitemap_urls JSON NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES cb_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cb_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    session_id INT NOT NULL,
    ai_response LONGTEXT,
    summary TEXT NULL,
    priority_actions JSON NULL,
    estimated_impact JSON NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES cb_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES cb_crawl_sessions(id) ON DELETE CASCADE,
    INDEX idx_project_session (project_id, session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
