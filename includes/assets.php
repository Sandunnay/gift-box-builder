<?php
/**
 * Handles asset enqueueing for Gift Box Builder.
 *
 * @package GiftBoxBuilder/Includes
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Enqueue frontend scripts and styles for the Gift Box Builder.
 *
 * Only enqueues assets if the [gift_box_builder] shortcode is found
 * on a singular page.
 */
function gbb_enqueue_frontend_assets() {
    // Check if we are on a singular page and the shortcode exists in the post content.
    // For better performance, this check should be as specific as possible.
    if ( is_singular() && has_shortcode( get_queried_object()->post_content, 'gift_box_builder' ) ) {
        
        // Enqueue styles.
        wp_enqueue_style(
            'gbb-frontend-styles', 
            plugin_dir_url( GBB_PLUGIN_FILE ) . 'assets/css/style.css', 
            array(), 
            GBB_VERSION 
        );

        // Enqueue scripts.
        wp_enqueue_script(
            'gbb-frontend-js', // Ensure this handle is correct
            plugin_dir_url( GBB_PLUGIN_FILE ) . 'assets/js/frontend.js', 
            array(), 
            GBB_VERSION, 
            true 
        );

        // Localize script with AJAX URL and nonce.
        // Ensure this uses the correct script handle 'gbb-frontend-js'
        // and the nonce name 'gbb_product_nonce' matches the one in check_ajax_referer.
        wp_localize_script(
            'gbb-frontend-js', // Script handle
            'gbb_ajax_object', // Object name in JavaScript
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'gbb_product_nonce' ) // Nonce for verification
            )
        );
    }
}
add_action( 'wp_enqueue_scripts', 'gbb_enqueue_frontend_assets' );

?>
