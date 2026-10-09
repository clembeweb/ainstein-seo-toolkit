-- AI Reputation Radar - raggruppamento AI degli articoli gap (ADR-014):
-- gap_pending = domanda scoperta non sostenuta dai fatti confermati; covered_prompts = domande coperte da un intervento
ALTER TABLE ar_actions
    MODIFY COLUMN type ENUM('removal','gap_article','correction','counter_content','gap_pending') NOT NULL,
    ADD COLUMN covered_prompts JSON DEFAULT NULL AFTER target_domain;
