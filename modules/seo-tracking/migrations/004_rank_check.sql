-- =============================================
-- Rank Check - Storico verifiche posizioni SERP
-- Migration 004 - SEO Tracking Module
-- =============================================

-- Storico check posizioni SERP via SerpAPI
CREATE TABLE IF NOT EXISTS st_rank_checks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    keyword VARCHAR(500) NOT NULL,
    target_domain VARCHAR(255) NOT NULL,
    location VARCHAR(100) DEFAULT 'Italy',
    language VARCHAR(10) DEFAULT 'it',
    device ENUM('desktop', 'mobile') DEFAULT 'desktop',

    -- Risultato SERP
    serp_position INT NULL COMMENT 'NULL = non trovato in top 100',
    serp_url VARCHAR(2000) NULL,
    serp_title VARCHAR(500) NULL,
    serp_snippet TEXT NULL,

    -- Confronto con GSC
    gsc_position DECIMAL(5,2) NULL,
    position_diff INT NULL COMMENT 'SERP - GSC (negativo = SERP migliore)',

    -- Meta
    total_organic_results INT NULL,
    credits_used DECIMAL(5,2) DEFAULT 1,
    checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES st_projects(id) ON DELETE CASCADE,
    INDEX idx_project_kw (project_id, keyword(255)),
    INDEX idx_user (user_id),
    INDEX idx_date (checked_at),
    INDEX idx_domain (target_domain)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
