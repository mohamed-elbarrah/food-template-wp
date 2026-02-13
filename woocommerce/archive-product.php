<?php
/**
 * Custom archive (shop) template for child theme
 */
defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

do_action( 'woocommerce_before_main_content' );

if ( woocommerce_product_loop() ) {

    do_action( 'woocommerce_before_shop_loop' );

    // Custom grid wrapper to match child theme markup
    echo '<div class="product-grid">';

    if ( wc_get_loop_prop( 'total' ) ) {
        while ( have_posts() ) {
            the_post();
            // content-product.php now outputs a .food-card div
            wc_get_template_part( 'content', 'product' );
        }
    }

    echo '</div>';

    do_action( 'woocommerce_after_shop_loop' );

} else {
    do_action( 'woocommerce_no_products_found' );
}

do_action( 'woocommerce_after_main_content' );

get_footer( 'shop' );
