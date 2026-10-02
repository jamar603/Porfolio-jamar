<?php
declare(strict_types=1);

// Creates or replaces the admin account used by /admin/.
// Usage : php php/create-admin.php <identifiant> <mot-de-passe>
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

[, $username, $password] = $argv + [null, null, null];
if (!$username || !$password) {
    fwrite(STDERR, "Usage : php php/create-admin.php <identifiant> <mot-de-passe>\n");
    exit(1);
}
if (mb_strlen($password) < 12) {
    fwrite(STDERR, "Le mot de passe doit contenir au moins 12 caractères.\n");
    exit(1);
}

$credentials = ['username' => $username, 'password_hash' => password_hash($password, PASSWORD_DEFAULT)];
$content = "<?php\n// Généré par php/create-admin.php : ne pas commiter, envoyer par FTP sur l'hébergeur.\nreturn "
    . var_export($credentials, true) . ";\n";
file_put_contents(__DIR__ . '/admin-config.php', $content);

echo "Compte administrateur « $username » enregistré dans php/admin-config.php\n";
