-- ============================================
-- Migration: proposte AI dal profilo dell'attività
-- Date: 2026-10-16
-- Parole di partenza, categorie, tipo di sito e target proposti dall'AI a partire dal profilo del progetto.
-- Si rigenerano solo se il profilo cambia (profile_hash).
-- ============================================

ALTER TABLE kr_projects ADD COLUMN suggestions JSON NULL AFTER default_language;
