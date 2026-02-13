<?php
/**
 * Custom product card template for child theme
 * Horizontal layout: text left, media right
 */
defined( 'ABSPATH' ) || exit;

global $product;

if ( empty( $product ) || ! $product->is_visible() ) {
    return;
}

$permalink = esc_url( get_permalink( $product->get_id() ) );
$title     = wp_kses_post( get_the_title() );
$excerpt   = wp_kses_post( wp_trim_words( get_the_excerpt(), 25, '...' ) );
$price     = $product->get_price_html();

?>
<div class="food-card" itemscope itemtype="http://schema.org/Product">
    <div class="food-card-info">
        <a href="<?php echo $permalink; ?>" class="food-link">
            <h2 class="food-title" itemprop="name"><?php echo $title; ?></h2>
        </a>

        <p class="food-description" itemprop="description"><?php echo $excerpt; ?></p>

        <div class="food-price" itemprop="offers" itemscope itemtype="http://schema.org/Offer">
            <?php
            // Structured price output: currency code + numeric amount
            $raw_price     = $product->get_price();
            $display_price = wc_get_price_to_display( $product );
            $decimals      = wc_get_price_decimals();
            $decimal_sep   = wc_get_price_decimal_separator();
            $thousand_sep  = wc_get_price_thousand_separator();
            $amount        = number_format( (float) $display_price, $decimals, $decimal_sep, $thousand_sep );
            $currency_code = get_woocommerce_currency();
            ?>

            <span class="price-code"><?php echo esc_html( $currency_code ); ?></span>
            <span class="price-amount"><?php echo esc_html( $amount ); ?></span>
        </div>
    </div>

    <div class="food-card-media">
        <div class="yellow-box">
            <?php echo $product->get_image( 'woocommerce_thumbnail' ); ?>

            <div class="add-button-wrapper">
                <?php if ( $product->is_type( 'simple' ) ) : ?>
                    <a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>" class="add-btn add_to_cart_button" aria-label="<?php esc_attr_e( 'Add to cart', 'blocksy-child' ); ?>">+</a>
                <?php else : ?>
                    <a href="<?php echo $permalink; ?>" class="add-btn" aria-label="<?php esc_attr_e( 'View product', 'blocksy-child' ); ?>">+</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
