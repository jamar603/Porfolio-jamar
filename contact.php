<?php
declare(strict_types=1);

require __DIR__ . '/php/bootstrap.php';
require __DIR__ . '/php/contact-service.php';

// fetch() asks for JSON; a plain form post (JavaScript disabled) gets a redirect back to the page
$wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

function respond(bool $wantsJson, int $status, bool $ok, string $message, array $errors = [], array $old = []): never
{
    if ($wantsJson) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => $ok, 'message' => $message, 'errors' => (object) $errors], JSON_UNESCAPED_UNICODE);
        exit;
    }
    set_flash(['status' => $ok ? 'success' : 'error', 'message' => $message, 'errors' => $errors, 'old' => $ok ? [] : $old]);
    header('Location: index.php#contact', true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    respond($wantsJson, 405, false, 'Méthode non autorisée.');
}

if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
    respond($wantsJson, 419, false, 'La session a expiré. Rechargez la page puis réessayez.');
}

// honeypot field is invisible to people; bots fill it in
$isBot = trim((string) ($_POST['website'] ?? '')) !== ''
    || time() - (int) ($_SESSION['form_started'] ?? 0) < config('min_fill_seconds');
if ($isBot) {
    // pretend it worked so the bot learns nothing
    respond($wantsJson, 200, true, 'Merci ! Votre message a bien été envoyé.');
}

[$data, $errors] = validate_contact($_POST);
if ($errors) {
    respond($wantsJson, 422, false, 'Certains champs sont à corriger.', $errors, $data);
}

try {
    $pdo = contact_database();
    $ipHash = visitor_hash();
    if (is_rate_limited($pdo, $ipHash)) {
        respond($wantsJson, 429, false, 'Trop de messages envoyés. Réessayez dans quelques minutes.', [], $data);
    }
    $id = store_message($pdo, $data, $ipHash);
} catch (PDOException $exception) {
    error_log('Contact form storage failed: ' . $exception->getMessage());
    respond($wantsJson, 500, false, 'Une erreur est survenue. Écrivez-moi directement à ' . config('recipient_email') . '.', [], $data);
}

if (send_contact_mail($data)) {
    mark_mailed($pdo, $id);
} else {
    error_log("Contact form: mail() failed for message #$id (saved in SQLite).");
}

respond($wantsJson, 200, true, 'Merci ! Votre message a bien été envoyé. Je vous réponds rapidement.');
