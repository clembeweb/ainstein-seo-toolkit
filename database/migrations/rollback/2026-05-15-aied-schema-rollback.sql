-- =====================================================================
-- Rollback schema Ainstein Editorial (aied_*) — Milestone M1.1
-- DROP in ordine inverso rispetto alle foreign key.
--
-- Esecuzione: mysql seo_toolkit < 2026-05-15-aied-schema-rollback.sql
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS aied_email_log;
DROP TABLE IF EXISTS aied_api_logs;
DROP TABLE IF EXISTS aied_internal_links;
DROP TABLE IF EXISTS aied_articles;
DROP TABLE IF EXISTS aied_editorial_items;
DROP TABLE IF EXISTS aied_editorial_plans;
DROP TABLE IF EXISTS aied_content_brain;
DROP TABLE IF EXISTS aied_actions_log;
DROP TABLE IF EXISTS aied_sites;
DROP TABLE IF EXISTS aied_users;

SET FOREIGN_KEY_CHECKS = 1;
