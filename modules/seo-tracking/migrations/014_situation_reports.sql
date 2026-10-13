-- AI Rank: "Punto della situazione" (GA4 + Search Console + AI), 2026-10-13
-- Spec: docs/specs/seo-tracking-punto-situazione.md
CREATE TABLE IF NOT EXISTS st_situation_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    period_key VARCHAR(20) NOT NULL,
    b_start DATE NOT NULL,
    b_end DATE NOT NULL,
    data LONGTEXT NOT NULL,
    narrative LONGTEXT NOT NULL,
    credits_used DECIMAL(8,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_project (project_id, created_at),
    CONSTRAINT fk_st_situation_project FOREIGN KEY (project_id) REFERENCES st_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
