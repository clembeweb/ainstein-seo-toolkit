-- Migration: kr_keyword_cache - Cache risultati API keyword research
-- Data: 2026-02-13
-- Evita chiamate API duplicate per la stessa keyword/location/language

CREATE TABLE IF NOT EXISTS kr_keyword_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seed_keyword VARCHAR(500) NOT NULL COMMENT 'Keyword cercata (seed)',
    location VARCHAR(10) DEFAULT 'IT',
    language VARCHAR(10) DEFAULT 'it',
    endpoint VARCHAR(50) DEFAULT '/keysuggest',

    -- Risultati API (JSON array di keyword con metriche)
    results JSON NOT NULL,
    results_count INT DEFAULT 0,

    -- Cache management
    cached_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uk_seed_loc_lang_ep (seed_keyword(100), location, language, endpoint),
    INDEX idx_cached_at (cached_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
