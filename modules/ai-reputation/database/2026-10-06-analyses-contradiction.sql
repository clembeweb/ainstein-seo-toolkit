-- AI Reputation Radar - risposta in contrasto con i fatti confermati del profilo (controllo a campione 2026-10-06)
ALTER TABLE ar_analyses
    ADD COLUMN contradicts_profile TINYINT(1) NOT NULL DEFAULT 0 AFTER negative,
    ADD COLUMN contradiction_note VARCHAR(500) DEFAULT NULL AFTER contradicts_profile;
