-- Migration: Add volume data columns to creator keywords
-- Permette di salvare volumi reali da Google Keyword Insight API

ALTER TABLE ga_creator_keywords
  ADD COLUMN search_volume INT DEFAULT NULL AFTER intent,
  ADD COLUMN cpc DECIMAL(10,2) DEFAULT NULL AFTER search_volume,
  ADD COLUMN competition_level VARCHAR(20) DEFAULT NULL AFTER cpc,
  ADD COLUMN competition_index INT DEFAULT NULL AFTER competition_level;
