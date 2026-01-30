-- Migration: 005_add_meta_tags
-- Crea tabella per gestione SEO Meta Tags
-- Data: 2026-01-30

CREATE TABLE IF NOT EXISTS aic_meta_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,

    -- URL e info pagina
    url VARCHAR(700) NOT NULL,
    original_title VARCHAR(500) NULL,
    original_h1 VARCHAR(500) NULL,

    -- Meta attuali (da WP o scrape)
    current_meta_title VARCHAR(200) NULL,
    current_meta_desc VARCHAR(500) NULL,

    -- Generati da AI
    generated_title VARCHAR(70) NULL,
    generated_desc VARCHAR(200) NULL,

    -- Contenuto scrappato
    scraped_content MEDIUMTEXT NULL,
    scraped_word_count INT NULL,

    -- Status workflow
    status ENUM('pending','scraped','generated','approved','published','error') DEFAULT 'pending',

    -- WordPress
    wp_site_id INT NULL,
    wp_post_id INT NULL,
    wp_post_type VARCHAR(50) DEFAULT 'post',

    -- Errori
    scrape_error TEXT NULL,
    generation_error TEXT NULL,
    publish_error TEXT NULL,

    -- Timestamps
    scraped_at DATETIME NULL,
    generated_at DATETIME NULL,
    published_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_project_url (project_id, url(255)),
    INDEX idx_status (status),
    INDEX idx_user_id (user_id),
    INDEX idx_wp_site_post (wp_site_id, wp_post_id),
    FOREIGN KEY (project_id) REFERENCES aic_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
