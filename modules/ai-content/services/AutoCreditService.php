<?php

namespace Modules\AiContent\Services;

use Core\Credits;
use Core\Database;

/**
 * Crediti degli Articoli Automatici (cron e "Processa Ora").
 *
 * Stessi importi e stesse voci del wizard manuale (WizardController):
 * source_scraping (content_scrape x fonti), brief_generation, article_generation,
 * cover_image_generation. I servizi AI sono chiamati con charge_credits=false,
 * quindi l'addebito spetta a chi orchestra: qui.
 */
class AutoCreditService
{
    /**
     * Utente a cui addebitare: il proprietario del progetto globale se c'è,
     * altrimenti il proprietario del progetto AI Content.
     */
    public static function creditUserId(int $aicProjectId, int $fallbackUserId): int
    {
        $row = Database::fetch(
            "SELECT p.user_id, gp.user_id AS owner_id
             FROM aic_projects p
             LEFT JOIN projects gp ON gp.id = p.global_project_id
             WHERE p.id = ?",
            [$aicProjectId]
        );

        if ($row && !empty($row['owner_id'])) {
            return (int) $row['owner_id'];
        }
        if ($row && !empty($row['user_id'])) {
            return (int) $row['user_id'];
        }
        return $fallbackUserId;
    }

    /**
     * Costo stimato di un articolo, copertina esclusa (la copertina si fa solo se restano crediti).
     */
    public static function estimate(int $sourcesCount): float
    {
        return Credits::getCost('content_scrape', 'ai-content') * max(1, $sourcesCount)
            + Credits::getCost('brief_generation', 'ai-content')
            + Credits::getCost('article_generation', 'ai-content');
    }

    public static function chargeScraping(int $userId, int $scrapedCount, string $keyword): void
    {
        $cost = Credits::getCost('content_scrape', 'ai-content') * $scrapedCount;
        if ($cost > 0) {
            Credits::consume($userId, $cost, 'source_scraping', 'ai-content', ['urls_count' => $scrapedCount, 'keyword' => $keyword, 'auto' => true]);
        }
    }

    public static function chargeBrief(int $userId, string $keyword): void
    {
        $cost = Credits::getCost('brief_generation', 'ai-content');
        if ($cost > 0) {
            Credits::consume($userId, $cost, 'brief_generation', 'ai-content', ['keyword' => $keyword, 'auto' => true]);
        }
    }

    public static function chargeArticle(int $userId, string $keyword, int $articleId): void
    {
        $cost = Credits::getCost('article_generation', 'ai-content');
        if ($cost > 0) {
            Credits::consume($userId, $cost, 'article_generation', 'ai-content', ['keyword' => $keyword, 'article_id' => $articleId, 'auto' => true]);
        }
    }

    public static function coverCost(): float
    {
        return Credits::getCost('cover_image_generation', 'ai-content');
    }

    public static function chargeCover(int $userId, string $keyword, int $articleId): void
    {
        $cost = self::coverCost();
        if ($cost > 0) {
            Credits::consume($userId, $cost, 'cover_image_generation', 'ai-content', ['keyword' => $keyword, 'article_id' => $articleId, 'auto' => true]);
        }
    }

    public static function insufficientMessage(int $userId, float $needed): string
    {
        $balance = Credits::getBalance($userId);
        return 'Crediti insufficienti: servono circa ' . rtrim(rtrim(number_format($needed, 1, ',', ''), '0'), ',')
            . ', disponibili ' . rtrim(rtrim(number_format($balance, 1, ',', ''), '0'), ',')
            . '. Ricarica i crediti e usa "Riprova".';
    }
}
