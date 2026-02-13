-- ============================================
-- Migration: Piano Editoriale
-- Date: 2026-02-13
-- Adds 'editorial' type + kr_editorial_items + kr_serp_cache
-- ============================================

-- Aggiungere 'editorial' al ENUM type di kr_researches
ALTER TABLE kr_researches
MODIFY COLUMN type ENUM('research', 'architecture', 'editorial') NOT NULL;

-- Tabella articoli del piano editoriale
CREATE TABLE IF NOT EXISTS kr_editorial_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    research_id INT NOT NULL,
    month_number INT NOT NULL,
    week_number INT DEFAULT NULL,
    category VARCHAR(255) NOT NULL,
    title VARCHAR(500) NOT NULL,
    main_keyword VARCHAR(500) NOT NULL,
    main_volume INT DEFAULT 0,
    secondary_keywords JSON,
    intent VARCHAR(50),
    difficulty VARCHAR(20) DEFAULT 'medium',
    content_type VARCHAR(100),
    notes TEXT,
    seasonal_note VARCHAR(500),
    serp_gap TEXT,
    sort_order INT DEFAULT 0,
    sent_to_content TINYINT(1) DEFAULT 0,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_research (research_id),
    INDEX idx_month (research_id, month_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cache SERP per evitare chiamate duplicate (TTL 7 giorni)
CREATE TABLE IF NOT EXISTS kr_serp_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    query VARCHAR(500) NOT NULL,
    location VARCHAR(10) DEFAULT 'IT',
    language VARCHAR(10) DEFAULT 'it',
    organic_results JSON NOT NULL,
    paa JSON,
    related_searches JSON,
    cached_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_query_loc_lang (query(200), location, language),
    INDEX idx_cached_at (cached_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
