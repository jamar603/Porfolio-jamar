<?php
declare(strict_types=1);

require dirname(__DIR__) . '/php/bootstrap.php';
require dirname(__DIR__) . '/php/admin-service.php';

header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');
header("Content-Security-Policy: default-src 'self'; style-src 'self' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; img-src 'self' data:; script-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");

$pdo = contact_database();

/* ---------- actions (POST, then redirect) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $redirect = 'index.php';
    $action = (string) ($_POST['action'] ?? '');

    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
        set_flash(['status' => 'error', 'message' => 'La session a expiré, réessayez.'], 'admin_flash');
    } elseif ($action === 'login') {
        $result = admin_login($pdo, trim((string) ($_POST['username'] ?? '')), (string) ($_POST['password'] ?? ''));
        if ($result !== true) {
            set_flash(['status' => 'error', 'message' => $result], 'admin_flash');
        }
    } elseif (is_admin()) {
        $id = (int) ($_POST['id'] ?? 0);
        $query = http_build_query(array_filter([
            'filter' => ($_POST['filter'] ?? '') === 'unread' ? 'unread' : null,
            'page' => (int) ($_POST['page'] ?? 1) > 1 ? (int) $_POST['page'] : null,
        ]));
        $redirect = 'index.php' . ($query ? "?$query" : '');

        switch ($action) {
            case 'logout':
                admin_logout();
                set_flash(['status' => 'success', 'message' => 'Vous êtes déconnecté.'], 'admin_flash');
                $redirect = 'index.php';
                break;
            case 'read':
            case 'unread':
                set_message_read($pdo, $id, $action === 'read');
                $redirect .= "#message-$id";
                break;
            case 'delete':
                delete_message($pdo, $id);
                set_flash(['status' => 'success', 'message' => 'Message supprimé.'], 'admin_flash');
                break;
        }
    }

    header("Location: $redirect", true, 303);
    exit;
}

/* ---------- view data ---------- */
$flash = take_flash('admin_flash');
$loggedIn = is_admin();
$hasAccount = admin_credentials() !== null;

if ($loggedIn) {
    $filter = ($_GET['filter'] ?? '') === 'unread' ? 'unread' : 'all';
    $counts = count_messages($pdo);
    $listed = $filter === 'unread' ? $counts['unread'] : $counts['total'];
    $pageCount = max(1, (int) ceil($listed / MESSAGES_PER_PAGE));
    $page = min(max(1, (int) ($_GET['page'] ?? 1)), $pageCount);
    $messages = list_messages($pdo, $filter === 'unread', $page);
}

$formatDate = static fn (int $timestamp): string => date('d/m/Y à H:i', $timestamp);
$pageUrl = static fn (string $filter, int $page = 1): string => 'index.php' . (($query = http_build_query(array_filter([
    'filter' => $filter === 'unread' ? 'unread' : null,
    'page' => $page > 1 ? $page : null,
]))) ? "?$query" : '');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title><?= $loggedIn ? 'Messages' : 'Connexion' ?> · Admin Jamar Carty</title>
<link rel="icon" href="../logo-jc.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="admin.css">
</head>
<body>

<?php if (!$loggedIn): ?>
<main class="login">
  <form class="login-card" method="post" action="index.php">
    <a class="brand" href="../index.php"><img src="../logo-jc.svg" alt="" width="30" height="30"> Jamar Carty</a>
    <h1>Espace administrateur</h1>
    <p class="muted">Connectez-vous pour lire les messages du formulaire de contact.</p>
    <?php if ($flash): ?>
      <p class="notice notice-<?= e($flash['status']) ?>" role="alert"><?= e($flash['message']) ?></p>
    <?php endif; ?>
    <?php if (!$hasAccount): ?>
      <p class="notice notice-error">Aucun compte administrateur. Dans le dossier du projet, lancez :<br><code>php php/create-admin.php &lt;identifiant&gt; &lt;mot-de-passe&gt;</code></p>
    <?php endif; ?>
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="login">
    <label for="username">Identifiant</label>
    <input id="username" name="username" type="text" autocomplete="username" required autofocus>
    <label for="password">Mot de passe</label>
    <input id="password" name="password" type="password" autocomplete="current-password" required>
    <button class="btn btn-primary" type="submit">Se connecter</button>
    <a class="back-link" href="../index.php">← Retour au portfolio</a>
  </form>
</main>

<?php else: ?>
<header class="topbar">
  <div class="wrap topbar-inner">
    <a class="brand" href="index.php"><img src="../logo-jc.svg" alt="" width="28" height="28"> Admin</a>
    <div class="topbar-actions">
      <a class="btn btn-ghost" href="../index.php" target="_blank" rel="noopener">Voir le site ↗</a>
      <form method="post" action="index.php">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <button class="btn btn-ghost" type="submit" name="action" value="logout">Déconnexion</button>
      </form>
    </div>
  </div>
