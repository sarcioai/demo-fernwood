<?php

declare(strict_types=1);

/**
 * Fernwood theme setup. Nothing here knows about Sarcio: the plugin enqueues its
 * reporter widget onto every page through `wp_enqueue_scripts`, exactly as it
 * would on any theme.
 */

defined('ABSPATH') || exit;

add_action('after_setup_theme', static function (): void {
    add_theme_support('title-tag');
});

add_action('wp_enqueue_scripts', static function (): void {
    wp_enqueue_style('fernwood', get_stylesheet_uri(), [], wp_get_theme()->get('Version'));
});
