-- Migration 003: Ad Group analysis tables
-- Metriche aggregate per ad group e keyword per ad group

-- Metriche aggregate per ad group
CREATE TABLE IF NOT EXISTS ga_campaign_ad_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    run_id INT NOT NULL,
    campaign_id_google VARCHAR(50),
    campaign_name VARCHAR(500),
    campaign_type VARCHAR(100),
    ad_group_id_google VARCHAR(50) NOT NULL,
    ad_group_name VARCHAR(500),
    ad_group_status VARCHAR(50),
    clicks INT DEFAULT 0,
    impressions INT DEFAULT 0,
    ctr DECIMAL(8,4) DEFAULT 0,
    avg_cpc DECIMAL(10,2) DEFAULT 0,
    cost DECIMAL(12,2) DEFAULT 0,
    conversions DECIMAL(10,2) DEFAULT 0,
    conversion_value DECIMAL(12,2) DEFAULT 0,
    conv_rate DECIMAL(8,4) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_project (project_id),
    INDEX idx_run (run_id),
    INDEX idx_ad_group (ad_group_id_google)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Keyword per ad group
CREATE TABLE IF NOT EXISTS ga_ad_group_keywords (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    run_id INT NOT NULL,
    campaign_id_google VARCHAR(50),
    campaign_name VARCHAR(500),
    ad_group_id_google VARCHAR(50) NOT NULL,
    ad_group_name VARCHAR(500),
    keyword_text VARCHAR(500) NOT NULL,
    match_type VARCHAR(50),
    keyword_status VARCHAR(50),
    clicks INT DEFAULT 0,
    impressions INT DEFAULT 0,
    ctr DECIMAL(8,4) DEFAULT 0,
    avg_cpc DECIMAL(10,2) DEFAULT 0,
    cost DECIMAL(12,2) DEFAULT 0,
    conversions DECIMAL(10,2) DEFAULT 0,
    quality_score INT DEFAULT NULL,
    first_page_cpc DECIMAL(10,2) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_project (project_id),
    INDEX idx_run (run_id),
    INDEX idx_ad_group (ad_group_id_google)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Aggiungere contatori alla tabella evaluations
-- Usare procedure per gestire colonne gia esistenti
DROP PROCEDURE IF EXISTS add_eval_columns;
DELIMITER //
CREATE PROCEDURE add_eval_columns()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ga_campaign_evaluations' AND COLUMN_NAME = 'ad_groups_evaluated') THEN
        ALTER TABLE ga_campaign_evaluations ADD COLUMN ad_groups_evaluated INT DEFAULT 0 AFTER ads_evaluated;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ga_campaign_evaluations' AND COLUMN_NAME = 'keywords_evaluated') THEN
        ALTER TABLE ga_campaign_evaluations ADD COLUMN keywords_evaluated INT DEFAULT 0 AFTER ad_groups_evaluated;
    END IF;
END //
DELIMITER ;
CALL add_eval_columns();
DROP PROCEDURE IF EXISTS add_eval_columns;
