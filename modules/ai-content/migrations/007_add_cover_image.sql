-- Migration 007: Aggiunge supporto immagine di copertina per articoli
-- Data: 2026-02-06

-- Colonna cover_image_path in aic_articles
ALTER TABLE aic_articles ADD COLUMN cover_image_path VARCHAR(500) DEFAULT NULL AFTER content;

-- Colonna generate_cover in aic_auto_config (default ON)
ALTER TABLE aic_auto_config ADD COLUMN generate_cover TINYINT(1) DEFAULT 1 AFTER auto_publish;

-- Step 'cover' nel ENUM di aic_process_jobs.current_step
ALTER TABLE aic_process_jobs MODIFY COLUMN current_step ENUM('pending','serp','scraping','brief','article','saving','cover','done') DEFAULT 'pending';
