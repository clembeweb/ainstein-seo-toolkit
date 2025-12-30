<?php

namespace Modules\SeoTracking\Services;

require_once __DIR__ . '/../../../services/AiService.php';

use Core\Credits;
use Modules\SeoTracking\Models\Project;
use Modules\SeoTracking\Models\AiReport;
use Modules\SeoTracking\Models\GscDaily;
use Modules\SeoTracking\Models\Ga4Daily;
use Modules\SeoTracking\Models\Keyword;
use Modules\SeoTracking\Models\Alert;
use Services\AiService;

/**
 * AiReportService
 * Genera report AI con Claude tramite AiService centralizzato
 */
class AiReportService
{
    private Project $project;
    private AiReport $aiReport;
    private GscDaily $gscDaily;
    private Ga4Daily $ga4Daily;
    private Keyword $keyword;
    private Alert $alert;
    private AiService $aiService;

    public function __construct()
    {
        $this->project = new Project();
        $this->aiReport = new AiReport();
        $this->gscDaily = new GscDaily();
        $this->ga4Daily = new Ga4Daily();
        $this->keyword = new Keyword();
        $this->alert = new Alert();
        $this->aiService = new AiService('seo-tracking');
    }

    /**
     * Verifica se il servizio e configurato
     */
    public function isConfigured(): bool
    {
        return $this->aiService->isConfigured();
    }

    /**
     * Genera report settimanale
     */
    public function generateWeeklyDigest(int $projectId, int $userId): ?array
    {
        $project = $this->project->find($projectId);

        if (!$project || !$this->isConfigured()) {
            return null;
        }

        // Raccogli dati
        $endDate = date('Y-m-d', strtotime('-1 day'));
        $startDate = date('Y-m-d', strtotime('-7 days'));
        $prevEndDate = date('Y-m-d', strtotime('-8 days'));
        $prevStartDate = date('Y-m-d', strtotime('-14 days'));

        $gscComparison = $this->gscDaily->comparePeriods($projectId, $startDate, $endDate, $prevStartDate, $prevEndDate);
        $ga4Comparison = $this->ga4Daily->comparePeriods($projectId, $startDate, $endDate, $prevStartDate, $prevEndDate);
        $topMovers = $this->keyword->getTopMovers($projectId, 10);
        $recentAlerts = $this->alert->getByProject($projectId, ['limit' => 10]);

        // Costruisci prompt
        $prompt = $this->buildWeeklyPrompt($project, $gscComparison, $ga4Comparison, $topMovers, $recentAlerts, $startDate, $endDate);

        // Chiama AI tramite AiService (gestisce crediti internamente)
        $response = $this->callAI($userId, $prompt, 'weekly_digest');

        if (!$response) {
            return null;
        }

        $creditCost = 5; // Costo nominale per tracking

        // Salva report
        $reportId = $this->aiReport->create([
            'project_id' => $projectId,
            'report_type' => 'weekly_digest',
            'title' => "Report Settimanale - " . date('d/m/Y', strtotime($startDate)) . " - " . date('d/m/Y', strtotime($endDate)),
            'content' => $response,
            'data_snapshot' => json_encode([
                'gsc' => $gscComparison,
                'ga4' => $ga4Comparison,
                'top_movers' => $topMovers,
            ]),
            'period_start' => $startDate,
            'period_end' => $endDate,
            'credits_used' => $creditCost,
        ]);

        return [
            'id' => $reportId,
            'content' => $response,
        ];
    }

