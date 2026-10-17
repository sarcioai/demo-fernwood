<?php
/**
 * Title: Fernwood header
 * Slug: fernwood/header
 * Inserter: no
 */

defined('ABSPATH') || exit;

$fernwood_links = [
    'Shop all' => home_url('/shop/'),
    'Houseplants' => home_url('/product-category/houseplants/'),
    'Garden' => home_url('/product-category/garden/'),
    'Pots & tools' => home_url('/product-category/pots-tools/'),
    'Visit us' => home_url('/visit/'),
];
?>
<!-- wp:group {"style":{"spacing":{"padding":{"top":"0.5rem","bottom":"0.5rem"}}},"backgroundColor":"forest","textColor":"base","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-base-color has-forest-background-color has-text-color has-background" style="padding-top:0.5rem;padding-bottom:0.5rem">
<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"0.85rem"}}} -->
<p class="has-text-align-center" style="font-size:0.85rem">Free local delivery across Portland on orders over $75 · Open daily 9–6 on SE Division St</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"1.25rem","bottom":"1.25rem"}},"border":{"bottom":{"color":"var:preset|color|linen","width":"1px"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group" style="border-bottom-color:var(--wp--preset--color--linen);border-bottom-width:1px;padding-top:1.25rem;padding-bottom:1.25rem">
<!-- wp:group {"layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap"}} -->
<div class="wp-block-group">
<!-- wp:group {"style":{"spacing":{"blockGap":"0.1rem"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group">
<!-- wp:paragraph {"style":{"typography":{"fontSize":"1.6rem","fontWeight":"600","lineHeight":"1"}},"fontFamily":"literata"} -->
<p class="has-literata-font-family" style="font-size:1.6rem;font-weight:600;line-height:1"><a href="<?php echo esc_url(home_url('/')); ?>" style="text-decoration:none">Fernwood Nursery</a></p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"textColor":"stone","style":{"typography":{"fontSize":"0.8rem","letterSpacing":"0.08em","textTransform":"uppercase"}}} -->
<p class="has-stone-color has-text-color" style="font-size:0.8rem;letter-spacing:0.08em;text-transform:uppercase">Grown in Portland since 1987</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"blockGap":"1.75rem"}},"layout":{"type":"flex","flexWrap":"wrap"}} -->
<div class="wp-block-group">
<?php foreach ($fernwood_links as $fernwood_label => $fernwood_url) : ?>
<!-- wp:paragraph {"style":{"typography":{"fontWeight":"600","fontSize":"0.95rem"}}} -->
<p style="font-size:0.95rem;font-weight:600"><a href="<?php echo esc_url($fernwood_url); ?>" style="text-decoration:none"><?php echo esc_html($fernwood_label); ?></a></p>
<!-- /wp:paragraph -->
<?php endforeach; ?>
<!-- wp:woocommerce/customer-account {"displayStyle":"icon_only","iconStyle":"line"} /-->
<!-- wp:woocommerce/mini-cart {"addToCartBehaviour":"open_drawer"} /-->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
