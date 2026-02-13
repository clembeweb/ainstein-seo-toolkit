-- Migration 006: Aggiunge run_id a tabelle negative-kw per integrazione nei progetti campagna
-- Eseguire su locale e produzione

-- run_id su ga_analyses per collegare analisi a un run specifico
ALTER TABLE ga_analyses ADD COLUMN run_id INT NULL AFTER project_id;
ALTER TABLE ga_analyses ADD INDEX idx_analyses_run (run_id);

-- run_id su ga_ad_groups per tracciare ad group per run
ALTER TABLE ga_ad_groups ADD COLUMN run_id INT NULL AFTER project_id;
ALTER TABLE ga_ad_groups ADD INDEX idx_adgroup_run (run_id);

-- run_id su ga_search_terms per tracciare termini per run
ALTER TABLE ga_search_terms ADD COLUMN run_id INT NULL AFTER project_id;
ALTER TABLE ga_search_terms ADD INDEX idx_searchterm_run (run_id);
