-- st_keywords.keyword_intent: intento della keyword salvato dal refresh volumi (models/Keyword.php).
-- In produzione la colonna esiste già (aggiunta a mano); serve agli ambienti locali che non ce l'hanno
-- (trovato 2026-10-13: pagina progetto in 500 "Unknown column 'k.keyword_intent'").
ALTER TABLE st_keywords
    ADD COLUMN keyword_intent VARCHAR(100) NULL DEFAULT NULL AFTER search_volume;
