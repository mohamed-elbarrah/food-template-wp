<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/admin.php';
require_once __DIR__ . '/save.php';
require_once __DIR__ . '/frontend.php';

// Register meta so block editor / REST can save product options
add_action( 'init', function() {
    if ( function_exists( 'register_post_meta' ) ) {
        register_post_meta( 'product', '_custom_product_options', array(
            'show_in_rest'      => true,
            'single'            => true,
            'type'              => 'string',
            'sanitize_callback' => function( $value ) {
                // Accept either JSON string or array; sanitize using existing helper
                if ( is_string( $value ) ) {
                    $decoded = json_decode( $value, true );
                } else {
                    $decoded = is_array( $value ) ? $value : array();
                }
                if ( ! is_array( $decoded ) ) {
                    return '';
                }
                $san = bcpo_sanitize_options_array( $decoded );
                return wp_json_encode( $san, JSON_UNESCAPED_UNICODE );
            },
        ) );
    }
} );

add_action( 'admin_enqueue_scripts', function( $hook ) {
    if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
        return;
    }
    $screen = get_current_screen();
    if ( ! $screen || 'product' !== $screen->post_type ) {
        return;
    }
    $base = get_stylesheet_directory_uri() . '/inc/product-options/assets';
    wp_enqueue_script( 'bcpo-admin', $base . '/admin.js', array( 'jquery' ), '1.0', true );
    wp_enqueue_style( 'bcpo-admin', $base . '/admin.css', array(), '1.0' );
} );

// Enqueue frontend styles when viewing a single product
add_action( 'wp_enqueue_scripts', function() {
    if ( ! is_product() ) {
        return;
    }
    $base = get_stylesheet_directory_uri() . '/inc/product-options/assets';
    wp_enqueue_style( 'bcpo-frontend', $base . '/frontend.css', array(), '1.0' );
} );
