-- Migration: aied webhook columns
-- Date: 2026-05-14
-- Milestone: M1.5 (Webhook handler Lemon Squeezy)
--
-- Aggiunge:
--   1) aied_users.topup_balance_articles  -- crediti articoli da top-up pack (order_created)
--   2) aied_users.last_payment_failed_at  -- timestamp ultimo fallimento pagamento (per warning email in M6)
--   3) aied_api_logs.external_event_id    -- ID evento provider esterno (LS event id) per idempotency
--      + UNIQUE INDEX su (provider, external_event_id) per garantire no double-processing

ALTER TABLE aied_users
    ADD COLUMN topup_balance_articles INT UNSIGNED NOT NULL DEFAULT 0 AFTER subscription_renews_at,
    ADD COLUMN last_payment_failed_at TIMESTAMP NULL AFTER topup_balance_articles;

ALTER TABLE aied_api_logs
    ADD COLUMN external_event_id VARCHAR(255) NULL AFTER provider,
    ADD UNIQUE KEY uniq_provider_event (provider, external_event_id);
