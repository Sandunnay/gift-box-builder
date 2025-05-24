<?php
/**
 * AJAX functions for the WooCommerce Gift Box Builder.
 *
 * This file contains handlers for all AJAX requests made by the frontend
 * gift box builder, such as loading step content, fetching product details,
 * and adding the completed gift box to the cart.
 *
 * @package WooCommerceGiftBoxBuilder/AJAX
 * @version 1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Register AJAX actions for loading step content, available to both logged-in and non-logged-in users.
add_action( 'wp_ajax_wgbb_load_step_content', 'wgbb_ajax_load_step_content' );
add_action( 'wp_ajax_nopriv_wgbb_load_step_content', 'wgbb_ajax_load_step_content' );

/**
 * AJAX handler for loading product content for Steps 2 (Chocolates) and 3 (Additions).
 *
 * Validates request parameters (bundle ID, target step), fetches the bundle's
 * configuration for the specified step, queries WooCommerce products based on this
 * configuration (either specific product IDs or products from a category),
 * and then returns the HTML for the product grid to be displayed on the frontend.
 *
 * Input (POST data):
 * - nonce: Security nonce ('wgbb_step_load_nonce').
 * - bundle_id: ID of the current gift box bundle being configured.
 * - target_step: The step number (2 or 3) for which to load content.
 *
 * Output: JSON success object with an 'html' key containing the product grid,
 *         or a JSON error object with a 'message' key.
 *
 * @since 1.0.0
 */
