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

        return static function (Email $message) use ($address): void {
            $message->returnPath($address);
        };
    }
}
