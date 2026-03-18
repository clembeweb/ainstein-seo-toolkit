<?php

namespace Modules\AdsAnalyzer\Models;

use Core\Database;

class ProductPerformance
{
    /**
     * Salva batch di dati performance prodotto da sync.
     * Usa DELETE + INSERT per essere re-runnable.
     */
    public static function saveBatch(int $projectId, int $syncId, array $products): void
    {
        // Elimina dati precedenti per questo sync (re-runnable)
        Database::execute(
            "DELETE FROM ga_product_performance WHERE project_id = ? AND sync_id = ?",
            [$projectId, $syncId]
        );

        foreach ($products as $p) {
            Database::insert('ga_product_performance', [
                'project_id' => $projectId,
                'sync_id' => $syncId,
                'campaign_id_google' => $p['campaign_id_google'] ?? null,
                'campaign_name' => $p['campaign_name'] ?? null,
                'product_item_id' => $p['product_item_id'] ?? null,
                'product_title' => $p['product_title'] ?? null,
                'product_brand' => $p['product_brand'] ?? null,
                'product_category_l1' => $p['product_category_l1'] ?? null,
                'product_type_l1' => $p['product_type_l1'] ?? null,
                'clicks' => $p['clicks'] ?? 0,
                'impressions' => $p['impressions'] ?? 0,
                'cost' => $p['cost'] ?? 0,
                'conversions' => $p['conversions'] ?? 0,
                'conversion_value' => $p['conversion_value'] ?? 0,
                'ctr' => $p['ctr'] ?? 0,
                'avg_cpc' => $p['avg_cpc'] ?? 0,
                'roas' => $p['roas'] ?? 0,
                'cpa' => $p['cpa'] ?? 0,
            ]);
        }
    }

    /**
     * Top prodotti per spesa di un sync
     */
    public static function getTopBySpend(int $projectId, int $syncId, int $limit = 20): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_product_performance
             WHERE project_id = ? AND sync_id = ? AND clicks > 0
             ORDER BY cost DESC LIMIT ?",
            [$projectId, $syncId, $limit]
        );
    }

    /**
     * Riepilogo performance per brand (aggregato)
     */
    public static function getBrandSummary(int $projectId, int $syncId): array
    {
        return Database::fetchAll(
            "SELECT product_brand,
                    COUNT(*) as product_count,
                    SUM(clicks) as total_clicks,
                    SUM(cost) as total_cost,
                    SUM(conversions) as total_conversions,
                    SUM(conversion_value) as total_conversion_value,
                    CASE WHEN SUM(cost) > 0 THEN ROUND(SUM(conversion_value) / SUM(cost), 1) ELSE 0 END as brand_roas,
                    CASE WHEN SUM(conversions) > 0 THEN ROUND(SUM(cost) / SUM(conversions), 2) ELSE 0 END as brand_cpa
             FROM ga_product_performance
             WHERE project_id = ? AND sync_id = ? AND product_brand IS NOT NULL AND product_brand != ''
             GROUP BY product_brand
             ORDER BY total_cost DESC",
            [$projectId, $syncId]
        );
    }

    /**
     * Riepilogo performance per categoria
     */
    public static function getCategorySummary(int $projectId, int $syncId): array
    {
        return Database::fetchAll(
            "SELECT product_category_l1,
                    COUNT(*) as product_count,
                    SUM(clicks) as total_clicks,
                    SUM(cost) as total_cost,
                    SUM(conversions) as total_conversions,
                    SUM(conversion_value) as total_conversion_value,
                    CASE WHEN SUM(cost) > 0 THEN ROUND(SUM(conversion_value) / SUM(cost), 1) ELSE 0 END as cat_roas,
                    CASE WHEN SUM(conversions) > 0 THEN ROUND(SUM(cost) / SUM(conversions), 2) ELSE 0 END as cat_cpa
             FROM ga_product_performance
             WHERE project_id = ? AND sync_id = ? AND product_category_l1 IS NOT NULL AND product_category_l1 != ''
             GROUP BY product_category_l1
             ORDER BY total_cost DESC",
            [$projectId, $syncId]
        );
    }

    /**
     * Prodotti con spreco: alta spesa, zero conversioni
     */
    public static function getWasteProducts(int $projectId, int $syncId, int $limit = 10): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_product_performance
             WHERE project_id = ? AND sync_id = ? AND conversions = 0 AND cost > 0
             ORDER BY cost DESC LIMIT ?",
            [$projectId, $syncId, $limit]
        );
    }

    /**
     * Elimina dati prodotto per un sync (cleanup)
     */
    public static function deleteBySyncId(int $syncId): void
    {
        Database::execute("DELETE FROM ga_product_performance WHERE sync_id = ?", [$syncId]);
    }
}