    /**
     * Genera report mensile executive
     */
    public function generateMonthlyExecutive(int $projectId, int $userId): ?array
    {
        $project = $this->project->find($projectId);

        if (!$project || !$this->isConfigured()) {
            return null;
        }

        // Dati ultimo mese
        $endDate = date('Y-m-d', strtotime('-1 day'));
        $startDate = date('Y-m-d', strtotime('-30 days'));
        $prevEndDate = date('Y-m-d', strtotime('-31 days'));
        $prevStartDate = date('Y-m-d', strtotime('-60 days'));

        $gscComparison = $this->gscDaily->comparePeriods($projectId, $startDate, $endDate, $prevStartDate, $prevEndDate);
        $ga4Comparison = $this->ga4Daily->comparePeriods($projectId, $startDate, $endDate, $prevStartDate, $prevEndDate);
        $topKeywords = $this->keyword->getTopByClicks($projectId, 20);
        $stats = $this->project->getStats($projectId);

        $prompt = $this->buildMonthlyPrompt($project, $gscComparison, $ga4Comparison, $topKeywords, $stats, $startDate, $endDate);

        // Chiama AI tramite AiService (gestisce crediti internamente)
        $response = $this->callAI($userId, $prompt, 'monthly_executive');

        if (!$response) {
            return null;
        }

        $creditCost = 15; // Costo nominale per tracking

        $reportId = $this->aiReport->create([
            'project_id' => $projectId,
            'report_type' => 'monthly_executive',
            'title' => "Report Mensile Executive - " . date('F Y', strtotime($startDate)),
            'content' => $response,
            'data_snapshot' => json_encode([
                'gsc' => $gscComparison,
                'ga4' => $ga4Comparison,
                'top_keywords' => $topKeywords,
                'stats' => $stats,
            ]),
            'period_start' => $startDate,
            'period_end' => $endDate,
            'credits_used' => $creditCost,
        ]);

        return [
            'id' => $reportId,
            'content' => $response,
        ];
    }

    /**
     * Genera analisi anomalie
     */
    public function generateAnomalyAnalysis(int $projectId, int $userId, array $anomalyData): ?array
    {
        $project = $this->project->find($projectId);

        if (!$project || !$this->isConfigured()) {
            return null;
        }

        $prompt = $this->buildAnomalyPrompt($project, $anomalyData);

        // Chiama AI tramite AiService (gestisce crediti internamente)
        $response = $this->callAI($userId, $prompt, 'anomaly_analysis');

        if (!$response) {
            return null;
        }

        $creditCost = 8; // Costo nominale per tracking

        $reportId = $this->aiReport->create([
            'project_id' => $projectId,
            'report_type' => 'anomaly_analysis',
            'title' => "Analisi Anomalie - " . date('d/m/Y'),
            'content' => $response,
            'data_snapshot' => json_encode($anomalyData),
            'period_start' => date('Y-m-d'),
            'period_end' => date('Y-m-d'),
            'credits_used' => $creditCost,
        ]);

        return [
            'id' => $reportId,
            'content' => $response,
        ];
    }

    /**
     * Genera report personalizzato
     */
    public function generateCustomReport(int $projectId, int $userId, string $type, ?string $customQuestion = null): ?array
    {
        $project = $this->project->find($projectId);

        if (!$project || !$this->isConfigured()) {
            return null;
        }

        // Raccogli dati base
        $endDate = date('Y-m-d', strtotime('-1 day'));
        $startDate = date('Y-m-d', strtotime('-30 days'));

        $gscData = $this->gscDaily->getSummary($projectId, $startDate, $endDate);
        $ga4Data = $this->ga4Daily->getSummary($projectId, $startDate, $endDate);
        $topKeywords = $this->keyword->getTopByClicks($projectId, 20);

        $prompt = $this->buildCustomPrompt($project, $gscData, $ga4Data, $topKeywords, $type, $customQuestion, $startDate, $endDate);

        // Chiama AI tramite AiService (gestisce crediti internamente)
        $response = $this->callAI($userId, $prompt, 'custom');

        if (!$response) {
            return null;
        }

        $creditCost = 5; // Costo nominale per tracking

        $title = match($type) {
            'keyword_analysis' => 'Analisi Keyword',
            'revenue_attribution' => 'Analisi Revenue Attribution',
            'custom' => 'Report Personalizzato',
            default => 'Report AI',
        };

        $reportId = $this->aiReport->create([
            'project_id' => $projectId,
            'report_type' => 'custom',
            'title' => "$title - " . date('d/m/Y'),
            'content' => $response,
            'data_snapshot' => json_encode([
                'gsc' => $gscData,
                'ga4' => $ga4Data,
                'top_keywords' => $topKeywords,
                'custom_question' => $customQuestion,
            ]),
            'period_start' => $startDate,
            'period_end' => $endDate,
            'credits_used' => $creditCost,
        ]);

        return [
            'id' => $reportId,
            'content' => $response,
        ];
    }

