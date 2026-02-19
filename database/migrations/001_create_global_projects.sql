-- ============================================================
-- Migration 001: Global Projects Hub
-- Creates the central `projects` table and adds FK columns
-- to all 7 module project tables.
-- ============================================================

-- 1. Drop legacy projects table (empty, unused, different schema)
DROP TABLE IF EXISTS projects;

-- 2. Create Global Projects table
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    domain VARCHAR(500) NULL,
    description TEXT NULL,
    color VARCHAR(7) DEFAULT '#3B82F6',
    status ENUM('active', 'archived') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Add global_project_id FK to all module project tables

-- 3a. aic_projects (AI Content Generator)
ALTER TABLE aic_projects
    ADD COLUMN global_project_id INT NULL AFTER user_id,
    ADD INDEX idx_aic_global (global_project_id),
    ADD CONSTRAINT fk_aic_global FOREIGN KEY (global_project_id) REFERENCES projects(id) ON DELETE SET NULL;

-- 3b. sa_projects (SEO Audit)
ALTER TABLE sa_projects
    ADD COLUMN global_project_id INT NULL AFTER user_id,
    ADD INDEX idx_sa_global (global_project_id),
    ADD CONSTRAINT fk_sa_global FOREIGN KEY (global_project_id) REFERENCES projects(id) ON DELETE SET NULL;

-- 3c. st_projects (SEO Tracking)
ALTER TABLE st_projects
    ADD COLUMN global_project_id INT NULL AFTER user_id,
    ADD INDEX idx_st_global (global_project_id),
    ADD CONSTRAINT fk_st_global FOREIGN KEY (global_project_id) REFERENCES projects(id) ON DELETE SET NULL;

-- 3d. kr_projects (Keyword Research)
ALTER TABLE kr_projects
    ADD COLUMN global_project_id INT NULL AFTER user_id,
    ADD INDEX idx_kr_global (global_project_id),
    ADD CONSTRAINT fk_kr_global FOREIGN KEY (global_project_id) REFERENCES projects(id) ON DELETE SET NULL;

-- 3e. ga_projects (Google Ads Analyzer)
ALTER TABLE ga_projects
    ADD COLUMN global_project_id INT NULL AFTER user_id,
    ADD INDEX idx_ga_global (global_project_id),
    ADD CONSTRAINT fk_ga_global FOREIGN KEY (global_project_id) REFERENCES projects(id) ON DELETE SET NULL;

-- 3f. il_projects (Internal Links)
ALTER TABLE il_projects
    ADD COLUMN global_project_id INT NULL AFTER user_id,
    ADD INDEX idx_il_global (global_project_id),
    ADD CONSTRAINT fk_il_global FOREIGN KEY (global_project_id) REFERENCES projects(id) ON DELETE SET NULL;

-- 3g. cc_projects (Content Creator)
ALTER TABLE cc_projects
    ADD COLUMN global_project_id INT NULL AFTER user_id,
    ADD INDEX idx_cc_global (global_project_id),
    ADD CONSTRAINT fk_cc_global FOREIGN KEY (global_project_id) REFERENCES projects(id) ON DELETE SET NULL;
