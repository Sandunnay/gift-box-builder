<?php
/**
 * Shortcode for displaying the WooCommerce Gift Box Builder.
 *
 * This file contains the main logic for the `[gift_box_builder]` shortcode,
 * including attribute processing, asset enqueuing, data fetching for the initial step,
 * and rendering the HTML structure for the multi-step builder interface.
 *
 * @package WooCommerceGiftBoxBuilder/Shortcodes
 * @version 1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Renders the Gift Box Builder shortcode.
 *
 * This function is responsible for:
 * - Processing shortcode attributes (primarily `bundle_id`).
 * - Enqueuing necessary frontend scripts and styles.
 * - Localizing data for JavaScript (AJAX URLs, nonces, translated strings).
 * - Fetching product data for Step 1 (Box Selection) based on the bundle configuration.
 * - Outputting the HTML structure for the multi-step gift box builder interface.
 *
 * @since 1.0.0
 * @version 1.0.1 - Added more l10n strings, ensuring correct filemtime for assets.
 *
 * @global object $post WordPress post object (used in wgbb_footer_actions_if_shortcode_present).
 *
 * @param array $atts Shortcode attributes. Expects 'bundle_id'.
 * @return string HTML output for the gift box builder.
 */
function wgbb_render_gift_box_builder_shortcode( $atts ) {
	// Sanitize and parse shortcode attributes.
	// 'bundle_id' specifies which "Gift Box Bundle" CPT post to use for configuration.
	$atts = shortcode_atts(
		array(
			'bundle_id' => 0, // Default to 0, indicating no specific ID passed.
		),
		$atts,
		'gift_box_builder' // Shortcode tag.
	);

	$bundle_id = absint( $atts['bundle_id'] );

	// If no bundle_id is provided via attribute, try to get the most recently published one.
	// This allows for a default behavior if the user forgets or omits the ID.
	if ( ! $bundle_id ) {
		$latest_bundle_query_args = array(
			'post_type'      => 'gift_box_bundle', // Our CPT.
			'post_status'    => 'publish',         // Only published bundles.
			'posts_per_page' => 1,                 // Get only the latest one.
			'orderby'        => 'date',            // Order by publication date.
			'order'          => 'DESC',            // Most recent first.
		);
		$latest_bundle_query = new WP_Query( $latest_bundle_query_args );
		if ( $latest_bundle_query->have_posts() ) {
			$bundle_id = $latest_bundle_query->posts[0]->ID; // Get the ID of the first post found.
		}
		wp_reset_postdata(); // Restore original post data after custom WP_Query.
	}

	// If still no bundle_id (none provided, none found), display an error message.
	// This uses Bootstrap alert classes for styling if available.
	if ( ! $bundle_id ) {
		// Using esc_html_e for direct output is not suitable here as we need to return the string.
		return '<p class="alert alert-danger">' . esc_html__( 'No Gift Box Bundle ID provided or no bundles found.', 'woocommerce-gift-box-builder' ) . '</p>';
	}

	// Validate that the provided ID actually belongs to a 'gift_box_bundle' post type.
	// This prevents issues if a random post ID is passed.
	if ( 'gift_box_bundle' !== get_post_type( $bundle_id ) ) {
		return '<p class="alert alert-danger">' . esc_html__( 'Invalid Bundle ID provided. Please check the shortcode.', 'woocommerce-gift-box-builder' ) . '</p>';
	}

	// Enqueue and localize the main frontend JavaScript file.
	// Constants WGBB_PLUGIN_DIR, WGBB_PLUGIN_URL are used (defined in main plugin file).
	$script_asset_path = WGBB_PLUGIN_DIR . 'assets/js/frontend-gift-box.js';
	if ( file_exists( $script_asset_path ) ) {
		wp_enqueue_script(
			'wgbb-frontend-gift-box', // Script handle.
			WGBB_PLUGIN_URL . 'assets/js/frontend-gift-box.js', // Script URL.
			array( 'jquery' ), // Dependencies (jQuery).
			filemtime( $script_asset_path ), // Version based on file modification time for cache busting.
			true // Load in footer to ensure HTML is present.
		);

		// Create nonces for AJAX actions to enhance security.
		$step_load_nonce           = wp_create_nonce( 'wgbb_step_load_nonce' );
		$get_product_details_nonce = wp_create_nonce( 'wgbb_get_product_details_nonce' );
		$add_to_cart_nonce         = wp_create_nonce( 'wgbb_add_to_cart_nonce' );

		// Prepare data to be passed to the JavaScript file via wp_localize_script.
		// This includes AJAX URLs, nonces, the current bundle ID, and translatable strings.
		$localized_data = array(
			'ajax_url'                  => admin_url( 'admin-ajax.php' ), // WordPress AJAX URL.
			'nonce'                     => $step_load_nonce, // For loading step content (Steps 2 & 3).
			'nonce_get_product_details' => $get_product_details_nonce, // For fetching product details for the modal.
			'nonce_add_to_cart'         => $add_to_cart_nonce, // For adding the gift box to the cart.
			'bundle_id'                 => $bundle_id, // The ID of the bundle being built.
			'placeholder_image_url'     => wc_placeholder_img_src(), // URL for WooCommerce's placeholder image.
			'cart_url'                  => wc_get_cart_url(), // URL to the cart page.
			'l10n'                      => array( // Translatable strings for JS.
				'next_button_text'                   => __( 'Next', 'woocommerce-gift-box-builder' ),
				'review_button_text'                 => __( 'Review Your Box', 'woocommerce-gift-box-builder' ),
				'add_to_cart_button_text'            => __( 'Add to Cart', 'woocommerce-gift-box-builder' ),
				'select_box_alert'                   => __( 'Please select a box to continue.', 'woocommerce-gift-box-builder' ),
				'loading_text'                       => __( 'Loading...', 'woocommerce-gift-box-builder' ),
				'error_loading_step'                 => __( 'Error loading content. Please try again.', 'woocommerce-gift-box-builder' ),
				'step2_title'                        => __( 'Step 2: Choose Your Chocolates', 'woocommerce-gift-box-builder' ),
				'step3_title'                        => __( 'Step 3: Select Additions', 'woocommerce-gift-box-builder' ),
				'error_text'                         => __( 'Error', 'woocommerce-gift-box-builder' ),
				'error_loading_details'              => __( 'Could not load product details. Please try again.', 'woocommerce-gift-box-builder' ),
				'choose_option'                      => __( 'Choose an option', 'woocommerce-gift-box-builder' ), // For variation dropdowns.
				'out_of_stock'                       => __( 'Out of stock', 'woocommerce-gift-box-builder' ),
				'max_stock_reached'                  => __( 'Maximum stock quantity (%s) reached.', 'woocommerce-gift-box-builder' ), // %s is placeholder for quantity.
				'select_variations_alert'            => __( 'Please select all product options before adding to box.', 'woocommerce-gift-box-builder' ),
				'quantity_greater_than_zero_alert'   => __( 'Quantity must be greater than zero.', 'woocommerce-gift-box-builder' ),
				'summary_title'                      => __( 'Your Gift Box Summary', 'woocommerce-gift-box-builder' ),
				'box_label'                          => __( 'Box', 'woocommerce-gift-box-builder' ),
				'remove_text'                        => __( 'Remove', 'woocommerce-gift-box-builder' ),
				'empty_summary_text'                 => __( 'Your gift box is currently empty. Start by selecting a box!', 'woocommerce-gift-box-builder' ),
				'add_to_cart_success'                => __( 'Gift box successfully added to your cart!', 'woocommerce-gift-box-builder' ),
				'add_to_cart_failure'                => __( 'Could not add gift box to cart. Some items may be out of stock or an error occurred.', 'woocommerce-gift-box-builder' ),
				'adding_to_cart'                     => __( 'Adding to cart...', 'woocommerce-gift-box-builder' ),
				'item_out_of_stock'                  => __( '%s is out of stock or has insufficient quantity.', 'woocommerce-gift-box-builder' ), // %s is placeholder for item name.
			),
		);
		wp_localize_script( 'wgbb-frontend-gift-box', 'wgbb_data', $localized_data );
	}

	// Start output buffering to capture all HTML generated by this function.
	ob_start();

	// Enqueue the main frontend stylesheet.
	// Constants WGBB_PLUGIN_DIR, WGBB_PLUGIN_URL, and WGBB_VERSION (from main file, though version not used here directly for CSS) are used.
	$style_asset_path = WGBB_PLUGIN_DIR . 'assets/css/frontend-gift-box.css';
	if ( file_exists( $style_asset_path ) ) {
		wp_enqueue_style(
			'wgbb-frontend-gift-box-css', // Style handle.
			WGBB_PLUGIN_URL . 'assets/css/frontend-gift-box.css', // Style URL.
			array(), // Dependencies (e.g., if it depends on a Bootstrap CSS handle from the theme).
			filemtime( $style_asset_path ) // Versioning using file modification time for cache busting.
		);
	}

	// Fetch configuration for Step 1 (Box Selection) from post meta.
	$step1_type             = get_post_meta( $bundle_id, '_wgbb_step1_type', true );
	$step1_products_ids_str = get_post_meta( $bundle_id, '_wgbb_step1_products', true );
	$step1_category_id      = get_post_meta( $bundle_id, '_wgbb_step1_category', true );

	// If Step 1 configuration (type) is missing, display an error and stop further rendering.
	if ( empty( $step1_type ) ) {
		// Use class 'alert alert-warning' for Bootstrap styling if available.
		echo '<p class="alert alert-warning">' . esc_html__( 'This Gift Box Bundle is not configured correctly (Step 1 type missing).', 'woocommerce-gift-box-builder' ) . '</p>';
		return ob_get_clean(); // Return buffered content (which is just the error message).
	}

	// Prepare an array to hold product data for Step 1.
	$products_for_step1 = array();

	// Fetch products based on whether 'Specific Products' or 'Product Category' is selected for Step 1.
	if ( 'products' === $step1_type ) {
		if ( ! empty( $step1_products_ids_str ) ) {
			// Convert comma-separated product IDs to an array of integers.
			$product_ids = array_map( 'absint', explode( ',', $step1_products_ids_str ) );
			$product_ids = array_filter( $product_ids ); // Remove any zeros or invalid IDs.

			if ( ! empty( $product_ids ) ) {
				// Query for the specified products.
				$args = array(
					'post_type'      => 'product',
					'post_status'    => 'publish',
					'posts_per_page' => -1, // Fetch all specified products.
					'post__in'       => $product_ids,
					'orderby'        => 'post__in', // Maintain the order from meta if possible.
				);
				$query = new WP_Query( $args );
				if ( $query->have_posts() ) {
					while ( $query->have_posts() ) {
						$query->the_post();
						$product = wc_get_product( get_the_ID() ); // Get WC_Product object.
						if ( $product ) {
							// Store relevant product data for display.
							$products_for_step1[] = array(
								'id'         => $product->get_id(),
								'name'       => $product->get_name(),
								'image_html' => $product->get_image( 'woocommerce_thumbnail' ), // Use a standard WC image size.
								'permalink'  => $product->get_permalink(),
							);
						}
					}
				}
				wp_reset_postdata(); // Restore original post data after custom query.
			}
		}
	} elseif ( 'category' === $step1_type ) {
		if ( ! empty( $step1_category_id ) ) {
			// Query for products in the specified category.
			$args = array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => -1, // Fetch all products in category.
				'tax_query'      => array(
					array(
						'taxonomy' => 'product_cat', // WooCommerce product category taxonomy.
						'field'    => 'term_id',
						'terms'    => $step1_category_id,
					),
				),
			);
			$query = new WP_Query( $args );
			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) {
					$query->the_post();
					 $product = wc_get_product( get_the_ID() );
					if ( $product ) {
						$products_for_step1[] = array(
							'id'         => $product->get_id(),
							'name'       => $product->get_name(),
							'image_html' => $product->get_image( 'woocommerce_thumbnail' ), // Use a standard WC image size.
							'permalink'  => $product->get_permalink(),
						);
					}
				}
			}
			wp_reset_postdata();
		}
	}

	// Begin HTML output for the gift box builder.
	// Utilizes Bootstrap classes for styling and layout.
	?>
	<div id="wgbb-gift-box-builder" class="wgbb-container container mt-4" data-bundle-id="<?php echo esc_attr( $bundle_id ); ?>">
		<!-- Step 1: Box Selection -->
		<div id="wgbb-step-1" class="wgbb-step card shadow-sm mb-4">
			<div class="card-body">
				<h2 class="card-title text-center mb-4"><?php esc_html_e( 'Step 1: Select Your Box', 'woocommerce-gift-box-builder' ); ?></h2>
				<?php if ( ! empty( $products_for_step1 ) ) : ?>
					<div class="wgbb-product-grid row">
						<?php foreach ( $products_for_step1 as $product_item ) : ?>
							<div class="col-lg-3 col-md-4 col-sm-6 mb-4"> <?php // Bootstrap responsive grid column. ?>
								<div class="wgbb-product-card card h-100"> <?php // Bootstrap card for product display. ?>
									<div class="wgbb-product-image card-img-top d-flex align-items-center justify-content-center p-3">
										<?php echo $product_item['image_html']; // WPCS: XSS ok. WooCommerce sanitizes this. ?>
									</div>
									<div class="card-body d-flex flex-column">
										<h3 class="wgbb-product-name card-title h5"><?php echo esc_html( $product_item['name'] ); ?></h3>
										<button class="btn btn-primary wgbb-select-product-button mt-auto" data-product-id="<?php echo esc_attr( $product_item['id'] ); ?>">
											<?php esc_html_e( 'Select This Box', 'woocommerce-gift-box-builder' ); ?>
										</button>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<p class="alert alert-info"><?php esc_html_e( 'No boxes available for selection in this step. Please check the bundle configuration.', 'woocommerce-gift-box-builder' ); ?></p>
				<?php endif; ?>
			</div>
		</div>

		<!-- Step 2: Chocolates Selection (Content loaded via AJAX) -->
		<div id="wgbb-step-2" class="wgbb-step card shadow-sm mb-4" style="display:none;">
			 <div class="card-body">
				<?php /* Content for step 2 will be loaded here via JS, including its own H2 title */ ?>
			</div>
		</div>

		<!-- Step 3: Additions Selection (Content loaded via AJAX) -->
		<div id="wgbb-step-3" class="wgbb-step card shadow-sm mb-4" style="display:none;">
			<div class="card-body">
				<?php /* Content for step 3 will be loaded here via JS, including its own H2 title */ ?>
			</div>
		</div>

		<!-- Step 4: Review & Finalize -->
		<div id="wgbb-step-4" class="wgbb-step card shadow-sm mb-4" style="display:none;">
			<div class="card-body text-center">
				<h2 class="card-title mb-3"><?php esc_html_e( 'Step 4: Review Your Gift Box', 'woocommerce-gift-box-builder' ); ?></h2>
				<p class="lead"><?php esc_html_e( 'Please review your selections in the summary below before adding to cart.', 'woocommerce-gift-box-builder' ); ?></p>
				
				<div id="wgbb-step-4-actions" class="mt-4">
					<button id="wgbb-add-to-cart-button" class="btn btn-success btn-lg mr-2 mb-2"><?php esc_html_e( 'Add to Cart', 'woocommerce-gift-box-builder' ); ?></button>
					<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" id="wgbb-view-cart-button" class="btn btn-info mr-2 mb-2" style="display:none;"><?php esc_html_e( 'View Cart', 'woocommerce-gift-box-builder' ); ?></a>
					<button id="wgbb-build-another-box-button" class="btn btn-outline-primary mb-2" style="display:none;"><?php esc_html_e( 'Build Another Box', 'woocommerce-gift-box-builder' ); ?></button>
				</div>
				<div id="wgbb-add-to-cart-messages" class="mt-3"></div> <?php // For success/error messages from AJAX add to cart. ?>
			</div>
		</div>

		<!-- Navigation Buttons -->
		<div class="wgbb-navigation d-flex justify-content-between my-4">
			<button id="wgbb-prev-button" class="btn btn-secondary" style="display:none;"><?php esc_html_e( 'Previous', 'woocommerce-gift-box-builder' ); ?></button>
			<button id="wgbb-next-button" class="btn btn-primary"><?php esc_html_e( 'Next', 'woocommerce-gift-box-builder' ); ?></button>
		</div>
	</div>

	<!-- Fixed Summary Display Area (populated by JavaScript) -->
	<div id="wgbb-summary-container" class="fixed-bottom bg-light p-3 border-top shadow-lg">
		<div class="container"> <?php // Bootstrap container for alignment with page content. ?>
			<div id="wgbb-summary">
				 <?php /* This div will be populated by the renderSummary() function in frontend-gift-box.js */ ?>
			</div>
		</div>
	</div>


	<!-- Bootstrap Modal for Product Details (populated by JavaScript) -->
	<div class="modal fade" id="wgbbProductModal" tabindex="-1" role="dialog" aria-labelledby="wgbbProductModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg modal-dialog-centered" role="document"> <?php // Added modal-dialog-centered for vertical centering. ?>
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title wgbb-popup-product-name" id="wgbbProductModalLabel"><?php esc_html_e( 'Product Details', 'woocommerce-gift-box-builder' ); ?></h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'woocommerce-gift-box-builder' ); ?>">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<!-- Loading spinner shown while product details are fetched -->
					<div class="wgbb-modal-loading text-center py-5" style="display:none;">
						<div class="spinner-border text-primary" role="status">
							<span class="sr-only"><?php esc_html_e( 'Loading...', 'woocommerce-gift-box-builder' ); ?></span>
						</div>
					</div>
					<!-- Content loaded via AJAX -->
					<div class="wgbb-modal-content-loaded">
						<div class="row">
							<div class="col-md-5 text-center">
								<img src="" class="img-fluid rounded wgbb-popup-product-image mb-3 mb-md-0" alt="<?php esc_attr_e( 'Product Image', 'woocommerce-gift-box-builder' ); ?>">
							</div>
							<div class="col-md-7">
								<div class="wgbb-popup-product-short-desc mb-3"></div>
								<p class="wgbb-popup-product-price h4 mb-3"></p>
								<div class="wgbb-popup-product-variations mb-3">
									<?php // Variation select dropdowns will be populated here by JavaScript. ?>
								</div>
								<div class="form-group wgbb-popup-quantity-section mb-3">
									<label for="wgbb-popup-qty-input" class="font-weight-bold"><?php esc_html_e( 'Quantity:', 'woocommerce-gift-box-builder' ); ?></label>
									<div class="input-group" style="max-width: 150px;">
										<div class="input-group-prepend">
											<button class="btn btn-outline-secondary wgbb-popup-qty-minus" type="button">-</button>
										</div>
										<input type="number" id="wgbb-popup-qty-input" class="form-control text-center wgbb-popup-qty" value="1" min="1" title="<?php esc_attr_e( 'Qty', 'woocommerce-gift-box-builder' ); ?>">
										<div class="input-group-append">
											<button class="btn btn-outline-secondary wgbb-popup-qty-plus" type="button">+</button>
										</div>
									</div>
									<div class="wgbb-popup-stock-warning text-danger mt-1" style="display: none;"></div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal"><?php esc_html_e( 'Close', 'woocommerce-gift-box-builder' ); ?></button>
					<button type="button" class="btn btn-primary wgbb-popup-add-to-box-button"><?php esc_html_e( 'Add to Box', 'woocommerce-gift-box-builder' ); ?></button>
				</div>
			</div>
		</div>
	</div>
	<?php

	return ob_get_clean(); // Return the buffered HTML content.
}
add_shortcode( 'gift_box_builder', 'wgbb_render_gift_box_builder_shortcode' );


// Remove the old inline style function and its hook
// function wgbb_enqueue_frontend_assets() { ... }
// remove_action( 'wp_footer', 'wgbb_conditionally_enqueue_frontend_assets', 5 );

/**
 * Placeholder for potential footer actions if the shortcode is present on the page.
 *
 * Currently not used for enqueuing assets as that's handled directly in the shortcode function
 * for better context and conditional loading. This hook remains for future possibilities
 * like outputting JSON+LD structured data or other footer-specific elements related to the builder.
 *
 * @since 1.0.0
 * @global object $post WordPress post object.
 */
add_action( 'wp_footer', 'wgbb_footer_actions_if_shortcode_present', 20 );
function wgbb_footer_actions_if_shortcode_present() {
	global $post;
	// Check if the current page/post object exists and contains the shortcode.
	if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'gift_box_builder' ) ) {
		// Future conditional logic for footer if needed.
		// For example, outputting JSON+LD data, etc.
	}
}

?>
