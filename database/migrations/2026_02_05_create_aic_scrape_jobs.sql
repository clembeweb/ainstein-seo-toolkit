-- Migration: Create aic_scrape_jobs table
-- Date: 2026-02-05
-- Module: ai-content (meta-tags)
-- Purpose: Traccia job di scraping in background per meta tags

CREATE TABLE IF NOT EXISTS `aic_scrape_jobs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `type` ENUM('scrape', 'generate') DEFAULT 'scrape',
    `status` ENUM('pending', 'running', 'completed', 'error', 'cancelled') DEFAULT 'pending',

    -- Progress tracking
    `items_requested` INT UNSIGNED DEFAULT 0 COMMENT 'Numero totale di item da elaborare',
    `items_completed` INT UNSIGNED DEFAULT 0 COMMENT 'Item completati con successo',
    `items_failed` INT UNSIGNED DEFAULT 0 COMMENT 'Item falliti',

    -- Current item being processed
    `current_item_id` INT UNSIGNED DEFAULT NULL COMMENT 'ID meta_tag corrente',
    `current_item` VARCHAR(500) DEFAULT NULL COMMENT 'URL corrente in elaborazione',

    -- Credits
    `credits_used` DECIMAL(10,2) DEFAULT 0 COMMENT 'Crediti consumati',

    -- Error handling
    `error_message` TEXT DEFAULT NULL,

    -- Timestamps
    `started_at` TIMESTAMP NULL DEFAULT NULL,
    `completed_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Indexes
    INDEX `idx_project_status` (`project_id`, `status`),
    INDEX `idx_user` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