function wgbb_ajax_load_step_content() {
	// 1. Verify AJAX nonce for security to prevent CSRF attacks.
	check_ajax_referer( 'wgbb_step_load_nonce', 'nonce' );

	// 2. Sanitize and validate input parameters from the POST request.
	$bundle_id   = isset( $_POST['bundle_id'] ) ? absint( $_POST['bundle_id'] ) : 0;
	$target_step = isset( $_POST['target_step'] ) ? absint( $_POST['target_step'] ) : 0;

	// Ensure bundle ID is valid and target step is either 2 or 3, as these are the AJAX-loaded steps.
	if ( ! $bundle_id || ! in_array( $target_step, array( 2, 3 ), true ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid request parameters.', 'woocommerce-gift-box-builder' ) ) );
	}

	// Ensure the bundle_id corresponds to an actual 'gift_box_bundle' post type for data integrity.
	if ( 'gift_box_bundle' !== get_post_type( $bundle_id ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid Bundle ID.', 'woocommerce-gift-box-builder' ) ) );
	}

	// 3. Fetch bundle configuration (meta_keys) for the target step.
	$step_type_meta_key     = "_wgbb_step{$target_step}_type";     // e.g., _wgbb_step2_type
	$step_products_meta_key = "_wgbb_step{$target_step}_products"; // e.g., _wgbb_step2_products
	$step_category_meta_key = "_wgbb_step{$target_step}_category"; // e.g., _wgbb_step2_category

	$step_type             = get_post_meta( $bundle_id, $step_type_meta_key, true );
	$step_products_ids_str = get_post_meta( $bundle_id, $step_products_meta_key, true );
	$step_category_id      = get_post_meta( $bundle_id, $step_category_meta_key, true );

	// If the step type (products/category) is not configured, the bundle is incomplete. Send an error.
	if ( empty( $step_type ) ) {
		wp_send_json_error( array( 'message' => sprintf( __( 'This Gift Box Bundle is not configured correctly (Step %d type missing).', 'woocommerce-gift-box-builder' ), $target_step ) ) );
	}

	// 4. Query products based on the step configuration.
	$products_for_step = array(); // Initialize array to hold product data.

	if ( 'products' === $step_type ) {
		// Fetch specific products by their IDs.
		if ( ! empty( $step_products_ids_str ) ) {
			$product_ids = array_map( 'absint', explode( ',', $step_products_ids_str ) ); // Ensure all IDs are integers.
			$product_ids = array_filter( $product_ids ); // Remove empty or zero values.

			if ( ! empty( $product_ids ) ) {
				$args = array(
					'post_type'      => 'product',         // Query WooCommerce products.
					'post_status'    => 'publish',       // Only published products.
					'posts_per_page' => -1,              // Get all specified products.
					'post__in'       => $product_ids,    // Array of product IDs to fetch.
					'orderby'        => 'post__in',      // Maintain the order of IDs if possible.
				);
				$query = new WP_Query( $args );
				if ( $query->have_posts() ) {
					while ( $query->have_posts() ) {
						$query->the_post();
						$product = wc_get_product( get_the_ID() ); // Get the WC_Product object.
						if ( $product ) {
							// Store essential product data for the frontend display.
							$products_for_step[] = array(
								'id'         => $product->get_id(),
								'name'       => $product->get_name(),
								'image_html' => $product->get_image( 'woocommerce_thumbnail' ), // Standard WooCommerce thumbnail size.
								'permalink'  => $product->get_permalink(),
							);
						}
					}
				}
				wp_reset_postdata(); // Restore global $post variable.
			}
		}
	} elseif ( 'category' === $step_type ) {
		// Fetch all products from a specific category.
		if ( ! empty( $step_category_id ) ) {
			$args = array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => -1, // Get all products in the category.
				'tax_query'      => array(
					array(
						'taxonomy' => 'product_cat', // WooCommerce product category taxonomy.
						'field'    => 'term_id',     // Select by term ID.
						'terms'    => $step_category_id, // The category ID.
					),
				),
			);
			$query = new WP_Query( $args );
			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) {
					$query->the_post();
					$product = wc_get_product( get_the_ID() );
					if ( $product ) {
						$products_for_step[] = array(
							'id'         => $product->get_id(),
							'name'       => $product->get_name(),
							'image_html' => $product->get_image( 'woocommerce_thumbnail' ),
							'permalink'  => $product->get_permalink(),
						);
					}
				}
			}
			wp_reset_postdata();
		}
	}

	// 5. Generate HTML for the product grid to be displayed on the frontend.
	ob_start(); // Start output buffering to capture HTML.
	if ( ! empty( $products_for_step ) ) : ?>
		<div class="wgbb-product-grid row"> <?php // Using Bootstrap's row class for grid system compatibility. ?>
			<?php foreach ( $products_for_step as $product_item ) : ?>
				<div class="col-lg-3 col-md-4 col-sm-6 mb-4"> <?php // Bootstrap responsive grid columns. ?>
					<div class="wgbb-product-card card h-100"> <?php // Bootstrap card styling. ?>
						<div class="wgbb-product-image card-img-top d-flex align-items-center justify-content-center p-3">
							<?php echo $product_item['image_html']; // WPCS: XSS ok. WooCommerce sanitizes product images. ?>
						</div>
						<div class="card-body d-flex flex-column">
							<h3 class="wgbb-product-name card-title h5"><?php echo esc_html( $product_item['name'] ); ?></h3>
							<?php // Button to trigger the product details modal. ?>
							<button class="btn btn-outline-primary btn-sm wgbb-add-to-box-button mt-auto" data-product-id="<?php echo esc_attr( $product_item['id'] ); ?>">
								<?php esc_html_e( 'Configure Item', 'woocommerce-gift-box-builder' ); // Text changed for clarity, as this button opens the modal. ?>
							</button>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
		<p class="alert alert-info">
			<?php
			// Display a user-friendly message if no products are found for the current step.
			if ( 2 === $target_step ) {
				esc_html_e( 'No chocolates available for selection in this step. Please check the bundle configuration.', 'woocommerce-gift-box-builder' );
			} elseif ( 3 === $target_step ) {
				esc_html_e( 'No additions available for selection in this step. Please check the bundle configuration.', 'woocommerce-gift-box-builder' );
			}
			?>
		</p>
	<?php endif;
	$html_output = ob_get_clean(); // Get the buffered HTML content.

	// 6. Send JSON response back to the frontend script.
	wp_send_json_success( array( 'html' => $html_output ) );
}

// Register AJAX actions for fetching product details for the modal.
add_action( 'wp_ajax_wgbb_get_product_details_for_popup', 'wgbb_ajax_get_product_details_for_popup' );
add_action( 'wp_ajax_nopriv_wgbb_get_product_details_for_popup', 'wgbb_ajax_get_product_details_for_popup' );

