-- Migration: Add Projects to AI Content Module
-- Date: 2026-01-12
-- Strategy: Retrocompatibility - project_id is NULLABLE

-- =============================================
-- STEP 1: Create aic_projects table
-- =============================================
CREATE TABLE IF NOT EXISTS aic_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    default_language VARCHAR(10) DEFAULT 'it',
    default_location VARCHAR(50) DEFAULT 'Italy',
    settings JSON NULL COMMENT 'AI settings: tone, style, etc.',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- STEP 2: Add project_id to aic_keywords (NULLABLE)
-- =============================================
-- Check if column exists before adding
SET @dbname = DATABASE();
SET @tablename = 'aic_keywords';
SET @columnname = 'project_id';
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
    'SELECT 1',
    'ALTER TABLE aic_keywords ADD COLUMN project_id INT NULL AFTER user_id'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add foreign key (will fail silently if exists)
-- ALTER TABLE aic_keywords ADD FOREIGN KEY (project_id) REFERENCES aic_projects(id) ON DELETE CASCADE;

-- =============================================
-- STEP 3: Add project_id to aic_articles (NULLABLE)
-- =============================================
SET @tablename = 'aic_articles';
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
    'SELECT 1',
    'ALTER TABLE aic_articles ADD COLUMN project_id INT NULL AFTER user_id'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add foreign key (will fail silently if exists)
-- ALTER TABLE aic_articles ADD FOREIGN KEY (project_id) REFERENCES aic_projects(id) ON DELETE CASCADE;
