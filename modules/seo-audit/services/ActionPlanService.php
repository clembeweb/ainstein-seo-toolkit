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
    private const BATCH_SIZE = 5; // Pagine per batch AI

    public function __construct()
    {
        $this->ai = new AiService(self::MODULE_SLUG);
        $this->projectModel = new Project();
        $this->issueModel = new Issue();
        $this->pageModel = new Page();
    }

    /**
     * Genera piano d'azione completo per un progetto
     * Nota: I crediti vengono gestiti automaticamente da AiService per ogni chiamata AI
     */
    public function generatePlan(int $projectId, ?int $sessionId, int $userId): array
    {
        // Verifica API configurata
        if (!$this->ai->isConfigured()) {
            return [
                'error' => true,
                'message' => 'API AI non configurata. Contatta l\'amministratore.',
            ];
        }

        // Recupera progetto (userId per verifica ownership)
        $project = $this->projectModel->findWithStats($projectId, $userId);
        if (!$project) {
            return ['error' => true, 'message' => 'Progetto non trovato'];
        }

        // Verifica che ci siano issue
        $issueCounts = $this->issueModel->countBySeverity($projectId);
        if ($issueCounts['total'] === 0) {
            return [
                'error' => true,
                'message' => 'Nessun problema rilevato. Health Score: 100!',
            ];
        }

        // Raggruppa issue per pagina
        $pageGroups = $this->groupIssuesByPage($projectId);
        if (empty($pageGroups)) {
            return ['error' => true, 'message' => 'Nessuna issue associata a pagine'];
        }

        // Crea record piano
        $planId = Database::insert('sa_action_plans', [
            'project_id' => $projectId,
            'session_id' => $sessionId,
            'status' => 'generating',
            'health_current' => $project['health_score'] ?? 0,
            'total_pages' => count($pageGroups),
        ]);

        $totalFixes = 0;
        $totalTime = 0;
        $totalImpact = 0;
        $errors = [];

        // Processa pagine in batch
        $batches = array_chunk($pageGroups, self::BATCH_SIZE, true);

        foreach ($batches as $batch) {
            foreach ($batch as $pageId => $pageData) {
                $result = $this->generatePageFixes($planId, $projectId, $pageId, $pageData, $project, $userId);

                if (isset($result['error'])) {
                    $errors[] = $result['message'];
                    continue;
                }

                $totalFixes += $result['fixes_count'];
                $totalTime += $result['total_time'];
                $totalImpact += $result['total_impact'];
            }

            // Reconnect dopo ogni batch per evitare timeout
            Database::reconnect();
        }

        // Calcola health score atteso
        $healthExpected = min(100, ($project['health_score'] ?? 0) + $totalImpact);

        // Aggiorna piano con totali
        Database::update('sa_action_plans', [
            'total_fixes' => $totalFixes,
            'estimated_time_minutes' => $totalTime,
            'health_expected' => $healthExpected,
            'status' => 'ready',
        ], 'id = ?', [$planId]);

        // Crediti già consumati da AiService per ogni chiamata AI

        return [
            'success' => true,
            'plan_id' => $planId,
            'total_pages' => count($pageGroups),
            'total_fixes' => $totalFixes,
            'estimated_time_minutes' => $totalTime,
            'health_current' => $project['health_score'] ?? 0,
            'health_expected' => $healthExpected,
            'errors' => $errors,
        ];
    }

    /**
     * Genera fix per una singola pagina via AI
     */
    private function generatePageFixes(int $planId, int $projectId, int $pageId, array $pageData, array $project, int $userId): array
    {
        $prompt = $this->buildPrompt($pageData, $project);

        $result = $this->ai->analyzeWithSystem($userId, $prompt['system'], $prompt['user'], self::MODULE_SLUG);

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
        $issuesJson = json_encode(array_map(function ($issue) {
            return [
                'id' => $issue['id'],
                'type' => $issue['issue_type'],
                'title' => $issue['title'],
                'severity' => $issue['severity'],
                'category' => $issue['category'],
                'affected_element' => $issue['affected_element'] ?? null,
                'recommendation' => $issue['recommendation'] ?? null,
            ];
        }, $pageData['issues']), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $system = <<<PROMPT
Sei un consulente SEO italiano esperto. Genera un PIANO D'AZIONE con fix PRONTI ALL'USO.
Rispondi SOLO con JSON valido, senza markdown o testo aggiuntivo.
PROMPT;

        $user = <<<PROMPT
CONTESTO SITO:
- URL base: {$project['base_url']}
- Health Score attuale: {$project['health_score']}

PAGINA DA OTTIMIZZARE:
- URL: {$pageData['url']}
- Title attuale: {$pageData['page_title']}
- Meta Description attuale: {$pageData['meta_description']}
- H1 count: {$pageData['h1_count']}
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
