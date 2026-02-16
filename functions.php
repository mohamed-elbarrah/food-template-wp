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
		'show_more_text'=> 'عرض المزيد',
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

	// Render using the theme template so archive & shortcode use same markup
	$out = '<div class="product-grid" data-columns="' . esc_attr( $columns ) . '">';

	ob_start();
	while ( $q->have_posts() ) {
		$q->the_post();
		// This will load our child theme's woocommerce/content-product.php
		wc_get_template_part( 'content', 'product' );
	}
	$out .= ob_get_clean();

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

/**
 * Remove default WooCommerce result count / ordering and render custom category tabs.
 */
add_action( 'wp', function() {
	// Remove default WooCommerce output if present.
	// These are the default Woo hooks for result count and catalog ordering.
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );

	// Some themes (like Blocksy) may wrap both outputs in a single helper function.
	// If you identify the parent wrapper function name, remove it here too, for example:
	// remove_action( 'woocommerce_before_shop_loop', 'blocksy_woo_listing_top', 15 );
}, 20 );

/**
 * Output horizontally-scrollable product category tabs above the product grid.
 */
function blocksy_child_render_category_tabs() {
	if ( ! function_exists( 'get_terms' ) ) {
		return;
	}

	$shop_url = get_permalink( wc_get_page_id( 'shop' ) );

	// Get product categories (only top-level for tabs, optional change)
	$terms = get_terms( array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => true,
		'parent'     => 0,
		'orderby'    => 'menu_order',
	) );

	// Determine current category slug if on a category archive
	$current_cat = '';
	if ( is_tax( 'product_cat' ) ) {
		$queried = get_queried_object();
		if ( isset( $queried->slug ) ) {
			$current_cat = $queried->slug;
		}
	} elseif ( isset( $_GET['product_cat'] ) ) {
		$current_cat = sanitize_text_field( wp_unslash( $_GET['product_cat'] ) );
	}

	echo '<div class="product-cat-wrap">';
	echo '<h2 class="product-cat-title">' . esc_html__( 'اختر طلبك من القائمة أسفله', 'blocksy-child' ) . '</h2>';
	echo '<nav class="product-cat-tabs" role="tablist" aria-label="Product categories">';
	// All / Shop link
	$all_class = $current_cat ? '' : ' active';
	echo '<a href="' . esc_url( $shop_url ) . '" class="cat-tab' . esc_attr( $all_class ) . '" data-cat="">' . esc_html__( 'الكل', 'blocksy-child' ) . '</a>';

	if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
		foreach ( $terms as $term ) {
			$link = get_term_link( $term );
			$active = ( $current_cat && $current_cat === $term->slug ) ? ' active' : '';
			echo '<a href="' . esc_url( $link ) . '" class="cat-tab' . esc_attr( $active ) . '" data-cat="' . esc_attr( $term->slug ) . '">' . esc_html( $term->name ) . '</a>';
		}
	}

	echo '</nav>';
	echo '</div>';
}

// Add tabs to the shop loop area before the product grid (low priority so it appears above)
add_action( 'woocommerce_before_shop_loop', 'blocksy_child_render_category_tabs', 5 );

/**
 * Register Customizer color controls so admin can change theme colors.
 */
