<?php

namespace Modules\SeoAudit\Services;

use Core\Database;
use Core\Credits;
use Services\AiService;
use Modules\SeoAudit\Models\Project;
use Modules\SeoAudit\Models\Issue;
use Modules\SeoAudit\Models\Page;

/**
 * ActionPlanService
 *
 * Genera piani d'azione AI con fix raggruppati per pagina
 */
class ActionPlanService
{
    private AiService $ai;
    private Project $projectModel;
    private Issue $issueModel;
    private Page $pageModel;

    private const MODULE_SLUG = 'seo-audit';

    public function __construct()
    {
        $this->ai = new AiService(self::MODULE_SLUG);
        $this->projectModel = new Project();
        $this->issueModel = new Issue();
        $this->pageModel = new Page();
    }

    /** Pagine al massimo in un piano: ~25 s di AI a pagina, oltre diventa un'attesa da non proporre */
    public const MAX_PAGES = 20;
    /** Pagine spuntate di default (le più importanti per impatto) */
    public const DEFAULT_PAGES = 10;
    /** Secondi di AI stimati per pagina (prod Madcrumbs: 24 s; locale fino a 55 s) */
    public const SECONDS_PER_PAGE = 30;

    /**
     * Pagine con problemi tra cui scegliere, in ordine di impatto (le prime sono le più utili da sistemare).
     *
     * @return array<int, array{page_id:int, url:string, page_title:?string, critical:int, warning:int, notice:int, issues:int}>
     */
    public function getSelectablePages(int $projectId): array
    {
        $out = [];
        foreach ($this->groupIssuesByPage($projectId) as $pageId => $g) {
            $sev = array_count_values(array_column($g['issues'], 'severity'));
            $out[] = [
                'page_id' => (int) $pageId,
                'url' => $g['url'],
                'page_title' => $g['page_title'],
                'critical' => (int) ($sev['critical'] ?? 0),
                'warning' => (int) ($sev['warning'] ?? 0),
                'notice' => (int) ($sev['notice'] ?? 0),
                'issues' => count($g['issues']),
            ];
        }
        return $out;
    }

    /**
     * Crea il piano con le pagine scelte (status 'generating'). Le pagine le lavora processPlan(), una alla volta.
     */
    public function startPlan(int $projectId, ?int $sessionId, int $userId, array $pageIds): array
    {
        if (!$this->ai->isConfigured()) {
            return ['error' => true, 'message' => 'API AI non configurata. Contatta l\'amministratore.'];
        }

        $project = $this->projectModel->findWithStats($projectId, $userId);
        if (!$project) {
            return ['error' => true, 'message' => 'Progetto non trovato'];
        }

        // Tengo solo pagine del progetto che hanno davvero problemi, nell'ordine di impatto
        $available = array_map('intval', array_keys($this->groupIssuesByPage($projectId)));
        $wanted = array_flip(array_map('intval', $pageIds));
        $chosen = array_values(array_filter($available, fn($id) => isset($wanted[$id])));

        if (empty($chosen)) {
            return ['error' => true, 'message' => 'Scegli almeno una pagina da analizzare.'];
        }
        if (count($chosen) > self::MAX_PAGES) {
            return ['error' => true, 'message' => 'Al massimo ' . self::MAX_PAGES . ' pagine per piano.'];
        }

        // Un piano per scansione: un tentativo rimasto a metà si sostituisce
        Database::execute(
            "DELETE FROM sa_action_plans WHERE project_id = ? AND session_id <=> ?",
            [$projectId, $sessionId]
        );

        $planId = Database::insert('sa_action_plans', [
            'project_id' => $projectId,
            'session_id' => $sessionId,
            'page_ids' => json_encode($chosen),
            'status' => 'generating',
            'health_current' => $project['health_score'] ?? 0,
            'total_pages' => count($chosen),
            'pages_done' => 0,
        ]);

        return ['success' => true, 'plan_id' => $planId, 'total_pages' => count($chosen)];
    }

