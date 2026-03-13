-- Content Creator — Image Generation Tables
-- Date: 2026-03-12

-- 1. Images (products to generate variants for)
CREATE TABLE IF NOT EXISTS cc_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    product_url VARCHAR(2048) DEFAULT NULL COMMENT 'URL pagina prodotto sul sito e-commerce',
    sku VARCHAR(100) DEFAULT NULL,
    product_name VARCHAR(500) NOT NULL,
    category ENUM('fashion', 'home', 'custom') NOT NULL DEFAULT 'fashion',
    source_image_path VARCHAR(500) DEFAULT NULL,
    source_image_url VARCHAR(2048) DEFAULT NULL,
    source_type ENUM('cms', 'upload', 'url') NOT NULL DEFAULT 'upload',
    connector_id INT UNSIGNED DEFAULT NULL,
    cms_entity_id VARCHAR(100) DEFAULT NULL,
    cms_entity_type VARCHAR(50) DEFAULT NULL,
    generation_settings JSON DEFAULT NULL COMMENT 'Override preset per-item, null = usa default progetto',
    status ENUM('pending', 'source_acquired', 'generated', 'approved', 'published', 'error') NOT NULL DEFAULT 'pending',
    error_message TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_project_status (project_id, status),
    INDEX idx_user (user_id),
    FOREIGN KEY (project_id) REFERENCES cc_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Image variants (AI-generated images per product)
CREATE TABLE IF NOT EXISTS cc_image_variants (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    image_id INT UNSIGNED NOT NULL,
    variant_number TINYINT UNSIGNED NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    prompt_used TEXT DEFAULT NULL,
    revised_prompt TEXT DEFAULT NULL,
    provider_used VARCHAR(50) DEFAULT NULL COMMENT 'gemini, fashn, stability — per quality tracking',
    is_approved TINYINT(1) NOT NULL DEFAULT 0,
    is_pushed TINYINT(1) NOT NULL DEFAULT 0,
    cms_sync_error TEXT DEFAULT NULL,
    file_size_bytes INT UNSIGNED DEFAULT NULL,
    generation_time_ms INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_image_id (image_id),
    UNIQUE KEY uq_image_variant (image_id, variant_number),
    FOREIGN KEY (image_id) REFERENCES cc_images(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Extend cc_jobs with image types
ALTER TABLE cc_jobs MODIFY COLUMN type ENUM('scrape','generate','cms_push','image_generate','image_push') NOT NULL DEFAULT 'scrape';
