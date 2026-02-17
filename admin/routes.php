<?php

use Core\Router;
use Admin\Controllers\AdminController;
use Admin\Controllers\FinanceController;
use Admin\Controllers\AiLogsController;
use Admin\Controllers\ApiLogsController;

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
Router::post('/admin/modules/{id}/rename', [AdminController::class, 'moduleRename']);
Router::post('/admin/settings/branding', [AdminController::class, 'brandingUpdate']);
Router::get('/admin/plans', [AdminController::class, 'plans']);
Router::post('/admin/plans/{id}', [AdminController::class, 'planUpdate']);

// Finance
Router::get('/admin/finance', [FinanceController::class, 'index']);

// AI Logs
Router::get('/admin/ai-logs', [AiLogsController::class, 'index']);
Router::get('/admin/ai-logs/{id}', [AiLogsController::class, 'show']);
Router::post('/admin/ai-logs/cleanup', [AiLogsController::class, 'cleanup']);

// API Logs
Router::get('/admin/api-logs', [ApiLogsController::class, 'index']);
Router::get('/admin/api-logs/{id}', [ApiLogsController::class, 'show']);
Router::post('/admin/api-logs/cleanup', [ApiLogsController::class, 'cleanup']);
