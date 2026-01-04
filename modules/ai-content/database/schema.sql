-- AI Content Module - Database Schema
-- Prefix: aic_ (ai content)
-- Multi-tenant: user_id on relevant tables

USE seo_toolkit;

-- ============================================
-- AIC_KEYWORDS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS aic_keywords (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    keyword VARCHAR(255) NOT NULL,
    language VARCHAR(10) DEFAULT 'it',
    location VARCHAR(50) DEFAULT 'Italy',
    serp_extracted_at TIMESTAMP NULL COMMENT 'When SERP was last extracted',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_keyword (keyword)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- AIC_SERP_RESULTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS aic_serp_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    keyword_id INT NOT NULL,
    position INT NOT NULL,
    title VARCHAR(500),
    url VARCHAR(2000) NOT NULL,
    snippet TEXT,
    domain VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (keyword_id) REFERENCES aic_keywords(id) ON DELETE CASCADE,
    INDEX idx_keyword (keyword_id),
    INDEX idx_position (position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- AIC_PAA_QUESTIONS TABLE (People Also Ask)
-- ============================================
CREATE TABLE IF NOT EXISTS aic_paa_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    keyword_id INT NOT NULL,
    question TEXT NOT NULL,
    snippet TEXT,
    position INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (keyword_id) REFERENCES aic_keywords(id) ON DELETE CASCADE,
    INDEX idx_keyword (keyword_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- AIC_WP_SITES TABLE (WordPress Sites)
-- ============================================
CREATE TABLE IF NOT EXISTS aic_wp_sites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    url VARCHAR(500) NOT NULL,
    api_key VARCHAR(64) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_sync_at TIMESTAMP NULL,
    categories_cache JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_site (user_id, url(255)),
    INDEX idx_user (user_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- AIC_ARTICLES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS aic_articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    keyword_id INT NOT NULL,
    title VARCHAR(500),
    content LONGTEXT,
    meta_description VARCHAR(320),
    word_count INT,
    status ENUM('draft', 'generating', 'ready', 'published', 'failed') DEFAULT 'draft',
    brief_data JSON COMMENT 'Full brief used for generation',
    ai_model VARCHAR(50),
    generation_time_ms INT,
    credits_used INT DEFAULT 0,
    published_at TIMESTAMP NULL,
    wp_site_id INT NULL,
    wp_post_id INT NULL,
    published_url VARCHAR(2000) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (keyword_id) REFERENCES aic_keywords(id) ON DELETE CASCADE,
    FOREIGN KEY (wp_site_id) REFERENCES aic_wp_sites(id) ON DELETE SET NULL,
    INDEX idx_user_status (user_id, status),
    INDEX idx_keyword (keyword_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- AIC_SOURCES TABLE (Selected sources for article)
-- ============================================
CREATE TABLE IF NOT EXISTS aic_sources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    url VARCHAR(2000) NOT NULL,
    title VARCHAR(500),
    content_extracted LONGTEXT,
    headings_json JSON COMMENT 'Extracted headings structure',
    word_count INT,
    is_custom BOOLEAN DEFAULT FALSE COMMENT 'User-added URL vs SERP result',
    scrape_status ENUM('pending', 'success', 'failed') DEFAULT 'pending',
    error_message VARCHAR(500) NULL,
    scraped_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES aic_articles(id) ON DELETE CASCADE,
    INDEX idx_article (article_id),
    INDEX idx_status (scrape_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- AIC_WP_PUBLISH_LOG TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS aic_wp_publish_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    wp_site_id INT NOT NULL,
    wp_post_id INT NULL,
    status ENUM('success', 'failed') NOT NULL,
    response_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES aic_articles(id) ON DELETE CASCADE,
    FOREIGN KEY (wp_site_id) REFERENCES aic_wp_sites(id) ON DELETE CASCADE,
    INDEX idx_article (article_id),
    INDEX idx_site (wp_site_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
