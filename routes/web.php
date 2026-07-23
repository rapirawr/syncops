<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AiAssistantController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\VisitorAnalyticsController;
use App\Http\Controllers\AuthController;

// Guest routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Authenticated routes
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/api/dashboard/stats', [DashboardController::class, 'fragmentStats'])->name('dashboard.fragments.stats');
    Route::get('/api/dashboard/telemetry', [DashboardController::class, 'fragmentTelemetry'])->name('dashboard.fragments.telemetry');
    Route::get('/api/dashboard/projects', [DashboardController::class, 'fragmentProjects'])->name('dashboard.fragments.projects');

    // Logs
    Route::get('/logs', [DashboardController::class, 'logs'])->name('logs');

    // Projects
    Route::post('/projects/reorder', [ProjectController::class, 'reorder'])->name('projects.reorder');
    Route::post('/projects/sync-all', [ProjectController::class, 'syncAll'])->name('projects.sync-all');
    Route::get('/projects/{project}/edit-data', [ProjectController::class, 'editData'])->name('projects.edit-data');
    Route::post('/projects/{project}/sync', [ProjectController::class, 'sync'])->name('projects.sync');
    Route::post('/projects/{project}/override', [ProjectController::class, 'storeOverride'])->name('projects.override');
    Route::post('/projects/{project}/clear-override', [ProjectController::class, 'clearOverride'])->name('projects.clear-override');
    Route::post('/projects/{project}/updates', [ProjectController::class, 'storeUpdate'])->name('projects.updates.store');
    Route::post('/projects/{project}/ping', [ProjectController::class, 'ping'])->name('projects.ping');
    Route::post('/projects/{project}/check-ssl', [ProjectController::class, 'checkSsl'])->name('projects.check-ssl');
    
    Route::resource('projects', ProjectController::class)->except(['index']);

    // AI Assistant
    Route::get('/ai/assistant', [AiAssistantController::class, 'index'])->name('ai.assistant');
    Route::post('/api/ai/chat', [AiAssistantController::class, 'chat'])->name('ai.chat');
    Route::post('/api/ai/chat/stream', [AiAssistantController::class, 'chatStream'])->name('ai.chat.stream');
    Route::delete('/api/ai/chat/clear', [AiAssistantController::class, 'clearHistory'])->name('ai.chat.clear');
    Route::get('/api/ai/session/{session}', [AiAssistantController::class, 'loadSession'])->name('ai.session.messages');
    Route::get('/api/ai/session/{session}/title', [AiAssistantController::class, 'sessionTitle'])->name('ai.session.title');
    Route::delete('/api/ai/session/{session}', [AiAssistantController::class, 'deleteSession'])->name('ai.session.delete');

    // System SLA Analytics & Synthetic Benchmark Suite
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    Route::post('/projects/{project}/benchmark', [AnalyticsController::class, 'runBenchmark'])->name('projects.benchmark');
    Route::post('/analytics/benchmark-all', [AnalyticsController::class, 'runAllBenchmarks'])->name('analytics.benchmark-all');
    Route::get('/analytics/export', [AnalyticsController::class, 'exportCsv'])->name('analytics.export');

    // Visitor Analytics Dashboard
    Route::get('/visitors', [VisitorAnalyticsController::class, 'index'])->name('visitors.index');
});

// Public Telemetry Pixel Collector API (CORS-enabled for external target websites)
Route::match(['POST', 'OPTIONS'], '/api/v1/telemetry/collect', [VisitorAnalyticsController::class, 'collect'])->name('api.telemetry.collect');

// Demo Self-Metrics Endpoint
Route::get('/metrics', function () {
    return response()->json([
        'status' => 'healthy',
        'uptime' => round((time() - strtotime('2025-01-01')) / 3600, 1) . 'h',
        'timestamp' => now()->toIso8601String(),
    ]);
});