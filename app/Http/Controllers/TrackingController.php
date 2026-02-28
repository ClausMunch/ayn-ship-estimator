<?php

namespace App\Http\Controllers;

use App\Models\EstimationLog;
use App\Models\PageView;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    public function view(Request $request): JsonResponse
    {
        PageView::create([
            'ip_hash' => hash('sha256', $request->ip()),
            'user_agent' => substr($request->userAgent() ?? '', 0, 512),
            'referrer' => substr($request->header('referer', ''), 0, 1024) ?: null,
        ]);

        return response()->json(['ok' => true]);
    }

    public function estimation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'model_variant_id' => 'required|integer|exists:model_variants,id',
            'order_prefix' => 'required|integer|min:1000|max:9999',
            'result_type' => 'required|string|in:shipped,estimated,extrapolated',
        ]);

        EstimationLog::create($validated);

        return response()->json(['ok' => true]);
    }
}
