<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EstimationLog;
use App\Models\PageView;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function index(): Response
    {
        $thirtyDaysAgo = Carbon::today()->subDays(29);

        // Daily page views (30 days)
        $dailyViews = PageView::where('created_at', '>=', $thirtyDaysAgo)
            ->select(
                DB::raw("DATE(created_at) as date"),
                DB::raw('COUNT(*) as total'),
                DB::raw('COUNT(DISTINCT ip_hash) as "unique"'),
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Daily estimations (30 days)
        $dailyEstimations = EstimationLog::where('created_at', '>=', $thirtyDaysAgo)
            ->select(
                DB::raw("DATE(created_at) as date"),
                DB::raw('COUNT(*) as total'),
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top model variants (30 days)
        $topVariants = EstimationLog::where('created_at', '>=', $thirtyDaysAgo)
            ->select('model_variant_id', DB::raw('COUNT(*) as count'))
            ->groupBy('model_variant_id')
            ->orderByDesc('count')
            ->limit(10)
            ->with('modelVariant:id,name')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->modelVariant?->name ?? 'Unknown',
                'count' => $row->count,
            ]);

        // Top order prefixes (30 days)
        $topPrefixes = EstimationLog::where('created_at', '>=', $thirtyDaysAgo)
            ->select('order_prefix', DB::raw('COUNT(*) as count'))
            ->groupBy('order_prefix')
            ->orderByDesc('count')
            ->limit(20)
            ->get();

        return Inertia::render('Admin/Analytics', [
            'dailyViews' => $dailyViews,
            'dailyEstimations' => $dailyEstimations,
            'topVariants' => $topVariants,
            'topPrefixes' => $topPrefixes,
        ]);
    }
}
