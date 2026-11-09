<?php
/** Opt-in sample store setup and catalog filters. */
defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', function() {
	add_theme_page(
		__( 'Food store setup', 'blocksy-child' ),
		__( 'Food store setup', 'blocksy-child' ),
		'edit_theme_options',
		'food-store-setup',
		'blocksy_child_demo_setup_page'
	);
} );

function blocksy_child_demo_setup_page() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	$done = isset( $_GET['food_demo_done'] ) && '1' === $_GET['food_demo_done'];
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Food store setup', 'blocksy-child' ); ?></h1>
		<p><?php esc_html_e( 'Create an example homepage, shop page, product categories, and sample products. This is optional and will not delete or overwrite your existing products. Demo products created by this tool can be removed manually.', 'blocksy-child' ); ?></p>
		<?php if ( ! class_exists( 'WooCommerce' ) ) : ?>
			<div class="notice notice-warning"><p><?php esc_html_e( 'Install and activate WooCommerce before creating the demo store.', 'blocksy-child' ); ?></p></div>
		<?php else : ?>
			<?php if ( $done ) : ?><div class="notice notice-success"><p><?php esc_html_e( 'Demo store setup is complete. Visit your homepage to preview it.', 'blocksy-child' ); ?></p></div><?php endif; ?>
			<form method="post">
				<?php wp_nonce_field( 'blocksy_child_install_demo', 'food_demo_nonce' ); ?>
				<input type="hidden" name="action" value="install_food_demo">
				<?php submit_button( __( 'Create demo store', 'blocksy-child' ) ); ?>
			</form>
		<?php endif; ?>
	</div>
	<?php
}

add_action( 'admin_init', function() {
	if ( ! is_admin() || empty( $_POST['action'] ) || 'install_food_demo' !== $_POST['action'] ) {
		return;
	}
	if ( ! current_user_can( 'edit_theme_options' ) || empty( $_POST['food_demo_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['food_demo_nonce'] ) ), 'blocksy_child_install_demo' ) ) {
		wp_die( esc_html__( 'You are not allowed to run this setup.', 'blocksy-child' ) );
	}
	if ( ! class_exists( 'WooCommerce' ) ) {
		wp_die( esc_html__( 'WooCommerce must be active first.', 'blocksy-child' ) );
	}
	blocksy_child_install_demo_store();
	wp_safe_redirect( add_query_arg( 'food_demo_done', '1', admin_url( 'themes.php?page=food-store-setup' ) ) );
	exit;
} );

function blocksy_child_install_demo_store() {
	$categories = array(
		'Burgers' => 'burgers',
		'Pizza' => 'pizza',
		'Drinks' => 'drinks',
		'Sides' => 'sides',
	);
	$term_ids = array();
	foreach ( $categories as $name => $slug ) {
		$term = term_exists( $slug, 'product_cat' );
		if ( ! $term ) {
			$term = wp_insert_term( $name, 'product_cat', array( 'slug' => $slug ) );
		}
		if ( ! is_wp_error( $term ) ) {
			$term_ids[ $slug ] = (int) ( is_array( $term ) ? $term['term_id'] : $term );
		}
	}

	$products = array(
		array( 'Classic Burger', 'Juicy grilled beef, fresh lettuce, tomato, and our house sauce.', 'burgers', '8.50' ),
		array( 'Crispy Chicken Burger', 'Golden crispy chicken with crunchy slaw and creamy sauce.', 'burgers', '7.95' ),
		array( 'Garden Veggie Burger', 'A delicious plant-based patty with garden-fresh toppings.', 'burgers', '7.50' ),
		array( 'Margherita Pizza', 'Tomato, mozzarella, and fragrant fresh basil.', 'pizza', '10.00' ),
		array( 'Pepperoni Pizza', 'Classic pepperoni, mozzarella, and rich tomato sauce.', 'pizza', '12.50' ),
		array( 'Golden Fries', 'Crispy seasoned fries, perfect for sharing.', 'sides', '3.50' ),
		array( 'Fresh Lemonade', 'Freshly squeezed lemons with a hint of mint.', 'drinks', '2.75' ),
		array( 'Chocolate Brownie', 'Rich chocolate brownie baked in-house.', 'sides', '4.25' ),
	);
	foreach ( $products as $row ) {
		$existing = get_posts( array( 'post_type' => 'product', 'post_status' => array( 'publish', 'draft', 'private' ), 'meta_key' => '_food_demo_product', 'meta_value' => sanitize_title( $row[0] ), 'fields' => 'ids', 'numberposts' => 1 ) );
		if ( $existing ) {
			continue;
		}
		$product = new WC_Product_Simple();
		$product->set_name( $row[0] );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'visible' );
		$product->set_description( $row[1] );
		$product->set_short_description( $row[1] );
		$product->set_regular_price( $row[3] );
		$product->set_price( $row[3] );
		$product_id = $product->save();
		update_post_meta( $product_id, '_food_demo_product', sanitize_title( $row[0] ) );
		if ( isset( $term_ids[ $row[2] ] ) ) {
			wp_set_object_terms( $product_id, array( $term_ids[ $row[2] ] ), 'product_cat' );
		}
	}

	$home = get_page_by_path( 'food-home' );
	if ( ! $home ) {
		$home_id = wp_insert_post( array(
			'post_type' => 'page', 'post_status' => 'publish', 'post_title' => __( 'Home', 'blocksy-child' ),
			'post_name' => 'food-home', 'post_content' => '[food_home]',
		) );
	} else {
		$home_id = $home->ID;
	}
	if ( ! is_wp_error( $home_id ) && $home_id ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', (int) $home_id );
	}

	$shop_id = wc_get_page_id( 'shop' );
	if ( $shop_id < 1 || ! get_post( $shop_id ) ) {
		$shop_id = wc_create_page( 'shop', 'woocommerce_shop_page_id', __( 'Menu', 'blocksy-child' ), '' );
	}
	if ( $shop_id && ! is_wp_error( $shop_id ) ) {
		update_option( 'woocommerce_shop_page_id', (int) $shop_id );
	}
	update_option( 'blocksy_child_demo_store_created', 1 );
}

add_shortcode( 'food_home', function() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return '<p>' . esc_html__( 'Install WooCommerce to display the food store.', 'blocksy-child' ) . '</p>';
	}
	$shop_url = get_permalink( wc_get_page_id( 'shop' ) );
	ob_start(); ?>
	<section class="food-home-hero">
		<div class="food-home-hero-content">
			<p class="food-home-eyebrow"><?php esc_html_e( 'FRESHLY MADE, JUST FOR YOU', 'blocksy-child' ); ?></p>
			<h1><?php esc_html_e( 'Good food. Good mood.', 'blocksy-child' ); ?></h1>
			<p><?php esc_html_e( 'Discover your new favorites, made fresh and delivered with care.', 'blocksy-child' ); ?></p>
			<a class="food-home-cta" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Explore the menu', 'blocksy-child' ); ?></a>
		</div>
	</section>
	<section class="food-home-section"><h2><?php esc_html_e( 'Browse categories', 'blocksy-child' ); ?></h2><?php echo do_shortcode( '[category_cards limit="4"]' ); ?></section>
	<section class="food-home-section"><h2><?php esc_html_e( 'Customer favorites', 'blocksy-child' ); ?></h2><?php echo do_shortcode( '[food_products limit="6" columns="3" show_more="true" show_more_text="See the full menu"]' ); ?></section>
	<?php return ob_get_clean();
} );

