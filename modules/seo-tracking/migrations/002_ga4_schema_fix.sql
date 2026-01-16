-- GA4 Schema Fix
-- Eseguire per allineare DB con codice GA4

-- 1. Aggiunge colonne per cache token JWT in st_ga4_connections
ALTER TABLE st_ga4_connections
    ADD COLUMN access_token TEXT NULL AFTER service_account_json,
    ADD COLUMN token_expires_at TIMESTAMP NULL AFTER access_token;

-- 2. Aggiunge unique index per upsert su st_ga4_data
-- Verifica se esiste già
SET @exists = (SELECT COUNT(*) FROM information_schema.STATISTICS
               WHERE table_schema = DATABASE()
               AND table_name = 'st_ga4_data'
               AND index_name = 'idx_ga4_data_unique');

SET @sql = IF(@exists = 0,
    'ALTER TABLE st_ga4_data ADD UNIQUE INDEX idx_ga4_data_unique (project_id, date, landing_page(255))',
    'SELECT "Index already exists"');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Verifica struttura finale
-- DESCRIBE st_ga4_connections;
-- DESCRIBE st_ga4_data;
