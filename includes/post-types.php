<?php
/**
 * Registers the custom post type for Gift Bundles.
 *
 * @package GiftBoxBuilder/Includes
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Register Gift Bundle Custom Post Type.
 */
function gbb_register_gift_bundle_post_type() {
    $labels = array(
        'name'                  => _x( 'Gift Box Builder', 'Post Type General Name', 'gift-box-builder' ),
        'singular_name'         => _x( 'Gift Bundle', 'Post Type Singular Name', 'gift-box-builder' ),
        'menu_name'             => __( 'Gift Box Builder', 'gift-box-builder' ),
        'name_admin_bar'        => __( 'Gift Bundle', 'gift-box-builder' ),
        'archives'              => __( 'Gift Bundle Archives', 'gift-box-builder' ),
        'attributes'            => __( 'Gift Bundle Attributes', 'gift-box-builder' ),
        'parent_item_colon'     => __( 'Parent Gift Bundle:', 'gift-box-builder' ),
        'all_items'             => __( 'All Gift Bundles', 'gift-box-builder' ), // "Manage" link
        'add_new_item'          => __( 'Add New Gift Bundle', 'gift-box-builder' ),
        'add_new'               => __( 'Add New Bundle', 'gift-box-builder' ), // "Add New" link
        'new_item'              => __( 'New Gift Bundle', 'gift-box-builder' ),
        'edit_item'             => __( 'Edit Gift Bundle', 'gift-box-builder' ),
        'update_item'           => __( 'Update Gift Bundle', 'gift-box-builder' ),
        'view_item'             => __( 'View Gift Bundle', 'gift-box-builder' ),
        'view_items'            => __( 'View Gift Bundles', 'gift-box-builder' ),
        'search_items'          => __( 'Search Gift Bundles', 'gift-box-builder' ),
        'not_found'             => __( 'No Gift Bundles found', 'gift-box-builder' ),
        'not_found_in_trash'    => __( 'No Gift Bundles found in Trash', 'gift-box-builder' ),
        'featured_image'        => __( 'Featured Image', 'gift-box-builder' ),
        'set_featured_image'    => __( 'Set featured image', 'gift-box-builder' ),
        'remove_featured_image' => __( 'Remove featured image', 'gift-box-builder' ),
        'use_featured_image'    => __( 'Use as featured image', 'gift-box-builder' ),
        'insert_into_item'      => __( 'Insert into Gift Bundle', 'gift-box-builder' ),
        'uploaded_to_this_item' => __( 'Uploaded to this Gift Bundle', 'gift-box-builder' ),
        'items_list'            => __( 'Gift Bundles list', 'gift-box-builder' ),
        'items_list_navigation' => __( 'Gift Bundles list navigation', 'gift-box-builder' ),
        'filter_items_list'     => __( 'Filter Gift Bundles list', 'gift-box-builder' ),
    );
    $args = array(
        'label'                 => __( 'Gift Bundle', 'gift-box-builder' ),
        'description'           => __( 'Custom post type for gift bundles.', 'gift-box-builder' ),
        'labels'                => $labels,
        'supports'              => array( 'title', 'editor', 'thumbnail' ),
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 20, // Below Pages
        'menu_icon'             => 'dashicons-archive',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'has_archive'           => true,
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'capability_type'       => 'post',
        'rewrite'               => array( 'slug' => 'gift-bundles' ),
        'show_in_rest'          => true, // Enable Gutenberg editor support
    );
    register_post_type( 'gift_bundle', $args );
}
add_action( 'init', 'gbb_register_gift_bundle_post_type', 0 );

?>
