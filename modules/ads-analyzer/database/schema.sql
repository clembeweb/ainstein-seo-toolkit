-- ============================================================
-- Google Ads Analyzer - Schema Consolidato
-- Generato da: migrations 001-012
-- Ultimo aggiornamento: 2026-03-10
-- Prefisso: ga_
-- ============================================================

-- Progetti Ads Analyzer
CREATE TABLE IF NOT EXISTS ga_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    google_ads_customer_id VARCHAR(20) NULL,
    google_ads_account_name VARCHAR(255) NULL,
    login_customer_id VARCHAR(20) NULL,
    oauth_token_id INT NULL,
    type ENUM('negative-kw','campaign','campaign-creator') NOT NULL DEFAULT 'campaign',
    name VARCHAR(255) NOT NULL,
    description TEXT,
    landing_url VARCHAR(2048) NULL,
    campaign_type_gads ENUM('search','pmax') NULL,
    input_mode ENUM('url','brief','both') DEFAULT 'url',
    brief TEXT NULL,
    scraped_content LONGTEXT NULL,
    scraped_context TEXT NULL,
    business_context TEXT,
    total_terms INT DEFAULT 0,
    total_ad_groups INT DEFAULT 0,
    total_negatives_found INT DEFAULT 0,
    status ENUM('draft','analyzing','completed','archived') DEFAULT 'draft',
    auto_evaluate TINYINT(1) DEFAULT 0,
    last_sync_at TIMESTAMP NULL,
    sync_enabled TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_user (user_id),
    KEY idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sincronizzazioni Google Ads API
CREATE TABLE IF NOT EXISTS ga_syncs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    sync_type ENUM('manual','cron','on_demand') DEFAULT 'manual',
    status ENUM('running','completed','error') DEFAULT 'running',
    date_range_start DATE NULL,
    date_range_end DATE NULL,
    campaigns_synced INT DEFAULT 0,
    ad_groups_synced INT DEFAULT 0,
    keywords_synced INT DEFAULT 0,
    ads_synced INT DEFAULT 0,
    extensions_synced INT DEFAULT 0,
    search_terms_synced INT DEFAULT 0,
    error_message TEXT NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    KEY idx_project (project_id),
    CONSTRAINT fk_syncs_project FOREIGN KEY (project_id) REFERENCES ga_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Campagne sincronizzate
CREATE TABLE IF NOT EXISTS ga_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    google_ads_id BIGINT NULL,
    project_id INT NOT NULL,
    sync_id INT NOT NULL,
    campaign_id_google VARCHAR(50) NOT NULL,
    campaign_name VARCHAR(500) NOT NULL,
    campaign_status VARCHAR(50),
    campaign_type VARCHAR(100),
    bidding_strategy VARCHAR(200),
    budget_amount DECIMAL(10,2),
    budget_type VARCHAR(50),
    clicks INT DEFAULT 0,
    impressions INT DEFAULT 0,
    ctr DECIMAL(8,4) DEFAULT 0,
    avg_cpc DECIMAL(10,2) DEFAULT 0,
    cost DECIMAL(12,2) DEFAULT 0,
    conversions DECIMAL(10,2) DEFAULT 0,
    conversion_value DECIMAL(12,2) DEFAULT 0,
    conv_rate DECIMAL(8,4) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_sync (sync_id),
    KEY idx_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ad Groups sincronizzati (dati campagna)
CREATE TABLE IF NOT EXISTS ga_campaign_ad_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    google_ads_id BIGINT NULL,
    project_id INT NOT NULL,
    sync_id INT NOT NULL,
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
    KEY idx_sync (sync_id),
    KEY idx_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Keywords per Ad Group (dati campagna)
