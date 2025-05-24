<?php
/**
 * Registers the Custom Post Type for Gift Box Bundles and handles its admin meta boxes.
 *
 * @package WooCommerceGiftBoxBuilder/Admin
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Registers the 'gift_box_bundle' Custom Post Type.
 *
 * Defines labels, arguments, and registers the CPT.
 * Hooked into 'init'.
 *
 * @since 1.0.0
 */
function wgbb_register_gift_box_bundle_cpt() {
	// Define labels for the CPT.
	$labels = array(
		'name'                  => _x( 'Gift Box Bundles', 'Post Type General Name', 'woocommerce-gift-box-builder' ),
		'singular_name'         => _x( 'Gift Box Bundle', 'Post Type Singular Name', 'woocommerce-gift-box-builder' ),
        'menu_name'             => __( 'Gift Box Bundles', 'woocommerce-gift-box-builder' ),
        'name_admin_bar'        => __( 'Gift Box Bundle', 'woocommerce-gift-box-builder' ),
        'archives'              => __( 'Gift Box Bundle Archives', 'woocommerce-gift-box-builder' ),
        'attributes'            => __( 'Gift Box Bundle Attributes', 'woocommerce-gift-box-builder' ),
        'parent_item_colon'     => __( 'Parent Gift Box Bundle:', 'woocommerce-gift-box-builder' ),
        'all_items'             => __( 'All Gift Box Bundles', 'woocommerce-gift-box-builder' ),
        'add_new_item'          => __( 'Add New Gift Box Bundle', 'woocommerce-gift-box-builder' ),
        'add_new'               => __( 'Add New Bundle', 'woocommerce-gift-box-builder' ),
        'new_item'              => __( 'New Gift Box Bundle', 'woocommerce-gift-box-builder' ),
        'edit_item'             => __( 'Edit Gift Box Bundle', 'woocommerce-gift-box-builder' ),
        'update_item'           => __( 'Update Gift Box Bundle', 'woocommerce-gift-box-builder' ),
        'view_item'             => __( 'View Gift Box Bundle', 'woocommerce-gift-box-builder' ),
        'view_items'            => __( 'View Gift Box Bundles', 'woocommerce-gift-box-builder' ),
        'search_items'          => __( 'Search Gift Box Bundles', 'woocommerce-gift-box-builder' ),
        'not_found'             => __( 'No Gift Box Bundles found', 'woocommerce-gift-box-builder' ),
        'not_found_in_trash'    => __( 'No Gift Box Bundles found in Trash', 'woocommerce-gift-box-builder' ),
        'featured_image'        => __( 'Featured Image', 'woocommerce-gift-box-builder' ),
        'set_featured_image'    => __( 'Set featured image', 'woocommerce-gift-box-builder' ),
        'remove_featured_image' => __( 'Remove featured image', 'woocommerce-gift-box-builder' ),
        'use_featured_image'    => __( 'Use as featured image', 'woocommerce-gift-box-builder' ),
        'insert_into_item'      => __( 'Insert into Gift Box Bundle', 'woocommerce-gift-box-builder' ),
        'uploaded_to_this_item' => __( 'Uploaded to this Gift Box Bundle', 'woocommerce-gift-box-builder' ),
        'items_list'            => __( 'Gift Box Bundles list', 'woocommerce-gift-box-builder' ),
        'items_list_navigation' => __( 'Gift Box Bundles list navigation', 'woocommerce-gift-box-builder' ),
        'filter_items_list'     => __( 'Filter Gift Box Bundles list', 'woocommerce-gift-box-builder' ),
    );
    $args   = array(
        'label'                 => __( 'Gift Box Bundle', 'woocommerce-gift-box-builder' ),
        'description'           => __( 'Custom Post Type for Gift Box Bundles', 'woocommerce-gift-box-builder' ),
        'labels'                => $labels,
        'supports'              => array( 'title', 'editor' ),
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 5,
        'menu_icon'             => 'dashicons-archive',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'has_archive'           => false,
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'capability_type'       => 'post',
		'rewrite'               => array( 'slug' => 'gift-box-bundles' ), // URL slug for the CPT.
	);

	// Define arguments for the CPT.
	$args = array(
		'label'                 => __( 'Gift Box Bundle', 'woocommerce-gift-box-builder' ),
		'description'           => __( 'Custom Post Type for Gift Box Bundles', 'woocommerce-gift-box-builder' ),
		'labels'                => $labels,
		'supports'              => array( 'title', 'editor' ), // Features supported in the CPT editor.
		'hierarchical'          => false,
		'public'                => true, // Makes the CPT publicly accessible.
		'show_ui'               => true, // Show in admin UI.
		'show_in_menu'          => true, // Show in admin menu.
		'menu_position'         => 5, // Position in the admin menu.
		'menu_icon'             => 'dashicons-archive', // Icon for the CPT.
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => true,
		'can_export'            => true,
		'has_archive'           => false, // No archive page for bundles themselves.
		'exclude_from_search'   => false, // Bundles can be searched.
		'publicly_queryable'    => true,
		'capability_type'       => 'post', // Standard post capabilities.
		'rewrite'               => array( 'slug' => 'gift-box-bundles' ), // URL slug for the CPT.
	);

	// Register the CPT.
	register_post_type( 'gift_box_bundle', $args );
}
add_action( 'init', 'wgbb_register_gift_box_bundle_cpt', 0 ); // Register CPT during WordPress initialization.

