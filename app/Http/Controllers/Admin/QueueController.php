<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class QueueController extends Controller
{
    public function index(): Response
    {
        $queueConnection = config('queue.connections.database.connection') ?: config('database.default');
        $failedConnection = config('queue.failed.database') ?: config('database.default');

        $pendingJobs = DB::connection($queueConnection)
            ->table('jobs')
            ->select(['id', 'queue', 'attempts', 'reserved_at', 'available_at', 'created_at'])
            ->orderByDesc('id')
            ->paginate(25, ['*'], 'pending_page')
            ->withQueryString();

        $failedJobs = DB::connection($failedConnection)
            ->table('failed_jobs')
            ->select(['id', 'uuid', 'connection', 'queue', 'exception', 'failed_at'])
            ->orderByDesc('id')
            ->paginate(25, ['*'], 'failed_page')
            ->withQueryString();

        return Inertia::render('Admin/Queue', [
            'pendingJobs' => $pendingJobs,
            'failedJobs' => $failedJobs,
            'queueConnection' => $queueConnection,
            'failedConnection' => $failedConnection,
        ]);
    }

    public function retryFailed(int $id): RedirectResponse
    {
        Artisan::call('queue:retry', ['id' => [$id]]);

        return back();
    }
}
