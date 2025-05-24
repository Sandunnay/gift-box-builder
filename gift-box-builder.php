<?php
/**
 * Plugin Name:       Gift Box Builder
 * Plugin URI:        https://example.com/plugins/gift-box-builder/
 * Description:       Allows customers to build customizable gift boxes with selected WooCommerce products.
 * Version:           1.0.0
 * Author:            Your Name or Company
 * Author URI:        https://example.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       gift-box-builder
 * Domain Path:       /languages
 * WC requires at least: 3.0
 * WC tested up to: 8.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Define GBB_PLUGIN_FILE - a constant for the main plugin file path.
if ( ! defined( 'GBB_PLUGIN_FILE' ) ) {
    define( 'GBB_PLUGIN_FILE', __FILE__ );
}

// Define GBB_PLUGIN_DIR - a constant for the plugin directory path.
if ( ! defined( 'GBB_PLUGIN_DIR' ) ) {
    define( 'GBB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

// Define GBB_VERSION - a constant for the plugin version.
if ( ! defined( 'GBB_VERSION' ) ) {
    define( 'GBB_VERSION', '1.0.0' );
}

// Add any other initial setup, like loading dependencies if you had an autoloader or core functions file.
// For now, it can be minimal.

// Load Post Types
require_once GBB_PLUGIN_DIR . 'includes/post-types.php';

// Load Meta Boxes
require_once GBB_PLUGIN_DIR . 'includes/meta-boxes.php';

// Load Shortcodes
require_once GBB_PLUGIN_DIR . 'includes/shortcodes.php';

// Load Assets Enqueue
require_once GBB_PLUGIN_DIR . 'includes/assets.php';

// Load AJAX Handlers
require_once GBB_PLUGIN_DIR . 'includes/ajax-handlers.php';
?>
