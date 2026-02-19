<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function bcpo_allowed_field_types() {
    // For now only single-choice (radio) and multi-choice (checkbox) are allowed
    return array( 'radio', 'checkbox' );
}

function bcpo_get_saved_options( $post_id ) {
    $raw = get_post_meta( $post_id, '_custom_product_options', true );
    if ( empty( $raw ) ) {
        return array();
    }
    $data = json_decode( $raw, true );
    return is_array( $data ) ? $data : array();
}

function bcpo_sanitize_options_array( $input ) {
    if ( ! is_array( $input ) ) {
        return array();
    }
    $out = array();
    foreach ( $input as $group ) {
        $g = array();
        $g['title'] = isset( $group['title'] ) ? sanitize_text_field( $group['title'] ) : '';
        $g['type']  = ( isset( $group['type'] ) && in_array( $group['type'], bcpo_allowed_field_types(), true ) ) ? $group['type'] : 'radio';
        $g['required'] = ! empty( $group['required'] ) ? 1 : 0;
        $g['description'] = isset( $group['description'] ) ? sanitize_textarea_field( $group['description'] ) : '';
        $g['options'] = array();
        if ( ! empty( $group['options'] ) && is_array( $group['options'] ) ) {
            foreach ( $group['options'] as $opt ) {
                if ( ! isset( $opt['label'] ) ) {
                    continue;
                }
                $label = sanitize_text_field( $opt['label'] );
                if ( '' === $label ) {
                    continue;
                }
                $price = isset( $opt['price'] ) ? floatval( $opt['price'] ) : 0;
                $price = max( 0, round( $price, 2 ) );
                $g['options'][] = array(
                    'label' => $label,
                    'price' => $price,
                );
            }
        }
        $out[] = $g;
    }
    return $out;
}
