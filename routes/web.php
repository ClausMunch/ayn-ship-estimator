<?php

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GuidesController;
use App\Http\Controllers\Admin\QueueController;
use App\Http\Controllers\Admin\ScrapeLogsController;
use App\Http\Controllers\Admin\SubscribersController;
use App\Http\Controllers\DeviceStatusConfirmationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\GuideController;
use App\Http\Controllers\SubscribeController;
use App\Models\Guide;
use App\Support\SiteUrl;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);

Route::view('/about', 'pages.about')->name('about');

Route::get('/guides', [GuideController::class, 'index'])->name('guides.index');
Route::get('/guides/{slug}', [GuideController::class, 'show'])->name('guides.show');

Route::get('/robots.txt', function () {
    return response()->view('robots', ['sitemapUrl' => SiteUrl::to('/sitemap.xml')], 200, [
        'Content-Type' => 'text/plain; charset=UTF-8',
    ]);
});

Route::get('/sitemap.xml', function () {
    $url = SiteUrl::to();

    return response()->view('sitemap', ['url' => $url, 'guides' => Guide::published()->orderBy('id')->get(['slug', 'updated_at'])], 200, [
        'Content-Type' => 'application/xml',
    ]);
});
Route::get('/verify/{token}', [SubscribeController::class, 'verify']);
Route::get('/unsubscribe/{token}', [SubscribeController::class, 'unsubscribe']);
Route::get('/confirm-device-status/{subscriber}/{milestone}/{status?}', DeviceStatusConfirmationController::class)
    ->whereIn('milestone', ['shipped', 'delivered'])
    ->whereIn('status', ['not-yet'])
    ->name('device-status.confirm');

// Admin auth (guest only)
Route::get('/admin/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/admin/login', [AuthController::class, 'login']);

// Admin panel (auth required)
Route::middleware('auth')
    ->prefix('admin')
    ->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/', [DashboardController::class, 'index']);
        Route::post('/scrape', [DashboardController::class, 'scrape'])->middleware('throttle:3,1');
        Route::get('/subscribers', [SubscribersController::class, 'index']);
        Route::post('/subscribers/resend-all-verifications', [
            SubscribersController::class,
            'resendAllVerifications',
        ])->middleware('throttle:1,1');
        Route::delete('/subscribers/bounced', [SubscribersController::class, 'destroyBounced']);
        Route::post('/subscribers/{subscriber}/resend-verification', [
            SubscribersController::class,
            'resendVerification',
        ])->middleware('throttle:5,1');
        Route::delete('/subscribers/{subscriber}', [SubscribersController::class, 'destroy']);
        Route::get('/analytics', [AnalyticsController::class, 'index']);
        Route::get('/guides/{guide}/preview', [GuidesController::class, 'preview'])->name('admin.guides.preview');
        Route::resource('guides', GuidesController::class)->except(['show', 'destroy'])->names('admin.guides');
        Route::get('/scrape-logs', [ScrapeLogsController::class, 'index']);
        Route::delete('/scrape-logs', [ScrapeLogsController::class, 'clear'])->middleware('throttle:3,1');
        Route::get('/queue', [QueueController::class, 'index']);
        Route::post('/queue/failed/{id}/retry', [QueueController::class, 'retryFailed'])->middleware('throttle:10,1');
    });
