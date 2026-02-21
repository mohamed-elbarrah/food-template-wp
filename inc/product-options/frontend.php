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

    $product = wc_get_product( $product_id );
    $base_price = $product ? floatval( $product->get_price() ) : 0;
    $price_decimals = intval( get_option( 'woocommerce_price_num_decimals', wc_get_price_decimals() ) );
    $thousand_sep = get_option( 'woocommerce_price_thousand_sep', ',' );
    $decimal_sep = get_option( 'woocommerce_price_decimal_sep', '.' );
    $currency_symbol = get_woocommerce_currency_symbol();
    $currency_pos = get_option( 'woocommerce_currency_pos', 'left' );

    echo '<div class="bcpo-frontend" data-base-price="' . esc_attr( $base_price ) . '" data-price-decimals="' . esc_attr( $price_decimals ) . '" data-price-thousand="' . esc_attr( $thousand_sep ) . '" data-price-decimal="' . esc_attr( $decimal_sep ) . '" data-price-symbol="' . esc_attr( $currency_symbol ) . '" data-price-pos="' . esc_attr( $currency_pos ) . '">';
    foreach ( $groups as $gi => $group ) {
        $title    = isset( $group['title'] ) ? $group['title'] : '';
        $type     = isset( $group['type'] ) ? $group['type'] : 'text';
        $required = ! empty( $group['required'] );

        // expose whether the group is required to the frontend via data-required
        $req_attr = $required ? ' data-required="1"' : '';
        echo '<fieldset class="bcpo-group-frontend" data-type="' . esc_attr( $type ) . '"' . $req_attr . '>';
        echo '<legend class="bcpo-group-title">' . esc_html( $title ) . ( $required ? ' *' : '' ) . '</legend>';

        if ( in_array( $type, array( 'radio', 'select', 'checkbox' ), true ) ) {
            echo '<div class="food-options-container" data-type="' . esc_attr( $type ) . '">';
            foreach ( $group['options'] as $oi => $opt ) {
                $label = isset( $opt['label'] ) ? $opt['label'] : '';
                $price = isset( $opt['price'] ) ? floatval( $opt['price'] ) : 0;
                $price_text = $price > 0 ? ( '+ ' . wc_price( $price ) ) : esc_html__( 'مجاني', 'blocksy-child' );
                $price_class = $price > 0 ? '' : ' free';

                if ( 'checkbox' === $type ) {
                    printf(
                        '<label class="option-item%4$s" data-price="%7$s"><input type="checkbox" class="bcpo-hidden-input" name="bcpo_options[%1$s][]" value="%2$s" data-price="%7$s" /><span class="option-name">%3$s</span><span class="option-price-badge%5$s">%6$s</span></label>',
                        esc_attr( $gi ),
                        esc_attr( $oi ),
                        esc_html( $label ),
                        '',
                        esc_attr( $price_class ),
                        wp_kses_post( $price_text )
                        , esc_attr( $price )
                    );
                } else {
                    // radio / select behave like single-choice pills
                    printf(
                        '<label class="option-item" data-price="%7$s"><input type="radio" class="bcpo-hidden-input" name="bcpo_options[%1$s]" value="%2$s" data-price="%7$s" /><span class="option-name">%3$s</span><span class="option-price-badge%5$s">%6$s</span></label>',
                        esc_attr( $gi ),
                        esc_attr( $oi ),
                        esc_html( $label ),
                        '',
                        esc_attr( $price_class ),
                        wp_kses_post( $price_text )
                        , esc_attr( $price )
                    );
                }
            }
            echo '</div>';
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
            // support radio (single string) and checkbox (array). Be careful: PHP considers the string '0' empty,
            // so test explicitly for empty string/null rather than using empty() which rejects valid '0' values.
            if ( isset( $_POST['bcpo_options'][ $gi ] ) ) {
                $val = wp_unslash( $_POST['bcpo_options'][ $gi ] );
            } else {
                $val = null;
            }
            if ( is_array( $val ) ) {
                if ( count( $val ) === 0 ) {
                    wc_add_notice( sprintf( __( 'Please choose required option: %s', 'blocksy-child' ), $group['title'] ), 'error' );
                    return false;
                }
            } else {
                // treat null or empty string as not provided; accept '0' as valid selection
                if ( $val === null || $val === '' ) {
                    wc_add_notice( sprintf( __( 'Please choose required option: %s', 'blocksy-child' ), $group['title'] ), 'error' );
                    return false;
                }
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
            // always include text groups so cart/checkout can display a placeholder when empty
            $selected[ $gi ] = array( 'type' => 'text', 'value' => $val );
        } else {
            $posted = isset( $_POST['bcpo_options'][ $gi ] ) ? wp_unslash( $_POST['bcpo_options'][ $gi ] ) : '';
            // if nothing posted for this group, record an empty marker so cart shows "لا شيء"
            if ( $posted === '' ) {
                if ( 'checkbox' === $type ) {
                    $selected[ $gi ] = array( 'type' => 'checkbox', 'values' => array() );
                } else {
                    $selected[ $gi ] = array( 'type' => $type, 'value' => null );
                }
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
                } else {
                    // keep empty checkbox group marker
                    if ( ! isset( $selected[ $gi ] ) ) {
                        $selected[ $gi ] = array( 'type' => 'checkbox', 'values' => array() );
                    }
                }
            } else {
                $oi = intval( $posted );
                if ( isset( $group['options'][ $oi ] ) ) {
                    $opt = $group['options'][ $oi ];
                    $selected[ $gi ] = array( 'type' => $type, 'value' => array( 'label' => $opt['label'], 'price' => floatval( $opt['price'] ) ) );
                    $extra += floatval( $opt['price'] );
                } else {
                    // posted value didn't match an option - record as null so cart shows "لا شيء"
                    if ( ! isset( $selected[ $gi ] ) ) {
                        $selected[ $gi ] = array( 'type' => $type, 'value' => null );
                    }
                }
            }
        }
    }
    // always attach options meta (may contain empty markers) so cart/checkout can render every group
    $cart_item_meta['bcpo_options'] = $selected;
    $product = wc_get_product( $product_id );
    $cart_item_meta['bcpo_base_price'] = $product ? floatval( $product->get_price() ) : 0;
    $cart_item_meta['bcpo_extra'] = round( $extra, 2 );
    $cart_item_meta['unique_key'] = md5( microtime() . rand() );

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
            if ( empty( $cart_item['bcpo_options'] ) ) {
                return $item_data;
            }

            // attempt to retrieve group titles so cart displays meaningful keys
            $product_id = 0;
            if ( isset( $cart_item['product_id'] ) ) {
                $product_id = intval( $cart_item['product_id'] );
            } elseif ( isset( $cart_item['data'] ) && is_object( $cart_item['data'] ) ) {
                try { $product_id = intval( $cart_item['data']->get_id() ); } catch ( Exception $e ) { $product_id = 0; }
            }
            $groups = $product_id ? ( function_exists( 'bcpo_get_saved_options' ) ? bcpo_get_saved_options( $product_id ) : array() ) : array();

            foreach ( $cart_item['bcpo_options'] as $gi => $info ) {
                $title = isset( $groups[ $gi ]['title'] ) ? sanitize_text_field( $groups[ $gi ]['title'] ) : '';
                if ( 'text' === $info['type'] ) {
                    $val = isset( $info['value'] ) ? sanitize_text_field( $info['value'] ) : '';
                    $item_data[] = array( 'key' => $title, 'name' => $title, 'value' => $val );
                } elseif ( 'checkbox' === $info['type'] ) {
                    $vals = array();
                    if ( ! empty( $info['values'] ) && is_array( $info['values'] ) ) {
                        $vals = array_map( function( $v ) { return sanitize_text_field( $v['label'] ); }, $info['values'] );
                    }
                    $item_data[] = array( 'key' => $title, 'name' => $title, 'value' => ( empty( $vals ) ? 'لا شيء' : implode( ', ', $vals ) ) );
                } else {
                    $label = isset( $info['value']['label'] ) ? sanitize_text_field( $info['value']['label'] ) : '';
                    $item_data[] = array( 'key' => $title, 'name' => $title, 'value' => ( $label === '' ? 'لا شيء' : $label ) );
                }
            }
        if ( 'text' === $info['type'] ) {
            $val = isset( $info['value'] ) ? sanitize_text_field( $info['value'] ) : '';
            $item_data[] = array( 'key' => '', 'value' => ( $val === '' ? 'لا شيء' : $val ) );
        } elseif ( 'checkbox' === $info['type'] ) {
            $vals = array();
            if ( ! empty( $info['values'] ) && is_array( $info['values'] ) ) {
                $vals = array_map( function( $v ) { return sanitize_text_field( $v['label'] ); }, $info['values'] );
            }
            $item_data[] = array( 'key' => '', 'value' => ( empty( $vals ) ? 'لا شيء' : implode( ', ', $vals ) ) );
        } else {
            $label = isset( $info['value']['label'] ) ? sanitize_text_field( $info['value']['label'] ) : '';
            $item_data[] = array( 'key' => '', 'value' => ( $label === '' ? 'لا شيء' : $label ) );
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
