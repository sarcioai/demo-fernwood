<?php
/**
 * Plugin Name: Sarcio (loader)
 * Description: Loads the Sarcio plugin, installed with Composer, as a must-use plugin.
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

// Loaded before anything else so its REST and request interceptors are
// registered in time. Every setting is pinned in wp-config.php, so there is
// nothing to activate or configure.
if (defined('FERNWOOD_SARCIO_PLUGIN') && is_file(FERNWOOD_SARCIO_PLUGIN . '/sarcio.php')) {
    require_once FERNWOOD_SARCIO_PLUGIN . '/sarcio.php';
}
