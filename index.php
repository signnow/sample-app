<?php
// Simple .env loader
if (file_exists(__DIR__ . '/.env')) {
    foreach (file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }
        [$name, $value] = array_map('trim', explode('=', $line, 2));
        $_ENV[$name] = trim($value, "'\"");
    }
}

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($path === '/samples/EmbeddedSignerConsentForm') {
    require __DIR__ . '/samples/EmbeddedSignerConsentForm/Sample.php';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        handlePost();
    } else {
        handleGet();
    }
    exit;
}

http_response_code(404);
echo 'Not found';
