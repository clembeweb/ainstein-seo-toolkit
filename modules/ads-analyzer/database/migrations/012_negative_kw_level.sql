-- Colonne per il livello di applicazione suggerito dall'AI
ALTER TABLE ga_negative_keywords
    ADD COLUMN suggested_level ENUM('campaign', 'ad_group') DEFAULT 'campaign' AFTER suggested_match_type,
    ADD COLUMN suggested_campaign_resource VARCHAR(255) NULL AFTER suggested_level,
    ADD COLUMN suggested_ad_group_resource VARCHAR(255) NULL AFTER suggested_campaign_resource;
