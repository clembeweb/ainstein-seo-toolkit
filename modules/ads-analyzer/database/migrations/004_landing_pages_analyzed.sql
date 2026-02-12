-- Migration 004: Add landing_pages_analyzed to campaign evaluations
-- Date: 2026-02-12

-- MySQL 8.0 non supporta ADD COLUMN IF NOT EXISTS
-- Usiamo stored procedure per sicurezza

DELIMITER //
CREATE PROCEDURE IF NOT EXISTS add_landing_pages_analyzed()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'ga_campaign_evaluations'
        AND COLUMN_NAME = 'landing_pages_analyzed'
    ) THEN
        ALTER TABLE ga_campaign_evaluations
            ADD COLUMN landing_pages_analyzed INT DEFAULT 0 AFTER keywords_evaluated;
    END IF;
END //
DELIMITER ;

CALL add_landing_pages_analyzed();
DROP PROCEDURE IF EXISTS add_landing_pages_analyzed;
