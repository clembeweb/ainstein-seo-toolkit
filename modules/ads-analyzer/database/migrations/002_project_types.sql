-- Aggiunge campo type per separare Negative KW e Analisi Campagne
ALTER TABLE ga_projects
  ADD COLUMN `type` ENUM('negative-kw', 'campaign') NOT NULL DEFAULT 'negative-kw' AFTER `user_id`,
  ADD INDEX idx_type (`type`);
