-- ============================================================
-- API LOGS: Sistema di logging per tutte le chiamate API esterne
-- Generated: 2026-02-03
--
-- Traccia chiamate a: DataForSEO, SerpAPI, Serper.dev, Google APIs
-- ============================================================

CREATE TABLE IF NOT EXISTS `api_logs` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT DEFAULT NULL,
  `module_slug` VARCHAR(50) NOT NULL COMMENT 'es: seo-tracking, ai-content, seo-audit',

  -- Provider info
  `provider` VARCHAR(50) NOT NULL COMMENT 'dataforseo, serpapi, serper, google_gsc, google_oauth',
  `endpoint` VARCHAR(255) NOT NULL COMMENT 'es: /serp/google/organic/live/regular',
  `method` VARCHAR(10) DEFAULT 'POST' COMMENT 'GET, POST, PUT, DELETE',

  -- Payload
  `request_payload` LONGTEXT COMMENT 'JSON request',
  `response_payload` LONGTEXT COMMENT 'JSON response (truncato se necessario)',
  `response_code` INT DEFAULT NULL COMMENT 'HTTP status code',

  -- Metriche
  `duration_ms` INT DEFAULT 0 COMMENT 'Tempo risposta in millisecondi',
  `cost` DECIMAL(10,6) DEFAULT 0.000000 COMMENT 'Costo chiamata USD',
  `credits_used` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Crediti piattaforma usati',

  -- Status
  `status` ENUM('success', 'error', 'rate_limited') NOT NULL DEFAULT 'success',
  `error_message` TEXT,

  -- Context
  `context` VARCHAR(500) DEFAULT NULL COMMENT 'es: keyword=test, project_id=5',
  `ip_address` VARCHAR(45) DEFAULT NULL,

  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  INDEX `idx_user` (`user_id`),
  INDEX `idx_provider` (`provider`),
  INDEX `idx_module` (`module_slug`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created` (`created_at`),
  INDEX `idx_provider_created` (`provider`, `created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Log di tutte le chiamate API esterne per debug e analisi costi';

-- ============================================================
-- VERIFICA POST-ESECUZIONE
-- ============================================================
/*
Run these queries to verify:

SELECT COUNT(*) FROM api_logs;

DESCRIBE api_logs;
*/
