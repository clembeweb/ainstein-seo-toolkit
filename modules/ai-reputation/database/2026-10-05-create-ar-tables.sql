-- AI Reputation Radar - Schema database
-- Prefisso: ar_
-- Data: 2026-10-05 (design.md v0.2 §4)
-- Applicare: mysql -u root seo_toolkit < modules/ai-reputation/database/2026-10-05-create-ar-tables.sql

-- =============================================
-- PROGETTI (un soggetto monitorato)
-- =============================================
CREATE TABLE IF NOT EXISTS ar_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    global_project_id INT DEFAULT NULL,
    name VARCHAR(255) NOT NULL,
    subject_name VARCHAR(255) NOT NULL,
    subject_type ENUM('person', 'company') NOT NULL DEFAULT 'person',
    website VARCHAR(500) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    disambiguation_notes TEXT DEFAULT NULL,
    engines JSON DEFAULT NULL,
    repeats TINYINT UNSIGNED NOT NULL DEFAULT 1,
    status ENUM('active', 'archived') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_user (user_id),
    INDEX idx_global_project (global_project_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- PROFILO (righe da confermare)
-- =============================================
CREATE TABLE IF NOT EXISTS ar_profile_facts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    category ENUM('identity', 'activity', 'alias', 'person', 'fact', 'risk', 'homonym', 'source') NOT NULL,
    text TEXT NOT NULL,
    status ENUM('proposed', 'confirmed', 'rejected', 'corrected') NOT NULL DEFAULT 'proposed',
    corrected_text TEXT DEFAULT NULL,
    source_url VARCHAR(2000) DEFAULT NULL,
    origin ENUM('ai', 'manual', 'run') NOT NULL DEFAULT 'ai',
    run_id INT DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_project (project_id),
    INDEX idx_status (project_id, status),
    FOREIGN KEY (project_id) REFERENCES ar_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- PROMPT (domande monitorate)
-- =============================================
CREATE TABLE IF NOT EXISTS ar_prompts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    cluster ENUM('nav', 'rep', 'comm', 'comp') NOT NULL DEFAULT 'nav',
    lang VARCHAR(5) NOT NULL DEFAULT 'it',
    persona VARCHAR(50) NOT NULL DEFAULT 'neutro',
    text TEXT NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    origin ENUM('ai', 'manual') NOT NULL DEFAULT 'manual',
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_project (project_id),
    INDEX idx_active (project_id, is_active),
    FOREIGN KEY (project_id) REFERENCES ar_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- RUN (una esecuzione del collector)
-- =============================================
CREATE TABLE IF NOT EXISTS ar_runs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('pending', 'running', 'completed', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
    engines JSON DEFAULT NULL,
    repeats TINYINT UNSIGNED NOT NULL DEFAULT 1,
    prompts_total INT NOT NULL DEFAULT 0,
    responses_total INT NOT NULL DEFAULT 0,
    responses_done INT NOT NULL DEFAULT 0,
    responses_error INT NOT NULL DEFAULT 0,
    analyses_done INT NOT NULL DEFAULT 0,
    cost_total DECIMAL(10, 4) NOT NULL DEFAULT 0,
    credits_used DECIMAL(10, 2) NOT NULL DEFAULT 0,
    error_message TEXT DEFAULT NULL,
    started_at TIMESTAMP NULL DEFAULT NULL,
    finished_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_project (project_id),
    INDEX idx_status (status),
    FOREIGN KEY (project_id) REFERENCES ar_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- RISPOSTE GREZZE (una per prompt x engine x repeat)
-- =============================================
CREATE TABLE IF NOT EXISTS ar_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    run_id INT NOT NULL,
    project_id INT NOT NULL,
    prompt_id INT NOT NULL,
    engine VARCHAR(30) NOT NULL,
    model VARCHAR(100) DEFAULT NULL,
    repeat_idx TINYINT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('pending', 'ok', 'error') NOT NULL DEFAULT 'pending',
    text LONGTEXT DEFAULT NULL,
    citations JSON DEFAULT NULL,
    sources_read JSON DEFAULT NULL,
    queries JSON DEFAULT NULL,
    search_count INT DEFAULT NULL,
    raw JSON DEFAULT NULL,
    tokens_in INT DEFAULT NULL,
    tokens_out INT DEFAULT NULL,
    cost DECIMAL(10, 6) DEFAULT NULL,
    cost_is_real TINYINT(1) NOT NULL DEFAULT 0,
    latency_ms INT DEFAULT NULL,
    error_message TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_run (run_id),
    INDEX idx_project (project_id),
    INDEX idx_prompt (prompt_id),
    INDEX idx_run_engine (run_id, engine),
    FOREIGN KEY (run_id) REFERENCES ar_runs(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES ar_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (prompt_id) REFERENCES ar_prompts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- GIUDIZI (judge, una per risposta)
-- =============================================
CREATE TABLE IF NOT EXISTS ar_analyses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    response_id INT NOT NULL,
    run_id INT NOT NULL,
    project_id INT NOT NULL,
    brand_mentioned TINYINT(1) NOT NULL DEFAULT 0,
    mention_position INT DEFAULT NULL,
    is_homonym ENUM('no', 'yes', 'uncertain') NOT NULL DEFAULT 'no',
    sentiment TINYINT NOT NULL DEFAULT 0,
    claims JSON DEFAULT NULL,
    competitors JSON DEFAULT NULL,
    negative TINYINT(1) NOT NULL DEFAULT 0,
    negative_reasons JSON DEFAULT NULL,
    negative_urls JSON DEFAULT NULL,
    cited_domains JSON DEFAULT NULL,
    citations_noise JSON DEFAULT NULL,
    judge_model VARCHAR(100) DEFAULT NULL,
    raw JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_response (response_id),
    INDEX idx_run (run_id),
    INDEX idx_project (project_id),
    FOREIGN KEY (response_id) REFERENCES ar_responses(id) ON DELETE CASCADE,
    FOREIGN KEY (run_id) REFERENCES ar_runs(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES ar_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- FONTI AGGREGATE PER DOMINIO (Source Map)
-- =============================================
CREATE TABLE IF NOT EXISTS ar_sources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    domain VARCHAR(255) NOT NULL,
    citations_count INT NOT NULL DEFAULT 0,
    negative_count INT NOT NULL DEFAULT 0,
    sentiment_avg DECIMAL(4, 2) DEFAULT NULL,
    first_seen_run_id INT DEFAULT NULL,
    last_seen_run_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_project_domain (project_id, domain),
    FOREIGN KEY (project_id) REFERENCES ar_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- COMPETITOR EMERSI
-- =============================================
CREATE TABLE IF NOT EXISTS ar_competitors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    mentions_count INT NOT NULL DEFAULT 0,
    first_seen_run_id INT DEFAULT NULL,
    last_seen_run_id INT DEFAULT NULL,
    is_confirmed TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_project_name (project_id, name),
    FOREIGN KEY (project_id) REFERENCES ar_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- PIANO D'AZIONE
-- =============================================
CREATE TABLE IF NOT EXISTS ar_actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    run_id INT NOT NULL,
    type ENUM('removal', 'gap_article', 'correction', 'counter_content') NOT NULL,
    target_url VARCHAR(2000) DEFAULT NULL,
    target_domain VARCHAR(255) DEFAULT NULL,
    title VARCHAR(500) NOT NULL,
    rationale TEXT DEFAULT NULL,
    status ENUM('proposed', 'accepted', 'done', 'dismissed') NOT NULL DEFAULT 'proposed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_project (project_id),
    INDEX idx_run (run_id),
    FOREIGN KEY (project_id) REFERENCES ar_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (run_id) REFERENCES ar_runs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- REGISTRAZIONE MODULO
-- =============================================
INSERT INTO modules (slug, name, description, version, is_active)
VALUES ('ai-reputation', 'AI Reputation Radar', 'Monitora cosa dicono ChatGPT, Gemini e le altre AI di un soggetto: fonti, rischi e piano d''azione', '0.1.0', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), version = VALUES(version);
