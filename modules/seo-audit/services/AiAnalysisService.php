<?php

namespace Modules\SeoAudit\Services;

use Core\Database;
use Core\Credits;
use Services\AiService;
use Modules\SeoAudit\Models\Project;
use Modules\SeoAudit\Models\Issue;

/**
 * AiAnalysisService
 *
 * Servizio per analisi AI degli audit SEO
 */
class AiAnalysisService
{
    private AiService $aiService;
    private AuditAnalyzer $analyzer;
    private Project $projectModel;

    private const MODULE_SLUG = 'seo-audit';

    /**
     * Costi in crediti per tipo di analisi
     */
    private const CREDIT_COSTS = [
        'overview' => 15,
        'category' => 3,
    ];

    public function __construct()
    {
        $this->aiService = new AiService('seo-audit');
        $this->analyzer = new AuditAnalyzer();
        $this->projectModel = new Project();
    }

    /**
     * Genera analisi AI overview del progetto
     */
    public function analyzeOverview(int $projectId, int $userId): array
    {
        // Verifica crediti
        $cost = $this->getCost('overview');
        if (!Credits::hasEnough($userId, $cost)) {
            return [
                'error' => true,
                'message' => 'Crediti insufficienti. Richiesti: ' . $cost,
                'credits_required' => $cost,
            ];
        }

        // Verifica API configurata
        if (!$this->aiService->isConfigured()) {
            return [
                'error' => true,
                'message' => 'API Claude non configurata. Contatta l\'amministratore.',
            ];
        }

        // Prepara dati e prompt
        $project = $this->projectModel->findWithStats($projectId, null);
        if (!$project) {
            return ['error' => true, 'message' => 'Progetto non trovato'];
        }

        $prompt = $this->buildOverviewPrompt($projectId, $project);
        $content = $this->buildOverviewContent($projectId, $project);

        // Chiama API
        $response = $this->callAiApi($prompt, $content);

        if (isset($response['error'])) {
            return $response;
        }

        // Consuma crediti
        Credits::consume($userId, $cost, 'ai_overview', self::MODULE_SLUG, [
            'project_id' => $projectId,
            'analysis_type' => 'overview',
        ]);

        // Salva analisi
        $analysisId = $this->saveAnalysis($projectId, 'overview', null, $response['content'], $cost);

        return [
            'success' => true,
            'analysis_id' => $analysisId,
            'result' => $response['content'],
            'credits_used' => $cost,
        ];
    }

    /**
     * Genera analisi AI per categoria specifica
     */
    public function analyzeCategory(int $projectId, string $category, int $userId): array
    {
        // Verifica categoria valida
        if (!isset(Issue::CATEGORIES[$category])) {
            return ['error' => true, 'message' => 'Categoria non valida'];
        }

        // Verifica crediti
        $cost = $this->getCost('category');
        if (!Credits::hasEnough($userId, $cost)) {
            return [
                'error' => true,
                'message' => 'Crediti insufficienti. Richiesti: ' . $cost,
                'credits_required' => $cost,
            ];
        }

        // Verifica API configurata
        if (!$this->aiService->isConfigured()) {
            return [
                'error' => true,
                'message' => 'API Claude non configurata. Contatta l\'amministratore.',
            ];
        }

        // Prepara dati e prompt
        $project = $this->projectModel->findWithStats($projectId, null);
        if (!$project) {
            return ['error' => true, 'message' => 'Progetto non trovato'];
        }

        $prompt = $this->buildCategoryPrompt($category);
        $content = $this->buildCategoryContent($projectId, $category);

        // Chiama API
        $response = $this->callAiApi($prompt, $content);

        if (isset($response['error'])) {
            return $response;
        }

        // Consuma crediti
        Credits::consume($userId, $cost, 'ai_category', self::MODULE_SLUG, [
            'project_id' => $projectId,
            'analysis_type' => 'category',
            'category' => $category,
        ]);

        // Salva analisi
        $analysisId = $this->saveAnalysis($projectId, 'category', $category, $response['content'], $cost);

        return [
            'success' => true,
            'analysis_id' => $analysisId,
            'result' => $response['content'],
            'credits_used' => $cost,
        ];
    }

    /**
     * Ottieni analisi salvata
     */
    public function getAnalysis(int $projectId, string $type, ?string $category = null): ?array
    {
        $sql = "SELECT * FROM sa_ai_analyses WHERE project_id = ? AND type = ?";
        $params = [$projectId, $type];

        if ($category) {
            $sql .= " AND category = ?";
            $params[] = $category;
        } else {
            $sql .= " AND category IS NULL";
        }

        $sql .= " ORDER BY created_at DESC LIMIT 1";

        return Database::fetch($sql, $params) ?: null;
    }

