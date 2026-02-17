-- Migration: Sincronizza settings DB con nuovo modello crediti 4 livelli
-- Data: 2026-02-17
-- Descrizione: Aggiorna valori obsoleti, rimuove zombie settings

-- 1. Aggiorna free_credits da 50 a 30
UPDATE settings SET value = '30' WHERE `key` = 'free_credits' AND value = '50';

-- 2. Rimuovi settings zombie (voci non piu utilizzate da nessun modulo)
DELETE FROM settings WHERE `key` IN (
    'cost_ai_analysis_small',
    'cost_ai_analysis_medium',
    'cost_ai_analysis_large',
    'cost_ai_overview',
    'cost_ai_category',
    'cost_gsc_sync',
    'cost_export_excel',
    'cost_bulk_analysis_discount'
);

-- 3. Aggiorna settings con valori vecchi ai nuovi 4 livelli
UPDATE settings SET value = '1' WHERE `key` = 'cost_scrape_url' AND value = '0.1';
UPDATE settings SET value = '3' WHERE `key` = 'cost_quick_wins' AND value = '2';
UPDATE settings SET value = '3' WHERE `key` = 'cost_weekly_digest' AND value = '5';
UPDATE settings SET value = '10' WHERE `key` = 'cost_monthly_executive' AND value = '15';
UPDATE settings SET value = '3' WHERE `key` = 'cost_ad_group_analysis' AND value = '2';
UPDATE settings SET value = '3' WHERE `key` = 'cost_bulk_analysis' AND value = '1.5';
UPDATE settings SET value = '10' WHERE `key` = 'cost_campaign_evaluation' AND value = '7';
UPDATE settings SET value = '10' WHERE `key` = 'cost_action_plan_generate' AND value = '15';
UPDATE settings SET value = '3' WHERE `key` = 'cost_brief_generation' AND value = '5';
UPDATE settings SET value = '1' WHERE `key` = 'cost_link_analysis' AND value = '0.5';
UPDATE settings SET value = '3' WHERE `key` = 'cost_kr_ai_clustering' AND value = '2';
UPDATE settings SET value = '3' WHERE `key` = 'cost_kr_ai_clustering_large' AND value = '5';
UPDATE settings SET value = '10' WHERE `key` = 'cost_kr_ai_architecture' AND value = '5';
UPDATE settings SET value = '10' WHERE `key` = 'cost_kr_editorial_plan' AND value = '5';
