-- Migration: Unify WordPress connectors
-- Adds global_project_id to aic_wp_sites (source of truth for WP connections)
-- Drops project_connectors (empty, replaced by aic_wp_sites)

-- 1. Add global_project_id column to aic_wp_sites
ALTER TABLE aic_wp_sites
  ADD COLUMN global_project_id INT NULL DEFAULT NULL AFTER user_id,
  ADD INDEX idx_global_project (global_project_id);

-- 2. Drop project_connectors table (empty, no data loss)
DROP TABLE IF EXISTS project_connectors;
