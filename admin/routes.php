<?php

use Core\Router;
use Admin\Controllers\AdminController;
use Admin\Controllers\AiLogsController;

// Admin routes
Router::get('/admin', [AdminController::class, 'dashboard']);
Router::get('/admin/users', [AdminController::class, 'users']);
Router::get('/admin/users/{id}', [AdminController::class, 'userShow']);
Router::post('/admin/users/{id}', [AdminController::class, 'userUpdate']);
Router::post('/admin/users/{id}/credits', [AdminController::class, 'userCredits']);
Router::get('/admin/settings', [AdminController::class, 'settings']);
Router::post('/admin/settings', [AdminController::class, 'settingsUpdate']);
Router::get('/admin/modules', [AdminController::class, 'modules']);
Router::post('/admin/modules/{id}/toggle', [AdminController::class, 'moduleToggle']);
Router::get('/admin/modules/{id}/settings', [AdminController::class, 'moduleSettings']);
Router::post('/admin/modules/{id}/settings', [AdminController::class, 'moduleSettingsUpdate']);
Router::get('/admin/plans', [AdminController::class, 'plans']);
Router::post('/admin/plans/{id}', [AdminController::class, 'planUpdate']);

// AI Logs
Router::get('/admin/ai-logs', [AiLogsController::class, 'index']);
Router::get('/admin/ai-logs/{id}', [AiLogsController::class, 'show']);
Router::post('/admin/ai-logs/cleanup', [AiLogsController::class, 'cleanup']);