    /**
     * Lista tutte le analisi di un progetto
     */
    public function getAllAnalyses(int $projectId): array
    {
        $sql = "SELECT * FROM sa_ai_analyses WHERE project_id = ? ORDER BY created_at DESC";
        return Database::fetchAll($sql, [$projectId]);
    }

    /**
     * Costruisce il prompt per analisi overview
     */
    private function buildOverviewPrompt(int $projectId, array $project): string
    {
        return <<<PROMPT
Sei un SEO Specialist senior. Analizza i dati di questo audit SEO completo.

Fornisci un'analisi strutturata con:
1. **PANORAMICA GENERALE** - Stato del sito con focus su visibilità e salute tecnica
2. **TOP 5 PRIORITÀ** - Problemi più urgenti considerando impatto su ranking
3. **CORRELAZIONI** - Problemi tecnici che impattano performance organica
4. **PUNTI DI FORZA** - Cosa funziona bene
5. **ROADMAP CONSIGLIATA** - Ordine di intervento con stima impatto (alto/medio/basso)

Scrivi in italiano. Usa un tono professionale ma accessibile.
Sii specifico con dati e numeri. Evidenzia le correlazioni tra problemi tecnici e performance.
Usa markdown per formattare (headers, liste, bold).
PROMPT;
    }