/**
 * Adds the meta box for configuring bundle steps.
 *
 * This function is hooked into 'add_meta_boxes'.
 *
 * @since 1.0.0
 */
function wgbb_add_bundle_configuration_meta_box() {
	add_meta_box(
		'wgbb_bundle_configuration', // Meta box ID.
        __( 'Bundle Configuration', 'woocommerce-gift-box-builder' ),
		__( 'Bundle Configuration', 'woocommerce-gift-box-builder' ), // Meta box title.
		'wgbb_render_bundle_configuration_meta_box', // Callback function to render content.
		'gift_box_bundle',                   // CPT slug.
		'normal',                            // Context (where it appears).
		'high'                               // Priority.
	);
}
add_action( 'add_meta_boxes', 'wgbb_add_bundle_configuration_meta_box' );

/**
 * Renders the content of the bundle configuration meta box.
 *
 * Outputs HTML for configuring up to 3 steps for the gift box.
 *
 * @since 1.0.0
 * @param WP_Post $post The current post object.
 */
function wgbb_render_bundle_configuration_meta_box( $post ) {
	// Add a nonce field for security.
	wp_nonce_field( 'wgbb_save_bundle_configuration_data', 'wgbb_bundle_configuration_nonce' );

	// Retrieve existing meta values for each step to populate the fields.
	$step1_type     = get_post_meta( $post->ID, '_wgbb_step1_type', true );
	$step1_products = get_post_meta( $post->ID, '_wgbb_step1_products', true );
	$step1_category = get_post_meta( $post->ID, '_wgbb_step1_category', true );

	$step2_type     = get_post_meta( $post->ID, '_wgbb_step2_type', true );
	$step2_products = get_post_meta( $post->ID, '_wgbb_step2_products', true );
	$step2_category = get_post_meta( $post->ID, '_wgbb_step2_category', true );

	$step3_type     = get_post_meta( $post->ID, '_wgbb_step3_type', true );
	$step3_products = get_post_meta( $post->ID, '_wgbb_step3_products', true );
	$step3_category = get_post_meta( $post->ID, '_wgbb_step3_category', true );

	// Product IDs are entered as comma-separated values.
	// Category selection uses a dropdown.
	?>
	<div class="wgbb-meta-box-content">
		<?php
		// Render configuration fields for each step.
		// Step 1: Box Selection
		wgbb_render_step_configuration( 1, __( 'Box', 'woocommerce-gift-box-builder' ), $step1_type, $step1_products, $step1_category );
		// Step 2: Chocolates Selection
		wgbb_render_step_configuration( 2, __( 'Chocolates', 'woocommerce-gift-box-builder' ), $step2_type, $step2_products, $step2_category );
		// Step 3: Additions Selection
		wgbb_render_step_configuration( 3, __( 'Additions', 'woocommerce-gift-box-builder' ), $step3_type, $step3_products, $step3_category );
		?>
	</div>
	<?php
}

