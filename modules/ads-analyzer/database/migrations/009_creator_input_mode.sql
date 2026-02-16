-- Migration: Campaign Creator input_mode
-- Aggiunge la scelta modalita input (url, brief, entrambi) per Campaign Creator

ALTER TABLE ga_projects
  ADD COLUMN input_mode ENUM('url', 'brief', 'both') DEFAULT 'url' AFTER campaign_type_gads;

-- Backfill: progetti esistenti con URL e brief popolati → 'both'
UPDATE ga_projects
  SET input_mode = 'both'
  WHERE type = 'campaign-creator'
    AND landing_url IS NOT NULL AND landing_url != ''
    AND brief IS NOT NULL AND brief != '';

-- Progetti con solo URL → 'url'
UPDATE ga_projects
  SET input_mode = 'url'
  WHERE type = 'campaign-creator'
    AND landing_url IS NOT NULL AND landing_url != ''
    AND (brief IS NULL OR brief = '');

-- Progetti con solo brief → 'brief'
UPDATE ga_projects
  SET input_mode = 'brief'
  WHERE type = 'campaign-creator'
    AND (landing_url IS NULL OR landing_url = '')
    AND brief IS NOT NULL AND brief != '';
