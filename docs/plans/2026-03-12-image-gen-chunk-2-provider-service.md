# Chunk 2: Provider Interface + Gemini Provider + Generation Service

> **Parent plan:** [Plan Index](./2026-03-12-image-generation-plan-index.md)
> **Design spec:** [Design Spec](./2026-03-12-content-creator-image-generation-design.md) — Sections 3, 4
> **Depends on:** Chunk 1 (models must exist)

---

## Task 5: ImageProviderInterface

**Files:**
- Create: `modules/content-creator/services/providers/ImageProviderInterface.php`

- [ ] **Step 1: Write interface**

```php
<?php

namespace Modules\ContentCreator\Services\Providers;

/**
 * Interface for AI image generation providers.
 *
 * Ogni provider accetta un'immagine sorgente + prompt
 * e ritorna un'immagine generata (binary data).
 *
 * Implementazioni: GeminiImageProvider (v1)
 * Future: FashnProvider (try-on), StabilityProvider (staging)
 */
interface ImageProviderInterface
{
    /**
     * Genera un'immagine a partire da un'immagine sorgente e un prompt.
     *
     * @param string $sourceImagePath Path assoluto all'immagine sorgente
     * @param string $prompt Prompt completo per la generazione
     * @return array{
     *   success: bool,
     *   image_data: ?string,   // Binary image data (PNG)
     *   mime: ?string,          // e.g. 'image/png'
     *   revised_prompt: ?string,// Prompt rivisto dal provider (se disponibile)
     *   error: ?string          // Messaggio errore (se success=false)
     * }
     */
    public function generate(string $sourceImagePath, string $prompt): array;

    /**
     * Nome identificativo del provider (per logging e tracking)
     * @return string e.g. 'gemini', 'fashn', 'stability'
     */
    public function getProviderName(): string;

    /**
     * Dimensione massima dell'immagine sorgente in bytes
     */
    public function getMaxImageSize(): int;

    /**
     * Formati immagine sorgente supportati
     * @return string[] e.g. ['png', 'jpg', 'jpeg', 'webp']
     */
    public function getSupportedFormats(): array;
}
```

- [ ] **Step 2: Verify syntax**

```bash
php -l modules/content-creator/services/providers/ImageProviderInterface.php
```

- [ ] **Step 3: Commit**

```bash
git add modules/content-creator/services/providers/ImageProviderInterface.php
git commit -m "feat(content-creator): add ImageProviderInterface for multi-provider image generation"
```

---

## Task 6: GeminiImageProvider

**Files:**
- Create: `modules/content-creator/services/providers/GeminiImageProvider.php`

**Note:** Bypasses `AiService` intenzionalmente — AiService è text-only (Claude/OpenAI). La generazione immagini richiede API multimodale Gemini con I/O binario, incompatibile con `AiService::analyze()`. Il bypass è giustificato e isolato dietro la provider interface. (Design spec sezione 3)

**API Key:** `Settings::get('google_gemini_api_key')` — chiave globale in admin settings.

**API Logger provider:** `google_gemini` (nuovo provider).

- [ ] **Step 1: Write GeminiImageProvider**

