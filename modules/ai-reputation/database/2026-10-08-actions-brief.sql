-- AI Reputation Radar - scheda operativa per intervento (canale, testate, brief) generata su richiesta
ALTER TABLE ar_actions
    ADD COLUMN channel ENUM('own_site', 'external', 'both') DEFAULT NULL AFTER status,
    ADD COLUMN channel_rationale TEXT DEFAULT NULL AFTER channel,
    ADD COLUMN suggested_outlets JSON DEFAULT NULL AFTER channel_rationale,
    ADD COLUMN brief JSON DEFAULT NULL AFTER suggested_outlets,
    ADD COLUMN brief_model VARCHAR(80) DEFAULT NULL AFTER brief,
    ADD COLUMN brief_generated_at DATETIME DEFAULT NULL AFTER brief_model,
    ADD COLUMN brief_error VARCHAR(500) DEFAULT NULL AFTER brief_generated_at;
