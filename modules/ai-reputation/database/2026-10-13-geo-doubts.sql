-- GEO Audit 2026-10-13: "dubbi delle AI" separati dalle fonti negative.
-- Caso Stabia (run 1): ChatGPT segnala "red flags" (stesso telefono su più siti, ragione sociale non trovata)
-- citando un concorrente, l'agenzia web e un registro imprese: non sono fonti da far rimuovere,
-- sono indizi di un dubbio che il soggetto scioglie pubblicando i fatti mancanti.
ALTER TABLE ar_analyses
    ADD COLUMN doubts JSON NULL AFTER negative_urls;

ALTER TABLE ar_actions
    MODIFY COLUMN type ENUM('removal','gap_article','correction','counter_content','gap_pending','clarify') NOT NULL;
