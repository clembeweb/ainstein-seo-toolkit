-- Migration: Rimozione progetti Keyword Negative standalone
-- I progetti negative-kw sono ridondanti: la funzionalita e integrata
-- nel tab "Keyword Negative" della modalita Campagne.

-- 1. Elimina keyword negative dei progetti negative-kw
DELETE nk FROM ga_negative_keywords nk
INNER JOIN ga_analyses a ON nk.analysis_id = a.id
INNER JOIN ga_projects p ON a.project_id = p.id
WHERE p.type = 'negative-kw';

-- 2. Elimina categorie negative dei progetti negative-kw
DELETE nc FROM ga_negative_categories nc
INNER JOIN ga_analyses a ON nc.analysis_id = a.id
INNER JOIN ga_projects p ON a.project_id = p.id
WHERE p.type = 'negative-kw';

-- 3. Elimina analisi dei progetti negative-kw
DELETE a FROM ga_analyses a
INNER JOIN ga_projects p ON a.project_id = p.id
WHERE p.type = 'negative-kw';

-- 4. Elimina search terms dei progetti negative-kw
DELETE st FROM ga_search_terms st
INNER JOIN ga_projects p ON st.project_id = p.id
WHERE p.type = 'negative-kw';

-- 5. Elimina ad groups dei progetti negative-kw
DELETE ag FROM ga_ad_groups ag
INNER JOIN ga_projects p ON ag.project_id = p.id
WHERE p.type = 'negative-kw';

-- 6. Elimina landing pages dei progetti negative-kw
DELETE lp FROM ga_landing_pages lp
INNER JOIN ga_projects p ON lp.project_id = p.id
WHERE p.type = 'negative-kw';

-- 7. Elimina i progetti negative-kw
DELETE FROM ga_projects WHERE type = 'negative-kw';
