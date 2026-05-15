<?php

declare(strict_types=1);

final class MailjetMailer
{
    private const SEND_ENDPOINT = 'https://api.mailjet.com/v3.1/send';

    public function send(string $toEmail, string $toName, string $subject, string $html, string $text): array
    {
        $apiKey = trim(setting('mailjet_api_key'));
        $secretKey = trim(setting('mailjet_secret_key'));
        $fromEmail = trim(setting('mail_from_email', setting('support_email')));
        $fromName = trim(setting('mail_from_name', setting('site_name', app_config('app.name', 'Store'))));
        if ($fromEmail === '') {
            $fromEmail = trim(setting('support_email'));
        }
        if ($fromName === '') {
            $fromName = trim(setting('site_name', app_config('app.name', 'Store')));
        }

        if ($apiKey === '' || $secretKey === '') {
            throw new RuntimeException('Mailjet API key and secret are required.');
        }
        if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('A valid From Email is required.');
        }
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('A valid recipient email is required.');
        }
        if (!function_exists('curl_init')) {
            throw new RuntimeException('PHP cURL extension is required for Mailjet API.');
        }

        $payload = [
            'Messages' => [[
                'From' => [
                    'Email' => $fromEmail,
                    'Name' => $fromName !== '' ? $fromName : $fromEmail,
                ],
                'To' => [[
                    'Email' => $toEmail,
                    'Name' => $toName !== '' ? $toName : $toEmail,
                ]],
                'Subject' => $subject,
                'TextPart' => $text,
                'HTMLPart' => $html,
            ]],
        ];

        $ch = curl_init(self::SEND_ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $apiKey . ':' . $secretKey,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 12,
        ]);

        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false || $body === '') {
            throw new RuntimeException($error !== '' ? $error : 'Mailjet returned an empty response.');
        }

        $decoded = json_decode($body, true);
        if ($status < 200 || $status >= 300) {
            $message = is_array($decoded)
                ? (string)($decoded['ErrorMessage'] ?? $decoded['Messages'][0]['Errors'][0]['ErrorMessage'] ?? $body)
                : $body;
            throw new RuntimeException('Mailjet send failed: ' . $message);
        }

        return is_array($decoded) ? $decoded : ['raw' => $body];
    }
}
