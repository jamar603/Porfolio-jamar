<?php
declare(strict_types=1);

const CONTACT_LIMITS = [
    'name' => [2, 80],
    'email' => [5, 254],
    'subject' => [3, 120],
    'message' => [10, 5000],
];

/**
 * Cleans and checks the submitted fields.
 * Returns [cleanData, errors] where errors is keyed by field name.
 */
function validate_contact(array $input): array
{
    $data = [];
    foreach (array_keys(CONTACT_LIMITS) as $field) {
        $value = trim((string) ($input[$field] ?? ''));
        // single-line fields must never carry line breaks (mail header injection)
        if ($field !== 'message') {
            $value = preg_replace('/[\r\n\t]+/', ' ', $value);
        }
        $data[$field] = $value;
    }

    $labels = ['name' => 'Le nom', 'email' => 'L’e-mail', 'subject' => 'L’objet', 'message' => 'Le message'];
    $errors = [];
    foreach (CONTACT_LIMITS as $field => [$min, $max]) {
        $length = mb_strlen($data[$field]);
        if ($length === 0) {
            $errors[$field] = $labels[$field] . ' est obligatoire.';
        } elseif ($length < $min) {
            $errors[$field] = $labels[$field] . " doit contenir au moins $min caractères.";
        } elseif ($length > $max) {
            $errors[$field] = $labels[$field] . " ne doit pas dépasser $max caractères.";
        }
    }
    if (!isset($errors['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Cette adresse e-mail n’est pas valide.';
    }

    return [$data, $errors];
}

function contact_database(): PDO
{
    $path = config('database_path');
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }
    if (!is_file($dir . '/.htaccess')) {
        file_put_contents($dir . '/.htaccess', "Require all denied\nDeny from all\n");
    }

    $pdo = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('CREATE TABLE IF NOT EXISTS messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL,
        subject TEXT NOT NULL,
        message TEXT NOT NULL,
        ip_hash TEXT NOT NULL,
        mailed INTEGER NOT NULL DEFAULT 0,
        read_at INTEGER NULL,
        created_at INTEGER NOT NULL
    )');
    // databases created before the admin page existed lack read_at
    $columns = array_column($pdo->query('PRAGMA table_info(messages)')->fetchAll(), 'name');
    if (!in_array('read_at', $columns, true)) {
        $pdo->exec('ALTER TABLE messages ADD COLUMN read_at INTEGER NULL');
    }
    return $pdo;
}

// the raw IP is never stored, only a salted hash used for rate limiting
function visitor_hash(): string
{
    return hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '|' . __DIR__);
}

function is_rate_limited(PDO $pdo, string $ipHash): bool
{
    $statement = $pdo->prepare('SELECT COUNT(*) FROM messages WHERE ip_hash = :ip AND created_at > :since');
    $statement->execute([':ip' => $ipHash, ':since' => time() - config('rate_limit_window')]);
    return (int) $statement->fetchColumn() >= config('rate_limit_max');
}

function store_message(PDO $pdo, array $data, string $ipHash): int
{
    $statement = $pdo->prepare('INSERT INTO messages (name, email, subject, message, ip_hash, created_at)
        VALUES (:name, :email, :subject, :message, :ip, :created)');
    $statement->execute([
        ':name' => $data['name'],
        ':email' => $data['email'],
        ':subject' => $data['subject'],
        ':message' => $data['message'],
        ':ip' => $ipHash,
        ':created' => time(),
    ]);
    return (int) $pdo->lastInsertId();
}

function mark_mailed(PDO $pdo, int $id): void
{
    $pdo->prepare('UPDATE messages SET mailed = 1 WHERE id = :id')->execute([':id' => $id]);
}

function send_contact_mail(array $data): bool
{
    $subject = mb_encode_mimeheader('[Portfolio] ' . $data['subject'], 'UTF-8', 'B');
    $body = "Nouveau message depuis le portfolio\n\n"
        . "Nom : {$data['name']}\n"
        . "E-mail : {$data['email']}\n"
        . "Objet : {$data['subject']}\n\n"
        . $data['message'] . "\n";
    $fromName = mb_encode_mimeheader('Portfolio Jamar Carty', 'UTF-8', 'B');
    $replyName = mb_encode_mimeheader($data['name'], 'UTF-8', 'B');
    $headers = [
        'From' => $fromName . ' <' . config('from_email') . '>',
        'Reply-To' => $replyName . ' <' . $data['email'] . '>',
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8',
        'Content-Transfer-Encoding' => '8bit',
        'X-Mailer' => 'PHP/' . PHP_VERSION,
    ];

    // mail() is unavailable on most local setups: the message stays saved in SQLite either way
    return @mail(config('recipient_email'), $subject, $body, $headers);
}
