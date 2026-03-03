-- Migration: Add AI Link Suggestions support
-- Date: 2026-03-03
-- Module: Internal Links (il_)

USE seo_toolkit;

-- ============================================
-- IL_LINK_SUGGESTIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS il_link_suggestions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    source_url_id INT NOT NULL,
    destination_url_id INT NOT NULL,

    -- Deterministic scoring (Phase 1)
    keyword_score INT DEFAULT 0,
    category_bonus INT DEFAULT 0,
    total_score INT DEFAULT 0,
    reason ENUM('hub_needs_outgoing','orphan_needs_inbound','topical_relevance') NOT NULL,

    -- AI enrichment (Phase 2)
    ai_relevance_score TINYINT NULL COMMENT '1-10 semantic relevance',
    ai_suggested_anchors JSON NULL COMMENT '["anchor1","anchor2","anchor3"]',
    ai_placement_hint TEXT NULL,
    ai_confidence ENUM('high','medium','low') NULL,
    ai_anchor_diversity_note TEXT NULL,
    ai_analyzed_at DATETIME NULL,

    -- AI insertion point (Phase 3, on-demand)
    ai_snippet_html TEXT NULL COMMENT 'Paragraph with link inserted',
    ai_original_paragraph TEXT NULL COMMENT 'Original paragraph before modification',
    ai_insertion_method ENUM('inline_existing_text','contextual_sentence') NULL,
    ai_anchor_used VARCHAR(255) NULL,
    ai_snippet_generated_at DATETIME NULL,

    -- Status
    status ENUM('pending','ai_validated','snippet_ready','applied','dismissed') DEFAULT 'pending',
    applied_at DATETIME NULL,
    applied_method ENUM('manual_copy','cms_push') NULL,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_project_status (project_id, status),
    INDEX idx_source (source_url_id),
    INDEX idx_destination (destination_url_id),
    INDEX idx_score (total_score),
    UNIQUE KEY unique_suggestion (project_id, source_url_id, destination_url_id),
    FOREIGN KEY (project_id) REFERENCES il_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (source_url_id) REFERENCES il_urls(id) ON DELETE CASCADE,
    FOREIGN KEY (destination_url_id) REFERENCES il_urls(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ALTER il_projects: add connector_id
-- ============================================
-- Note: MySQL 8.0 does not support ADD COLUMN IF NOT EXISTS.
-- This will error on re-run if column already exists (safe for migration).
ALTER TABLE il_projects ADD COLUMN connector_id INT NULL AFTER status;

-- ============================================
-- ALTER il_project_stats: add suggestion counters
-- ============================================
ALTER TABLE il_project_stats ADD COLUMN total_suggestions INT DEFAULT 0;
ALTER TABLE il_project_stats ADD COLUMN pending_suggestions INT DEFAULT 0;
ALTER TABLE il_project_stats ADD COLUMN applied_suggestions INT DEFAULT 0;
