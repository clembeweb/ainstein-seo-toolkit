-- AI Reputation Radar - esito e verdetto del judge in colonne interrogabili (fetta 2)
ALTER TABLE ar_analyses
    ADD COLUMN outcome ENUM('answered', 'clarification_requested', 'refused', 'empty') NOT NULL DEFAULT 'answered' AFTER project_id,
    ADD COLUMN verdict ENUM('positive', 'neutral', 'mixed', 'negative', 'not_mentioned') NOT NULL DEFAULT 'not_mentioned' AFTER outcome,
    ADD COLUMN summary VARCHAR(500) DEFAULT NULL AFTER verdict,
    ADD COLUMN homonym_note VARCHAR(500) DEFAULT NULL AFTER is_homonym,
    ADD INDEX idx_verdict (run_id, verdict);
