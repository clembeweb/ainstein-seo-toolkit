<?php

namespace Modules\AiContent\Services;

use Core\Database;
use Core\Settings;
use Services\AiService;
use Services\ApiLoggerService;

/**
 * CoverImageService
 *
 * Genera immagini di copertina per articoli usando:
 * 1. Claude AI per generare il prompt ottimale
 * 2. OpenAI DALL-E 3 per generare l'immagine
 * 3. Salvataggio locale in storage/images/covers/
 */
class CoverImageService
{
    private AiService $aiService;
    private string $storageBase;

    public function __construct()
    {
        $this->aiService = new AiService('ai-content');
        $this->storageBase = \ROOT_PATH . '/storage/images/covers';
    }

    /**
     * Genera immagine di copertina per un articolo
     *
     * @return array{success: bool, path?: string, prompt?: string, error?: string}
     */
    public function generate(int $articleId, string $title, string $keyword, string $contentExcerpt, int $userId): array
    {
        // 1. Genera prompt per DALL-E via Claude
        $promptResult = $this->buildImagePrompt($title, $keyword, $contentExcerpt, $userId);

        Database::reconnect();

        if (!$promptResult['success']) {
            return $promptResult;
        }

        $imagePrompt = $promptResult['prompt'];

        // 2. Chiama l'API immagini OpenAI (gpt-image-*; DALL-E 3 ritirato il 2026-05-12)
        $dalleResult = $this->callDallE($imagePrompt);

        Database::reconnect();

        if (!$dalleResult['success']) {
            return $dalleResult;
        }

        // 3. Scarica e salva immagine
        $savePath = $this->saveImage($dalleResult['url'] ?? '', $articleId, $dalleResult['b64'] ?? null);

        if (!$savePath) {
            return [
                'success' => false,
                'error' => 'Impossibile salvare l\'immagine generata'
            ];
        }

        return [
            'success' => true,
            'path' => $savePath,
            'prompt' => $imagePrompt,
        ];
    }

    /**
     * Genera il prompt DALL-E usando Claude AI
     *
     * @return array{success: bool, prompt?: string, error?: string}
     */
    private function buildImagePrompt(string $title, string $keyword, string $contentExcerpt, int $userId): array
    {
        if (!$this->aiService->isConfigured()) {
            return [
                'success' => false,
                'error' => 'API Key AI non configurata'
            ];
        }

        $prompt = <<<PROMPT
Sei un esperto di image prompting per modelli text-to-image. Genera un prompt in inglese per creare un'immagine di copertina per un articolo blog.

Titolo articolo: {$title}
Keyword: {$keyword}
Estratto contenuto: {$contentExcerpt}

REGOLE OBBLIGATORIE:
- L'immagine deve essere una foto/illustrazione professionale e moderna
- NON includere MAI testo, scritte, lettere, numeri o parole nell'immagine
- NON includere MAI loghi, watermark o elementi testuali
- Stile: fotorealistico o illustrazione moderna, colori vivaci
- Formato: orizzontale (landscape), adatto come hero image di un blog
- Il soggetto deve essere tematicamente correlato al contenuto dell'articolo
- L'immagine deve essere visivamente accattivante e professionale
- Massimo 80 parole per il prompt

Rispondi SOLO con il prompt in inglese, senza altre spiegazioni o commenti.
PROMPT;

        // Model from module/global settings (no hardcoded model)
        $result = $this->aiService->complete($userId, [
            ['role' => 'user', 'content' => $prompt],
        ], [
            // 200 troncava la risposta: con i modelli attuali il ragionamento conta nel tetto
            'max_tokens' => 1500,
            'charge_credits' => false, // il chiamante addebita cover_image_generation
        ], 'ai-content');

        if (isset($result['error'])) {
            return [
                'success' => false,
                'error' => 'Errore generazione prompt immagine: ' . ($result['message'] ?? 'Errore sconosciuto')
            ];
        }

        $imagePrompt = trim($result['result'] ?? '');

        if (empty($imagePrompt)) {
            return [
                'success' => false,
                'error' => 'Prompt immagine vuoto dalla risposta AI'
            ];
        }

        // Rimuovi eventuali virgolette wrapping
        $imagePrompt = trim($imagePrompt, '"\'');

        return [
            'success' => true,
            'prompt' => $imagePrompt,
        ];
    }

