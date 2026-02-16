-- Migration 005: Rimuovi GSC e AI Analysis dal modulo SEO Audit
-- Data: 2026-02-16
-- Motivo: GSC duplica funzionalità del modulo SEO Tracking, AI Analysis ridondante con Piano d'Azione

-- Rimuovi tabelle GSC (ordine inverso per FK)
DROP TABLE IF EXISTS sa_gsc_sync_log;
DROP TABLE IF EXISTS sa_gsc_mobile_usability;
DROP TABLE IF EXISTS sa_gsc_core_web_vitals;
DROP TABLE IF EXISTS sa_gsc_coverage;
DROP TABLE IF EXISTS sa_gsc_performance;
DROP TABLE IF EXISTS sa_gsc_connections;

-- Rimuovi tabella analisi AI (Piano d'Azione resta)
DROP TABLE IF EXISTS sa_ai_analyses;

-- Rimuovi colonne GSC da sa_projects (MySQL 8.0 non supporta DROP COLUMN IF EXISTS)
-- Verificare esistenza prima di eseguire manualmente se necessario
ALTER TABLE sa_projects DROP COLUMN gsc_connected;
ALTER TABLE sa_projects DROP COLUMN gsc_property;

-- Rimuovi issues provenienti da GSC
DELETE FROM sa_issues WHERE source = 'gsc';