/**
 * Renders the HTML fields for a single step configuration within the meta box.
 *
 * @since 1.0.0
 * @param int    $step_number      The step number (1, 2, or 3).
 * @param string $step_label       The human-readable label for the step (e.g., "Box", "Chocolates").
 * @param string $current_type     The currently saved selection type ('products' or 'category').
 * @param string $current_products Comma-separated string of product IDs (if $current_type is 'products').
 * @param string $current_category The ID of the selected category (if $current_type is 'category').
 */
function wgbb_render_step_configuration( $step_number, $step_label, $current_type, $current_products, $current_category ) {
	?>
	<h4><?php printf( esc_html__( 'Step %d: %s Selection', 'woocommerce-gift-box-builder' ), (int) $step_number, esc_html( $step_label ) ); ?></h4>
	<p>
		<label for="wgbb_step<?php echo (int) $step_number; ?>_type"><?php printf( esc_html__( '%s Source:', 'woocommerce-gift-box-builder' ), esc_html( $step_label ) ); ?></label>
		<select name="wgbb_step<?php echo (int) $step_number; ?>_type" id="wgbb_step<?php echo (int) $step_number; ?>_type" class="wgbb-type-selector widefat">
			<option value="products" <?php selected( $current_type, 'products' ); ?>><?php esc_html_e( 'Specific Products', 'woocommerce-gift-box-builder' ); ?></option>
			<option value="category" <?php selected( $current_type, 'category' ); ?>><?php esc_html_e( 'Product Category', 'woocommerce-gift-box-builder' ); ?></option>
		</select>
		<span class="description"><?php esc_html_e( 'Choose whether to select specific products or a whole product category for this step.', 'woocommerce-gift-box-builder'); ?></span>
	</p>
	<p class="wgbb-products-field" style="<?php echo ( 'products' === $current_type || empty( $current_type ) ) ? '' : 'display:none;'; ?>">
		<label for="wgbb_step<?php echo (int) $step_number; ?>_products"><?php esc_html_e( 'Product IDs (comma-separated)', 'woocommerce-gift-box-builder' ); ?></label>
		<input type="text" id="wgbb_step<?php echo (int) $step_number; ?>_products" name="wgbb_step<?php echo (int) $step_number; ?>_products" value="<?php echo esc_attr( $current_products ); ?>" class="widefat" />
		<span class="description"><?php esc_html_e( 'Enter WooCommerce product IDs, separated by commas (e.g., 10,25,30).', 'woocommerce-gift-box-builder'); ?></span>
	</p>
	<p class="wgbb-category-field" style="<?php echo ( 'category' === $current_type ) ? '' : 'display:none;'; ?>">
		<label for="wgbb_step<?php echo (int) $step_number; ?>_category"><?php esc_html_e( 'Select Category', 'woocommerce-gift-box-builder' ); ?></label>
		<?php
		// Arguments for the category dropdown.
		$dropdown_args = array(
			'show_option_none' => __( 'Select a category', 'woocommerce-gift-box-builder' ),
			'taxonomy'         => 'product_cat', // WooCommerce product category taxonomy.
			'name'             => 'wgbb_step' . (int) $step_number . '_category',
			'id'               => 'wgbb_step' . (int) $step_number . '_category',
			'selected'         => $current_category,
			'hierarchical'     => true,
			'show_count'       => true,
			'hide_empty'       => false,
			'class'            => 'widefat',
		);
		wp_dropdown_categories( $dropdown_args );
		?>
		<span class="description"><?php esc_html_e( 'Choose a product category. All products from this category will be available for this step.', 'woocommerce-gift-box-builder'); ?></span>
	</p>
	<hr style="margin-top: 20px; margin-bottom: 20px;">
	<?php
}


/**
 * Enqueues admin scripts and styles for the Gift Box Bundle CPT edit screen.
 *
 * Specifically, enqueues JavaScript for toggling field visibility based on selection.
 * Hooked into 'admin_enqueue_scripts'.
 *
 * @since 1.0.0
 * @param string $hook The current admin page hook.
 */
