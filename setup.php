<?php

declare(strict_types=1);

/**
 * Provision Fernwood Journal — a REAL WordPress, not a stand-in. Core from
 * wordpress.org, the official SQLite drop-in so there is no database server to
 * run, this repo's theme and mu-plugin, and a programmatic install (the same
 * `wp_install()` WP-CLI calls).
 *
 *   php setup.php --dir=/var/www/wp [--force]
 *
 * Idempotent and cached: an existing install is reused, and the core tarball is
 * kept next to it so a re-run needs no network. Prints the install path.
 */

const WP_TARBALL_URL = 'https://wordpress.org/latest.tar.gz';
const SQLITE_PLUGIN_URL = 'https://downloads.wordpress.org/plugin/sqlite-database-integration.zip';

$opts = getopt('', ['dir:', 'force', 'quiet']);
$dir = rtrim((string) ($opts['dir'] ?? sys_get_temp_dir() . '/fernwood'), '/');
$force = isset($opts['force']);
$quiet = isset($opts['quiet']);

function say(string $message): void
{
    global $quiet;
    if (!$quiet) {
        fwrite(STDERR, "[setup] $message\n");
    }
}

function fail(string $message): never
{
    fwrite(STDERR, "[setup] error: $message\n");
    exit(1);
}

/** Download to a path, keeping any previously fetched copy. */
function fetch(string $url, string $to): bool
{
    if (is_file($to) && filesize($to) > 0) {
        return true;
    }
    say("downloading $url");
    $context = stream_context_create(['http' => ['timeout' => 120, 'user_agent' => 'fernwood-demo']]);
    $data = @file_get_contents($url, false, $context);
    if ($data === false || $data === '') {
        return false;
    }
    return @file_put_contents($to, $data) !== false;
}

function rmrf(string $path): void
{
    if (!is_dir($path)) {
        @unlink($path);
        return;
    }
    foreach (scandir($path) ?: [] as $entry) {
        if ($entry !== '.' && $entry !== '..') {
            rmrf($path . '/' . $entry);
        }
    }
    @rmdir($path);
}

if ($force) {
    say('--force: removing the existing install');
    rmrf($dir . '/wp-includes');
    rmrf($dir . '/wp-admin');
    rmrf($dir . '/wp-content');
    @unlink($dir . '/wp-config.php');
    foreach (glob($dir . '/*.php') ?: [] as $file) {
        @unlink($file);
    }
}

@mkdir($dir, 0o755, true);
$cache = $dir . '/.cache';
@mkdir($cache, 0o755, true);

// --- 1. WordPress core ------------------------------------------------------

if (!is_file($dir . '/wp-settings.php')) {
    $tarball = $cache . '/wordpress.tar.gz';
    if (!fetch(WP_TARBALL_URL, $tarball)) {
        fail('could not download WordPress core (no network?)');
    }
    say('extracting core');
    // The tarball nests everything under wordpress/; strip it.
    exec(sprintf('tar -xzf %s -C %s --strip-components=1 2>&1', escapeshellarg($tarball), escapeshellarg($dir)), $out, $rc);
    if ($rc !== 0 || !is_file($dir . '/wp-settings.php')) {
        fail('core extraction failed: ' . implode("\n", $out));
    }
}

// --- 2. the SQLite drop-in (no database server for the demo) ----------------

$sqlitePlugin = $dir . '/wp-content/plugins/sqlite-database-integration';
if (!is_dir($sqlitePlugin)) {
    $zip = $cache . '/sqlite-database-integration.zip';
    if (!fetch(SQLITE_PLUGIN_URL, $zip)) {
        fail('could not download the SQLite integration plugin');
    }
    say('installing the SQLite integration plugin');
    // ext-zip isn't in every PHP build (the slim Docker images notably), so fall
    // back to the unzip binary rather than requiring an extension for a demo.
    if (class_exists(ZipArchive::class)) {
        $archive = new ZipArchive();
        if ($archive->open($zip) !== true) {
            fail('could not open the SQLite plugin zip');
        }
        $archive->extractTo($dir . '/wp-content/plugins');
        $archive->close();
    } else {
        exec(sprintf('unzip -qo %s -d %s 2>&1', escapeshellarg($zip), escapeshellarg($dir . '/wp-content/plugins')), $unzipOut, $unzipRc);
        if ($unzipRc !== 0) {
            fail('no ext-zip and unzip failed: ' . implode("\n", $unzipOut));
        }
    }
}
if (!is_dir($sqlitePlugin)) {
    fail('the SQLite plugin did not land where expected');
}

// The drop-in ships as db.copy with the implementation path left as a
// placeholder; this is exactly what WP-CLI's sqlite command does with it.
$dropIn = $dir . '/wp-content/db.php';
if (!is_file($dropIn)) {
    $template = @file_get_contents($sqlitePlugin . '/db.copy');
    if ($template === false) {
        fail('the SQLite plugin has no db.copy template');
    }
    $rendered = str_replace(
        ["'{SQLITE_IMPLEMENTATION_FOLDER_PATH}'", "'{SQLITE_PLUGIN}'"],
        ["'" . $sqlitePlugin . "'", "'sqlite-database-integration/load.php'"],
        $template,
    );
    @file_put_contents($dropIn, $rendered);
    say('wrote the wp-content/db.php drop-in');
}

// --- 3. wp-config.php -------------------------------------------------------