    /**
     * Lavora le pagine del piano ancora da fare, una chiamata AI per pagina, e chiude il piano.
     * Riprende da pages_done: se la connessione cade, riaprire la pagina continua da dove era rimasto.
     *
     * @param callable $onEvent fn(string $event, array $data): void — avanzamento per lo stream SSE
     * @return array ['success' => true, 'finalized' => bool, ...] oppure ['error' => true, 'message' => ...]
     */
    public function processPlan(int $planId, int $projectId, int $userId, callable $onEvent): array
    {
        $plan = Database::fetch("SELECT * FROM sa_action_plans WHERE id = ? AND project_id = ?", [$planId, $projectId]);
        if (!$plan) {
            return ['error' => true, 'message' => 'Piano non trovato'];
        }
        if ($plan['status'] !== 'generating') {
            return ['success' => true, 'finalized' => false, 'already_done' => true];
        }

        $project = $this->projectModel->findWithStats($projectId, $userId);
        if (!$project) {
            return ['error' => true, 'message' => 'Progetto non trovato'];
        }

        $pageIds = json_decode((string) $plan['page_ids'], true) ?: [];
        $groups = $this->groupIssuesByPage($projectId);
        $total = count($pageIds);
        $done = (int) $plan['pages_done'];
        $errors = json_decode((string) ($plan['page_errors'] ?? ''), true) ?: [];

        $onEvent('started', ['total' => $total, 'done' => $done]);

        for ($i = $done; $i < $total; $i++) {
            set_time_limit(300); // per pagina: il conto riparte a ogni giro
            $pageId = (int) $pageIds[$i];
            $pageData = $groups[$pageId] ?? null;

            $onEvent('page_started', ['index' => $i + 1, 'total' => $total, 'url' => $pageData['url'] ?? '']);

            if ($pageData === null) {
                $result = ['error' => true, 'message' => 'La pagina non ha più problemi'];
            } else {
                // Una ripresa dopo un'interruzione non lascia fix doppi sulla stessa pagina
                Database::delete('sa_page_fixes', 'plan_id = ? AND page_id = ?', [$planId, $pageId]);
                $result = $this->generatePageFixes($planId, $projectId, $pageId, $pageData, $project, $userId);
            }

            Database::reconnect();
            if (isset($result['error'])) {
                $errors[] = ['page_id' => $pageId, 'url' => $pageData['url'] ?? '', 'message' => $result['message']];
            }
            Database::update('sa_action_plans', [
                'pages_done' => $i + 1,
                'page_errors' => $errors ? json_encode($errors, JSON_UNESCAPED_UNICODE) : null,
            ], 'id = ?', [$planId]);

            $onEvent('page_completed', [
                'index' => $i + 1,
                'total' => $total,
                'url' => $pageData['url'] ?? '',
                'fixes' => $result['fixes_count'] ?? 0,
                'error' => $result['message'] ?? null,
            ]);
        }

        return $this->finalizePlan($planId, $project);
    }

    /**
     * Totali dal DB e status 'ready'. 'finalized' => true solo per la richiesta che ha chiuso il piano
     * (è quella che addebita), così due stream sullo stesso piano non addebitano due volte.
     */
    private function finalizePlan(int $planId, array $project): array
    {
        $totals = Database::fetch(
            "SELECT COUNT(*) AS fixes, COALESCE(SUM(time_estimate_minutes), 0) AS time_min,
                    COALESCE(SUM(impact_points), 0) AS impact, COUNT(DISTINCT page_id) AS pages
             FROM sa_page_fixes WHERE plan_id = ?",
            [$planId]
        );

        if ((int) $totals['fixes'] === 0) {
            Database::delete('sa_action_plans', 'id = ?', [$planId]);
            return ['error' => true, 'message' => 'L\'AI non ha prodotto correzioni per le pagine scelte. Nessun credito scalato: riprova.'];
        }

        $healthExpected = min(100, (int) ($project['health_score'] ?? 0) + (int) $totals['impact']);
        $closed = Database::execute(
            "UPDATE sa_action_plans SET total_fixes = ?, estimated_time_minutes = ?, health_expected = ?, status = 'ready'
             WHERE id = ? AND status = 'generating'",
            [(int) $totals['fixes'], (int) $totals['time_min'], $healthExpected, $planId]
        );

        return [
            'success' => true,
            'finalized' => $closed === 1,
            'plan_id' => $planId,
            'total_pages' => (int) $totals['pages'],
            'total_fixes' => (int) $totals['fixes'],
            'health_expected' => $healthExpected,
        ];
    }

