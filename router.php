<?php
// Local server only: php -S localhost:8080 router.php
// The PHP built-in server ignores .htaccess, so this mirrors its rules: private folders stay private.
$path = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if (preg_match('#^/(php|data)(/|$)|/\.|\.(sqlite|ini|log|md|sql|bak|code-workspace)$#i', $path)) {
    http_response_code(403);
    exit('Accès interdit');
}
return false;
