<?php

namespace Modules\AdsAnalyzer\Services;

require_once __DIR__ . '/../../../services/AiService.php';
require_once __DIR__ . '/../../../services/ScraperService.php';

use Core\Database;

class ContextExtractorService
{
    private \Services\AiService $aiService;
    private \Services\ScraperService $scraper;

    public function __construct()
    {
        $this->aiService = new \Services\AiService('ads-analyzer');
        $this->scraper = new \Services\ScraperService();
    }

    /**
     * Scrape landing page con Readability e estrai contesto con AI
     *
     * @param string $mode 'negative-kw' (default) o 'campaign'
     */
    public function extractFromUrl(int $userId, string $url, string $mode = 'negative-kw'): array
    {
        error_log("=== ContextExtractor: START ===");
        error_log("URL: $url | Mode: $mode");

        // Step 1: Scraping con Readability (ScraperService::scrape)
        try {
            $scrapeResult = $this->scraper->scrape($url);
        } catch (\Exception $e) {
            error_log("Scraping error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Impossibile accedere alla pagina: ' . $e->getMessage()
            ];
        }

        // Formatta contenuto scrappato
        $scrapedContent = $this->formatScrapedContent([
            'title' => $scrapeResult['title'] ?? '',
            'description' => $scrapeResult['description'] ?? '',
            'headings' => $scrapeResult['headings'] ?? [],
            'content' => $scrapeResult['content'] ?? '',
        ]);

        if (strlen(trim($scrapedContent)) < 50) {
            return [
                'success' => false,
                'error' => 'Contenuto insufficiente dalla pagina'
            ];
        }

        error_log("Scraped content length: " . strlen($scrapedContent));

        // Step 2: AI estrae contesto (limitare a 8000 chars)
        $contentForPrompt = substr($scrapedContent, 0, 8000);
        $prompt = $this->buildExtractionPrompt($contentForPrompt, $mode);

        error_log("Calling AI for context extraction...");

        $response = $this->aiService->analyze(
            $userId,
            $prompt,
            '',
            'ads-analyzer-context'
        );

        Database::reconnect();

        if (isset($response['error'])) {
            return [
                'success' => false,
                'error' => $response['message'] ?? 'Errore AI'
            ];
        }

        $extractedContext = $this->parseContextResponse($response['result'] ?? '');

        error_log("Extracted context length: " . strlen($extractedContext));
        error_log("=== ContextExtractor: SUCCESS ===");

        return [
            'success' => true,
            'scraped_content' => $scrapedContent,
            'extracted_context' => $extractedContext
        ];
    }

    /**
     * Formatta i dati scrappati in testo strutturato
     */
    private function formatScrapedContent(array $data): string
    {
        $parts = [];

        if (!empty($data['title'])) {
            $parts[] = "TITOLO: " . $data['title'];
        }

        if (!empty($data['description'])) {
            $parts[] = "DESCRIZIONE: " . $data['description'];
        }

        if (!empty($data['headings'])) {
            $headingTexts = [];
            foreach ($data['headings'] as $level => $headings) {
                foreach ($headings as $heading) {
                    if (!empty($heading)) {
                        $headingTexts[] = strtoupper($level) . ": " . $heading;
                    }
                }
            }
            if (!empty($headingTexts)) {
                $parts[] = "TITOLI PAGINA:\n" . implode("\n", $headingTexts);
            }
        }

        if (!empty($data['content'])) {
            $parts[] = "CONTENUTO:\n" . $data['content'];
        }

        return implode("\n\n", $parts);
    }

    /**
     * Prompt per estrazione contesto - varia in base al mode
     */
    private function buildExtractionPrompt(string $content, string $mode = 'negative-kw'): string
    {
        if ($mode === 'campaign') {
            return <<<PROMPT
Sei un esperto di marketing digitale e Google Ads. Analizza il contenuto di questa landing page e genera un CONTESTO BUSINESS dettagliato.

CONTENUTO PAGINA:
{$content}

GENERA un'analisi strutturata che descriva:
1. Cosa vende/promuove questa pagina (prodotti, servizi, offerte specifiche)
2. Il target di riferimento (B2B/B2C, fascia d'eta, settore, esigenze)
3. La proposta di valore unica (USP - cosa la differenzia dalla concorrenza)
4. Il tono/stile comunicativo (professionale, informale, urgenza, etc.)
5. Call-to-action principali (cosa vuole che l'utente faccia)
6. Punti deboli evidenti (contenuto scarso, mancanza info, UX issues percepiti dal testo)

Rispondi con un paragrafo di 150-300 parole strutturato e dettagliato.
Rispondi SOLO con il testo dell'analisi, senza preamboli o spiegazioni.
PROMPT;
        }

        // Default: negative-kw mode
        return <<<PROMPT
Sei un esperto di Google Ads. Analizza il contenuto di questa landing page e genera un CONTESTO BUSINESS per l'analisi delle keyword negative.

CONTENUTO PAGINA:
{$content}

GENERA un contesto business che descriva:
1. Cosa vende/promuove questa pagina
2. Il target di riferimento
3. Cosa NON vende/offre (importante per keyword negative)
4. Eventuali brand/prodotti menzionati

FORMATO OUTPUT:
Scrivi un paragrafo di 100-200 parole che descriva chiaramente:
- L'attivita/servizio promosso
- Il pubblico target
- Cosa esplicitamente NON viene offerto

Rispondi SOLO con il testo del contesto, senza preamboli o spiegazioni.
PROMPT;
    }

    /**
     * Pulisce la risposta AI
     */
    private function parseContextResponse(string $response): string
    {
        $context = trim($response);
        $context = preg_replace('/^```[\w]*\n?/', '', $context);
        $context = preg_replace('/\n?```$/', '', $context);
        return trim($context);
    }
}
