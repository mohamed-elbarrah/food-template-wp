<?php
/** Food catalog product card. */
defined( 'ABSPATH' ) || exit;

global $product;
if ( ! $product instanceof WC_Product || ! $product->is_visible() ) {
    return;
}
$product_id = $product->get_id();
$permalink  = get_permalink( $product_id );
?>
<article <?php wc_product_class( 'product-card', $product ); ?>>
    <div class="card-image">
        <a href="<?php echo esc_url( $permalink ); ?>" class="product-link" aria-label="<?php echo esc_attr( sprintf( __( 'View %s', 'blocksy-child' ), $product->get_name() ) ); ?>">
            <?php echo wp_kses_post( $product->get_image( 'woocommerce_thumbnail', array( 'loading' => 'lazy' ) ) ); ?>
        </a>
    </div>
    <div class="card-content">
        <div class="title-price-row">
            <a href="<?php echo esc_url( $permalink ); ?>" class="product-link"><h3 class="product-title"><?php echo esc_html( $product->get_name() ); ?></h3></a>
        </div>
        <p class="ingredients"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $product->get_short_description() ?: $product->get_description() ), 25 ) ); ?></p>
        <div class="card-footer">
            <div class="price-rating-row">
                <span class="price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
                <?php if ( wc_review_ratings_enabled() ) : ?>
                    <div class="rating" aria-label="<?php echo esc_attr( sprintf( __( 'Rated %s out of 5', 'blocksy-child' ), $product->get_average_rating() ) ); ?>">
                        <span class="rating-value"><?php echo esc_html( number_format_i18n( (float) $product->get_average_rating(), 1 ) ); ?></span>
                        <span class="star" aria-hidden="true">★</span>
                    </div>
                <?php endif; ?>
            </div>
            <a class="add-btn full-width-btn" href="<?php echo esc_url( $permalink ); ?>"><?php esc_html_e( 'View product', 'blocksy-child' ); ?></a>
        </div>
    </div>
</article>
