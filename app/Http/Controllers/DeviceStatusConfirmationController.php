<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;
use Illuminate\Contracts\View\View;

class DeviceStatusConfirmationController extends Controller
{
    public function __invoke(Subscriber $subscriber, string $milestone): View
    {
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
