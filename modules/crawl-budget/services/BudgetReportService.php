<?php

namespace Modules\CrawlBudget\Services;

use Core\Database;
use Core\Credits;
use Services\AiService;
use Modules\CrawlBudget\Models\Page;
use Modules\CrawlBudget\Models\Issue;
use Modules\CrawlBudget\Models\Report;
use Modules\CrawlBudget\Models\Project;
use Modules\CrawlBudget\Models\CrawlSession;

/**
 * BudgetReportService
 *
 * Genera report AI tramite Claude con analisi dei dati di crawl.
 * Struttura report: Executive Summary, Impatto, Top 5 Azioni, Analisi per Categoria, Quick Wins.
 */
class BudgetReportService
{
    private const MODULE_SLUG = 'crawl-budget';

    private AiService $ai;
    private Page $pageModel;
    private Issue $issueModel;
    private Report $reportModel;
    private Project $projectModel;

    public function __construct()
    {
        $this->ai = new AiService(self::MODULE_SLUG);
        $this->pageModel = new Page();
        $this->issueModel = new Issue();
        $this->reportModel = new Report();
        $this->projectModel = new Project();
    }

    /**
     * Genera report AI per una sessione di crawl.
     *
     * @return array ['success' => true, 'report_id' => int] o ['error' => true, 'message' => string]
     */
    public function generate(int $projectId, int $sessionId, int $userId): array
    {
        // Verifica API configurata
        if (!$this->ai->isConfigured()) {
            return [
                'error' => true,
                'message' => 'API AI non configurata. Contatta l\'amministratore.',
            ];
        }

        // Verifica crediti
        $cost = Credits::getCost('report_generate', self::MODULE_SLUG, 5);
        if (!Credits::hasEnough($userId, $cost)) {
            return [
                'error' => true,
                'message' => 'Crediti insufficienti per generare il report (' . $cost . ' crediti necessari).',
            ];
        }

        // Recupera progetto
        $project = $this->projectModel->find($projectId);
        if (!$project) {
            return ['error' => true, 'message' => 'Progetto non trovato.'];
        }

        // Raccogli dati per il report
        $data = $this->gatherReportData($projectId, $sessionId, $project);

        if (empty($data['total_pages'])) {
            return ['error' => true, 'message' => 'Nessuna pagina analizzata in questa sessione.'];
        }

        // Costruisci prompt
        $prompt = $this->buildPrompt($data);
        $dataContext = $this->buildDataContext($data);

        // Chiama AI
        $result = $this->ai->analyze($userId, $prompt, $dataContext, self::MODULE_SLUG);

        Database::reconnect();

        if (isset($result['error'])) {
            return [
                'error' => true,
                'message' => $result['message'] ?? 'Errore nella generazione del report AI.',
            ];
        }

        $aiResponse = $result['result'] ?? '';
        if (empty($aiResponse)) {
            return ['error' => true, 'message' => 'Risposta AI vuota.'];
        }

        // Estrai summary (primo paragrafo)
        $summary = $this->extractSummary($aiResponse);

        // Estrai azioni prioritarie
        $priorityActions = $this->extractPriorityActions($aiResponse);

        // Impatto stimato per categoria
        $estimatedImpact = [
            'redirect' => $data['issue_categories']['redirect'] ?? 0,
            'waste' => $data['issue_categories']['waste'] ?? 0,
            'indexability' => $data['issue_categories']['indexability'] ?? 0,
            'waste_percentage' => $data['waste_percentage'] ?? 0,
            'score' => $data['score'] ?? 0,
        ];

        // Salva report
        $reportId = $this->reportModel->create([
            'project_id' => $projectId,
            'session_id' => $sessionId,
            'ai_response' => $aiResponse,
            'summary' => $summary,
            'priority_actions' => $priorityActions,
            'estimated_impact' => $estimatedImpact,
        ]);

        // Consuma crediti
        Credits::consume($userId, $cost, 'report_generate', self::MODULE_SLUG);

        return [
            'success' => true,
            'report_id' => $reportId,
        ];
    }

