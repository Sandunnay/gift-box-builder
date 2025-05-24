<?php
/**
 * Handles meta boxes for the Gift Bundle CPT.
 *
 * @package GiftBoxBuilder/Includes
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Register meta boxes for Gift Bundle CPT.
 *
 * @param WP_Post $post The post object.
 */
function gbb_add_bundle_meta_boxes( $post ) {
    add_meta_box(
        'gbb_step1_box_settings',
        __( 'Step 1: Box Selection Settings', 'gift-box-builder' ),
        'gbb_render_step1_box_settings_meta_box',
        'gift_bundle',
        'normal',
        'high'
    );
    add_meta_box(
        'gbb_step2_chocolates_settings',
        __( 'Step 2: Chocolates Selection Settings', 'gift-box-builder' ),
        'gbb_render_step2_chocolates_settings_meta_box',
        'gift_bundle',
        'normal',
        'high'
    );
    add_meta_box(
        'gbb_step3_additions_settings',
        __( 'Step 3: Additions Selection Settings', 'gift-box-builder' ),
        'gbb_render_step3_additions_settings_meta_box',
        'gift_bundle',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes_gift_bundle', 'gbb_add_bundle_meta_boxes' );

/**
 * Helper function to render category selection UI.
 *
 * @param WP_Post $post         The post object.
 * @param string  $meta_key     The meta key for storing selected categories.
 * @param string  $field_name   The name attribute for the select field.
 * @param string  $label_text   The label text for the field.
 */
function gbb_render_category_selection( $post, $meta_key, $field_name, $label_text ) {
    // Add a nonce field for security.
    // The nonce is added once per meta box, but it's fine to call it multiple times if needed within a single meta box context.
    // For simplicity here, we'll ensure it's present in each meta box rendering function.
    // A single nonce for all three meta boxes is also an option if they are part of one logical save operation.
    // Let's use one nonce for the whole set of meta boxes, rendered in the first meta box.
    if ($meta_key === '_gbb_step1_box_categories'){ // only render nonce for the first metabox
        wp_nonce_field( 'gbb_save_bundle_step_settings', 'gbb_bundle_step_nonce' );
    }

    $saved_categories = get_post_meta( $post->ID, $meta_key, true );
    if ( ! is_array( $saved_categories ) ) {
        $saved_categories = array();
    }

    $categories = get_terms( array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
    ) );

    echo '<p>';
    echo '<label for="' . esc_attr( $field_name ) . '">' . esc_html( $label_text ) . ':</label><br />';
    if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
        echo '<select name="' . esc_attr( $field_name ) . '[]" id="' . esc_attr( $field_name ) . '" multiple="multiple" style="width:100%; min-height: 150px;">';
        foreach ( $categories as $category ) {
            echo '<option value="' . esc_attr( $category->term_id ) . '"' . selected( in_array( $category->term_id, $saved_categories ), true, false ) . '>' . esc_html( $category->name ) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__( 'Select one or more product categories. Use Ctrl/Cmd + Click to select multiple.', 'gift-box-builder') . '</p>';
    } else {
        echo esc_html__( 'No product categories found.', 'gift-box-builder' );
    }
    echo '</p>';
}

/**
 * Render Meta Box for Step 1: Box Selection Settings.
 *
 * @param WP_Post $post The post object.
 */
function gbb_render_step1_box_settings_meta_box( $post ) {
    gbb_render_category_selection(
        $post,
        '_gbb_step1_box_categories',
        'gbb_step1_box_categories',
        __( 'Select Box Categories', 'gift-box-builder' )
    );
}

/**
 * Render Meta Box for Step 2: Chocolates Selection Settings.
 *
 * @param WP_Post $post The post object.
 */
function gbb_render_step2_chocolates_settings_meta_box( $post ) {
    gbb_render_category_selection(
        $post,
        '_gbb_step2_chocolate_categories',
        'gbb_step2_chocolate_categories',
        __( 'Select Chocolate Categories', 'gift-box-builder' )
    );
}

/**
 * Render Meta Box for Step 3: Additions Selection Settings.
 *
 * @param WP_Post $post The post object.
 */
function gbb_render_step3_additions_settings_meta_box( $post ) {
    gbb_render_category_selection(
        $post,
        '_gbb_step3_addition_categories',
        'gbb_step3_addition_categories',
        __( 'Select Addition Categories', 'gift-box-builder' )
    );
}

/**
 * Save meta box data for Gift Bundle CPT.
 *
 * @param int $post_id The ID of the post being saved.
 */
function gbb_save_bundle_meta_data( $post_id ) {
    // Check if our nonce is set.
    if ( ! isset( $_POST['gbb_bundle_step_nonce'] ) ) {
        return;
    }
    // Verify that the nonce is valid.
    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gbb_bundle_step_nonce'] ) ), 'gbb_save_bundle_step_settings' ) ) {
        return;
    }

    // If this is an autosave, our form has not been submitted, so we don't want to do anything.
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Check the user's permissions.
    if ( isset( $_POST['post_type'] ) && 'gift_bundle' === $_POST['post_type'] ) {
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
    } else {
        // Not a gift_bundle post, so we don't need to do anything.
        // This check might be redundant if hooked to save_post_gift_bundle, but good for save_post.
        return; 
    }
    
    // Sanitize and save Step 1 categories.
    if ( isset( $_POST['gbb_step1_box_categories'] ) ) {
        $step1_categories = array_map( 'intval', (array) $_POST['gbb_step1_box_categories'] );
        update_post_meta( $post_id, '_gbb_step1_box_categories', $step1_categories );
    } else {
        // If nothing is selected, save an empty array or delete meta.
        update_post_meta( $post_id, '_gbb_step1_box_categories', array() );
    }

    // Sanitize and save Step 2 categories.
    if ( isset( $_POST['gbb_step2_chocolate_categories'] ) ) {
        $step2_categories = array_map( 'intval', (array) $_POST['gbb_step2_chocolate_categories'] );
        update_post_meta( $post_id, '_gbb_step2_chocolate_categories', $step2_categories );
    } else {
        update_post_meta( $post_id, '_gbb_step2_chocolate_categories', array() );
    }

    // Sanitize and save Step 3 categories.
    if ( isset( $_POST['gbb_step3_addition_categories'] ) ) {
        $step3_categories = array_map( 'intval', (array) $_POST['gbb_step3_addition_categories'] );
        update_post_meta( $post_id, '_gbb_step3_addition_categories', $step3_categories );
    } else {
        update_post_meta( $post_id, '_gbb_step3_addition_categories', array() );
    }
}
add_action( 'save_post_gift_bundle', 'gbb_save_bundle_meta_data' );

?>
