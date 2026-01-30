-- =============================================
-- Migration 011 - Sistema Auto Rank Check
-- Modulo: SEO Tracking (prefisso st_)
-- Data: 2026-01-30
--
-- Descrizione:
-- Crea la tabella st_rank_queue per la coda delle keyword
-- da verificare automaticamente.
--
-- Le impostazioni (rank_auto_enabled, rank_auto_days, rank_auto_time)
-- sono gestite nel module.json e salvate in modules.settings JSON.
-- =============================================

-- =============================================
-- TABELLA st_rank_queue
-- Coda keyword per verifica posizioni automatica
-- =============================================

CREATE TABLE IF NOT EXISTS st_rank_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    keyword_id INT NOT NULL,
    keyword VARCHAR(500) NOT NULL COMMENT 'Testo keyword (cache per performance)',
    target_domain VARCHAR(255) NOT NULL COMMENT 'Dominio target da cercare in SERP',
    location_code VARCHAR(10) DEFAULT 'IT' COMMENT 'Codice paese per ricerca SERP',
    device ENUM('desktop', 'mobile') DEFAULT 'mobile' COMMENT 'Tipo dispositivo simulato',

    -- Stato elaborazione
    status ENUM('pending', 'processing', 'completed', 'error') DEFAULT 'pending',

    -- Scheduling
    scheduled_at DATETIME NOT NULL COMMENT 'Data/ora pianificata per il check',
    started_at DATETIME NULL COMMENT 'Inizio elaborazione',
    completed_at DATETIME NULL COMMENT 'Fine elaborazione',

    -- Risultati
    result_position INT NULL COMMENT 'Posizione trovata (NULL = non in top 100)',
    result_url VARCHAR(2000) NULL COMMENT 'URL trovato in SERP',
    error_message TEXT NULL COMMENT 'Messaggio errore se status=error',

    -- Collegamento al rank check salvato
    rank_check_id INT NULL COMMENT 'FK a st_rank_checks dopo completamento',

    -- Timestamp
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Indici per performance
    INDEX idx_status_scheduled (status, scheduled_at) COMMENT 'Per query cron: trova pending da processare',
    INDEX idx_project (project_id) COMMENT 'Per filtro per progetto',
    INDEX idx_keyword (keyword_id) COMMENT 'Per verifica duplicati',
    INDEX idx_scheduled (scheduled_at) COMMENT 'Per ordinamento cronologico',

    -- Foreign keys con cascade delete
    FOREIGN KEY (project_id) REFERENCES st_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (keyword_id) REFERENCES st_keywords(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Coda keyword per verifica automatica posizioni SERP';


-- =============================================
-- VERIFICA MIGRAZIONE
-- =============================================

DESCRIBE st_rank_queue;


-- =============================================
-- NOTE UTILIZZO
-- =============================================
/*
La tabella st_rank_queue funziona come coda di lavoro:

1. POPOLAMENTO CODA (scheduler):
   - Il cron dispatcher popola la coda nei giorni/orari configurati
   - Per ogni progetto con keywords tracciate, inserisce un record
   - Status iniziale: 'pending'

2. ELABORAZIONE (worker):
   - Il dispatcher processa i record pending in ordine di scheduled_at
   - Aggiorna status a 'processing' quando inizia
   - Chiama RankCheckerService per verifica SERP
   - Salva risultato in st_rank_checks
   - Aggiorna status a 'completed' o 'error'

3. PULIZIA:
   - I record completati possono essere eliminati dopo N giorni
   - Mantenere solo record error per debug

IMPOSTAZIONI (in /admin/modules/{id}/settings - modules.settings JSON):
- rank_auto_enabled: true/false per attivare/disattivare
- rank_auto_days: preset (mon_thu, mon_wed_fri, daily, weekly)
- rank_auto_time: orario esecuzione HH:MM (es. "04:00")
*/
