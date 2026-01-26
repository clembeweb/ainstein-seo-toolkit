-- ============================================================
-- SEO-TRACKING: Locations Table + Keyword Location Column
-- Generated: 2026-01-22
-- Version: 6.0
--
-- Crea tabella locations con codici per tutti i provider SERP
-- e aggiunge colonna location_code a st_keywords.
-- ============================================================

-- ============================================================
-- 1. TABELLA LOCATIONS
-- ============================================================

CREATE TABLE IF NOT EXISTS st_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    country_code VARCHAR(5) NOT NULL,
    language_code VARCHAR(5) NOT NULL DEFAULT 'en',
    -- Codici per DataForSEO
    dataforseo_location_code INT NOT NULL,
    dataforseo_language_code VARCHAR(10) NOT NULL,
    -- Codici per Serper.dev
    serper_gl VARCHAR(5) NOT NULL,
    serper_hl VARCHAR(5) NOT NULL,
    -- Codici per SERP API
    serpapi_location VARCHAR(100) DEFAULT NULL,
    serpapi_google_domain VARCHAR(50) DEFAULT 'google.com',
    -- Stato
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_country (country_code),
    INDEX idx_active_sort (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Locations supportate per SERP check e volumi';

-- ============================================================
-- 2. INSERISCI LOCATIONS COMUNI
-- ============================================================

INSERT IGNORE INTO st_locations
(name, country_code, language_code, dataforseo_location_code, dataforseo_language_code, serper_gl, serper_hl, serpapi_location, serpapi_google_domain, sort_order)
VALUES
('Italia', 'IT', 'it', 2380, 'it', 'it', 'it', 'Italy', 'google.it', 1),
('Stati Uniti', 'US', 'en', 2840, 'en', 'us', 'en', 'United States', 'google.com', 2),
('Regno Unito', 'GB', 'en', 2826, 'en', 'uk', 'en', 'United Kingdom', 'google.co.uk', 3),
('Germania', 'DE', 'de', 2276, 'de', 'de', 'de', 'Germany', 'google.de', 4),
('Francia', 'FR', 'fr', 2250, 'fr', 'fr', 'fr', 'France', 'google.fr', 5),
('Spagna', 'ES', 'es', 2724, 'es', 'es', 'es', 'Spain', 'google.es', 6),
('Svizzera', 'CH', 'de', 2756, 'de', 'ch', 'de', 'Switzerland', 'google.ch', 7),
('Austria', 'AT', 'de', 2040, 'de', 'at', 'de', 'Austria', 'google.at', 8),
('Paesi Bassi', 'NL', 'nl', 2528, 'nl', 'nl', 'nl', 'Netherlands', 'google.nl', 9),
('Belgio', 'BE', 'fr', 2056, 'fr', 'be', 'fr', 'Belgium', 'google.be', 10),
('Portogallo', 'PT', 'pt', 2620, 'pt', 'pt', 'pt', 'Portugal', 'google.pt', 11),
('Brasile', 'BR', 'pt', 2076, 'pt', 'br', 'pt', 'Brazil', 'google.com.br', 12),
('Canada', 'CA', 'en', 2124, 'en', 'ca', 'en', 'Canada', 'google.ca', 13),
('Australia', 'AU', 'en', 2036, 'en', 'au', 'en', 'Australia', 'google.com.au', 14),
('Messico', 'MX', 'es', 2484, 'es', 'mx', 'es', 'Mexico', 'google.com.mx', 15),
('Argentina', 'AR', 'es', 2032, 'es', 'ar', 'es', 'Argentina', 'google.com.ar', 16),
('Cile', 'CL', 'es', 2152, 'es', 'cl', 'es', 'Chile', 'google.cl', 17),
('Colombia', 'CO', 'es', 2170, 'es', 'co', 'es', 'Colombia', 'google.com.co', 18),
('Polonia', 'PL', 'pl', 2616, 'pl', 'pl', 'pl', 'Poland', 'google.pl', 19),
('Svezia', 'SE', 'sv', 2752, 'sv', 'se', 'sv', 'Sweden', 'google.se', 20);

-- ============================================================
-- 3. AGGIUNGI COLONNA location_code A st_keywords
-- ============================================================

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'st_keywords'
                   AND COLUMN_NAME = 'location_code');

SET @sql = IF(@col_exists = 0,
              'ALTER TABLE st_keywords ADD COLUMN location_code VARCHAR(5) DEFAULT "IT" COMMENT "Codice paese per SERP e volumi" AFTER keyword',
              'SELECT "location_code already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Indice per location
SET @idx_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
                   WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'st_keywords'
                   AND INDEX_NAME = 'idx_location_code');

SET @sql = IF(@idx_exists = 0,
              'ALTER TABLE st_keywords ADD INDEX idx_location_code (location_code)',
              'SELECT "idx_location_code already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- VERIFICA POST-ESECUZIONE
-- ============================================================
/*
Run these queries to verify:

SELECT * FROM st_locations ORDER BY sort_order LIMIT 10;

SELECT COLUMN_NAME, COLUMN_TYPE, COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME='st_keywords' AND COLUMN_NAME='location_code';
*/
