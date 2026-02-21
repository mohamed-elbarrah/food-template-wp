<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add-to-cart toast/modal markup printed in the footer.
 */
function bcpo_add_to_cart_toast_markup( $echo = false ) {
    static $printed = false;

    $out = '';

    $out .= '<div class="bcpo-add-to-cart-toast" aria-hidden="true">';
    $out .= '<div class="bcpo-add-toast-inner">';

    $out .= '<div class="bcpo-toast-content">';
    $out .= '<h3 class="bcpo-toast-title">تم إضافة طلبك إلى السلة بنجاح</h3>';
    $out .= '<p class="bcpo-toast-sub">يمكنك المتابعة لإتمام الطلب أو مواصلة التصفح.</p>';
    $out .= '</div>'; // .bcpo-toast-content
    $out .= '<div class="bcpo-toast-actions">';
    $cart_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : site_url( '/cart/' );
    $out .= '<a class="bcpo-toast-action bcpo-go-checkout" href="' . esc_url( $cart_url ) . '">إتمام الطلب</a>';
    $out .= '<button class="bcpo-toast-action bcpo-continue" type="button">مواصلة التصفح</button>';
    $out .= '</div>'; // .bcpo-toast-actions
    $out .= '</div>'; // .bcpo-add-toast-inner
    $out .= '</div>'; // .bcpo-add-to-cart-toast

    // Only print the markup once per page
    if ( $echo ) {
        echo $out;
        return '';
    }

    return $out;
}

// Print markup in footer so it's available on every page
add_action( 'wp_footer', function() {
    echo bcpo_add_to_cart_toast_markup( false );
}, 5 );

/**
 * Prevent WooCommerce default "added to cart" success message from being printed
 * We return an empty string for the HTML filter, and also remove matching
 * success notices early on template load so they won't appear after redirects.
 */
add_filter( 'wc_add_to_cart_message_html', '__return_empty_string', 10, 2 );

add_action( 'template_redirect', function() {
    if ( is_admin() ) {
        return;
    }
    if ( function_exists( 'wc_get_notices' ) && function_exists( 'wc_clear_notices' ) ) {
        $notices = wc_get_notices();
        if ( ! empty( $notices ) && is_array( $notices ) ) {
            $keep = array();
            foreach ( $notices as $type => $items ) {
                if ( ! is_array( $items ) ) {
                    continue;
                }
                foreach ( $items as $msg ) {
                    // Remove default "added to cart" notices which usually contain
                    // English or Arabic phrases. Keep other notices untouched.
                    if ( is_string( $msg ) && ( strpos( $msg, 'added to your cart' ) !== false || strpos( $msg, 'تم إضافة' ) !== false || strpos( $msg, 'إلى سلة مشترياتك' ) !== false ) ) {
                        continue;
                    }
                    // Only keep scalar/string notices to avoid later array-to-string conversion
                    if ( is_scalar( $msg ) ) {
                        $keep[ $type ][] = (string) $msg;
                    } else {
                        // If the message is an array/object, attempt to extract a string safely
                        if ( is_array( $msg ) ) {
                            // flatten simple arrays to a single string if possible
                            $flatten = array();
                            foreach ( $msg as $part ) {
                                if ( is_scalar( $part ) ) {
                                    $flatten[] = (string) $part;
                                }
                            }
                            if ( ! empty( $flatten ) ) {
                                $keep[ $type ][] = implode( ' ', $flatten );
                            }
                        }
                        // skip other complex notice structures to avoid warnings
                    }
                }
            }
            // replace notices with filtered set
            wc_clear_notices();
            if ( ! empty( $keep ) ) {
                foreach ( $keep as $type => $items ) {
                    foreach ( $items as $m ) {
                        // sanitize HTML and re-add as string
                        $safe = wp_kses_post( wp_strip_all_tags( (string) $m ) );
                        if ( $safe !== '' ) {
                            wc_add_notice( $safe, $type );
                        }
                    }
                }
            }
        }
    }
}, 5 );
