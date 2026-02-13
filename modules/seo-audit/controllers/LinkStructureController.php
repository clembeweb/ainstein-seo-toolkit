<?php

namespace Modules\SeoAudit\Controllers;

use Core\View;
use Core\Auth;
use Core\ModuleLoader;
use Modules\SeoAudit\Models\Project;
use Modules\SeoAudit\Services\LinkStructureService;

/**
 * LinkStructureController
 *
 * Gestisce la sezione "Struttura Link" nel SEO Audit
 * Analizza link interni usando dati già disponibili dal crawl
 */
class LinkStructureController
{
    private Project $projectModel;
    private LinkStructureService $linkService;

    public function __construct()
    {
        $this->projectModel = new Project();
        $this->linkService = new LinkStructureService();
    }

    /**
     * Dashboard struttura link
     */
    public function overview(int $id): string
    {
        $user = Auth::user();
        $project = $this->projectModel->findWithStats($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            header('Location: ' . url('/seo-audit'));
            exit;
        }

        $stats = $this->linkService->analyzeStructure($id);

        return View::render('seo-audit/links/overview', [
            'title' => $project['name'] . ' - Struttura Link',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'currentPage' => 'links',
            'stats' => $stats,
        ]);
    }

    /**
     * Pagine orfane
     */
    public function orphans(int $id): string
    {
        $user = Auth::user();
        $project = $this->projectModel->findWithStats($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            header('Location: ' . url('/seo-audit'));
            exit;
        }

        $orphans = $this->linkService->getOrphanPages($id);

        return View::render('seo-audit/links/orphans', [
            'title' => $project['name'] . ' - Pagine Orfane',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'currentPage' => 'links-orphans',
            'orphans' => $orphans,
        ]);
    }

    /**
     * Report anchor text
     */
    public function anchors(int $id): string
    {
        $user = Auth::user();
        $project = $this->projectModel->findWithStats($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            header('Location: ' . url('/seo-audit'));
            exit;
        }

        $anchorData = $this->linkService->getAnchorAnalysis($id);

        return View::render('seo-audit/links/anchors', [
            'title' => $project['name'] . ' - Analisi Anchor Text',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'currentPage' => 'links-anchors',
            'anchorData' => $anchorData,
        ]);
    }

    /**
     * Grafo interattivo
     */
    public function graph(int $id): string
    {
        $user = Auth::user();
        $project = $this->projectModel->findWithStats($id, $user['id']);

        if (!$project) {
            $_SESSION['_flash']['error'] = 'Progetto non trovato';
            header('Location: ' . url('/seo-audit'));
            exit;
        }

        $graphData = $this->linkService->getLinkGraph($id, 50);

        return View::render('seo-audit/links/graph', [
            'title' => $project['name'] . ' - Grafo Link',
            'user' => $user,
            'modules' => ModuleLoader::getUserModules($user['id']),
            'project' => $project,
            'currentPage' => 'links-graph',
            'graphData' => $graphData,
        ]);
    }
}
