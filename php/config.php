<?php
declare(strict_types=1);

return [
    // where contact messages are delivered
    'recipient_email' => 'jamarcarty131@gmail.com',
    'recipient_name' => 'Jamar Carty',

    // domains this site answers to; add the real one in php/secrets.php (see secrets.example.php)
    'allowed_hosts' => ['localhost', '127.0.0.1'],

    // sender used by mail(); most hosts require an address on your own domain
    // empty = no-reply@<first trusted domain>
    'from_email' => '',

    // SQLite backup of every message (folder is created automatically, blocked from the web by .htaccess)
    'database_path' => dirname(__DIR__) . '/data/messages.sqlite',

    // anti-spam
    'rate_limit_max' => 3,          // messages allowed per visitor...
    'rate_limit_window' => 600,     // ...within this many seconds
    'min_fill_seconds' => 3,        // bots submit faster than this

    // phone / Discord notifications: set the real values in php/secrets.php (see secrets.example.php)
    'ntfy_server' => 'https://ntfy.sh',
    'ntfy_topic' => '',
    'discord_webhook' => '',
];
