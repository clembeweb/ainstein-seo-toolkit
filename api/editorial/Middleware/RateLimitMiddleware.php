<?php

namespace Editorial\Middleware;

/**
 * RateLimitMiddleware
 *
 * Limite semplice file-based: 60 richieste/minuto per license_key
 * (o per IP se la license_key non e ancora disponibile).
 *
 * Storage: storage/editorial_ratelimit/{hash}.json
 * Contenuto: { "window_start": <unix_ts>, "count": N }
 *
 * Risposta 429 quando il limite e superato, con header Retry-After
 * indicante i secondi restanti nella finestra.
 */
class RateLimitMiddleware
{
    private const LIMIT = 60;
    private const WINDOW_SECONDS = 60;

    /**
     * @param string|null $key Identificatore per il bucket (es. license_key).
     *                         Se null, usa l'IP del client.
     */
    public static function handle(?string $key = null): void
    {
        $bucket = $key !== null && $key !== '' ? $key : self::clientIp();
        $cacheDir = self::cacheDir();
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0775, true);
        }

        // Cleanup opportunistico (1% delle richieste): rimuove file piu' vecchi
        // di 2x la finestra → mantiene la cartella in dimensioni gestibili.
        if (random_int(1, 100) === 1) {
            self::pruneStaleFiles($cacheDir);
        }

        $file = $cacheDir . '/' . hash('sha256', $bucket) . '.json';
        $now  = time();
        $data = ['window_start' => $now, 'count' => 0];

        // Lock + read-modify-write
        $fp = @fopen($file, 'c+');
        if ($fp === false) {
            // Se il file non e accessibile, fail-open (meglio servire la richiesta che bloccare).
            error_log("[editorial.ratelimit] fopen failed for {$file}");
            self::setHeaders(self::LIMIT, self::LIMIT, self::WINDOW_SECONDS);
            return;
        }

        if (!flock($fp, LOCK_EX)) {
            // Lock fallito → fail-open ma logghiamo
            error_log("[editorial.ratelimit] flock failed for bucket " . substr(hash('sha256', $bucket), 0, 8));
            fclose($fp);
            self::setHeaders(self::LIMIT, self::LIMIT, self::WINDOW_SECONDS);
            return;
        }

        $contents = stream_get_contents($fp);
        if ($contents) {
            $decoded = json_decode($contents, true);
            if (is_array($decoded) && isset($decoded['window_start'], $decoded['count'])) {
                $data = $decoded;
            }
        }

        // Reset finestra se scaduta
        if (($now - (int) $data['window_start']) >= self::WINDOW_SECONDS) {
            $data = ['window_start' => $now, 'count' => 0];
        }

        $data['count']++;
        $remaining = max(0, self::LIMIT - (int) $data['count']);
        $resetIn   = self::WINDOW_SECONDS - ($now - (int) $data['window_start']);

        // Salva
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($data));
        flock($fp, LOCK_UN);
        fclose($fp);

        self::setHeaders(self::LIMIT, $remaining, $resetIn);

        if ((int) $data['count'] > self::LIMIT) {
            self::respondTooMany($resetIn);
        }
    }

    /**
     * Rimuove file di rate-limit piu' vecchi di 2x la finestra.
     * Chiamato in modo opportunistico (1% delle richieste) per non aggiungere overhead.
     */
    private static function pruneStaleFiles(string $dir): void
    {
        $cutoff = time() - (self::WINDOW_SECONDS * 2);
        $iter = @scandir($dir);
        if (!is_array($iter)) return;
        foreach ($iter as $name) {
            if ($name === '.' || $name === '..') continue;
            if (!str_ends_with($name, '.json')) continue;
            $path = $dir . '/' . $name;
            $mtime = @filemtime($path);
            if ($mtime !== false && $mtime < $cutoff) {
                @unlink($path);
            }
        }
    }

    private static function setHeaders(int $limit, int $remaining, int $resetIn): void
    {
        header('X-RateLimit-Limit: ' . $limit);
        header('X-RateLimit-Remaining: ' . $remaining);
        header('X-RateLimit-Reset: ' . $resetIn);
    }

    private static function respondTooMany(int $retryAfter): void
    {
        if (function_exists('ob_get_level') && ob_get_level() > 0) {
            @ob_end_clean();
        }
        http_response_code(429);
        header('Content-Type: application/json; charset=utf-8');
        header('Retry-After: ' . max(1, $retryAfter));
        echo json_encode([
            'error'       => 'Rate limit exceeded',
            'retry_after' => max(1, $retryAfter),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    private static function clientIp(): string
    {
        // X-Forwarded-For (in Coupler/proxy scenarios) - prima entry
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $list = explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($list[0]);
        }
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    private static function cacheDir(): string
    {
        return __DIR__ . '/../../../storage/editorial_ratelimit';
    }
}
