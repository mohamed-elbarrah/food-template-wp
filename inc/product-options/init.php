<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/admin.php';
require_once __DIR__ . '/save.php';
require_once __DIR__ . '/frontend.php';

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