    /**
     * Raccoglie tutti i dati necessari per il report.
     */
    private function gatherReportData(int $projectId, int $sessionId, array $project): array
    {
        $data = [];

        $data['domain'] = $project['domain'] ?? '';
        $data['project_name'] = $project['name'] ?? '';

        // Totale pagine
        $data['total_pages'] = $this->pageModel->countBySession($sessionId, 'crawled');

        // Distribuzione status code
        $data['status_distribution'] = $this->pageModel->getStatusDistribution($sessionId);

        // Riepilogo issue per categoria e severity
        $issueSummary = $this->issueModel->getSummaryBySession($sessionId);
        $data['issue_summary'] = $issueSummary;

        // Conta totali per severity
        $data['severity_counts'] = $this->issueModel->countBySeverity($sessionId);

        // Conta per categoria
        $categoryTotals = [];
        foreach ($issueSummary as $row) {
            $cat = $row['category'];
            $categoryTotals[$cat] = ($categoryTotals[$cat] ?? 0) + (int) $row['cnt'];
        }
        $data['issue_categories'] = $categoryTotals;

        // Top 20 issue piu gravi
        $data['top_issues'] = $this->issueModel->getTopIssues($sessionId, 20);

        // Top 5 redirect chain piu lunghe
        $data['top_chains'] = $this->pageModel->getTopRedirectChains($sessionId, 5);

        // Score
        $analyzer = new BudgetAnalyzerService();
        $data['score'] = $analyzer->calculateScore($sessionId);
        $data['score_label'] = BudgetAnalyzerService::getScoreLabel($data['score']);

        // Waste percentage
        $wasteRow = Database::fetch(
            "SELECT COUNT(DISTINCT p.id) as cnt FROM cb_pages p
             WHERE p.session_id = ? AND p.status = 'crawled' AND (
                 p.http_status < 200 OR p.http_status >= 300
                 OR (p.word_count < 100 AND p.http_status = 200)
                 OR (p.has_parameters = 1 AND (p.canonical_url IS NULL OR p.canonical_url LIKE '%?%'))
             )",
            [$sessionId]
        );
        $wastePagesCount = (int) ($wasteRow['cnt'] ?? 0);
        $data['waste_percentage'] = $data['total_pages'] > 0
            ? round(($wastePagesCount / $data['total_pages']) * 100, 1)
            : 0;

        return $data;
    }

    /**
     * Costruisce il prompt di sistema per Claude.
     */
    private function buildPrompt(array $data): string
    {
        return "Sei un esperto SEO specializzato in crawl budget optimization.
Analizza i dati di crawl forniti e genera un report strutturato in italiano.

Il report deve avere queste 5 sezioni:
1. EXECUTIVE SUMMARY (3-4 righe: stato generale, score, problemi principali)
2. IMPATTO STIMATO (% crawl budget sprecato, numero pagine problematiche per categoria)
3. TOP 5 AZIONI PRIORITARIE (ordinate per impatto, con URL specifici se disponibili)
4. ANALISI PER CATEGORIA (Redirect / Pagine Spreco / Indexability — dettagli e raccomandazioni)
5. QUICK WINS (fix facili con alto impatto, implementabili in poche ore)

Rispondi SOLO con il report in formato HTML (usa tag h2, h3, p, ul, li, strong, em).
NON aggiungere tag html, head, body — solo il contenuto del report.
Sii specifico: cita URL, numeri e problemi concreti.";
    }

