-- ============================================================
-- SEO-TRACKING: KEYWORD GROUPS
-- Migration: 001_keyword_groups.sql
-- Created: 2026-01-07
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. st_keyword_groups - Gruppi di keyword
-- ============================================================
CREATE TABLE IF NOT EXISTS st_keyword_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    color VARCHAR(7) DEFAULT '#6366f1' COMMENT 'Hex color for UI',
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_project_group (project_id, name),
    KEY idx_project_active (project_id, is_active),

    CONSTRAINT fk_kg_project FOREIGN KEY (project_id)
        REFERENCES st_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. st_keyword_group_members - Relazione keyword-gruppo (M:N)
-- ============================================================
CREATE TABLE IF NOT EXISTS st_keyword_group_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    keyword_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uk_group_keyword (group_id, keyword_id),
    KEY idx_keyword (keyword_id),

    CONSTRAINT fk_kgm_group FOREIGN KEY (group_id)
        REFERENCES st_keyword_groups(id) ON DELETE CASCADE,
    CONSTRAINT fk_kgm_keyword FOREIGN KEY (keyword_id)
        REFERENCES st_keywords(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- MIGRATION NOTES
-- ============================================================
/*
Questo schema supporta:
- Gruppi multipli per progetto
- Ogni keyword può appartenere a più gruppi (many-to-many)
- Colori personalizzabili per UI
- Ordinamento custom dei gruppi
- Soft-active flag per disabilitare temporaneamente

Queries utili:
-- Keyword per gruppo
SELECT k.* FROM st_keywords k
JOIN st_keyword_group_members m ON k.id = m.keyword_id
WHERE m.group_id = ?;

-- Gruppi per keyword
SELECT g.* FROM st_keyword_groups g
JOIN st_keyword_group_members m ON g.id = m.group_id
WHERE m.keyword_id = ?;

-- Stats per gruppo
SELECT
    g.id,
    g.name,
    COUNT(m.keyword_id) as keyword_count,
    AVG(k.last_position) as avg_position,
    SUM(k.last_clicks) as total_clicks
FROM st_keyword_groups g
LEFT JOIN st_keyword_group_members m ON g.id = m.group_id
LEFT JOIN st_keywords k ON m.keyword_id = k.id
WHERE g.project_id = ?
GROUP BY g.id;
*/
