-- Migration 011: Google Ads API Integration
-- Date: 2026-03-09
-- Replaces script-based ingestion with direct Google Ads API access
-- Tables affected: ga_projects, ga_campaigns, ga_ads, ga_extensions,
--   ga_campaign_ad_groups, ga_ad_group_keywords, ga_search_terms,
--   ga_campaign_evaluations, ga_auto_eval_queue, ga_analyses, ga_ad_groups

-- ============================================
-- 1. Create ga_syncs table (replaces ga_script_runs)
-- ============================================
CREATE TABLE IF NOT EXISTS ga_syncs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    sync_type ENUM('manual', 'cron', 'on_demand') DEFAULT 'manual',
    status ENUM('running', 'completed', 'error') DEFAULT 'running',
    date_range_start DATE NULL,
    date_range_end DATE NULL,
    campaigns_synced INT DEFAULT 0,
    ad_groups_synced INT DEFAULT 0,
    keywords_synced INT DEFAULT 0,
    ads_synced INT DEFAULT 0,
    search_terms_synced INT DEFAULT 0,
    error_message TEXT NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    INDEX idx_project (project_id),
    INDEX idx_status (status),
    FOREIGN KEY (project_id) REFERENCES ga_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. Add Google Ads connection columns to ga_projects
-- ============================================
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS ga_011_add_project_columns()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'ga_projects'
        AND COLUMN_NAME = 'google_ads_customer_id'
    ) THEN
        ALTER TABLE ga_projects
            ADD COLUMN google_ads_customer_id VARCHAR(20) NULL AFTER user_id,
            ADD COLUMN google_ads_account_name VARCHAR(255) NULL AFTER google_ads_customer_id,
            ADD COLUMN oauth_token_id INT NULL AFTER google_ads_account_name;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'ga_projects'
        AND COLUMN_NAME = 'last_sync_at'
    ) THEN
        ALTER TABLE ga_projects
            ADD COLUMN last_sync_at TIMESTAMP NULL,
            ADD COLUMN sync_enabled TINYINT(1) DEFAULT 1;
    END IF;
END //
DELIMITER ;

CALL ga_011_add_project_columns();
DROP PROCEDURE IF EXISTS ga_011_add_project_columns;

-- ============================================
-- 3. Drop old script columns from ga_projects
-- ============================================
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS ga_011_drop_script_columns()
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'ga_projects'
        AND COLUMN_NAME = 'api_token'
    ) THEN
        -- Drop unique index on api_token first
        SET @idx_exists = (SELECT COUNT(1) FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'ga_projects'
            AND INDEX_NAME = 'idx_api_token');
        IF @idx_exists > 0 THEN
            ALTER TABLE ga_projects DROP INDEX idx_api_token;
        END IF;

        ALTER TABLE ga_projects DROP COLUMN api_token;
    END IF;

    IF EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'ga_projects'
        AND COLUMN_NAME = 'api_token_created_at'
    ) THEN
        ALTER TABLE ga_projects DROP COLUMN api_token_created_at;
    END IF;

    IF EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'ga_projects'
        AND COLUMN_NAME = 'script_config'
    ) THEN
        ALTER TABLE ga_projects DROP COLUMN script_config;
    END IF;
END //
DELIMITER ;

CALL ga_011_drop_script_columns();
DROP PROCEDURE IF EXISTS ga_011_drop_script_columns;

-- ============================================
-- 4. Rename run_id → sync_id in all campaign tables
--    No FK constraints to drop (verified in schema)
-- ============================================

