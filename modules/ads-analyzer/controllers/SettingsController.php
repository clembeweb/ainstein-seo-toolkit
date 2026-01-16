<?php

namespace Modules\AdsAnalyzer\Controllers;

use Core\View;
use Core\Auth;
use Core\Settings;
use Core\ModuleLoader;
use Modules\AdsAnalyzer\Models\BusinessContext;
use Modules\AdsAnalyzer\Services\ValidationService;

class SettingsController
{
    public function index(): string
    {
        $user = Auth::user();

        // Carica settings utente
        $settings = [
            'max_terms_per_analysis' => Settings::getUser($user['id'], 'ads_analyzer_max_terms', 300),
            'auto_select_high_priority' => Settings::getUser($user['id'], 'ads_analyzer_auto_high', true),
            'auto_select_medium_priority' => Settings::getUser($user['id'], 'ads_analyzer_auto_medium', true),
            'default_match_type' => Settings::getUser($user['id'], 'ads_analyzer_match_type', 'phrase')
        ];

        // Carica contesti salvati
        $savedContexts = BusinessContext::getByUser($user['id']);

        return View::render('ads-analyzer/settings/index', [
            'title' => 'Impostazioni - Google Ads Analyzer',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'settings' => $settings,
            'savedContexts' => $savedContexts
        ]);
    }

    public function update(): void
    {
        $user = Auth::user();

        // Salva settings utente
        Settings::setUser($user['id'], 'ads_analyzer_max_terms', (int)($_POST['max_terms_per_analysis'] ?? 300));
        Settings::setUser($user['id'], 'ads_analyzer_auto_high', isset($_POST['auto_select_high_priority']));
        Settings::setUser($user['id'], 'ads_analyzer_auto_medium', isset($_POST['auto_select_medium_priority']));
        Settings::setUser($user['id'], 'ads_analyzer_match_type', $_POST['default_match_type'] ?? 'phrase');

        $_SESSION['flash_success'] = 'Impostazioni salvate';
        header('Location: ' . url('/ads-analyzer/settings'));
        exit;
    }

    /**
     * Salva contesto business
     */
    public function saveContext(): void
    {
        $user = Auth::user();

        $data = [
            'name' => trim($_POST['context_name'] ?? ''),
            'context' => trim($_POST['context'] ?? ''),
            'user_id' => $user['id']
        ];

        $errors = ValidationService::validateSavedContext($data);

        if (!empty($errors)) {
            jsonResponse(['error' => implode(', ', $errors)], 400);
        }

        $contextId = BusinessContext::create($data);

        jsonResponse([
            'success' => true,
            'id' => $contextId,
            'name' => $data['name']
        ]);
    }

    /**
     * Elimina contesto salvato
     */
    public function deleteContext(int $id): void
    {
        $user = Auth::user();

        if (!BusinessContext::deleteByUser($user['id'], $id)) {
            jsonResponse(['error' => 'Contesto non trovato'], 404);
        }

        jsonResponse(['success' => true]);
    }
}
