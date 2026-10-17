<?php

declare(strict_types=1);

/**
 * Fernwood Nursery theme setup. A child of Twenty Twenty-Five, so WooCommerce's
 * block templates (shop, product, cart, checkout) come from the parent and
 * WooCommerce itself; this theme adds the nursery's colours, type, header,
 * footer and front page.
 */

defined('ABSPATH') || exit;

add_action('wp_enqueue_scripts', static function (): void {
    wp_enqueue_style('fernwood', get_stylesheet_uri(), [], wp_get_theme()->get('Version'));
});

add_action('after_setup_theme', static function (): void {
    add_theme_support('woocommerce');
});

// The front page's canonical address is the bare base (`/fernwood`, which is
// what WP_HOME holds), but WordPress' canonical redirect always trailing-slashes
// the front page, and the router 301s the slashed form back, so the two would
// loop. Drop that slash from a front-page redirect (so `/fernwood/index.php`
// lands on `/fernwood` in one hop), and skip the redirect entirely when the
// slash was all it would change. A site served at the host root keeps its `/`.
add_filter('redirect_canonical', static function ($redirect, $requested) {
    if (!is_string($redirect) || !is_front_page()) {
        return $redirect;
    }
    [$url, $query] = array_pad(explode('?', $redirect, 2), 2, null);
    if (trim((string) wp_parse_url($url, PHP_URL_PATH), '/') !== '') {
        $redirect = untrailingslashit($url) . ($query === null ? '' : '?' . $query);
    }
    return $redirect === $requested ? false : $redirect;
}, 10, 2);