/** Add safe GET-based price/order filters to the main WooCommerce product query. */
add_action( 'woocommerce_before_shop_loop', function() {
	if ( ! is_shop() && ! is_product_taxonomy() ) {
		return;
	}
	$min = isset( $_GET['min_price'] ) ? wc_format_localized_price( wp_unslash( $_GET['min_price'] ) ) : '';
	$max = isset( $_GET['max_price'] ) ? wc_format_localized_price( wp_unslash( $_GET['max_price'] ) ) : '';
	$order = isset( $_GET['food_order'] ) ? sanitize_key( wp_unslash( $_GET['food_order'] ) ) : '';
	$action = is_shop() ? get_permalink( wc_get_page_id( 'shop' ) ) : get_term_link( get_queried_object() );
	if ( is_wp_error( $action ) ) {
		$action = '';
	}
	?>
	<form class="food-catalog-filters" method="get" action="<?php echo esc_url( $action ); ?>">
		<label><?php esc_html_e( 'Min price', 'blocksy-child' ); ?> <input type="number" name="min_price" min="0" step="0.01" value="<?php echo esc_attr( $min ); ?>"></label>
		<label><?php esc_html_e( 'Max price', 'blocksy-child' ); ?> <input type="number" name="max_price" min="0" step="0.01" value="<?php echo esc_attr( $max ); ?>"></label>
		<label><?php esc_html_e( 'Sort', 'blocksy-child' ); ?>
			<select name="food_order"><option value=""><?php esc_html_e( 'Featured', 'blocksy-child' ); ?></option>
				<option value="price_asc" <?php selected( $order, 'price_asc' ); ?>><?php esc_html_e( 'Price: low to high', 'blocksy-child' ); ?></option>
				<option value="price_desc" <?php selected( $order, 'price_desc' ); ?>><?php esc_html_e( 'Price: high to low', 'blocksy-child' ); ?></option>
				<option value="newest" <?php selected( $order, 'newest' ); ?>><?php esc_html_e( 'Newest', 'blocksy-child' ); ?></option>
			</select>
		</label>
		<button type="submit"><?php esc_html_e( 'Apply filters', 'blocksy-child' ); ?></button>
	</form>
	<?php
}, 6 );

add_action( 'woocommerce_product_query', function( $query ) {
	$min = isset( $_GET['min_price'] ) ? wc_format_localized_price( wp_unslash( $_GET['min_price'] ) ) : '';
	$max = isset( $_GET['max_price'] ) ? wc_format_localized_price( wp_unslash( $_GET['max_price'] ) ) : '';
	$min = is_numeric( $min ) ? max( 0, (float) $min ) : '';
	$max = is_numeric( $max ) ? max( 0, (float) $max ) : '';
	if ( '' !== $min || '' !== $max ) {
		$meta_query = (array) $query->get( 'meta_query' );
		$meta_query[] = array( 'key' => '_price', 'type' => 'DECIMAL(10,2)', 'value' => array( '' === $min ? 0 : $min, '' === $max ? 999999999 : $max ), 'compare' => 'BETWEEN' );
		$query->set( 'meta_query', $meta_query );
	}
	$order = isset( $_GET['food_order'] ) ? sanitize_key( wp_unslash( $_GET['food_order'] ) ) : '';
	if ( 'price_asc' === $order || 'price_desc' === $order ) {
		$query->set( 'meta_key', '_price' );
		$query->set( 'orderby', 'meta_value_num' );
		$query->set( 'order', 'price_asc' === $order ? 'ASC' : 'DESC' );
	} elseif ( 'newest' === $order ) {
		$query->set( 'orderby', 'date' );
		$query->set( 'order', 'DESC' );
	}
} );