/**
 * AJAX handler for fetching detailed product information for the popup modal.
 *
 * This function is triggered when a user clicks the "Configure Item" button on a product
 * in Steps 2 or 3. It retrieves comprehensive details for the specified product,
 * including name, image, description, price, stock status, and any variations.
 *
 * Input (POST data):
 * - nonce: Security nonce ('wgbb_get_product_details_nonce').
 * - product_id: ID of the product for which to fetch details.
 *
 * Output: JSON success object with detailed product data,
 *         or a JSON error object with a 'message' key.
 *
 * @since 1.0.0
 */
function wgbb_ajax_get_product_details_for_popup() {
	// 1. Verify AJAX nonce for security.
	check_ajax_referer( 'wgbb_get_product_details_nonce', 'nonce' );

	// 2. Sanitize and validate the incoming product ID.
	$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;

	if ( ! $product_id ) {
		wp_send_json_error( array( 'message' => __( 'Invalid product ID.', 'woocommerce-gift-box-builder' ) ) );
	}

	// 3. Get the WC_Product object.
	$product = wc_get_product( $product_id );

	// If the product doesn't exist or is invalid, send an error.
	if ( ! $product ) {
		wp_send_json_error( array( 'message' => __( 'Product not found.', 'woocommerce-gift-box-builder' ) ) );
	}

	// 4. Prepare an array of product data to be sent in the JSON response.
	$image_size = 'woocommerce_single'; // Use a standard WooCommerce image size for consistency.
	$image_id   = $product->get_image_id();
	$image_url  = $image_id ? wp_get_attachment_image_url( $image_id, $image_size ) : wc_placeholder_img_src(); // Fallback to placeholder if no image.

	$data = array(
		'id'                => $product->get_id(),
		'name'              => $product->get_name(),
		'image_url'         => $image_url,
		'short_description' => wc_format_content( $product->get_short_description() ), // Apply WordPress content formatting.
		'price_html'        => $product->get_price_html(), // Get formatted price including currency symbol.
		'managing_stock'    => $product->managing_stock(),
		'stock_quantity'    => $product->get_stock_quantity(),
		'backorders_allowed'=> $product->backorders_allowed(),
		'is_in_stock'       => $product->is_in_stock(),
		'variations'        => array(), // Initialize for storing detailed variation data.
		'variation_attributes' => array(), // Initialize for storing attribute definitions (e.g., Color: Red, Blue).
	);

	// If the product is a variable product, fetch its variations and attributes.
	if ( $product->is_type( 'variable' ) ) {
		$variable_product = new WC_Product_Variable( $product->get_id() );
		// Get attribute definitions (e.g., 'Color' => array('Red', 'Blue')).
		$data['variation_attributes'] = $variable_product->get_variation_attributes(); 

		// Get detailed data for each available variation.
		$available_variations = $variable_product->get_available_variations();
		foreach ( $available_variations as $variation_data_item ) { 
			$variation_obj = wc_get_product( $variation_data_item['variation_id'] ); // Get WC_Product_Variation object.
			if ( $variation_obj ) {
				$data['variations'][] = array(
					'variation_id'       => $variation_data_item['variation_id'],
					'attributes'         => $variation_data_item['attributes'], // e.g., array('attribute_pa_color' => 'red')
					'price_html'         => $variation_obj->get_price_html(),
					'image_url'          => wp_get_attachment_image_url( $variation_obj->get_image_id(), $image_size ) ?: $image_url, // Fallback to parent product image if variation has none.
					'stock_quantity'     => $variation_obj->get_stock_quantity(),
					'is_in_stock'        => $variation_obj->is_in_stock(),
					'managing_stock'     => $variation_obj->managing_stock(),
					'backorders_allowed' => $variation_obj->backorders_allowed(),
				);
			}
		}
	}

	// 5. Send JSON response with the collected product data.
	wp_send_json_success( $data );
}

// Register AJAX actions for adding the completed gift box to the cart.
add_action( 'wp_ajax_wgbb_add_gift_box_to_cart', 'wgbb_ajax_add_gift_box_to_cart' );
add_action( 'wp_ajax_nopriv_wgbb_add_gift_box_to_cart', 'wgbb_ajax_add_gift_box_to_cart' );

