-- SEO Audit: WordPress support + Background crawl jobs
-- Adds source tracking to sa_pages and creates sa_crawl_jobs for background processing

-- 1. Add source tracking to sa_pages
ALTER TABLE sa_pages ADD COLUMN source ENUM('crawl','wordpress') DEFAULT 'crawl' AFTER session_id;
ALTER TABLE sa_pages ADD COLUMN cms_entity_id INT NULL AFTER source;
ALTER TABLE sa_pages ADD COLUMN cms_entity_type VARCHAR(50) NULL AFTER cms_entity_id;

-- 2. Background crawl jobs (replaces frontend-only polling)
CREATE TABLE IF NOT EXISTS sa_crawl_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    session_id INT NOT NULL,
    user_id INT NOT NULL,
    type ENUM('crawl','wordpress') DEFAULT 'crawl',
    status ENUM('pending','running','completed','error','cancelled') DEFAULT 'pending',
    config JSON NULL,
    items_total INT DEFAULT 0,
    items_completed INT DEFAULT 0,
    items_failed INT DEFAULT 0,
    current_item VARCHAR(500) NULL,
    error_message TEXT NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_status (status),
    INDEX idx_project (project_id),
    INDEX idx_session (session_id),
    FOREIGN KEY (project_id) REFERENCES sa_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES sa_crawl_sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
