<?php
/**
 * Content Creator — Image Generation PoC
 *
 * Test Gemini quality with sample images.
 * Run: php modules/content-creator/scripts/poc-image-generation.php
 *
 * Requirements:
 * - google_gemini_api_key configured in admin settings
 * - Sample images in storage/images/poc/
 */

require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Settings.php';
require_once __DIR__ . '/../../../core/ModuleLoader.php';
require_once __DIR__ . '/../../../core/Cache.php';
require_once __DIR__ . '/../../../services/ApiLoggerService.php';
require_once __DIR__ . '/../services/providers/ImageProviderInterface.php';
require_once __DIR__ . '/../services/providers/GeminiImageProvider.php';
require_once __DIR__ . '/../services/ImageGenerationService.php';

use Core\Database;
use Modules\ContentCreator\Services\Providers\GeminiImageProvider;
use Modules\ContentCreator\Services\ImageGenerationService;

echo "=== Content Creator — Image Generation PoC ===\n\n";

// Check API key
$apiKey = \Core\Settings::get('google_gemini_api_key');
if (empty($apiKey)) {
    echo "ERROR: google_gemini_api_key non configurata in admin settings.\n";
    exit(1);
}

echo "API key trovata: " . substr($apiKey, 0, 8) . "...\n";

// Create output directory
$pocDir = dirname(__DIR__, 3) . '/storage/images/poc';
$outputDir = $pocDir . '/output';
if (!is_dir($outputDir)) mkdir($outputDir, 0755, true);

// Find sample images
$sampleImages = glob($pocDir . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE);

if (empty($sampleImages)) {
    echo "\nNessuna immagine di test trovata in: {$pocDir}\n";
    echo "Inserisci 5-10 foto prodotto (JPG/PNG/WebP) nella directory.\n";
    echo "Esempi: t-shirt, scarpa, borsa, lampada, divano, tazza.\n";
    exit(1);
}

echo "Trovate " . count($sampleImages) . " immagini di test.\n\n";

$service = new ImageGenerationService();
$provider = new GeminiImageProvider();

$prompts = [
    'fashion' => "Using the product shown in the attached image, generate a professional e-commerce photo of a woman model wearing/using this exact product. The product must be faithfully reproduced with accurate colors, textures, patterns and details. Setting: studio white. Photography style: professional catalog. No text, no watermarks. High resolution, commercial quality.",
    'home' => "Using the product shown in the attached image, generate a professional interior design photo showing this exact product placed naturally in a modern living room setting. The product must be faithfully reproduced with accurate colors, proportions, materials. Photography style: professional catalog. Natural lighting, realistic perspective. No text, no watermarks. High resolution.",
];

$results = [];

foreach ($sampleImages as $i => $imagePath) {
    $filename = basename($imagePath);
    $category = ($i < count($sampleImages) / 2) ? 'fashion' : 'home';

    echo "--- [{$category}] {$filename} ---\n";
    echo "Generazione in corso...";

    $startTime = microtime(true);
    $result = $provider->generate($imagePath, $prompts[$category]);
    $elapsed = round(microtime(true) - $startTime, 1);

    Database::reconnect();

    if ($result['success']) {
        $outputFile = "{$outputDir}/{$category}_{$filename}";
        file_put_contents($outputFile, $result['image_data']);
        $size = round(strlen($result['image_data']) / 1024);
        echo " OK ({$elapsed}s, {$size}KB)\n";
        echo "  Output: {$outputFile}\n";
        if (!empty($result['revised_prompt'])) {
            echo "  Revised: " . mb_substr($result['revised_prompt'], 0, 100) . "...\n";
        }
        $results[] = ['file' => $filename, 'category' => $category, 'success' => true, 'time' => $elapsed, 'size' => $size];
    } else {
        echo " ERRORE ({$elapsed}s)\n";
        echo "  Error: {$result['error']}\n";
        $results[] = ['file' => $filename, 'category' => $category, 'success' => false, 'time' => $elapsed, 'error' => $result['error']];
    }

    echo "\n";

    // Rate limit: 2s between calls
    if ($i < count($sampleImages) - 1) {
        sleep(2);
    }
}

// Summary
echo "=== RISULTATI ===\n\n";
$success = count(array_filter($results, fn($r) => $r['success']));
$failed = count($results) - $success;
$avgTime = count($results) > 0 ? round(array_sum(array_column($results, 'time')) / count($results), 1) : 0;

echo "Totale: " . count($results) . " | Successo: {$success} | Falliti: {$failed}\n";
echo "Tempo medio: {$avgTime}s per immagine\n";
echo "Output: {$outputDir}/\n\n";

echo "VALUTARE MANUALMENTE:\n";
echo "1. Fedelta al prodotto originale (colori, texture, forma)\n";
echo "2. Qualita fotografica (luci, ombre, composizione)\n";
echo "3. Naturalezza (posa modello / posizionamento prodotto)\n";
echo "4. Uso commerciale (adeguato per e-commerce?)\n\n";

if ($failed > 0) {
    echo "ATTENZIONE: {$failed} generazioni fallite. Controllare error messages.\n";
}
