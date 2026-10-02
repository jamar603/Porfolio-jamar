<?php
declare(strict_types=1);

date_default_timezone_set('Europe/Paris');

// security headers for every PHP page; admin/index.php overrides some with stricter values
if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header_remove('X-Powered-By');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; img-src 'self' data: https://static.wixstatic.com; connect-src 'self'; form-action 'self'; frame-ancestors 'self'; base-uri 'self'; object-src 'none'");
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000');
    }
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    // refuse session ids the server did not create (session fixation)
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

function config(string $key)
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/config.php';
        // private values (webhook URLs, ntfy topic) live in a git-ignored file
        if (is_file(__DIR__ . '/secrets.php')) {
            $config = array_replace($config, require __DIR__ . '/secrets.php');
        }
    }
    return $config[$key] ?? null;
}

// the Host header is sent by the visitor: only trust it when it is one of our domains,
// otherwise links in notifications could point to an attacker's site
function site_host(): string
{
    $allowed = config('allowed_hosts') ?: ['localhost'];
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    return in_array(preg_replace('/:\d+$/', '', $host), $allowed, true) ? $host : $allowed[0];
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_is_valid(?string $token): bool
{
    return is_string($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// one-shot message carried across the redirect after a form post (no-JS fallback)
function set_flash(array $flash, string $key = 'flash'): void
{
    $_SESSION[$key] = $flash;
}

function take_flash(string $key = 'flash'): array
{
    $flash = $_SESSION[$key] ?? [];
    unset($_SESSION[$key]);
    return $flash;
}
