<?php

namespace Modules\KeywordResearch\Controllers;

use Core\View;
use Core\Auth;
use Core\ModuleLoader;
use Modules\KeywordResearch\Services\KeywordInsightService;

class QuickCheckController
{
    public function index(): string
    {
        $user = Auth::user();

        return View::render('keyword-research::quick-check/index', [
            'title' => 'Quick Check - Keyword Research',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'keyword' => '',
            'location' => 'IT',
            'results' => null,
        ]);
    }

    public function search(): string
    {
        $user = Auth::user();
        $keyword = trim($_POST['keyword'] ?? '');
        $location = trim($_POST['location'] ?? 'IT');

        if (empty($keyword)) {
            $_SESSION['_flash']['error'] = 'Inserisci una keyword da cercare.';
            return View::render('keyword-research::quick-check/index', [
                'title' => 'Quick Check - Keyword Research',
                'user' => $user,
                'modules' => ModuleLoader::getUserModules($user['id']),
                'keyword' => '',
                'location' => $location,
                'results' => null,
            ]);
        }

        $langMap = [
            'IT' => 'it', 'US' => 'en', 'GB' => 'en',
            'DE' => 'de', 'FR' => 'fr', 'ES' => 'es',
        ];
        $lang = $langMap[$location] ?? 'it';

        $service = new KeywordInsightService();

        if (!$service->isConfigured()) {
            $_SESSION['_flash']['error'] = 'API RapidAPI non configurata. Contatta l\'amministratore.';
            return View::render('keyword-research::quick-check/index', [
                'title' => 'Quick Check - Keyword Research',
                'user' => $user,
                'modules' => ModuleLoader::getUserModules($user['id']),
                'keyword' => $keyword,
                'location' => $location,
                'results' => null,
            ]);
        }

        $result = $service->keySuggest($keyword, $location, $lang);

        $mainKeyword = null;
        $related = [];

        if ($result['success'] && !empty($result['data'])) {
            // Cerca la keyword esatta o la più vicina
            foreach ($result['data'] as $item) {
                if (strtolower($item['text'] ?? '') === strtolower($keyword)) {
                    $mainKeyword = $item;
                    break;
                }
            }

            // Se non trovata esatta, prendi la prima
            if (!$mainKeyword && !empty($result['data'][0])) {
                $mainKeyword = $result['data'][0];
            }

            // Le correlate sono tutte tranne la main
            foreach ($result['data'] as $item) {
                if ($mainKeyword && ($item['text'] ?? '') === ($mainKeyword['text'] ?? '')) continue;
                $related[] = $item;
            }

            // Ordina correlate per volume
            usort($related, fn($a, $b) => ($b['volume'] ?? 0) - ($a['volume'] ?? 0));
        }

        return View::render('keyword-research::quick-check/index', [
            'title' => 'Quick Check - ' . $keyword,
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'keyword' => $keyword,
            'location' => $location,
            'results' => $result['success'] ? [
                'main' => $mainKeyword,
                'related' => $related,
                'total_found' => count($result['data']),
            ] : null,
            'error' => !$result['success'] ? $result['error'] : null,
        ]);
    }
}
