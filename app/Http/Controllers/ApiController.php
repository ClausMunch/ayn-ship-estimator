<?php

namespace App\Http\Controllers;

use App\Models\ModelVariant;
use Illuminate\Http\JsonResponse;

class ApiController extends Controller
{
    public function data(): JsonResponse
    {
        $variants = ModelVariant::orderBy('display_order')
            ->with(['shippingBatches' => fn ($q) => $q->orderBy('order_range_end')])
            ->get();

        return response()->json(['variants' => $variants]);
    }
}