function blocksy_child_customize_register( $wp_customize ) {
	// Add colors section
	$wp_customize->add_section( 'blocksy_child_colors', array(
		'title'    => __( 'Blocksy Child Colors', 'blocksy-child' ),
		'priority' => 30,
	));

	// Primary orange
	$wp_customize->add_setting( 'blocksy_child_primary_orange', array(
		'default'           => '#f58220',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'refresh',
	));
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'blocksy_child_primary_orange_ctrl', array(
		'label'    => __( 'Primary orange', 'blocksy-child' ),
		'section'  => 'blocksy_child_colors',
		'settings' => 'blocksy_child_primary_orange',
	)));

	// Yellow (accent)
	$wp_customize->add_setting( 'blocksy_child_yellow', array(
		'default'           => '#FFC20E',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'refresh',
	));
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'blocksy_child_yellow_ctrl', array(
		'label'    => __( 'Accent yellow', 'blocksy-child' ),
		'section'  => 'blocksy_child_colors',
		'settings' => 'blocksy_child_yellow',
	)));

	// Background light
	$wp_customize->add_setting( 'blocksy_child_bg_light', array(
		'default'           => '#f9f9f9',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'refresh',
	));
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'blocksy_child_bg_light_ctrl', array(
		'label'    => __( 'Background (light)', 'blocksy-child' ),
		'section'  => 'blocksy_child_colors',
		'settings' => 'blocksy_child_bg_light',
	)));

	// Text gray
	$wp_customize->add_setting( 'blocksy_child_text_gray', array(
		'default'           => '#a0a0a0',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'refresh',
	));
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'blocksy_child_text_gray_ctrl', array(
		'label'    => __( 'Text gray', 'blocksy-child' ),
		'section'  => 'blocksy_child_colors',
		'settings' => 'blocksy_child_text_gray',
	)));

	// Text dark (primary text)
	$wp_customize->add_setting( 'blocksy_child_text_dark', array(
		'default'           => '#0b1623',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'refresh',
	));
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'blocksy_child_text_dark_ctrl', array(
		'label'    => __( 'Text dark (primary)', 'blocksy-child' ),
		'section'  => 'blocksy_child_colors',
		'settings' => 'blocksy_child_text_dark',
	)));

	// Button / dark accent
	$wp_customize->add_setting( 'blocksy_child_button_dark', array(
		'default'           => '#081a3a',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'refresh',
	));
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'blocksy_child_button_dark_ctrl', array(
		'label'    => __( 'Button / dark accent', 'blocksy-child' ),
		'section'  => 'blocksy_child_colors',
		'settings' => 'blocksy_child_button_dark',
	)));
}
add_action( 'customize_register', 'blocksy_child_customize_register' );

/**
 * Print inline CSS variables for colors from Customizer settings.
 */
function blocksy_child_print_color_vars() {
	$primary = get_theme_mod( 'blocksy_child_primary_orange', '#f58220' );
	$yellow  = get_theme_mod( 'blocksy_child_yellow', '#FFC20E' );
	$bg      = get_theme_mod( 'blocksy_child_bg_light', '#f9f9f9' );
	$textg   = get_theme_mod( 'blocksy_child_text_gray', '#a0a0a0' );
	$textd   = get_theme_mod( 'blocksy_child_text_dark', '#0b1623' );
	$buttond = get_theme_mod( 'blocksy_child_button_dark', '#081a3a' );

	// sanitize just in case
	$primary = sanitize_hex_color( $primary ) ?: '#f58220';
	$yellow  = sanitize_hex_color( $yellow ) ?: '#FFC20E';
	$bg      = sanitize_hex_color( $bg ) ?: '#f9f9f9';
	$textg   = sanitize_hex_color( $textg ) ?: '#a0a0a0';

	echo '<style id="blocksy-child-color-vars">\n:root{';
	// Core palette coming from Customizer
	echo "--primary-orange: {$primary}; --yellow: {$yellow}; --bg-light: {$bg}; --text-gray: {$textg}; --text-dark: {$textd}; --button-dark: {$buttond};";
	// Semantic aliases and theme palette fallbacks (map to Blocksy palette vars when available)
	echo "--primary-orange-hover: {$primary}; --muted-border: #e5e7eb; --text-muted: {$textg}; --bg-muted: {$bg};";
	echo "--theme-color-1: var(--paletteColor1, #212121); --theme-color-2: var(--paletteColor2, #4a4a4a); --theme-color-3: var(--paletteColor3, #2196f3); --theme-color-4: var(--paletteColor4, #1976d2); --theme-color-5: var(--paletteColor5, #f5f5f5); --theme-color-6: var(--paletteColor6, #e0e0e0); --theme-color-7: var(--paletteColor7, #ffffff); --theme-color-8: var(--paletteColor8, #ffffff);";
	echo '}</style>' . "\n";
}
add_action( 'wp_head', 'blocksy_child_print_color_vars', 5 );

// Load product options admin module
if ( file_exists( get_stylesheet_directory() . '/inc/product-options/init.php' ) ) {
	require_once get_stylesheet_directory() . '/inc/product-options/init.php';
}
