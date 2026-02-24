<?php

namespace Controllers;

use Core\View;
use Core\Auth;
use Core\Middleware;
use Core\ModuleLoader;

/**
 * NotificationController
 * Gestisce endpoint notifiche: conteggio non lette, lista recenti, pagina completa, segna come lette.
 */
class NotificationController
{
    /**
     * Conteggio notifiche non lette (JSON)
     * GET /notifications/unread-count
     */
    public function unreadCount(): string
    {
        Middleware::auth();

        $count = \Services\NotificationService::getUnreadCount(Auth::id());

        header('Content-Type: application/json');
        echo json_encode(['count' => $count]);
        exit;
    }

    /**
     * Notifiche recenti per dropdown (JSON)
     * GET /notifications/recent
     */
    public function recent(): string
    {
        Middleware::auth();

        $notifications = \Services\NotificationService::getRecent(Auth::id());

        header('Content-Type: application/json');
        echo json_encode($notifications);
        exit;
    }

    /**
     * Pagina tutte le notifiche (paginata)
     * GET /notifications
     */
    public function index(): string
    {
        Middleware::auth();

        $user = Auth::user();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $filter = $_GET['filter'] ?? null;

        // Accetta solo 'unread' come filtro valido
        if ($filter && $filter !== 'unread') {
            $filter = null;
        }

        $result = \Services\NotificationService::getAll($user['id'], $page, 20, $filter);

        return View::render('notifications/index', [
            'title' => 'Notifiche',
            'user' => $user,
            'notifications' => $result['notifications'],
            'total' => $result['total'],
            'page' => $result['page'],
            'perPage' => $result['perPage'],
            'filter' => $filter,
            'unreadCount' => \Services\NotificationService::getUnreadCount($user['id']),
            'modules' => ModuleLoader::getActiveModules()
        ]);
    }

    /**
     * Segna una notifica come letta (JSON)
     * POST /notifications/{id}/read
     */
    public function markRead(int $id): string
    {
        Middleware::auth();
        Middleware::csrf();

        \Services\NotificationService::markAsRead($id, Auth::id());

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    /**
     * Segna tutte le notifiche come lette (JSON o redirect)
     * POST /notifications/read-all
     */
    public function markAllRead(): string
    {
        Middleware::auth();
        Middleware::csrf();

        $count = \Services\NotificationService::markAllAsRead(Auth::id());

        // AJAX: ritorna JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
            str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'count' => $count]);
            exit;
        }

        // Form standard: redirect
        $_SESSION['_flash']['success'] = 'Tutte le notifiche segnate come lette';
        \Core\Router::redirect('/notifications');
    }
}
