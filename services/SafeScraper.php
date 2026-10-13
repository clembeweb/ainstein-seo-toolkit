<?php

namespace Services;

/**
 * Scraper per richieste nate da un indirizzo scritto da uno sconosciuto (controllo AI gratuito della home).
 * Anti-SSRF: ogni URL (anche dopo un redirect) deve risolvere SOLO a IP pubblici; la connessione è pinnata all'IP
 * verificato (CURLOPT_RESOLVE: niente DNS rebinding), solo http/https, redirect seguiti a mano e rivalidati,
 * risposta limitata a 2 MB. Stessa firma e stesso formato di ScraperService::fetchRaw().
 */
class SafeScraper extends ScraperService
{
    private const MAX_REDIRECTS = 5;
    private const MAX_BYTES = 2_000_000;

    public function fetchRaw(string $url, array $options = []): array
    {
        $headers = ($options['api_mode'] ?? false)
            ? ($options['headers'] ?? [])
            : array_merge(['User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8', 'Accept-Language: it-IT,it;q=0.9,en;q=0.8'], $options['headers'] ?? []);
        // Controllo pubblico: attese corte. Le richieste coi lettori AI (api_mode) che restano appese oltre 6 s
        // contano come bloccate, che è anche quello che vede il crawler vero (beweb.it: 93 s con 15 s per richiesta).
        $timeout = ($options['api_mode'] ?? false) ? 6 : min((int) ($options['timeout'] ?? 10), 10);

        for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
            $target = self::resolveTarget($url);
            if ($target === null) {
                return ['error' => true, 'message' => 'Indirizzo non raggiungibile da un controllo pubblico'];
            }
            [$host, $port, $ip] = $target;
            $body = '';
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_ENCODING => '',
                CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                CURLOPT_RESOLVE => ["{$host}:{$port}:" . (str_contains($ip, ':') ? "[{$ip}]" : $ip)],
                CURLOPT_WRITEFUNCTION => function ($ch, $chunk) use (&$body) {
                    if (strlen($body) + strlen($chunk) > self::MAX_BYTES) {
                        return 0; // interrompe: pagina troppo grande
                    }
                    $body .= $chunk;
                    return strlen($chunk);
                },
            ]);
            curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $next = (string) curl_getinfo($ch, CURLINFO_REDIRECT_URL);
            $errno = curl_errno($ch);
            $err = curl_error($ch);
            curl_close($ch);
            if ($errno && !($errno === CURLE_WRITE_ERROR && $body !== '')) {
                return ['error' => true, 'message' => 'Errore fetch: ' . $err, 'curl_errno' => $errno];
            }
            if ($code >= 300 && $code < 400 && $next !== '') {
                $url = $next;
                continue;
            }
            return ['success' => true, 'body' => $body, 'http_code' => $code, 'final_url' => $url];
        }
        return ['error' => true, 'message' => 'Troppi redirect'];
    }

    /** [host, porta, ip pubblico] oppure null se l'URL non è http/https o l'host risolve anche a un IP interno. */
    public static function resolveTarget(string $url): ?array
    {
        $p = parse_url($url);
        $scheme = strtolower($p['scheme'] ?? '');
        if (!in_array($scheme, ['http', 'https'], true) || empty($p['host']) || isset($p['user'])) {
            return null;
        }
        $port = (int) ($p['port'] ?? ($scheme === 'https' ? 443 : 80));
        if (!in_array($port, [80, 443], true)) {
            return null;
        }
        $host = strtolower(trim($p['host'], '[]'));
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return PublicAiCheckService::isPublicIp($host) ? [$host, $port, $host] : null;
        }
        $ips = gethostbynamel($host) ?: [];
        foreach (@dns_get_record($host, DNS_AAAA) ?: [] as $rec) {
            if (!empty($rec['ipv6'])) {
                $ips[] = $rec['ipv6'];
            }
        }
        if (!$ips) {
            return null;
        }
        foreach ($ips as $ip) {
            if (!PublicAiCheckService::isPublicIp($ip)) {
                return null; // basta un IP interno per rifiutare (difesa da record misti)
            }
        }
        $v4 = array_values(array_filter($ips, fn($i) => filter_var($i, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)));
        return [$host, $port, $v4[0] ?? $ips[0]];
    }
}
