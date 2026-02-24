<?php

namespace Admin\Controllers;

use Core\Database;
use Core\View;
use Core\Auth;
use Core\ModuleLoader;
use Core\Middleware;

class EmailTemplateController
{
    public function __construct()
    {
        Middleware::admin();
    }

    /**
     * Lista tutti i template email
     */
    public function index(): string
    {
        $templates = Database::fetchAll(
            "SELECT id, slug, name, category, is_active, updated_at FROM email_templates ORDER BY category, name"
        );

        // Raggruppa per categoria
        $grouped = [];
        foreach ($templates as $t) {
            $grouped[$t['category']][] = $t;
        }

        return View::render('admin/email-templates/index', [
            'title' => 'Template Email',
            'user' => Auth::user(),
            'modules' => ModuleLoader::getActiveModules(),
            'templates' => $templates,
            'grouped' => $grouped,
        ]);
    }

    /**
     * Form modifica template
     */
    public function edit(string $slug): string
    {
        $template = Database::fetch(
            "SELECT * FROM email_templates WHERE slug = ?",
            [$slug]
        );

        if (!$template) {
            $_SESSION['_flash']['error'] = 'Template non trovato';
            \Core\Router::redirect('/admin/email-templates');
            return '';
        }

        // Decode available_vars JSON
        $availableVars = json_decode($template['available_vars'] ?? '[]', true) ?: [];

        return View::render('admin/email-templates/edit', [
            'title' => 'Modifica Template - ' . $template['name'],
            'user' => Auth::user(),
            'modules' => ModuleLoader::getActiveModules(),
            'template' => $template,
            'availableVars' => $availableVars,
        ]);
    }

    /**
     * Salva modifiche template (POST)
     */
    public function update(string $slug): string
    {
        Middleware::csrf();

        $template = Database::fetch(
            "SELECT id FROM email_templates WHERE slug = ?",
            [$slug]
        );

        if (!$template) {
            $_SESSION['_flash']['error'] = 'Template non trovato';
            \Core\Router::redirect('/admin/email-templates');
            return '';
        }

        $subject = trim($_POST['subject'] ?? '');
        $bodyHtml = $_POST['body_html'] ?? '';
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (empty($subject)) {
            $_SESSION['_flash']['error'] = 'L\'oggetto non puo essere vuoto';
            \Core\Router::redirect('/admin/email-templates/' . $slug);
            return '';
        }

        Database::update(
            'email_templates',
            [
                'subject' => $subject,
                'body_html' => $bodyHtml,
                'is_active' => $isActive,
            ],
            'slug = ?',
            [$slug]
        );

        $_SESSION['_flash']['success'] = 'Template aggiornato con successo';
        \Core\Router::redirect('/admin/email-templates/' . $slug);
        return '';
    }

    /**
     * Preview AJAX del template (POST)
     */
    public function preview(string $slug): string
    {
        $subject = $_POST['subject'] ?? null;
        $bodyHtml = $_POST['body_html'] ?? null;

        $html = \Services\EmailService::renderPreview($slug, $subject, $bodyHtml);

        header('Content-Type: text/html; charset=UTF-8');
        return $html;
    }

    /**
     * Invia email di test all'admin (POST AJAX)
     */
    public function sendTest(string $slug): string
    {
        Middleware::csrf();

        $adminEmail = Auth::user()['email'] ?? '';

        if (empty($adminEmail)) {
            return View::json(['success' => false, 'message' => 'Email admin non trovata']);
        }

        // Verifica che il template esista
        $template = Database::fetch(
            "SELECT * FROM email_templates WHERE slug = ?",
            [$slug]
        );

        if (!$template) {
            return View::json(['success' => false, 'message' => 'Template non trovato']);
        }

        // Dati di esempio per il test
        $sampleData = \Services\EmailService::getSampleDataPublic($slug);

        // Invia usando sendTemplate che gestisce tutto
        $result = \Services\EmailService::sendTemplate(
            $adminEmail,
            $template['subject'],
            $slug,
            $sampleData,
            Auth::id()
        );

        if ($result['success']) {
            $result['message'] = "Email di test inviata a {$adminEmail}";
        }

        return View::json($result);
    }

    /**
     * Ripristina template ai valori predefiniti (POST AJAX)
     */
    public function resetDefault(string $slug): string
    {
        Middleware::csrf();

        $defaults = \Services\EmailService::getDefaultTemplates();

        if (!isset($defaults[$slug])) {
            return View::json(['success' => false, 'message' => 'Template predefinito non trovato per: ' . $slug]);
        }

        $default = $defaults[$slug];

        Database::update(
            'email_templates',
            [
                'subject' => $default['subject'],
                'body_html' => $default['body_html'],
            ],
            'slug = ?',
            [$slug]
        );

        return View::json([
            'success' => true,
            'message' => 'Template ripristinato ai valori predefiniti',
            'subject' => $default['subject'],
            'body_html' => $default['body_html'],
        ]);
    }

    /**
     * Toggle attivo/inattivo template (POST AJAX)
     */
    public function toggle(string $slug): string
    {
        Middleware::csrf();

        $template = Database::fetch(
            "SELECT id, is_active FROM email_templates WHERE slug = ?",
            [$slug]
        );

        if (!$template) {
            return View::json(['success' => false, 'message' => 'Template non trovato']);
        }

        $newStatus = $template['is_active'] ? 0 : 1;

        Database::update(
            'email_templates',
            ['is_active' => $newStatus],
            'slug = ?',
            [$slug]
        );

        return View::json([
            'success' => true,
            'is_active' => $newStatus,
            'message' => $newStatus ? 'Template attivato' : 'Template disattivato',
        ]);
    }
}
