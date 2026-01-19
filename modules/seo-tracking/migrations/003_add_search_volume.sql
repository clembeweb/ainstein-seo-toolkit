-- ============================================================
-- SEO-TRACKING: Add search_volume column
-- Generated: 2026-01-19
-- Version: 3.0
--
-- Aggiunge campo search_volume a st_keywords per
-- la feature Position Compare.
-- ============================================================

-- Aggiungi search_volume se non esiste
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'st_keywords'
                   AND COLUMN_NAME = 'search_volume');

SET @sql = IF(@col_exists = 0,
              'ALTER TABLE st_keywords ADD COLUMN search_volume INT UNSIGNED NULL DEFAULT NULL COMMENT "Volume di ricerca mensile stimato" AFTER keyword',
              'SELECT "search_volume already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Indice per ordinamento
SET @idx_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
                   WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'st_keywords'
                   AND INDEX_NAME = 'idx_search_volume');

SET @sql = IF(@idx_exists = 0,
              'ALTER TABLE st_keywords ADD INDEX idx_search_volume (search_volume)',
              'SELECT "idx_search_volume already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- Indici su st_gsc_data per performance query confronto
-- ============================================================

-- Indice composito per query periodo (se non esiste)
SET @idx_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
                   WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'st_gsc_data'
                   AND INDEX_NAME = 'idx_project_date_query');

SET @sql = IF(@idx_exists = 0,
              'ALTER TABLE st_gsc_data ADD INDEX idx_project_date_query (project_id, date, query(100))',
              'SELECT "idx_project_date_query already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- VERIFICA POST-ESECUZIONE
-- ============================================================
/*
Run these queries to verify:

SELECT COLUMN_NAME, COLUMN_TYPE, COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME='st_keywords' AND COLUMN_NAME='search_volume';

SHOW INDEX FROM st_keywords WHERE Key_name = 'idx_search_volume';
SHOW INDEX FROM st_gsc_data WHERE Key_name = 'idx_project_date_query';
*/