    /**
     * Genera fix per una singola pagina via AI
     */
    private function generatePageFixes(int $planId, int $projectId, int $pageId, array $pageData, array $project, int $userId): array
    {
        $prompt = $this->buildPrompt($pageData, $project);

        // charge_credits=false: il piano costa cost_action_plan_generate una volta sola (ActionPlanController::generate)
        $result = $this->ai->analyzeWithSystem($userId, $prompt['system'], $prompt['user'], self::MODULE_SLUG, ['charge_credits' => false]);

        // Reconnect dopo chiamata AI lunga
        Database::reconnect();

        if (isset($result['error'])) {
            return ['error' => true, 'message' => $result['message'] ?? 'Errore AI'];
        }

        // Parse JSON response
        $fixes = $this->parseAiResponse($result['result'] ?? '');

        if (empty($fixes)) {
            return ['error' => true, 'message' => 'Risposta AI non valida'];
        }

        $fixesCount = 0;
        $totalTime = 0;
        $totalImpact = 0;

        // Build list of valid issue IDs for this page (cast to int for strict comparison)
        $validIssueIds = array_map('intval', array_column($pageData['issues'], 'id'));
        $defaultIssueId = $validIssueIds[0] ?? null;

        // Skip if no valid issues
        if (!$defaultIssueId) {
            return ['error' => true, 'message' => 'Nessuna issue valida per la pagina'];
        }

        foreach ($fixes as $fix) {
            // Valida difficulty (ENUM: facile, medio, difficile)
            $validDifficulties = ['facile', 'medio', 'difficile'];
            $difficulty = strtolower($fix['difficulty'] ?? 'medio');
            if (!in_array($difficulty, $validDifficulties)) {
                $difficulty = 'medio';
            }

            // Valida issue_id - deve essere un ID valido dalla lista delle issues di questa pagina
            // Cast to int for strict comparison since AI may return string
            $issueId = (int)($fix['issue_id'] ?? $defaultIssueId);
            if (!in_array($issueId, $validIssueIds, true)) {
                $issueId = $defaultIssueId;
            }

            Database::insert('sa_page_fixes', [
                'plan_id' => $planId,
                'project_id' => $projectId,
                'page_id' => $pageId,
                'issue_id' => $issueId,
                'fix_code' => $fix['fix_code'] ?? null,
                'fix_explanation' => $fix['fix_explanation'] ?? '',
                'priority' => min(10, max(1, (int)($fix['priority'] ?? 5))),
                'difficulty' => $difficulty,
                'time_estimate_minutes' => max(1, (int)($fix['time_estimate_minutes'] ?? 5)),
                'impact_points' => max(1, (int)($fix['impact_points'] ?? 1)),
                'step_order' => $fix['step'] ?? ($fixesCount + 1),
            ]);

            $fixesCount++;
            $totalTime += $fix['time_estimate_minutes'] ?? 5;
            $totalImpact += $fix['impact_points'] ?? 1;
        }

        return [
            'fixes_count' => $fixesCount,
            'total_time' => $totalTime,
            'total_impact' => $totalImpact,
        ];
    }

    /**
     * Recupera piano esistente con fix raggruppati per pagina
     */
    public function getPlan(int $projectId): ?array
    {
        $plan = Database::fetch(
            "SELECT * FROM sa_action_plans WHERE project_id = ? ORDER BY created_at DESC LIMIT 1",
            [$projectId]
        );

        if (!$plan) {
            return null;
        }

        // Recupera fix raggruppati per pagina
        $plan['pages'] = $this->getPagesWithFixes($plan['id']);

        return $plan;
    }

    /**
     * Recupera pagine con fix per un piano
     */
    private function getPagesWithFixes(int $planId): array
    {
        $sql = "
            SELECT
                p.id as page_id,
                p.url,
                p.title as page_title,
                COUNT(f.id) as fixes_count,
                SUM(CASE WHEN f.is_completed = 1 THEN 1 ELSE 0 END) as fixes_completed,
                SUM(f.impact_points) as total_impact,
                SUM(f.time_estimate_minutes) as total_time,
                MAX(f.priority) as max_priority
            FROM sa_page_fixes f
            JOIN sa_pages p ON f.page_id = p.id
            WHERE f.plan_id = ?
            GROUP BY p.id, p.url, p.title
            ORDER BY max_priority DESC, total_impact DESC
        ";

        return Database::fetchAll($sql, [$planId]);
    }

