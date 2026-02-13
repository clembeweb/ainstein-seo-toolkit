-- Migration 005: Auto-evaluation system
-- Date: 2026-02-12
-- Adds: ga_auto_eval_queue table, new columns on evaluations and projects

-- 1. Tabella coda auto-valutazione
CREATE TABLE IF NOT EXISTS ga_auto_eval_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    run_id INT NOT NULL,
    status ENUM('pending','processing','completed','skipped','error') DEFAULT 'pending',
    skip_reason VARCHAR(255) NULL,
    attempts INT DEFAULT 0,
    error_message TEXT NULL,
    scheduled_for TIMESTAMP NOT NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_run (run_id),
    INDEX idx_status_scheduled (status, scheduled_for),
    INDEX idx_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Nuove colonne su ga_campaign_evaluations
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS add_auto_eval_columns()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'ga_campaign_evaluations'
        AND COLUMN_NAME = 'eval_type'
    ) THEN
        ALTER TABLE ga_campaign_evaluations
            ADD COLUMN eval_type ENUM('manual','auto') DEFAULT 'manual' AFTER run_id;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'ga_campaign_evaluations'
        AND COLUMN_NAME = 'previous_eval_id'
    ) THEN
        ALTER TABLE ga_campaign_evaluations
            ADD COLUMN previous_eval_id INT NULL AFTER eval_type;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'ga_campaign_evaluations'
        AND COLUMN_NAME = 'metric_deltas'
    ) THEN
        ALTER TABLE ga_campaign_evaluations
            ADD COLUMN metric_deltas JSON NULL AFTER ai_response;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'ga_campaign_evaluations'
        AND COLUMN_NAME = 'campaigns_filter'
    ) THEN
        ALTER TABLE ga_campaign_evaluations
            ADD COLUMN campaigns_filter JSON NULL AFTER metric_deltas;
    END IF;
END //
DELIMITER ;

CALL add_auto_eval_columns();
DROP PROCEDURE IF EXISTS add_auto_eval_columns;

-- 3. Auto-eval toggle su progetto
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS add_auto_evaluate_column()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'ga_projects'
        AND COLUMN_NAME = 'auto_evaluate'
    ) THEN
        ALTER TABLE ga_projects
            ADD COLUMN auto_evaluate TINYINT(1) DEFAULT 0 AFTER script_config;
    END IF;
END //
DELIMITER ;

CALL add_auto_evaluate_column();
DROP PROCEDURE IF EXISTS add_auto_evaluate_column;
