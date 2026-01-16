-- Migration: Add discovered_urls field to sa_site_config
-- This stores the list of URLs found during discovery phase
-- Used by crawlBatch to know which URLs to crawl

-- Check if column exists and add if not
SET @dbname = DATABASE();
SET @tablename = 'sa_site_config';
SET @columnname = 'discovered_urls';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = @tablename
    AND COLUMN_NAME = @columnname
  ) > 0,
  'SELECT 1',
  'ALTER TABLE sa_site_config ADD COLUMN discovered_urls LONGTEXT NULL AFTER sitemap_urls'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;
