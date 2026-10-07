<?php
declare(strict_types=1);

// Builds a static copy of the portfolio for GitHub Pages (no PHP there):
//   php build-static.php [output-dir]   (default: _site)
// The contact form is sent through FormSubmit instead of contact.php, and the admin area is left out.

$root = __DIR__;
$out = $argv[1] ?? $root . '/_site';
$contactEndpoint = 'https://formsubmit.co/ajax/jamarcarty131@gmail.com';

// render index.php as a first-time visitor would see it
ob_start();
require $root . '/index.php';
$html = ob_get_clean();

$html = preg_replace('/\s*<input type="hidden" name="csrf_token"[^>]*>/', '', $html);
$html = str_replace('action="contact.php"', 'action="' . $contactEndpoint . '"', $html);
// FormSubmit options: its own honeypot field name, no captcha page, a readable e-mail
$html = str_replace('name="website"', 'name="_honey"', $html);
$html = str_replace(
    '<div class="field-trap"',
    "<input type=\"hidden\" name=\"_subject\" value=\"Nouveau message depuis le portfolio\">\n"
    . "          <input type=\"hidden\" name=\"_captcha\" value=\"false\">\n"
    . "          <input type=\"hidden\" name=\"_template\" value=\"table\">\n"
    . '          <div class="field-trap"',
    $html
);
$html = preg_replace('/\s*<a class="footer-admin".*?<\/a>/s', '', $html);

if (is_dir($out)) {
    exit("Output folder already exists: $out\n");
}
mkdir($out, 0777, true);
file_put_contents("$out/index.html", $html);
touch("$out/.nojekyll");

// static files served as-is; PHP, admin and private data stay out
$skip = ['/^\./', '/\.php$/', '/^admin\//', '/^php\//', '/^data\//', '/^_site\//', '/\.code-workspace$/', '/^README\.md$/'];
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($files as $file) {
    $path = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
    foreach ($skip as $pattern) {
        if (preg_match($pattern, $path)) continue 2;
    }
    @mkdir(dirname("$out/$path"), 0777, true);
    $content = file_get_contents($file->getPathname());
    // old redirect pages point to index.php
    if (str_ends_with($path, '.html')) {
        $content = str_replace('index.php', './', $content);
    }
    file_put_contents("$out/$path", $content);
}

echo "Static site written to $out\n";
