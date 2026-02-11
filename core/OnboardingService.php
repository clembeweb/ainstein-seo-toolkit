<?php

namespace Core;

class OnboardingService
{
    /**
     * Verifica se il welcome globale è stato completato
     */
    public static function isWelcomeCompleted(int $userId): bool
    {
        $row = Database::fetch(
            "SELECT welcome_completed FROM user_onboarding_global WHERE user_id = ?",
            [$userId]
        );
        return $row && (bool) $row['welcome_completed'];
    }

    /**
     * Segna il welcome globale come completato
     */
    public static function completeWelcome(int $userId): void
    {
        $existing = Database::fetch(
            "SELECT id FROM user_onboarding_global WHERE user_id = ?",
            [$userId]
        );

        if ($existing) {
            Database::execute(
                "UPDATE user_onboarding_global SET welcome_completed = 1, welcome_completed_at = NOW() WHERE user_id = ?",
                [$userId]
            );
        } else {
            Database::insert('user_onboarding_global', [
                'user_id' => $userId,
                'welcome_completed' => 1,
                'welcome_completed_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * Verifica se il tour di un modulo è stato completato
     */
    public static function isModuleCompleted(int $userId, string $moduleSlug): bool
    {
        $row = Database::fetch(
            "SELECT completed FROM user_onboarding WHERE user_id = ? AND module_slug = ?",
            [$userId, $moduleSlug]
        );
        return $row && (bool) $row['completed'];
    }

    /**
     * Segna il tour di un modulo come completato
     */
    public static function completeModule(int $userId, string $moduleSlug): void
    {
        $existing = Database::fetch(
            "SELECT id FROM user_onboarding WHERE user_id = ? AND module_slug = ?",
            [$userId, $moduleSlug]
        );

        if ($existing) {
            Database::execute(
                "UPDATE user_onboarding SET completed = 1, completed_at = NOW() WHERE user_id = ? AND module_slug = ?",
                [$userId, $moduleSlug]
            );
        } else {
            Database::insert('user_onboarding', [
                'user_id' => $userId,
                'module_slug' => $moduleSlug,
                'completed' => 1,
                'completed_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * Ritorna array di module_slug completati dall'utente
     */
    public static function getCompletedModules(int $userId): array
    {
        $rows = Database::fetchAll(
            "SELECT module_slug FROM user_onboarding WHERE user_id = ? AND completed = 1",
            [$userId]
        );
        return array_column($rows, 'module_slug');
    }

    /**
     * Resetta il tour di un modulo (per ri-vederlo)
     */
    public static function resetModule(int $userId, string $moduleSlug): void
    {
        Database::execute(
            "UPDATE user_onboarding SET completed = 0, completed_at = NULL WHERE user_id = ? AND module_slug = ?",
            [$userId, $moduleSlug]
        );
    }

    /**
     * Resetta tutti i tour (welcome + moduli)
     */
    public static function resetAll(int $userId): void
    {
        Database::execute(
            "UPDATE user_onboarding SET completed = 0, completed_at = NULL WHERE user_id = ?",
            [$userId]
        );
        Database::execute(
            "UPDATE user_onboarding_global SET welcome_completed = 0, welcome_completed_at = NULL WHERE user_id = ?",
            [$userId]
        );
    }
}
