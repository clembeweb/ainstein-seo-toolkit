<?php

namespace Modules\ContentCreator\Services\Providers;

use Core\Settings;
use Core\ModuleLoader;
use Services\ApiLoggerService;

class GeminiImageProvider implements ImageProviderInterface
{
    private string $apiKey;
    private string $model;
    private int $timeout;

    private const API_BASE = 'https://generativelanguage.googleapis.com/v1beta';
    private const MAX_IMAGE_SIZE = 10 * 1024 * 1024; // 10MB
    private const SUPPORTED_FORMATS = ['png', 'jpg', 'jpeg', 'webp'];
    private const DEFAULT_MODEL = 'gemini-2.0-flash';
    private const DEFAULT_TIMEOUT = 120;

    public function __construct()
    {
        $this->apiKey = Settings::get('google_gemini_api_key', '');
        $this->model = ModuleLoader::getSetting('content-creator', 'image_model', self::DEFAULT_MODEL);
        $this->timeout = self::DEFAULT_TIMEOUT;

        if (empty($this->apiKey)) {
            throw new \RuntimeException('Google Gemini API key non configurata. Vai in Admin > Settings.');
        }
    }

    public function generate(string $sourceImagePath, string $prompt): array
    {
        if (!file_exists($sourceImagePath) || !is_readable($sourceImagePath)) {
            return [
                'success' => false,
                'image_data' => null,
                'mime' => null,
                'revised_prompt' => null,
                'error' => "Immagine sorgente non trovata: {$sourceImagePath}",
            ];
        }

        $fileSize = filesize($sourceImagePath);
        if ($fileSize > self::MAX_IMAGE_SIZE) {
            $maxMB = self::MAX_IMAGE_SIZE / (1024 * 1024);
            return [
                'success' => false,
                'image_data' => null,
                'mime' => null,
                'revised_prompt' => null,
                'error' => "Immagine troppo grande ({$this->formatBytes($fileSize)}). Max: {$maxMB}MB",
            ];
        }

        $imageData = file_get_contents($sourceImagePath);
        $mimeType = $this->getMimeType($sourceImagePath);
        $base64Image = base64_encode($imageData);

        $endpoint = self::API_BASE . "/models/{$this->model}:generateContent";
        $requestBody = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'inline_data' => [
                                'mime_type' => $mimeType,
                                'data' => $base64Image,
                            ],
                        ],
                        [
                            'text' => $prompt,
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'responseModalities' => ['IMAGE', 'TEXT'],
                'temperature' => 0.8,
            ],
        ];

        $startTime = microtime(true);
        $httpCode = 0;
        $responseBody = null;

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $endpoint . '?key=' . $this->apiKey,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($requestBody),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_CONNECTTIMEOUT => 10,
            ]);

            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                throw new \RuntimeException("Errore connessione Gemini: {$curlError}");
            }

            $response = json_decode($responseBody, true);

            $logRequest = $requestBody;
            $logRequest['contents'][0]['parts'][0]['inline_data']['data'] = '[BASE64_TRUNCATED]';
            ApiLoggerService::log('google_gemini', '/generateContent', $logRequest, $response, $httpCode, $startTime, [
                'module' => 'content-creator',
                'cost' => 0,
                'context' => "image_generation model={$this->model}",
            ]);

            if ($httpCode !== 200) {
                $errorMsg = $this->extractErrorMessage($response, $httpCode);
                return [
                    'success' => false,
                    'image_data' => null,
                    'mime' => null,
                    'revised_prompt' => null,
                    'error' => $errorMsg,
                ];
            }

            return $this->parseResponse($response);

        } catch (\Exception $e) {
            ApiLoggerService::log('google_gemini', '/generateContent', ['error' => 'exception'], ['error' => $e->getMessage()], $httpCode, $startTime, [
                'module' => 'content-creator',
                'cost' => 0,
                'context' => "image_generation_error",
            ]);

            return [
                'success' => false,
                'image_data' => null,
                'mime' => null,
                'revised_prompt' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function parseResponse(array $response): array
    {
        $candidates = $response['candidates'] ?? [];
        if (empty($candidates)) {
            $blockReason = $response['promptFeedback']['blockReason'] ?? null;
            if ($blockReason) {
                return [
                    'success' => false,
                    'image_data' => null,
                    'mime' => null,
                    'revised_prompt' => null,
                    'error' => "Contenuto bloccato dalla policy Gemini: {$blockReason}",
                ];
            }
            return [
                'success' => false,
                'image_data' => null,
                'mime' => null,
                'revised_prompt' => null,
                'error' => 'Nessun candidato nella risposta Gemini',
            ];
        }

        $parts = $candidates[0]['content']['parts'] ?? [];
        $imageData = null;
        $imageMime = null;
        $textResponse = null;

        foreach ($parts as $part) {
            if (isset($part['inline_data'])) {
                $imageData = base64_decode($part['inline_data']['data']);
                $imageMime = $part['inline_data']['mime_type'] ?? 'image/png';
            }
            if (isset($part['text'])) {
                $textResponse = $part['text'];
            }
        }

        if ($imageData === null) {
            return [
                'success' => false,
                'image_data' => null,
                'mime' => null,
                'revised_prompt' => $textResponse,
                'error' => 'Nessuna immagine nella risposta Gemini' . ($textResponse ? ": {$textResponse}" : ''),
            ];
        }

        return [
            'success' => true,
            'image_data' => $imageData,
            'mime' => $imageMime,
            'revised_prompt' => $textResponse,
            'error' => null,
        ];
    }

    private function extractErrorMessage(?array $response, int $httpCode): string
    {
        if (!$response) {
            return "Errore HTTP {$httpCode} dalla API Gemini";
        }

        $error = $response['error'] ?? null;
        if ($error) {
            $msg = $error['message'] ?? 'Errore sconosciuto';
            $status = $error['status'] ?? '';

            if ($httpCode === 429) {
                return "Rate limit Gemini superato. Riprova tra qualche secondo.";
            }
            if ($httpCode === 403) {
                return "API key Gemini non valida o senza permessi.";
            }

            return "Errore Gemini ({$status}): {$msg}";
        }

        return "Errore HTTP {$httpCode} dalla API Gemini";
    }

    private function getMimeType(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return match ($ext) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . 'MB';
        if ($bytes >= 1024) return round($bytes / 1024, 1) . 'KB';
        return $bytes . 'B';
    }

    public function isTransientError(string $error): bool
    {
        return str_contains($error, 'Rate limit')
            || str_contains($error, '429')
            || str_contains($error, '500')
            || str_contains($error, '503')
            || str_contains($error, 'timeout')
            || str_contains($error, 'connessione');
    }

    public function isContentPolicyError(string $error): bool
    {
        return str_contains($error, 'bloccato dalla policy')
            || str_contains($error, 'SAFETY')
            || str_contains($error, 'HARM');
    }

    public function getProviderName(): string
    {
        return 'gemini';
    }

    public function getMaxImageSize(): int
    {
        return self::MAX_IMAGE_SIZE;
    }

    public function getSupportedFormats(): array
    {
        return self::SUPPORTED_FORMATS;
    }
}