function wgbb_admin_enqueue_scripts( $hook ) {
	global $post_type; // Get the current post type.

	// Only enqueue on the 'post.php' and 'post-new.php' screens for our CPT.
	if ( ( 'post.php' === $hook || 'post-new.php' === $hook ) && 'gift_box_bundle' === $post_type ) {
		// Use plugin constants for URL and version.
		wp_enqueue_script(
			'wgbb-admin-bundle-cpt-js', // Handle for the script.
			WGBB_PLUGIN_URL . 'assets/js/admin-bundle-cpt.js', // Path to the script.
			array( 'jquery' ), // Dependencies.
			WGBB_VERSION,      // Version number.
			true               // Load in the footer.
		);
	}
}
add_action( 'admin_enqueue_scripts', 'wgbb_admin_enqueue_scripts' );

/**
 * Saves the meta box data for the Gift Box Bundle CPT.
 *
 * Handles security checks (nonce, user permissions) and sanitizes data before saving.
 * Hooked into 'save_post'.
 *
 * @since 1.0.0
 * @param int $post_id The ID of the post being saved.
 */
function wgbb_save_bundle_configuration_data( $post_id ) {
	// 1. Check if our nonce is set.
	if ( ! isset( $_POST['wgbb_bundle_configuration_nonce'] ) ) {
		return;
	}

	// 2. Verify that the nonce is valid.
	if ( ! wp_verify_nonce( $_POST['wgbb_bundle_configuration_nonce'], 'wgbb_save_bundle_configuration_data' ) ) {
		return;
	}

	// 3. If this is an autosave, our form has not been submitted, so we don't want to do anything.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// 4. Check the user's permissions.
	if ( isset( $_POST['post_type'] ) && 'gift_box_bundle' === $_POST['post_type'] ) {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
	} else {
		// Not our CPT, so bail.
		return;
	}

	// Iterate through each of the 3 steps to save their configuration.
	for ( $i = 1; $i <= 3; $i++ ) {
		// Define meta keys for the current step.
		$step_type_key     = "_wgbb_step{$i}_type";
		$step_products_key = "_wgbb_step{$i}_products";
		$step_category_key = "_wgbb_step{$i}_category";

		// Check if the type for the current step is set in POST data.
		if ( isset( $_POST[ "wgbb_step{$i}_type" ] ) ) {
			// Sanitize the selection type ('products' or 'category').
			$type = sanitize_text_field( $_POST[ "wgbb_step{$i}_type" ] );
			update_post_meta( $post_id, $step_type_key, $type );

			// Based on the selected type, save either product IDs or category ID.
			if ( 'products' === $type ) {
				if ( isset( $_POST[ "wgbb_step{$i}_products" ] ) ) {
					// Sanitize comma-separated product IDs: ensure they are integers.
					$product_ids_raw       = explode( ',', sanitize_text_field( $_POST[ "wgbb_step{$i}_products" ] ) );
					$product_ids_sanitized = array_map( 'absint', $product_ids_raw ); // Convert all to absolute integers.
					$product_ids_filtered  = array_filter( $product_ids_sanitized ); // Remove zeros or invalid entries.
					update_post_meta( $post_id, $step_products_key, implode( ',', $product_ids_filtered ) );
				}
				// Clear the category meta if 'products' type is selected.
				update_post_meta( $post_id, $step_category_key, '' );
			} elseif ( 'category' === $type ) {
				if ( isset( $_POST[ "wgbb_step{$i}_category" ] ) ) {
					// Sanitize the category ID (ensure it's an integer).
					$category_id = absint( $_POST[ "wgbb_step{$i}_category" ] );
					update_post_meta( $post_id, $step_category_key, $category_id );
				}
				// Clear the products meta if 'category' type is selected.
				update_post_meta( $post_id, $step_products_key, '' );
			}
		} else {
			// If type is not set for this step (e.g., form manipulation), clear all related meta for this step.
			update_post_meta( $post_id, $step_type_key, '' );
			update_post_meta( $post_id, $step_products_key, '' );
			update_post_meta( $post_id, $step_category_key, '' );
		}
	}
}
add_action( 'save_post', 'wgbb_save_bundle_configuration_data' ); // Hook into the save_post action.
?>
