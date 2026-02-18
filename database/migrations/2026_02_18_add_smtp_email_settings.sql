-- Migration: Aggiunge settings SMTP per il servizio email
-- Data: 2026-02-18

-- Inserisci settings SMTP (solo se non esistono gia)
INSERT IGNORE INTO settings (key_name, value, updated_by) VALUES
('smtp_host', '', NULL),
('smtp_port', '465', NULL),
('smtp_username', '', NULL),
('smtp_password', '', NULL),
('smtp_from_email', '', NULL),
('smtp_from_name', 'Ainstein', NULL);

-- Tabella password_resets (se non esiste gia)
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
