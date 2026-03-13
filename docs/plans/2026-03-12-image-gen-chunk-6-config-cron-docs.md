# Chunk 6: Configuration, Cron, Documentation, PoC

> **Parent plan:** [Plan Index](./2026-03-12-image-generation-plan-index.md)
> **Design spec:** [Design Spec](./2026-03-12-content-creator-image-generation-design.md) — Sections 9-14
> **Depends on:** Chunks 1-5 (all previous work)

---

## Task 23: Update module.json

**Files:**
- Modify: `modules/content-creator/module.json`

- [ ] **Step 1: Read current module.json**

- [ ] **Step 2: Add image_config group and settings**

Add new group after `content_length` (order: 3):

```json
"image_config": {
    "label": "Generazione Immagini",
    "order": 3,
    "collapsed": false
}
```

Add settings to the `settings` section:

```json
"image_provider": {
    "label": "Provider Immagini",
    "type": "select",
    "options": { "gemini": "Google Gemini" },
    "default": "gemini",
    "group": "image_config",
    "description": "Provider AI per la generazione immagini"
},
"image_model": {
    "label": "Modello Immagini",
    "type": "text",
    "default": "gemini-2.0-flash-exp",
    "group": "image_config",
    "description": "Modello Gemini per image-to-image"
},
"default_variants_count": {
    "label": "Varianti per prodotto (default)",
    "type": "number",
    "default": 3,
    "min": 1,
    "max": 4,
    "group": "image_config"
},
"default_scene_type": {
    "label": "Tipo scena (default)",
    "type": "select",
    "options": { "fashion": "Fashion (try-on)", "home": "Home/Lifestyle (staging)" },
    "default": "fashion",
    "group": "image_config"
},
"default_gender": {
    "label": "Genere modello (default)",
    "type": "select",
    "options": { "woman": "Donna", "man": "Uomo", "neutral": "Neutro" },
    "default": "woman",
    "group": "image_config"
},
"default_background": {
    "label": "Sfondo (default)",
    "type": "select",
    "options": { "studio_white": "Studio bianco", "urban": "Urbano", "lifestyle": "Lifestyle", "nature": "Natura" },
    "default": "studio_white",
    "group": "image_config"
},
"default_environment": {
    "label": "Ambiente (default)",
    "type": "select",
    "options": { "living_room": "Soggiorno", "kitchen": "Cucina", "bedroom": "Camera", "office": "Ufficio", "outdoor": "Esterno" },
    "default": "living_room",
    "group": "image_config"
},
"default_photo_style": {
    "label": "Stile fotografico (default)",
    "type": "select",
    "options": { "professional": "Professional catalog", "editorial": "Editorial magazine", "minimal": "Minimal clean" },
    "default": "professional",
    "group": "image_config"
},
"image_push_mode": {
    "label": "Modalità push CMS (default)",
    "type": "select",
    "options": { "add_as_gallery": "Aggiungi in galleria", "replace_main": "Sostituisci principale", "add": "Aggiungi" },
    "default": "add_as_gallery",
    "group": "image_config"
},
"image_output_format": {
    "label": "Formato export",
    "type": "select",
    "options": { "webp": "WebP", "jpeg": "JPEG", "png": "PNG" },
    "default": "webp",
    "group": "image_config"
},
"image_output_quality": {
    "label": "Qualità export (%)",
    "type": "number",
    "default": 85,
    "min": 60,
    "max": 100,
    "group": "image_config"
},
"image_cleanup_days": {
    "label": "Retention varianti rifiutate (giorni)",
    "type": "number",
    "default": 30,
    "group": "image_config"
}
```

Add cost to `costs` group:

```json
"cost_image_generate": {
    "label": "Costo generazione immagine (per variante)",
    "type": "number",
    "default": 2,
    "group": "costs"
}
```

- [ ] **Step 3: Verify JSON is valid**

```bash
python3 -c "import json; json.load(open('modules/content-creator/module.json'))" 2>&1 || echo "INVALID JSON"
```

Or manually validate at jsonlint.com.

- [ ] **Step 4: Commit**

```bash
git add modules/content-creator/module.json
git commit -m "feat(content-creator): add image_config settings group + cost_image_generate to module.json"
```

---

## Task 24: Image Cleanup Cron

**Files:**
- Create: `modules/content-creator/cron/image-cleanup.php`

**Schedule:** Daily at 5:00 AM (see design spec section 9).

- [ ] **Step 1: Write cleanup cron**

