-- Migration: Ainstein Editorial plugin schema (aied_*)
-- Data: 2026-05-15
-- Milestone: M1.1
-- Run: mysql -u root seo_toolkit < database/migrations/2026_05_15_create_aied_schema.sql
--
-- Crea 9 tabelle namespace aied_* per il plugin WordPress "Ainstein Editorial".
-- Separazione totale dalle tabelle Ainstein esistenti (users, aic_*, sa_*, etc.).
-- Ordine di creazione rispetta dipendenze FK:
--   1) aied_users
--   2) aied_sites              FK -> aied_users
--   3) aied_actions_log        FK -> aied_users, aied_sites
--   4) aied_content_brain      FK -> aied_sites
--   5) aied_editorial_plans    FK -> aied_sites
--   6) aied_editorial_items    FK -> aied_editorial_plans
--   7) aied_articles           FK -> aied_sites, aied_editorial_items
--   8) aied_internal_links     FK -> aied_sites
--   9) aied_api_logs           (no FK constraints, soft references)

-- ---------------------------------------------------------------------------
-- 1. aied_users
-- Account utenti del plugin. Creati silenziosamente all'attivazione license.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS aied_users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    license_key VARCHAR(255) NOT NULL,
    lemon_squeezy_customer_id VARCHAR(255) NULL,
    lemon_squeezy_subscription_id VARCHAR(255) NULL,
    tier ENUM('starter', 'pro', 'business', 'agency') NOT NULL DEFAULT 'starter',
    subscription_status ENUM('active', 'past_due', 'cancelled', 'expired') DEFAULT 'active',
    subscription_renews_at TIMESTAMP NULL,
    locale VARCHAR(10) DEFAULT 'it',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_seen_at TIMESTAMP NULL,
    UNIQUE KEY idx_license (license_key),
    INDEX idx_lemon_subscription (lemon_squeezy_subscription_id),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 2. aied_sites
