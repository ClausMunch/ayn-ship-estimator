<?php

namespace App\Console\Commands;

use App\Models\Subscriber;
use Illuminate\Console\Command;
use IMAP\Connection;
use RuntimeException;

class CollectMailBounces extends Command
{
    protected $signature = 'mail:collect-bounces';

    protected $description = 'Collect delivery failures from the configured IMAP bounce mailbox';

    public function handle(): int
    {
        if (! function_exists('imap_open')) {
            $this->error('The PHP IMAP extension is not installed.');

            return self::FAILURE;
        }

        try {
            $inbox = $this->openInbox();
            $messages = imap_search($inbox, 'UNSEEN') ?: [];
            $processed = 0;

            foreach ($messages as $number) {
                $content = imap_fetchheader($inbox, $number)."\n".imap_body($inbox, $number);
                $subscriber = $this->subscriberFromMessage($content);

                if (! $subscriber) {
                    continue;
                }

                $status = $this->dsnField($content, 'Status');
                $diagnostic = $this->dsnField($content, 'Diagnostic-Code')
                    ?? 'Delivery was rejected; no diagnostic was supplied.';
                $hardBounce = $status === null || str_starts_with($status, '5.');

                $subscriber->update([
                    'delivery_status' => $hardBounce ? 'bounced' : 'deferred',
                    'delivery_error' => mb_substr($diagnostic, 0, 2000),
                    'bounced_at' => $hardBounce ? now() : null,
                ]);

                imap_delete($inbox, (string) $number);
                $processed++;
            }

            imap_expunge($inbox);
            imap_close($inbox);
            $this->info("Processed {$processed} delivery failure(s).");

            return self::SUCCESS;
        } catch (\Throwable $error) {
            $this->error($error->getMessage());

            return self::FAILURE;
        }
    }

    private function openInbox(): Connection
    {
        $config = config('mail.bounce');

        foreach (['host', 'username', 'password'] as $key) {
            if (empty($config[$key])) {
                throw new RuntimeException('MAIL_BOUNCE_'.strtoupper($key).' is not configured.');
            }
        }

        $flags = '/imap';
        if (($config['encryption'] ?? null) === 'ssl') {
            $flags .= '/ssl';
        } elseif (($config['encryption'] ?? null) === 'tls') {
            $flags .= '/tls';
        }

        $path = sprintf('{%s:%d%s}%s', $config['host'], $config['port'], $flags, $config['mailbox']);
        $inbox = @imap_open($path, $config['username'], $config['password']);

        if ($inbox === false) {
            throw new RuntimeException('Could not open bounce mailbox: '.(imap_last_error() ?: 'unknown IMAP error'));
        }

        return $inbox;
    }

    private function subscriberFromMessage(string $content): ?Subscriber
    {
        $address = (string) config('mail.bounce.address');
        $local = preg_quote(strstr($address, '@', true) ?: '', '/');
        $domain = preg_quote(ltrim((string) strstr($address, '@'), '@'), '/');

        if ($local === '' || $domain === ''
            || ! preg_match("/{$local}\\+([A-Za-z0-9]{64})@{$domain}/i", $content, $match)) {
            return null;
        }

        return Subscriber::where('unsubscribe_token', $match[1])->first();
    }

    private function dsnField(string $content, string $field): ?string
    {
        if (! preg_match('/^'.preg_quote($field, '/').':\\s*(.+(?:\\r?\\n[ \\t].+)*)/mi', $content, $match)) {
            return null;
        }

        return trim(preg_replace('/\\r?\\n[ \\t]+/', ' ', $match[1]));
    }
}
