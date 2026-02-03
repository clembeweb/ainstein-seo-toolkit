-- Migration: st_rank_jobs - Job tracking per rank check in background
-- Data: 2026-02-03

-- Tabella per tracking job di rank check (manuale e cron)
CREATE TABLE IF NOT EXISTS st_rank_jobs (
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
    keywords_found INT NOT NULL DEFAULT 0 COMMENT 'Keywords trovate in SERP',

    -- Current processing info
    current_keyword_id INT DEFAULT NULL,
    current_keyword VARCHAR(500) DEFAULT NULL,

    -- Results summary
    avg_position DECIMAL(5,2) DEFAULT NULL,
    credits_used DECIMAL(10,2) NOT NULL DEFAULT 0,

    -- Error handling
    error_message TEXT DEFAULT NULL,

    -- Timestamps
    started_at TIMESTAMP NULL DEFAULT NULL,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Indexes
    INDEX idx_project (project_id),
    INDEX idx_user_status (user_id, status),
    INDEX idx_status (status),
    INDEX idx_type_status (type, status),

    -- Foreign keys
    CONSTRAINT fk_st_rank_jobs_project FOREIGN KEY (project_id)
        REFERENCES st_projects(id) ON DELETE CASCADE,
    CONSTRAINT fk_st_rank_jobs_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Aggiungi job_id alla tabella queue esistente (se non esiste)
SET @column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'st_rank_queue'
    AND COLUMN_NAME = 'job_id'
);

SET @sql = IF(@column_exists = 0,
    'ALTER TABLE st_rank_queue ADD COLUMN job_id INT DEFAULT NULL COMMENT ''FK to st_rank_jobs for manual processing''',
    'SELECT ''Column job_id already exists'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Index per job_id (se non esiste)
SET @index_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'st_rank_queue'
    AND INDEX_NAME = 'idx_job'
);

SET @sql = IF(@index_exists = 0,
    'ALTER TABLE st_rank_queue ADD INDEX idx_job (job_id)',
    'SELECT ''Index idx_job already exists'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