    /**
     * Recupera fix di una pagina specifica
     */
    public function getPageFixes(int $planId, int $pageId): array
    {
        $sql = "
            SELECT
                f.*,
                i.issue_type,
                i.title as issue_title,
                i.severity,
                i.category
            FROM sa_page_fixes f
            LEFT JOIN sa_issues i ON f.issue_id = i.id
            WHERE f.plan_id = ? AND f.page_id = ?
            ORDER BY f.step_order ASC, f.priority DESC
        ";

        return Database::fetchAll($sql, [$planId, $pageId]);
    }

    /**
     * Toggle completamento singolo fix
     */
    public function toggleFixComplete(int $fixId): array
    {
        $fix = Database::fetch("SELECT * FROM sa_page_fixes WHERE id = ?", [$fixId]);

        if (!$fix) {
            return ['error' => true, 'message' => 'Fix non trovato'];
        }

        $newStatus = !$fix['is_completed'];

        Database::update('sa_page_fixes', [
            'is_completed' => $newStatus,
            'completed_at' => $newStatus ? date('Y-m-d H:i:s') : null,
        ], 'id = ?', [$fixId]);

        // Ricalcola stats piano
        $this->recalculatePlanStats($fix['plan_id']);

        // Recupera dati aggiornati
        $plan = Database::fetch("SELECT * FROM sa_action_plans WHERE id = ?", [$fix['plan_id']]);

        return [
            'success' => true,
            'fix_id' => $fixId,
            'is_completed' => $newStatus,
            'plan_progress' => [
                'fixes_completed' => $plan['fixes_completed'],
                'total_fixes' => $plan['total_fixes'],
                'percentage' => $plan['total_fixes'] > 0
                    ? round(($plan['fixes_completed'] / $plan['total_fixes']) * 100)
                    : 0,
            ],
        ];
    }

    /**
     * Raggruppa issue per pagina con calcolo impatto
     */
    private function groupIssuesByPage(int $projectId): array
    {
        $sql = "
            SELECT
                i.*,
                p.url,
                p.title as page_title,
                p.meta_description,
                p.h1_count,
                p.hreflang_tags,
                p.session_id as page_session_id,
                SUBSTRING(p.html_content, 1, 500) as content_snippet
            FROM sa_issues i
            JOIN sa_pages p ON i.page_id = p.id
            WHERE i.project_id = ? AND i.page_id IS NOT NULL
            ORDER BY
                FIELD(i.severity, 'critical', 'warning', 'notice', 'info'),
                i.page_id
        ";

        $issues = Database::fetchAll($sql, [$projectId]);

        $pageGroups = [];
        foreach ($issues as $issue) {
            $pageId = $issue['page_id'];

            if (!isset($pageGroups[$pageId])) {
                $pageGroups[$pageId] = [
                    'page_id' => $pageId,
                    'url' => $issue['url'],
                    'page_title' => $issue['page_title'],
                    'meta_description' => $issue['meta_description'],
                    'h1_count' => $issue['h1_count'],
                    'hreflang_tags' => $issue['hreflang_tags'] ?? null,
                    'page_session_id' => $issue['page_session_id'] ?? null,
                    'content_snippet' => $issue['content_snippet'],
                    'issues' => [],
                    'impact' => 0,
                ];
            }

            $pageGroups[$pageId]['issues'][] = $issue;
            $pageGroups[$pageId]['impact'] += $this->calculateIssueImpact($issue);
        }

        // Ordina per impatto decrescente
        uasort($pageGroups, fn($a, $b) => $b['impact'] <=> $a['impact']);

        return $pageGroups;
    }

    /**
     * Calcola impatto di una singola issue
     */
    private function calculateIssueImpact(array $issue): int
    {
        $severityWeight = match ($issue['severity']) {
            'critical' => 5,
            'warning' => 2,
            'notice' => 1,
            default => 0,
        };

        return $severityWeight;
    }

