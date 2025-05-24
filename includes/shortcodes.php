<?php
/**
 * Shortcode definitions for Gift Box Builder.
 *
 * @package GiftBoxBuilder/Includes
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Renders the Gift Box Builder interface.
 *
 * Shortcode: [gift_box_builder bundle_id="X"]
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML output for the gift box builder.
 */
function gbb_render_gift_box_builder_shortcode( $atts ) {
    $atts = shortcode_atts(
        array(
            'bundle_id' => null,
        ),
        $atts,
        'gift_box_builder'
    );

    $bundle_id = $atts['bundle_id'];

    // Validate bundle_id
    if ( empty( $bundle_id ) || ! is_numeric( $bundle_id ) || intval( $bundle_id ) <= 0 ) {
        return '<p>' . esc_html__( 'Gift Box Builder: Invalid or missing bundle ID.', 'gift-box-builder' ) . '</p>';
    }

    $bundle_id = intval( $bundle_id );
    $bundle_post = get_post( $bundle_id );

    if ( ! $bundle_post || 'gift_bundle' !== $bundle_post->post_type || 'publish' !== $bundle_post->post_status ) {
        return '<p>' . esc_html__( 'Gift Box Builder: Specified bundle ID is invalid or does not refer to a published gift bundle.', 'gift-box-builder' ) . '</p>';
    }

    // If bundle_id is valid, output placeholder HTML with Bootstrap structure
    ob_start();
    ?>
    <div class="container gift-box-builder-container my-5" data-bundle-id="<?php echo esc_attr( $bundle_id ); ?>">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8">

                <!-- Step 1: Select Box -->
                <div class="card gbb-step shadow-sm" id="gbb-step-1">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><?php esc_html_e( 'Step 1: Select Your Box', 'gift-box-builder' ); ?></h5>
                    </div>
                    <div class="card-body">
                        <p class="card-text"><?php esc_html_e( 'Choose the perfect box for your gift.', 'gift-box-builder' ); ?></p>
                        <div class="gbb-step-content">
                            <?php
                            $step1_categories = get_post_meta( $bundle_id, '_gbb_step1_box_categories', true );
                            echo gbb_display_products_for_step(
                                $step1_categories,
                                __( 'No boxes found in the configured categories for this step.', 'gift-box-builder' ),
                                __( 'Select Box', 'gift-box-builder' ),
                                'btn-outline-primary gbb-select-button', 
                                'gbb-selectable-box',
                                1 // Current step number
                            );
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Select Chocolates -->
                <div class="card gbb-step shadow-sm d-none" id="gbb-step-2">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><?php esc_html_e( 'Step 2: Select Chocolates', 'gift-box-builder' ); ?></h5>
                    </div>
                    <div class="card-body">
                        <p class="card-text"><?php esc_html_e( 'Pick your favorite chocolates. You can select multiple items.', 'gift-box-builder' ); ?></p>
                        <div class="gbb-step-content">
                            <?php
                            $step2_categories = get_post_meta( $bundle_id, '_gbb_step2_chocolate_categories', true );
                            echo gbb_display_products_for_step(
                                $step2_categories,
                                __( 'No chocolates found in the configured categories for this step.', 'gift-box-builder' ),
                                __( 'Details & Add', 'gift-box-builder' ), 
                                'gbb-view-details-button btn-outline-info', 
                                'gbb-addable-item',
                                2 // Current step number
                            );
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Choose Additions -->
                <div class="card gbb-step shadow-sm d-none" id="gbb-step-3">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><?php esc_html_e( 'Step 3: Choose Additions', 'gift-box-builder' ); ?></h5>
                    </div>
                    <div class="card-body">
                        <p class="card-text"><?php esc_html_e( 'Select any additional items to include. You can select multiple items.', 'gift-box-builder' ); ?></p>
                        <div class="gbb-step-content">
                             <?php
                            $step3_categories = get_post_meta( $bundle_id, '_gbb_step3_addition_categories', true );
                            echo gbb_display_products_for_step(
                                $step3_categories,
                                __( 'No additions found in the configured categories for this step.', 'gift-box-builder' ),
                                __( 'Details & Add', 'gift-box-builder' ), 
                                'gbb-view-details-button btn-outline-info', 
                                'gbb-addable-item',
                                3 // Current step number
                            );
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Finalize -->
                <div class="card gbb-step shadow-sm d-none" id="gbb-step-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><?php esc_html_e( 'Step 4: Review & Finalize', 'gift-box-builder' ); ?></h5>
                    </div>
                    <div class="card-body">
                        <p class="card-text"><?php esc_html_e( 'Review your selections and complete your gift box.', 'gift-box-builder' ); ?></p>
                        <div class="gbb-step-content">
                            <h4 class="mb-3"><?php esc_html_e( 'Review Your Custom Gift Box', 'gift-box-builder' ); ?></h4>
        
                            <div id="gbb-summary-step1-box" class="mb-3">
                                <h5><?php esc_html_e( 'Selected Box:', 'gift-box-builder' ); ?></h5>
                                <p><em><?php esc_html_e( 'Loading...', 'gift-box-builder' ); ?></em></p> 
                            </div>
                            
                            <div id="gbb-summary-step2-chocolates" class="mb-3">
                                <h5><?php esc_html_e( 'Selected Chocolates:', 'gift-box-builder' ); ?></h5>
                                <ul class="list-group">
                                    <li class="list-group-item"><em><?php esc_html_e( 'Loading...', 'gift-box-builder' ); ?></em></li>
                                </ul>
                            </div>
                            
                            <div id="gbb-summary-step3-additions" class="mb-3">
                                <h5><?php esc_html_e( 'Selected Additions:', 'gift-box-builder' ); ?></h5>
                                <ul class="list-group">
                                    <li class="list-group-item"><em><?php esc_html_e( 'Loading...', 'gift-box-builder' ); ?></em></li>
                                </ul>
                            </div>
                            
                            <div id="gbb-summary-total-price" class="mb-4">
                                <h5><?php esc_html_e( 'Estimated Total:', 'gift-box-builder' ); ?></h5>
                                <p><strong><em><?php esc_html_e( 'Calculating...', 'gift-box-builder' ); ?></em></strong></p>
                            </div>

                            <div class="gbb-step4-actions mt-4">
                                <button type="button" class="btn btn-primary btn-lg gbb-add-to-cart-ajax-button"><?php esc_html_e( 'Add Gift Box to Cart', 'gift-box-builder' ); ?></button>
                                <button type="button" class="btn btn-success btn-lg gbb-build-another-box-button" style="display: none;"><?php esc_html_e( 'Build Another Box', 'gift-box-builder' ); ?></button>
                            </div>
                            <div id="gbb-add-to-cart-message" class="mt-3"></div> 
                        </div>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="gbb-navigation mt-4 d-flex justify-content-between">
                    <button class="btn btn-secondary d-none" id="gbb-nav-prev"><?php esc_html_e( 'Previous', 'gift-box-builder' ); ?></button>
                    <button class="btn btn-primary" id="gbb-nav-next"><?php esc_html_e( 'Next', 'gift-box-builder' ); ?></button>
                    <a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="btn btn-info gbb-view-cart-button d-none"><?php esc_html_e( 'View Cart', 'gift-box-builder' ); ?></a>
                </div>
                
                <div class="mt-3 text-center">
                     <small class="text-muted"><?php esc_html_e( 'Bundle ID:', 'gift-box-builder' ); ?> <?php echo esc_html( $bundle_id ); ?></small>
                </div>

            </div>
        </div>
    </div>
    <!-- Product Details Modal -->
    <div class="modal fade" id="gbbProductModal" tabindex="-1" aria-labelledby="gbbProductModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg"> <!-- modal-lg for more space -->
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="gbbProductModalLabel">Product Name</h5> <!-- Will be updated by JS -->
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-6 text-center" id="gbb-modal-image-container">
                                <img src="" alt="Product Image" class="img-fluid" style="max-height: 300px; object-fit: contain;">
                            </div>
                            <div class="col-md-6">
                                <h4 id="gbb-modal-product-name-display" class="mb-3"></h4> <!-- Updated by JS -->
                                <div id="gbb-modal-short-description" class="mb-3"></div>
                                <div class="mb-3"><strong>Price:</strong> <span id="gbb-modal-price"></span></div>
                                <div id="gbb-modal-variations-container" class="mb-3">
                                    <!-- Variations will be loaded here by JS -->
                                </div>
                                <div class="gbb-modal-quantity-selector mb-3">
                                    <label for="gbb-modal-quantity" class="form-label">Quantity:</label>
                                    <div class="input-group" style="max-width: 150px;">
                                        <button class="btn btn-outline-secondary gbb-qty-btn" type="button" data-action="decrease">-</button>
                                        <input type="number" id="gbb-modal-quantity" class="form-control text-center" value="1" min="1" data-max-stock="">
                                        <button class="btn btn-outline-secondary gbb-qty-btn" type="button" data-action="increase">+</button>
                                    </div>
                                    <small class="text-muted d-block mt-1" id="gbb-modal-stock-message"></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="gbb-modal-add-item-button" data-product-id="" data-product-type="" data-current-step="">Add to Box</button>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Register shortcodes used by the plugin.
 */
function gbb_register_shortcodes() {
    add_shortcode( 'gift_box_builder', 'gbb_render_gift_box_builder_shortcode' );
}

/**
 * Helper function to display products for a given step.
 *
 * @param array  $category_ids         Array of category IDs.
 * @param string $no_products_message  Message if no products are found.
 * @param string $button_text          Text for the action button.
 * @param string $button_class         CSS class for the action button.
 * @param string $item_class           CSS class for the product item card.
 * @param int    $current_step_number  The number of the current step.
 * @return string HTML for the products display.
 */
function gbb_display_products_for_step( $category_ids, $no_products_message, $button_text, $button_class, $item_class, $current_step_number ) {
    ob_start();
    if ( ! empty( $category_ids ) && is_array( $category_ids ) ) {
        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'tax_query'      => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => $category_ids,
                ),
            ),
            'meta_query' => array(
                array(
                    'key' => '_stock_status',
                    'value' => 'instock',
                    'compare' => '=',
                )
            )
        );
        $products_query = new WP_Query( $args );

        if ( $products_query->have_posts() ) {
            echo '<div class="row gbb-product-list">';
            while ( $products_query->have_posts() ) {
                $products_query->the_post();
                $product = wc_get_product( get_the_ID() );
                $image_url = wp_get_attachment_image_url( $product->get_image_id(), 'woocommerce_thumbnail' );
                if ( ! $image_url ) {
                    $image_url = wc_placeholder_img_src();
                }
                ?>
                <div class="col-md-4 mb-3">
                    <div class="card <?php echo esc_attr($item_class); ?> h-100" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>" data-step-number="<?php echo esc_attr($current_step_number); ?>" style="cursor: pointer;">
                        <img src="<?php echo esc_url( $image_url ); ?>" class="card-img-top p-3" alt="<?php echo esc_attr( $product->get_name() ); ?>" style="max-height: 180px; object-fit: contain;">
                        <div class="card-body text-center d-flex flex-column">
                            <h5 class="card-title fs-6 mb-1"><?php echo esc_html( $product->get_name() ); ?></h5>
                            <?php if ( $product->get_short_description() ) : ?>
                                <small class="text-muted mb-2"><?php echo wp_kses_post( wp_trim_words( $product->get_short_description(), 10, '...' ) ); ?></small>
                            <?php endif; ?>
                            <?php if ( $product->get_price_html() ) : ?>
                                <p class="card-text price mt-auto mb-2"><?php echo wp_kses_post( $product->get_price_html() ); ?></p>
                            <?php endif; ?>
                            <button class="btn <?php echo esc_attr($button_class); ?> btn-sm mt-auto" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>" data-step-number="<?php echo esc_attr($current_step_number); ?>"><?php echo esc_html( $button_text ); ?></button>
                        </div>
                    </div>
                </div>
                <?php
            }
            echo '</div>';
            wp_reset_postdata();
        } else {
            echo '<p>' . esc_html( $no_products_message ) . '</p>';
        }
    } else {
        echo '<p>' . esc_html__( 'No categories have been configured for this step.', 'gift-box-builder' ) . '</p>';
    }
    return ob_get_clean();
}

add_action( 'init', 'gbb_register_shortcodes' );

?>
