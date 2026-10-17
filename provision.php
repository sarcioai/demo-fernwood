<?php

declare(strict_types=1);

/**
 * Stock the Fernwood Nursery store: WooCommerce's own install, the store
 * settings, the delivery zone and payment method, the catalogue with its
 * photos, and the shop's pages. Run by setup.php once WooCommerce is active,
 * in a process of its own so WooCommerce has loaded the way it does on a
 * normal request.
 *
 *   php provision.php --dir=/var/www/wp
 *
 * Idempotent: a store already stocked at this version is left alone.
 */

const FERNWOOD_STORE_VERSION = '2026.10.1';

$opts = getopt('', ['dir:']);
$dir = rtrim((string) ($opts['dir'] ?? ''), '/');
if ($dir === '' || !is_file($dir . '/wp-load.php')) {
    fwrite(STDERR, "[provision] error: --dir must point at the install\n");
    exit(1);
}

$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';
define('WP_USE_THEMES', false);
require_once $dir . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

function say(string $message): void
{
    fwrite(STDERR, "[provision] $message\n");
}

if (!class_exists('WooCommerce')) {
    fwrite(STDERR, "[provision] error: WooCommerce is not active\n");
    exit(1);
}
if (get_option('fernwood_store_version') === FERNWOOD_STORE_VERSION) {
    say('store already stocked');
    exit(0);
}

wp_set_current_user(1);
WC_Install::install();

// --- the business ----------------------------------------------------------

say('store settings');
$settings = [
    'blogdescription' => 'Plants grown in Portland, delivered to your door',
    'blogname' => 'Fernwood Nursery',
    'woocommerce_allowed_countries' => 'specific',
    'woocommerce_calc_taxes' => 'no',
    'woocommerce_coming_soon' => 'no',
    'woocommerce_currency' => 'USD',
    'woocommerce_default_country' => 'US:OR',
    'woocommerce_default_customer_address' => 'base',
    'woocommerce_enable_guest_checkout' => 'yes',
    'woocommerce_enable_reviews' => 'no',
    'woocommerce_onboarding_profile' => ['skipped' => true],
    'woocommerce_ship_to_countries' => 'specific',
    'woocommerce_specific_allowed_countries' => ['US'],
    'woocommerce_specific_ship_to_countries' => ['US'],
    'woocommerce_store_address' => '4120 SE Division St',
    'woocommerce_store_city' => 'Portland',
    'woocommerce_store_pages_only' => 'no',
    'woocommerce_store_postcode' => '97202',
    'woocommerce_task_list_hidden' => 'yes',
    'woocommerce_ship_to_destination' => 'shipping',
    'woocommerce_show_marketplace_suggestions' => 'no',
    'woocommerce_allow_tracking' => 'no',
];
foreach ($settings as $name => $value) {
    update_option($name, $value);
}

// The delivery area the van covers, as the routing spreadsheet exports it.
$zips = array_merge(range(97201, 97239), [97005, 97006, 97007, 97008, 97030, 97034, 97035, 97080, 97222, 97223, 97266, 97267]);
update_option('fernwood_delivery_zips', array_values(array_unique($zips)));

// --- delivery and payment --------------------------------------------------

say('delivery zone and payment');
$existing = array_filter(WC_Shipping_Zones::get_zones(), static fn (array $zone): bool => $zone['zone_name'] === 'Portland metro');
if ($existing === []) {
    $zone = new WC_Shipping_Zone();
    $zone->set_zone_name('Portland metro');
    $zone->add_location('US:OR', 'state');
    $zone->add_location('970*', 'postcode');
    $zone->add_location('972*', 'postcode');
    $zone->save();

    $flat = $zone->add_shipping_method('flat_rate');
    update_option('woocommerce_flat_rate_' . $flat . '_settings', ['cost' => '12', 'tax_status' => 'none', 'title' => 'Local delivery (Tue–Sat)']);
    $free = $zone->add_shipping_method('free_shipping');
    update_option('woocommerce_free_shipping_' . $free . '_settings', ['ignore_discounts' => 'no', 'min_amount' => '75', 'requires' => 'min_amount', 'title' => 'Free local delivery']);
}

update_option('woocommerce_cod_settings', [
    'description' => 'Pay by card or cash when your plants arrive.',
    'enable_for_methods' => [],
    'enable_for_virtual' => 'yes',
    'enabled' => 'yes',
    'instructions' => 'Our driver will take payment by card or cash on delivery.',
    'title' => 'Pay on delivery',
]);

// --- catalogue -------------------------------------------------------------

say('categories');
$categories = [
    'houseplants' => ['Houseplants', 'Easy-going greenery for every window, from low light to full sun.'],
    'garden' => ['Trees & perennials', 'Maples, hydrangeas and lavender, hardened off for Portland winters.'],
    'pots-tools' => ['Pots & tools', 'Terracotta, shears and cans we use in our own greenhouses.'],
];
$categoryIds = [];
foreach ($categories as $slug => [$name, $description]) {
    $term = get_term_by('slug', $slug, 'product_cat');
    $categoryIds[$slug] = $term
        ? (int) $term->term_id
        : (int) wp_insert_term($name, 'product_cat', ['description' => $description, 'slug' => $slug])['term_id'];
}

