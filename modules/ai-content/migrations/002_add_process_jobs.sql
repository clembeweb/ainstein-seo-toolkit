-- Migration: Add process jobs tracking and auto config enhancements
-- Date: 2026-01-16

-- =====================================================
-- TABELLA: aic_process_jobs
-- Tracking dei job di elaborazione (cron + manuali)
-- =====================================================
CREATE TABLE IF NOT EXISTS aic_process_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,

    -- Tipo e stato
    type ENUM('cron', 'manual') NOT NULL DEFAULT 'manual',
    status ENUM('pending', 'running', 'completed', 'error', 'cancelled') NOT NULL DEFAULT 'pending',

    -- Progress tracking
    keywords_requested INT NOT NULL DEFAULT 0,
    keywords_completed INT NOT NULL DEFAULT 0,
    keywords_failed INT NOT NULL DEFAULT 0,

    -- Current processing info
    current_queue_id INT DEFAULT NULL COMMENT 'FK a aic_queue item in elaborazione',
    current_keyword VARCHAR(255) DEFAULT NULL,
    current_step ENUM('pending', 'serp', 'scraping', 'brief', 'article', 'saving', 'done') DEFAULT 'pending',

    -- Results
    articles_generated INT NOT NULL DEFAULT 0,
    credits_used DECIMAL(10,2) NOT NULL DEFAULT 0,

    -- Error handling
    error_message TEXT DEFAULT NULL,

    -- Timestamps
    started_at TIMESTAMP NULL DEFAULT NULL,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Indexes
    INDEX idx_project (project_id),
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_type_status (type, status),

    -- Foreign keys
    CONSTRAINT fk_process_jobs_project FOREIGN KEY (project_id)
        REFERENCES aic_projects(id) ON DELETE CASCADE,
    CONSTRAINT fk_process_jobs_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ALTER: aic_auto_config
-- Aggiungi campi per tracking giornaliero
-- Run each separately to handle "column already exists" gracefully
-- =====================================================

-- Check and add columns (ignore errors if already exist)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'aic_auto_config' AND COLUMN_NAME = 'articles_today') = 0,
    'ALTER TABLE aic_auto_config ADD COLUMN articles_today INT NOT NULL DEFAULT 0 COMMENT "Articoli generati oggi"',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'aic_auto_config' AND COLUMN_NAME = 'last_reset_date') = 0,
    'ALTER TABLE aic_auto_config ADD COLUMN last_reset_date DATE DEFAULT NULL COMMENT "Data ultimo reset"',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'aic_auto_config' AND COLUMN_NAME = 'last_run_at') = 0,
    'ALTER TABLE aic_auto_config ADD COLUMN last_run_at TIMESTAMP NULL DEFAULT NULL COMMENT "Ultimo run dispatcher"',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- ALTER: aic_queue
-- Aggiungi job_id per collegare queue items ai jobs
-- =====================================================
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'aic_queue' AND COLUMN_NAME = 'job_id') = 0,
    'ALTER TABLE aic_queue ADD COLUMN job_id INT DEFAULT NULL COMMENT "FK a aic_process_jobs"',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
