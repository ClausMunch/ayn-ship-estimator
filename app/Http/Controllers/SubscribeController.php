<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubscribeRequest;
use App\Mail\VerifySubscription;
use App\Models\Subscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SubscribeController extends Controller
{
    public function store(SubscribeRequest $request): JsonResponse
    {
        $subscriber = Subscriber::where('email', $request->email)
            ->where('model_variant_id', $request->model_variant_id)
            ->where('order_prefix', $request->order_prefix)
            ->first();

        if ($subscriber) {
            if ($subscriber->isVerified()) {
                return response()->json(['message' => 'You are already subscribed for this order.']);
            }

            // Resend verification
            Mail::to($subscriber->email)->queue(new VerifySubscription($subscriber));

            return response()->json(['message' => 'Verification email resent. Please check your inbox.']);
        }

        $subscriber = Subscriber::create([
            'email' => $request->email,
            'model_variant_id' => $request->model_variant_id,
            'order_prefix' => $request->order_prefix,
            'verification_token' => Str::random(64),
            'unsubscribe_token' => Str::random(64),
        ]);

        Mail::to($subscriber->email)->queue(new VerifySubscription($subscriber));

        return response()->json(['message' => 'Check your email to verify your subscription.']);
    }

    public function verify(string $token): RedirectResponse
    {
        $subscriber = Subscriber::where('verification_token', $token)->first();

        if (!$subscriber) {
            return redirect('/')->with('error', 'Invalid or expired verification link.');
        }

        if (!$subscriber->isVerified()) {
            $subscriber->update(['email_verified_at' => now()]);
        }

        return redirect('/?verified=1');
    }

    public function unsubscribe(string $token): \Illuminate\View\View
    {
        $subscriber = Subscriber::where('unsubscribe_token', $token)->first();

        if ($subscriber) {
            $subscriber->delete();
        }

        return view('unsubscribed');
    }
}
