<?php

namespace App\Console\Commands;

use App\Mail\ScrapeAlert;
use App\Models\ScrapeLog;
use App\Services\ScraperService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class Scrape extends Command
{
    protected $signature = 'scrape';
    protected $description = 'Scrape the AYN shipping dashboard and update batch data';

    public function handle(ScraperService $scraper): int
    {
        $this->info('Starting scrape...');

        $result = $scraper->run();

        // Log the result
        ScrapeLog::create([
            'status' => $result->status,
            'records_found' => $result->recordsFound,
            'records_new' => $result->recordsNew,
            'error_message' => $result->error,
            'duration_ms' => $result->durationMs,
            'created_at' => now(),
        ]);

        if ($result->failed()) {
            $this->error("Scrape failed: {$result->error}");
            $this->sendAlert("Scrape threw an exception: {$result->error}", $result);
            return self::FAILURE;
        }

        if ($result->recordsFound === 0) {
            $this->warn('Scrape succeeded but found 0 records — page structure may have changed.');
            $this->sendAlert('Scrape returned 0 records. The page structure may have changed.', $result);
            return self::FAILURE;
        }

        // Check if record count dropped significantly from last successful scrape
        $lastSuccessful = ScrapeLog::where('status', 'success')
            ->where('records_found', '>', 0)
            ->latest('created_at')
            ->skip(1) // skip the one we just created
            ->first();

        if ($lastSuccessful && $result->recordsFound < $lastSuccessful->records_found * 0.5) {
            $this->warn("Record count dropped from {$lastSuccessful->records_found} to {$result->recordsFound}.");
            $this->sendAlert(
                "Record count dropped significantly: {$lastSuccessful->records_found} → {$result->recordsFound}. Data may have been removed.",
                $result,
            );
        }

        $this->info("Scrape complete: {$result->recordsFound} records found, {$result->recordsNew} new. ({$result->durationMs}ms)");

        return self::SUCCESS;
    }

    private function sendAlert(string $message, \App\Services\ScrapeResult $result): void
    {
        $adminEmail = config('mail.admin_email');

        if (!$adminEmail) {
            $this->warn('No ADMIN_EMAIL configured, skipping alert.');
            return;
        }

        Mail::to($adminEmail)->send(new ScrapeAlert($message, $result));
    }
}
