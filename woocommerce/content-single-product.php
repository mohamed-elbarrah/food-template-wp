<?php
/**
 * Child theme single product two-column template.
 * Path: woocommerce/content-single-product.php
 */
defined( 'ABSPATH' ) || exit;

global $product;
if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
    return;
}
?>
<div class="single-product-container">
<div class="single-product-layout">
  <div class="single-left">
    <h1 class="single-product-title"><?php echo esc_html( $product->get_name() ); ?></h1>

    <?php if ( $short = $product->get_short_description() ) : ?>
      <div class="single-excerpt"><?php echo wp_kses_post( wpautop( $short ) ); ?></div>
    <?php endif; ?>

    <?php
    /**
     * Render product options from BCPO above the card footer so they appear
     * before the add-to-cart controls rather than inside the form.
     */
    do_action( 'bcpo_render_product_options', $product->get_id() );
    ?>
    <div class="card-footer">
      <div class="price-rating-row">
        <span class="price"><?php echo wc_price( wc_get_price_to_display( $product ) ); ?></span>

        <div class="rating">
          <span class="rating-value"><?php echo number_format( (float) $product->get_average_rating(), 1 ); ?></span>
          <span class="star">★</span>
        </div>
      </div>

      <div class="add-to-cart-row">
        <?php if ( $product->is_type( 'simple' ) ) : ?>
          <form class="custom-single-add-to-cart" action="<?php echo esc_url( $product->add_to_cart_url() ); ?>" method="post">
            <div class="qty-wrap" aria-label="Quantity selector">
              <button type="button" class="qty-btn qty-decrease" aria-label="Decrease quantity">−</button>
              <input type="number" name="quantity" value="1" min="1" step="1" class="qty-input" aria-label="Quantity" readonly="readonly" />
              <button type="button" class="qty-btn qty-increase" aria-label="Increase quantity">+</button>
            </div>
            
            <input type="hidden" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>" />
            <button type="submit" class="add-btn full-width-btn"><?php echo esc_html__( 'اطلب الحين', 'blocksy-child' ); ?></button>
          </form>
        <?php else : ?>
          <?php woocommerce_template_single_add_to_cart(); ?>
        <?php endif; ?>
      </div>

      <script>
        (function(){
          function initQty(root){
            var dec = root.querySelector('.qty-decrease');
            var inc = root.querySelector('.qty-increase');
            var input = root.querySelector('.qty-input');
            if(!input) return;
            dec && dec.addEventListener('click', function(){
              var val = parseInt(input.value) || 1;
              var min = parseInt(input.getAttribute('min')) || 1;
              if(val > min) input.value = val - 1;
            });
            inc && inc.addEventListener('click', function(){
              var val = parseInt(input.value) || 1;
              input.value = val + 1;
            });
          }
          document.addEventListener('DOMContentLoaded', function(){
            var wrappers = document.querySelectorAll('.custom-single-add-to-cart .qty-wrap');
            wrappers.forEach(function(w){ initQty(w); });
          });
        })();
      </script>
    </div>
  </div>

  <div class="single-right">
    <?php
    $image = $product->get_image( 'large' );
    if ( $image ) {
        echo wp_kses_post( $image );
    } else {
        echo '<img src="' . esc_url( wc_placeholder_img_src( 'large' ) ) . '" alt="' . esc_attr__( 'Placeholder', 'blocksy-child' ) . '" class="single-product-image" />';
    }
    ?>
  </div>
</div>
</div>
