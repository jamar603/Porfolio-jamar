<?php
declare(strict_types=1);

return [
    // where contact messages are delivered
    'recipient_email' => 'jamarcarty131@gmail.com',
    'recipient_name' => 'Jamar Carty',

    // sender used by mail(); most hosts require an address on your own domain
    'from_email' => 'no-reply@' . preg_replace('/^www\./', '', strtok($_SERVER['HTTP_HOST'] ?? 'localhost', ':')),

    // SQLite backup of every message (folder is created automatically, blocked from the web by .htaccess)
    'database_path' => dirname(__DIR__) . '/data/messages.sqlite',

    // anti-spam
    'rate_limit_max' => 3,          // messages allowed per visitor...
    'rate_limit_window' => 600,     // ...within this many seconds
    'min_fill_seconds' => 3,        // bots submit faster than this
];
