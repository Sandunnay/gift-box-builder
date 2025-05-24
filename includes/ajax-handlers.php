<?php
/**
 * AJAX Handlers for Gift Box Builder.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * AJAX handler to get product details.
 * Handles simple and variable products.
 */
function gbb_get_product_details_ajax_handler() {
    // Verify nonce
    check_ajax_referer( 'gbb_product_nonce', 'nonce' );

    if ( ! isset( $_POST['product_id'] ) ) {
        wp_send_json_error( array( 'message' => __( 'Product ID not provided.', 'gift-box-builder' ) ) );
        return;
    }

    $product_id = intval( $_POST['product_id'] );
    $product = wc_get_product( $product_id );

    if ( ! $product ) {
        wp_send_json_error( array( 'message' => __( 'Product not found.', 'gift-box-builder' ) ) );
        return;
    }

    $image_id = $product->get_image_id();
    $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'woocommerce_single' ) : wc_placeholder_img_src('woocommerce_single');

    $data = array(
        'product_id'        => $product->get_id(),
        'name'              => $product->get_name(),
        'image_url'         => $image_url,
        'short_description' => $product->get_short_description() ? apply_filters( 'woocommerce_short_description', $product->get_short_description() ) : wc_format_content($product->get_description()),
        'price_html'        => $product->get_price_html(),
        'is_in_stock'       => $product->is_in_stock(),
        'stock_quantity'    => $product->get_stock_quantity(),
        'product_type'      => $product->get_type(),
        'is_variable'       => false, // Default to false
        'max_stock'         => $product->get_manage_stock() ? $product->get_stock_quantity() : '', // For simple product quantity
    );

    if ( $product->is_type( 'variable' ) ) {
        $data['is_variable'] = true;
        $data['max_stock'] = ''; // Reset for variable, as stock is per variation

        $available_variations = $product->get_available_variations();
        $formatted_variations = array();

        foreach ( $available_variations as $variation_data ) {
            $variation_product = wc_get_product( $variation_data['variation_id'] );
            if ( ! $variation_product ) {
                continue;
            }

            $variation_image_url = wp_get_attachment_image_url( $variation_product->get_image_id(), 'woocommerce_single' );
            if ( ! $variation_image_url && $variation_data['image_id'] ) { // Fallback to variation_data if WC_Product image is missing
                 $variation_image_url = wp_get_attachment_image_url( $variation_data['image_id'], 'woocommerce_single' );
            }
            
            $formatted_variations[] = array(
                'variation_id'        => $variation_product->get_id(),
                'attributes'          => $variation_data['attributes'], // e.g., array('attribute_pa_color' => 'Blue')
                'price_html'          => $variation_product->get_price_html(),
                'image'               => array( // Consistent with prompt's example structure
                    'url' => $variation_image_url ?: $image_url // Fallback to parent product image
                ),
                'is_in_stock'         => $variation_product->is_in_stock(),
                'stock_quantity'      => $variation_product->get_stock_quantity(), // For display
                'max_qty'             => $variation_product->get_manage_stock() === 'yes' && $variation_product->get_stock_quantity() !== null ? $variation_product->get_stock_quantity() : '', // For input max
                'display_price'       => $variation_product->get_price(), // Numeric price
                'display_regular_price' => $variation_product->get_regular_price(), // Numeric price
            );
        }
        $data['variations'] = $formatted_variations;

        $attributes_data = array();
        $product_attributes = $product->get_variation_attributes(); 
        foreach ( $product_attributes as $attribute_name => $options ) {
            $attributes_data[] = array(
                'name'    => wc_attribute_label( $attribute_name, $product ), 
                'slug'    => $attribute_name,
                'options' => $options, 
            );
        }
        $data['attributes_info'] = $attributes_data;
    }

    wp_send_json_success( $data );
}
add_action( 'wp_ajax_gbb_get_product_details', 'gbb_get_product_details_ajax_handler' );
add_action( 'wp_ajax_nopriv_gbb_get_product_details', 'gbb_get_product_details_ajax_handler' ); 