```php
<?php

namespace Modules\ContentCreator\Services\Providers;

use Core\Settings;
use Core\ModuleLoader;
use Services\ApiLoggerService;

/**
 * Google Gemini Image Provider
 *
 * Usa Gemini API per generazione image-to-image.
 * Input: immagine sorgente + prompt testuale.
 * Output: immagine generata (PNG).
 *
 * Nota: NON usa AiService (che è text-only Claude/OpenAI).
 * Questo bypass è intenzionale — vedi design spec sezione 3.
 */
class GeminiImageProvider implements ImageProviderInterface
{
    private string $apiKey;
    private string $model;
    private int $timeout;

    private const API_BASE = 'https://generativelanguage.googleapis.com/v1beta';
    private const MAX_IMAGE_SIZE = 10 * 1024 * 1024; // 10MB
    private const SUPPORTED_FORMATS = ['png', 'jpg', 'jpeg', 'webp'];
    private const DEFAULT_MODEL = 'gemini-2.0-flash-exp';
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

    /**
     * Genera un'immagine usando Gemini multimodal API
     */
    public function generate(string $sourceImagePath, string $prompt): array
    {
        // Validate source image exists and is readable
        if (!file_exists($sourceImagePath) || !is_readable($sourceImagePath)) {
            return [
                'success' => false,
                'image_data' => null,
                'mime' => null,
                'revised_prompt' => null,
                'error' => "Immagine sorgente non trovata: {$sourceImagePath}",
            ];
        }

        // Validate file size
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

        // Read and encode source image
        $imageData = file_get_contents($sourceImagePath);
        $mimeType = $this->getMimeType($sourceImagePath);
        $base64Image = base64_encode($imageData);

        // Build API request
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

        // API call with logging
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

            // Log API call (truncate base64 from request for logging)
            $logRequest = $requestBody;
            $logRequest['contents'][0]['parts'][0]['inline_data']['data'] = '[BASE64_TRUNCATED]';
            ApiLoggerService::log('google_gemini', '/generateContent', $logRequest, $response, $httpCode, $startTime, [
                'module' => 'content-creator',
                'cost' => 0,
                'context' => "image_generation model={$this->model}",
            ]);

            // Handle HTTP errors
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

            // Extract generated image from response
            return $this->parseResponse($response);

        } catch (\Exception $e) {
            // Log failed call
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

    /**
     * Parse Gemini response to extract generated image
     */
    private function parseResponse(array $response): array
    {
        $candidates = $response['candidates'] ?? [];
        if (empty($candidates)) {
            // Check for content policy block
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

    /**
     * Extract meaningful error message from API response
     */
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

    /**
     * Detect MIME type from file extension
     */
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

    /**
     * Format bytes for display
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . 'MB';
        if ($bytes >= 1024) return round($bytes / 1024, 1) . 'KB';
        return $bytes . 'B';
    }

    /**
     * Check if error is transient (should retry)
     */
    public function isTransientError(string $error): bool
    {
        return str_contains($error, 'Rate limit')
            || str_contains($error, '429')
            || str_contains($error, '500')
            || str_contains($error, '503')
            || str_contains($error, 'timeout')
            || str_contains($error, 'connessione');
    }

    /**
     * Check if error is content policy (should NOT retry)
     */
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
```

- [ ] **Step 2: Verify syntax**

```bash
php -l modules/content-creator/services/providers/GeminiImageProvider.php
```

- [ ] **Step 3: Commit**

```bash
git add modules/content-creator/services/providers/GeminiImageProvider.php
git commit -m "feat(content-creator): add GeminiImageProvider with multimodal API, error handling, logging"
```

---

## Task 7: ImageGenerationService (Orchestrator)

**Files:**
- Create: `modules/content-creator/services/ImageGenerationService.php`

**Responsibility:** Builds prompts from templates + presets, calls provider for each variant, saves generated images to disk, converts formats for export.

- [ ] **Step 1: Write ImageGenerationService**

