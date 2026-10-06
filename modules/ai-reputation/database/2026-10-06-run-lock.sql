-- AI Reputation Radar - lease sul run: un solo stream lo elabora, gli altri seguono (fix doppia elaborazione)
ALTER TABLE ar_runs
    ADD COLUMN locked_by VARCHAR(40) DEFAULT NULL AFTER error_message,
    ADD COLUMN locked_at TIMESTAMP NULL DEFAULT NULL AFTER locked_by;
