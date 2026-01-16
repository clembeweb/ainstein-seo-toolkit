-- MIGRATIONS - Eseguire se necessario dopo dump
-- Data: 2026-01-12

-- =============================================
-- SEO AUDIT: Crawl Sessions
-- =============================================
CREATE TABLE IF NOT EXISTS sa_crawl_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    status ENUM('pending','running','completed','failed') DEFAULT 'pending',
    total_urls INT DEFAULT 0,
    crawled_urls INT DEFAULT 0,
    started_at DATETIME,
    completed_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES sa_projects(id) ON DELETE CASCADE,
    INDEX idx_project (project_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- SEO TRACKING: Keyword Groups
-- =============================================
CREATE TABLE IF NOT EXISTS st_keyword_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    color VARCHAR(7) DEFAULT '#3B82F6',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES st_projects(id) ON DELETE CASCADE,
    INDEX idx_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Colonna group_id su keywords (se non esiste)
-- ALTER TABLE st_keywords ADD COLUMN group_id INT NULL AFTER project_id;
-- ALTER TABLE st_keywords ADD FOREIGN KEY (group_id) REFERENCES st_keyword_groups(id) ON DELETE SET NULL;
