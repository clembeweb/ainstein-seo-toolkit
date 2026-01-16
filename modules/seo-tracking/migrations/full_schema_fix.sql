-- ============================================================
-- SEO-TRACKING: FULL SCHEMA ALIGNMENT FIX
-- Generated: 2026-01-07
-- Version: 2.0 (COMPLETE)
--
-- Questo script include TUTTI i fix necessari per allineare
-- lo schema DB al codice PHP del modulo seo-tracking.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. st_projects - ENUM sync_status
-- ============================================================
ALTER TABLE st_projects
MODIFY COLUMN sync_status ENUM('idle','running','completed','error','failed') DEFAULT 'idle';

-- ============================================================
-- 2. st_gsc_connections - Nullable property fields
-- ============================================================
ALTER TABLE st_gsc_connections
MODIFY COLUMN property_url VARCHAR(500) NULL DEFAULT NULL,
MODIFY COLUMN property_type ENUM('URL_PREFIX','DOMAIN') NULL DEFAULT NULL;

-- ============================================================
-- 3. st_gsc_data - Nullable query/page
-- ============================================================
ALTER TABLE st_gsc_data
MODIFY COLUMN query VARCHAR(500) NULL DEFAULT '',
MODIFY COLUMN page VARCHAR(2000) NULL DEFAULT '';

-- ============================================================
-- 4. st_sync_log - ENUM sync_type + nullable dates
-- ============================================================
ALTER TABLE st_sync_log
MODIFY COLUMN sync_type ENUM('gsc','ga4','full','gsc_daily','ga4_daily','gsc_full') NOT NULL,
MODIFY COLUMN date_from DATE NULL DEFAULT NULL,
MODIFY COLUMN date_to DATE NULL DEFAULT NULL;

-- ============================================================
-- 5. st_alerts - ENUM severity
-- ============================================================
ALTER TABLE st_alerts
MODIFY COLUMN severity ENUM('critical','high','medium','warning','info') NOT NULL;

-- ============================================================
-- 6. st_ai_reports - ENUM report_type
-- ============================================================
ALTER TABLE st_ai_reports
MODIFY COLUMN report_type ENUM('weekly_digest','monthly_executive','keyword_analysis','revenue_attribution','anomaly_detection','anomaly_analysis','custom') NOT NULL;

-- ============================================================
-- 7. st_keywords - NUOVE COLONNE E RENAME
-- ============================================================

-- 7a. Aggiungi is_tracked se non esiste
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'st_keywords' AND COLUMN_NAME = 'is_tracked');
SET @sql = IF(@col_exists = 0,
              'ALTER TABLE st_keywords ADD COLUMN is_tracked TINYINT(1) DEFAULT 1 AFTER alert_enabled',
              'SELECT "is_tracked exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 7b. Aggiungi source se non esiste
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'st_keywords' AND COLUMN_NAME = 'source');
SET @sql = IF(@col_exists = 0,
              'ALTER TABLE st_keywords ADD COLUMN source VARCHAR(50) DEFAULT ''manual'' AFTER is_tracked',
              'SELECT "source exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 7c. Aggiungi notes se non esiste
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'st_keywords' AND COLUMN_NAME = 'notes');
SET @sql = IF(@col_exists = 0,
              'ALTER TABLE st_keywords ADD COLUMN notes TEXT NULL AFTER source',
              'SELECT "notes exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 7d. Aggiungi target_position se non esiste
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'st_keywords' AND COLUMN_NAME = 'target_position');
SET @sql = IF(@col_exists = 0,
              'ALTER TABLE st_keywords ADD COLUMN target_position INT NULL AFTER notes',
              'SELECT "target_position exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 7e. Rinomina keyword_group -> group_name se esiste keyword_group
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'st_keywords' AND COLUMN_NAME = 'keyword_group');
SET @sql = IF(@col_exists > 0,
              'ALTER TABLE st_keywords CHANGE COLUMN keyword_group group_name VARCHAR(100) NULL DEFAULT NULL',
              'SELECT "keyword_group already renamed or not exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- VERIFICA POST-ESECUZIONE
-- ============================================================
/*
Run these queries to verify:

SELECT 'st_projects.sync_status', COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='st_projects' AND COLUMN_NAME='sync_status';
SELECT 'st_gsc_connections.property_url', IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='st_gsc_connections' AND COLUMN_NAME='property_url';
SELECT 'st_gsc_data.query', IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='st_gsc_data' AND COLUMN_NAME='query';
SELECT 'st_sync_log.sync_type', COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='st_sync_log' AND COLUMN_NAME='sync_type';
SELECT 'st_alerts.severity', COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='st_alerts' AND COLUMN_NAME='severity';
SELECT 'st_ai_reports.report_type', COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='st_ai_reports' AND COLUMN_NAME='report_type';
SELECT 'st_keywords columns' as info, GROUP_CONCAT(COLUMN_NAME) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='st_keywords';
*/

-- ============================================================
-- SUMMARY OF CHANGES
-- ============================================================
/*
| Table               | Column          | Change                                    |
|---------------------|-----------------|-------------------------------------------|
| st_projects         | sync_status     | Added 'failed' to ENUM                    |
| st_gsc_connections  | property_url    | Made nullable                             |
| st_gsc_connections  | property_type   | Made nullable                             |
| st_gsc_data         | query           | Made nullable, default ''                 |
| st_gsc_data         | page            | Made nullable, default ''                 |
| st_sync_log         | sync_type       | Added 'gsc_daily','ga4_daily','gsc_full'  |
| st_sync_log         | date_from       | Made nullable                             |
| st_sync_log         | date_to         | Made nullable                             |
| st_alerts           | severity        | Added 'high','medium'                     |
| st_ai_reports       | report_type     | Added 'anomaly_analysis'                  |
| st_keywords         | is_tracked      | Added column (TINYINT DEFAULT 1)          |
| st_keywords         | source          | Added column (VARCHAR DEFAULT 'manual')   |
| st_keywords         | notes           | Added column (TEXT NULL)                  |
| st_keywords         | target_position | Added column (INT NULL)                   |
| st_keywords         | keyword_group   | Renamed to group_name                     |
*/
