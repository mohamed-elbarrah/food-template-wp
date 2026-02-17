<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Frontend rendering and cart integration for product options
 */

add_action( 'bcpo_render_product_options', 'bcpo_render_frontend_options', 10, 1 );
function bcpo_render_frontend_options( $product_id ) {
    $groups = function_exists( 'bcpo_get_saved_options' ) ? bcpo_get_saved_options( $product_id ) : array();
    if ( empty( $groups ) ) {
        return;
    }

    echo '<div class="bcpo-frontend">';
    foreach ( $groups as $gi => $group ) {
        $title = isset( $group['title'] ) ? $group['title'] : '';
        $type  = isset( $group['type'] ) ? $group['type'] : 'text';
        $required = ! empty( $group['required'] );
        echo '<fieldset class="bcpo-group-frontend" data-type="' . esc_attr( $type ) . '">';
        echo '<legend class="bcpo-group-title">' . esc_html( $title ) . ( $required ? ' *' : '' ) . '</legend>';
        if ( in_array( $type, array( 'radio', 'select', 'checkbox' ), true ) ) {
            if ( 'select' === $type ) {
                echo '<select name="bcpo_options[' . esc_attr( $gi ) . ']" class="bcpo-select">';
                echo '<option value="">' . esc_html__( 'Choose', 'blocksy-child' ) . '</option>';
            }
            foreach ( $group['options'] as $oi => $opt ) {
                $label = isset( $opt['label'] ) ? $opt['label'] : '';
                $price = isset( $opt['price'] ) ? floatval( $opt['price'] ) : 0;
                if ( 'radio' === $type ) {
                    printf( '<p><label><input type="radio" name="bcpo_options[%1$s]" value="%2$s"> %3$s</label></p>', esc_attr( $gi ), esc_attr( $oi ), esc_html( $label ) );
                } elseif ( 'checkbox' === $type ) {
                    $price_text = ( isset( $opt['price'] ) && floatval( $opt['price'] ) > 0 ) ? wc_price( floatval( $opt['price'] ) ) : esc_html__( 'مجاني', 'blocksy-child' );
                    // Render checkbox as a button-like label with price
                    printf(
                        '<label class="bcpo-multi-btn"><input type="checkbox" name="bcpo_options[%1$s][]" value="%2$s" /><span class="bcpo-btn-label">%3$s</span><span class="bcpo-btn-price">%4$s</span></label>',
                        esc_attr( $gi ),
                        esc_attr( $oi ),
                        esc_html( $label ),
                        wp_kses_post( $price_text )
                    );
                } else { // select
                    printf( '<option value="%1$s">%2$s</option>', esc_attr( $oi ), esc_html( $label ) );
                }
            }
            if ( 'select' === $type ) {
                echo '</select>';
            }
        } else { // text
            printf( '<p><input type="text" name="bcpo_options_text[%1$s]" class="bcpo-text" placeholder="%2$s"></p>', esc_attr( $gi ), esc_attr__( 'Enter text', 'blocksy-child' ) );
        }
        echo '</fieldset>';
    }
    echo '</div>';
}

// Validate required options on add-to-cart
add_filter( 'woocommerce_add_to_cart_validation', 'bcpo_validate_add_to_cart', 10, 3 );
function bcpo_validate_add_to_cart( $passed, $product_id, $quantity ) {
    $groups = function_exists( 'bcpo_get_saved_options' ) ? bcpo_get_saved_options( $product_id ) : array();
    if ( empty( $groups ) ) {
        return $passed;
    }

    foreach ( $groups as $gi => $group ) {
        $required = ! empty( $group['required'] );
        if ( ! $required ) {
            continue;
        }
        $type = isset( $group['type'] ) ? $group['type'] : 'text';
        if ( 'text' === $type ) {
            $val = isset( $_POST['bcpo_options_text'][ $gi ] ) ? trim( wp_unslash( $_POST['bcpo_options_text'][ $gi ] ) ) : '';
            if ( '' === $val ) {
                wc_add_notice( sprintf( __( 'Please fill required option: %s', 'blocksy-child' ), $group['title'] ), 'error' );
                return false;
            }
        } else {
            $val = isset( $_POST['bcpo_options'][ $gi ] ) ? wp_unslash( $_POST['bcpo_options'][ $gi ] ) : '';
            if ( empty( $val ) ) {
                wc_add_notice( sprintf( __( 'Please choose required option: %s', 'blocksy-child' ), $group['title'] ), 'error' );
                return false;
            }
        }
    }
    return $passed;
}

