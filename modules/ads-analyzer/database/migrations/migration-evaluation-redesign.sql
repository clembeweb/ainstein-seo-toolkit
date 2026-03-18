-- Ads Evaluation Redesign Migration
-- Run locale: mysql -u root seo_toolkit < modules/ads-analyzer/database/migrations/migration-evaluation-redesign.sql
-- Run prod:   mysql -u ainstein -p'Ainstein_DB_2026!Secure' ainstein_seo < modules/ads-analyzer/database/migrations/migration-evaluation-redesign.sql

-- 1. schema_version per backward compat (v1=vecchio formato, v2=nuovo)
ALTER TABLE ga_campaign_evaluations
ADD COLUMN schema_version INT NOT NULL DEFAULT 1 AFTER eval_type;

-- 2. Espandi fix_type e scope_level da ENUM a VARCHAR per estensibilità
ALTER TABLE ga_generated_fixes
MODIFY COLUMN fix_type VARCHAR(50) NOT NULL,
MODIFY COLUMN scope_level VARCHAR(30) NOT NULL DEFAULT 'campaign';

-- 3. Nuove colonne per targeting annuncio specifico e PMax asset group
ALTER TABLE ga_generated_fixes
ADD COLUMN target_ad_index INT NULL AFTER ad_group_name,
ADD COLUMN asset_group_id_google VARCHAR(50) NULL AFTER ad_group_id_google;

-- 4. Tabella product performance per analisi Shopping/PMax
CREATE TABLE IF NOT EXISTS ga_product_performance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    sync_id INT NOT NULL,
    campaign_id_google VARCHAR(50),
    campaign_name VARCHAR(255),
    product_item_id VARCHAR(255),
    product_title VARCHAR(500),
    product_brand VARCHAR(255),
    product_category_l1 VARCHAR(255),
    product_type_l1 VARCHAR(255),
    clicks INT DEFAULT 0,
    impressions INT DEFAULT 0,
    cost DECIMAL(10,2) DEFAULT 0,
    conversions DECIMAL(10,2) DEFAULT 0,
    conversion_value DECIMAL(12,2) DEFAULT 0,
    ctr DECIMAL(5,2) DEFAULT 0,
    avg_cpc DECIMAL(8,4) DEFAULT 0,
    roas DECIMAL(8,2) DEFAULT 0,
    cpa DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_project_sync (project_id, sync_id),
    INDEX idx_product (product_item_id),
    INDEX idx_brand (product_brand)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
