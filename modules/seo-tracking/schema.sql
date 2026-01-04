-- =============================================
-- SEO Position Tracking Module - Database Schema
-- Prefisso: st_
-- =============================================

-- =============================================
-- 1. PROGETTI
-- =============================================

CREATE TABLE IF NOT EXISTS st_projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    domain VARCHAR(500) NOT NULL,

    -- Stato connessioni
    gsc_connected BOOLEAN DEFAULT FALSE,
    ga4_connected BOOLEAN DEFAULT FALSE,

    -- Impostazioni sync
    sync_enabled BOOLEAN DEFAULT TRUE,
    last_sync_at TIMESTAMP NULL,
    sync_status ENUM('idle', 'running', 'completed', 'error') DEFAULT 'idle',

    -- Impostazioni report AI
    ai_reports_enabled BOOLEAN DEFAULT TRUE,
    weekly_report_day TINYINT DEFAULT 1 COMMENT '1=Lunedi, 7=Domenica',
    weekly_report_time TIME DEFAULT '08:00:00',
    monthly_report_day TINYINT DEFAULT 1 COMMENT 'Giorno del mese',

    -- Email notifiche
    notification_emails JSON NULL COMMENT 'Array di email per report/alert',

    -- Storico
    data_retention_months INT DEFAULT 16,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_sync (sync_enabled, last_sync_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 2. CONNESSIONE GSC (OAuth2)
-- =============================================

CREATE TABLE IF NOT EXISTS st_gsc_connections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL UNIQUE,

    -- OAuth tokens
    access_token TEXT NOT NULL,
    refresh_token TEXT NOT NULL,
    token_expires_at TIMESTAMP NOT NULL,

    -- Proprietà selezionata
    property_url VARCHAR(500) NOT NULL,
    property_type ENUM('URL_PREFIX', 'DOMAIN') NOT NULL,

    -- Stato
    is_active BOOLEAN DEFAULT TRUE,
    last_sync_at TIMESTAMP NULL,
    last_error TEXT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES st_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 3. CONNESSIONE GA4 (Service Account)
-- =============================================

CREATE TABLE IF NOT EXISTS st_ga4_connections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL UNIQUE,

    -- Service Account (JSON criptato)
    service_account_json TEXT NOT NULL COMMENT 'JSON criptato',

    -- Property GA4
    property_id VARCHAR(50) NOT NULL COMMENT 'es: 123456789',
    property_name VARCHAR(255) NULL,

    -- Stato
    is_active BOOLEAN DEFAULT TRUE,
    last_sync_at TIMESTAMP NULL,
    last_error TEXT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES st_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 4. KEYWORD MONITORATE
-- =============================================

CREATE TABLE IF NOT EXISTS st_keywords (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    keyword VARCHAR(500) NOT NULL,

    -- Categorizzazione
    keyword_group VARCHAR(100) NULL COMMENT 'Gruppo personalizzato',
    is_brand BOOLEAN DEFAULT FALSE,
    target_url VARCHAR(2000) NULL COMMENT 'URL target atteso',

    -- Priorità
    priority ENUM('high', 'medium', 'low') DEFAULT 'medium',

    -- Alert specifici
    alert_position_change INT DEFAULT 5 COMMENT 'Alert se variazione >= N posizioni',
    alert_enabled BOOLEAN DEFAULT TRUE,

    -- Ultima posizione nota (cache)
    last_position DECIMAL(5,2) NULL,
    last_clicks INT NULL,
    last_impressions INT NULL,
    last_ctr DECIMAL(5,4) NULL,
    last_updated_at TIMESTAMP NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES st_projects(id) ON DELETE CASCADE,
    UNIQUE KEY uk_project_keyword (project_id, keyword(255)),
    INDEX idx_group (project_id, keyword_group),
    INDEX idx_priority (project_id, priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 5. DATI STORICI GSC
-- =============================================

CREATE TABLE IF NOT EXISTS st_gsc_data (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,

    -- Dimensioni
    date DATE NOT NULL,
    query VARCHAR(500) NOT NULL,
    page VARCHAR(2000) NOT NULL,
    country VARCHAR(10) NOT NULL DEFAULT 'all',
    device ENUM('DESKTOP', 'MOBILE', 'TABLET', 'ALL') DEFAULT 'ALL',

    -- Metriche
    clicks INT DEFAULT 0,
    impressions INT DEFAULT 0,
    ctr DECIMAL(5,4) DEFAULT 0,
    position DECIMAL(5,2) DEFAULT 0,

    -- Link a keyword tracciata (se esiste)
    keyword_id INT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES st_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (keyword_id) REFERENCES st_keywords(id) ON DELETE SET NULL,

    UNIQUE KEY uk_data_point (project_id, date, query(255), page(255), country, device),
    INDEX idx_project_date (project_id, date),
    INDEX idx_project_query (project_id, query(255)),
    INDEX idx_project_page (project_id, page(255)),
    INDEX idx_keyword (keyword_id, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 6. SNAPSHOT GIORNALIERO POSIZIONI KEYWORD
-- =============================================

CREATE TABLE IF NOT EXISTS st_keyword_positions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    keyword_id INT NOT NULL,
    date DATE NOT NULL,

    -- Posizione aggregata (media pesata per impressions)
    avg_position DECIMAL(5,2) NOT NULL,
    best_position DECIMAL(5,2) NULL,

    -- Metriche aggregate
    total_clicks INT DEFAULT 0,
    total_impressions INT DEFAULT 0,
    avg_ctr DECIMAL(5,4) DEFAULT 0,

    -- Variazioni vs giorno precedente
    position_change DECIMAL(5,2) NULL COMMENT 'Positivo = miglioramento',
    clicks_change INT NULL,
    impressions_change INT NULL,

    -- Top landing pages per questa keyword
    top_pages JSON NULL COMMENT '[{url, clicks, position}]',

    -- Country breakdown
    country_data JSON NULL COMMENT '{IT: {clicks, position}, US: {...}}',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES st_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (keyword_id) REFERENCES st_keywords(id) ON DELETE CASCADE,

    UNIQUE KEY uk_keyword_date (keyword_id, date),
    INDEX idx_project_date (project_id, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 7. DATI GA4 PER LANDING PAGE
-- =============================================

CREATE TABLE IF NOT EXISTS st_ga4_data (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,

    -- Dimensioni
    date DATE NOT NULL,
    landing_page VARCHAR(2000) NOT NULL,
    source_medium VARCHAR(255) DEFAULT 'google / organic',
    country VARCHAR(10) NULL,
    device_category VARCHAR(50) NULL,

    -- Metriche traffico
    sessions INT DEFAULT 0,
    users INT DEFAULT 0,
    new_users INT DEFAULT 0,

    -- Metriche engagement
    avg_session_duration DECIMAL(10,2) DEFAULT 0 COMMENT 'Secondi',
    bounce_rate DECIMAL(5,4) DEFAULT 0,
    engagement_rate DECIMAL(5,4) DEFAULT 0,

    -- Metriche e-commerce
    add_to_carts INT DEFAULT 0,
    begin_checkouts INT DEFAULT 0,
    purchases INT DEFAULT 0,
    revenue DECIMAL(12,2) DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES st_projects(id) ON DELETE CASCADE,

    UNIQUE KEY uk_ga4_data (project_id, date, landing_page(255), source_medium, country, device_category),
    INDEX idx_project_date (project_id, date),
    INDEX idx_project_page (project_id, landing_page(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 8. AGGREGATI GIORNALIERI GA4
-- =============================================

CREATE TABLE IF NOT EXISTS st_ga4_daily (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    date DATE NOT NULL,

    -- Traffico
    sessions INT DEFAULT 0,
    users INT DEFAULT 0,
    new_users INT DEFAULT 0,

    -- Engagement
    avg_session_duration DECIMAL(10,2) DEFAULT 0,
    bounce_rate DECIMAL(5,4) DEFAULT 0,
    engagement_rate DECIMAL(5,4) DEFAULT 0,

    -- E-commerce
    add_to_carts INT DEFAULT 0,
    begin_checkouts INT DEFAULT 0,
    purchases INT DEFAULT 0,
    revenue DECIMAL(12,2) DEFAULT 0,

    -- Variazioni vs giorno precedente
    sessions_change INT NULL,
    revenue_change DECIMAL(12,2) NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES st_projects(id) ON DELETE CASCADE,

    UNIQUE KEY uk_project_date (project_id, date),
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 9. ATTRIBUZIONE REVENUE PER KEYWORD
-- =============================================

CREATE TABLE IF NOT EXISTS st_keyword_revenue (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    keyword_id INT NULL COMMENT 'NULL = keyword non tracciata',

    date DATE NOT NULL,
    query VARCHAR(500) NOT NULL,
    landing_page VARCHAR(2000) NOT NULL,

    -- Dati GSC
    clicks INT DEFAULT 0,
    impressions INT DEFAULT 0,
    position DECIMAL(5,2) DEFAULT 0,

    -- Dati GA4 attribuiti
    sessions INT DEFAULT 0,
    revenue DECIMAL(12,2) DEFAULT 0,
    purchases INT DEFAULT 0,
    add_to_carts INT DEFAULT 0,

    -- Metriche calcolate
    revenue_per_click DECIMAL(10,4) NULL,
    conversion_rate DECIMAL(5,4) NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES st_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (keyword_id) REFERENCES st_keywords(id) ON DELETE SET NULL,

    INDEX idx_project_date (project_id, date),
    INDEX idx_keyword (keyword_id, date),
    INDEX idx_revenue (project_id, date, revenue DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 10. CONFIGURAZIONE ALERT
-- =============================================

CREATE TABLE IF NOT EXISTS st_alert_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL UNIQUE,

    -- Alert posizione
    position_alert_enabled BOOLEAN DEFAULT TRUE,
    position_threshold INT DEFAULT 5 COMMENT 'Alert se variazione >= N',
    position_alert_keywords ENUM('all', 'tracked', 'high_priority') DEFAULT 'tracked',

    -- Alert traffico
    traffic_alert_enabled BOOLEAN DEFAULT TRUE,
    traffic_drop_threshold INT DEFAULT 20 COMMENT 'Alert se calo >= N%',

    -- Alert revenue
    revenue_alert_enabled BOOLEAN DEFAULT TRUE,
    revenue_drop_threshold INT DEFAULT 20 COMMENT 'Alert se calo >= N%',

    -- Alert anomalie AI
    anomaly_alert_enabled BOOLEAN DEFAULT TRUE,

    -- Notifiche
    email_enabled BOOLEAN DEFAULT TRUE,
    email_frequency ENUM('immediate', 'daily_digest', 'weekly_digest') DEFAULT 'daily_digest',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES st_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 11. LOG ALERT GENERATI
-- =============================================

CREATE TABLE IF NOT EXISTS st_alerts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,

    -- Tipo e severità
    alert_type ENUM(
        'position_drop',
        'position_gain',
        'traffic_drop',
        'traffic_spike',
        'revenue_drop',
        'revenue_spike',
        'keyword_lost',
        'keyword_new',
        'anomaly'
    ) NOT NULL,
    severity ENUM('critical', 'warning', 'info') NOT NULL,

    -- Dettagli
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,

    -- Contesto
    keyword_id INT NULL,
    query VARCHAR(500) NULL,
    page VARCHAR(2000) NULL,

    -- Valori
    previous_value DECIMAL(12,2) NULL,
    current_value DECIMAL(12,2) NULL,
    change_percent DECIMAL(5,2) NULL,

    -- AI insight (opzionale)
    ai_analysis TEXT NULL,
    ai_suggestion TEXT NULL,

    -- Stato
    status ENUM('new', 'read', 'actioned', 'dismissed') DEFAULT 'new',
    read_at TIMESTAMP NULL,

    -- Email
    email_sent BOOLEAN DEFAULT FALSE,
    email_sent_at TIMESTAMP NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES st_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (keyword_id) REFERENCES st_keywords(id) ON DELETE SET NULL,

    INDEX idx_project_status (project_id, status),
    INDEX idx_project_type (project_id, alert_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 12. REPORT AI GENERATI
-- =============================================

CREATE TABLE IF NOT EXISTS st_ai_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,

    -- Tipo report
    report_type ENUM(
        'weekly_digest',
        'monthly_executive',
        'keyword_analysis',
        'revenue_attribution',
        'anomaly_detection',
        'custom'
    ) NOT NULL,

    -- Periodo analizzato
    date_from DATE NOT NULL,
    date_to DATE NOT NULL,

    -- Contenuto
    title VARCHAR(255) NOT NULL,
    summary TEXT NOT NULL COMMENT 'Executive summary breve',
    content LONGTEXT NOT NULL COMMENT 'Report completo markdown/HTML',

    -- Metriche incluse (per reference)
    metrics_snapshot JSON NULL COMMENT 'Snapshot dati al momento generazione',

    -- Costi
    credits_used INT NOT NULL,

    -- Email
    email_sent BOOLEAN DEFAULT FALSE,
    email_sent_at TIMESTAMP NULL,
    email_recipients JSON NULL,

    -- Scheduling
    is_scheduled BOOLEAN DEFAULT FALSE COMMENT 'TRUE se generato da cron',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES st_projects(id) ON DELETE CASCADE,

    INDEX idx_project_type (project_id, report_type),
    INDEX idx_project_date (project_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 13. LOG SYNC
-- =============================================

CREATE TABLE IF NOT EXISTS st_sync_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,

    sync_type ENUM('gsc', 'ga4', 'full') NOT NULL,
    date_from DATE NOT NULL,
    date_to DATE NOT NULL,

    -- Risultati
    records_fetched INT DEFAULT 0,
    records_inserted INT DEFAULT 0,
    records_updated INT DEFAULT 0,

    -- Stato
    status ENUM('running', 'completed', 'failed', 'partial') NOT NULL,
    error_message TEXT NULL,

    -- Timing
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    duration_seconds INT NULL,

    FOREIGN KEY (project_id) REFERENCES st_projects(id) ON DELETE CASCADE,

    INDEX idx_project (project_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 14. AGGREGATI GSC GIORNALIERI (per performance)
-- =============================================

CREATE TABLE IF NOT EXISTS st_gsc_daily (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    date DATE NOT NULL,

    -- Metriche aggregate
    total_clicks INT DEFAULT 0,
    total_impressions INT DEFAULT 0,
    avg_ctr DECIMAL(5,4) DEFAULT 0,
    avg_position DECIMAL(5,2) DEFAULT 0,

    -- Conteggi
    unique_queries INT DEFAULT 0,
    unique_pages INT DEFAULT 0,

    -- Variazioni vs giorno precedente
    clicks_change INT NULL,
    impressions_change INT NULL,
    position_change DECIMAL(5,2) NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES st_projects(id) ON DELETE CASCADE,

    UNIQUE KEY uk_project_date (project_id, date),
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