    /**
     * Build weekly digest prompt
     */
    private function buildWeeklyPrompt(array $project, array $gsc, array $ga4, array $movers, array $alerts, string $start, string $end): string
    {
        $data = [
            'dominio' => $project['domain'],
            'periodo' => "$start - $end",
            'gsc' => [
                'click' => $gsc['current']['total_clicks'] ?? 0,
                'click_change' => ($gsc['clicks_change_pct'] ?? 0) . '%',
                'impressioni' => $gsc['current']['total_impressions'] ?? 0,
                'posizione_media' => round($gsc['current']['avg_position'] ?? 0, 1),
            ],
            'ga4' => [
                'sessioni' => $ga4['current']['sessions'] ?? 0,
                'sessioni_change' => ($ga4['sessions_change_pct'] ?? 0) . '%',
                'revenue' => $ga4['current']['revenue'] ?? 0,
                'revenue_change' => ($ga4['revenue_change_pct'] ?? 0) . '%',
            ],
            'top_movers' => array_map(fn($m) => [
                'keyword' => $m['keyword'],
                'change' => $m['position_change'],
            ], array_slice($movers, 0, 5)),
            'alert_count' => count($alerts),
        ];

        return "Sei un esperto SEO. Genera un report settimanale in italiano per il sito {$project['domain']}.

DATI:
" . json_encode($data, JSON_PRETTY_PRINT) . "

ISTRUZIONI:
1. Scrivi un riassunto executive di 2-3 frasi
2. Analizza le performance GSC (click, impressioni, posizione media)
3. Analizza le performance GA4 (sessioni organiche, revenue)
4. Evidenzia le keyword con maggiori variazioni
5. Fornisci 3 raccomandazioni actionable

Usa un tono professionale ma accessibile. Formatta con markdown.";
    }

    /**
     * Build monthly executive prompt
     */
    private function buildMonthlyPrompt(array $project, array $gsc, array $ga4, array $keywords, array $stats, string $start, string $end): string
    {
        $data = [
            'dominio' => $project['domain'],
            'periodo' => "$start - $end",
            'gsc' => [
                'click_totali' => $gsc['current']['total_clicks'] ?? 0,
                'impressioni_totali' => $gsc['current']['total_impressions'] ?? 0,
                'posizione_media' => round($gsc['current']['avg_position'] ?? 0, 1),
                'variazione_click' => ($gsc['clicks_change_pct'] ?? 0) . '%',
            ],
            'ga4' => [
                'sessioni_totali' => $ga4['current']['sessions'] ?? 0,
                'revenue_totale' => $ga4['current']['revenue'] ?? 0,
                'acquisti' => $ga4['current']['purchases'] ?? 0,
                'variazione_revenue' => ($ga4['revenue_change_pct'] ?? 0) . '%',
            ],
            'keyword_totali' => $stats['total_keywords'] ?? 0,
            'keyword_top10' => $stats['keywords_top10'] ?? 0,
            'top_keywords' => array_map(fn($k) => [
                'keyword' => $k['keyword'],
                'click' => $k['last_clicks'],
                'posizione' => round($k['last_position'], 1),
            ], array_slice($keywords, 0, 10)),
        ];

        return "Sei un consulente SEO senior. Genera un report mensile executive in italiano per {$project['domain']}.

DATI MENSILI:
" . json_encode($data, JSON_PRETTY_PRINT) . "

STRUTTURA RICHIESTA:
1. **Executive Summary** (3-4 frasi per decision maker)
2. **Performance Overview** (metriche chiave con trend)
3. **Analisi Keyword** (top performer, opportunita)
4. **Revenue Attribution** (impatto SEO su conversioni)
5. **Raccomandazioni Strategiche** (3-5 azioni prioritarie)
6. **Outlook** (previsioni prossimo mese)

Usa tono executive, dati concreti, formattazione markdown professionale.";
    }