    /**
     * Chiama OpenAI DALL-E 3 per generare l'immagine
     *
     * @return array{success: bool, url?: string, error?: string}
     */
    private function callDallE(string $prompt): array
    {
        $apiKey = $this->getOpenAiKey();

        if (empty($apiKey)) {
            return [
                'success' => false,
                'error' => 'API Key OpenAI non configurata. Necessaria per la generazione immagini.'
            ];
        }

        // Modello configurabile dall'admin (default gpt-image-2: DALL-E 3 ritirato il 2026-05-12,
        // gpt-image-1-mini in ritiro il 2026-12-01). Risposta sempre in base64 per i modelli gpt-image-*.
        $model = \Core\ModuleLoader::getSetting('ai-content', 'cover_image_model', 'gpt-image-2') ?: 'gpt-image-2';
        $quality = \Core\ModuleLoader::getSetting('ai-content', 'cover_image_quality', 'medium') ?: 'medium';
        $data = [
            'model' => $model,
            'prompt' => $prompt,
            'n' => 1,
            'size' => '1536x1024',
            'quality' => $quality,
            'output_format' => 'jpeg',
            'output_compression' => 85,
        ];

        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);

        $startTime = microtime(true);

        $ch = curl_init('https://api.openai.com/v1/images/generations');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_TIMEOUT => 120,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Log API call (redact key)
        $logRequest = $data;
        ApiLoggerService::log('openai_dalle', '/v1/images/generations', $logRequest, json_decode($response, true), $httpCode, $startTime, [
            'module' => 'ai-content',
            'cost' => 0.04,
            'context' => 'cover_image',
        ]);

        if ($curlError) {
            return [
                'success' => false,
                'error' => 'Errore connessione OpenAI DALL-E: ' . $curlError
            ];
        }

        if ($httpCode !== 200) {
            $error = json_decode($response, true);
            $errorMsg = $error['error']['message'] ?? "Errore API DALL-E (HTTP {$httpCode})";
            return [
                'success' => false,
                'error' => $errorMsg
            ];
        }

        $result = json_decode($response, true);
        $imageUrl = $result['data'][0]['url'] ?? null;
        $imageB64 = $result['data'][0]['b64_json'] ?? null;

        if (empty($imageUrl) && empty($imageB64)) {
            return [
                'success' => false,
                'error' => 'Risposta API immagini senza dati immagine'
            ];
        }

        return [
            'success' => true,
            'url' => $imageUrl,
            'b64' => $imageB64,
            'revised_prompt' => $result['data'][0]['revised_prompt'] ?? null,
        ];
    }

    /**
     * Scarica immagine da URL e salva in locale
     *
     * @return string|null Path relativo dell'immagine salvata, o null se errore
     */
    private function saveImage(string $imageUrl, int $articleId, ?string $base64 = null): ?string
    {
        // Crea directory anno/mese
        $year = date('Y');
        $month = date('m');
        $dir = $this->storageBase . '/' . $year . '/' . $month;

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Immagine in base64 (gpt-image-*): nessun download

        if (!empty($base64)) {

            $imageData = base64_decode($base64, true);

            if (empty($imageData)) {

                return null;

            }

            $filename = $articleId . '_' . time() . '.jpg';

            $fullPath = $dir . '/' . $filename;

            if (file_put_contents($fullPath, $imageData) === false) {

                return null;

            }

            return 'storage/images/covers/' . $year . '/' . $month . '/' . $filename;

        }


        // Download immagine da URL (modelli legacy)
        $startTime = microtime(true);

        $ch = curl_init($imageUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 60,
        ]);

        $imageData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Log image download API call
        ApiLoggerService::log('openai_dalle', '/download', ['url' => substr($imageUrl, 0, 100)], [
            'http_code' => $httpCode,
            'size' => strlen($imageData ?: ''),
            'error' => $curlError ?: null,
        ], $httpCode, $startTime, [
            'module' => 'ai-content',
            'cost' => 0,
            'context' => 'cover_image_download',
        ]);

        if ($curlError || $httpCode !== 200 || empty($imageData)) {
            return null;
        }

        // Salva come PNG (DALL-E ritorna PNG)
        $filename = $articleId . '_' . time() . '.png';
        $fullPath = $dir . '/' . $filename;

        if (file_put_contents($fullPath, $imageData) === false) {
            return null;
        }

        // Ritorna path relativo dalla root del progetto
        return 'storage/images/covers/' . $year . '/' . $month . '/' . $filename;
    }

    /**
     * Elimina file immagine dal filesystem
     */
    public function deleteImage(string $relativePath): bool
    {
        $fullPath = \ROOT_PATH . '/' . $relativePath;

        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }

        return true;
    }

    /**
     * Ottieni API key OpenAI da settings
     */
    private function getOpenAiKey(): string
    {
        return Settings::get('openai_api_key', '');
    }
}
