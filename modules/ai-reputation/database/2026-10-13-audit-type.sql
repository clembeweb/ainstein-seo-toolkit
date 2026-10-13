-- GEO Audit 2026-10-13: due audit nello stesso progetto (idea di Clemente).
-- purchase = domande d'acquisto (il cliente cerca il servizio: ti citano o citano i concorrenti?)
-- reputation = domande sulla reputazione (cosa dicono di te, rischi). I progetti esistenti restano "reputation".
ALTER TABLE ar_projects
    ADD COLUMN audit_type ENUM('purchase','reputation','both') NOT NULL DEFAULT 'reputation' AFTER subject_type;

ALTER TABLE ar_prompts
    ADD COLUMN audit_set ENUM('purchase','reputation') NOT NULL DEFAULT 'reputation' AFTER cluster;

ALTER TABLE ar_runs
    ADD COLUMN audit_type ENUM('purchase','reputation','both') NOT NULL DEFAULT 'reputation' AFTER project_id;
