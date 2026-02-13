-- =============================================
-- Migration 004: Fix schema mismatches
-- Aggiunge tabelle e colonne mancanti
-- MySQL 8.0 compatibile (no IF NOT EXISTS per colonne)
-- =============================================

-- Tabella crawl sessions (referenziata da CrawlSession model)
CREATE TABLE IF NOT EXISTS sa_crawl_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    status ENUM('pending','running','paused','stopping','stopped','completed','failed') DEFAULT 'pending',
    max_pages INT DEFAULT 500,
    crawl_mode VARCHAR(50) DEFAULT 'both',
    respect_robots BOOLEAN DEFAULT 1,
    include_external BOOLEAN DEFAULT 0,
    pages_found INT DEFAULT 0,
    pages_crawled INT DEFAULT 0,
    issues_found INT DEFAULT 0,
    current_url VARCHAR(2000),
    health_score INT DEFAULT NULL,
    critical_count INT DEFAULT 0,
    warning_count INT DEFAULT 0,
    notice_count INT DEFAULT 0,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES sa_projects(id) ON DELETE CASCADE,
    INDEX idx_project_status (project_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Colonne mancanti in sa_projects (procedural per MySQL 8.0)
SET @sql = (SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'sa_projects' AND column_name = 'current_session_id'),
    'SELECT 1',
    'ALTER TABLE sa_projects ADD COLUMN current_session_id INT DEFAULT NULL AFTER gsc_property'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'sa_projects' AND column_name = 'crawl_config'),
    'SELECT 1',
    'ALTER TABLE sa_projects ADD COLUMN crawl_config JSON DEFAULT NULL AFTER current_session_id'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Colonna session_id in sa_issues
SET @sql = (SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'sa_issues' AND column_name = 'session_id'),
    'SELECT 1',
    'ALTER TABLE sa_issues ADD COLUMN session_id INT DEFAULT NULL AFTER page_id'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Colonna status in sa_pages (pending, crawled, error)
SET @sql = (SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'sa_pages' AND column_name = 'status'),
    'SELECT 1',
    'ALTER TABLE sa_pages ADD COLUMN status VARCHAR(20) DEFAULT ''pending'' AFTER url'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Indice session_id in sa_issues
SET @sql = (SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'sa_issues' AND index_name = 'idx_session_id'),
    'SELECT 1',
    'ALTER TABLE sa_issues ADD INDEX idx_session_id (session_id)'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