</header>

<main class="wrap dashboard">
  <div class="dashboard-head">
    <div>
      <h1>Messages</h1>
      <p class="muted">Reçus depuis le formulaire de contact du portfolio.</p>
    </div>
    <div class="stats">
      <div class="stat"><span class="stat-value"><?= $counts['total'] ?></span><span class="stat-label">au total</span></div>
      <div class="stat stat-accent"><span class="stat-value"><?= $counts['unread'] ?></span><span class="stat-label">non lu<?= $counts['unread'] > 1 ? 's' : '' ?></span></div>
    </div>
  </div>

  <?php if ($flash): ?>
    <p class="notice notice-<?= e($flash['status']) ?>" role="status"><?= e($flash['message']) ?></p>
  <?php endif; ?>

  <nav class="tabs" aria-label="Filtrer les messages">
    <a class="tab<?= $filter === 'all' ? ' is-active' : '' ?>" href="<?= e($pageUrl('all')) ?>"<?= $filter === 'all' ? ' aria-current="page"' : '' ?>>Tous <span><?= $counts['total'] ?></span></a>
    <a class="tab<?= $filter === 'unread' ? ' is-active' : '' ?>" href="<?= e($pageUrl('unread')) ?>"<?= $filter === 'unread' ? ' aria-current="page"' : '' ?>>Non lus <span><?= $counts['unread'] ?></span></a>
  </nav>

  <?php if (!$messages): ?>
    <div class="empty">
      <p class="empty-title"><?= $filter === 'unread' ? 'Aucun message non lu' : 'Aucun message pour le moment' ?></p>
      <p class="muted"><?= $filter === 'unread' ? 'Vous êtes à jour.' : 'Les messages envoyés depuis le formulaire de contact apparaîtront ici.' ?></p>
    </div>
  <?php else: ?>
    <ul class="messages">
      <?php foreach ($messages as $message):
          $unread = $message['read_at'] === null;
          $replySubject = rawurlencode('Re: ' . $message['subject']);
      ?>
      <li class="message<?= $unread ? ' is-unread' : '' ?>" id="message-<?= (int) $message['id'] ?>">
        <details>
          <summary>
            <span class="message-dot" aria-hidden="true"></span>
            <span class="message-from">
              <strong><?= e($message['name']) ?></strong>
              <span class="muted"><?= e($message['email']) ?></span>
            </span>
            <span class="message-subject"><?= $unread ? '<span class="visually-hidden">Non lu : </span>' : '' ?><?= e($message['subject']) ?></span>
            <time class="message-date" datetime="<?= date('c', (int) $message['created_at']) ?>"><?= e($formatDate((int) $message['created_at'])) ?></time>
          </summary>
          <div class="message-body">
            <p class="message-text"><?= nl2br(e($message['message'])) ?></p>
            <?php if (!(int) $message['mailed']): ?>
              <p class="tag-warning">E-mail non envoyé : message disponible uniquement ici.</p>
            <?php endif; ?>
            <div class="message-actions">
              <a class="btn btn-primary" href="mailto:<?= e($message['email']) ?>?subject=<?= e($replySubject) ?>">Répondre</a>
              <form method="post" action="index.php">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int) $message['id'] ?>">
                <input type="hidden" name="filter" value="<?= e($filter) ?>">
                <input type="hidden" name="page" value="<?= $page ?>">
                <button class="btn btn-ghost" type="submit" name="action" value="<?= $unread ? 'read' : 'unread' ?>"><?= $unread ? 'Marquer comme lu' : 'Marquer comme non lu' ?></button>
              </form>
              <form method="post" action="index.php" data-confirm="Supprimer définitivement le message de <?= e($message['name']) ?> ?">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int) $message['id'] ?>">
                <input type="hidden" name="filter" value="<?= e($filter) ?>">
                <input type="hidden" name="page" value="<?= $page ?>">
                <button class="btn btn-danger" type="submit" name="action" value="delete">Supprimer</button>
              </form>
            </div>
          </div>
        </details>
      </li>
      <?php endforeach; ?>
    </ul>

    <?php if ($pageCount > 1): ?>
      <nav class="pagination" aria-label="Pages">
        <?php if ($page > 1): ?><a class="btn btn-ghost" href="<?= e($pageUrl($filter, $page - 1)) ?>">← Précédent</a><?php endif; ?>
        <span class="muted">Page <?= $page ?> / <?= $pageCount ?></span>
        <?php if ($page < $pageCount): ?><a class="btn btn-ghost" href="<?= e($pageUrl($filter, $page + 1)) ?>">Suivant →</a><?php endif; ?>
      </nav>
    <?php endif; ?>
  <?php endif; ?>
</main>
<?php endif; ?>

<script src="admin.js"></script>
</body>
</html>
