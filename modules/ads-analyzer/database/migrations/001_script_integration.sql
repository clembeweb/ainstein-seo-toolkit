-- ============================================
-- Google Ads Script Integration
-- Aggiunge supporto per Google Ads Scripts
-- ============================================

-- Colonne aggiuntive su ga_projects per token API e config script
ALTER TABLE ga_projects
  ADD COLUMN api_token VARCHAR(64) DEFAULT NULL AFTER status,
  ADD COLUMN api_token_created_at TIMESTAMP NULL AFTER api_token,
  ADD COLUMN script_config JSON DEFAULT NULL AFTER api_token_created_at,
  ADD UNIQUE INDEX idx_api_token (api_token);

-- Storico esecuzioni script
CREATE TABLE IF NOT EXISTS ga_script_runs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    run_type ENUM('search_terms', 'campaign_performance', 'both') NOT NULL,
    status ENUM('received', 'processing', 'completed', 'error') DEFAULT 'received',
    items_received INT DEFAULT 0,
    error_message TEXT NULL,
    script_version VARCHAR(20) DEFAULT NULL,
    date_range_start DATE NULL,
    date_range_end DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_project (project_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dati campagne (Tool 2 - Campaign Performance)
CREATE TABLE IF NOT EXISTS ga_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    run_id INT NOT NULL,
    campaign_id_google VARCHAR(50) NOT NULL,
    campaign_name VARCHAR(500) NOT NULL,
    campaign_status VARCHAR(50) DEFAULT NULL,
    campaign_type VARCHAR(100) DEFAULT NULL,
    bidding_strategy VARCHAR(200) DEFAULT NULL,
    budget_amount DECIMAL(10,2) DEFAULT NULL,
    budget_type VARCHAR(50) DEFAULT NULL,
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
    INDEX idx_run (run_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dati annunci (Tool 2 - Campaign Performance)
CREATE TABLE IF NOT EXISTS ga_ads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    run_id INT NOT NULL,
    campaign_id_google VARCHAR(50) NOT NULL,
    campaign_name VARCHAR(500) DEFAULT NULL,
    ad_group_id_google VARCHAR(50) NOT NULL,
    ad_group_name VARCHAR(500) DEFAULT NULL,
    ad_type VARCHAR(100) DEFAULT NULL,
    headline1 VARCHAR(255) DEFAULT NULL,
    headline2 VARCHAR(255) DEFAULT NULL,
    headline3 VARCHAR(255) DEFAULT NULL,
    description1 TEXT DEFAULT NULL,
    description2 TEXT DEFAULT NULL,
    final_url TEXT DEFAULT NULL,
    path1 VARCHAR(100) DEFAULT NULL,
    path2 VARCHAR(100) DEFAULT NULL,
    ad_status VARCHAR(50) DEFAULT NULL,
    clicks INT DEFAULT 0,
    impressions INT DEFAULT 0,
    ctr DECIMAL(8,4) DEFAULT 0,
    avg_cpc DECIMAL(10,2) DEFAULT 0,
    cost DECIMAL(12,2) DEFAULT 0,
    conversions DECIMAL(10,2) DEFAULT 0,
    quality_score INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_project (project_id),
    INDEX idx_run (run_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dati estensioni (Tool 2 - Campaign Performance)
CREATE TABLE IF NOT EXISTS ga_extensions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    run_id INT NOT NULL,
    campaign_id_google VARCHAR(50) DEFAULT NULL,
    extension_type VARCHAR(100) NOT NULL,
    extension_text TEXT DEFAULT NULL,
    status VARCHAR(50) DEFAULT NULL,
    clicks INT DEFAULT 0,
    impressions INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_project (project_id),
    INDEX idx_run (run_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Valutazioni AI campagne (Tool 2)
CREATE TABLE IF NOT EXISTS ga_campaign_evaluations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    run_id INT NULL,
    name VARCHAR(255) NOT NULL,
    campaigns_evaluated INT DEFAULT 0,
    ads_evaluated INT DEFAULT 0,
    ai_response LONGTEXT NULL,
    status ENUM('pending', 'analyzing', 'completed', 'error') DEFAULT 'pending',
    error_message TEXT NULL,
    credits_used DECIMAL(5,1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    INDEX idx_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
