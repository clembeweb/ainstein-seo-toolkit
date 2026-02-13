-- ============================================
-- Migration 001: Pivot da Meta Tag Generator a Page Content Generator
-- Date: 2026-02-13
-- Cambia cc_urls per generare contenuto completo pagina (non meta tag)
-- Aggiunge supporto plugin CMS a cc_connectors
-- ============================================

-- 1. cc_urls: rimuovere campi meta, aggiungere campi contenuto
ALTER TABLE cc_urls
  DROP COLUMN ai_meta_title,
  DROP COLUMN ai_meta_description,
  CHANGE COLUMN ai_page_description ai_content LONGTEXT DEFAULT NULL,
  ADD COLUMN ai_h1 VARCHAR(500) DEFAULT NULL AFTER ai_content,
  ADD COLUMN ai_word_count INT DEFAULT 0 AFTER ai_h1,
  ADD COLUMN secondary_keywords JSON DEFAULT NULL AFTER keyword,
  ADD COLUMN intent VARCHAR(50) DEFAULT NULL AFTER secondary_keywords,
  ADD COLUMN source_type ENUM('manual','csv','sitemap','cms','keyword_research') DEFAULT 'manual' AFTER intent;

-- 1b. cc_projects: aggiungere 'service' al content_type ENUM
ALTER TABLE cc_projects
  MODIFY COLUMN content_type ENUM('product','category','article','service','custom') NOT NULL DEFAULT 'product';

-- 2. cc_connectors: supporto plugin CMS (API key, categorie cache, SEO plugin rilevato)
ALTER TABLE cc_connectors
  ADD COLUMN api_key VARCHAR(100) DEFAULT NULL AFTER config,
  ADD COLUMN categories_cache JSON DEFAULT NULL AFTER last_sync_at,
  ADD COLUMN seo_plugin VARCHAR(50) DEFAULT NULL AFTER categories_cache;
