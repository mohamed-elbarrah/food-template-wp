<?php
if (! defined('WP_DEBUG')) {
	die( 'Direct access forbidden.' );
}
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
	wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array( 'parent-style' ), wp_get_theme()->get( 'Version' ) );
});

/**
 * Shortcode to render food product grid for Elementor or any content area.
 * Usage: [food_products limit="6" columns="3" category=""]
 */
function blocksy_child_food_products_shortcode( $atts ) {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return '<p>WooCommerce not active.</p>';
	}

	$atts = shortcode_atts( array(
		'limit'    => 6,
		'columns'  => 3,
		'category' => '',
	), $atts, 'food_products' );

	$limit   = absint( $atts['limit'] );
	$columns = absint( $atts['columns'] );
	$cat     = sanitize_text_field( $atts['category'] );

	// Use WP_Query to fetch products so category by slug works reliably
	$query_args = array(
		'post_type'      => 'product',
		'posts_per_page' => $limit,
		'post_status'    => 'publish',
		'orderby'        => 'date',
	);

	if ( $cat ) {
		$cats = array_map( 'trim', explode( ',', $cat ) );
		$query_args['tax_query'] = array(
			array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => $cats,
			),
		);
	}

	$q = new WP_Query( $query_args );

	if ( ! $q->have_posts() ) {
		return '<p class="no-products">No products found.</p>';
	}

	$out = '<div class="product-grid" data-columns="' . esc_attr( $columns ) . '">';

	while ( $q->have_posts() ) {
		$q->the_post();
		$prod_id = get_the_ID();
		$product  = wc_get_product( $prod_id );
		if ( ! $product ) {
			continue;
		}

		$permalink = esc_url( get_permalink( $prod_id ) );
		$title     = esc_html( $product->get_name() );
		$excerpt   = wp_kses_post( wp_trim_words( $product->get_short_description() ? $product->get_short_description() : $product->get_description(), 25, '...' ) );

		$display_price = wc_get_price_to_display( $product );
		$decimals      = wc_get_price_decimals();
		$decimal_sep   = wc_get_price_decimal_separator();
		$thousand_sep  = wc_get_price_thousand_separator();
		$amount        = number_format( (float) $display_price, $decimals, $decimal_sep, $thousand_sep );
		$currency_code = get_woocommerce_currency();

		$out .= '<div class="food-card">';
		$out .= '<div class="food-card-info">';
		$out .= '<a class="food-link" href="' . $permalink . '"><h2 class="food-title">' . $title . '</h2></a>';
		$out .= '<p class="food-description">' . $excerpt . '</p>';
		$out .= '<div class="food-price"><span class="price-code">' . esc_html( $currency_code ) . '</span> <span class="price-amount">' . esc_html( $amount ) . '</span></div>';
		$out .= '</div>'; // .food-card-info

		$out .= '<div class="food-card-media"><div class="yellow-box">';
		// product image
		$out .= get_the_post_thumbnail( $prod_id, 'woocommerce_thumbnail' );

		// add button
		if ( $product->is_type( 'simple' ) ) {
			$out .= '<div class="add-button-wrapper"><a href="' . esc_url( $product->add_to_cart_url() ) . '" class="add-btn add_to_cart_button" aria-label="'. esc_attr__( 'Add to cart', 'blocksy-child' ) .'">+</a></div>';
		} else {
			$out .= '<div class="add-button-wrapper"><a href="' . $permalink . '" class="add-btn" aria-label="'. esc_attr__( 'View product', 'blocksy-child' ) .'">+</a></div>';
		}

		$out .= '</div></div>'; // .yellow-box .food-card-media
		$out .= '</div>'; // .food-card
	}

	wp_reset_postdata();

	$out .= '</div>'; // .product-grid

	return $out;
}
add_shortcode( 'food_products', 'blocksy_child_food_products_shortcode' );

/* Make shortcode available in Elementor's Shortcode widget (it's global) */
