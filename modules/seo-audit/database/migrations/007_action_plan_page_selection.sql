-- SEO Audit: Piano d'azione con pagine scelte dall'utente e generazione a passi (2026-10-16)
-- Prima: una chiamata AI per OGNI pagina con problemi in un'unica richiesta (Madcrumbs 12 pagine = 5 minuti
-- senza avanzamento; 100 pagine ~45 minuti). Ora l'utente sceglie le pagine e lo stream SSE le lavora una alla volta.

ALTER TABLE sa_action_plans ADD COLUMN page_ids TEXT NULL COMMENT 'JSON: id delle pagine scelte, in ordine' AFTER session_id;
ALTER TABLE sa_action_plans ADD COLUMN pages_done INT DEFAULT 0 COMMENT 'Pagine gia lavorate (anche fallite)' AFTER total_pages;
ALTER TABLE sa_action_plans ADD COLUMN page_errors TEXT NULL COMMENT 'JSON: errori per pagina' AFTER pages_done;