```php
<?php
/**
 * Content Creator — Image Cleanup Cron
 *
 * Pulizia periodica:
 * 1. Varianti rifiutate (is_approved=0) più vecchie di N giorni → elimina file + record
 * 2. File ZIP temporanei in /tmp → elimina se > 24h
 *
 * Schedule: 0 5 * * * (daily at 5:00 AM)
 */

require_once __DIR__ . '/../../../cron/bootstrap.php';

use Core\Database;
use Core\ModuleLoader;

$logPrefix = '[image-cleanup]';

try {
    $cleanupDays = (int) ModuleLoader::getSetting('content-creator', 'image_cleanup_days', 30);
    $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$cleanupDays} days"));
    $storagePath = dirname(__DIR__, 3) . '/storage/images';

    echo "{$logPrefix} Inizio pulizia immagini (retention: {$cleanupDays} giorni, cutoff: {$cutoffDate})\n";

    // 1. Find rejected variants older than cutoff
    $oldVariants = Database::fetchAll(
        "SELECT v.id, v.image_path
         FROM cc_image_variants v
         WHERE v.is_approved = 0
         AND v.created_at < ?",
        [$cutoffDate]
    );

    $filesDeleted = 0;
    $recordsDeleted = 0;

    foreach ($oldVariants as $v) {
        // Delete file
        $fullPath = $storagePath . '/' . $v['image_path'];
        if (file_exists($fullPath)) {
            unlink($fullPath);
            $filesDeleted++;
        }

        // Also delete any converted versions (.webp, .jpg)
        $basePath = preg_replace('/\.[^.]+$/', '', $fullPath);
        foreach (['webp', 'jpg', 'jpeg'] as $ext) {
            $convertedPath = $basePath . '.' . $ext;
            if (file_exists($convertedPath)) {
                unlink($convertedPath);
                $filesDeleted++;
            }
        }

        // Delete DB record
        Database::execute("DELETE FROM cc_image_variants WHERE id = ?", [$v['id']]);
        $recordsDeleted++;
    }

    echo "{$logPrefix} Varianti rifiutate: {$recordsDeleted} record, {$filesDeleted} file eliminati\n";

    // 2. Clean orphan source files (images with no project — shouldn't happen with CASCADE, but safety net)
    $orphanSources = Database::fetchAll(
        "SELECT i.id, i.source_image_path
         FROM cc_images i
         LEFT JOIN cc_projects p ON p.id = i.project_id
         WHERE p.id IS NULL"
    );

    foreach ($orphanSources as $orphan) {
        if (!empty($orphan['source_image_path']) && file_exists($orphan['source_image_path'])) {
            unlink($orphan['source_image_path']);
        }
        Database::execute("DELETE FROM cc_images WHERE id = ?", [$orphan['id']]);
    }

    if (count($orphanSources) > 0) {
        echo "{$logPrefix} Sorgenti orfane eliminate: " . count($orphanSources) . "\n";
    }

    // 3. Clean old ZIP files in /tmp
    $tmpPattern = sys_get_temp_dir() . '/ainstein_export_*';
    $oldZips = glob($tmpPattern);
    $zipsDeleted = 0;

    foreach ($oldZips as $zipFile) {
        if (filemtime($zipFile) < time() - 86400) { // > 24h
            unlink($zipFile);
            $zipsDeleted++;
        }
    }

    if ($zipsDeleted > 0) {
        echo "{$logPrefix} ZIP temporanei eliminati: {$zipsDeleted}\n";
    }

    echo "{$logPrefix} Pulizia completata\n";

} catch (\Exception $e) {
    echo "{$logPrefix} ERRORE: " . $e->getMessage() . "\n";
}
```

- [ ] **Step 2: Verify syntax**

```bash
php -l modules/content-creator/cron/image-cleanup.php
```

- [ ] **Step 3: Commit**

```bash
git add modules/content-creator/cron/image-cleanup.php
git commit -m "feat(content-creator): add image-cleanup cron for rejected variants and temp files"
```

- [ ] **Step 4: Document crontab entry**

Add to `CLAUDE.md` and `docs/DEPLOY.md` the crontab line:
```
0 5 * * * cd /var/www/ainstein.it/public_html && php modules/content-creator/cron/image-cleanup.php >> /var/log/ainstein/cron.log 2>&1
```

---

## Task 25: Update Documentation

**Files:**
- Modify: `shared/views/docs/content-creator.php`
- Modify: `docs/data-model.html`

**Golden Rule #18:** Docs must be updated after significant development.

- [ ] **Step 1: Update user docs**

Add new section to `shared/views/docs/content-creator.php`:

```html
<!-- After existing features section -->
<div class="mt-8">
    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-3">
        <svg class="w-5 h-5 inline-block text-violet-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        Generazione Immagini
    </h3>
    <p class="text-sm text-slate-600 dark:text-slate-300 mb-2">
        Genera varianti professionali delle immagini prodotto usando l'AI. Due modalita:
    </p>
    <ul class="list-disc list-inside text-sm text-slate-600 dark:text-slate-300 space-y-1 ml-4">
        <li><strong>Fashion try-on:</strong> modello che indossa il prodotto (abiti, accessori, scarpe)</li>
        <li><strong>Home staging:</strong> prodotto posizionato in ambientazione realistica (soggiorno, cucina, ufficio)</li>
    </ul>
    <div class="mt-3 bg-violet-50 dark:bg-violet-900/20 rounded-lg p-3">
        <h4 class="text-sm font-medium text-violet-800 dark:text-violet-200">Quick Start Immagini</h4>
        <ol class="mt-1 text-sm text-violet-700 dark:text-violet-300 list-decimal list-inside space-y-0.5">
            <li>Apri un progetto e clicca il toggle "Immagini"</li>
            <li>Importa prodotti da CMS, CSV o upload manuale</li>
            <li>Clicca "Genera Immagini" — l'AI crea varianti</li>
            <li>Rivedi, approva le migliori e pubblica sul CMS</li>
        </ol>
    </div>
</div>
```