if (!is_file($dir . '/wp-config.php')) {
    say('writing wp-config.php');
    $salts = '';
    foreach (['AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT'] as $name) {
        $salts .= sprintf("define('%s', '%s');\n", $name, bin2hex(random_bytes(24)));
    }
    $plugin = var_export(getenv('SARCIO_PLUGIN_DIR') ?: __DIR__ . '/vendor/sarcio/wordpress', true);
    // The DB_* constants are inert under the SQLite drop-in, but core still
    // expects them to be defined.
    $config = <<<PHP
    <?php
    // Generated by setup.php — the Fernwood Journal demo install.
    define('DB_NAME', 'fernwood');
    define('DB_USER', 'fernwood');
    define('DB_PASSWORD', 'fernwood');
    define('DB_HOST', 'localhost');
    define('DB_CHARSET', 'utf8mb4');
    define('DB_COLLATE', '');
    {$salts}
    \$table_prefix = 'wp_';

    define('WP_DEBUG', true);
    define('WP_DEBUG_DISPLAY', false);
    define('DISABLE_WP_CRON', true);
    define('AUTOMATIC_UPDATER_DISABLED', true);
    define('WP_AUTO_UPDATE_CORE', false);

    // Follow the host the visitor used, under the path the site is served at
    // (SARCIO_BASE_PATH, e.g. /fernwood), so one image works on an ad-hoc port,
    // behind the local stack's nginx, and behind TLS on the public box — without
    // WordPress canonical-redirecting to a stale siteurl. The scheme comes from
    // the proxy when there is one, so asset URLs match the page.
    \$fernwood_base = rtrim((string) getenv('SARCIO_BASE_PATH'), '/');
    \$fernwood_host = \$_SERVER['HTTP_HOST'] ?? 'localhost';
    \$fernwood_scheme = strtolower(\$_SERVER['HTTP_X_FORWARDED_PROTO'] ?? (empty(\$_SERVER['HTTPS']) ? 'http' : 'https'));
    if (\$fernwood_scheme === 'https') {
        \$_SERVER['HTTPS'] = 'on';
    }
    define('WP_HOME', \$fernwood_scheme . '://' . \$fernwood_host . \$fernwood_base);
    define('WP_SITEURL', WP_HOME);

    // --- Sarcio, pinned exactly as a real deploy would ---------------------
    // Every setting is constant-pinnable, so the demo writes no settings to the
    // database and the settings screen shows each field as deploy-managed.
    define('FERNWOOD_SARCIO_PLUGIN', {$plugin});
    define('SARCIO_SITE_KEY', getenv('SARCIO_SITE_KEY') ?: 'pk_demo_fernwood');
    define('SARCIO_API_URL', getenv('SARCIO_API_URL') ?: 'http://control.sarcio.local');
    define('SARCIO_WIDGET_SRC', getenv('SARCIO_WIDGET_SRC') ?: SARCIO_API_URL . '/sarcio.js');
    // The demo shows the reporter to every visitor; a real public site should not.
    define('SARCIO_WIDGET_AUDIENCE', 'everyone');
    define('SARCIO_SERVER_SCOPE', 'all');
    define('SARCIO_SIDECAR_DSN', getenv('SARCIO_SIDECAR_DSN') ?: 'unix:///run/sarcio/fernwood.sock');
    define('SARCIO_SHIM_TOKEN', getenv('SARCIO_SHIM_TOKEN') ?: '');

    if (!defined('ABSPATH')) {
        define('ABSPATH', __DIR__ . '/');
    }
    require_once ABSPATH . 'wp-settings.php';
    PHP;
    @file_put_contents($dir . '/wp-config.php', preg_replace('/^    /m', '', $config));
}

// --- 4. this repo's theme + mu-plugin --------------------------------------

/** Copy a directory tree, replacing what is there (re-running picks up edits). */
function copy_tree(string $from, string $to): void
{
    @mkdir($to, 0o755, true);
    foreach (scandir($from) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        is_dir("$from/$entry") ? copy_tree("$from/$entry", "$to/$entry") : copy("$from/$entry", "$to/$entry");
    }
}
say('installing the Fernwood theme and newsletter mu-plugin');
copy_tree(__DIR__ . '/wp-content/themes/fernwood', $dir . '/wp-content/themes/fernwood');
copy_tree(__DIR__ . '/wp-content/mu-plugins', $dir . '/wp-content/mu-plugins');

// --- 5. install ------------------------------------------------------------

define('WP_INSTALLING', true);
define('WP_USE_THEMES', false);
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';

require_once $dir . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/upgrade.php';

if (!is_blog_installed()) {
    say('installing WordPress');
    // A random admin password: nobody signs in to wp-admin for this demo, and a
    // known one would be a standing credential on a public host.
    $result = wp_install('Fernwood Journal', 'admin', 'admin@fernwood.test', false, '', bin2hex(random_bytes(18)));
    if (is_wp_error($result)) {
        fail('wp_install failed: ' . $result->get_error_message());
    }
}

switch_theme('fernwood');

// Pretty permalinks, so <base>/wp-json/… is a real path — which is what makes
// the route id the browser sees (`POST /fernwood/wp-json/fernwood/v1/subscribe`)
// the same one the patch targets.
if (get_option('permalink_structure') !== '/%postname%/') {
    say('enabling pretty permalinks');
    update_option('permalink_structure', '/%postname%/');
    require_once ABSPATH . 'wp-includes/class-wp-rewrite.php';
    $GLOBALS['wp_rewrite']->init();
    $GLOBALS['wp_rewrite']->flush_rules(true);
}

say('ready at ' . $dir);
echo $dir . "\n";
