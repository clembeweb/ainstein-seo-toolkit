-- Keyword Planner Integration Migration
-- 2026-03-12

-- Rate limiting table (cross-module, kp_ prefix)
CREATE TABLE IF NOT EXISTS kp_usage (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    operations_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_user_date (user_id, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add provider and cpc_low to keyword volumes cache
-- Usa procedure per check colonna (MySQL 8.0 non supporta IF NOT EXISTS su ALTER)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'st_keyword_volumes' AND COLUMN_NAME = 'provider');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE st_keyword_volumes ADD COLUMN provider VARCHAR(30) DEFAULT ''unknown'' AFTER location_code',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'st_keyword_volumes' AND COLUMN_NAME = 'cpc_low');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE st_keyword_volumes ADD COLUMN cpc_low DECIMAL(10,2) NULL AFTER provider',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add cpc_low to tracked keywords (DECIMAL(10,2) come cpc esistente)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'st_keywords' AND COLUMN_NAME = 'cpc_low');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE st_keywords ADD COLUMN cpc_low DECIMAL(10,2) NULL AFTER cpc',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Keyword Planner settings (defaults)
INSERT INTO settings (key_name, value) VALUES
    ('kp_enabled', '0'),
    ('kp_daily_limit_per_user', '100'),
    ('kp_daily_limit_global', '5000'),
    ('kp_default_language', 'languageConstants/1004'),
    ('kp_default_location', 'geoTargetConstants/2380'),
    ('kp_cache_ttl_days', '7')
ON DUPLICATE KEY UPDATE key_name = key_name;