-- ga_campaigns (run_id NOT NULL, from migration 001)
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS ga_011_rename_run_ids()
BEGIN
    -- ga_campaigns
    IF EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'ga_campaigns'
        AND COLUMN_NAME = 'run_id'
    ) THEN
        ALTER TABLE ga_campaigns CHANGE COLUMN run_id sync_id INT NOT NULL;
    END IF;

    -- ga_campaign_ad_groups (run_id NOT NULL, from migration 003)
    IF EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'ga_campaign_ad_groups'
        AND COLUMN_NAME = 'run_id'
    ) THEN
        ALTER TABLE ga_campaign_ad_groups CHANGE COLUMN run_id sync_id INT NOT NULL;
    END IF;

    -- ga_ad_group_keywords (run_id NOT NULL, from migration 003)
    IF EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'ga_ad_group_keywords'
        AND COLUMN_NAME = 'run_id'
    ) THEN
        ALTER TABLE ga_ad_group_keywords CHANGE COLUMN run_id sync_id INT NOT NULL;
    END IF;

    -- ga_ads (run_id NOT NULL, from migration 001)
    IF EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'ga_ads'
        AND COLUMN_NAME = 'run_id'
    ) THEN
        ALTER TABLE ga_ads CHANGE COLUMN run_id sync_id INT NOT NULL;
    END IF;

    -- ga_extensions (run_id NOT NULL, from migration 001)
    IF EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'ga_extensions'
        AND COLUMN_NAME = 'run_id'
    ) THEN
        ALTER TABLE ga_extensions CHANGE COLUMN run_id sync_id INT NOT NULL;
    END IF;

    -- ga_campaign_evaluations (run_id NULL, from migration 001)
    IF EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'ga_campaign_evaluations'
        AND COLUMN_NAME = 'run_id'
    ) THEN
        ALTER TABLE ga_campaign_evaluations CHANGE COLUMN run_id sync_id INT NULL;
    END IF;

    -- ga_auto_eval_queue (run_id NOT NULL, from migration 005)
    IF EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'ga_auto_eval_queue'
        AND COLUMN_NAME = 'run_id'
    ) THEN
        -- Drop unique key on run_id first
        SET @uq_exists = (SELECT COUNT(1) FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'ga_auto_eval_queue'
            AND INDEX_NAME = 'uq_run');
        IF @uq_exists > 0 THEN
            ALTER TABLE ga_auto_eval_queue DROP INDEX uq_run;
        END IF;

        ALTER TABLE ga_auto_eval_queue CHANGE COLUMN run_id sync_id INT NOT NULL;
        ALTER TABLE ga_auto_eval_queue ADD UNIQUE KEY uq_sync (sync_id);
    END IF;

    -- ga_analyses (run_id NULL, added in migration 006)
    IF EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'ga_analyses'
        AND COLUMN_NAME = 'run_id'
    ) THEN
        ALTER TABLE ga_analyses CHANGE COLUMN run_id sync_id INT NULL;
    END IF;

    -- ga_ad_groups (run_id NULL, added in migration 006)
    IF EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'ga_ad_groups'
        AND COLUMN_NAME = 'run_id'
    ) THEN
        ALTER TABLE ga_ad_groups CHANGE COLUMN run_id sync_id INT NULL;
    END IF;

    -- ga_search_terms (run_id NULL, added in migration 006)
    IF EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'ga_search_terms'
        AND COLUMN_NAME = 'run_id'
    ) THEN
        ALTER TABLE ga_search_terms CHANGE COLUMN run_id sync_id INT NULL;
    END IF;
END //
DELIMITER ;

CALL ga_011_rename_run_ids();
DROP PROCEDURE IF EXISTS ga_011_rename_run_ids;

-- ============================================
-- 5. Add google_ads_id to entity tables
-- ============================================
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS ga_011_add_google_ads_ids()
BEGIN
    -- ga_campaigns
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'ga_campaigns'
        AND COLUMN_NAME = 'google_ads_id'
    ) THEN
        ALTER TABLE ga_campaigns ADD COLUMN google_ads_id BIGINT NULL AFTER id;
    END IF;

    -- ga_campaign_ad_groups
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'ga_campaign_ad_groups'
        AND COLUMN_NAME = 'google_ads_id'
    ) THEN
        ALTER TABLE ga_campaign_ad_groups ADD COLUMN google_ads_id BIGINT NULL AFTER id;
    END IF;

    -- ga_ads
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'ga_ads'
        AND COLUMN_NAME = 'google_ads_id'
    ) THEN
        ALTER TABLE ga_ads ADD COLUMN google_ads_id BIGINT NULL AFTER id;
    END IF;
END //
DELIMITER ;

CALL ga_011_add_google_ads_ids();
DROP PROCEDURE IF EXISTS ga_011_add_google_ads_ids;

-- ============================================
-- 6. Add sync_id, campaign_name, ad_group_name to ga_search_terms
--    (sync_id already renamed from run_id above, add the name columns)
-- ============================================
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS ga_011_add_search_term_columns()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'ga_search_terms'
        AND COLUMN_NAME = 'campaign_name'
    ) THEN
        ALTER TABLE ga_search_terms
            ADD COLUMN campaign_name VARCHAR(255) NULL,
            ADD COLUMN ad_group_name VARCHAR(255) NULL;
    END IF;
END //
DELIMITER ;

CALL ga_011_add_search_term_columns();
DROP PROCEDURE IF EXISTS ga_011_add_search_term_columns;

-- ============================================
-- 7. Create API usage tracking table
-- ============================================
CREATE TABLE IF NOT EXISTS ga_api_usage (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    operations_count INT DEFAULT 0,
    UNIQUE KEY idx_user_date (user_id, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 8. Add applied_at to ga_negative_keywords
-- ============================================
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS ga_011_add_applied_at()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'ga_negative_keywords'
        AND COLUMN_NAME = 'applied_at'
    ) THEN
        ALTER TABLE ga_negative_keywords ADD COLUMN applied_at TIMESTAMP NULL DEFAULT NULL;
    END IF;
END //
DELIMITER ;

CALL ga_011_add_applied_at();
DROP PROCEDURE IF EXISTS ga_011_add_applied_at;

-- ============================================
-- 9. Drop legacy script_runs table
-- ============================================
DROP TABLE IF EXISTS ga_script_runs;

-- ============================================
-- 10. Add publish tracking to creator campaigns
-- ============================================
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS ga_011_add_publish_columns()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'ga_creator_campaigns'
        AND COLUMN_NAME = 'published_to_google_ads'
    ) THEN
        ALTER TABLE ga_creator_campaigns
            ADD COLUMN published_to_google_ads TINYINT(1) DEFAULT 0,
            ADD COLUMN google_ads_campaign_id VARCHAR(255) NULL,
            ADD COLUMN published_at TIMESTAMP NULL;
    END IF;
END //
DELIMITER ;

CALL ga_011_add_publish_columns();
DROP PROCEDURE IF EXISTS ga_011_add_publish_columns;
