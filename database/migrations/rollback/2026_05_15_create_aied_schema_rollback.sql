-- Rollback: Ainstein Editorial plugin schema (aied_*)
-- Data: 2026-05-15
-- Milestone: M1.1
-- Run: mysql -u root seo_toolkit < database/migrations/rollback/2026_05_15_create_aied_schema_rollback.sql
--
-- Elimina le 9 tabelle aied_* in ordine inverso rispetto alle FK.
-- ATTENZIONE: cancellazione distruttiva. Usare solo in ambienti di test.
--
-- Ordine drop (reverse FK):
--   1) aied_api_logs           (no FK)
--   2) aied_internal_links     (FK -> aied_sites)
--   3) aied_articles           (FK -> aied_sites, aied_editorial_items)
--      [prima rimuovere FK circolare su aied_editorial_items.article_id]
--   4) aied_editorial_items    (FK -> aied_editorial_plans, aied_articles)
--   5) aied_editorial_plans    (FK -> aied_sites)
--   6) aied_content_brain      (FK -> aied_sites)
--   7) aied_actions_log        (FK -> aied_users, aied_sites)
--   8) aied_sites              (FK -> aied_users)
--   9) aied_users
--
-- Usiamo SET FOREIGN_KEY_CHECKS=0 per evitare problemi con dipendenze circolari.

SET FOREIGN_KEY_CHECKS = 0;

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
