<?php
declare(strict_types=1);

// Push notifications (ntfy, Discord) sent when a contact message is saved.
// A failing service is only logged: the visitor's message is already stored.

const NOTIFY_TIMEOUT = 4; // seconds, keeps the contact form fast if a service is down

function admin_url(): string
{
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $base . '/admin/';
}

function http_post(string $url, string $body, array $headers): bool
{
    $context = stream_context_create(['http' => [
        'method' => 'POST',
        'header' => implode("\r\n", $headers),
        'content' => $body,
        'timeout' => NOTIFY_TIMEOUT,
        'ignore_errors' => true,
    ]]);
    $response = @file_get_contents($url, false, $context);
    $status = isset($http_response_header[0]) && preg_match('#\s(\d{3})\s#', $http_response_header[0], $match) ? (int) $match[1] : 0;
    if ($response === false || $status < 200 || $status >= 300) {
        error_log("Notification failed ($status) for " . parse_url($url, PHP_URL_HOST));
        return false;
    }
    return true;
}

function excerpt(string $text, int $length): string
{
    $text = preg_replace('/\s+/', ' ', trim($text));
    return mb_strlen($text) > $length ? mb_substr($text, 0, $length - 1) . '…' : $text;
}

// ntfy: only name + subject leave the server (a topic is readable by anyone who knows its name)
function notify_ntfy(array $data): void
{
    $topic = (string) config('ntfy_topic');
    if ($topic === '') {
        return;
    }
    http_post(rtrim((string) config('ntfy_server'), '/') . '/' . rawurlencode($topic), excerpt($data['subject'], 140), [
        'Content-Type: text/plain; charset=utf-8',
        // HTTP headers must stay ASCII: RFC 2047 encoding lets ntfy show accents in the title
        'Title: =?UTF-8?B?' . base64_encode('Nouveau message de ' . excerpt($data['name'], 60)) . '?=',
        'Tags: envelope',
        'Priority: high',
        'Click: ' . admin_url(),
    ]);
}

function notify_discord(array $data, int $id): void
{
    $webhook = (string) config('discord_webhook');
    if ($webhook === '') {
        return;
    }
    $payload = [
        'username' => 'Portfolio',
        // never ping @everyone/@here even if a visitor types it
        'allowed_mentions' => ['parse' => []],
        'embeds' => [[
            'title' => '📩 ' . excerpt($data['subject'], 200),
            'url' => admin_url() . '#message-' . $id,
            'description' => excerpt($data['message'], 400),
            'color' => 0xB7FF35,
            'fields' => [
                ['name' => 'De', 'value' => excerpt($data['name'], 100), 'inline' => true],
                ['name' => 'E-mail', 'value' => excerpt($data['email'], 100), 'inline' => true],
            ],
            'timestamp' => date('c'),
        ]],
    ];
    http_post($webhook, json_encode($payload, JSON_UNESCAPED_UNICODE), ['Content-Type: application/json']);
}

function notify_new_message(array $data, int $id): void
{
    notify_ntfy($data);
    notify_discord($data, $id);
}