/**
 * AJAX handler to get details for multiple products/variations for the summary step.
 */
function gbb_get_summary_details_ajax_handler() {
    check_ajax_referer( 'gbb_product_nonce', 'nonce' ); // Re-use existing nonce for simplicity

    if ( ! isset( $_POST['items_to_fetch'] ) || ! is_array( $_POST['items_to_fetch'] ) ) {
        wp_send_json_error( array( 'message' => __( 'Invalid items data provided.', 'gift-box-builder' ) ) );
        return;
    }

    $items_to_fetch = array_map( 'sanitize_text_field', array_map( 'wp_unslash', $_POST['items_to_fetch'] ) );
    $detailed_items = array();

    foreach ( $items_to_fetch as $item_identifier_json ) {
        // Each item_identifier_json is a JSON string like '{"productId":123}' or '{"productId":123,"variationId":456}'
        $item_identifier = json_decode( $item_identifier_json, true );

        if ( ! isset( $item_identifier['productId'] ) ) {
            continue;
        }

        $product_id = intval( $item_identifier['productId'] );
        $variation_id = isset( $item_identifier['variationId'] ) ? intval( $item_identifier['variationId'] ) : 0;
        $item_key = $variation_id > 0 ? $variation_id . '_' . $product_id : $product_id;
        
        $product_to_fetch = $variation_id > 0 ? wc_get_product( $variation_id ) : wc_get_product( $product_id );

        if ( ! $product_to_fetch ) {
            $detailed_items[ $item_key ] = array( 'error' => 'Product or variation not found' );
            continue;
        }
        
        $image_id = $product_to_fetch->get_image_id();
        $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : wc_placeholder_img_src('thumbnail'); // Use thumbnail for summary

        $item_data = array(
            'id'                => $product_to_fetch->get_id(),
            'name'              => $product_to_fetch->get_name(),
            'price_html'        => $product_to_fetch->get_price_html(),
            'price'             => (float) $product_to_fetch->get_price(), // For calculation
            'image_url'         => $image_url,
            'product_type'      => $product_to_fetch->get_type(),
        );

        if ( $variation_id > 0 && $product_to_fetch->is_type( 'variation' ) ) {
            $item_data['attributes_description'] = wc_get_formatted_variation( $product_to_fetch, true, false, false ); // Get formatted attributes
        }
        
        $detailed_items[ $item_key ] = $item_data;
    }

    if ( empty( $detailed_items ) ) {
        wp_send_json_error( array( 'message' => __( 'Could not fetch details for any items.', 'gift-box-builder' ) ) );
        return;
    }

    wp_send_json_success( $detailed_items );
}
add_action( 'wp_ajax_gbb_get_summary_details', 'gbb_get_summary_details_ajax_handler' );
add_action( 'wp_ajax_nopriv_gbb_get_summary_details', 'gbb_get_summary_details_ajax_handler' );

/**
 * AJAX handler to add the complete gift box to the WooCommerce cart.
 */