Update the credit cost table adding:
```html
<tr>
    <td class="px-4 py-3 text-sm">Generazione immagine (per variante)</td>
    <td class="px-4 py-3 text-sm font-medium">2 crediti</td>
</tr>
```

- [ ] **Step 2: Update data model**

Add `cc_images` and `cc_image_variants` to `docs/data-model.html` in the content-creator section (amber border).

Add to erDiagram:
```mermaid
cc_projects ||--o{ cc_images : "has"
cc_images ||--o{ cc_image_variants : "has"
```

Add collapsible `<details>` for each new table.

- [ ] **Step 3: Verify syntax + commit**

```bash
php -l shared/views/docs/content-creator.php
git add shared/views/docs/content-creator.php docs/data-model.html
git commit -m "docs(content-creator): add image generation to user docs + data model"
```

---

## Task 26: Proof of Concept Script (Task 0)

**Files:**
- Create: `modules/content-creator/scripts/poc-image-generation.php`

**Purpose:** Test Gemini image quality with 10 sample products BEFORE implementing full UI. Run from CLI.

- [ ] **Step 1: Write PoC script**

```php
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
        if ($result['revised_prompt']) {
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
$avgTime = round(array_sum(array_column($results, 'time')) / count($results), 1);

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
```

- [ ] **Step 2: Create PoC directory**

```bash
mkdir -p storage/images/poc/output
echo "Inserisci 5-10 foto prodotto (JPG/PNG/WebP) qui per il PoC" > storage/images/poc/README.txt
```

- [ ] **Step 3: Verify syntax + commit**

```bash
php -l modules/content-creator/scripts/poc-image-generation.php
git add modules/content-creator/scripts/poc-image-generation.php storage/images/poc/README.txt
git commit -m "feat(content-creator): add PoC script for Gemini image generation quality testing"
```

---

## Task 26b: Update CLAUDE.md

**Files:**
- Modify: `CLAUDE.md`

- [ ] **Step 1: Add cron entry to CLAUDE.md cron section**

Add to the cron jobs table:

```
modules/content-creator/cron/image-cleanup.php    # Daily (0 5 * * *)
```

- [ ] **Step 2: Update module status**

Change Content Creator status from `Completo (4 CMS connectors)` to `Completo (4 CMS connectors + image generation)`.

- [ ] **Step 3: Commit**

```bash
git add CLAUDE.md
git commit -m "docs: update CLAUDE.md with image-cleanup cron + module status"
```

---

## Chunk 6 Complete — Feature Ready

**Final verification checklist:**
```bash
# All PHP files syntax check
php -l modules/content-creator/models/Image.php
php -l modules/content-creator/models/ImageVariant.php
php -l modules/content-creator/models/Job.php
php -l modules/content-creator/controllers/ImageController.php
php -l modules/content-creator/controllers/ImageGeneratorController.php
php -l modules/content-creator/services/providers/ImageProviderInterface.php
php -l modules/content-creator/services/providers/GeminiImageProvider.php
php -l modules/content-creator/services/ImageGenerationService.php
php -l modules/content-creator/services/connectors/ImageCapableConnectorInterface.php
php -l modules/content-creator/cron/image-cleanup.php
php -l modules/content-creator/routes.php
php -l modules/content-creator/views/images/index.php
php -l modules/content-creator/views/images/import.php
php -l modules/content-creator/views/images/preview.php

# Database tables exist
mysql -u root seo_toolkit -e "SELECT COUNT(*) FROM cc_images;"
mysql -u root seo_toolkit -e "SELECT COUNT(*) FROM cc_image_variants;"

# Storage directories exist
ls -la storage/images/sources/
ls -la storage/images/generated/
```

**Manual browser test sequence:**
1. Open a content-creator project → segmented toggle visible
2. Click "Immagini" → empty state with import CTA
3. Go to Import → 3 tabs (CMS/CSV/Manual)
4. Upload manual image → appears in list with "Foto OK" status
5. Click "Genera Immagini" → SSE progress → variants generated
6. Click product name → preview with variants + lightbox
7. Approve a variant → status changes
8. Settings → "Immagini" tab → presets visible
9. Export ZIP → downloads correctly

**Production deploy (after all testing):**
```bash
# Run migration on production
ssh -i ~/.ssh/ainstein_hetzner ainstein@91.99.20.247
cd /var/www/ainstein.it/public_html
git pull origin main
mysql -u ainstein -p'Ainstein_DB_2026!Secure' ainstein_seo < modules/content-creator/database/migration-images.sql
mkdir -p storage/images/sources storage/images/generated

# Add crontab
crontab -e
# Add: 0 5 * * * cd /var/www/ainstein.it/public_html && php modules/content-creator/cron/image-cleanup.php >> /var/log/ainstein/cron.log 2>&1

# Add Gemini API key via admin panel
# Admin > Settings > API Keys > google_gemini_api_key
```
