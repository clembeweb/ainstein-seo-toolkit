<?php

namespace Modules\CrawlBudget\Models;

use Core\Database;

/**
 * SiteConfig Model
 *
 * Gestisce la tabella cb_site_config (robots.txt, sitemap)
 */
class SiteConfig
{
    private const TABLE = 'cb_site_config';

    /**
     * Trova configurazione per progetto
     */
    public function findByProject(int $projectId): ?array
    {
        return Database::fetch(
            "SELECT * FROM " . self::TABLE . " WHERE project_id = ?",
            [$projectId]
        );
    }

    /**
     * Inserisci o aggiorna configurazione
     */
    public function upsert(int $projectId, array $data): void
    {
        // Encode JSON fields
        foreach (['robots_rules', 'sitemaps', 'sitemap_urls'] as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = json_encode($data[$field]);
            }
        }

        $data['updated_at'] = date('Y-m-d H:i:s');

        $existing = $this->findByProject($projectId);
        if ($existing) {
            Database::update(self::TABLE, $data, 'project_id = ?', [$projectId]);
        } else {
            $data['project_id'] = $projectId;
            Database::insert(self::TABLE, $data);
        }
    }

    /**
     * Ottieni URL dalla sitemap (decoded)
     */
    public function getSitemapUrls(int $projectId): array
    {
        $config = $this->findByProject($projectId);
        if (!$config || empty($config['sitemap_urls'])) {
            return [];
        }

        $urls = json_decode($config['sitemap_urls'], true);
        return is_array($urls) ? $urls : [];
    }

    /**
     * Ottieni regole robots.txt (decoded)
     */
    public function getRobotsRules(int $projectId): array
    {
        $config = $this->findByProject($projectId);
        if (!$config || empty($config['robots_rules'])) {
            return [];
        }

        $rules = json_decode($config['robots_rules'], true);
        return is_array($rules) ? $rules : [];
    }
}
