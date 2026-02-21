<?php
if ( ! defined( 'WP_DEBUG' ) ) {
    die( 'Direct access forbidden.' );
}

/**
 * Reusable features modal component.
 * Provides a shortcode [bcpo_features_modal] and a helper function
 * `bcpo_features_modal()` to output a modal that contains the same
 * `single-left` structure used on the single product page.
 *
 * Usage (shortcode): [bcpo_features_modal]
 * Usage (php): echo bcpo_features_modal();
 */

function bcpo_features_modal_markup( $echo = false, $render_trigger = true ) {
    static $printed = false;

    $out = '';

    // Modal HTML (keeps internal product options area using class `single-left`)
    $out .= '<div class="bcpo-features-overlay" aria-hidden="true">';
    $out .= '<div class="bcpo-features-modal" role="dialog" aria-modal="true" aria-label="Product options">';
    $out .= '<button class="bcpo-features-close" aria-label="Close">&times;</button>';
    // Use the same `single-left` wrapper so theme styles apply directly
    $out .= '<div class="single-left bcpo-features-inner">';
    $out .= '<!-- Insert product option controls here. This container mirrors single product layout. -->';
    $out .= '</div>'; // .single-left
    $out .= '</div>'; // .bcpo-features-modal
    $out .= '</div>'; // .bcpo-features-overlay

    // Optional inline trigger. Consumers may provide their own triggers; set
    // $render_trigger=false when printing site-wide modal to avoid duplicate buttons.
    if ( $render_trigger ) {
        $out .= '<button class="bcpo-features-open" type="button" aria-haspopup="dialog">فتح خيارات المنتج</button>';
    }

    // Only print styles and scripts once per page.
    if ( ! $printed ) {
                $out .= <<<'CSS'
<style>
.bcpo-features-overlay{position:fixed;  z-index: 99; inset:0;display:flex;align-items:center;justify-content:center;background:rgba(11,22,35,0.45);opacity:0;pointer-events:none;transition:opacity .28s ease}
.bcpo-features-overlay.bcpo-show{opacity:1;pointer-events:auto}
.bcpo-features-modal{background:var(--bg-light,#fff);border-radius:30px;box-shadow:0 20px 40px rgba(3,8,18,0.12);transform:translateY(18px) scale(.995);opacity:0;transition:transform .32s cubic-bezier(.2,.9,.2,1),opacity .24s ease;max-width:980px;width:calc(100% - 40px);margin:18px;}
.bcpo-features-overlay.bcpo-show .bcpo-features-modal{transform:translateY(0) scale(1);opacity:1}
.bcpo-features-close{position:absolute;right:12px;top:12px;background:transparent;border:0;font-size:26px;line-height:1;color:var(--text-dark,#0b1623);cursor:pointer}
.bcpo-features-inner{padding:22px}
.bcpo-features-open{background:var(--primary-orange,#f58220);color:#fff;border:0;padding:10px 14px;border-radius:6px;cursor:pointer}
@media (max-width:720px){.bcpo-features-modal{width:100%;border-radius:30;max-width:none}}
</style>
CSS;

                // expose assets base to the inline script so modal can lazy-load frontend assets
                $out .= "<script>var bcpo_assets_base='" . esc_js( get_stylesheet_directory_uri() . '/inc/product-options/assets' ) . "';</script>";

                $out .= <<<'JS'
<script>(function(){
var overlay = document.querySelector('.bcpo-features-overlay');
var closeBtn = null;
function ensure() {
    overlay = overlay || document.querySelector('.bcpo-features-overlay');
    closeBtn = closeBtn || (overlay ? overlay.querySelector('.bcpo-features-close') : null);
}
function setLoading(show){
    ensure(); var inner = overlay && overlay.querySelector('.bcpo-features-inner'); if(!inner) return; if(show){ inner.innerHTML = '<div class="bcpo-features-loading">تحميل...</div>'; } }
function openModalWithProductId( productId ){
    ensure(); if(!overlay) return; overlay.classList.add('bcpo-show'); document.body.style.overflow = 'hidden'; var inner = overlay.querySelector('.bcpo-features-inner'); if(!inner) return;
    // If content already loaded for same product, don't fetch again
    if ( inner.dataset.loadedFor && inner.dataset.loadedFor === String(productId) ) {
        var first = overlay.querySelector('button, [href], input, select, textarea, [tabindex]'); if(first) first.focus(); return;
    }
    setLoading(true);
    var fd = new FormData(); fd.append('action','bcpo_fetch_single_left'); fd.append('product_id', String(productId));
    var ajaxUrl = (window.ajaxurl) ? window.ajaxurl : (window.location.origin + '/wp-admin/admin-ajax.php');
    fetch(ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function(r){ return r.json(); })
        .then(function(j){
            if ( j && j.success && j.data && j.data.html ) {
                inner.innerHTML = j.data.html;
                inner.dataset.loadedFor = String(productId);

                // lazy-load product-options CSS/JS if not already present
                try {
                    var base = (typeof bcpo_assets_base !== 'undefined') ? bcpo_assets_base : '';
                    if ( base ) {
                        var cssHref = base + '/frontend.css';
                        if (!document.querySelector('link[href="' + cssHref + '"]')) {
                            var l = document.createElement('link'); l.rel = 'stylesheet'; l.href = cssHref; document.head.appendChild(l);
                        }
                        var jsHref = base + '/frontend.js';
                        var scriptAlready = document.querySelector('script[src="' + jsHref + '"]');
                        if (!scriptAlready) {
                            var s = document.createElement('script'); s.src = jsHref; s.defer = false; s.async = false;
                            s.onload = function(){
                                // plugin script attaches handlers on DOM ready; call a safe update if available
                                if (typeof jQuery !== 'undefined') {
                                    jQuery(function(){ if (typeof bcpo_frontend !== 'undefined') { /* localized exists */ } });
                                }
                                var first = overlay.querySelector('button, [href], input, select, textarea, [tabindex]'); if(first) first.focus();
                            };
                            s.onerror = function(){ var first = overlay.querySelector('button, [href], input, select, textarea, [tabindex]'); if(first) first.focus(); };
                            document.body.appendChild(s);
                        } else {
                            // script already present — just focus
                            var first = overlay.querySelector('button, [href], input, select, textarea, [tabindex]'); if(first) first.focus();
                        }
                    } else {
                        var first = overlay.querySelector('button, [href], input, select, textarea, [tabindex]'); if(first) first.focus();
                    }
                } catch (e) {
                    var first = overlay.querySelector('button, [href], input, select, textarea, [tabindex]'); if(first) first.focus();
                }
            } else {
                var msg = (j && j.data && j.data.message) ? j.data.message : 'خطأ أثناء التحميل';
                inner.innerHTML = '<div class="bcpo-features-error">'+ msg +'</div>';
                var first = overlay.querySelector('button, [href], input, select, textarea, [tabindex]'); if(first) first.focus();
            }
        })
        .catch(function(){ inner.innerHTML = '<div class="bcpo-features-error">خطأ بالاتصال</div>'; })
        .finally(function(){ /* no-op */ });
}
function closeModal(){ ensure(); if(!overlay) return; overlay.classList.remove('bcpo-show'); document.body.style.overflow = ''; }
document.addEventListener('click', function(e){
    var openBtn = e.target.closest('.bcpo-features-open');
    if ( openBtn ){
        e.preventDefault(); e.stopPropagation(); var pid = openBtn.getAttribute('data-product-id') || openBtn.dataset.productId || '';
        openModalWithProductId( pid );
        return;
    }
    if(e.target.closest('.bcpo-features-close')){ e.preventDefault(); closeModal(); }
    if(e.target.classList && e.target.classList.contains('bcpo-features-overlay')){ closeModal(); }
});
document.addEventListener('keydown', function(e){ if(e.key === 'Escape'){ closeModal(); } if(e.key === 'Tab' && overlay && overlay.classList.contains('bcpo-show')){
    var focusables = overlay.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
    if(!focusables.length) return;
    focusables = Array.prototype.slice.call(focusables);
    var focused = document.activeElement;
    var idx = focusables.indexOf(focused);
    if(e.shiftKey){ if(idx === 0){ focusables[focusables.length-1].focus(); e.preventDefault(); } }
    else { if(idx === focusables.length-1){ focusables[0].focus(); e.preventDefault(); } }
} });
})();</script>
JS;

        $printed = true;
    }

    if ( $echo ) {
        echo $out;
        return '';
    }

    return $out;
}

function bcpo_features_modal_shortcode( $atts ) {
    return bcpo_features_modal_markup( false );
}
add_shortcode( 'bcpo_features_modal', 'bcpo_features_modal_shortcode' );

/**
 * Helper to echo modal from PHP templates: `bcpo_features_modal( true );`
 */
function bcpo_features_modal( $echo = true ) {
    if ( $echo ) {
        bcpo_features_modal_markup( true );
        return;
    }
    return bcpo_features_modal_markup( false );
}

// Ensure modal DOM, styles and scripts are available on every page footer
// (without printing the default trigger button). This prevents the page
// from attempting navigation when the card button is clicked and the
// modal HTML isn't present.
add_action( 'wp_footer', function() {
    // low priority so it's printed near the end
    bcpo_features_modal_markup( true, false );
}, 5 );

/**
 * AJAX endpoint to fetch the `.single-left` HTML for a product by ID.
 * We fetch the product permalink, retrieve the rendered HTML and extract
 * the element with class `single-left` so the modal shows the same markup.
 */
function bcpo_ajax_fetch_single_left() {
    $product_id = isset( $_REQUEST['product_id'] ) ? intval( wp_unslash( $_REQUEST['product_id'] ) ) : 0;
    if ( $product_id <= 0 ) {
        wp_send_json_error( array( 'message' => 'Invalid product id' ), 400 );
    }

    $permalink = get_permalink( $product_id );
    if ( ! $permalink ) {
        wp_send_json_error( array( 'message' => 'Could not get permalink' ), 404 );
    }

    $resp = wp_remote_get( $permalink );
    if ( is_wp_error( $resp ) || wp_remote_retrieve_response_code( $resp ) !== 200 ) {
        wp_send_json_error( array( 'message' => 'Could not fetch product page' ), 500 );
    }

    $html = wp_remote_retrieve_body( $resp );

    // Parse HTML and extract element with class `single-left`
    libxml_use_internal_errors( true );
    $dom = new DOMDocument();
    $loaded = $dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html );
    libxml_clear_errors();

    if ( ! $loaded ) {
        wp_send_json_error( array( 'message' => 'Could not parse HTML' ), 500 );
    }

    $xpath = new DOMXPath( $dom );
    $nodes = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' single-left ')]");
    if ( ! $nodes || $nodes->length === 0 ) {
        wp_send_json_error( array( 'message' => 'single-left not found' ), 404 );
    }

    // Return the first match's HTML
    $node = $nodes->item(0);
    $inner = '';
    foreach ( $node->childNodes as $child ) {
        $inner .= $dom->saveHTML( $child );
    }

    wp_send_json_success( array( 'html' => $inner ) );
}
add_action( 'wp_ajax_bcpo_fetch_single_left', 'bcpo_ajax_fetch_single_left' );
add_action( 'wp_ajax_nopriv_bcpo_fetch_single_left', 'bcpo_ajax_fetch_single_left' );