    /**
     * Costruisce il contenuto dati per analisi overview
     */
    private function buildOverviewContent(int $projectId, array $project): string
    {
        // Statistiche issues
        $issueModel = new Issue();
        $issueCounts = $issueModel->countBySeverity($projectId);
        $categoryStats = $this->analyzer->getStatsByCategory($projectId);

        // Statistiche pagine
        $pageStats = Database::fetch("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN is_indexable = 1 THEN 1 ELSE 0 END) as indexable,
                AVG(load_time_ms) as avg_load_time,
                AVG(word_count) as avg_word_count
            FROM sa_pages WHERE project_id = ?
        ", [$projectId]);

        // Issues per categoria formattate
        $issuesByCat = [];
        foreach ($categoryStats as $slug => $stat) {
            if ($stat['total'] > 0) {
                $issuesByCat[] = sprintf(
                    "- %s: %d totali (%d critici, %d warning, %d notice)",
                    $stat['label'],
                    $stat['total'],
                    $stat['critical'],
                    $stat['warning'],
                    $stat['notice']
                );
            }
        }

        // Top issues
        $topIssues = $this->analyzer->getTopIssuesByImpact($projectId, 10);
        $topIssuesFormatted = [];
        foreach ($topIssues as $issue) {
            $topIssuesFormatted[] = sprintf(
                "- [%s] %s: %d occorrenze",
                strtoupper($issue['severity']),
                $issue['title'],
                $issue['occurrences']
            );
        }

        $content = <<<CONTENT
DATI AUDIT:
- Sito: {$project['base_url']}
- Pagine analizzate: {$pageStats['total']}
- Pagine indicizzabili: {$pageStats['indexable']}
- Health Score: {$project['health_score']}/100
- Tempo medio caricamento: {$pageStats['avg_load_time']}ms
- Parole medie per pagina: {$pageStats['avg_word_count']}

STATISTICHE ISSUES:
- Totali: {$issueCounts['total']}
- Critiche: {$issueCounts['critical']}
- Warning: {$issueCounts['warning']}
- Notice: {$issueCounts['notice']}

ISSUES PER CATEGORIA:
CONTENT;

        $content .= "\n" . implode("\n", $issuesByCat);

        $content .= "\n\nTOP PROBLEMI PIÙ FREQUENTI:\n" . implode("\n", $topIssuesFormatted);

        return $content;
    }

    /**
     * Costruisce il prompt per analisi categoria
     */
    private function buildCategoryPrompt(string $category): string
    {
        $categoryLabel = Issue::CATEGORIES[$category] ?? $category;

        return <<<PROMPT
Sei un SEO Specialist senior. Analizza nel dettaglio la categoria "{$categoryLabel}" di questo audit SEO.

Fornisci:
1. **ANALISI DETTAGLIATA** - Pattern comuni nei problemi rilevati
2. **IMPATTO SEO** - Come questi problemi influenzano ranking e CTR
3. **SOLUZIONI SPECIFICHE** - Per ogni tipo di problema trovato
4. **PRIORITÀ** - Ordina le fix dalla più urgente
5. **TEMPLATE/ESEMPI** - Esempi concreti di come risolvere i problemi principali

Scrivi in italiano. Sii tecnico e pratico con esempi concreti.
Usa markdown per formattare (headers, liste, bold, code blocks per esempi).
PROMPT;
    }

    /**
     * Costruisce il contenuto dati per analisi categoria
     */
    private function buildCategoryContent(int $projectId, string $category): string
    {
        $categoryLabel = Issue::CATEGORIES[$category] ?? $category;

        // Issues di questa categoria
        $issueModel = new Issue();
        $issuesData = $issueModel->getByProject($projectId, 1, 100, ['category' => $category]);

        // Statistiche categoria
        $categoryStats = Database::fetch("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical,
                SUM(CASE WHEN severity = 'warning' THEN 1 ELSE 0 END) as warning,
                SUM(CASE WHEN severity = 'notice' THEN 1 ELSE 0 END) as notice
            FROM sa_issues
            WHERE project_id = ? AND category = ?
        ", [$projectId, $category]);

        // Raggruppa per tipo di issue
        $issuesByType = [];
        foreach ($issuesData['data'] as $issue) {
            $type = $issue['issue_type'];
            if (!isset($issuesByType[$type])) {
                $issuesByType[$type] = [
                    'title' => $issue['title'],
                    'severity' => $issue['severity'],
                    'count' => 0,
                    'examples' => [],
                ];
            }
            $issuesByType[$type]['count']++;
            if (count($issuesByType[$type]['examples']) < 3 && $issue['affected_element']) {
                $issuesByType[$type]['examples'][] = substr($issue['affected_element'], 0, 100);
            }
        }

        // Formatta issues per tipo
        $issuesFormatted = [];
        foreach ($issuesByType as $type => $data) {
            $examples = !empty($data['examples'])
                ? "\n  Esempi: " . implode(', ', $data['examples'])
                : '';
            $issuesFormatted[] = sprintf(
                "- [%s] %s: %d occorrenze%s",
                strtoupper($data['severity']),
                $data['title'],
                $data['count'],
                $examples
            );
        }

        // Pagine più problematiche per questa categoria
        $problemPages = Database::fetchAll("
            SELECT p.url, COUNT(i.id) as issues_count
            FROM sa_pages p
            JOIN sa_issues i ON i.page_id = p.id
            WHERE p.project_id = ? AND i.category = ?
            GROUP BY p.id, p.url
            ORDER BY issues_count DESC
            LIMIT 5
        ", [$projectId, $category]);

        $problemPagesFormatted = [];
        foreach ($problemPages as $page) {
            $problemPagesFormatted[] = "- {$page['url']} ({$page['issues_count']} problemi)";
        }

        $content = <<<CONTENT
CATEGORIA: {$categoryLabel}

STATISTICHE:
- Issues totali: {$categoryStats['total']}
- Critiche: {$categoryStats['critical']}
- Warning: {$categoryStats['warning']}
- Notice: {$categoryStats['notice']}

PROBLEMI RILEVATI PER TIPO:
CONTENT;

        $content .= "\n" . implode("\n", $issuesFormatted);

        if (!empty($problemPagesFormatted)) {
            $content .= "\n\nPAGINE PIÙ PROBLEMATICHE:\n" . implode("\n", $problemPagesFormatted);
        }

        return $content;
    }

    /**
     * Chiama API Claude via shared AiService
     */
    private function callAiApi(string $prompt, string $content, int $userId = 0): array
    {
        if (!$this->aiService->isConfigured()) {
            return ['error' => true, 'message' => 'API Key Claude non configurata'];
        }

        // Use shared AiService
        $result = $this->aiService->analyze($userId, $prompt, $content, self::MODULE_SLUG);

        if (isset($result['error'])) {
            return [
                'error' => true,
                'message' => $result['message'] ?? 'Errore API Claude',
            ];
        }

        return [
            'content' => $result['result'] ?? '',
            'credits_used' => $result['credits_used'] ?? 0,
        ];
    }

    /**
     * Salva analisi nel database
     */
    private function saveAnalysis(int $projectId, string $type, ?string $category, string $result, int $creditsUsed): int
    {
        $analysisId = Database::insert('sa_ai_analyses', [
            'project_id' => $projectId,
            'type' => $type,
            'category' => $category,
            'content' => $result,
            'credits_used' => $creditsUsed,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $analysisId;
    }

    /**
     * Ottieni costo in crediti per tipo analisi
     */
    public function getCost(string $type): int
    {
        // Prova a leggere da module.json
        $moduleJsonPath = dirname(__DIR__) . '/module.json';
        if (file_exists($moduleJsonPath)) {
            $moduleConfig = json_decode(file_get_contents($moduleJsonPath), true);
            $key = $type === 'overview' ? 'ai_overview' : 'ai_category';
            if (isset($moduleConfig['credits'][$key]['cost'])) {
                return (int) $moduleConfig['credits'][$key]['cost'];
            }
        }

        return self::CREDIT_COSTS[$type] ?? 10;
    }

    /**
     * Elimina analisi
     */
    public function deleteAnalysis(int $analysisId, int $projectId): bool
    {
        return Database::delete('sa_ai_analyses', 'id = ? AND project_id = ?', [$analysisId, $projectId]) > 0;
    }
}
