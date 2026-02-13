<?php

namespace Modules\SeoAudit\Controllers;

use Core\View;
use Core\Auth;
use Core\Credits;
use Core\ModuleLoader;
use Modules\SeoAudit\Models\Project;
use Modules\SeoAudit\Models\Page;
use Modules\SeoAudit\Models\Issue;

/**
 * ProjectController
 *
 * Gestisce operazioni CRUD per i progetti SEO Audit
 */
class ProjectController
{
    private Project $project;
    private Page $page;
    private Issue $issue;

    public function __construct()
    {
        $this->project = new Project();
        $this->page = new Page();
        $this->issue = new Issue();
    }

    /**
     * Lista progetti utente
     */
    public function index(): string
    {
        $user = Auth::user();
        $projects = $this->project->allWithStats($user['id']);

        // Calcola costi crediti per nuovo audit
        $crawlCost = Credits::getCost('crawl_per_page') ?? 0.2;
        $creditBalance = Credits::getBalance($user['id']);

        return View::render('seo-audit/projects/index', [
            'title' => 'SEO Audit - Progetti',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'projects' => $projects,
            'credits' => [
                'balance' => $creditBalance,
                'crawl_cost' => $crawlCost,
            ],
        ]);
    }

    /**
     * Form nuovo progetto
     */
    public function create(): string
    {
        $user = Auth::user();
        $creditBalance = Credits::getBalance($user['id']);
        $crawlCost = Credits::getCost('crawl_per_page') ?? 0.2;

        return View::render('seo-audit/projects/create', [
            'title' => 'Nuovo Audit SEO',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'credits' => [
                'balance' => $creditBalance,
                'crawl_cost' => $crawlCost,
            ],
        ]);
    }

    /**
     * Salva nuovo progetto
     */
    public function store(): void
    {
        $user = Auth::user();

        // Input
        $name = trim($_POST['name'] ?? '');
        $baseUrl = trim($_POST['base_url'] ?? '');
        $crawlMode = $_POST['crawl_mode'] ?? 'both';
        $maxPages = (int) ($_POST['max_pages'] ?? 500);

        // Validazione
        $errors = [];

        if (empty($name)) {
            $errors[] = 'Il nome del progetto è obbligatorio';
        } elseif (strlen($name) > 255) {
            $errors[] = 'Il nome non può superare 255 caratteri';
        }

        if (empty($baseUrl)) {
            $errors[] = 'L\'URL del sito è obbligatorio';
        } else {
            // Normalizza URL
            $baseUrl = Project::normalizeBaseUrl($baseUrl);
            if (!filter_var($baseUrl, FILTER_VALIDATE_URL)) {
                $errors[] = 'Formato URL non valido';
            }
        }

        if (!in_array($crawlMode, ['sitemap', 'spider', 'both'])) {
            $errors[] = 'Modalità di scansione non valida';
        }

        if ($maxPages < 10 || $maxPages > 5000) {
            $errors[] = 'Il limite pagine deve essere tra 10 e 5000';
        }

        // Verifica crediti minimi (stima 50 pagine)
        $crawlCost = Credits::getCost('crawl_per_page') ?? 0.2;
        $estimatedCost = 50 * $crawlCost;
        if (!Credits::hasEnough($user['id'], $estimatedCost)) {
            $errors[] = 'Crediti insufficienti per avviare un audit (minimo ' . $estimatedCost . ' crediti)';
        }

        if (!empty($errors)) {
            $_SESSION['_flash']['error'] = implode('. ', $errors);
            header('Location: ' . url('/seo-audit/create'));
            exit;
        }

        try {
            $projectId = $this->project->createWithConfig([
                'user_id' => $user['id'],
                'name' => $name,
                'base_url' => $baseUrl,
                'crawl_mode' => $crawlMode,
                'max_pages' => $maxPages,
                'status' => 'pending',
            ]);

            $_SESSION['_flash']['success'] = 'Progetto creato! Avvia la scansione per iniziare l\'audit.';
            header('Location: ' . url('/seo-audit/project/' . $projectId . '/dashboard'));
            exit;

        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore nella creazione: ' . $e->getMessage();
            header('Location: ' . url('/seo-audit/create'));
            exit;
        }
    }

    /**
     * Dettaglio progetto - redirect a dashboard
     */
    public function show(int $id): void
    {
        header('Location: ' . url('/seo-audit/project/' . $id . '/dashboard'));
        exit;
    }

    /**
     * Impostazioni progetto
     */
    public function settings(int $id): string
    {
        $user = Auth::user();
        $project = $this->project->findWithStats($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            header('Location: ' . url('/seo-audit'));
            exit;
        }

        $creditBalance = Credits::getBalance($user['id']);

        return View::render('seo-audit/projects/settings', [
            'title' => $project['name'] . ' - Impostazioni',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'currentPage' => 'settings',
            'credits' => [
                'balance' => $creditBalance,
            ],
        ]);
    }

    /**
     * Aggiorna impostazioni progetto
     */
    public function updateSettings(int $id): void
    {
        $user = Auth::user();
        $project = $this->project->find($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            header('Location: ' . url('/seo-audit'));
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $maxPages = (int) ($_POST['max_pages'] ?? 500);
        $crawlMode = $_POST['crawl_mode'] ?? $project['crawl_mode'];

        $errors = [];

        if (empty($name)) {
            $errors[] = 'Il nome è obbligatorio';
        }

        if ($maxPages < 10 || $maxPages > 5000) {
            $errors[] = 'Il limite pagine deve essere tra 10 e 5000';
        }

        if (!empty($errors)) {
            $_SESSION['_flash']['error'] = implode('. ', $errors);
            header('Location: ' . url('/seo-audit/project/' . $id . '/settings'));
            exit;
        }

        try {
            $this->project->update($id, [
                'name' => $name,
                'max_pages' => $maxPages,
                'crawl_mode' => $crawlMode,
            ], $user['id']);

            $this->project->logActivity($id, $user['id'], 'settings_updated');
            $_SESSION['_flash']['success'] = 'Impostazioni aggiornate';

        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore: ' . $e->getMessage();
        }

        header('Location: ' . url('/seo-audit/project/' . $id . '/settings'));
        exit;
    }

    /**
     * Elimina progetto (cascade)
     */
    public function destroy(int $id): void
    {
        $user = Auth::user();
        $project = $this->project->find($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            header('Location: ' . url('/seo-audit'));
            exit;
        }

        try {
            // Delete cascade handled by FK
            $this->project->delete($id, $user['id']);
            $_SESSION['_flash']['success'] = 'Progetto eliminato';

        } catch (\Exception $e) {
            $_SESSION['_flash']['error'] = 'Errore: ' . $e->getMessage();
        }

        header('Location: ' . url('/seo-audit'));
        exit;
    }

    /**
     * Pagina import URL
     */
    public function import(int $id): string
    {
        $user = Auth::user();
        $project = $this->project->findWithStats($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            header('Location: ' . url('/seo-audit'));
            exit;
        }

        return View::render('seo-audit/urls/import', [
            'title' => $project['name'] . ' - Importa URL',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'currentPage' => 'import',
        ]);
    }
}
