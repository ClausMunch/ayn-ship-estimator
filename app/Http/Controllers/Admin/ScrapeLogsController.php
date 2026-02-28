<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScrapeLog;
use Inertia\Inertia;
use Inertia\Response;

class ScrapeLogsController extends Controller
{
    public function index(): Response
    {
        $logs = ScrapeLog::latest('created_at')->paginate(50);

        return Inertia::render('Admin/ScrapeLogs', [
            'logs' => $logs,
        ]);
    }
}
