-- Migration: Add crawl budget columns to sa_pages and sa_site_config
-- Module: seo-audit
-- Date: 2026-03-03
-- Description: Columns for crawl budget analysis merged into SEO Audit
-- Safe: all columns nullable or with defaults, no existing data affected

-- sa_pages: redirect tracking
ALTER TABLE sa_pages
  ADD COLUMN redirect_chain JSON DEFAULT NULL COMMENT 'Array hop: [{"url":"...","status":301}, ...]',
  ADD COLUMN redirect_hops TINYINT(3) UNSIGNED DEFAULT NULL COMMENT 'Numero hop redirect (0=nessuno)',
  ADD COLUMN redirect_target VARCHAR(2048) DEFAULT NULL COMMENT 'URL finale dopo redirect chain',
  ADD COLUMN is_redirect_loop TINYINT(1) DEFAULT 0 COMMENT 'Loop rilevato nella chain';

-- sa_pages: crawl budget metadata
ALTER TABLE sa_pages
  ADD COLUMN depth TINYINT(3) UNSIGNED DEFAULT 0 COMMENT 'Profondita dal root (0=homepage)',
  ADD COLUMN discovered_from VARCHAR(2048) DEFAULT NULL COMMENT 'URL che ha scoperto questa pagina',
  ADD COLUMN has_parameters TINYINT(1) DEFAULT 0 COMMENT 'URL contiene query string',
  ADD COLUMN in_sitemap TINYINT(1) DEFAULT NULL COMMENT 'URL trovata nella sitemap XML',
  ADD COLUMN in_robots_allowed TINYINT(1) DEFAULT NULL COMMENT 'URL permessa da robots.txt',
  ADD COLUMN crawl_source ENUM('spider','sitemap','import') DEFAULT 'spider' COMMENT 'Come e stata scoperta';

-- sa_site_config: crawl_delay + robots_rules
-- NOTE: sitemap_urls already exists (JSON), no need to re-add
ALTER TABLE sa_site_config
  ADD COLUMN crawl_delay INT DEFAULT NULL COMMENT 'Crawl-Delay da robots.txt',
  ADD COLUMN robots_rules JSON DEFAULT NULL COMMENT 'Regole parsed per User-Agent';

-- sa_unified_reports: AI report storage
CREATE TABLE IF NOT EXISTS sa_unified_reports (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  session_id INT DEFAULT NULL,
  report_type ENUM('unified','crawl_budget','on_page') DEFAULT 'unified',
  html_content LONGTEXT NOT NULL COMMENT 'Report HTML completo self-contained',
  summary TEXT DEFAULT NULL COMMENT 'Executive summary estratto',
  priority_actions JSON DEFAULT NULL COMMENT 'Top 5 azioni prioritarie',
  estimated_impact JSON DEFAULT NULL COMMENT 'Metriche impatto stimato',
  site_profile JSON DEFAULT NULL COMMENT 'Profilo sito rilevato (tipo, dimensione, settore)',
  health_score TINYINT(3) UNSIGNED DEFAULT NULL,
  budget_score TINYINT(3) UNSIGNED DEFAULT NULL,
  waste_percentage DECIMAL(5,2) DEFAULT NULL,
  credits_used INT DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_project_date (project_id, created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- sa_crawl_sessions: add budget_score
ALTER TABLE sa_crawl_sessions
  ADD COLUMN budget_score TINYINT(3) UNSIGNED DEFAULT NULL COMMENT 'Crawl budget score 0-100',
  ADD COLUMN waste_percentage DECIMAL(5,2) DEFAULT NULL COMMENT 'Percentuale spreco crawl budget';

-- sa_projects: add budget_score
ALTER TABLE sa_projects
  ADD COLUMN budget_score TINYINT(3) UNSIGNED DEFAULT NULL COMMENT 'Crawl budget score 0-100';
