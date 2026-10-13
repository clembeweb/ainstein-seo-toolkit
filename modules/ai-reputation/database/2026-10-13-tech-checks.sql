-- GEO Audit 2026-10-13: diagnosi tecnica (crawler AI, dati strutturati, fatti visibili). Spec:
-- docs/superpowers/specs/2026-10-13-ai-reputation-diagnosi-tecnica-design.md
CREATE TABLE IF NOT EXISTS ar_tech_checks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    run_id INT NULL,
    url VARCHAR(500) NOT NULL,
    result JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_project (project_id, id),
    INDEX idx_run (run_id),
    FOREIGN KEY (project_id) REFERENCES ar_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
