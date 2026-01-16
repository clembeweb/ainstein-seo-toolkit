-- ============================================================
-- SEO-TRACKING: Fix Schema Alignment
-- Generated: 2026-01-07
-- Description: Allinea schema DB ai valori usati nel codice PHP
-- ============================================================

-- BACKUP CONSIGLIATO PRIMA DI ESEGUIRE:
-- mysqldump -u root seo_toolkit st_alerts st_ai_reports st_gsc_data st_sync_log st_projects > backup_before_fix.sql

-- ============================================================
-- 1. FIX st_alerts.severity
-- DB attuale: ENUM('critical','warning','info')
-- Codice usa: 'critical', 'high', 'medium' (warning/info mai usati)
-- ============================================================
ALTER TABLE st_alerts
MODIFY COLUMN severity ENUM('critical','high','medium','warning','info') NOT NULL;

-- ============================================================
-- 2. FIX st_ai_reports.report_type
-- DB attuale: ENUM('weekly_digest','monthly_executive','keyword_analysis','revenue_attribution','anomaly_detection','custom')
-- Codice usa: 'anomaly_analysis' invece di 'anomaly_detection'
-- ============================================================
ALTER TABLE st_ai_reports
MODIFY COLUMN report_type ENUM('weekly_digest','monthly_executive','keyword_analysis','revenue_attribution','anomaly_detection','anomaly_analysis','custom') NOT NULL;

-- ============================================================
-- 3. FIX st_gsc_data.query e st_gsc_data.page
-- Problema: Entrambi NOT NULL, ma insert separati (query-only o page-only)
-- Soluzione: Rendere nullable con default stringa vuota
-- ============================================================
ALTER TABLE st_gsc_data
MODIFY COLUMN query VARCHAR(500) NULL DEFAULT '',
MODIFY COLUMN page VARCHAR(2000) NULL DEFAULT '';

-- ============================================================
-- 4. FIX st_sync_log - già corretto in sessione precedente
-- sync_type: aggiunto 'gsc_daily','ga4_daily','gsc_full'
-- date_from/date_to: resi nullable
-- Verifica che siano ancora OK:
-- ============================================================
-- (Nessuna modifica necessaria, già applicato)

-- ============================================================
-- 5. FIX st_projects.sync_status - già corretto in sessione precedente
-- Aggiunto 'failed' all'ENUM
-- ============================================================
-- (Nessuna modifica necessaria, già applicato)

-- ============================================================
-- 6. FIX st_gsc_connections.property_url e property_type
-- Già resi nullable in sessione precedente
-- ============================================================
-- (Nessuna modifica necessaria, già applicato)

-- ============================================================
-- VERIFICA FINALE - Esegui dopo le modifiche
-- ============================================================
-- SHOW COLUMNS FROM st_alerts WHERE Field = 'severity';
-- SHOW COLUMNS FROM st_ai_reports WHERE Field = 'report_type';
-- SHOW COLUMNS FROM st_gsc_data WHERE Field IN ('query', 'page');

-- ============================================================
-- ROLLBACK (se necessario)
-- ============================================================
-- ALTER TABLE st_alerts MODIFY COLUMN severity ENUM('critical','warning','info') NOT NULL;
-- ALTER TABLE st_ai_reports MODIFY COLUMN report_type ENUM('weekly_digest','monthly_executive','keyword_analysis','revenue_attribution','anomaly_detection','custom') NOT NULL;
-- ALTER TABLE st_gsc_data MODIFY COLUMN query VARCHAR(500) NOT NULL, MODIFY COLUMN page VARCHAR(2000) NOT NULL;
