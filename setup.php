<?php

declare(strict_types=1);

/**
 * Provision Fernwood Nursery — a REAL WordPress and WooCommerce store, not a
 * stand-in. Core from wordpress.org, the official SQLite drop-in so there is no
 * database server to run, WooCommerce from wordpress.org, this repo's theme and
 * mu-plugins, a programmatic install (the same `wp_install()` WP-CLI calls), and
 * then provision.php, which stocks the store.
 *
 *   php setup.php --dir=/var/www/wp [--data-dir=/var/lib/fernwood]
 *                 [--cache-dir=/tmp/fernwood-cache] [--force]
 *
 * Idempotent and cached: an existing install is reused, and the downloads are
 * kept in the cache directory so a re-run needs no network. Prints the install
 * path.
 *
 * `--dir` is the web root. The SQLite database (`--data-dir`, the drop-in's
 * `DB_DIR`) and the download cache (`--cache-dir`) must live OUTSIDE it: PHP's
 * built-in server ignores the drop-in's `.htaccess`, so anything under the web
 * root is downloadable.
 */

const WP_TARBALL_URL = 'https://wordpress.org/latest.tar.gz';
const SQLITE_PLUGIN_URL = 'https://downloads.wordpress.org/plugin/sqlite-database-integration.zip';
const WOOCOMMERCE_VERSION = '11.2.1';
const WOOCOMMERCE_URL = 'https://downloads.wordpress.org/plugin/woocommerce.' . WOOCOMMERCE_VERSION . '.zip';

$opts = getopt('', ['dir:', 'data-dir:', 'cache-dir:', 'force', 'quiet']);
$dir = rtrim((string) ($opts['dir'] ?? sys_get_temp_dir() . '/fernwood'), '/');
$dataDir = rtrim((string) ($opts['data-dir'] ?? sys_get_temp_dir() . '/fernwood-data'), '/');
$cache = rtrim((string) ($opts['cache-dir'] ?? sys_get_temp_dir() . '/fernwood-cache'), '/');
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
    @unlink($dataDir . '/.ht.sqlite');
    foreach (glob($dir . '/*.php') ?: [] as $file) {
        @unlink($file);
    }
}

/** True when $path is $root or anything beneath it (neither need exist yet). */
function is_within(string $path, string $root): bool
{
    $normalize = static fn (string $p): string => rtrim((string) (realpath($p) ?: $p), '/') . '/';
    return str_starts_with($normalize($path), $normalize($root));
}

@mkdir($dir, 0o755, true);
@mkdir($dataDir, 0o700, true);
@mkdir($cache, 0o755, true);
foreach (['--data-dir' => $dataDir, '--cache-dir' => $cache] as $flag => $path) {
    if (is_within($path, $dir) || is_within($dir, $path)) {
        fail("$flag ($path) must be outside the web root ($dir)");
    }
}

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

// --- 2b. WooCommerce, pinned ------------------------------------------------

/** Unzip a plugin archive into wp-content/plugins (ext-zip, else the unzip binary). */
function install_plugin_zip(string $zip, string $pluginsDir): void
{
    if (class_exists(ZipArchive::class)) {
        $archive = new ZipArchive();
        if ($archive->open($zip) !== true) {
            fail("could not open $zip");
        }
        $archive->extractTo($pluginsDir);
        $archive->close();
        return;
    }
    exec(sprintf('unzip -qo %s -d %s 2>&1', escapeshellarg($zip), escapeshellarg($pluginsDir)), $out, $rc);
    if ($rc !== 0) {
        fail('no ext-zip and unzip failed: ' . implode("\n", $out));
    }
}

