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
    // Only delete variants where parent image is NOT in pending/generated state
    // (those are still awaiting review and should not be cleaned up)
    $oldVariants = Database::fetchAll(
        "SELECT v.id, v.image_path
         FROM cc_image_variants v
         JOIN cc_images i ON i.id = v.image_id
         WHERE v.is_approved = 0
         AND v.is_pushed = 0
         AND i.status NOT IN ('pending', 'source_acquired', 'generated')
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

    // 2. Clean orphan source files (images with no project — safety net for CASCADE)
    $orphanSources = Database::fetchAll(
        "SELECT i.id, i.source_image_path
         FROM cc_images i
         LEFT JOIN cc_projects p ON p.id = i.project_id
         WHERE p.id IS NULL"
    );

    foreach ($orphanSources as $orphan) {
        $fullPath = $storagePath . '/' . $orphan['source_image_path'];
        if (!empty($orphan['source_image_path']) && file_exists($fullPath)) {
            unlink($fullPath);
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

    if ($oldZips) {
        foreach ($oldZips as $zipFile) {
            if (filemtime($zipFile) < time() - 86400) { // > 24h
                unlink($zipFile);
                $zipsDeleted++;
            }
        }
    }

    if ($zipsDeleted > 0) {
        echo "{$logPrefix} ZIP temporanei eliminati: {$zipsDeleted}\n";
    }

    echo "{$logPrefix} Pulizia completata\n";

} catch (\Exception $e) {
    echo "{$logPrefix} ERRORE: " . $e->getMessage() . "\n";
}
