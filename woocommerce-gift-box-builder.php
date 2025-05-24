<?php
/**
 * Plugin Name:       WooCommerce Gift Box Builder
 * Plugin URI:        https://example.com/woocommerce-gift-box-builder
 * Description:       Allows customers to build customizable gift boxes with selected WooCommerce products.
 * Version:           1.0.0
 * Author:            Jules
 * Author URI:        https://example.com/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       woocommerce-gift-box-builder
 * Domain Path:       /languages
 */

// Prevent direct file access
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define plugin constants
define( 'WGBB_VERSION', '1.0.0' );
define( 'WGBB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WGBB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Activation hook.
 *
 * @since 1.0.0
 */
function wgbb_activate() {
	// Placeholder for activation code (e.g., setting default options, flushing rewrite rules if CPTs are registered here).
	error_log( 'WooCommerce Gift Box Builder activated.' );
}
register_activation_hook( __FILE__, 'wgbb_activate' );

/**
 * Deactivation hook.
 *
 * @since 1.0.0
 */
function wgbb_deactivate() {
	// Placeholder for deactivation code (e.g., cleaning up options).
	error_log( 'WooCommerce Gift Box Builder deactivated.' );
}
register_deactivation_hook( __FILE__, 'wgbb_deactivate' );

/**
 * Include CPT registration and meta box handling for "Gift Box Bundles".
 * This file handles the creation of the custom post type and its associated admin fields.
 */
if ( file_exists( WGBB_PLUGIN_DIR . 'includes/admin/bundle-cpt.php' ) ) {
	require_once WGBB_PLUGIN_DIR . 'includes/admin/bundle-cpt.php';
} else {
	// Log an error and display an admin notice if the critical CPT file is missing.
	add_action( 'admin_notices', 'wgbb_missing_cpt_file_notice' );
	error_log( 'WooCommerce Gift Box Builder: Critical file missing - includes/admin/bundle-cpt.php' );
}

/**
 * Admin notice for missing CPT file.
 * @since 1.0.0
 */
function wgbb_missing_cpt_file_notice() {
	?>
	<div class="notice notice-error">
		<p><?php esc_html_e( 'WooCommerce Gift Box Builder: Critical file missing - includes/admin/bundle-cpt.php. Please reinstall the plugin.', 'woocommerce-gift-box-builder' ); ?></p>
	</div>
	<?php
}

/**
 * Include shortcode registration for the gift box builder.
 * This file defines the `[gift_box_builder]` shortcode and its rendering logic.
 */
if ( file_exists( WGBB_PLUGIN_DIR . 'includes/shortcodes/gift-box-shortcode.php' ) ) {
	require_once WGBB_PLUGIN_DIR . 'includes/shortcodes/gift-box-shortcode.php';
} else {
	// Log an error and display an admin notice if the critical shortcode file is missing.
	add_action( 'admin_notices', 'wgbb_missing_shortcode_file_notice' );
	error_log( 'WooCommerce Gift Box Builder: Critical file missing - includes/shortcodes/gift-box-shortcode.php' );
}

/**
 * Admin notice for missing shortcode file.
 * @since 1.0.0
 */
function wgbb_missing_shortcode_file_notice() {
	?>
	<div class="notice notice-error">
		<p><?php esc_html_e( 'WooCommerce Gift Box Builder: Critical file missing - includes/shortcodes/gift-box-shortcode.php. Please reinstall the plugin.', 'woocommerce-gift-box-builder' ); ?></p>
	</div>
	<?php
}

/**
 * Include AJAX handler functions.
 * This file contains all AJAX endpoints used by the frontend gift box builder.
 */
if ( file_exists( WGBB_PLUGIN_DIR . 'includes/ajax-functions.php' ) ) {
	require_once WGBB_PLUGIN_DIR . 'includes/ajax-functions.php';
} else {
	// Log an error and display an admin notice if the critical AJAX file is missing.
	add_action( 'admin_notices', 'wgbb_missing_ajax_file_notice' );
	error_log( 'WooCommerce Gift Box Builder: Critical file missing - includes/ajax-functions.php' );
}

/**
 * Admin notice for missing AJAX file.
 * @since 1.0.0
 */
function wgbb_missing_ajax_file_notice() {
	?>
	<div class="notice notice-error">
		<p><?php esc_html_e( 'WooCommerce Gift Box Builder: Critical file missing - includes/ajax-functions.php. Please reinstall the plugin.', 'woocommerce-gift-box-builder' ); ?></p>
	</div>
	<?php
}

// TODO: Consider adding a function to load the text domain for localization:
// add_action( 'plugins_loaded', 'wgbb_load_textdomain' );
// function wgbb_load_textdomain() {
//     load_plugin_textdomain( 'woocommerce-gift-box-builder', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
// }
?>
