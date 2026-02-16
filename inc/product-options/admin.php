<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Render UI inside the General > Pricing panel (below price fields) for better UX
add_action( 'woocommerce_product_options_pricing', function() {
    global $post;
    $saved = bcpo_get_saved_options( $post->ID );
    echo '<div id="bcpo_product_options" class="options_group">';
    wp_nonce_field( 'bcpo_save_options', 'bcpo_nonce' );
    echo '<p class="form-field"><label>' . esc_html__( 'Define product option groups', 'blocksy-child' ) . '</label></p>';
    echo '<div id="bcpo-groups" data-saved="' . esc_attr( wp_json_encode( $saved ) ) . '"></div>';
    echo '<p><button type="button" class="button" id="bcpo-add-group">' . esc_html__( 'Add Option Group', 'blocksy-child' ) . '</button></p>';
    echo '<input type="hidden" id="bcpo_payload" name="bcpo_payload" value="' . esc_attr( wp_json_encode( $saved ) ) . '">';
    echo '</div>';
} );
