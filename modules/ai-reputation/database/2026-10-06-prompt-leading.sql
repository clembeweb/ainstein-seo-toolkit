-- AI Reputation Radar - domande "mirate" (nominano o presuppongono un fatto negativo specifico), ADR-011
ALTER TABLE ar_prompts
    ADD COLUMN is_leading TINYINT(1) NOT NULL DEFAULT 0 AFTER persona;