// Attach selected options to cart item
add_filter( 'woocommerce_add_cart_item_data', 'bcpo_add_cart_item_data', 10, 3 );
function bcpo_add_cart_item_data( $cart_item_meta, $product_id, $variation_id ) {
    $groups = function_exists( 'bcpo_get_saved_options' ) ? bcpo_get_saved_options( $product_id ) : array();
    if ( empty( $groups ) ) {
        return $cart_item_meta;
    }

    $selected = array();
    $extra = 0;
    foreach ( $groups as $gi => $group ) {
        $type = isset( $group['type'] ) ? $group['type'] : 'text';
        if ( 'text' === $type ) {
            $val = isset( $_POST['bcpo_options_text'][ $gi ] ) ? sanitize_text_field( wp_unslash( $_POST['bcpo_options_text'][ $gi ] ) ) : '';
            if ( '' !== $val ) {
                $selected[ $gi ] = array( 'type' => 'text', 'value' => $val );
            }
        } else {
            $posted = isset( $_POST['bcpo_options'][ $gi ] ) ? wp_unslash( $_POST['bcpo_options'][ $gi ] ) : '';
            if ( $posted === '' ) {
                continue;
            }
            if ( is_array( $posted ) ) {
                // checkboxes
                $vals = array();
                foreach ( $posted as $oi ) {
                    $oi = intval( $oi );
                    if ( isset( $group['options'][ $oi ] ) ) {
                        $opt = $group['options'][ $oi ];
                        $vals[] = array( 'label' => $opt['label'], 'price' => floatval( $opt['price'] ) );
                        $extra += floatval( $opt['price'] );
                    }
                }
                if ( ! empty( $vals ) ) {
                    $selected[ $gi ] = array( 'type' => 'checkbox', 'values' => $vals );
                }
            } else {
                $oi = intval( $posted );
                if ( isset( $group['options'][ $oi ] ) ) {
                    $opt = $group['options'][ $oi ];
                    $selected[ $gi ] = array( 'type' => $type, 'value' => array( 'label' => $opt['label'], 'price' => floatval( $opt['price'] ) ) );
                    $extra += floatval( $opt['price'] );
                }
            }
        }
    }

    if ( ! empty( $selected ) ) {
        $cart_item_meta['bcpo_options'] = $selected;
        $product = wc_get_product( $product_id );
        $cart_item_meta['bcpo_base_price'] = $product ? floatval( $product->get_price() ) : 0;
        $cart_item_meta['bcpo_extra'] = round( $extra, 2 );
        $cart_item_meta['unique_key'] = md5( microtime() . rand() );
    }

    return $cart_item_meta;
}

// Adjust cart item price using stored base price + extra
add_action( 'woocommerce_before_calculate_totals', 'bcpo_before_calculate_totals', 20 );
function bcpo_before_calculate_totals( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
        return;
    }
    foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
        if ( empty( $cart_item['bcpo_extra'] ) ) {
            continue;
        }
        $base = isset( $cart_item['bcpo_base_price'] ) ? floatval( $cart_item['bcpo_base_price'] ) : floatval( $cart_item['data']->get_price() );
        $new = $base + floatval( $cart_item['bcpo_extra'] );
        $cart_item['data']->set_price( $new );
    }
}

// Show selected options in cart and mini-cart
add_filter( 'woocommerce_get_item_data', 'bcpo_get_item_data', 10, 2 );
function bcpo_get_item_data( $item_data, $cart_item ) {
    if ( empty( $cart_item['bcpo_options'] ) ) {
        return $item_data;
    }
    foreach ( $cart_item['bcpo_options'] as $gi => $info ) {
        if ( 'text' === $info['type'] ) {
            $item_data[] = array( 'key' => '', 'value' => sanitize_text_field( $info['value'] ) );
        } elseif ( 'checkbox' === $info['type'] ) {
            $vals = array_map( function( $v ) { return sanitize_text_field( $v['label'] ); }, $info['values'] );
            $item_data[] = array( 'key' => '', 'value' => implode( ', ', $vals ) );
        } else {
            $item_data[] = array( 'key' => '', 'value' => sanitize_text_field( $info['value']['label'] ) );
        }
    }
    return $item_data;
}

// Persist options to order line items
add_action( 'woocommerce_checkout_create_order_line_item', 'bcpo_order_item_meta', 10, 4 );
function bcpo_order_item_meta( $item, $cart_item_key, $values, $order ) {
    if ( ! empty( $values['bcpo_options'] ) ) {
        $item->add_meta_data( '_bcpo_options', $values['bcpo_options'], true );
        if ( ! empty( $values['bcpo_extra'] ) ) {
            $item->add_meta_data( '_bcpo_extra_price', $values['bcpo_extra'], true );
        }
    }
}