    /**
     * Costruisce il contesto dati per Claude.
     */
    private function buildDataContext(array $data): string
    {
        $lines = [];

        $lines[] = "DOMINIO: {$data['domain']}";
        $lines[] = "SCORE CRAWL BUDGET: {$data['score']}/100 ({$data['score_label']})";
        $lines[] = "PAGINE ANALIZZATE: {$data['total_pages']}";
        $lines[] = "% CRAWL BUDGET SPRECATO: {$data['waste_percentage']}%";
        $lines[] = "";

        // Severity totals
        $sev = $data['severity_counts'];
        $lines[] = "PROBLEMI TOTALI:";
        $lines[] = "- Critici: {$sev['critical']}";
        $lines[] = "- Warning: {$sev['warning']}";
        $lines[] = "- Notice: {$sev['notice']}";
        $lines[] = "";

        // Status distribution
        $lines[] = "DISTRIBUZIONE STATUS CODE:";
        foreach ($data['status_distribution'] as $row) {
            $lines[] = "- {$row['status_group']}: {$row['cnt']} pagine";
        }
        $lines[] = "";

        // Issue per categoria
        $lines[] = "PROBLEMI PER CATEGORIA:";
        foreach ($data['issue_categories'] as $cat => $cnt) {
            $catLabel = match ($cat) {
                'redirect' => 'Redirect',
                'waste' => 'Pagine Spreco',
                'indexability' => 'Indexability',
                default => ucfirst($cat),
            };
            $lines[] = "- {$catLabel}: {$cnt}";
        }
        $lines[] = "";

        // Riepilogo issue dettagliato
        $lines[] = "RIEPILOGO PROBLEMI (categoria / severity / conteggio):";
        foreach ($data['issue_summary'] as $row) {
            $lines[] = "- {$row['category']} / {$row['severity']}: {$row['cnt']}";
        }
        $lines[] = "";

        // Top 20 issue
        $lines[] = "TOP 20 PROBLEMI PIU GRAVI:";
        foreach ($data['top_issues'] as $i => $issue) {
            $num = $i + 1;
            $url = $issue['page_url'] ?? 'N/A';
            $lines[] = "{$num}. [{$issue['severity']}] {$issue['title']} — URL: {$url} (tipo: {$issue['type']})";
        }
        $lines[] = "";

        // Top redirect chains
        if (!empty($data['top_chains'])) {
            $lines[] = "CATENE DI REDIRECT PIU LUNGHE:";
            foreach ($data['top_chains'] as $chain) {
                $lines[] = "- {$chain['url']} → {$chain['redirect_target']} ({$chain['redirect_hops']} hop, status {$chain['http_status']})";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Estrai executive summary (primo blocco di testo).
     */
    private function extractSummary(string $html): string
    {
        // Cerca il primo paragrafo dopo l'h2 del summary
        if (preg_match('/<p>(.*?)<\/p>/si', $html, $match)) {
            return strip_tags($match[1]);
        }

        // Fallback: primi 500 caratteri
        $text = strip_tags($html);
        return mb_substr(trim($text), 0, 500);
    }

    /**
     * Estrai azioni prioritarie come array strutturato.
     */
    private function extractPriorityActions(string $html): array
    {
        $actions = [];

        // Cerca lista dopo "AZIONI PRIORITARIE" o "TOP 5"
        if (preg_match('/(?:azioni prioritarie|top 5)[^<]*<\/h[23]>\s*(?:<p>[^<]*<\/p>\s*)?<[ou]l>(.*?)<\/[ou]l>/si', $html, $match)) {
            if (preg_match_all('/<li>(.*?)<\/li>/si', $match[1], $items)) {
                foreach ($items[1] as $item) {
                    $actions[] = trim(strip_tags($item));
                }
            }
        }

        // Se non trovate, cerca tutti i <li> dopo il terzo h2
        if (empty($actions) && preg_match_all('/<li>(.*?)<\/li>/si', $html, $allItems)) {
            // Prendi i primi 5
            foreach (array_slice($allItems[1], 0, 5) as $item) {
                $actions[] = trim(strip_tags($item));
            }
        }

        return array_slice($actions, 0, 5);
    }

    /**
     * Costo crediti per operazione.
     */
    public function getCost(string $operation): float
    {
        return Credits::getCost($operation, self::MODULE_SLUG, 5);
    }
}
