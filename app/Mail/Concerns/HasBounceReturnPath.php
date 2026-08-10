<?php

namespace App\Mail\Concerns;

use Closure;
use Symfony\Component\Mime\Email;

trait HasBounceReturnPath
{
    private function bounceEnvelopeCallback(): ?Closure
    {
        $address = config('mail.bounce.address');

        if (! is_string($address) || ! str_contains($address, '@')) {
            return null;
        }

        [$local, $domain] = explode('@', $address, 2);
        $returnPath = "{$local}+{$this->subscriber->unsubscribe_token}@{$domain}";

        return static function (Email $message) use ($returnPath): void {
            $message->returnPath($returnPath);
        };
    }
}
