-- Migration: Add default WP site to projects
-- Date: 2026-01-23
-- Description: Adds wp_site_id column to aic_projects for default WordPress site selection

-- =====================================================
-- STEP 1: Add wp_site_id column to aic_projects
-- =====================================================
SET @dbname = DATABASE();
SET @tablename = 'aic_projects';
SET @columnname = 'wp_site_id';

SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
    'SELECT 1',
    'ALTER TABLE aic_projects ADD COLUMN wp_site_id INT NULL DEFAULT NULL COMMENT "Default WP site for publishing" AFTER settings'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- =====================================================
-- STEP 2: Add index for performance
-- =====================================================
SET @indexname = 'idx_wp_site';
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND INDEX_NAME = @indexname) > 0,
    'SELECT 1',
    'ALTER TABLE aic_projects ADD INDEX idx_wp_site (wp_site_id)'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Note: Foreign key not enforced to allow deleting WP sites without affecting projects
-- The application should handle NULL wp_site_id gracefully
