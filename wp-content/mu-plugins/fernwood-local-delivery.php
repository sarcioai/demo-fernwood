<?php
/**
 * Plugin Name: Fernwood Local Delivery
 * Description: Keeps delivery orders inside the area the nursery's van covers.
 * Version: 2.1.0
 * Author: Fernwood Nursery
 */

declare(strict_types=1);

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;

defined('ABSPATH') || exit;

/**
 * The ZIP codes the van delivers to. Since 2.1 the list is imported from the
 * routing spreadsheet, which stores each ZIP as a number.
 *
 * @return list<int>
 */
function fernwood_delivery_zips(): array
{
    return array_map('intval', (array) get_option('fernwood_delivery_zips', []));
}

/**
 * Refuse an order whose shipping ZIP is outside the delivery area.
 *
 * The site skips this check while a Sarcio fix says so (`sarcio_skips()`), so a
 * reviewer can unblock checkout without a deploy if the check misbehaves.
 */
function fernwood_check_delivery_area(string $postcode): ?string
{
    if (function_exists('sarcio_skips') && sarcio_skips('delivery_area_check')) {
        return null;
    }

    $zip = substr(preg_replace('/\D/', '', $postcode) ?? '', 0, 5);
    if (!in_array($zip, fernwood_delivery_zips(), true)) {
        return sprintf(
            'Sorry, we don’t deliver to %s yet. Local delivery covers the Portland metro area.',
            $zip === '' ? 'that address' : $zip,
        );
    }
    return null;
}

// The block checkout (Store API): refuse the order before payment is taken.
add_action('woocommerce_store_api_checkout_update_order_from_request', static function (WC_Order $order): void {
    $error = fernwood_check_delivery_area((string) $order->get_shipping_postcode());
    if ($error !== null) {
        throw new RouteException('fernwood_delivery_area', $error, 400);
    }
});

// The classic checkout, for themes that still use the shortcode.
add_action('woocommerce_after_checkout_validation', static function (array $data, WP_Error $errors): void {
    $error = fernwood_check_delivery_area((string) ($data['shipping_postcode'] ?? $data['billing_postcode'] ?? ''));
    if ($error !== null) {
        $errors->add('fernwood_delivery_area', $error);
    }
}, 10, 2);
