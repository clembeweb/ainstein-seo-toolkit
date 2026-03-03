<?php

namespace Modules\SeoAudit\Services;

use Core\Database;
use Core\Credits;
use Core\ModuleLoader;
use Services\AiService;
use Modules\SeoAudit\Models\Project;
use Modules\SeoAudit\Models\Page;
use Modules\SeoAudit\Models\Issue;
use Modules\SeoAudit\Models\UnifiedReport;

/**
 * UnifiedReportService
 *
 * Genera report AI unificati che combinano dati on-page e crawl budget.
 * Il report include: executive summary, issue card, positivi, timeline e azioni prioritarie.
 *
 * Flusso:
 * 1. Verifica crediti
 * 2. Raccoglie dati on-page (health score, issues per categoria/severity)
 * 3. Raccoglie dati budget (budget score, waste%, redirect chain, issue budget)
 * 4. Rileva profilo sito
 * 5. Costruisce prompt AI contestualizzato
 * 6. Chiama AiService::analyze()
 * 7. Parsa risposta JSON
 * 8. Salva in sa_unified_reports + file system
 * 9. Consuma crediti
 */
class UnifiedReportService
{
    private const MODULE_SLUG = 'seo-audit';

    private AiService $ai;
    private Project $projectModel;
    private Page $pageModel;
    private Issue $issueModel;
    private UnifiedReport $reportModel;
    private SiteProfileDetector $profileDetector;
    private BudgetScoreCalculator $budgetCalculator;
    private AuditAnalyzer $auditAnalyzer;

    public function __construct()
    {
        $this->ai = new AiService(self::MODULE_SLUG);
        $this->projectModel = new Project();
        $this->pageModel = new Page();
        $this->issueModel = new Issue();
        $this->reportModel = new UnifiedReport();
        $this->profileDetector = new SiteProfileDetector();
        $this->budgetCalculator = new BudgetScoreCalculator();
        $this->auditAnalyzer = new AuditAnalyzer();
    }

    /**
     * Genera report unificato AI per un progetto.
     *
     * @return array ['success' => true, 'report_id' => int, 'html_path' => string]
     *               o ['error' => true, 'message' => string]
     */
    public function generate(int $projectId, int $userId, ?int $sessionId = null): array
    {
        // 1. Verifica API configurata
        if (!$this->ai->isConfigured()) {
            return [
                'error' => true,
                'message' => 'API AI non configurata. Contatta l\'amministratore.',
            ];
        }

        // 2. Verifica crediti
        $cost = (int) ModuleLoader::getSetting(self::MODULE_SLUG, 'cost_unified_report', 15);
        if (!Credits::hasEnough($userId, $cost)) {
            return [
                'error' => true,
                'message' => 'Crediti insufficienti per generare il report (' . $cost . ' crediti necessari).',
            ];
        }

        // 3. Recupera progetto
        $project = $this->projectModel->find($projectId);
        if (!$project) {
            return ['error' => true, 'message' => 'Progetto non trovato.'];
        }

        // 4. Raccogli tutti i dati
        $data = $this->gatherData($projectId, $sessionId, $project);

        if ($data['total_pages'] === 0) {
            return ['error' => true, 'message' => 'Nessuna pagina analizzata. Esegui prima un crawl.'];
        }

        // 5. Costruisci prompt e contesto
        $prompt = $this->buildPrompt($data);
        $dataContext = $this->buildDataContext($data);

        // 6. Chiama AI
        $result = $this->ai->analyze($userId, $prompt, $dataContext, self::MODULE_SLUG);

        // 7. SEMPRE reconnect dopo AI call
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

        // 8. Parse JSON response (cleanup markdown fences)
        $parsed = $this->parseAiResponse($aiResponse);

        if ($parsed === null) {
            return ['error' => true, 'message' => 'Impossibile interpretare la risposta AI. Riprova.'];
        }

        // 9. Salva report HTML su file system
        $htmlPath = $this->saveHtmlReport($projectId, $parsed, $data);

        // 10. Salva nel database
        $reportId = $this->reportModel->create([
            'project_id' => $projectId,
            'session_id' => $sessionId,
            'report_type' => 'unified',
            'html_content' => $htmlPath ? file_get_contents($htmlPath) : json_encode($parsed, JSON_UNESCAPED_UNICODE),
            'summary' => $parsed['executive_summary'] ?? '',
            'priority_actions' => $parsed['priority_actions'] ?? [],
            'estimated_impact' => $parsed['estimated_impact'] ?? [],
            'site_profile' => $data['site_profile'],
            'health_score' => $data['health_score'],
            'budget_score' => $data['budget_score'],
            'waste_percentage' => $data['waste_percentage'],
            'credits_used' => $cost,
        ]);

        // 11. Consuma crediti
        Credits::consume($userId, $cost, 'unified_report', self::MODULE_SLUG);

        // 12. Log attivita
        $this->projectModel->logActivity($projectId, $userId, 'unified_report_generated', [
            'report_id' => $reportId,
            'health_score' => $data['health_score'],
            'budget_score' => $data['budget_score'],
        ]);

        return [
            'success' => true,
            'report_id' => $reportId,
            'html_path' => $htmlPath,
        ];
    }

