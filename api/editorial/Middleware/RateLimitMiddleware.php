<?php

declare(strict_types=1);

namespace Ainstein\Editorial\Middleware;

use Ainstein\Editorial\Http\HttpException;
use Ainstein\Editorial\Http\Request;

/**
 * Rate limit per license key (default 60 req/min), basato su file counter.
 *
 * Implementazione file-based (no dipendenza Redis) sufficiente per MVP single-node.
 * Finestra fissa al minuto. Se manca la license key, usa l'IP come chiave.
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    public function handle(Request $request): void
    {
        $config = require __DIR__ . '/../../../config/editorial.php';
        $limit  = (int) ($config['rate_limit']['per_minute'] ?? 60);

        $key = $request->header('x-license-key')
            ?: ($_SERVER['REMOTE_ADDR'] ?? 'anon');
        $window = (int) floor(time() / 60);
        $hash   = hash('sha256', $key . ':' . $window);

        $dir = sys_get_temp_dir() . '/aied_ratelimit';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $file = $dir . '/' . $hash;

        $count = 0;
        $fh = @fopen($file, 'c+');
        if ($fh !== false) {
            flock($fh, LOCK_EX);
            $count = (int) stream_get_contents($fh);
            $count++;
            rewind($fh);
            ftruncate($fh, 0);
            fwrite($fh, (string) $count);
            flock($fh, LOCK_UN);
            fclose($fh);
        }

        if ($count > $limit) {
            throw new HttpException(429, 'Troppe richieste. Riprova tra qualche istante.', [
                'retry_after' => 60 - (time() % 60),
            ]);
        }
    }
}