/**
 * AJAX handler for adding the complete gift box (selected box and items) to the WooCommerce cart.
 *
 * This function takes the current state of the gift box from the frontend,
 * validates the data, performs a crucial stock check for all items (box and contents)
 * to ensure an "all or nothing" addition to the cart. If all items are in stock
 * and available in the requested quantities, they are added to the WooCommerce cart.
 *
 * Input (POST data):
 * - nonce: Security nonce ('wgbb_add_to_cart_nonce').
 * - gift_box_data: JSON string representing the `giftBoxState` from JavaScript,
 *                  which includes `selectedBox` and an array of `items`.
 *
 * Output: JSON success object with a confirmation message, cart URL, and cart fragments/hash
 *         for updating the frontend (e.g., mini-cart), or a JSON error object with a message
 *         detailing any issues (e.g., out of stock items, invalid data).
 *
 * @since 1.0.0
 */
function wgbb_ajax_add_gift_box_to_cart() {
	// 1. Verify AJAX nonce for security.
	check_ajax_referer( 'wgbb_add_to_cart_nonce', 'nonce' );

	// 2. Validate and decode incoming gift box data from JSON string.
	if ( ! isset( $_POST['gift_box_data'] ) ) {
		wp_send_json_error( array( 'message' => __( 'No gift box data received.', 'woocommerce-gift-box-builder' ) ) );
	}

	$gift_box_data_json = stripslashes( $_POST['gift_box_data'] ); // Remove slashes potentially added by WordPress.
	$gift_box_state     = json_decode( $gift_box_data_json, true ); // Decode JSON into an associative array.

	// Check for JSON decoding errors or if the result is not an array.
	if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $gift_box_state ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid gift box data format.', 'woocommerce-gift-box-builder' ) ) );
	}

	// Ensure a box has been selected as it's the primary component of the gift box.
	if ( empty( $gift_box_state['selectedBox'] ) || ! isset( $gift_box_state['selectedBox']['productId'] ) ) {
		wp_send_json_error( array( 'message' => __( 'No box selected for the gift box.', 'woocommerce-gift-box-builder' ) ) );
	}
	
	// 3. Prepare a list of all items to be added to the cart, including the selected box and its contents.
	$all_items_to_add = array();

	// Add the main box product to the list.
	$all_items_to_add[] = array(
		'product_id'     => absint( $gift_box_state['selectedBox']['productId'] ),
		'quantity'       => 1, // The box itself is always quantity 1.
		'variation_id'   => 0, // Assuming the box product is not a variable product. This might need adjustment if boxes can be variable.
		'variation_data' => array(), // Variation attributes, if any.
		'name'           => isset( $gift_box_state['selectedBox']['name'] ) ? $gift_box_state['selectedBox']['name'] : __( 'Selected Box', 'woocommerce-gift-box-builder' ), // Fallback name.
	);

	// Add items selected by the user to go inside the box.
	if ( isset( $gift_box_state['items'] ) && is_array( $gift_box_state['items'] ) ) {
		foreach ( $gift_box_state['items'] as $item ) {
			// Basic validation for each item's structure.
			if ( ! isset( $item['productId'], $item['quantity'] ) ) {
				wp_send_json_error( array( 'message' => __( 'Invalid item data in gift box.', 'woocommerce-gift-box-builder' ) ) );
			}
			$all_items_to_add[] = array(
				'product_id'     => absint( $item['productId'] ),
				'quantity'       => absint( $item['quantity'] ),
				'variation_id'   => isset( $item['variationId'] ) ? absint( $item['variationId'] ) : 0,
				'variation_data' => isset( $item['variationData'] ) ? (array) $item['variationData'] : array(),
				'name'           => isset( $item['name'] ) ? $item['name'] : __( 'Item', 'woocommerce-gift-box-builder' ), // Fallback name.
			);
		}
	}
	
	// If the list of items to add is empty (shouldn't happen if a box is selected, but as a safeguard).
	if ( empty( $all_items_to_add ) ) {
		wp_send_json_error( array( 'message' => __( 'Your gift box appears to be empty.', 'woocommerce-gift-box-builder' ) ) );
	}

	$added_item_keys = array(); // Store keys of items successfully added to cart, for potential rollback.
	$errors          = array(); // Collect error messages for stock issues or other problems.

	// 4. First pass: Crucial stock check for all items. This ensures an "all or nothing" addition.
	foreach ( $all_items_to_add as $item_to_add ) {
		$product_id   = $item_to_add['product_id'];
		$quantity     = $item_to_add['quantity'];
		$variation_id = $item_to_add['variation_id'];
		$_product     = wc_get_product( $variation_id ? $variation_id : $product_id ); // Get product or variation object.

		if ( ! $_product ) {
			$errors[] = sprintf( __( 'Product with ID %d not found.', 'woocommerce-gift-box-builder' ), $product_id );
			continue; // Skip to the next item if this one doesn't exist.
		}

		// Check if product is in stock and if the requested quantity is available.
		if ( ! $_product->is_in_stock() ) {
			$errors[] = sprintf( __( '%s is out of stock.', 'woocommerce-gift-box-builder' ), $item_to_add['name'] );
		} elseif ( $_product->managing_stock() && ! $_product->has_enough_stock( $quantity ) ) { // Check stock only if product manages stock.
			$errors[] = sprintf( __( 'Not enough stock for %s (requested %d, available %d).', 'woocommerce-gift-box-builder' ), $item_to_add['name'], $quantity, $_product->get_stock_quantity() );
		}
	}

	// If any errors occurred during the stock check, send an error response and stop processing.
	if ( ! empty( $errors ) ) {
		wp_send_json_error( array( 'message' => implode( ' ', $errors ) ) );
		return;
	}

	// 5. Second pass: If all stock checks passed, proceed to add items to the WooCommerce cart.
	foreach ( $all_items_to_add as $item_to_add ) {
		$product_id     = $item_to_add['product_id'];
		$quantity       = $item_to_add['quantity'];
		$variation_id   = $item_to_add['variation_id'];
		$variation_data = $item_to_add['variation_data'];
		
		// Filter variation_data to pass only actual attribute key-value pairs for variations.
		$attributes_for_cart = array();
		if ( $variation_id ) {
			foreach ( $variation_data as $key => $value ) {
				// Ensure it's an attribute (e.g., 'attribute_pa_color') and has a value.
				if ( strpos( $key, 'attribute_' ) === 0 && ! empty( $value ) ) {
					$attributes_for_cart[ sanitize_title( $key ) ] = $value;
				}
			}
		}

		// Add the item to the WooCommerce cart.
		// Optionally, custom cart item data can be passed as the 4th argument to add_to_cart
		// to further identify these items as part of a gift box (e.g., for display or pricing adjustments later).
		// $cart_item_data = array('wgbb_gift_box_item' => true, 'wgbb_parent_box_id' => $gift_box_state['selectedBox']['productId']);
		$cart_item_key = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $attributes_for_cart /*, $cart_item_data */ );
		
		if ( ! $cart_item_key ) {
			// This case should ideally be caught by the prior stock checks, but serves as a fallback.
			$errors[] = sprintf( __( 'Could not add %s to cart. Please try again.', 'woocommerce-gift-box-builder' ), $item_to_add['name'] );
		} else {
			$added_item_keys[] = $cart_item_key; // Store the key of the successfully added item.
		}
	}

	// 6. Handle the final result of the add-to-cart operation.
	if ( ! empty( $errors ) ) {
		// If any errors occurred during the add-to-cart process (e.g., a last-minute stock change),
		// attempt to remove items already added in this batch to maintain the "all or nothing" principle.
		foreach ( $added_item_keys as $key_to_remove ) {
			WC()->cart->remove_cart_item( $key_to_remove );
		}
		wp_send_json_error( array( 'message' => implode( ' ', $errors ) ) );
	} else {
		// All items were added successfully.
		WC()->cart->calculate_totals(); // Ensure cart totals are up-to-date.
		
		// Get cart fragments for dynamic updates on the frontend (e.g., mini-cart).
		$fragments = array();
		// wc_get_refreshed_fragments() is deprecated since WC 3.0.0 but often used for compatibility.
		// Modern WooCommerce typically relies on JS events like 'wc_fragment_refresh'.
		if ( function_exists( 'wc_get_refreshed_fragments' ) ) {
			 $fragments = wc_get_refreshed_fragments();
		}

		// Send a success response with a message, cart URL, and data for frontend updates.
		wp_send_json_success( array(
			'message'   => __( 'Gift box successfully added to your cart!', 'woocommerce-gift-box-builder' ),
			'cart_url'  => wc_get_cart_url(),
			'fragments' => $fragments, // For mini-cart updates.
			'cart_hash' => WC()->cart->get_cart_hash(), // For cart state checking by some themes/plugins.
		) );
	}
}
?>