    /**
     * Costruisce prompt AI per generare fix
     */
    private function buildPrompt(array $pageData, array $project): array
    {
        $issuesJson = json_encode(array_map(function ($issue) use ($pageData) {
            $row = [
                'id' => $issue['id'],
                'type' => $issue['issue_type'],
                'title' => $issue['title'],
                'severity' => $issue['severity'],
                'category' => $issue['category'],
                'affected_element' => $issue['affected_element'] ?? null,
                'recommendation' => $issue['recommendation'] ?? null,
            ];
            // Duplicati: con quali pagine, e se sono la stessa pagina in un'altra lingua (/x/ e /en/x/)
            $others = $this->duplicateUrls($issue, $pageData);
            if ($others) {
                $row['uguale_a'] = $others;
                $row['stessa_pagina_in_altra_lingua'] = LanguageVariants::areVariants(array_merge([$pageData['url']], $others));
            }
            return $row;
        }, $pageData['issues']), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $pageLang = LanguageVariants::language($pageData['url']);
        $langLine = $pageLang
            ? "- Lingua della pagina (dal percorso): {$pageLang}"
            : '- Lingua della pagina: quella principale del sito';
        $hreflang = !empty($pageData['hreflang_tags']) && $pageData['hreflang_tags'] !== '[]' ? 'sì' : 'no';

        $system = <<<PROMPT
Sei un consulente SEO italiano esperto. Genera un PIANO D'AZIONE con fix PRONTI ALL'USO.
Rispondi SOLO con JSON valido, senza markdown o testo aggiuntivo.
PROMPT;

        $user = <<<PROMPT
CONTESTO SITO:
- URL base: {$project['base_url']}
- Punteggio di salute attuale: {$project['health_score']}

PAGINA DA OTTIMIZZARE:
- URL: {$pageData['url']}
- Title attuale: {$pageData['page_title']}
- Meta Description attuale: {$pageData['meta_description']}
- H1 count: {$pageData['h1_count']}
{$langLine}
- Tag hreflang presenti: {$hreflang}
- Contenuto (snippet): {$pageData['content_snippet']}

PROBLEMI RILEVATI SU QUESTA PAGINA:
{$issuesJson}

ISTRUZIONI:
1. Genera fix come STEP NUMERATI (Step 1, Step 2, Step 3...)
2. Ogni fix = codice/testo PRONTO DA COPIARE
3. Considera COERENZA tra fix (es. title e H1 devono essere allineati)
4. Spiegazione MAX 2 frasi in italiano
5. Priorità 1-10 basata su impatto SEO reale
6. Limiti: Title max 60 char, Description max 155 char
7. Stima tempo realistico per ogni fix
8. Scrivi title, description, H1 e testi NELLA LINGUA DELLA PAGINA (una pagina /en/ va in inglese)
9. Se un duplicato ha "stessa_pagina_in_altra_lingua": true, è la traduzione della stessa pagina, NON un duplicato:
   MAI canonical verso l'altra lingua né redirect 301 (toglierebbero quella lingua da Google). Proponi il testo
   tradotto nella lingua della pagina e, se mancano gli hreflang, i tag hreflang reciproci tra le versioni.

OUTPUT JSON (rispondi SOLO con questo formato):
{
  "fixes": [
    {
      "step": 1,
      "issue_id": 123,
      "issue_type": "tipo_issue",
      "fix_code": "codice o testo del fix",
      "fix_explanation": "Spiegazione breve in italiano.",
      "priority": 9,
      "difficulty": "facile",
      "time_estimate_minutes": 2,
      "impact_points": 5
    }
  ]
}
PROMPT;

        return ['system' => $system, 'user' => $user];
    }

    /**
     * Altre pagine con lo stesso title/description di questa (solo per i duplicati), al massimo 5.
     */
    private function duplicateUrls(array $issue, array $pageData): array
    {
        $col = match ($issue['issue_type']) {
            'duplicate_title', 'duplicate_title_budget' => 'title',
            'duplicate_description' => 'meta_description',
            default => null,
        };
        $value = $col === 'title' ? ($pageData['page_title'] ?? '') : ($col ? ($pageData['meta_description'] ?? '') : '');
        if ($col === null || trim((string) $value) === '') {
            return [];
        }
        $rows = Database::fetchAll(
            "SELECT url FROM sa_pages WHERE project_id = ? AND session_id <=> ? AND {$col} = ? AND id <> ? LIMIT 5",
            [(int) $issue['project_id'], $pageData['page_session_id'] ?? null, $value, (int) $pageData['page_id']]
        );
        return array_column($rows, 'url');
    }

    /**
     * Parse risposta AI JSON
     */
    private function parseAiResponse(string $response): array
    {
        // Rimuovi eventuali backtick markdown
        $response = preg_replace('/```json\s*/', '', $response);
        $response = preg_replace('/```\s*/', '', $response);
        $response = trim($response);

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Prova a estrarre JSON da testo
            if (preg_match('/\{[\s\S]*\}/', $response, $matches)) {
                $data = json_decode($matches[0], true);
            }
        }

        return $data['fixes'] ?? [];
    }

