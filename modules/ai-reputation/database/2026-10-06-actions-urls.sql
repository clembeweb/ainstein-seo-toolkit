-- AI Reputation Radar - un'azione di rimozione per sito con tutte le sue pagine negative
ALTER TABLE ar_actions
    ADD COLUMN target_urls JSON DEFAULT NULL AFTER target_url;