    /**
     * Raccoglie tutti i dati necessari per il report.
     */
    private function gatherData(int $projectId, ?int $sessionId, array $project): array
    {
        $data = [];

        // Info progetto
        $data['domain'] = $project['base_url'] ?? '';
        $data['project_name'] = $project['name'] ?? '';

        // === DATI ON-PAGE ===

        // Health score
        $data['health_score'] = (int) ($project['health_score'] ?? $this->auditAnalyzer->calculateHealthScore($projectId));

        // Statistiche pagine
        $pageStats = $this->pageModel->getStats($projectId);
        $data['total_pages'] = $pageStats['total'];
        $data['page_stats'] = $pageStats;

        // Issues per severity
        $data['issue_severity'] = $this->issueModel->countBySeverity($projectId);

        // Issues per categoria (include tutte le categorie: on-page + budget)
        $data['issue_categories'] = $this->issueModel->countByCategory($projectId);

        // Top issues per impatto
        $data['top_issues'] = $this->auditAnalyzer->getTopIssuesByImpact($projectId, 15);

        // Status distribution
        $data['status_distribution'] = $this->pageModel->getStatusDistribution($projectId, $sessionId);

        // === DATI CRAWL BUDGET ===

        // Budget score
        $budgetData = $this->budgetCalculator->calculate($projectId, $sessionId);
        $data['budget_score'] = $budgetData['score'];
        $data['budget_label'] = $budgetData['label'];
        $data['waste_percentage'] = $budgetData['waste_percentage'];
        $data['waste_pages'] = $budgetData['waste_pages'];
        $data['budget_severity'] = $budgetData['severity_counts'];

        // Top redirect chains
        $data['top_chains'] = $this->pageModel->getTopRedirectChains($projectId, 5, $sessionId);

        // === PROFILO SITO ===
        $data['site_profile'] = $this->profileDetector->detect($projectId);

        return $data;
    }

