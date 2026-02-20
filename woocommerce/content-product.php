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
<article class="product-card" itemscope itemtype="http://schema.org/Product">
    <div class="card-image">
        <a href="<?php echo $permalink; ?>" class="product-link" aria-label="<?php esc_attr_e( 'View product', 'blocksy-child' ); ?>">
            <?php echo get_the_post_thumbnail( $product->get_id(), 'large', array( 'loading' => 'lazy' ) ); ?>
        </a>
    </div>

    <div class="card-content">
        <div class="title-price-row">
            <a href="<?php echo $permalink; ?>" class="product-link"><h3 class="product-title" itemprop="name"><?php echo $title; ?></h3></a>
        </div>

        <p class="ingredients"><?php echo $excerpt; ?></p>

        <div class="card-footer">
            

            <div class="card-footer">
            <div class="price-rating-row">
                <span class="price"><?php echo wc_price( wc_get_price_to_display( $product ) ); ?></span>

                <div class="rating">
                    <span class="rating-value"><?php echo number_format( (float) $product->get_average_rating(), 1 ); ?></span>
                    <span class="star">★</span>
                </div>
            </div>

            <?php if ( $product->is_type( 'simple' ) ) : ?>
                <a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>" class="add-btn add_to_cart_button full-width-btn"><?php esc_html_e( 'اطلب الحين', 'blocksy-child' ); ?></a>
            <?php else : ?>
                <a href="<?php echo $permalink; ?>" class="add-btn full-width-btn"><?php esc_html_e( 'View Product', 'blocksy-child' ); ?></a>
            <?php endif; ?>
        </div>
    </div>
</article>