if (!is_file($dir . '/wp-content/plugins/woocommerce/woocommerce.php')) {
    $zip = $cache . '/woocommerce.' . WOOCOMMERCE_VERSION . '.zip';
    if (!fetch(WOOCOMMERCE_URL, $zip)) {
        fail('could not download WooCommerce ' . WOOCOMMERCE_VERSION);
    }
    say('installing WooCommerce ' . WOOCOMMERCE_VERSION);
    install_plugin_zip($zip, $dir . '/wp-content/plugins');
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
    $dbDir = var_export($dataDir . '/', true);
    $plugin = var_export(getenv('SARCIO_PLUGIN_DIR') ?: __DIR__ . '/vendor/sarcio/wordpress', true);
    // The DB_* constants are inert under the SQLite drop-in, but core still
    // expects them to be defined.
    $config = <<<PHP
    <?php
    // Generated by setup.php — the Fernwood Nursery demo install.
    define('DB_NAME', 'fernwood');
    define('DB_USER', 'fernwood');
    define('DB_PASSWORD', 'fernwood');
    define('DB_HOST', 'localhost');
    define('DB_CHARSET', 'utf8mb4');
    define('DB_COLLATE', '');
    // The SQLite file lives outside the web root: the built-in server ignores the
    // drop-in's .htaccess, so wp-content/database/ would be downloadable.
    define('DB_DIR', {$dbDir});
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

    // The front page is served at the bare base (`/fernwood` without a trailing
    // slash, which the router makes canonical) while WordPress would scope its
    // cookies to `/fernwood/`, so a signed-in visitor looked signed out on the
    // front page alone. A cookie path without the slash matches both forms.
    define('COOKIEPATH', \$fernwood_base === '' ? '/' : \$fernwood_base);
    define('SITECOOKIEPATH', COOKIEPATH);
    define('ADMIN_COOKIE_PATH', (\$fernwood_base === '' ? '' : \$fernwood_base) . '/wp-admin');
    define('PLUGINS_COOKIE_PATH', (\$fernwood_base === '' ? '' : \$fernwood_base) . '/wp-content/plugins');

    // --- Sarcio, pinned exactly as a real deploy would ---------------------
    // The two keys are saved as ordinary settings at start-up (connect.php),
    // the way a site owner pastes them in; everything else is pinned here.
    define('FERNWOOD_SARCIO_PLUGIN', {$plugin});
    define('SARCIO_API_URL', getenv('SARCIO_API_URL') ?: 'http://control.sarcio.local');
    define('SARCIO_WIDGET_SRC', getenv('SARCIO_WIDGET_SRC') ?: SARCIO_API_URL . '/sarcio.js');
    // The demo shows the reporter to every visitor; a real public site should not.
    define('SARCIO_WIDGET_AUDIENCE', 'everyone');
    define('SARCIO_SERVER_SCOPE', 'all');
    // Server-side fixes without a sidecar: the plugin fetches the site's signed
    // fixes itself with the secret key, exactly as on shared hosting. A sidecar
    // DSN, when one is set, takes over as the fast path.
    if (getenv('SARCIO_SIDECAR_DSN')) {
        define('SARCIO_SIDECAR_DSN', getenv('SARCIO_SIDECAR_DSN'));
        define('SARCIO_SHIM_TOKEN', getenv('SARCIO_SHIM_TOKEN') ?: '');
    }

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
say('installing the Fernwood theme and mu-plugins');
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
    // A random admin password unless the deployment supplies one: a known one
    // would be a standing credential on a public host.
    $result = wp_install('Fernwood Nursery', 'admin', 'admin@fernwood.test', false, '', getenv('FERNWOOD_ADMIN_PASSWORD') ?: bin2hex(random_bytes(18)));
    if (is_wp_error($result)) {
        fail('wp_install failed: ' . $result->get_error_message());
    }
}

switch_theme('fernwood');
update_option('active_plugins', ['woocommerce/woocommerce.php']);

// Pretty permalinks, so <base>/wp-json/… is a real path — which is what makes
// the route id the browser sees (`POST /fernwood/wp-json/wc/store/v1/checkout`)
// the same one the patch targets.
if (get_option('permalink_structure') !== '/%postname%/') {
    say('enabling pretty permalinks');
    update_option('permalink_structure', '/%postname%/');
    require_once ABSPATH . 'wp-includes/class-wp-rewrite.php';
    $GLOBALS['wp_rewrite']->init();
    $GLOBALS['wp_rewrite']->flush_rules(true);
}

// --- 6. stock the store, in a process where WooCommerce loads normally ------

passthru(sprintf('%s -d memory_limit=512M %s --dir=%s', escapeshellarg(PHP_BINARY), escapeshellarg(__DIR__ . '/provision.php'), escapeshellarg($dir)), $provisionRc);
if ($provisionRc !== 0) {
    fail('provision.php failed');
}

say('ready at ' . $dir);
echo $dir . "\n";
