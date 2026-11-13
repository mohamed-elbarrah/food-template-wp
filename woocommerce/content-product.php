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
        <a href="<?php echo esc_url( $permalink ); ?>" class="product-link product-title-link">
            <h3 class="product-title"><?php echo esc_html( $product->get_name() ); ?></h3>
        </a>
        <div class="card-footer">
            <span class="price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
        </div>
    </div>
</article>
