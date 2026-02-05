-- SEO Onpage Optimizer Module - Database Schema
-- Prefisso: sop_
-- Versione: 1.0.0

-- =============================================
-- PROGETTI
-- =============================================
CREATE TABLE IF NOT EXISTS sop_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    domain VARCHAR(500) NOT NULL,
    default_device ENUM('desktop', 'mobile') DEFAULT 'desktop',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_user (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- PAGINE
-- =============================================
CREATE TABLE IF NOT EXISTS sop_pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    url VARCHAR(2000) NOT NULL,
    url_hash VARCHAR(64) NOT NULL,
    title VARCHAR(500) DEFAULT NULL,
    status ENUM('pending', 'analyzing', 'completed', 'error') DEFAULT 'pending',
    onpage_score INT DEFAULT NULL,
    last_analyzed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_project_url (project_id, url_hash),
    INDEX idx_project (project_id),
    INDEX idx_status (status),
    INDEX idx_score (onpage_score),
    FOREIGN KEY (project_id) REFERENCES sop_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- ANALISI
-- =============================================
CREATE TABLE IF NOT EXISTS sop_analyses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    page_id INT NOT NULL,
    user_id INT NOT NULL,
    device ENUM('desktop', 'mobile') DEFAULT 'desktop',
    onpage_score INT DEFAULT NULL,

    -- Metriche Meta
    meta_title VARCHAR(500) DEFAULT NULL,
    meta_title_length INT DEFAULT NULL,
    meta_description TEXT DEFAULT NULL,
    meta_description_length INT DEFAULT NULL,
    canonical_url VARCHAR(2000) DEFAULT NULL,

    -- Metriche Content
    content_word_count INT DEFAULT NULL,
    content_readability DECIMAL(5,2) DEFAULT NULL,

    -- Headings
    h1_count INT DEFAULT 0,
    h1_content VARCHAR(500) DEFAULT NULL,
    h2_count INT DEFAULT 0,
    h3_count INT DEFAULT 0,

    -- Images
    images_count INT DEFAULT 0,
    images_without_alt INT DEFAULT 0,
    images_without_title INT DEFAULT 0,

    -- Links
    internal_links_count INT DEFAULT 0,
    external_links_count INT DEFAULT 0,
    broken_links_count INT DEFAULT 0,

    -- Technical
    is_indexable TINYINT(1) DEFAULT 1,
    has_schema_markup TINYINT(1) DEFAULT 0,
    has_hreflang TINYINT(1) DEFAULT 0,

    -- Core Web Vitals
    lcp_score DECIMAL(8,2) DEFAULT NULL,
    fid_score DECIMAL(8,2) DEFAULT NULL,
    cls_score DECIMAL(8,4) DEFAULT NULL,
    ttfb_score DECIMAL(8,2) DEFAULT NULL,

    -- Performance
    page_size_bytes BIGINT DEFAULT NULL,
    dom_complete_ms INT DEFAULT NULL,

    -- Issues count
    issues_critical INT DEFAULT 0,
    issues_warning INT DEFAULT 0,
    issues_notice INT DEFAULT 0,

    -- Raw data
    checks_json JSON DEFAULT NULL,
    raw_data JSON DEFAULT NULL,

    -- Crediti
    credits_used DECIMAL(10,2) DEFAULT 0,
    api_cost DECIMAL(10,6) DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_project (project_id),
    INDEX idx_page (page_id),
    INDEX idx_score (onpage_score),
    INDEX idx_created (created_at),
    FOREIGN KEY (project_id) REFERENCES sop_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (page_id) REFERENCES sop_pages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- ISSUES
-- =============================================
CREATE TABLE IF NOT EXISTS sop_issues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    analysis_id INT NOT NULL,
    page_id INT NOT NULL,
    check_name VARCHAR(200) NOT NULL,
    category ENUM('meta', 'content', 'images', 'links', 'technical', 'performance') NOT NULL,
    severity ENUM('critical', 'warning', 'notice') NOT NULL,
    message TEXT NOT NULL,
    current_value TEXT DEFAULT NULL,
    recommended_value TEXT DEFAULT NULL,
    status ENUM('open', 'fixed', 'ignored') DEFAULT 'open',
    fixed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_analysis (analysis_id),
    INDEX idx_page (page_id),
    INDEX idx_severity (severity),
    INDEX idx_category (category),
    INDEX idx_status (status),
    FOREIGN KEY (analysis_id) REFERENCES sop_analyses(id) ON DELETE CASCADE,
    FOREIGN KEY (page_id) REFERENCES sop_pages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- AI SUGGESTIONS
-- =============================================
CREATE TABLE IF NOT EXISTS sop_ai_suggestions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    analysis_id INT NOT NULL,
    page_id INT NOT NULL,
    suggestion_type ENUM('title', 'description', 'h1', 'content', 'technical', 'overall') NOT NULL,
    priority ENUM('high', 'medium', 'low') DEFAULT 'medium',
    current_value TEXT DEFAULT NULL,
    suggested_value TEXT DEFAULT NULL,
    reasoning TEXT DEFAULT NULL,
    estimated_score_gain INT DEFAULT NULL,
    status ENUM('pending', 'applied', 'rejected') DEFAULT 'pending',
    applied_at TIMESTAMP NULL,
    credits_used DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_analysis (analysis_id),
    INDEX idx_page (page_id),
    INDEX idx_priority (priority),
    INDEX idx_status (status),
    FOREIGN KEY (analysis_id) REFERENCES sop_analyses(id) ON DELETE CASCADE,
    FOREIGN KEY (page_id) REFERENCES sop_pages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- JOBS (Background Processing)
-- =============================================
CREATE TABLE IF NOT EXISTS sop_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    type ENUM('audit', 'ai_suggestions') DEFAULT 'audit',
    status ENUM('pending', 'running', 'completed', 'error', 'cancelled') DEFAULT 'pending',

    -- Progress
    pages_requested INT DEFAULT 0,
    pages_completed INT DEFAULT 0,
    pages_failed INT DEFAULT 0,
    current_url VARCHAR(2000) DEFAULT NULL,

    -- Results
    avg_score DECIMAL(5,2) DEFAULT NULL,
    total_issues INT DEFAULT 0,

    -- Credits
    credits_used DECIMAL(10,2) DEFAULT 0,

    -- Error
    error_message TEXT DEFAULT NULL,

    -- Timestamps
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_project (project_id),
    INDEX idx_status (status),
    INDEX idx_created (created_at),
    FOREIGN KEY (project_id) REFERENCES sop_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- QUEUE (Items da processare)
-- =============================================
CREATE TABLE IF NOT EXISTS sop_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    project_id INT NOT NULL,
    page_id INT DEFAULT NULL,
    url VARCHAR(2000) NOT NULL,
    device ENUM('desktop', 'mobile') DEFAULT 'desktop',
    status ENUM('pending', 'processing', 'completed', 'error') DEFAULT 'pending',
    analysis_id INT DEFAULT NULL,
    error_message TEXT DEFAULT NULL,
    scheduled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,

    INDEX idx_job (job_id),
    INDEX idx_project (project_id),
    INDEX idx_status (status),
    INDEX idx_scheduled (scheduled_at),
    FOREIGN KEY (job_id) REFERENCES sop_jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES sop_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
