<?php

declare(strict_types=1);

namespace App;

final class Mailer
{
    public static function send(string $to, string $subject, string $htmlBody, array $headers = []): bool
    {
        $driver = (string) config('mail.driver', 'mail');
        $from = (string) (config('mail.from') ?? config('store.support_email') ?? 'sales@ronakfire.com');
        $fromName = (string) (config('mail.from_name') ?? config('store.name') ?? 'Ronak Fire India');

        $defaultHeaders = [];
        $defaultHeaders[] = 'From: ' . self::formatAddress($from, $fromName);
        $defaultHeaders[] = 'MIME-Version: 1.0';
        $defaultHeaders[] = 'Content-Type: text/html; charset=UTF-8';

        if ($driver === 'smtp') {
            // Fallback to mail() if SMTP not fully configured; PHPMailer not present by default
            $host = (string) config('mail.host', '');
            if ($host === '') {
                $driver = 'mail';
            }
        }

        if ($driver === 'smtp') {
            // Best-effort using PHP stream/socket if possible is complex; use mail() as fallback
        }

        $allHeaders = array_merge($defaultHeaders, $headers);
        $headerStr = implode("\r\n", array_unique($allHeaders));

        try {
            return @mail($to, $subject, $htmlBody, $headerStr);
        } catch (\Throwable $e) {
            error_log('Mailer send failed: ' . $e->getMessage());
            return false;
        }
    }

    private static function formatAddress(string $email, string $name): string
    {
        if ($name === '') {
            return $email;
        }
        return $name . ' <' . $email . '>';
    }
}
