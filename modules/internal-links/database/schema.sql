-- Internal Links Module - Database Schema
-- Prefix: il_ (internal links)
-- Multi-tenant: user_id on all tables

USE seo_toolkit;

-- ============================================
-- IL_PROJECTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS il_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    base_url VARCHAR(500) NOT NULL,
    css_selector VARCHAR(255) NULL DEFAULT NULL,
    html_block_regex VARCHAR(500) NULL DEFAULT NULL,
    scrape_delay INT DEFAULT 1000 COMMENT 'Milliseconds between requests',
    user_agent VARCHAR(500) DEFAULT 'Mozilla/5.0 (compatible; SEOToolkit/1.0)',
    status ENUM('active','paused','archived') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_status (user_id, status),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- IL_URLS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS il_urls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    url VARCHAR(500) NOT NULL,
    keyword VARCHAR(255) NULL,
    raw_html LONGTEXT NULL,
    content_html LONGTEXT NULL COMMENT 'Content extracted with CSS selector',
    http_status INT NULL,
    scraped_at DATETIME NULL,
    status ENUM('pending','scraping','scraped','error','no_content') DEFAULT 'pending',
    error_message VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES il_projects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_project_url (project_id, url),
    INDEX idx_project_status (project_id, status),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- IL_INTERNAL_LINKS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS il_internal_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    source_url_id INT NOT NULL,
    destination_url VARCHAR(500) NOT NULL,
    anchor_text VARCHAR(500) NULL,
    anchor_text_clean VARCHAR(500) NULL COMMENT 'Cleaned anchor (no extra spaces)',
    link_position INT NULL COMMENT 'Link position in content',
    source_block TEXT NULL COMMENT 'HTML block containing the link',
    is_internal BOOLEAN DEFAULT TRUE,
    is_valid BOOLEAN DEFAULT TRUE COMMENT 'Destination URL exists in project',
    -- AI Analysis Fields
    ai_relevance_score TINYINT NULL COMMENT '1-10 semantic relevance',
    ai_anchor_quality TINYINT NULL COMMENT '1-10 anchor text quality',
    ai_juice_flow ENUM('optimal','good','weak','poor','orphan') NULL,
    ai_notes TEXT NULL,
    ai_suggestions TEXT NULL,
    analyzed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES il_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (source_url_id) REFERENCES il_urls(id) ON DELETE CASCADE,
    INDEX idx_project (project_id),
    INDEX idx_source (source_url_id),
    INDEX idx_destination (destination_url(255)),
    INDEX idx_relevance (ai_relevance_score),
    INDEX idx_juice (ai_juice_flow)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- IL_PROJECT_STATS TABLE (Cache)
-- ============================================
CREATE TABLE IF NOT EXISTS il_project_stats (
    project_id INT PRIMARY KEY,
    total_urls INT DEFAULT 0,
    scraped_urls INT DEFAULT 0,
    error_urls INT DEFAULT 0,
    total_links INT DEFAULT 0,
    internal_links INT DEFAULT 0,
    external_links INT DEFAULT 0,
    analyzed_links INT DEFAULT 0,
    avg_relevance_score DECIMAL(3,1) NULL,
    orphan_pages INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES il_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- IL_SCRAPE_SESSIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS il_scrape_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    status ENUM('running','paused','completed','stopped') DEFAULT 'running',
    mode ENUM('pending','all') DEFAULT 'pending',
    total_urls INT DEFAULT 0,
    processed_urls INT DEFAULT 0,
    error_count INT DEFAULT 0,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (project_id) REFERENCES il_projects(id) ON DELETE CASCADE,
    INDEX idx_project_status (project_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- IL_ACTIVITY_LOGS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS il_activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NULL,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    details JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES il_projects(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_project_action (project_id, action),
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- IL_SNAPSHOTS TABLE (for comparison mode)
-- ============================================
CREATE TABLE IF NOT EXISTS il_snapshots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    total_urls INT DEFAULT 0,
    total_links INT DEFAULT 0,
    internal_links INT DEFAULT 0,
    external_links INT DEFAULT 0,
    orphan_pages INT DEFAULT 0,
    avg_relevance_score DECIMAL(3,1) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES il_projects(id) ON DELETE CASCADE,
    INDEX idx_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
