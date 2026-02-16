-- ============================================
-- Migration: Aggiunta tipo progetto (research/architecture/editorial)
-- Date: 2026-02-16
-- Adds 'type' column to kr_projects for mode-specific filtering
-- ============================================

-- Aggiungere colonna 'type' a kr_projects
ALTER TABLE kr_projects
ADD COLUMN type ENUM('research', 'architecture', 'editorial') NOT NULL DEFAULT 'research' AFTER description;

-- Backfill: assegna tipo in base alla ricerca più recente del progetto
UPDATE kr_projects p SET p.type = COALESCE(
    (SELECT r.type FROM kr_researches r WHERE r.project_id = p.id ORDER BY r.created_at DESC LIMIT 1),
    'research'
);

-- Indice composito per query filtrate per utente + tipo
ALTER TABLE kr_projects ADD INDEX idx_user_type (user_id, type);
