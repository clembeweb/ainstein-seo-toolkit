-- Migration: Align ga_* tables with spec
-- Date: 2025-01-02
-- Description: Fix column mismatches between production DB and spec

-- =============================================
-- 1. ga_projects
-- =============================================
-- Add missing columns
ALTER TABLE ga_projects ADD COLUMN total_ad_groups INT DEFAULT 0 AFTER total_terms;
ALTER TABLE ga_projects ADD COLUMN total_negatives_found INT DEFAULT 0 AFTER total_ad_groups;

-- Extend status ENUM
ALTER TABLE ga_projects MODIFY COLUMN status ENUM('draft', 'uploaded', 'analyzing', 'analyzed', 'completed', 'exported', 'archived') DEFAULT 'draft';

-- =============================================
-- 2. ga_ad_groups
-- =============================================
-- Rename and add columns
ALTER TABLE ga_ad_groups CHANGE COLUMN total_terms terms_count INT DEFAULT 0;
ALTER TABLE ga_ad_groups ADD COLUMN zero_ctr_count INT DEFAULT 0 AFTER terms_count;
ALTER TABLE ga_ad_groups ADD COLUMN wasted_impressions INT DEFAULT 0 AFTER zero_ctr_count;
ALTER TABLE ga_ad_groups ADD COLUMN analysis_status ENUM('pending', 'analyzing', 'completed', 'error') DEFAULT 'pending' AFTER wasted_impressions;
ALTER TABLE ga_ad_groups ADD COLUMN analyzed_at TIMESTAMP NULL AFTER analysis_status;

-- =============================================
-- 3. ga_search_terms
-- =============================================
-- Rename and add columns
ALTER TABLE ga_search_terms CHANGE COLUMN is_negative_candidate is_zero_ctr TINYINT(1) DEFAULT 0;
ALTER TABLE ga_search_terms ADD COLUMN match_type VARCHAR(50) NULL AFTER term;
ALTER TABLE ga_search_terms ADD COLUMN conversions INT DEFAULT 0 AFTER cost;
ALTER TABLE ga_search_terms ADD COLUMN conversion_value DECIMAL(10,2) DEFAULT 0 AFTER conversions;
ALTER TABLE ga_search_terms MODIFY COLUMN ctr DECIMAL(5,4) DEFAULT 0;

-- =============================================
-- 4. ga_negative_categories
-- =============================================
-- Rename columns
ALTER TABLE ga_negative_categories CHANGE COLUMN name category_name VARCHAR(255) NOT NULL;
ALTER TABLE ga_negative_categories CHANGE COLUMN keyword_count keywords_count INT DEFAULT 0;

-- Add columns
ALTER TABLE ga_negative_categories ADD COLUMN ad_group_id INT NULL AFTER project_id;
ALTER TABLE ga_negative_categories ADD COLUMN category_key VARCHAR(100) NULL AFTER ad_group_id;
ALTER TABLE ga_negative_categories ADD COLUMN sort_order INT DEFAULT 0 AFTER keywords_count;

-- Add index
ALTER TABLE ga_negative_categories ADD INDEX idx_ad_group (ad_group_id);

-- Extend priority ENUM
ALTER TABLE ga_negative_categories MODIFY COLUMN priority ENUM('high', 'medium', 'low', 'evaluate') DEFAULT 'medium';

-- =============================================
-- 5. ga_negative_keywords
-- =============================================
-- Rename columns
ALTER TABLE ga_negative_keywords CHANGE COLUMN selected is_selected TINYINT(1) DEFAULT 1;
ALTER TABLE ga_negative_keywords CHANGE COLUMN match_type suggested_match_type ENUM('exact', 'phrase', 'broad') DEFAULT 'phrase';

-- =============================================
-- 6. ga_analysis_log (new table)
-- =============================================
CREATE TABLE IF NOT EXISTS ga_analysis_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    ad_group_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('success', 'error') NOT NULL,
    error_message TEXT NULL,
    terms_analyzed INT DEFAULT 0,
    categories_found INT DEFAULT 0,
    keywords_extracted INT DEFAULT 0,
    credits_used INT DEFAULT 0,
    duration_ms INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES ga_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (ad_group_id) REFERENCES ga_ad_groups(id) ON DELETE CASCADE,
    INDEX idx_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
