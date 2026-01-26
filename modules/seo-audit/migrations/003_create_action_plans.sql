-- Migration: Create Action Plan tables
-- Module: seo-audit
-- Date: 2026-01-19
-- Description: Tabelle per Piano d'Azione AI con fix raggruppati per pagina

-- Tabella Piano d'Azione
CREATE TABLE IF NOT EXISTS sa_action_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    session_id INT NULL,

    -- Metriche piano
    total_pages INT DEFAULT 0,
    total_fixes INT DEFAULT 0,
    fixes_completed INT DEFAULT 0,
    health_current INT DEFAULT 0,
    health_expected INT DEFAULT 0,
    estimated_time_minutes INT DEFAULT 0,

    -- Stato
    status ENUM('generating', 'ready', 'in_progress', 'completed') DEFAULT 'generating',

    -- Meta
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES sa_projects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_project_session (project_id, session_id),
    INDEX idx_project_status (project_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella Fix per Pagina
CREATE TABLE IF NOT EXISTS sa_page_fixes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_id INT NOT NULL,
    project_id INT NOT NULL,
    page_id INT NOT NULL,
    issue_id INT NOT NULL,

    -- Fix generato
    fix_code TEXT NULL,
    fix_explanation TEXT NOT NULL,

    -- Metriche
    priority TINYINT DEFAULT 5,
    difficulty ENUM('facile', 'medio', 'difficile') DEFAULT 'medio',
    time_estimate_minutes INT DEFAULT 5,
    impact_points INT DEFAULT 1,
    step_order TINYINT DEFAULT 1,

    -- Stato
    is_completed BOOLEAN DEFAULT FALSE,
    completed_at TIMESTAMP NULL,

    -- Meta
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (plan_id) REFERENCES sa_action_plans(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES sa_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (page_id) REFERENCES sa_pages(id) ON DELETE CASCADE,
    FOREIGN KEY (issue_id) REFERENCES sa_issues(id) ON DELETE CASCADE,
    INDEX idx_plan_page (plan_id, page_id),
    INDEX idx_completed (plan_id, is_completed),
    INDEX idx_priority (plan_id, priority DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
