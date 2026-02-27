<?php

namespace App\Http\Controllers;

use App\Models\ModelVariant;
use App\Models\ScrapeLog;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $variants = ModelVariant::orderBy('display_order')
            ->with(['shippingBatches' => fn ($q) => $q->orderBy('order_range_end')])
            ->get();

        $lastUpdated = ScrapeLog::where('status', 'success')
            ->latest('created_at')
            ->value('created_at');

        $timestamp = time();
        $hmac = hash_hmac('sha256', (string) $timestamp, config('app.key'));

        return Inertia::render('Home', [
            'variants' => $variants,
            'lastUpdated' => $lastUpdated?->toIso8601String(),
            'csrfToken' => csrf_token(),
            'signedTs' => "{$timestamp}.{$hmac}",
        ]);
    }
}