    /**
     * Build anomaly analysis prompt
     */
    private function buildAnomalyPrompt(array $project, array $anomalyData): string
    {
        return "Sei un esperto di analytics SEO. Analizza questa anomalia rilevata per {$project['domain']}:

ANOMALIA:
" . json_encode($anomalyData, JSON_PRETTY_PRINT) . "

ANALISI RICHIESTA:
1. Descrivi l'anomalia in termini semplici
2. Elenca le possibili cause (Google update, problemi tecnici, stagionalita, competitor)
3. Suggerisci azioni di investigazione
4. Raccomanda azioni correttive se necessario

Rispondi in italiano con formattazione markdown.";
    }

    /**
     * Build custom report prompt
     */
    private function buildCustomPrompt(array $project, array $gsc, array $ga4, array $keywords, string $type, ?string $customQuestion, string $start, string $end): string
    {
        $data = [
            'dominio' => $project['domain'],
            'periodo' => "$start - $end",
            'gsc' => $gsc,
            'ga4' => $ga4,
            'top_keywords' => array_map(fn($k) => [
                'keyword' => $k['keyword'],
                'click' => $k['last_clicks'] ?? 0,
                'posizione' => round($k['last_position'] ?? 0, 1),
            ], array_slice($keywords, 0, 15)),
        ];

        $basePrompt = "Sei un esperto SEO. Analizza questi dati per {$project['domain']}:\n\n" . json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

        if ($type === 'keyword_analysis') {
            return $basePrompt . "RICHIESTA: Fornisci un'analisi dettagliata delle keyword:
1. Identifica le keyword con migliore performance
2. Trova opportunita di miglioramento (keyword vicine alla top 10)
3. Suggerisci keyword correlate da targetizzare
4. Analizza il CTR e suggerisci ottimizzazioni

Rispondi in italiano con formattazione markdown.";
        }

        if ($type === 'revenue_attribution') {
            return $basePrompt . "RICHIESTA: Analizza l'attribuzione revenue:
1. Quali keyword generano piu conversioni?
2. Qual e il valore medio per click?
3. Identifica keyword con alto potenziale di revenue non sfruttato
4. Suggerisci strategie per aumentare il ROI SEO

Rispondi in italiano con formattazione markdown.";
        }

        // Custom question
        if (!empty($customQuestion)) {
            return $basePrompt . "DOMANDA UTENTE: $customQuestion\n\nRispondi in italiano con formattazione markdown, basandoti sui dati forniti.";
        }

        return $basePrompt . "Fornisci un'analisi generale delle performance SEO con suggerimenti di miglioramento. Rispondi in italiano con formattazione markdown.";
    }

    /**
     * Chiama AI tramite AiService centralizzato
     */
    private function callAI(int $userId, string $prompt, string $reportType): ?string
    {
        if (!$this->aiService->isConfigured()) {
            error_log('AiReportService: AI non configurata');
            return null;
        }

        $maxTokens = match($reportType) {
            'weekly_digest' => 1500,
            'monthly_executive' => 3000,
            'anomaly_analysis' => 1000,
            default => 1500,
        };

        try {
            $response = $this->aiService->complete($userId, [
                ['role' => 'user', 'content' => $prompt]
            ], [
                'max_tokens' => $maxTokens,
            ]);

            if (isset($response['error'])) {
                error_log('AiReportService: ' . ($response['message'] ?? 'Errore sconosciuto'));
                return null;
            }

            return $response['result'] ?? null;
        } catch (\Exception $e) {
            error_log('AiReportService: ' . $e->getMessage());
            return null;
        }
    }
}
