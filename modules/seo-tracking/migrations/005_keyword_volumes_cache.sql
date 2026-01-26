-- ============================================================
-- SEO-TRACKING: Keyword Volumes Cache + Extended Columns
-- Generated: 2026-01-22
-- Version: 5.0
--
-- Crea tabella cache per volumi DataForSEO e aggiunge
-- colonne CPC, competition a st_keywords.
-- ============================================================

-- ============================================================
-- 1. TABELLA CACHE VOLUMI DI RICERCA
-- ============================================================

CREATE TABLE IF NOT EXISTS st_keyword_volumes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    keyword VARCHAR(500) NOT NULL,
    location_code INT NOT NULL DEFAULT 2380,
    data JSON NOT NULL COMMENT 'Dati DataForSEO: search_volume, cpc, competition, monthly_searches',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_keyword_location (keyword(255), location_code),
    INDEX idx_updated (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Cache volumi di ricerca da DataForSEO API';

-- ============================================================
-- 2. COLONNE AGGIUNTIVE A st_keywords
-- ============================================================

-- CPC (Costo per click)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'st_keywords'
                   AND COLUMN_NAME = 'cpc');

SET @sql = IF(@col_exists = 0,
              'ALTER TABLE st_keywords ADD COLUMN cpc DECIMAL(10,2) DEFAULT NULL COMMENT "Costo per click medio (EUR)" AFTER search_volume',
              'SELECT "cpc already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Competition (0-1)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'st_keywords'
                   AND COLUMN_NAME = 'competition');

SET @sql = IF(@col_exists = 0,
              'ALTER TABLE st_keywords ADD COLUMN competition DECIMAL(5,4) DEFAULT NULL COMMENT "Competition score (0-1)" AFTER cpc',
              'SELECT "competition already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Competition Level (LOW, MEDIUM, HIGH)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'st_keywords'
                   AND COLUMN_NAME = 'competition_level');

SET @sql = IF(@col_exists = 0,
              'ALTER TABLE st_keywords ADD COLUMN competition_level VARCHAR(20) DEFAULT NULL COMMENT "Competition level: LOW, MEDIUM, HIGH" AFTER competition',
              'SELECT "competition_level already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Volume Updated At
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'st_keywords'
                   AND COLUMN_NAME = 'volume_updated_at');

SET @sql = IF(@col_exists = 0,
              'ALTER TABLE st_keywords ADD COLUMN volume_updated_at TIMESTAMP NULL COMMENT "Ultimo aggiornamento dati volume" AFTER competition_level',
              'SELECT "volume_updated_at already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- VERIFICA POST-ESECUZIONE
-- ============================================================
/*
Run these queries to verify:

DESCRIBE st_keyword_volumes;

SELECT COLUMN_NAME, COLUMN_TYPE, COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME='st_keywords'
AND COLUMN_NAME IN ('search_volume', 'cpc', 'competition', 'competition_level', 'volume_updated_at');
*/
