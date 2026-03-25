<?php

namespace Modules\ContentCreator\Services;

use Core\ModuleLoader;
use Modules\ContentCreator\Services\Providers\ImageProviderInterface;
use Modules\ContentCreator\Services\Providers\GeminiImageProvider;

class ImageGenerationService
{
    private ImageProviderInterface $provider;
    private string $storagePath;

    private const TEMPLATE_FASHION = <<<'PROMPT'
Edit this product photo. Keep the product exactly as shown — do not modify
its shape, color, texture, pattern or any detail. The product is the focus.
Generate a professional e-commerce photo of a {gender} model wearing/using
this exact product. Setting: {background}. Photography style: {photo_style}.
The model should have a natural, confident pose suitable for e-commerce catalog.
Add realistic shadows beneath the product for depth.
No text, no watermarks, no logos, no overlays.
High resolution, commercial quality, clean composition.
PROMPT;

    private const TEMPLATE_HOME = <<<'PROMPT'
Edit this product photo. Keep the product exactly as shown — do not modify
its shape, color, material, proportion or any detail. The product is the focus.
Place this product naturally in a {environment} setting.
Photography style: {photo_style}. Use soft natural lighting from the left.
Add realistic shadows beneath the product for depth.
No text, no watermarks, no logos.
High resolution, commercial quality, inviting atmosphere.
PROMPT;

    private const TEMPLATE_CUSTOM = <<<'PROMPT'
Edit this product photo. Keep the product exactly as shown — do not modify
its shape, color, proportion or any detail. The product is the focus.
Place this product in an appealing commercial context.
Photography style: {photo_style}. Use professional studio lighting.
Add realistic shadows beneath the product for depth.
No text, no watermarks, no logos.
High resolution, commercial quality.
PROMPT;

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

    public function getProvider(): ImageProviderInterface
    {
        return $this->provider;
    }

    public function buildPrompt(array $image, array $projectDefaults): string
    {
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

        $template = match ($category) {
            'fashion' => self::TEMPLATE_FASHION,
            'home' => self::TEMPLATE_HOME,
            'custom' => self::TEMPLATE_CUSTOM,
            default => self::TEMPLATE_CUSTOM,
        };

        $prompt = str_replace(
            ['{gender}', '{background}', '{environment}', '{photo_style}'],
            [$gender, str_replace('_', ' ', $background), str_replace('_', ' ', $environment), $photoStyle],
            $template
        );

        if (!empty($customPrompt)) {
            $prompt .= "\n\nAdditional user instructions: " . trim($customPrompt);
        }

        return $prompt;
    }

    public function generateVariant(array $image, array $projectDefaults, int $variantNumber): array
    {
        $sourcePath = $this->storagePath . '/' . $image['source_image_path'];

        if (empty($image['source_image_path']) || !file_exists($sourcePath)) {
            return ['success' => false, 'variant_data' => null, 'error' => 'Immagine sorgente non disponibile'];
        }

        $prompt = $this->buildPrompt($image, $projectDefaults);
        $startMs = (int) (microtime(true) * 1000);

        $result = $this->provider->generate($sourcePath, $prompt);

        $generationTimeMs = (int) (microtime(true) * 1000) - $startMs;

        if (!$result['success']) {
            return ['success' => false, 'variant_data' => null, 'error' => $result['error']];
        }

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

        return "generated/{$year}/{$month}/{$filename}";
    }

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

        return "sources/{$year}/{$month}/{$filename}";
    }

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

        $ext = $this->detectExtension($contentType, $url);

        if (!in_array($ext, $this->provider->getSupportedFormats())) {
            return ['success' => false, 'path' => null, 'error' => "Formato non supportato: {$ext}"];
        }

        if (strlen($imageData) > $this->provider->getMaxImageSize()) {
            return ['success' => false, 'path' => null, 'error' => 'Immagine troppo grande (max 10MB)'];
        }

        $path = $this->saveSourceImage($imageData, $imageId, $ext);

        if (!$path) {
            return ['success' => false, 'path' => null, 'error' => 'Errore salvataggio su disco'];
        }

        return ['success' => true, 'path' => $path, 'error' => null];
    }

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
            'png' => true,
            default => false,
        };

        imagedestroy($image);

        if (!$success) return null;

        return preg_replace('/\.png$/i', ".{$format}", $sourcePath);
    }

    public function getAbsolutePath(string $relativePath): string
    {
        return $this->storagePath . '/' . $relativePath;
    }

    public static function getProjectDefaults(array $project): array
    {
        $aiSettings = [];
        if (!empty($project['ai_settings'])) {
            $aiSettings = is_string($project['ai_settings'])
                ? json_decode($project['ai_settings'], true)
                : $project['ai_settings'];
        }

        $projectDefaults = $aiSettings['image_defaults'] ?? [];

        // Admin defaults from module settings (fallback for unset project values)
        $adminDefaults = [
            'scene_type' => \Core\ModuleLoader::getSetting('content-creator', 'default_scene_type', 'fashion'),
            'gender' => \Core\ModuleLoader::getSetting('content-creator', 'default_gender', 'woman'),
            'background' => \Core\ModuleLoader::getSetting('content-creator', 'default_background', 'studio_white'),
            'environment' => \Core\ModuleLoader::getSetting('content-creator', 'default_environment', 'living_room'),
            'photo_style' => \Core\ModuleLoader::getSetting('content-creator', 'default_photo_style', 'professional'),
            'variants_count' => (int) \Core\ModuleLoader::getSetting('content-creator', 'default_variants_count', 3),
            'custom_prompt' => '',
            'push_mode' => \Core\ModuleLoader::getSetting('content-creator', 'image_push_mode', 'add_as_gallery'),
        ];

        return array_merge($adminDefaults, $projectDefaults);
    }

    /**
     * Get admin-level defaults (from module.json settings)
     */
    public static function getAdminDefaults(): array
    {
        return [
            'scene_type' => \Core\ModuleLoader::getSetting('content-creator', 'default_scene_type', 'fashion'),
            'gender' => \Core\ModuleLoader::getSetting('content-creator', 'default_gender', 'woman'),
            'background' => \Core\ModuleLoader::getSetting('content-creator', 'default_background', 'studio_white'),
            'environment' => \Core\ModuleLoader::getSetting('content-creator', 'default_environment', 'living_room'),
            'photo_style' => \Core\ModuleLoader::getSetting('content-creator', 'default_photo_style', 'professional'),
            'variants_count' => (int) \Core\ModuleLoader::getSetting('content-creator', 'default_variants_count', 3),
            'custom_prompt' => '',
            'push_mode' => \Core\ModuleLoader::getSetting('content-creator', 'image_push_mode', 'add_as_gallery'),
        ];
    }

    private function detectExtension(?string $contentType, string $url): string
    {
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

        $path = parse_url($url, PHP_URL_PATH);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) return $ext;

        return 'jpg';
    }

    public function deleteFile(string $relativePath): bool
    {
        $fullPath = $this->storagePath . '/' . $relativePath;
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        return true;
    }
}
