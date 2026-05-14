-- Rollback: aied webhook columns (M1.5)

ALTER TABLE aied_api_logs
    DROP INDEX uniq_provider_event,
    DROP COLUMN external_event_id;

ALTER TABLE aied_users
    DROP COLUMN last_payment_failed_at,
    DROP COLUMN topup_balance_articles;
