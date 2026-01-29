-- AI Optimizer Module - Database Schema
-- Prefisso: aio_
-- Creato: 2026-01-29

-- Progetti
CREATE TABLE IF NOT EXISTS aio_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    domain VARCHAR(255) NULL,
    description TEXT NULL,
    language VARCHAR(5) DEFAULT 'it',
    location_code VARCHAR(10) DEFAULT 'IT',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ottimizzazioni (articoli da ottimizzare)
CREATE TABLE IF NOT EXISTS aio_optimizations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    project_id INT NULL,

    -- Input
    original_url VARCHAR(500) NOT NULL,
    keyword VARCHAR(255) NOT NULL,
    original_title VARCHAR(500) NULL,
    original_meta_description TEXT NULL,
    original_h1 VARCHAR(500) NULL,
    original_content LONGTEXT NULL,
    original_word_count INT DEFAULT 0,
    original_headings_json LONGTEXT NULL,

    -- Analisi Gap
    analysis_json LONGTEXT NULL,
    competitors_json LONGTEXT NULL,
    competitors_count INT DEFAULT 0,
    seo_score INT NULL,

    -- Output Riscrittura
    optimized_title VARCHAR(500) NULL,
    optimized_meta_description TEXT NULL,
    optimized_h1 VARCHAR(500) NULL,
    optimized_content LONGTEXT NULL,
    optimized_word_count INT DEFAULT 0,

    -- Stato e tracking
    status ENUM('imported', 'analyzing', 'analyzed', 'refactoring', 'refactored', 'exported', 'failed') DEFAULT 'imported',
    error_message TEXT NULL,
    credits_used DECIMAL(10,2) DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    analyzed_at TIMESTAMP NULL,
    refactored_at TIMESTAMP NULL,

    INDEX idx_user (user_id),
    INDEX idx_project (project_id),
    INDEX idx_status (status),
    INDEX idx_keyword (keyword),
    INDEX idx_created (created_at),

    FOREIGN KEY (project_id) REFERENCES aio_projects(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cache scraping pagine (condivisa tra ottimizzazioni)
CREATE TABLE IF NOT EXISTS aio_page_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    url VARCHAR(500) NOT NULL,
    url_hash VARCHAR(64) NOT NULL,

    title VARCHAR(500) NULL,
    meta_description TEXT NULL,
    h1 VARCHAR(500) NULL,
    headings_json LONGTEXT NULL,
    content_text LONGTEXT NULL,
    word_count INT DEFAULT 0,

    scrape_status ENUM('success', 'error') DEFAULT 'success',
    error_message TEXT NULL,

    scraped_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uk_url_hash (url_hash),
    INDEX idx_scraped (scraped_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cache SERP (risultati ricerca per keyword)
CREATE TABLE IF NOT EXISTS aio_serp_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    keyword VARCHAR(255) NOT NULL,
    location_code VARCHAR(10) DEFAULT 'IT',
    language VARCHAR(5) DEFAULT 'it',

    results_json LONGTEXT NOT NULL,
    results_count INT DEFAULT 0,

    fetched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uk_keyword_location (keyword, location_code, language),
    INDEX idx_fetched (fetched_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