function gbb_add_gift_box_to_cart_ajax_handler() {
    check_ajax_referer( 'gbb_product_nonce', 'nonce' );

    if ( ! isset( $_POST['gift_box_data'] ) ) {
        wp_send_json_error( array( 'message' => __( 'Gift box data not provided.', 'gift-box-builder' ) ) );
        return;
    }

    $gift_box_data_json = sanitize_text_field( wp_unslash( $_POST['gift_box_data'] ) );
    $gift_box_data = json_decode( $gift_box_data_json, true );

    if ( json_last_error() !== JSON_ERROR_NONE || empty( $gift_box_data ) ) {
        wp_send_json_error( array( 'message' => __( 'Invalid gift box data format.', 'gift-box-builder' ) ) );
        return;
    }

    $added_items_feedback = array();
    $errors = array();

    // Add Step 1 Box
    if ( ! empty( $gift_box_data['step1_selectedBoxId'] ) ) {
        $product_id = intval( $gift_box_data['step1_selectedBoxId'] );
        $quantity = 1; // Assuming 1 for the box
        $product = wc_get_product( $product_id );

        if ( ! $product ) {
            $errors[] = sprintf( __( 'Selected box (ID: %d) not found.', 'gift-box-builder' ), $product_id );
        } elseif ( ! $product->is_in_stock() || ! $product->has_enough_stock( $quantity ) ) {
            $errors[] = sprintf( __( 'Box \'%s\' is out of stock.', 'gift-box-builder' ), $product->get_name() );
        } else {
            $cart_item_key = WC()->cart->add_to_cart( $product_id, $quantity );
            if ( $cart_item_key ) {
                $added_items_feedback[] = sprintf( __( 'Added box: %s', 'gift-box-builder' ), $product->get_name() );
            } else {
                $errors[] = sprintf( __( 'Could not add box \'%s\' to cart.', 'gift-box-builder' ), $product->get_name() );
            }
        }
    } else {
        $errors[] = __( 'No box selected for the gift.', 'gift-box-builder' );
    }


    // Add Step 2 & 3 Items
    $item_groups = array( 'step2_items', 'step3_items' );
    foreach ( $item_groups as $group_key ) {
        if ( ! empty( $gift_box_data[ $group_key ] ) && is_array( $gift_box_data[ $group_key ] ) ) {
            foreach ( $gift_box_data[ $group_key ] as $item ) {
                $product_id = intval( $item['productId'] );
                $quantity = intval( $item['quantity'] );
                $variation_id = !empty( $item['variationId'] ) ? intval( $item['variationId'] ) : 0;
                $attributes = !empty( $item['attributes'] ) ? (array) $item['attributes'] : array();
                
                $item_to_add = $variation_id > 0 ? wc_get_product( $variation_id ) : wc_get_product( $product_id );

                if ( ! $item_to_add ) {
                    $errors[] = sprintf( __( 'Item (ID: %d) not found.', 'gift-box-builder' ), $variation_id ?: $product_id );
                    continue;
                }

                if ( ! $item_to_add->is_in_stock() || ! $item_to_add->has_enough_stock( $quantity ) ) {
                    $errors[] = sprintf( __( 'Item \'%s\' is out of stock for the requested quantity.', 'gift-box-builder' ), $item_to_add->get_name() );
                } else {
                    $cart_item_key = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $attributes );
                    if ( $cart_item_key ) {
                        $added_items_feedback[] = sprintf( __( 'Added: %d x %s', 'gift-box-builder' ), $quantity, $item_to_add->get_name() );
                    } else {
                        $errors[] = sprintf( __( 'Could not add item \'%s\' to cart.', 'gift-box-builder' ), $item_to_add->get_name() );
                    }
                }
            }
        }
    }

    if ( ! empty( $errors ) ) {
        // Optionally, if some items were added but others failed, you might still want to send a partial success
        // or clear the cart if it's an all-or-nothing scenario. For now, just report errors.
        wp_send_json_error( array( 'message' => implode( '; ', $errors ), 'items_added' => $added_items_feedback ) );
    } else {
        wp_send_json_success( array( 
            'message' => __( 'Gift box items added to cart successfully!', 'gift-box-builder' ), 
            'items_added' => $added_items_feedback, 
            'cart_url' => wc_get_cart_url() 
        ) );
    }
}
add_action( 'wp_ajax_gbb_add_gift_box_to_cart', 'gbb_add_gift_box_to_cart_ajax_handler' );
add_action( 'wp_ajax_nopriv_gbb_add_gift_box_to_cart', 'gbb_add_gift_box_to_cart_ajax_handler' );

?>
