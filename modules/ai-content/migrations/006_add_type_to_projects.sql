-- Migration: 006_add_type_to_projects
-- Aggiunge 'meta-tag' all'ENUM type esistente o crea la colonna se non esiste
-- Data: 2026-01-30

-- Se la colonna type non esiste, la crea
-- Se esiste, la modifica per includere meta-tag
-- Usiamo MODIFY che funziona in entrambi i casi su MySQL

ALTER TABLE aic_projects
MODIFY COLUMN type ENUM('manual', 'auto', 'meta-tag') NOT NULL DEFAULT 'manual';
