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
      <div class="card-footer-inner single-product-container">
        <div class="price-rating-row">
          <span class="price bcpo-price-num-target"><span class="bcpo-num-wrap"><span class="bcpo-num current"><?php echo wc_price( wc_get_price_to_display( $product ) ); ?></span></span></span>

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

<!-- Rating section: placed under .single-product-layout (sibling) -->
<div class="bcpo-rating-section single-product-container" aria-label="Product rating section">
  <?php
  // If a logged-in user already left a rating for this product, show the success message
  $existing_rating = 0;
  $current_user_id = get_current_user_id();
  if ( $current_user_id ) {
    $prev = get_comments( array(
      'post_id'   => $product->get_id(),
      'user_id'   => $current_user_id,
      'meta_key'  => 'rating',
      'number'    => 1,
      'status'    => 'approve',
    ) );
    if ( ! empty( $prev ) && isset( $prev[0]->comment_ID ) ) {
      $existing_rating = intval( get_comment_meta( $prev[0]->comment_ID, 'rating', true ) );
    }
  }

  if ( $existing_rating > 0 ) :
    // render the same success bar we use after AJAX submit
  ?>
    <?php
    $prev_comment_id = isset( $prev[0]->comment_ID ) ? intval( $prev[0]->comment_ID ) : 0;
    $prev_comment_text = isset( $prev[0]->comment_content ) ? $prev[0]->comment_content : '';
    ?>
    <div class="bcpo-rating-thanks-bar single-product-container" role="status" aria-live="polite" data-comment-id="<?php echo esc_attr( $prev_comment_id ); ?>" data-comment="<?php echo esc_attr( rawurlencode( $prev_comment_text ) ); ?>" data-rating="<?php echo esc_attr( $existing_rating ); ?>" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>" style="border:1px dashed #b19cd9;padding:8px 12px;display:flex;align-items:center;justify-content:space-between;">
      <div class="bcpo-thanks-left" style="display:flex;align-items:center;gap:10px;color:#2e7d32;font-weight:600;">
        <span class="bcpo-thanks-check" style="display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;border-radius:50%;background:#e8f5e9;color:#2e7d32;font-size:14px;">✓</span>
        <span><?php echo esc_html__( 'تم إرسال ردك بنجاح', 'blocksy-child' ); ?></span>
      </div>
      <div class="bcpo-thanks-right" style="display:flex;align-items:center;gap:12px;">
        <div class="bcpo-thank-stars" style="display:flex;align-items:center;">
          <?php for ( $s = 1; $s <= 5; $s++ ) : ?>
            <span class="bcpo-thank-star" style="color:#2e7d32;margin-left:6px;font-size:1.05em"><?php echo ( $s <= $existing_rating ) ? '★' : '☆'; ?></span>
          <?php endfor; ?>
        </div>
        <span class="bcpo-thanks-score" style="margin-right:8px;color:#333;">(<?php echo number_format( (float) $existing_rating, 1 ); ?>)</span>
        <?php if ( $current_user_id ) : ?>
          <button type="button" class="bcpo-edit-review" style="background:transparent;border:1px solid #ccc;padding:6px 8px;border-radius:4px;">تعديل تقييمي</button>
        <?php endif; ?>
      </div>
      <div class="bcpo-comment-data" style="display:none;"><?php echo wp_kses_post( wp_json_encode( $prev_comment_text ) ); ?></div>
    </div>
  <?php else : ?>
    <form class="bcpo-rating-form" method="post" action="#">
      <div class="bcpo-rating-grid">
        <div class="bcpo-rating-left">
          <label class="bcpo-rating-label">أخبرنا عن رأيك في هذه الخدمة</label>
          <textarea name="bcpo_comment" class="bcpo-comment" placeholder="النص المدخل" rows="4"></textarea>
          <div class="bcpo-rating-actions">
            <button type="submit" class="bcpo-rating-submit add-btn">إرسال</button>
          </div>
        </div>

        <div class="bcpo-rating-right">
          <div class="bcpo-rating-prompt">كيف تقييم هذه الخدمة؟</div>
          <div class="bcpo-stars" role="radiogroup" aria-label="Rate this product">
            <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
              <button type="button" class="bcpo-star" data-value="<?php echo $i; ?>" aria-label="<?php echo $i; ?> stars">☆</button>
            <?php endfor; ?>
          </div>
        </div>
      </div>
      <input type="hidden" name="product_id" value="<?php echo esc_attr( $product->get_id() ); ?>">
      <input type="hidden" name="rating" value="">
    </form>
  <?php endif; ?>
</div>
    </div>

