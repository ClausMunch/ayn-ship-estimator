<?php

namespace App\Mail\Concerns;

use Throwable;

trait HasReliableDelivery
{
    public int $tries = 3;

    public int $timeout = 45;

    /** @var list<int> */
    public array $backoff = [60, 300];

    public function failed(Throwable $error): void
    {
        $this->subscriber->refresh()->update([
            'delivery_status' => 'failed',
            'delivery_error' => mb_substr($error->getMessage(), 0, 2000),
        ]);
    }
}