$products = [
    ['monstera', 'houseplants', 'Monstera deliciosa', '38.00', true, '6-inch nursery pot · bright, indirect light',
        'Our best-selling houseplant, grown from cuttings in our east greenhouse. Big split leaves that keep coming all year. Happy in bright, indirect light and a weekly drink once the top inch of soil is dry.'],
    ['fiddle-leaf-fig', 'houseplants', 'Fiddle-leaf fig', '68.00', false, '10-inch pot · about 3 ft tall',
        'A tall, architectural fig with glossy violin-shaped leaves. It likes a bright spot it can stay in: pick the window, then leave it be.'],
    ['snake-plant', 'houseplants', "Snake plant 'Laurentii'", '29.00', true, '6-inch ceramic pot · low light OK',
        'Upright, yellow-edged leaves that shrug off low light and a forgotten watering or two. The plant we recommend for first-time plant owners and north-facing rooms.'],
    ['golden-pothos', 'houseplants', 'Golden pothos', '24.00', false, '6-inch pot · trailing',
        'Heart-shaped leaves marbled with gold, trailing a foot or more from a shelf or hanging basket. Grows fast and roots easily if you want to share a cutting.'],
    ['japanese-maple', 'garden', "Japanese maple 'Bloodgood'", '89.00', true, '5-gallon container · 4–5 ft',
        'Deep burgundy leaves from spring to autumn, turning scarlet before they fall. Grown on in our yard for three seasons and hardened off for Portland winters. Plant in part shade, sheltered from afternoon sun.'],
    ['lavender', 'garden', "English lavender 'Hidcote'", '14.00', false, '1-gallon pot · full sun',
        'Compact, deep-purple lavender that flowers from June. Bees love it, deer leave it alone, and it is happy in a sunny border or a big pot by the door.'],
    ['hydrangea', 'garden', "Bigleaf hydrangea 'Endless Summer'", '42.00', true, '2-gallon container · part shade',
        'Flowers on old and new wood, so it blooms all summer even after a hard winter. Blue in our acidic Portland soil, pink if you add lime.'],
    ['terracotta-pots', 'pots-tools', 'Terracotta pot trio', '32.00', false, '4, 6 and 8 inch · drainage holes',
        'Classic unglazed terracotta that lets roots breathe. Each pot has a drainage hole and comes with a matching saucer.'],
    ['pruning-shears', 'pots-tools', 'Bypass pruning shears', '26.00', false, 'Hardened steel · fits small and large hands',
        'The shears our own team uses every day. A clean bypass cut that heals fast, a locking catch and a spring that lasts.'],
    ['watering-can', 'pots-tools', 'Galvanized watering can', '34.00', false, '1.5 gallons · removable rose',
        'A hand-finished steel can with a long spout for reaching the back of a border and a brass rose for seedlings.'],
];

say('products');
foreach ($products as [$slug, $category, $name, $price, $featured, $short, $description]) {
    $existingId = wc_get_product_id_by_sku('FW-' . strtoupper($slug));
    $product = $existingId ? wc_get_product($existingId) : new WC_Product_Simple();
    $product->set_name($name);
    $product->set_slug($slug);
    $product->set_sku('FW-' . strtoupper($slug));
    $product->set_regular_price($price);
    $product->set_featured($featured);
    $product->set_short_description($short);
    $product->set_description($description);
    $product->set_category_ids([$categoryIds[$category]]);
    $product->set_manage_stock(true);
    $product->set_stock_quantity(random_int(12, 40));
    $product->set_status('publish');

    if (!$product->get_image_id()) {
        $source = __DIR__ . '/assets/products/' . $slug . '.jpg';
        $tmp = wp_tempnam($slug . '.jpg');
        copy($source, $tmp);
        $imageId = media_handle_sideload(['name' => $slug . '.jpg', 'tmp_name' => $tmp], 0, $name);
        if (is_wp_error($imageId)) {
            say("image for $slug failed: " . $imageId->get_error_message());
        } else {
            $product->set_image_id($imageId);
        }
    }
    $product->save();
}

// --- pages -----------------------------------------------------------------

say('pages');
$pages = [
    'visit' => ['Visit the nursery', <<<'HTML'
    <!-- wp:paragraph -->
    <p>We're on SE Division Street, between 41st and 42nd, with free parking in the yard behind the greenhouses.</p>
    <!-- /wp:paragraph -->
    <!-- wp:heading {"level":3} -->
    <h3 class="wp-block-heading">Hours</h3>
    <!-- /wp:heading -->
    <!-- wp:paragraph -->
    <p>Open every day, 9am to 6pm. Closed Thanksgiving, Christmas Day and New Year's Day.</p>
    <!-- /wp:paragraph -->
    <!-- wp:heading {"level":3} -->
    <h3 class="wp-block-heading">Find us</h3>
    <!-- /wp:heading -->
    <!-- wp:paragraph -->
    <p>4120 SE Division St, Portland, OR 97202 · (503) 555-0187</p>
    <!-- /wp:paragraph -->
    HTML],
    'delivery' => ['Delivery & returns', <<<'HTML'
    <!-- wp:paragraph -->
    <p>Our own van delivers Tuesday to Saturday across Portland, Beaverton, Gresham, Milwaukie and Lake Oswego. Delivery is $12, and free on orders over $75.</p>
    <!-- /wp:paragraph -->
    <!-- wp:paragraph -->
    <p>If a tree or shrub from us doesn't make it through its first year, bring the tag back and we'll replace it. Houseplants can be returned within 30 days.</p>
    <!-- /wp:paragraph -->
    HTML],
];
foreach ($pages as $slug => [$title, $content]) {
    if (!get_page_by_path($slug)) {
        wp_insert_post(['post_content' => preg_replace('/^    /m', '', $content), 'post_name' => $slug, 'post_status' => 'publish', 'post_title' => $title, 'post_type' => 'page']);
    }
}

// WordPress' starter content has no place in a shop.
foreach (['hello-world' => 'post', 'sample-page' => 'page'] as $slug => $type) {
    $post = get_page_by_path($slug, OBJECT, $type);
    if ($post) {
        wp_delete_post($post->ID, true);
    }
}

flush_rewrite_rules(true);
update_option('fernwood_store_version', FERNWOOD_STORE_VERSION);
say('store stocked');
