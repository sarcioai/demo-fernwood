<?php
/**
 * Title: Fernwood home
 * Slug: fernwood/home
 * Inserter: no
 */

defined('ABSPATH') || exit;

$fernwood_image = static fn (string $file): string => esc_url(get_theme_file_uri('assets/images/' . $file));
$fernwood_categories = [
    ['Houseplants', 'Easy-going greenery for every window, from low light to full sun.', '/product-category/houseplants/', 'cat-houseplants.jpg'],
    ['Trees & perennials', 'Maples, hydrangeas and lavender, hardened off for Portland winters.', '/product-category/garden/', 'cat-garden.jpg'],
    ['Pots & tools', 'Terracotta, shears and cans we use in our own greenhouses.', '/product-category/pots-tools/', 'cat-pots.jpg'],
];
?>
<!-- wp:cover {"url":"<?php echo $fernwood_image('hero-greenhouse.jpg'); ?>","dimRatio":40,"overlayColor":"contrast","minHeight":560,"isDark":true,"align":"full","style":{"spacing":{"padding":{"top":"6rem","bottom":"6rem"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-cover alignfull is-dark" style="padding-top:6rem;padding-bottom:6rem;min-height:560px"><span aria-hidden="true" class="wp-block-cover__background has-contrast-background-color has-background-dim-40 has-background-dim"></span><img class="wp-block-cover__image-background" alt="Rows of potted plants in the Fernwood greenhouse" src="<?php echo $fernwood_image('hero-greenhouse.jpg'); ?>" data-object-fit="cover"/><div class="wp-block-cover__inner-container">
<!-- wp:paragraph {"textColor":"base","style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.12em","fontSize":"0.85rem","fontWeight":"700"}}} -->
<p class="has-base-color has-text-color" style="font-size:0.85rem;font-weight:700;letter-spacing:0.12em;text-transform:uppercase">Autumn planting is here</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"textColor":"base","style":{"typography":{"fontSize":"clamp(2.5rem, 5vw, 4.25rem)","lineHeight":"1.05"}}} -->
<h1 class="wp-block-heading has-base-color has-text-color" style="font-size:clamp(2.5rem, 5vw, 4.25rem);line-height:1.05">Plants grown in Portland,<br>delivered to your door.</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"textColor":"base","style":{"typography":{"fontSize":"1.2rem"}}} -->
<p class="has-base-color has-text-color" style="font-size:1.2rem">Houseplants, trees and perennials from our greenhouses on SE Division. Same-week delivery across the metro area.</p>
<!-- /wp:paragraph -->
<!-- wp:buttons -->
<div class="wp-block-buttons">
<!-- wp:button {"backgroundColor":"base","textColor":"forest"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-forest-color has-base-background-color has-text-color has-background wp-element-button" href="<?php echo esc_url(home_url('/shop/')); ?>">Shop the nursery</a></div>
<!-- /wp:button -->
<!-- wp:button {"className":"is-style-outline","textColor":"base"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-base-color has-text-color wp-element-button" href="<?php echo esc_url(home_url('/visit/')); ?>">Plan a visit</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div></div>
<!-- /wp:cover -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"4.5rem"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group" style="padding-top:4.5rem">
<!-- wp:heading -->
<h2 class="wp-block-heading">Shop by what you're growing</h2>
<!-- /wp:heading -->
<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"1.5rem"}}}} -->
<div class="wp-block-columns">
<?php foreach ($fernwood_categories as [$fernwood_title, $fernwood_blurb, $fernwood_path, $fernwood_file]) : ?>
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:image {"sizeSlug":"large","style":{"border":{"radius":"12px"}}} -->
<figure class="wp-block-image size-large has-custom-border"><a href="<?php echo esc_url(home_url($fernwood_path)); ?>"><img src="<?php echo $fernwood_image($fernwood_file); ?>" alt="<?php echo esc_attr($fernwood_title); ?>" style="border-radius:12px;aspect-ratio:4/3;object-fit:cover"/></a></figure>
<!-- /wp:image -->
<!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.35rem"}}} -->
<h3 class="wp-block-heading" style="font-size:1.35rem"><a href="<?php echo esc_url(home_url($fernwood_path)); ?>" style="text-decoration:none"><?php echo esc_html($fernwood_title); ?></a></h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"textColor":"stone"} -->
<p class="has-stone-color has-text-color"><?php echo esc_html($fernwood_blurb); ?></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<?php endforeach; ?>
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"4rem"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group" style="padding-top:4rem">
<!-- wp:group {"layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap"}} -->
<div class="wp-block-group">
<!-- wp:heading -->
<h2 class="wp-block-heading">This week at the nursery</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p><a href="<?php echo esc_url(home_url('/shop/')); ?>">See all plants →</a></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:woocommerce/product-collection {"queryId":11,"query":{"perPage":4,"pages":1,"offset":0,"postType":"product","order":"desc","orderBy":"date","search":"","exclude":[],"inherit":false,"taxQuery":[],"isProductCollectionBlock":true,"featured":true,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","outofstock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":[]},"tagName":"div","displayLayout":{"type":"flex","columns":4,"shrinkColumns":true},"collection":"woocommerce/product-collection/featured","queryContextIncludes":["collection"]} -->
<div class="wp-block-woocommerce-product-collection">
<!-- wp:woocommerce/product-template -->
<!-- wp:woocommerce/product-image {"imageSizing":"thumbnail","isDescendentOfQueryLoop":true} /-->
<!-- wp:post-title {"level":3,"isLink":true,"style":{"typography":{"fontSize":"1.1rem"}},"__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->
<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true} /-->
<!-- /wp:woocommerce/product-template -->
</div>
<!-- /wp:woocommerce/product-collection -->
</div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"4.5rem"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group" style="padding-top:4.5rem">
<!-- wp:media-text {"mediaPosition":"right","mediaType":"image","mediaWidth":48,"style":{"border":{"radius":"14px"}},"backgroundColor":"moss-50"} -->
<div class="wp-block-media-text has-media-on-the-right is-stacked-on-mobile has-moss-50-background-color has-background" style="border-radius:14px;grid-template-columns:auto 48%"><div class="wp-block-media-text__content">
<!-- wp:paragraph {"className":"fernwood-badge"} -->
<p class="fernwood-badge">Family run since 1987</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Ask us anything about your garden</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Maya and Theo Lindqvist took over the nursery from Maya's parents in 2016. Bring a photo of the spot you're planting, or a leaf that's turned yellow, and we'll help you work out what it needs.</p>
<!-- /wp:paragraph -->
<!-- wp:buttons -->
<div class="wp-block-buttons">
<!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url(home_url('/visit/')); ?>">Hours &amp; directions</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div><figure class="wp-block-media-text__media"><img src="<?php echo $fernwood_image('about-team.jpg'); ?>" alt="Maya and Theo in the greenhouse"/></figure></div>
<!-- /wp:media-text -->
</div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"4rem"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group" style="padding-top:4rem">
<!-- wp:columns -->
<div class="wp-block-columns">
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.15rem"}}} -->
<h3 class="wp-block-heading" style="font-size:1.15rem">Same-week delivery</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"textColor":"stone"} -->
<p class="has-stone-color has-text-color">Our own van delivers Tuesday to Saturday across Portland, Beaverton, Gresham and Lake Oswego.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.15rem"}}} -->
<h3 class="wp-block-heading" style="font-size:1.15rem">One-year plant promise</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"textColor":"stone"} -->
<p class="has-stone-color has-text-color">If a tree or shrub from us doesn't make it through its first year, bring the tag back and we'll replace it.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.15rem"}}} -->
<h3 class="wp-block-heading" style="font-size:1.15rem">Pick up in the yard</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"textColor":"stone"} -->
<p class="has-stone-color has-text-color">Order online and collect from the front desk the same day, ready within two hours.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->
