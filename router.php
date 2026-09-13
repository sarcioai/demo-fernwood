<?php

declare(strict_types=1);

/**
 * Router for PHP's built-in server, so the demo runs without Apache or nginx.
 *
 * WordPress is served under SARCIO_BASE_PATH (e.g. /fernwood): the entrypoint
 * links that path in the document root to the install. The router does what a
 * real web server's WordPress rules do — serve a file if one exists, otherwise
 * hand the request to `index.php` with the URI intact. Keeping the URI intact is
 * what makes `/fernwood/wp-json/fernwood/v1/subscribe` a real path here, exactly
 * as it is in production, so the route id Sarcio sees is the one a patch targets.
 *
 * The front page's canonical address is the bare base (`/fernwood`): it goes
 * straight to WordPress, and the slashed form (`/fernwood/`) 301s to it.
 */

$root = rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? getcwd()), '/');
$base = rtrim((string) getenv('SARCIO_BASE_PATH'), '/');
$path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$path = is_string($path) ? $path : '/';

$home = $base === '' ? '/' : $base;

if ($base !== '' && $path === $base . '/') {
    $query = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_QUERY);
    header('Location: ' . $home . (is_string($query) && $query !== '' ? '?' . $query : ''), true, 301);
    return true;
}
if ($path !== $home && !str_starts_with($path, $base . '/')) {
    http_response_code(404);
    echo 'not found';
    return true;
}

$candidate = $root . $path;
if (!str_contains($path, '..') && $path !== $home) {
    if (is_file($candidate) || (is_dir($candidate) && is_file(rtrim($candidate, '/') . '/index.php'))) {
        return false; // let the built-in server serve (and execute) it
    }
}

$_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = $base . '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $root . $base . '/index.php';
require $root . $base . '/index.php';
