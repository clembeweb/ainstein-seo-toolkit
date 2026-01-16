-- Migration: Create sa_crawl_sessions table
-- Module: seo-audit
-- Date: 2025-01-07
-- Description: Tabella per tracciare sessioni di crawl con supporto stop/resume

CREATE TABLE IF NOT EXISTS sa_crawl_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,

    -- Status tracking
    status ENUM('pending', 'running', 'paused', 'stopping', 'stopped', 'completed', 'failed') DEFAULT 'pending',

    -- Progress tracking
    pages_found INT DEFAULT 0,
    pages_crawled INT DEFAULT 0,
    issues_found INT DEFAULT 0,
    current_url VARCHAR(2000) NULL,

    -- Configuration
    max_pages INT DEFAULT 500,
    crawl_mode ENUM('sitemap', 'spider', 'both') DEFAULT 'both',
    respect_robots TINYINT(1) DEFAULT 1,
    include_external TINYINT(1) DEFAULT 0,

    -- Timestamps
    started_at TIMESTAMP NULL,
    stopped_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,

    -- Error handling
    error_message TEXT NULL,
    last_error_at TIMESTAMP NULL,

    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Indexes
    INDEX idx_project_status (project_id, status),
    INDEX idx_status (status),

    -- Foreign keys
    FOREIGN KEY (project_id) REFERENCES sa_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add session_id column to sa_projects if not exists
-- Questo permette di sapere quale sessione è attiva per un progetto
ALTER TABLE sa_projects
ADD COLUMN IF NOT EXISTS current_session_id INT NULL,
ADD COLUMN IF NOT EXISTS crawl_config JSON NULL COMMENT 'Configurazione crawl salvata';
