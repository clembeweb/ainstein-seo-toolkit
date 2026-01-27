-- Migration: 007_create_serp_results.sql
-- Tabella per salvare tutti i risultati SERP (competitor)
-- Permette analisi competitiva e scraping pagine competitor

CREATE TABLE IF NOT EXISTS st_serp_results (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rank_check_id INT UNSIGNED NULL COMMENT 'FK a st_rank_checks (NULL se check standalone)',
    project_id INT UNSIGNED NOT NULL,
    keyword VARCHAR(500) NOT NULL,

    -- Dati risultato SERP
    position INT UNSIGNED NOT NULL COMMENT 'Posizione in SERP (1-100)',
    domain VARCHAR(255) NOT NULL COMMENT 'Dominio normalizzato',
    url VARCHAR(2000) NOT NULL COMMENT 'URL completo',
    title VARCHAR(500) DEFAULT NULL COMMENT 'Titolo pagina da SERP',
    snippet TEXT DEFAULT NULL COMMENT 'Snippet/descrizione da SERP',

    -- Classificazione
    result_type ENUM('organic', 'featured_snippet', 'knowledge_panel', 'shopping', 'news', 'image', 'video', 'ad') DEFAULT 'organic',
    is_target_domain BOOLEAN DEFAULT FALSE COMMENT 'TRUE se questo è il nostro dominio',

    -- Metadata ricerca
    location_code VARCHAR(10) DEFAULT 'IT',
    device VARCHAR(10) DEFAULT 'desktop',
    checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Indici
    INDEX idx_project_keyword (project_id, keyword(255)),
    INDEX idx_rank_check (rank_check_id),
    INDEX idx_domain (domain),
    INDEX idx_position (position),
    INDEX idx_checked (checked_at),

    FOREIGN KEY (project_id) REFERENCES st_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella per salvare analisi pagina (cache scraping)
CREATE TABLE IF NOT EXISTS st_page_analysis (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    url VARCHAR(2000) NOT NULL,
    url_hash VARCHAR(64) NOT NULL COMMENT 'SHA256 dell URL per lookup veloce',

    -- Dati scraping
    title VARCHAR(500) DEFAULT NULL,
    meta_description TEXT DEFAULT NULL,
    h1 TEXT DEFAULT NULL,
    headings_json TEXT DEFAULT NULL COMMENT 'JSON con tutti gli heading H1-H6',
    content_text LONGTEXT DEFAULT NULL COMMENT 'Testo principale estratto',
    word_count INT UNSIGNED DEFAULT 0,

    -- Metadata
    scraped_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    scrape_status ENUM('success', 'error', 'timeout', 'blocked') DEFAULT 'success',
    error_message VARCHAR(500) DEFAULT NULL,

    -- Indici
    INDEX idx_project (project_id),
    INDEX idx_url_hash (url_hash),
    INDEX idx_scraped (scraped_at),

    UNIQUE KEY unique_url (url_hash),
    FOREIGN KEY (project_id) REFERENCES st_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella per salvare analisi AI delle pagine
CREATE TABLE IF NOT EXISTS st_page_ai_analysis (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    keyword_id INT UNSIGNED NULL COMMENT 'FK a st_keywords se analisi per keyword specifica',
    keyword VARCHAR(500) NOT NULL,

    -- URL analizzata
    target_url VARCHAR(2000) NOT NULL,
    target_position INT UNSIGNED DEFAULT NULL,

    -- Risultato analisi AI
    analysis_json LONGTEXT NOT NULL COMMENT 'JSON completo analisi AI',
    summary TEXT DEFAULT NULL COMMENT 'Sommario testuale',

    -- Competitor analizzati
    competitors_analyzed INT UNSIGNED DEFAULT 0,
    competitors_json TEXT DEFAULT NULL COMMENT 'JSON con dati competitor usati',

    -- Metadata
    credits_used DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Indici
    INDEX idx_project (project_id),
    INDEX idx_keyword_id (keyword_id),
    INDEX idx_created (created_at),

    FOREIGN KEY (project_id) REFERENCES st_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