    /**
     * Ricalcola stats piano dopo toggle
     */
    private function recalculatePlanStats(int $planId): void
    {
        $stats = Database::fetch("
            SELECT
                COUNT(*) as total_fixes,
                SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as fixes_completed
            FROM sa_page_fixes
            WHERE plan_id = ?
        ", [$planId]);

        $status = 'ready';
        if ($stats['fixes_completed'] > 0 && $stats['fixes_completed'] < $stats['total_fixes']) {
            $status = 'in_progress';
        } elseif ($stats['fixes_completed'] == $stats['total_fixes'] && $stats['total_fixes'] > 0) {
            $status = 'completed';
        }

        Database::update('sa_action_plans', [
            'total_fixes' => $stats['total_fixes'],
            'fixes_completed' => $stats['fixes_completed'],
            'status' => $status,
        ], 'id = ?', [$planId]);
    }

    /**
     * Ottieni costo operazione
     */
    public function getCost(string $operation): float
    {
        return Credits::getCost($operation, self::MODULE_SLUG, 15);
    }

    /**
     * Conta issue per progetto (per preview)
     */
    public function getIssueStats(int $projectId): array
    {
        $counts = $this->issueModel->countBySeverity($projectId);

        $pagesWithIssues = Database::fetch("
            SELECT COUNT(DISTINCT page_id) as count
            FROM sa_issues
            WHERE project_id = ? AND page_id IS NOT NULL
        ", [$projectId]);

        return [
            'total_issues' => $counts['total'],
            'critical' => $counts['critical'],
            'warning' => $counts['warning'],
            'notice' => $counts['notice'],
            'pages_with_issues' => (int) $pagesWithIssues['count'],
        ];
    }

    /**
     * Elimina piano esistente
     */
    public function deletePlan(int $planId, int $projectId): bool
    {
        return Database::delete('sa_action_plans', 'id = ? AND project_id = ?', [$planId, $projectId]) > 0;
    }

    /**
     * Export piano in formato Markdown
     */
    public function exportMarkdown(int $projectId): ?string
    {
        $plan = $this->getPlan($projectId);
        if (!$plan) {
            return null;
        }

        // Per export usiamo find() senza verifica ownership (già verificata dal controller)
        $project = $this->projectModel->find($projectId);

        $md = "# Piano d'Azione SEO - {$project['base_url']}\n";
        $md .= "Generato: " . date('d F Y', strtotime($plan['created_at'])) . "\n";
        $md .= "Health Score: {$plan['health_current']} → {$plan['health_expected']} (+";
        $md .= ($plan['health_expected'] - $plan['health_current']) . " punti)\n";
        $md .= "Tempo stimato: " . $this->formatTime($plan['estimated_time_minutes']) . "\n\n";
        $md .= "---\n\n";

        $pageNum = 1;
        foreach ($plan['pages'] as $page) {
            $fixes = $this->getPageFixes($plan['id'], $page['page_id']);

            $priority = $page['max_priority'] >= 8 ? 'ALTA' : ($page['max_priority'] >= 5 ? 'MEDIA' : 'BASSA');

            $md .= "## Pagina {$pageNum}: {$page['url']}\n";
            $md .= "Impatto: +{$page['total_impact']} punti | ";
            $md .= "Tempo: ~{$page['total_time']} min | ";
            $md .= "Priorità: {$priority}\n\n";

            foreach ($fixes as $fix) {
                $status = $fix['is_completed'] ? '[x]' : '[ ]';
                $md .= "### {$status} Step {$fix['step_order']}: {$fix['issue_title']}\n";
                $md .= "**Fix:**\n```\n{$fix['fix_code']}\n```\n\n";
                $md .= "**Note:** {$fix['fix_explanation']}\n\n";
                $md .= "---\n\n";
            }

            $pageNum++;
        }

        return $md;
    }

    /**
     * Formatta minuti in formato leggibile
     */
    private function formatTime(int $minutes): string
    {
        if ($minutes < 60) {
            return "{$minutes} min";
        }

        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        return $mins > 0 ? "{$hours}h {$mins}m" : "{$hours}h";
    }
}
