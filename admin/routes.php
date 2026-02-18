<?php

use Core\Router;
use Admin\Controllers\AdminController;
use Admin\Controllers\FinanceController;
use Admin\Controllers\AiLogsController;
use Admin\Controllers\ApiLogsController;
use Admin\Controllers\JobsController;

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
Router::post('/admin/settings/test-smtp', [AdminController::class, 'testSmtp']);
Router::post('/admin/settings/test-email', [AdminController::class, 'testEmail']);
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

// Jobs Monitor
Router::get('/admin/jobs', [JobsController::class, 'index']);
Router::post('/admin/jobs/cancel', [JobsController::class, 'cancelJob']);
Router::post('/admin/jobs/cancel-stuck', [JobsController::class, 'cancelStuck']);
Router::post('/admin/jobs/cleanup', [JobsController::class, 'cleanup']);

// Cache Management
Router::get('/admin/cache', [AdminController::class, 'cache']);
Router::post('/admin/cache/clear', [AdminController::class, 'cacheClear']);
Router::post('/admin/cache/clear-key', [AdminController::class, 'cacheClearKey']);