<!-- Product reviews carousel: show product reviews as cards and autoplay loop if more than 3 -->
<?php
$comments = get_comments( array(
  'post_id' => $product->get_id(),
  'status'  => 'approve',
  'meta_key'=> 'rating',
  'order'   => 'DESC',
) );
if ( ! empty( $comments ) ) :
  $count = count( $comments );
?>
  <div class="bcpo-reviews-wrap single-product-container" aria-label="Product reviews">
    <h3 class="bcpo-reviews-heading" style="margin-bottom:12px;"><?php echo esc_html__( 'تقييمات المستخدمين', 'blocksy-child' ); ?></h3>
    <div class="bcpo-reviews-viewport" style="overflow:hidden;">
      <div class="bcpo-reviews-track">
        <?php foreach ( $comments as $c ) :
          $rating = intval( get_comment_meta( $c->comment_ID, 'rating', true ) );
          $author = get_comment_author( $c );
          $text = wp_trim_words( wp_strip_all_tags( $c->comment_content ), 30, '...' );
        ?>
          <article class="bcpo-review-card" role="article" aria-label="Review by <?php echo esc_attr( $author ); ?>">
            <div class="bcpo-review-stars">
              <?php for ( $s = 1; $s <= 5; $s++ ) : ?>
                <span class="bcpo-star<?php echo ( $s <= $rating ) ? ' filled' : ''; ?>">★</span>
              <?php endfor; ?>
            </div>
            <blockquote class="bcpo-review-quote"><?php echo esc_html( $text ); ?></blockquote>
            <div class="bcpo-review-meta">
              <div class="bcpo-review-author">
                <img class="bcpo-g2" src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='28' height='28' viewBox='0 0 24 24'><circle cx='12' cy='12' r='12' fill='%23ff4b2b'/></svg>" alt="G2"> 
                <span class="bcpo-author-name"><?php echo esc_html( $author ); ?></span>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <style>
    .bcpo-reviews-wrap { margin-top:18px; }
    .bcpo-reviews-heading { font-size:1.05rem; color:#222; }
    .bcpo-reviews-viewport { position:relative; }
    .bcpo-reviews-track { display:flex; gap:18px; align-items:stretch; padding-bottom:8px; }
    .bcpo-review-card { background:#fff; border-radius:12px; box-shadow:0 12px 30px rgba(19,21,30,0.06); padding:18px; width:320px; flex:0 0 320px; color:#24303a; }
    .bcpo-review-stars { color:#f6c84c; font-size:1.05rem; margin-bottom:8px; }
    .bcpo-review-stars .filled { color:#f6c84c; }
    .bcpo-review-quote { font-style:normal; color:#2b2b2b; font-weight:600; margin:0 0 14px 0; }
    .bcpo-review-meta { display:flex; align-items:center; justify-content:flex-start; }
    .bcpo-review-author { display:flex; align-items:center; gap:10px; color:#556; font-weight:600; }
    .bcpo-review-author .bcpo-g2 { width:28px; height:28px; border-radius:50%; }
    @media (max-width:900px){ .bcpo-review-card{ width:260px; flex:0 0 260px } }
  </style>

  <script>
    (function(){
      var track = document.querySelector('.bcpo-reviews-track');
      if (!track) return;
      var viewport = track.parentElement;
      var items = track.children.length;
      // If more than 3 items, enable continuous loop by cloning items
      if (items > 3) {
        // duplicate items to make seamless loop
        track.innerHTML = track.innerHTML + track.innerHTML;
        var speed = 0.5; // pixels per frame-ish
        var rafId;
        function step(){
          viewport.scrollLeft += speed;
          if ( viewport.scrollLeft >= (track.scrollWidth / 2) ) {
            viewport.scrollLeft = 0;
          }
          rafId = requestAnimationFrame(step);
        }
        // pause on hover
        viewport.addEventListener('mouseenter', function(){ cancelAnimationFrame(rafId); });
        viewport.addEventListener('mouseleave', function(){ rafId = requestAnimationFrame(step); });
        // start
        rafId = requestAnimationFrame(step);
      } else {
        // center if few items
        viewport.style.display = 'flex';
        viewport.style.justifyContent = 'center';
      }
    })();
  </script>

<?php
endif;

?>

<!-- Fallback JS: if the frontend script isn't loaded, handle rating form submit via fetch -->
<script>
  (function(){
    // If the main frontend handler is present, skip fallback
    if ( typeof bcpo_frontend !== 'undefined' ) return;
    document.addEventListener('submit', function(e){
      var form = e.target;
      if (!form || !form.classList || !form.classList.contains('bcpo-rating-form')) return;
      e.preventDefault();
      var productIdEl = form.querySelector('input[name="product_id"]');
      var ratingEl = form.querySelector('input[name="rating"]');
      var commentEl = form.querySelector('textarea[name="bcpo_comment"]');
      var commentIdEl = form.querySelector('input[name="comment_id"]');
      var productId = productIdEl ? productIdEl.value : '';
      var rating = ratingEl ? parseInt(ratingEl.value||0,10) : 0;
      var comment = commentEl ? commentEl.value : '';
      var commentId = commentIdEl ? parseInt(commentIdEl.value||0,10) : 0;
      if ( rating < 1 || rating > 5 ) { alert('الرجاء اختيار تقييم من 1 إلى 5'); return; }
      var ajaxUrl = '<?php echo esc_url( admin_url( "admin-ajax.php" ) ); ?>';
      var nonce = '<?php echo esc_js( wp_create_nonce( "bcpo-rate" ) ); ?>';
      var params = new URLSearchParams();
      params.append('action','bcpo_submit_rating');
      params.append('product_id', productId);
      params.append('rating', rating);
      params.append('comment', comment);
      params.append('comment_id', commentId);
      params.append('nonce', nonce);
      fetch( ajaxUrl, { method: 'POST', body: params, credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' } } )
        .then(function(r){ return r.json(); })
        .then(function(resp){
          if ( resp && resp.success ) {
            var commentIdResp = resp.data && resp.data.comment_id ? parseInt(resp.data.comment_id,10) : 0;
            var stars=''; for(var s=1;s<=5;s++){ stars += '<span class="bcpo-thank-star" style="color:#2e7d32;margin-left:6px;font-size:1.05em;">' + (s <= rating ? '★' : '☆') + '</span>'; }
            var encComment = encodeURIComponent( comment || '' );
            var html = '';
            html += '<div class="bcpo-rating-thanks-bar single-product-container" role="status" aria-live="polite" data-comment-id="'+commentIdResp+'" data-comment="'+encComment+'" data-rating="'+rating+'" data-product-id="'+(productId||'')+'" style="border:1px dashed #b19cd9;padding:8px 12px;display:flex;align-items:center;justify-content:space-between;">';
            html += '<div class="bcpo-thanks-left" style="display:flex;align-items:center;gap:10px;color:#2e7d32;font-weight:600;">';
            html += '<span class="bcpo-thanks-check" style="display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;border-radius:50%;background:#e8f5e9;color:#2e7d32;font-size:14px;">✓</span>';
            html += '<span>تم إرسال ردك بنجاح</span>';
            html += '</div>';
            html += '<div class="bcpo-thanks-right" style="display:flex;align-items:center;gap:12px;">';
            html += '<div class="bcpo-thank-stars" style="display:flex;align-items:center;">' + stars + '</div>';
            html += '<span class="bcpo-thanks-score" style="margin-right:8px;color:#333;">(' + (parseFloat(rating)).toFixed(1) + ')</span>';
            html += '<button type="button" class="bcpo-edit-review" style="background:transparent;border:1px solid #ccc;padding:6px 8px;border-radius:4px;">تعديل تقييمي</button>';
            html += '</div>';
            html += '<div class="bcpo-comment-data" style="display:none;">' + JSON.stringify(comment||'') + '</div>';
            html += '</div>';
            var section = form.closest('.bcpo-rating-section');
            if ( section ) section.outerHTML = html;
            // update rating values on page if server returned new average
            if ( resp && resp.data && typeof resp.data.average !== 'undefined' ) {
              var newAvg = parseFloat( resp.data.average ) || 0;
              var newText = newAvg.toFixed(1);
              try {
                var els = document.querySelectorAll('.rating-value');
                els.forEach(function(el){ el.textContent = newText; });
              } catch(e){}
            }
          } else {
            alert( (resp && resp.data && resp.data.message) ? resp.data.message : 'خطأ في الإرسال' );
          }
        }).catch(function(){ alert('Network error'); });
    }, false );
  })();
</script>

  <!-- Fixed bottom purchase footer (duplicate of inline footer for quick access) -->
  <div class="card-footer fixed" aria-hidden="false" data-fixed="1">
    <div class="card-footer-inner single-product-container">
      <div class="price-rating-row">
        <span class="price bcpo-price-num-target"><span class="bcpo-num-wrap"><span class="bcpo-num current"><?php echo wc_price( wc_get_price_to_display( $product ) ); ?></span></span></span>

        <div class="rating">
          <span class="rating-value"><?php echo number_format( (float) $product->get_average_rating(), 1 ); ?></span>
          <span class="star">★</span>
        </div>
      </div>

      <div class="add-to-cart-row">
        <?php if ( $product->is_type( 'simple' ) ) : ?>
          <form class="custom-single-add-to-cart fixed-footer-form" action="<?php echo esc_url( $product->add_to_cart_url() ); ?>" method="post">
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
    </div>
  </div>
  </div>
