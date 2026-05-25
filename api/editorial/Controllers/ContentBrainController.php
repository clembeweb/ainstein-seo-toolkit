<?php

declare(strict_types=1);

namespace Editorial\Controllers;

use Editorial\Middleware\LicenseAuthMiddleware;
use Editorial\Services\ContentBrainService;
use Throwable;

/**
 * ContentBrainController — M2
 *
 * Gestione del "Content Brain" del sito: scan iniziale (SSE), lettura, update manuale.
 *
 * M2.2 (questo file, current): POST /content-brain/scan con SSE stream.
 * M2.3 (prossimo): GET / PUT /content-brain.
 *
 * Pattern SSE: copia da modules/seo-tracking/RankCheckController::processStream().
 * NO tabella aied_scan_jobs: stato deriva da aied_content_brain.last_scan_at (vedi
 * docs/milestones/M2-content-brain.md §2.2).
 */
class ContentBrainController extends BaseController
{
    private ?ContentBrainService $service;

    /**
     * Service injection opzionale per testing. In produzione il Router istanzia
     * il controller senza args e si costruisce il Service di default.
     */
    public function __construct(?ContentBrainService $service = null)
    {
        $this->service = $service;
    }

    /** GET /content-brain — implementato in M2.3 */
    public function show(): string
    {
        return $this->jsonNotImplemented('2.3');
    }

    /** PUT /content-brain — implementato in M2.3 */
    public function update(): string
    {
        return $this->jsonNotImplemented('2.3');
    }

    /**
     * POST /content-brain/scan
     *
     * Esegue scan completo del sito con streaming SSE.
     * Body opzionale: { "article_urls": ["...", "..."] }. Se assente, discovery automatica.
     *
     * Eventi emessi (vedi spec §2.2):
     *   started, article_scraped, article_error, aggregating,
     *   analyzing, completed, error
     */
    public function scan(): void
    {
        // SSE preflight: lunga durata, no client abort, sessione chiusa per non
        // bloccare altre request dello stesso utente.
        ignore_user_abort(true);
        @set_time_limit(300);

        $siteId = (int) (LicenseAuthMiddleware::$currentSite['id'] ?? 0);
        $articleUrls = $this->sanitizeUrls($this->jsonInput()['article_urls'] ?? []);

        // SSE headers PRIMA di session_write_close per evitare race su header sent.
        $this->setupSseHeaders();
        if (function_exists('session_write_close')) {
            @session_write_close();
        }

        // Callback emit: chiunque chiami $emit($event, $data) scrive sullo stream.
        $emit = static function (string $event, array $data): void {
            echo "event: {$event}\n";
            echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
            if (ob_get_level() > 0) {
                @ob_flush();
            }
            @flush();
        };

        if ($siteId <= 0) {
            $emit('error', [
                'message' => 'Sito non identificato dal token.',
                'retry_possible' => false,
            ]);
            exit;
        }

        try {
            $service = $this->service ?? new ContentBrainService();
            $service->scan($siteId, $articleUrls, $emit);
        } catch (Throwable $e) {
            $emit('error', [
                'message' => 'Errore durante lo scan: ' . $e->getMessage(),
                'retry_possible' => true,
            ]);
        }

        exit;
    }

    // =====================================================================
    // Internals
    // =====================================================================

    private function setupSseHeaders(): void
    {
        // Disattiva eventuale output buffer in atto: SSE deve flushare subito.
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');
        header('X-Content-Type-Options: nosniff');
    }

    /**
     * @param mixed $raw
     * @return string[]
     */
    private function sanitizeUrls($raw): array
    {
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $u) {
            if (!is_string($u)) continue;
            $u = trim($u);
            if ($u === '') continue;
            if (!filter_var($u, FILTER_VALIDATE_URL)) continue;
            $out[] = $u;
        }
        return array_values(array_unique($out));
    }
}
