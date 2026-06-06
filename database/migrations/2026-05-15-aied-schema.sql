-- =====================================================================
-- Ainstein Editorial — Schema DB (namespace aied_*)
-- Milestone M1.1 — Foundation
-- Riferimento: plugins/ainstein-editorial/docs/design.md §4
--
-- Tutte le tabelle hanno prefisso `aied_` per separazione totale dalle
-- tabelle Ainstein. Engine InnoDB + utf8mb4 per coerenza con la piattaforma.
--
-- Esecuzione (locale):   mysql seo_toolkit < 2026-05-15-aied-schema.sql
-- Esecuzione (prod):      mysql -u ainstein -p ainstein_seo < 2026-05-15-aied-schema.sql
-- Rollback:               rollback/2026-05-15-aied-schema-rollback.sql
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- aied_users — Account plugin (creati silenziosamente all'attivazione license)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS aied_users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    license_key VARCHAR(255) NOT NULL UNIQUE,
    lemon_squeezy_customer_id VARCHAR(255) NULL,
    lemon_squeezy_subscription_id VARCHAR(255) NULL,
    tier ENUM('starter', 'pro', 'business', 'agency') NOT NULL DEFAULT 'starter',
    subscription_status ENUM('active', 'past_due', 'cancelled', 'expired') DEFAULT 'active',
    subscription_renews_at TIMESTAMP NULL,
    topup_balance_articles INT NOT NULL DEFAULT 0,
    locale VARCHAR(10) DEFAULT 'it',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_seen_at TIMESTAMP NULL,
    INDEX idx_license (license_key),
    INDEX idx_lemon_subscription (lemon_squeezy_subscription_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- aied_sites — Siti WordPress collegati a un user (limite N per tier)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS aied_sites (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    domain VARCHAR(255) NOT NULL,
    wp_version VARCHAR(20) NULL,
    plugin_version VARCHAR(20) NULL,
    api_token VARCHAR(255) NOT NULL UNIQUE,        -- JWT short-lived per chiamate plugin → backend
    api_token_expires_at TIMESTAMP NULL,
    ls_instance_id VARCHAR(255) NULL,              -- Lemon Squeezy license instance id (per deactivate)
    status ENUM('active', 'paused', 'deactivated') DEFAULT 'active',
    activated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deactivated_at TIMESTAMP NULL,
    settings JSON NULL,                            -- Preferenze utente (tone, audience, post freq)
    FOREIGN KEY (user_id) REFERENCES aied_users(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_user_domain (user_id, domain),
    INDEX idx_token (api_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- aied_actions_log — Consumo "azioni" per quota tracking mensile
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS aied_actions_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    site_id BIGINT UNSIGNED NOT NULL,
    action_type ENUM('article', 'meta_tag', 'image', 'keyword_research', 'internal_link_scan') NOT NULL,
    cost_in_units INT NOT NULL DEFAULT 1,
    billing_period_start DATE NOT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES aied_users(id) ON DELETE CASCADE,
    FOREIGN KEY (site_id) REFERENCES aied_sites(id) ON DELETE CASCADE,
    INDEX idx_user_period (user_id, billing_period_start),
    INDEX idx_action_type (action_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- aied_content_brain — Brand voice + glossario + editorial guidelines per sito
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS aied_content_brain (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id BIGINT UNSIGNED NOT NULL UNIQUE,
    brand_voice_summary TEXT NULL,
    brand_voice_examples JSON NULL,
    tone ENUM('professional', 'friendly', 'fun', 'authoritative', 'casual') DEFAULT 'friendly',
    target_audience TEXT NULL,
    site_topic TEXT NULL,
    glossary JSON NULL,                            -- {brand_names: [], products: [], avoid_terms: []}
    editorial_guidelines JSON NULL,                -- {do: [], dont: [], emphasize: []}
    language VARCHAR(10) DEFAULT 'it',
    last_scan_at TIMESTAMP NULL,
    scanned_articles_count INT DEFAULT 0,
    FOREIGN KEY (site_id) REFERENCES aied_sites(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- aied_editorial_plans — Calendari editoriali (1 sito: 1 attivo + storico)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS aied_editorial_plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    status ENUM('draft', 'active', 'paused', 'completed') DEFAULT 'draft',
    posts_per_month INT NOT NULL DEFAULT 4,
    auto_publish BOOLEAN DEFAULT FALSE,            -- opt-in v1.1
    starts_at DATE NOT NULL,
    ends_at DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES aied_sites(id) ON DELETE CASCADE,
    INDEX idx_site_status (site_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- aied_editorial_items — Singoli articoli pianificati nel calendario
-- ---------------------------------------------------------------------
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
    article_id BIGINT UNSIGNED NULL,               -- FK → aied_articles dopo generazione
    failure_reason TEXT NULL,
    FOREIGN KEY (plan_id) REFERENCES aied_editorial_plans(id) ON DELETE CASCADE,
    INDEX idx_plan_scheduled (plan_id, scheduled_for),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- aied_articles — Output AI generato + metadata + tracking pubblicazione WP
-- ---------------------------------------------------------------------
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
    FOREIGN KEY (site_id) REFERENCES aied_sites(id) ON DELETE CASCADE,
    FOREIGN KEY (editorial_item_id) REFERENCES aied_editorial_items(id) ON DELETE SET NULL,
    INDEX idx_site_created (site_id, created_at),
    INDEX idx_wp_post (wp_post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- aied_internal_links — Tracking link inseriti automaticamente (undo + audit)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS aied_internal_links (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id BIGINT UNSIGNED NOT NULL,
    from_article_id BIGINT UNSIGNED NOT NULL,      -- wp_posts.ID dell'articolo modificato
    to_article_id BIGINT UNSIGNED NOT NULL,        -- wp_posts.ID target del link
    anchor_text VARCHAR(500) NULL,
    context_snippet TEXT NULL,
    direction ENUM('forward', 'backward') NOT NULL,
    inserted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    removed_at TIMESTAMP NULL,
    FOREIGN KEY (site_id) REFERENCES aied_sites(id) ON DELETE CASCADE,
    INDEX idx_site_articles (site_id, from_article_id, to_article_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- aied_api_logs — Log chiamate AI/SERP/external + webhook (debug + costi)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS aied_api_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    site_id BIGINT UNSIGNED NULL,
    provider VARCHAR(50) NULL,                     -- 'anthropic', 'serper', 'gemini', 'lemonsqueezy', ...
    endpoint VARCHAR(255) NULL,
    external_event_id VARCHAR(255) NULL,           -- idempotency (es. webhook event id LS)
    request_payload JSON NULL,
    response_status INT NULL,
    response_summary TEXT NULL,
    duration_ms INT NULL,
    cost_eur DECIMAL(10,6) NULL,
    error TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_created (user_id, created_at),
    INDEX idx_provider (provider),
    INDEX idx_external_event (external_event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- aied_email_log — Log email inviate (deliverability + unsubscribe)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS aied_email_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    type ENUM('welcome', 'weekly_digest', 'monthly_report', 'license_recovery', 'quota_warning') NOT NULL,
    subject VARCHAR(500) NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    opened_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES aied_users(id) ON DELETE CASCADE,
    INDEX idx_user_type (user_id, type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- Test data (M1.1.g) — 3 user di test, uno per tier.
-- license_key e api_token sono placeholder per smoke test query downstream.
-- =====================================================================
INSERT INTO aied_users (email, license_key, tier, subscription_status) VALUES
    ('test-starter@ainstein.local',  'TEST-LICENSE-STARTER-0001',  'starter',  'active'),
    ('test-pro@ainstein.local',      'TEST-LICENSE-PRO-0001',      'pro',      'active'),
    ('test-business@ainstein.local', 'TEST-LICENSE-BUSINESS-0001', 'business', 'active')
ON DUPLICATE KEY UPDATE email = VALUES(email);
