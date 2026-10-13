-- Controllo AI gratuito della home (landing in stile Semrush), 2026-10-13
-- Design: docs/plans/2026-10-13-landing-semrush-design.md
CREATE TABLE IF NOT EXISTS public_ai_checks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token CHAR(32) NOT NULL UNIQUE,
    url VARCHAR(500) NOT NULL,
    host VARCHAR(255) NOT NULL,
    result LONGTEXT NOT NULL,
    score TINYINT NOT NULL DEFAULT 0,
    ip_hash CHAR(64) NOT NULL,
    user_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip (ip_hash, created_at),
    INDEX idx_host (host, created_at),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
