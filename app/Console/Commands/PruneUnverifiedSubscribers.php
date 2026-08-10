<?php

namespace App\Console\Commands;

use App\Models\Subscriber;
use Illuminate\Console\Command;

class PruneUnverifiedSubscribers extends Command
{
    protected $signature = 'subscribers:prune-unverified {--days=7 : Days after the last successful verification email}';

    protected $description = 'Delete subscribers who did not verify within the retention period';

    public function handle(): int
    {
        $days = filter_var($this->option('days'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 365],
        ]);

        if ($days === false) {
            $this->error('--days must be an integer between 1 and 365.');

            return self::FAILURE;
        }

        $deleted = Subscriber::whereNull('email_verified_at')
            ->whereNotNull('verification_sent_at')
            ->where('verification_sent_at', '<=', now()->subDays($days))
            ->delete();

        $this->info("Deleted {$deleted} unverified subscriber(s).");

        return self::SUCCESS;
    }
}
