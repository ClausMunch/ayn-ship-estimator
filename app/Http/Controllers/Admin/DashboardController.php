<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EstimationLog;
use App\Models\PageView;
use App\Models\ScrapeLog;
use App\Models\Subscriber;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $today = Carbon::today();
        $sevenDaysAgo = Carbon::today()->subDays(6);

        // Today's visitors
        $todayTotal = PageView::whereDate('created_at', $today)->count();
        $todayUnique = PageView::whereDate('created_at', $today)->distinct('ip_hash')->count('ip_hash');

        // Weekly visitors (last 7 days)
        $weeklyViews = PageView::where('created_at', '>=', $sevenDaysAgo)
            ->select(
                DB::raw("DATE(created_at) as date"),
                DB::raw('COUNT(*) as total'),
                DB::raw('COUNT(DISTINCT ip_hash) as "unique"'),
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Total verified subscribers
        $totalVerified = Subscriber::whereNotNull('email_verified_at')->count();

        // Most checked model variant (7 days)
        $topVariant = EstimationLog::where('created_at', '>=', $sevenDaysAgo)
            ->select('model_variant_id', DB::raw('COUNT(*) as count'))
            ->groupBy('model_variant_id')
            ->orderByDesc('count')
            ->with('modelVariant:id,name')
            ->first();

        // Last scrape
        $lastScrape = ScrapeLog::latest('created_at')->first();

        return Inertia::render('Admin/Dashboard', [
            'todayTotal' => $todayTotal,
            'todayUnique' => $todayUnique,
            'weeklyViews' => $weeklyViews,
            'totalVerified' => $totalVerified,
            'topVariant' => $topVariant ? [
                'name' => $topVariant->modelVariant?->name ?? 'Unknown',
                'count' => $topVariant->count,
            ] : null,
            'lastScrape' => $lastScrape,
        ]);
    }

    public function scrape(): RedirectResponse
    {
        Artisan::call('scrape');

        return back();
    }
}
