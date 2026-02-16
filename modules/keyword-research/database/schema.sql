-- ============================================
-- AI Keyword Research Module - Schema
-- Prefix: kr_
-- ============================================

-- Progetti keyword research
CREATE TABLE IF NOT EXISTS kr_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('research', 'architecture', 'editorial') NOT NULL DEFAULT 'research',
    default_location VARCHAR(10) DEFAULT 'IT',
    default_language VARCHAR(10) DEFAULT 'it',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_user_type (user_id, type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ricerche (research guidata + architettura sito)
CREATE TABLE IF NOT EXISTS kr_researches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    type ENUM('research', 'architecture', 'editorial') NOT NULL,
    status ENUM('draft', 'collecting', 'analyzing', 'completed', 'error') DEFAULT 'draft',

    -- Brief input (JSON)
    brief JSON NOT NULL,

    -- Risultati
    raw_keywords_count INT DEFAULT 0,
    filtered_keywords_count INT DEFAULT 0,
    ai_response JSON,
    strategy_note TEXT,

    -- Metadata
    credits_used DECIMAL(5,2) DEFAULT 0,
    api_time_ms INT DEFAULT 0,
    ai_time_ms INT DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_project (project_id),
    INDEX idx_user (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cluster semantici
CREATE TABLE IF NOT EXISTS kr_clusters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    research_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    main_keyword VARCHAR(500) NOT NULL,
    main_volume INT DEFAULT 0,
    total_volume INT DEFAULT 0,
    keywords_count INT DEFAULT 0,
    intent VARCHAR(50),
    note TEXT,
    suggested_url VARCHAR(500),
    suggested_h1 VARCHAR(500),
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_research (research_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Keyword singole
CREATE TABLE IF NOT EXISTS kr_keywords (
    id INT AUTO_INCREMENT PRIMARY KEY,
    research_id INT NOT NULL,
    cluster_id INT,
    text VARCHAR(500) NOT NULL,
    volume INT DEFAULT 0,
    competition_level VARCHAR(20),
    competition_index INT DEFAULT 0,
    low_bid DECIMAL(10,6) DEFAULT 0,
    high_bid DECIMAL(10,6) DEFAULT 0,
    trend DECIMAL(10,2) DEFAULT 0,
    intent VARCHAR(50),
    is_main TINYINT(1) DEFAULT 0,
    is_excluded TINYINT(1) DEFAULT 0,
    source VARCHAR(50) DEFAULT 'keysuggest',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_research (research_id),
    INDEX idx_cluster (cluster_id),
    INDEX idx_text (text(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
