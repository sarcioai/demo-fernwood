<?php
/**
 * Plugin Name: Fernwood Newsletter
 * Description: The Sunday Letter sign-up route, plus the Sarcio plugin loaded as a must-use plugin.
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

// The Sarcio plugin is a Composer dependency, loaded before anything else so
// its REST and request interceptors are registered in time. Every setting is
// pinned in wp-config.php, so there is nothing to activate or configure.
if (defined('FERNWOOD_SARCIO_PLUGIN') && is_file(FERNWOOD_SARCIO_PLUGIN . '/sarcio.php')) {
    require_once FERNWOOD_SARCIO_PLUGIN . '/sarcio.php';
}

/** The journal's newsletters, by list id. */
const FERNWOOD_LISTS = ['sunday-letter', 'field-notes'];

add_action('rest_api_init', static function (): void {
    register_rest_route('fernwood/v1', '/subscribe', [
        'callback' => 'fernwood_subscribe',
        'methods' => 'POST',
        'permission_callback' => '__return_true',
    ]);

    // Introspection: which routes currently carry an active server patch.
    register_rest_route('fernwood/v1', '/status', [
        'callback' => static fn (): WP_REST_Response => new WP_REST_Response([
            'patchedRoutes' => function_exists('sarcio_patched_routes') ? sarcio_patched_routes() : [],
        ]),
        'methods' => 'GET',
        'permission_callback' => '__return_true',
    ]);
});

/**
 * `POST /wp-json/fernwood/v1/subscribe`. WordPress hands this the request AFTER
 * Sarcio has applied any live verdict, so a `defaultValue` server patch supplies
 * `list` before this code runs.
 */
function fernwood_subscribe(WP_REST_Request $request): WP_REST_Response
{
    $body = $request->get_json_params();
    $body = is_array($body) ? $body : $request->get_body_params();

    $email = is_string($body['email'] ?? null) ? sanitize_email($body['email']) : '';
    if (!is_email($email)) {
        return new WP_REST_Response(['error' => 'a valid email is required'], 400);
    }

    // BUG: when the journal added a second newsletter (Field Notes), this route
    // started requiring which `list` to join — but the front page's Sunday Letter
    // form was never updated to send one, so every sign-up is refused.
    $list = is_string($body['list'] ?? null) ? sanitize_key($body['list']) : '';
    if (!in_array($list, FERNWOOD_LISTS, true)) {
        return new WP_REST_Response(['error' => 'list is required'], 400);
    }

    return new WP_REST_Response(['ok' => true, 'list' => $list, 'subscribed' => $email], 201);
}
