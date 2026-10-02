<?php
// Copy this file to php/secrets.php (git-ignored) and fill in the values.
// On the web host, upload php/secrets.php by FTP like php/admin-config.php.
return [
    // ntfy: install the ntfy app, "Subscribe to topic", type the same name.
    // Pick a long random name: anyone who knows it can read the notifications.
    'ntfy_topic' => '',

    // Discord: channel settings > Integrations > Webhooks > New webhook > Copy webhook URL
    'discord_webhook' => '',
];
