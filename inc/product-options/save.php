<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'woocommerce_process_product_meta', function( $post_id ) {
    if ( ! isset( $_POST['bcpo_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['bcpo_nonce'] ), 'bcpo_save_options' ) ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $payload = isset( $_POST['bcpo_payload'] ) ? wp_unslash( $_POST['bcpo_payload'] ) : '';
    if ( empty( $payload ) ) {
        delete_post_meta( $post_id, '_custom_product_options' );
        return;
    }

    $decoded = json_decode( $payload, true );
    if ( ! is_array( $decoded ) ) {
        // invalid JSON
        return;
    }

    $sanitized = bcpo_sanitize_options_array( $decoded );
    if ( empty( $sanitized ) ) {
        delete_post_meta( $post_id, '_custom_product_options' );
        return;
    }

    update_post_meta( $post_id, '_custom_product_options', wp_json_encode( $sanitized ) );
} );
