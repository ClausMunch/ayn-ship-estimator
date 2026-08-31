<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DeviceStatusConfirmationController extends Controller
{
    public function __invoke(Request $request, Subscriber $subscriber, string $milestone): View
    {
        abort_unless(
            $request->hasValidSignature(absolute: false) || $request->hasValidSignature(),
            403,
            'Invalid signature.',
        );

        $now = now();

        if ($milestone === 'delivered') {
            $subscriber->update([
                'shipped_confirmed_at' => $subscriber->shipped_confirmed_at ?? $now,
                'delivered_confirmed_at' => $subscriber->delivered_confirmed_at ?? $now,
            ]);
        } else {
            $subscriber->update([
                'shipped_confirmed_at' => $subscriber->shipped_confirmed_at ?? $now,
            ]);
        }

        return view('device-status-confirmed', ['milestone' => $milestone]);
    }
}