-- Siti WordPress collegati a un user. Limite N siti per tier.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS aied_sites (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    domain VARCHAR(255) NOT NULL,
    wp_version VARCHAR(20) NULL,
    plugin_version VARCHAR(20) NULL,
    api_token VARCHAR(255) NOT NULL,
    api_token_expires_at TIMESTAMP NULL,
    status ENUM('active', 'paused', 'deactivated') DEFAULT 'active',
    activated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deactivated_at TIMESTAMP NULL,
    settings JSON NULL,
    UNIQUE KEY uniq_api_token (api_token),
    UNIQUE KEY uniq_user_domain (user_id, domain),
    INDEX idx_status (status),
    CONSTRAINT fk_aied_sites_user FOREIGN KEY (user_id) REFERENCES aied_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 3. aied_actions_log
-- Log consumo "azioni" per quota tracking mensile.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS aied_actions_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    site_id BIGINT UNSIGNED NOT NULL,
    action_type ENUM('article', 'meta_tag', 'image', 'keyword_research', 'internal_link_scan') NOT NULL,
    cost_in_units INT NOT NULL DEFAULT 1,
    billing_period_start DATE NOT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_period (user_id, billing_period_start),
    INDEX idx_action_type (action_type),
    INDEX idx_site_created (site_id, created_at),
    CONSTRAINT fk_aied_actions_user FOREIGN KEY (user_id) REFERENCES aied_users(id) ON DELETE CASCADE,
    CONSTRAINT fk_aied_actions_site FOREIGN KEY (site_id) REFERENCES aied_sites(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 4. aied_content_brain
-- Brand voice + glossary + editorial guidelines per sito.
-- 1 sito -> 1 content brain (UNIQUE site_id).
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS aied_content_brain (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id BIGINT UNSIGNED NOT NULL,
    brand_voice_summary TEXT NULL,
    brand_voice_examples JSON NULL,
    tone ENUM('professional', 'friendly', 'fun', 'authoritative', 'casual') DEFAULT 'friendly',
    target_audience TEXT NULL,
    site_topic TEXT NULL,
    glossary JSON NULL,
    editorial_guidelines JSON NULL,
    language VARCHAR(10) DEFAULT 'it',
    last_scan_at TIMESTAMP NULL,
    scanned_articles_count INT DEFAULT 0,
    UNIQUE KEY uniq_site (site_id),
    CONSTRAINT fk_aied_brain_site FOREIGN KEY (site_id) REFERENCES aied_sites(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 5. aied_editorial_plans
-- Calendari editoriali. 1 sito puo' avere 1 piano attivo + storico.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS aied_editorial_plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    status ENUM('draft', 'active', 'paused', 'completed') DEFAULT 'draft',
    posts_per_month INT NOT NULL DEFAULT 4,
    auto_publish BOOLEAN DEFAULT FALSE,
    starts_at DATE NOT NULL,
    ends_at DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_site_status (site_id, status),
    CONSTRAINT fk_aied_plans_site FOREIGN KEY (site_id) REFERENCES aied_sites(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 6. aied_editorial_items
-- Singoli articoli pianificati nel calendario.
-- NB: article_id FK aggiunta DOPO la creazione di aied_articles (ALTER finale).
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS aied_editorial_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plan_id BIGINT UNSIGNED NOT NULL,
    keyword VARCHAR(500) NOT NULL,
    keyword_difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
    monthly_volume INT DEFAULT 0,
    intent ENUM('informational', 'commercial', 'navigational', 'transactional') DEFAULT 'informational',
    proposed_title VARCHAR(500) NULL,
    proposed_outline JSON NULL,
    scheduled_for DATE NOT NULL,
    status ENUM('pending', 'in_progress', 'generated', 'published', 'failed', 'skipped') DEFAULT 'pending',
    article_id BIGINT UNSIGNED NULL,
    failure_reason TEXT NULL,
    INDEX idx_plan_scheduled (plan_id, scheduled_for),
    INDEX idx_status (status),
    INDEX idx_article (article_id),
    CONSTRAINT fk_aied_items_plan FOREIGN KEY (plan_id) REFERENCES aied_editorial_plans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 7. aied_articles
-- Output AI generato + metadata + tracking pubblicazione WP.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS aied_articles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id BIGINT UNSIGNED NOT NULL,
    editorial_item_id BIGINT UNSIGNED NULL,
    keyword VARCHAR(500) NOT NULL,
    title VARCHAR(500) NOT NULL,
    content LONGTEXT NOT NULL,
    word_count INT NULL,
    meta_title VARCHAR(255) NULL,
    meta_description TEXT NULL,
    cover_image_url VARCHAR(1000) NULL,
    cover_image_attachment_id BIGINT NULL,
    wp_post_id BIGINT NULL,
    wp_post_status VARCHAR(20) NULL,
    serp_data JSON NULL,
    sources JSON NULL,
    brief JSON NULL,
    quality_score INT NULL,
    generation_time_seconds INT NULL,
    ai_cost_eur DECIMAL(10,4) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    published_at TIMESTAMP NULL,
    INDEX idx_site_created (site_id, created_at),
    INDEX idx_wp_post (wp_post_id),
    INDEX idx_editorial_item (editorial_item_id),
    CONSTRAINT fk_aied_articles_site FOREIGN KEY (site_id) REFERENCES aied_sites(id) ON DELETE CASCADE,
    CONSTRAINT fk_aied_articles_item FOREIGN KEY (editorial_item_id) REFERENCES aied_editorial_items(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Aggiunge FK circolare aied_editorial_items.article_id -> aied_articles.id
-- (creata dopo aied_articles per evitare problemi di ordine creazione).
-- IF NOT EXISTS non e' supportato per FK su MySQL: usiamo procedura conditional.
SET @fk_exists := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'aied_editorial_items'
      AND CONSTRAINT_NAME = 'fk_aied_items_article'
);
SET @sql := IF(@fk_exists = 0,
    'ALTER TABLE aied_editorial_items ADD CONSTRAINT fk_aied_items_article FOREIGN KEY (article_id) REFERENCES aied_articles(id) ON DELETE SET NULL',
    'SELECT "fk_aied_items_article already exists" AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- 8. aied_internal_links
-- Tracking link interni inseriti automaticamente. Permette undo + audit.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS aied_internal_links (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id BIGINT UNSIGNED NOT NULL,
    from_article_id BIGINT UNSIGNED NOT NULL,
    to_article_id BIGINT UNSIGNED NOT NULL,
    anchor_text VARCHAR(500) NULL,
    context_snippet TEXT NULL,
    direction ENUM('forward', 'backward') NOT NULL,
    inserted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    removed_at TIMESTAMP NULL,
    INDEX idx_site_articles (site_id, from_article_id, to_article_id),
    INDEX idx_removed (removed_at),
    CONSTRAINT fk_aied_links_site FOREIGN KEY (site_id) REFERENCES aied_sites(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 9. aied_api_logs
-- Log chiamate AI/SERP/external per debugging + analytics costi.
-- Soft references (no FK) perche' utile mantenere log anche dopo delete user/site.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS aied_api_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    site_id BIGINT UNSIGNED NULL,
    provider VARCHAR(50) NULL,
    endpoint VARCHAR(255) NULL,
    request_payload JSON NULL,
    response_status INT NULL,
    response_summary TEXT NULL,
    duration_ms INT NULL,
    cost_eur DECIMAL(10,6) NULL,
    error TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_created (user_id, created_at),
    INDEX idx_provider (provider),
    INDEX idx_site_created (site_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
