-- AI Reputation Radar - stato 'processing' per la prenotazione atomica degli item (revisione codice 2026-10-06)
ALTER TABLE ar_responses
    MODIFY COLUMN status ENUM('pending', 'processing', 'ok', 'error') NOT NULL DEFAULT 'pending';