    /**
     * Costruisce il prompt di sistema per l'AI.
     */
    private function buildPrompt(array $data): string
    {
        $siteType = $data['site_profile']['type'] ?? 'generic';
        $siteSize = $data['site_profile']['size_label'] ?? 'N/D';

        return "Sei un consulente SEO senior specializzato in audit tecnici e crawl budget optimization.
Analizza i dati di audit forniti e genera un report strutturato in formato JSON.

CONTESTO SITO:
- Tipo: {$siteType}
- Dimensione: {$siteSize}
- Health Score On-Page: {$data['health_score']}/100
- Budget Score Crawl: {$data['budget_score']}/100
- Spreco Crawl Budget: {$data['waste_percentage']}%

ISTRUZIONI:
1. Contestualizza le raccomandazioni in base al tipo di sito (es. per e-commerce dare priorita a pagine prodotto, per blog alla struttura categorie)
2. Le issue devono essere ordinate per impatto reale sul business, non solo per severity tecnica
3. Includi esempi di codice dove utile (meta tag, robots.txt, redirect .htaccess)
4. La timeline deve essere realistica per la dimensione del sito
5. Rispondi SOLO con JSON valido, senza markdown fence o testo aggiuntivo

FORMATO RISPOSTA (JSON):
{
    \"executive_summary\": \"Riassunto esecutivo 3-5 righe in italiano\",
    \"issues\": [
        {
            \"id\": 1,
            \"title\": \"Titolo problema\",
            \"severity\": \"critical|important|minor\",
            \"impact\": \"Descrizione impatto su ranking/crawl\",
            \"description\": \"Spiegazione dettagliata del problema\",
            \"fix\": \"Come risolvere step by step\",
            \"affected_urls\": [\"url1\", \"url2\"],
            \"code_example\": \"codice opzionale\"
        }
    ],
    \"positives\": [
        {\"title\": \"Aspetto positivo\", \"description\": \"Perche e positivo\"}
    ],
    \"timeline\": {
        \"week1\": {\"title\": \"Settimana 1: ...\", \"actions\": [\"Azione 1\", \"Azione 2\"]},
        \"week2_3\": {\"title\": \"Settimane 2-3: ...\", \"actions\": [\"Azione 1\", \"Azione 2\"]},
        \"week4_plus\": {\"title\": \"Settimana 4+: ...\", \"actions\": [\"Azione 1\", \"Azione 2\"]}
    },
    \"priority_actions\": [\"Azione prioritaria 1\", \"Azione prioritaria 2\", \"Azione prioritaria 3\"],
    \"estimated_impact\": {
        \"crawl_efficiency\": \"+X%\",
        \"index_coverage\": \"+X%\",
        \"pages_to_fix\": N
    }
}";
    }

    /**
     * Costruisce il contesto dati da inviare all'AI.
     */
    private function buildDataContext(array $data): string
    {
        $lines = [];

        // Header
        $lines[] = "=== DATI AUDIT SEO: {$data['domain']} ===";
        $lines[] = "";

        // Profilo sito
        $profile = $data['site_profile'];
        $lines[] = "PROFILO SITO:";
        $lines[] = "- Tipo: {$profile['type']}";
        $lines[] = "- Dimensione: {$profile['size_label']}";
        $lines[] = "- Profondita media: {$profile['avg_depth']}";
        $lines[] = "- Sitemap: " . ($profile['has_sitemap'] ? 'Si' : 'No');
        $lines[] = "- Rapporto link interni: " . round($profile['internal_links_ratio'] * 100, 1) . '%';
        $lines[] = "";

        // Score
        $lines[] = "SCORE:";
        $lines[] = "- Health Score On-Page: {$data['health_score']}/100";
        $lines[] = "- Budget Score Crawl: {$data['budget_score']}/100 ({$data['budget_label']})";
        $lines[] = "- Spreco Crawl Budget: {$data['waste_percentage']}% ({$data['waste_pages']} pagine)";
        $lines[] = "";

        // Statistiche pagine
        $ps = $data['page_stats'];
        $lines[] = "PAGINE ({$ps['total']} totali):";
        $lines[] = "- Crawlate: {$ps['crawled']}";
        $lines[] = "- 2xx: {$ps['status_2xx']} | 3xx: {$ps['status_3xx']} | 4xx: {$ps['status_4xx']} | 5xx: {$ps['status_5xx']}";
        $lines[] = "- Indicizzabili: {$ps['indexable']} | Non indicizzabili: {$ps['not_indexable']}";
        $lines[] = "- Tempo medio caricamento: {$ps['avg_load_time']}ms";
        $lines[] = "- Parole medie: {$ps['avg_word_count']}";
        $lines[] = "";

        // Issues per severity
        $sev = $data['issue_severity'];
        $lines[] = "PROBLEMI PER GRAVITA:";
        $lines[] = "- Critici: {$sev['critical']}";
        $lines[] = "- Warning: {$sev['warning']}";
        $lines[] = "- Notice: {$sev['notice']}";
        $lines[] = "- Totale: {$sev['total']}";
        $lines[] = "";

        // Issues per categoria
        $lines[] = "PROBLEMI PER CATEGORIA:";
        foreach ($data['issue_categories'] as $cat => $counts) {
            $label = $counts['label'] ?? $cat;
            $lines[] = "- {$label}: {$counts['total']} (critici: {$counts['critical']}, warning: {$counts['warning']}, notice: {$counts['notice']})";
        }
        $lines[] = "";

        // Budget severity breakdown
        $bs = $data['budget_severity'];
        $lines[] = "PROBLEMI CRAWL BUDGET:";
        $lines[] = "- Critici: {$bs['critical']}";
        $lines[] = "- Warning: {$bs['warning']}";
        $lines[] = "- Notice: {$bs['notice']}";
        $lines[] = "";

        // Top issues per impatto
        $lines[] = "TOP 15 PROBLEMI PIU GRAVI:";
        foreach ($data['top_issues'] as $i => $issue) {
            $num = $i + 1;
            $occurrences = $issue['occurrences'] ?? 1;
            $examples = is_array($issue['examples'] ?? null) ? implode(', ', array_filter($issue['examples'])) : '';
            $lines[] = "{$num}. [{$issue['severity']}] {$issue['title']} ({$issue['category']}) — {$occurrences} occorrenze";
            if ($examples) {
                $lines[] = "   Esempi: {$examples}";
            }
        }
        $lines[] = "";

        // Status distribution
        $sd = $data['status_distribution'];
        $lines[] = "DISTRIBUZIONE STATUS CODE:";
        $lines[] = "- 2xx: " . ($sd['2xx'] ?? 0);
        $lines[] = "- 3xx: " . ($sd['3xx'] ?? 0);
        $lines[] = "- 4xx: " . ($sd['4xx'] ?? 0);
        $lines[] = "- 5xx: " . ($sd['5xx'] ?? 0);
        $lines[] = "";

        // Top redirect chains
        if (!empty($data['top_chains'])) {
            $lines[] = "CATENE DI REDIRECT PIU LUNGHE:";
            foreach ($data['top_chains'] as $chain) {
                $hops = $chain['redirect_hops'] ?? 0;
                $target = $chain['redirect_target'] ?? 'N/A';
                $status = $chain['status_code'] ?? 'N/A';
                $lines[] = "- {$chain['url']} -> {$target} ({$hops} hop, status {$status})";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Parsa la risposta AI, rimuovendo eventuali fence markdown.
     */
    private function parseAiResponse(string $response): ?array
    {
        // Rimuovi fence markdown ```json ... ```
        $cleaned = preg_replace('/^```(?:json)?\s*/i', '', trim($response));
        $cleaned = preg_replace('/\s*```\s*$/', '', $cleaned);
        $cleaned = trim($cleaned);

        $parsed = json_decode($cleaned, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Tentativo: cerca JSON nel testo
            if (preg_match('/\{[\s\S]*\}/u', $response, $match)) {
                $parsed = json_decode($match[0], true);
            }
        }

        if (!is_array($parsed)) {
            return null;
        }

        // Valida struttura minima
        if (!isset($parsed['executive_summary']) && !isset($parsed['issues'])) {
            return null;
        }

        // Normalizza campi opzionali
        $parsed['executive_summary'] = $parsed['executive_summary'] ?? '';
        $parsed['issues'] = $parsed['issues'] ?? [];
        $parsed['positives'] = $parsed['positives'] ?? [];
        $parsed['timeline'] = $parsed['timeline'] ?? [];
        $parsed['priority_actions'] = $parsed['priority_actions'] ?? [];
        $parsed['estimated_impact'] = $parsed['estimated_impact'] ?? [];

        return $parsed;
    }

    /**
     * Salva il report HTML standalone su file system.
     * Per ora salva il JSON raw + summary. Il template HTML verra creato in un task separato.
     */
    private function saveHtmlReport(int $projectId, array $parsed, array $data): ?string
    {
        $dir = $this->getReportDir($projectId);
        if (!$dir) {
            return null;
        }

        $filename = 'report-' . date('Y-m-d-His') . '.json';
        $path = $dir . '/' . $filename;

        $reportData = [
            'generated_at' => date('Y-m-d H:i:s'),
            'domain' => $data['domain'],
            'project_name' => $data['project_name'],
            'health_score' => $data['health_score'],
            'budget_score' => $data['budget_score'],
            'waste_percentage' => $data['waste_percentage'],
            'site_profile' => $data['site_profile'],
            'ai_report' => $parsed,
        ];

        $written = file_put_contents($path, json_encode($reportData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return $written !== false ? $path : null;
    }

    /**
     * Ottieni (e crea se necessario) la directory report per il progetto.
     */
    private function getReportDir(int $projectId): ?string
    {
        $baseDir = dirname(__DIR__, 3) . '/storage/reports/' . $projectId;

        if (!is_dir($baseDir)) {
            if (!mkdir($baseDir, 0755, true)) {
                return null;
            }
        }

        return $baseDir;
    }

    /**
     * Ottieni il costo crediti per la generazione.
     */
    public function getCost(): int
    {
        return (int) ModuleLoader::getSetting(self::MODULE_SLUG, 'cost_unified_report', 15);
    }
}
