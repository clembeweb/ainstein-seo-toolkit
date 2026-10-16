<?php

namespace Modules\SeoAudit\Services;

/**
 * Riconosce le versioni in lingua della stessa pagina (/servizi/ e /en/servizi/).
 *
 * Su un sito multilingua il titolo uguale tra /x/ e /en/x/ non è un duplicato da togliere con canonical o 301
 * (toglierebbe la lingua da Google): è una traduzione mancante. Test manuali 2026-10-16, Madcrumbs.
 */
class LanguageVariants
{
    /** Primo segmento di percorso che indica una lingua: en, de, fr, en-us, pt-br... */
    private const LANG_SEGMENT = '#^/([a-z]{2}(?:[-_][a-z]{2})?)(/|$)#i';

    /**
     * Lingua dichiarata nel percorso ('en', 'pt-br'...) o null se l'URL non ha un prefisso di lingua.
     */
    public static function language(string $url): ?string
    {
        $path = (string) (parse_url($url, PHP_URL_PATH) ?? '/');
        return preg_match(self::LANG_SEGMENT, $path, $m) ? strtolower($m[1]) : null;
    }

    /**
     * Chiave della pagina senza prefisso di lingua: host + percorso.
     */
    public static function key(string $url): string
    {
        $host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?? ''));
        $host = preg_replace('/^www\./', '', $host);
        $path = (string) (parse_url($url, PHP_URL_PATH) ?? '/');
        $path = preg_replace(self::LANG_SEGMENT, '/', $path);
        return $host . '/' . trim($path, '/');
    }

    /**
     * true se tutti gli URL sono la stessa pagina in lingue diverse (almeno 2, nessuna lingua ripetuta).
     */
    public static function areVariants(array $urls): bool
    {
        $urls = array_values(array_unique($urls));
        if (count($urls) < 2) {
            return false;
        }
        $keys = array_unique(array_map([self::class, 'key'], $urls));
        if (count($keys) !== 1) {
            return false;
        }
        $langs = array_map(fn($u) => self::language($u) ?? '_default', $urls);
        return count(array_unique($langs)) === count($langs);
    }
}
