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
		'limit'         => 6,
		'columns'       => 3,
		'category'      => '',
		'show_more'     => 'false',
		'show_more_text'=> 'View All',
		'show_more_class'=> '',
	), $atts, 'food_products' );

	$limit    = absint( $atts['limit'] );
	$columns  = absint( $atts['columns'] );
	$cat      = sanitize_text_field( $atts['category'] );
	$show_more = filter_var( $atts['show_more'], FILTER_VALIDATE_BOOLEAN );
	$show_more_text = sanitize_text_field( $atts['show_more_text'] );
	$show_more_class = sanitize_text_field( $atts['show_more_class'] );

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

	// optional show more button (links to category archive if provided, else shop)
	if ( $show_more ) {
		$more_url = '';
		if ( $cat ) {
			$cats = array_map( 'trim', explode( ',', $cat ) );
			// try first category slug to build archive link
			$term = get_term_by( 'slug', $cats[0], 'product_cat' );
			if ( $term && ! is_wp_error( $term ) ) {
				$more_url = get_term_link( $term );
			} else {
				// fallback to shop with query param
				$shop_id = wc_get_page_id( 'shop' );
				$more_url = get_permalink( $shop_id );
				if ( $more_url ) {
					$more_url = add_query_arg( 'product_cat', $cats[0], $more_url );
				}
			}
		} else {
			$shop_id = wc_get_page_id( 'shop' );
			$more_url = get_permalink( $shop_id );
		}

		if ( $more_url ) {
			$out .= '<div class="food-products-footer">';
			$out .= '<a class="food-show-more ' . esc_attr( $show_more_class ) . '" href="' . esc_url( $more_url ) . '">' . esc_html( $show_more_text ) . '</a>';
			$out .= '</div>';
		}
	}

	return $out;
}
add_shortcode( 'food_products', 'blocksy_child_food_products_shortcode' );

/* Make shortcode available in Elementor's Shortcode widget (it's global) */
