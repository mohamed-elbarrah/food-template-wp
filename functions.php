<?php
if (! defined('WP_DEBUG')) {
	die( 'Direct access forbidden.' );
}
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
	wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array( 'parent-style' ), wp_get_theme()->get( 'Version' ) );
});

/**
 * Add bottom spacing to body only on single product pages to account for
 * the fixed purchase footer. This injects inline CSS attached to the
 * child-style handle so it only loads when `is_product()` is true.
 */
function blocksy_child_single_product_body_spacing() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	// Ensure child-style is registered/enqueued (it is above). Attach inline CSS.
	$css = 'body.single-product{padding-bottom:100px!important;}';
	wp_add_inline_style( 'child-style', $css );
}
add_action( 'wp_enqueue_scripts', 'blocksy_child_single_product_body_spacing', 20 );

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

/**
 * Shortcode: category_cards
 * Render product categories as horizontally scrollable cards matching supplied design.
 * Usage: [category_cards limit="6" parent="0"]
 */
function blocksy_child_category_cards_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'limit'  => 6,
		'parent' => 0,
	), $atts, 'category_cards' );

	$args = array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
		'parent'     => intval( $atts['parent'] ),
		'orderby'    => 'menu_order',
		'number'     => intval( $atts['limit'] ),
	);

	$terms = get_terms( $args );
	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return '<p class="no-cats">' . esc_html__( 'لا توجد فئات', 'blocksy-child' ) . '</p>';
	}

	$out  = '<div class="bcpo-cat-cards-wrap">';
	$out .= '<div class="bcpo-cat-viewport">';
	$out .= '<div class="bcpo-cat-track">';

	foreach ( $terms as $term ) {
		$thumb_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
		$img_url = $thumb_id ? esc_url( wp_get_attachment_image_url( $thumb_id, 'medium' ) ) : esc_url( wc_placeholder_img_src( 'medium' ) );
		$link = esc_url( get_term_link( $term ) );
		$name = esc_html( $term->name );

		$out .= '<a class="bcpo-cat-card" href="' . $link . '" aria-label="' . $name . '" style="background-image:url(' . $img_url . ')">';
		$out .= '<span class="bcpo-cat-label">' . $name . '</span>';
		$out .= '</a>';
	}

	$out .= '</div>'; // .bcpo-cat-track
	$out .= '</div>'; // .bcpo-cat-viewport
	$out .= '</div>'; // .bcpo-cat-cards-wrap

	// Use Swiper.js for smooth, accessible sliding instead of custom drag code.
	// Enqueue Swiper assets (CDN). These will be printed by WP in header/footer.
	wp_enqueue_style( 'bcpo-swiper', 'https://unpkg.com/swiper/swiper-bundle.min.css', array(), null );
	wp_enqueue_script( 'bcpo-swiper', 'https://unpkg.com/swiper/swiper-bundle.min.js', array(), null, true );

	// Wrap the previously-built track in Swiper structure.
	// NOTE: we already built the slides above; but to keep changes minimal we will
	// generate a unique container id and reconstruct the HTML here.

	// Generate a unique ID for this instance so multiple shortcodes can coexist.
	$bcpo_id = 'bcpo-cat-swiper-' . uniqid();

	// Recreate the markup using Swiper classes. We'll re-query terms to keep code simple.
	$terms = get_terms( $args );
	if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
		$out  = '<div class="bcpo-cat-cards-wrap">';
		$out .= '<div id="' . esc_attr( $bcpo_id ) . '" class="bcpo-cat-swiper swiper">';
		$out .= '<div class="swiper-wrapper">';

		foreach ( $terms as $term ) {
			$thumb_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
			$img_url = $thumb_id ? esc_url( wp_get_attachment_image_url( $thumb_id, 'medium' ) ) : esc_url( wc_placeholder_img_src( 'medium' ) );
			$link = esc_url( get_term_link( $term ) );
			$name = esc_html( $term->name );

			$out .= '<div class="swiper-slide">';
			$out .= '<a class="bcpo-cat-card" href="' . $link . '" aria-label="' . $name . '" style="background-image:url(' . $img_url . ')">';
			$out .= '<span class="bcpo-cat-label">' . $name . '</span>';
			$out .= '</a>';
			$out .= '</div>';
		}

		$out .= '</div>'; // .swiper-wrapper
		$out .= '</div>'; // .bcpo-cat-swiper
		$out .= '</div>'; // .bcpo-cat-cards-wrap
	} else {
		// fallback (shouldn't happen since we checked earlier)
		$out = '<p class="no-cats">' . esc_html__( 'لا توجد فئات', 'blocksy-child' ) . '</p>';
	}

	// Minimal styles for the cards (keeps original visual design, but uses Swiper layout)
	$out .= '<style>' . "\n";
	// Instance-scoped styles
	$out .= '#' . esc_attr( $bcpo_id ) . ' .bcpo-cat-cards-wrap{ margin:18px 0; }' . "\n";
	$out .= '#' . esc_attr( $bcpo_id ) . ' .bcpo-cat-swiper{ padding-bottom:6px; }' . "\n";
	$out .= '#' . esc_attr( $bcpo_id ) . ' .swiper-wrapper{ display:flex; align-items:stretch; }' . "\n";
	// Force slides to size to their content (override Swiper default width:100%)
	$out .= '#' . esc_attr( $bcpo_id ) . ' .swiper-slide{ width:auto !important; flex:0 0 auto; display:block; height:100%; position:relative; transition-property:transform; }' . "\n";
	$out .= '#' . esc_attr( $bcpo_id ) . ' .bcpo-cat-card{ display:block; min-width:260px; width:260px; height:140px; background-size:cover; background-position:center; border-radius:12px; box-shadow:0 10px 26px rgba(19,21,30,0.06); position:relative; text-decoration:none; color:#fff; }' . "\n";
	$out .= '#' . esc_attr( $bcpo_id ) . ' .bcpo-cat-label{ position:absolute; left:12px; bottom:12px; background:linear-gradient(90deg, rgba(0,0,0,0.45), rgba(0,0,0,0.18)); padding:6px 10px; border-radius:8px; font-weight:700; font-size:0.95rem; }' . "\n";
	$out .= '@media (max-width:900px){ #' . esc_attr( $bcpo_id ) . ' .bcpo-cat-card{ min-width:220px; width:220px; height:120px } }' . "\n";

	// Global gradient fade/mask effect applied to the container class
	$out .= '.bcpo-cat-cards-wrap{ position:relative; }' . "\n";
	$out .= '.bcpo-cat-cards-wrap .bcpo-cat-viewport{ -webkit-mask-image: linear-gradient(to right, transparent 0%, black 8%, black 92%, transparent 100%); mask-image: linear-gradient(to right, transparent 0%, black 8%, black 92%, transparent 100%); }' . "\n";
	$out .= '.bcpo-cat-cards-wrap::before, .bcpo-cat-cards-wrap::after{ content:""; position:absolute; top:0; bottom:0; width:72px; pointer-events:none; z-index:5; }' . "\n";
	$out .= '.bcpo-cat-cards-wrap::before{ left:0; background:linear-gradient(90deg, var(--bg-light, #ffffff) 0%, rgba(255,255,255,0) 100%); }' . "\n";
	$out .= '.bcpo-cat-cards-wrap::after{ right:0; background:linear-gradient(270deg, var(--bg-light, #ffffff) 0%, rgba(255,255,255,0) 100%); }' . "\n";

	$out .= '</style>';

	// Initialize Swiper for this instance. Use wp_add_inline_script so the init
	// runs after the Swiper bundle is printed (footer-safe).
	$init_js = "(function(){ if (typeof Swiper === 'undefined') return; new Swiper('#" . esc_js( $bcpo_id ) . "', { slidesPerView: 'auto', spaceBetween: 14, freeMode: true, freeModeMomentum: true, grabCursor: true, watchOverflow: true }); })();";
	wp_add_inline_script( 'bcpo-swiper', $init_js );

	return $out;
}
add_shortcode( 'category_cards', 'blocksy_child_category_cards_shortcode' );

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

