<?php

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ScrapeLogsController;
use App\Http\Controllers\Admin\SubscribersController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SubscribeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);
Route::get('/verify/{token}', [SubscribeController::class, 'verify']);
Route::get('/unsubscribe/{token}', [SubscribeController::class, 'unsubscribe']);

// Admin auth (guest only)
Route::get('/admin/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/admin/login', [AuthController::class, 'login']);

// Admin panel (auth required)
Route::middleware('auth')
    ->prefix('admin')
    ->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/', [DashboardController::class, 'index']);
        Route::get('/subscribers', [SubscribersController::class, 'index']);
        Route::post('/subscribers/{subscriber}/resend-verification', [
            SubscribersController::class,
            'resendVerification',
        ]);
        Route::delete('/subscribers/{subscriber}', [SubscribersController::class, 'destroy']);
        Route::get('/analytics', [AnalyticsController::class, 'index']);
        Route::get('/scrape-logs', [ScrapeLogsController::class, 'index']);
    });
