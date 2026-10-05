-- AI Reputation Radar — setting admin per la API key Perplexity (engine Sonar)
-- Il salvataggio da /admin/settings accetta solo chiavi già presenti in `settings` (whitelist implicita):
-- la riga va creata vuota prima che il campo nel pannello funzioni.
-- Idempotente. Eseguire in locale e in produzione.
INSERT INTO settings (key_name, value, is_secret)
SELECT 'perplexity_api_key', '', 1
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE key_name = 'perplexity_api_key');