```php
<?php

namespace Modules\ContentCreator\Services;

use Core\ModuleLoader;
use Modules\ContentCreator\Services\Providers\ImageProviderInterface;
use Modules\ContentCreator\Services\Providers\GeminiImageProvider;

/**
 * Orchestratore per la generazione immagini.
 *
 * Responsabilità:
 * - Costruisce prompt dai template + preset progetto + override item
 * - Chiama il provider per ogni variante
 * - Salva le immagini generate su disco
 * - Converte formato per export (PNG → WebP/JPEG)
 */
class ImageGenerationService
{
    private ImageProviderInterface $provider;
    private string $storagePath;

    // Prompt templates
    private const TEMPLATE_FASHION = <<<'PROMPT'
Using the product shown in the attached image, generate a professional
e-commerce photo of a {gender} model wearing/using this exact product.
The product must be faithfully reproduced with accurate colors, textures,
patterns and details — it must be clearly recognizable as the same item.
Setting: {background}. Photography style: {photo_style}.
The model should have a natural, confident pose suitable for e-commerce catalog.
No text, no watermarks, no logos, no overlays.
High resolution, commercial quality, clean composition.
PROMPT;

    private const TEMPLATE_HOME = <<<'PROMPT'
Using the product shown in the attached image, generate a professional
interior design / lifestyle photo showing this exact product placed naturally
in a {environment} setting. The product must be faithfully reproduced with
accurate colors, proportions, materials and details — it must be clearly
recognizable as the same item. Photography style: {photo_style}.
Natural lighting, realistic perspective and shadows.
No text, no watermarks, no logos.
High resolution, commercial quality, inviting atmosphere.
PROMPT;

    private const TEMPLATE_CUSTOM = <<<'PROMPT'
Using the product shown in the attached image, generate a professional
commercial photo showcasing this exact product in an appealing context.
The product must be faithfully reproduced with accurate colors, proportions
and details — it must be clearly recognizable as the same item.
Photography style: {photo_style}.
No text, no watermarks, no logos.
High resolution, commercial quality.
PROMPT;

    // Preset labels (for UI display)
    public const GENDER_OPTIONS = ['woman' => 'Donna', 'man' => 'Uomo', 'neutral' => 'Neutro'];
    public const BACKGROUND_OPTIONS = [
        'studio_white' => 'Studio bianco',
        'urban' => 'Urbano/Street',
        'lifestyle' => 'Lifestyle indoor',
        'nature' => 'Natura/Outdoor',
    ];
    public const ENVIRONMENT_OPTIONS = [
        'living_room' => 'Soggiorno moderno',
        'kitchen' => 'Cucina rustica',
        'bedroom' => 'Camera minimal',
        'office' => 'Ufficio contemporaneo',
        'outdoor' => 'Giardino/Terrazza',
    ];
    public const PHOTO_STYLE_OPTIONS = [
        'professional' => 'Professional catalog',
        'editorial' => 'Editorial magazine',
        'minimal' => 'Minimal clean',
    ];
    public const CATEGORY_OPTIONS = [
        'fashion' => 'Fashion (try-on)',
        'home' => 'Home/Lifestyle (staging)',
        'custom' => 'Custom',
    ];

    public function __construct()
    {
        $providerName = ModuleLoader::getSetting('content-creator', 'image_provider', 'gemini');
        $this->provider = match ($providerName) {
            'gemini' => new GeminiImageProvider(),
            default => throw new \RuntimeException("Provider immagini non supportato: {$providerName}"),
        };

        $this->storagePath = dirname(__DIR__, 3) . '/storage/images';
    }

    /**
     * Get the current provider instance
     */
    public function getProvider(): ImageProviderInterface
    {
        return $this->provider;
    }

    /**
     * Build prompt from template + settings
     *
     * @param array $image cc_images row
     * @param array $projectDefaults From cc_projects.ai_settings.image_defaults
     * @return string Complete prompt
     */
    public function buildPrompt(array $image, array $projectDefaults): string
    {
        // Per-item override > project defaults
        $settings = $projectDefaults;
        if (!empty($image['generation_settings'])) {
            $override = is_string($image['generation_settings'])
                ? json_decode($image['generation_settings'], true)
                : $image['generation_settings'];
            if (is_array($override)) {
                $settings = array_merge($settings, $override);
            }
        }

        $category = $image['category'] ?? 'fashion';
        $gender = $settings['gender'] ?? 'woman';
        $background = $settings['background'] ?? 'studio_white';
        $environment = $settings['environment'] ?? 'living_room';
        $photoStyle = $settings['photo_style'] ?? 'professional';
        $customPrompt = $settings['custom_prompt'] ?? '';

        // Select template by category
        $template = match ($category) {
            'fashion' => self::TEMPLATE_FASHION,
            'home' => self::TEMPLATE_HOME,
            'custom' => self::TEMPLATE_CUSTOM,
            default => self::TEMPLATE_CUSTOM,
        };

        // Replace variables
        $prompt = str_replace(
            ['{gender}', '{background}', '{environment}', '{photo_style}'],
            [$gender, str_replace('_', ' ', $background), str_replace('_', ' ', $environment), $photoStyle],
            $template
        );

        // Append custom instructions if present
        if (!empty($customPrompt)) {
            $prompt .= "\n\nAdditional user instructions: " . trim($customPrompt);
        }

        return $prompt;
    }

    /**
     * Generate a single variant for an image
     *
     * @return array{success: bool, variant_data: ?array, error: ?string}
     */
    public function generateVariant(array $image, array $projectDefaults, int $variantNumber): array
    {
        $sourcePath = $image['source_image_path'];

        if (empty($sourcePath) || !file_exists($sourcePath)) {
            return ['success' => false, 'variant_data' => null, 'error' => 'Immagine sorgente non disponibile'];
        }

        $prompt = $this->buildPrompt($image, $projectDefaults);
        $startMs = (int) (microtime(true) * 1000);

        $result = $this->provider->generate($sourcePath, $prompt);

        $generationTimeMs = (int) (microtime(true) * 1000) - $startMs;

        if (!$result['success']) {
            return ['success' => false, 'variant_data' => null, 'error' => $result['error']];
        }

        // Save generated image to disk
        $savedPath = $this->saveImage($result['image_data'], (int) $image['id'], $variantNumber);

        if (!$savedPath) {
            return ['success' => false, 'variant_data' => null, 'error' => 'Errore salvataggio immagine su disco'];
        }

        $fileSize = strlen($result['image_data']);

        return [
            'success' => true,
            'variant_data' => [
                'image_id' => (int) $image['id'],
                'variant_number' => $variantNumber,
                'image_path' => $savedPath,
                'prompt_used' => $prompt,
                'revised_prompt' => $result['revised_prompt'],
                'provider_used' => $this->provider->getProviderName(),
                'file_size_bytes' => $fileSize,
                'generation_time_ms' => $generationTimeMs,
            ],
            'error' => null,
        ];
    }

    /**
     * Save generated image to disk
     *
     * @return string|null Relative path from storage root, or null on failure
     */
    public function saveImage(string $imageData, int $imageId, int $variantNumber): ?string
    {
        $year = date('Y');
        $month = date('m');
        $timestamp = time();

        $dir = "{$this->storagePath}/generated/{$year}/{$month}";
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = "{$imageId}_v{$variantNumber}_{$timestamp}.png";
        $fullPath = "{$dir}/{$filename}";

        if (file_put_contents($fullPath, $imageData) === false) {
            return null;
        }

        // Return path relative to storage root
        return "generated/{$year}/{$month}/{$filename}";
    }

    /**
     * Save source image to disk (during import)
     *
     * @return string|null Absolute path, or null on failure
     */
    public function saveSourceImage(string $imageData, int $imageId, string $extension = 'jpg'): ?string
    {
        $year = date('Y');
        $month = date('m');
        $timestamp = time();

        $dir = "{$this->storagePath}/sources/{$year}/{$month}";
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = "{$imageId}_{$timestamp}.{$extension}";
        $fullPath = "{$dir}/{$filename}";

        if (file_put_contents($fullPath, $imageData) === false) {
            return null;
        }

        return $fullPath;
    }

    /**
     * Download source image from URL
     *
     * @return array{success: bool, path: ?string, error: ?string}
     */
    public function downloadSourceImage(string $url, int $imageId): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_USERAGENT => 'Ainstein/1.0 (Image Import)',
        ]);

        $imageData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return ['success' => false, 'path' => null, 'error' => "Download fallito: {$curlError}"];
        }

        if ($httpCode !== 200) {
            return ['success' => false, 'path' => null, 'error' => "Download fallito: HTTP {$httpCode}"];
        }

        if (empty($imageData)) {
            return ['success' => false, 'path' => null, 'error' => 'Immagine vuota'];
        }

        // Detect extension from content type or URL
        $ext = $this->detectExtension($contentType, $url);

        // Validate it's an actual image
        if (!in_array($ext, $this->provider->getSupportedFormats())) {
            return ['success' => false, 'path' => null, 'error' => "Formato non supportato: {$ext}"];
        }

        // Validate file size
        if (strlen($imageData) > $this->provider->getMaxImageSize()) {
            return ['success' => false, 'path' => null, 'error' => 'Immagine troppo grande (max 10MB)'];
        }

        $path = $this->saveSourceImage($imageData, $imageId, $ext);

        if (!$path) {
            return ['success' => false, 'path' => null, 'error' => 'Errore salvataggio su disco'];
        }

        return ['success' => true, 'path' => $path, 'error' => null];
    }

    /**
     * Convert image format for export (PNG → WebP/JPEG)
     *
     * @return string|null Path to converted file, or null on failure
     */
    public function convertFormat(string $sourcePath, string $format = 'webp', int $quality = 85): ?string
    {
        $fullPath = $this->storagePath . '/' . $sourcePath;
        if (!file_exists($fullPath)) return null;

        $image = imagecreatefrompng($fullPath);
        if (!$image) return null;

        $outputPath = preg_replace('/\.png$/i', ".{$format}", $fullPath);

        $success = match ($format) {
            'webp' => imagewebp($image, $outputPath, $quality),
            'jpeg', 'jpg' => imagejpeg($image, $outputPath, $quality),
            'png' => true, // No conversion needed
            default => false,
        };

        imagedestroy($image);

        if (!$success) return null;

        return preg_replace('/\.png$/i', ".{$format}", $sourcePath);
    }

    /**
     * Get absolute path for a storage-relative path
     */
    public function getAbsolutePath(string $relativePath): string
    {
        return $this->storagePath . '/' . $relativePath;
    }

    /**
     * Get project image defaults from ai_settings JSON
     */
    public static function getProjectDefaults(array $project): array
    {
        $aiSettings = [];
        if (!empty($project['ai_settings'])) {
            $aiSettings = is_string($project['ai_settings'])
                ? json_decode($project['ai_settings'], true)
                : $project['ai_settings'];
        }

        $defaults = $aiSettings['image_defaults'] ?? [];

        return array_merge([
            'scene_type' => 'fashion',
            'gender' => 'woman',
            'background' => 'studio_white',
            'environment' => 'living_room',
            'photo_style' => 'professional',
            'variants_count' => 3,
            'custom_prompt' => '',
            'push_mode' => 'add_as_gallery',
        ], $defaults);
    }

    /**
     * Detect file extension from content type or URL
     */
    private function detectExtension(?string $contentType, string $url): string
    {
        // Try content type first
        if ($contentType) {
            $map = [
                'image/png' => 'png',
                'image/jpeg' => 'jpg',
                'image/webp' => 'webp',
                'image/jpg' => 'jpg',
            ];
            foreach ($map as $mime => $ext) {
                if (str_contains($contentType, $mime)) return $ext;
            }
        }

        // Fallback to URL extension
        $path = parse_url($url, PHP_URL_PATH);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) return $ext;

        return 'jpg'; // Default
    }

    /**
     * Delete image file from disk
     */
    public function deleteFile(string $relativePath): bool
    {
        $fullPath = $this->storagePath . '/' . $relativePath;
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        return true; // Already gone
    }
}
```

- [ ] **Step 2: Verify syntax**

```bash
php -l modules/content-creator/services/ImageGenerationService.php
```

- [ ] **Step 3: Commit**

```bash
git add modules/content-creator/services/ImageGenerationService.php
git commit -m "feat(content-creator): add ImageGenerationService orchestrator with prompt templates, download, conversion"
```

---

## Chunk 2 Complete

**Verify all together:**
```bash
php -l modules/content-creator/services/providers/ImageProviderInterface.php
php -l modules/content-creator/services/providers/GeminiImageProvider.php
php -l modules/content-creator/services/ImageGenerationService.php
```

All three must report `No syntax errors detected`.
