<?php

namespace Modules\SeoTracking\Helpers;

/**
 * UrlHelper - Utility per normalizzazione URL
 * Permette match tra URL GSC (completi) e GA4 (solo path)
 */
class UrlHelper
{
    /**
     * Normalizza URL per match GSC ↔ GA4
     * Rimuove dominio, trailing slash, query string
     *
     * @param string $url URL completo o path
     * @return string Path normalizzato (es: /pagina)
     */
    public static function normalize(?string $url): string
    {
        // Se null o vuoto, ritorna /
        if ($url === null || $url === '') {
            return '/';
        }

        // Parse URL
        $parsed = parse_url($url);

        // Prendi solo il path
        $path = $parsed['path'] ?? '/';

        // Rimuovi trailing slash (tranne root)
        if ($path !== '/' && substr($path, -1) === '/') {
            $path = rtrim($path, '/');
        }

        // Se path vuoto dopo parsing, usa /
        if (empty($path)) {
            $path = '/';
        }

        return $path;
    }

    /**
     * Estrae dominio da URL
     *
     * @param string $url
     * @return string|null
     */
    public static function extractDomain(string $url): ?string
    {
        $parsed = parse_url($url);
        return $parsed['host'] ?? null;
    }

    /**
     * Confronta due URL (normalizzati)
     *
     * @param string $url1
     * @param string $url2
     * @return bool
     */
    public static function match(string $url1, string $url2): bool
    {
        return self::normalize($url1) === self::normalize($url2);
    }

    /**
     * Normalizza array di URL (per batch processing)
     *
     * @param array $urls
     * @return array Array con chiave = URL originale, valore = normalizzato
     */
    public static function normalizeMany(array $urls): array
    {
        $result = [];
        foreach ($urls as $url) {
            $result[$url] = self::normalize($url);
        }
        return $result;
    }

    /**
     * Crea lookup table per match veloce
     * Utile per JOIN in memoria tra GSC e GA4 data
     *
     * @param array $rows Array di righe con campo URL
     * @param string $urlField Nome del campo URL
     * @return array Lookup table: normalized_path => [rows...]
     */
    public static function createLookupTable(array $rows, string $urlField): array
    {
        $lookup = [];
        foreach ($rows as $row) {
            $url = $row[$urlField] ?? '';
            $normalized = self::normalize($url);

            if (!isset($lookup[$normalized])) {
                $lookup[$normalized] = [];
            }
            $lookup[$normalized][] = $row;
        }
        return $lookup;
    }
}
