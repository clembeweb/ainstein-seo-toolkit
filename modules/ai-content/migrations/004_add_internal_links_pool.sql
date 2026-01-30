-- Migration: Add Internal Links Pool to AI Content Module
-- Date: 2026-01-29
-- Purpose: Store internal links for automatic insertion in generated articles

-- =============================================
-- AIC_INTERNAL_LINKS_POOL TABLE
-- =============================================
-- Pool di link interni per progetto, importati da sitemap
-- L'AI usa questi dati per inserire link contestuali negli articoli

CREATE TABLE IF NOT EXISTS aic_internal_links_pool (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL COMMENT 'Reference to aic_projects',
    url VARCHAR(700) NOT NULL COMMENT 'Full URL of internal page',
    title VARCHAR(500) NULL COMMENT 'Page title (from scraping)',
    description TEXT NULL COMMENT 'Meta description (from scraping)',
    sitemap_source VARCHAR(500) NULL COMMENT 'Source sitemap URL',
    scrape_status ENUM('pending', 'completed', 'error') DEFAULT 'pending',
    scrape_error TEXT NULL COMMENT 'Error message if scraping failed',
    is_active TINYINT(1) DEFAULT 1 COMMENT '1=include in pool, 0=excluded',
    scraped_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES aic_projects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_project_url (project_id, url(255)),
    INDEX idx_project (project_id),
    INDEX idx_scrape_status (scrape_status),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
