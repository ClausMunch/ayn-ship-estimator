<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\VerifySubscription;
use App\Models\Subscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SubscribersController extends Controller
{
    public function index(Request $request): Response
    {
        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');

        $allowed = ['email', 'order_prefix', 'email_verified_at', 'created_at'];
        if (!in_array($sort, $allowed)) {
            $sort = 'created_at';
        }

        $subscribers = Subscriber::with('modelVariant:id,name')
            ->orderBy($sort, $direction === 'asc' ? 'asc' : 'desc')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/Subscribers', [
            'subscribers' => $subscribers,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function destroy(Subscriber $subscriber): RedirectResponse
    {
        $subscriber->delete();

        return back();
    }

    public function resendVerification(Subscriber $subscriber): RedirectResponse
    {
        if ($subscriber->isVerified()) {
            throw ValidationException::withMessages([
                'resend' => 'Subscriber is already verified.',
            ]);
        }

        Mail::to($subscriber->email)->queue(new VerifySubscription($subscriber));

        return back();
    }
}
