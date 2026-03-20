-- Migration: Campaign Creator
-- Aggiunge la modalita "Campaign Creator" per generare campagne Google Ads
-- complete (Search/PMax) con AI partendo da brief + landing page.

-- 1. Estendere ENUM type su ga_projects
ALTER TABLE ga_projects MODIFY COLUMN `type`
  ENUM('negative-kw', 'campaign', 'campaign-creator') NOT NULL DEFAULT 'campaign';

-- 2. Colonne aggiuntive su ga_projects per Campaign Creator
ALTER TABLE ga_projects
  ADD COLUMN landing_url VARCHAR(2048) NULL AFTER description,
  ADD COLUMN campaign_type_gads ENUM('search', 'pmax') NULL AFTER landing_url,
  ADD COLUMN brief TEXT NULL AFTER campaign_type_gads,
  ADD COLUMN scraped_content LONGTEXT NULL AFTER brief,
  ADD COLUMN scraped_context TEXT NULL AFTER scraped_content;

-- 3. Tracking generazioni AI (keyword research + campaign generation)
CREATE TABLE ga_creator_generations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    step ENUM('keywords', 'campaign') NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'error') DEFAULT 'pending',
    ai_response LONGTEXT NULL,
    credits_used DECIMAL(5,1) DEFAULT 0,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    INDEX idx_project_step (project_id, step),
    INDEX idx_user (user_id)
);

-- 4. Keywords generate (editabili dall'utente prima della generazione campagna)
CREATE TABLE ga_creator_keywords (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    generation_id INT NOT NULL,
    keyword TEXT NOT NULL,
    match_type ENUM('broad', 'phrase', 'exact') DEFAULT 'broad',
    ad_group_name VARCHAR(255) NULL,
    intent VARCHAR(50) NULL,
    is_negative TINYINT(1) DEFAULT 0,
    is_selected TINYINT(1) DEFAULT 1,
    reason VARCHAR(500) NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_project (project_id),
    INDEX idx_generation (generation_id)
);

-- 5. Campagna generata (JSON con tutti gli asset)
CREATE TABLE ga_creator_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    generation_id INT NOT NULL,
    campaign_type ENUM('search', 'pmax') NOT NULL,
    campaign_name VARCHAR(255) NOT NULL,
    assets_json LONGTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_project (project_id),
    INDEX idx_generation (generation_id)
);
