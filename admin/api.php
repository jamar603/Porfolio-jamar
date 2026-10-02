<?php
declare(strict_types=1);

// Polled by notify.js: unread count + messages newer than ?since=<id>. Read-only.
require dirname(__DIR__) . '/php/bootstrap.php';
require dirname(__DIR__) . '/php/admin-service.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

if (!is_admin()) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}
// the session is only read from here on: release its lock so other tabs are not blocked
session_write_close();

$pdo = contact_database();
$since = max(0, (int) ($_GET['since'] ?? 0));
echo json_encode([
    'unread' => count_messages($pdo)['unread'],
    'latestId' => latest_message_id($pdo),
    'messages' => $since > 0 ? messages_since($pdo, $since) : [],
], JSON_UNESCAPED_UNICODE);
