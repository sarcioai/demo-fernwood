<?php

declare(strict_types=1);

$_SERVER['HTTP_HOST'] = 'localhost';
define('WP_USE_THEMES', false);
require '/var/www/wp/wp-load.php';

$settings = get_option('sarcio_settings', []);
$settings = is_array($settings) ? $settings : [];
$settings['site_key'] = getenv('SARCIO_SITE_KEY') ?: ($settings['site_key'] ?? 'pk_demo_fernwood');
if (getenv('SARCIO_SIDECAR_KEY')) {
    $settings['sidecar_key'] = getenv('SARCIO_SIDECAR_KEY');
}
$settings['launcher_color'] ??= '#2f4a3a';
$settings['launcher_icon'] ??= 'bubble';
$settings['launcher_label'] ??= 'Report a bug';
$settings['launcher_position'] ??= 'right';
$settings['launcher_style'] ??= 'label';
$settings['review_in_admin'] ??= true;
update_option('sarcio_settings', $settings);

$password = (string) getenv('FERNWOOD_ADMIN_PASSWORD');
$admin = get_user_by('login', 'admin');
if ($password !== '' && $admin && !wp_check_password($password, $admin->user_pass, $admin->ID)) {
    wp_set_password($password, $admin->ID);
}