CREATE TABLE IF NOT EXISTS ga_ad_group_keywords (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    sync_id INT NOT NULL,
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
    quality_score INT NULL,
    first_page_cpc DECIMAL(10,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_sync (sync_id),
    KEY idx_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Annunci sincronizzati
CREATE TABLE IF NOT EXISTS ga_ads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    google_ads_id BIGINT NULL,
    project_id INT NOT NULL,
    sync_id INT NOT NULL,
    campaign_id_google VARCHAR(50) NOT NULL,
    campaign_name VARCHAR(500),
    ad_group_id_google VARCHAR(50) NOT NULL,
    ad_group_name VARCHAR(500),
    ad_type VARCHAR(100),
    headline1 VARCHAR(255),
    headline2 VARCHAR(255),
    headline3 VARCHAR(255),
    description1 TEXT,
    description2 TEXT,
    final_url TEXT,
    path1 VARCHAR(100),
    path2 VARCHAR(100),
    ad_status VARCHAR(50),
    clicks INT DEFAULT 0,
    impressions INT DEFAULT 0,
    ctr DECIMAL(8,4) DEFAULT 0,
    avg_cpc DECIMAL(10,2) DEFAULT 0,
    cost DECIMAL(12,2) DEFAULT 0,
    conversions DECIMAL(10,2) DEFAULT 0,
    quality_score INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_sync (sync_id),
    KEY idx_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Estensioni annuncio
CREATE TABLE IF NOT EXISTS ga_extensions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    sync_id INT NOT NULL,
    campaign_id_google VARCHAR(50),
    extension_type VARCHAR(100) NOT NULL,
    extension_text TEXT,
    status VARCHAR(50),
    clicks INT DEFAULT 0,
    impressions INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_sync (sync_id),
    KEY idx_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Valutazioni AI campagne
CREATE TABLE IF NOT EXISTS ga_campaign_evaluations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    sync_id INT NULL,
    eval_type ENUM('manual','auto') DEFAULT 'manual',
    previous_eval_id INT NULL,
    name VARCHAR(255) NOT NULL,
    campaigns_evaluated INT DEFAULT 0,
    ads_evaluated INT DEFAULT 0,
    ad_groups_evaluated INT DEFAULT 0,
    keywords_evaluated INT DEFAULT 0,
    landing_pages_analyzed INT DEFAULT 0,
    ai_response LONGTEXT NULL,
    metric_deltas JSON NULL,
    campaigns_filter JSON NULL,
    status ENUM('pending','analyzing','completed','error') DEFAULT 'pending',
    error_message TEXT NULL,
    credits_used DECIMAL(5,1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    KEY idx_project (project_id),
    KEY idx_sync (sync_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Coda auto-valutazione
CREATE TABLE IF NOT EXISTS ga_auto_eval_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    sync_id INT NOT NULL,
    status ENUM('pending','processing','completed','skipped','error') DEFAULT 'pending',
    skip_reason VARCHAR(255) NULL,
    attempts INT DEFAULT 0,
    error_message TEXT NULL,
    scheduled_for TIMESTAMP NOT NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_sync (sync_id),
    KEY idx_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ad Groups per analisi keyword negative
CREATE TABLE IF NOT EXISTS ga_ad_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    sync_id INT NULL,
    name VARCHAR(255) NOT NULL,
    landing_url VARCHAR(500),
    scraped_content TEXT,
    extracted_context TEXT,
    terms_count INT DEFAULT 0,
    zero_ctr_count INT DEFAULT 0,
    wasted_impressions INT DEFAULT 0,
    analysis_status ENUM('pending','analyzing','completed','error') DEFAULT 'pending',
    analyzed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_project (project_id),
    KEY idx_sync (sync_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Search terms per analisi
CREATE TABLE IF NOT EXISTS ga_search_terms (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    sync_id INT NULL,
    ad_group_id INT NOT NULL,
    term VARCHAR(500) NOT NULL,
    match_type VARCHAR(50),
    clicks INT DEFAULT 0,
    impressions INT DEFAULT 0,
    ctr DECIMAL(5,4) DEFAULT 0,
    cost DECIMAL(10,2) DEFAULT 0,
    conversions INT DEFAULT 0,
    conversion_value DECIMAL(10,2) DEFAULT 0,
    is_zero_ctr TINYINT(1) DEFAULT 0,
    campaign_name VARCHAR(255) NULL,
    ad_group_name VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_project (project_id),
    KEY idx_ad_group (ad_group_id),
    KEY idx_sync (sync_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Analisi keyword negative
CREATE TABLE IF NOT EXISTS ga_analyses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    sync_id INT NULL,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    business_context TEXT,
    context_mode ENUM('manual','auto','mixed') DEFAULT 'manual',
    ad_groups_analyzed INT DEFAULT 0,
    total_categories INT DEFAULT 0,
    total_keywords INT DEFAULT 0,
    credits_used INT DEFAULT 0,
    status ENUM('draft','analyzing','completed','error') DEFAULT 'draft',
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    KEY idx_project (project_id),
    KEY idx_sync (sync_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Categorie keyword negative
CREATE TABLE IF NOT EXISTS ga_negative_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    ad_group_id INT NOT NULL,
    analysis_id INT NULL,
    category_key VARCHAR(100) NOT NULL,
    category_name VARCHAR(255) NOT NULL,
    description TEXT,
    priority ENUM('high','medium','evaluate') DEFAULT 'medium',
    keywords_count INT DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_project (project_id),
    KEY idx_ad_group (ad_group_id),
    KEY idx_analysis (analysis_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Keyword negative suggerite
CREATE TABLE IF NOT EXISTS ga_negative_keywords (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    ad_group_id INT NOT NULL,
    category_id INT NOT NULL,
    analysis_id INT NULL,
    keyword VARCHAR(255) NOT NULL,
    is_selected TINYINT(1) DEFAULT 1,
    suggested_match_type ENUM('exact','phrase','broad') DEFAULT 'phrase',
    suggested_level ENUM('campaign','ad_group') DEFAULT 'campaign',
    suggested_campaign_resource VARCHAR(255) NULL,
    suggested_ad_group_resource VARCHAR(255) NULL,
    applied_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_project (project_id),
    KEY idx_category (category_id),
    KEY idx_analysis (analysis_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Contesti business salvati
CREATE TABLE IF NOT EXISTS ga_saved_contexts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    context TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Campaign Creator: generazioni AI
CREATE TABLE IF NOT EXISTS ga_creator_generations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    step ENUM('keywords','campaign') NOT NULL,
    status ENUM('pending','processing','completed','error') DEFAULT 'pending',
    ai_response LONGTEXT NULL,
    credits_used DECIMAL(5,1) DEFAULT 0,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    KEY idx_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Campaign Creator: keywords generate
CREATE TABLE IF NOT EXISTS ga_creator_keywords (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    generation_id INT NOT NULL,
    keyword TEXT NOT NULL,
    match_type ENUM('broad','phrase','exact') DEFAULT 'broad',
    ad_group_name VARCHAR(255) NULL,
    intent VARCHAR(50) NULL,
    search_volume INT NULL,
    cpc DECIMAL(10,2) NULL,
    competition_level VARCHAR(20) NULL,
    competition_index INT NULL,
    is_negative TINYINT(1) DEFAULT 0,
    is_selected TINYINT(1) DEFAULT 1,
    reason VARCHAR(500) NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_project (project_id),
    KEY idx_generation (generation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Campaign Creator: campagne generate
CREATE TABLE IF NOT EXISTS ga_creator_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    generation_id INT NOT NULL,
    campaign_type ENUM('search','pmax') NOT NULL,
    campaign_name VARCHAR(255) NOT NULL,
    assets_json LONGTEXT NOT NULL,
    published_to_google_ads TINYINT(1) DEFAULT 0,
    google_ads_campaign_id VARCHAR(255) NULL,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_project (project_id),
    KEY idx_generation (generation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tracking utilizzo API Google Ads
CREATE TABLE IF NOT EXISTS ga_api_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    operations_count INT DEFAULT 0,
    UNIQUE KEY uq_user_date (user_id, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