// Load features modal helper (provides shortcode and template helper)
if ( file_exists( get_stylesheet_directory() . '/features.php' ) ) {
	require_once get_stylesheet_directory() . '/features.php';
}

/**
 * AJAX handler to submit a quick product rating.
 * Saves a comment with meta key 'rating' so WooCommerce can pick it up.
 */
function bcpo_ajax_submit_rating() {
	// check nonce
	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'bcpo-rate' ) ) {
		wp_send_json_error( array( 'message' => 'Invalid nonce' ), 403 );
	}

	$product_id = isset( $_POST['product_id'] ) ? intval( wp_unslash( $_POST['product_id'] ) ) : 0;
	$rating = isset( $_POST['rating'] ) ? intval( wp_unslash( $_POST['rating'] ) ) : 0;
	$comment = isset( $_POST['comment'] ) ? sanitize_text_field( wp_unslash( $_POST['comment'] ) ) : '';
	$comment_id = isset( $_POST['comment_id'] ) ? intval( wp_unslash( $_POST['comment_id'] ) ) : 0;

	if ( $product_id <= 0 || $rating < 1 || $rating > 5 ) {
		wp_send_json_error( array( 'message' => 'Invalid data' ), 400 );
	}

	$user = wp_get_current_user();
	$author_name = $user && $user->exists() ? $user->display_name : 'زائر';

	if ( $comment_id > 0 ) {
		// attempt to update existing comment - only allow owner or admin
		$existing = get_comment( $comment_id );
		if ( ! $existing || intval( $existing->comment_post_ID ) !== $product_id ) {
			wp_send_json_error( array( 'message' => 'Invalid comment' ), 400 );
		}
		$allowed = false;
		if ( $user && $user->exists() && intval( $existing->user_id ) === intval( $user->ID ) ) {
			$allowed = true;
		}
		if ( ! $allowed && current_user_can( 'manage_options' ) ) {
			$allowed = true; // admins may edit
		}
		if ( ! $allowed ) {
			wp_send_json_error( array( 'message' => 'Not allowed to edit this review' ), 403 );
		}

		$update = array(
			'comment_ID'      => $comment_id,
			'comment_content' => $comment,
			'comment_approved'=> 1,
		);
		$res = wp_update_comment( $update );
		if ( ! $res ) {
			wp_send_json_error( array( 'message' => 'Could not update rating' ), 500 );
		}
		update_comment_meta( $comment_id, 'rating', $rating );
		$saved_comment_id = $comment_id;
	} else {
		$commentdata = array(
			'comment_post_ID' => $product_id,
			'comment_author'  => $author_name,
			'comment_author_email' => $user && $user->exists() ? $user->user_email : '',
			'comment_content' => $comment,
			'comment_type'    => 'review',
			'comment_parent'  => 0,
			'user_id'         => $user && $user->exists() ? intval( $user->ID ) : 0,
			'comment_approved'=> 1,
		);

		$saved_comment_id = wp_insert_comment( $commentdata );
		if ( ! $saved_comment_id ) {
			wp_send_json_error( array( 'message' => 'Could not save rating' ), 500 );
		}

		// save rating meta in the format WooCommerce expects
		add_comment_meta( $saved_comment_id, 'rating', $rating );
	}

	// update product rating caches if WooCommerce function exists
	if ( function_exists( 'wc_update_product_review_count' ) ) {
		// update cached counts/rating
		try {
			wc_update_product_review_count( $product_id );
		} catch ( Exception $e ) {
			// ignore
		}
	}

	// Attempt to clear related caches/transients so later reads reflect new values
	if ( function_exists( 'wc_delete_product_transients' ) ) {
		wc_delete_product_transients( $product_id );
	}
	// clear post caches
	if ( function_exists( 'clean_post_cache' ) ) {
		clean_post_cache( $product_id );
	}

	// read back the updated product average so frontend can update UI without reload
	$product_obj = wc_get_product( $product_id );
	$new_average = $product_obj ? floatval( $product_obj->get_average_rating() ) : 0.0;

	wp_send_json_success( array( 'comment_id' => $saved_comment_id, 'average' => $new_average ) );
}
add_action( 'wp_ajax_bcpo_submit_rating', 'bcpo_ajax_submit_rating' );
add_action( 'wp_ajax_nopriv_bcpo_submit_rating', 'bcpo_ajax_submit_rating' );
