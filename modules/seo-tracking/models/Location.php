<?php

namespace Modules\SeoTracking\Models;

use Core\Database;

/**
 * Location Model
 *
 * Gestisce le locations per SERP check e volumi di ricerca.
 * Fornisce codici specifici per ogni provider (Serper, SerpAPI, DataForSEO).
 */
class Location
{
    private string $table = 'st_locations';

    /**
     * Tutte le locations attive
     */
    public function all(): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY sort_order, name";
        return Database::fetchAll($sql);
    }

    /**
     * Trova per ID
     */
    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        return Database::fetch($sql, [$id]) ?: null;
    }

    /**
     * Trova per country code (es: IT, US, DE)
     */
    public function findByCountryCode(string $code): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE country_code = ? AND is_active = 1";
        return Database::fetch($sql, [strtoupper($code)]) ?: null;
    }

    /**
     * Location di default (Italia)
     */
    public function getDefault(): array
    {
        $default = $this->findByCountryCode('IT');
        if ($default) {
            return $default;
        }
        // Fallback al primo disponibile
        $all = $this->all();
        return $all[0] ?? $this->getFallbackLocation();
    }

    /**
     * Location fallback se DB vuoto
     */
    private function getFallbackLocation(): array
    {
        return [
            'id' => 0,
            'name' => 'Italia',
            'country_code' => 'IT',
            'language_code' => 'it',
            'dataforseo_location_code' => 2380,
            'dataforseo_language_code' => 'it',
            'serper_gl' => 'it',
            'serper_hl' => 'it',
            'serpapi_location' => 'Italy',
            'serpapi_google_domain' => 'google.it',
        ];
    }

    /**
     * Ottieni parametri per Serper.dev
     */
    public function getSerperParams(string $countryCode): array
    {
        $location = $this->findByCountryCode($countryCode) ?? $this->getDefault();
        return [
            'gl' => $location['serper_gl'],
            'hl' => $location['serper_hl'],
        ];
    }

    /**
     * Ottieni parametri per SERP API
     */
    public function getSerpApiParams(string $countryCode): array
    {
        $location = $this->findByCountryCode($countryCode) ?? $this->getDefault();
        return [
            'location' => $location['serpapi_location'],
            'google_domain' => $location['serpapi_google_domain'],
            'gl' => strtolower($location['country_code']),
            'hl' => $location['language_code'],
        ];
    }

    /**
     * Ottieni parametri per DataForSEO
     */
    public function getDataForSeoParams(string $countryCode): array
    {
        $location = $this->findByCountryCode($countryCode) ?? $this->getDefault();
        return [
            'location_code' => (int) $location['dataforseo_location_code'],
            'language_code' => $location['dataforseo_language_code'],
        ];
    }

    /**
     * Lista semplice per dropdown (id => name)
     */
    public function getDropdownList(): array
    {
        $locations = $this->all();
        $list = [];
        foreach ($locations as $loc) {
            $list[$loc['country_code']] = $loc['name'] . ' (' . $loc['country_code'] . ')';
        }
        return $list;
    }

    /**
     * Conta locations attive
     */
    public function count(): int
    {
        return Database::count($this->table, 'is_active = 1');
    }
}
