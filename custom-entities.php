<?php
/*
Plugin Name: Custom Entities
Description: Plugin for creating and managing entities (applications or bots) with voting functionality.
Version: 1.9
Author: Ped4enko
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Register custom post types and taxonomy
function ce_register_custom_post_types() {
    $entity_labels = array(
        'name'               => 'Entities',
        'singular_name'      => 'Entity',
        'menu_name'          => 'Entities',
        'name_admin_bar'     => 'Entity',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Entity',
        'new_item'           => 'New Entity',
        'edit_item'          => 'Edit Entity',
        'view_item'          => 'View Entity',
        'all_items'          => 'All Entities',
        'search_items'       => 'Search Entities',
        'parent_item_colon'  => 'Parent Entity:',
        'not_found'          => 'No entities found.',
        'not_found_in_trash' => 'No entities found in Trash.'
    );

    $entity_args = array(
        'labels'             => $entity_labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'menu_position'      => 5,
        'menu_icon'          => 'dashicons-admin-users',
        'supports'           => array('title', 'editor', 'thumbnail', 'excerpt'),
        'taxonomies'         => array('entity_features')
    );

    $list_labels = array(
        'name'               => 'Entity Lists',
        'singular_name'      => 'Entity List',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Entity List',
        'new_item'           => 'New Entity List',
        'edit_item'          => 'Edit Entity List',
        'view_item'          => 'View Entity List',
        'all_items'          => 'All Entity Lists',
        'search_items'       => 'Search Entity Lists',
        'not_found'          => 'No entity lists found.',
        'not_found_in_trash' => 'No entity lists found in Trash.'
    );

    $list_args = array(
        'labels'             => $list_labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => 'edit.php?post_type=entity',
        'supports'           => array('title', 'editor', 'custom-fields')
    );

    register_post_type('entity', $entity_args);
    register_post_type('entity_list', $list_args);

    register_taxonomy(
        'entity_features',
        'entity',
        array(
            'label' => 'Key Features',
            'rewrite' => array('slug' => 'entity-features'),
            'hierarchical' => false,
        )
    );
}
add_action('init', 'ce_register_custom_post_types');

// Add meta boxes for entity lists
function ce_add_entity_list_meta_boxes() {
    add_meta_box(
        'entity_list_details',
        'Entity List Details',
        'ce_render_entity_list_details_meta_box',
        'entity_list',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'ce_add_entity_list_meta_boxes');

function ce_render_entity_list_details_meta_box($post) {
    $entity_ids = get_post_meta($post->ID, '_entity_ids', true);
    $entity_votes = get_post_meta($post->ID, '_entity_votes', true);
    $show_title = get_post_meta($post->ID, '_entity_list_show_title', true);
    ?>
    <p>
        <label for="entity_ids">Entity IDs (comma separated):</label>
        <input type="text" name="entity_ids" id="entity_ids" value="<?php echo esc_attr($entity_ids); ?>" class="widefat" />
    </p>
    <p>
        <label for="entity_votes">Votes Count (comma separated, corresponding to entity IDs):</label>
        <input type="text" name="entity_votes" id="entity_votes" value="<?php echo esc_attr($entity_votes); ?>" class="widefat" />
    </p>
    <p>
        <label for="entity_list_show_title">Show list title:</label>
        <input type="checkbox" name="entity_list_show_title" id="entity_list_show_title" value="1" <?php checked($show_title, '1'); ?> />
    </p>
    <?php
}

function ce_save_entity_list_meta_boxes($post_id) {
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!isset($_POST['entity_list_nonce']) || !wp_verify_nonce($_POST['entity_list_nonce'], 'save_entity_list')) {
        return;
    }

    if (isset($_POST['entity_ids'])) {
        update_post_meta($post_id, '_entity_ids', sanitize_text_field($_POST['entity_ids']));
    }

    if (isset($_POST['entity_votes'])) {
        update_post_meta($post_id, '_entity_votes', sanitize_text_field($_POST['entity_votes']));
    }

    $show_title = isset($_POST['entity_list_show_title']) ? '1' : '0';
    update_post_meta($post_id, '_entity_list_show_title', $show_title);
}
add_action('save_post', 'ce_save_entity_list_meta_boxes');

// Add nonce field for security
function ce_entity_list_meta_box_nonce() {
    wp_nonce_field('save_entity_list', 'entity_list_nonce');
}
add_action('edit_form_after_title', 'ce_entity_list_meta_box_nonce');

// Add meta boxes for entities
function ce_add_meta_boxes() {
    add_meta_box(
        'entity_details',
        'Entity Details',
        'ce_render_entity_details_meta_box',
        'entity',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'ce_add_meta_boxes');

function ce_render_entity_details_meta_box($post) {
    $logo = get_post_meta($post->ID, '_entity_logo', true);
    $link = get_post_meta($post->ID, '_entity_link', true);
    ?>
    <p>
        <label for="entity_logo">Logo (URL or select from gallery):</label>
        <input type="text" name="entity_logo" id="entity_logo" value="<?php echo esc_attr($logo); ?>" class="widefat" />
        <input type="button" id="upload_logo_button" class="button" value="Select from Gallery" />
    </p>
    <p>
        <label for="entity_link">Link:</label>
        <input type="text" name="entity_link" id="entity_link" value="<?php echo esc_attr($link); ?>" class="widefat" />
    </p>
    <?php
}

function ce_save_entity_meta_boxes($post_id) {
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['entity_logo'])) {
        update_post_meta($post_id, '_entity_logo', sanitize_text_field($_POST['entity_logo']));
    }

    if (isset($_POST['entity_link'])) {
        update_post_meta($post_id, '_entity_link', esc_url($_POST['entity_link']));
    }
}
add_action('save_post', 'ce_save_entity_meta_boxes');

// Add shortcode for displaying entities
function ce_entities_shortcode($atts) {
    $atts = shortcode_atts(array(
        'orderby' => 'date',
        'order' => 'DESC'
    ), $atts, 'entities');

    $query = new WP_Query(array(
        'post_type' => 'entity',
        'posts_per_page' => -1,
        'orderby' => $atts['orderby'],
        'order' => $atts['order']
    ));

    if ($query->have_posts()) {
        $output = '<div class="entities-list">';
        while ($query->have_posts()) {
            $query->the_post();
            $logo = get_post_meta(get_the_ID(), '_entity_logo', true);
            $link = get_post_meta(get_the_ID(), '_entity_link', true);
            $features = wp_get_post_terms(get_the_ID(), 'entity_features', array("fields" => "names"));
            $rating = get_post_meta(get_the_ID(), '_entity_rating', true);
            $votes = get_post_meta(get_the_ID(), '_entity_votes', true);
            if (!$votes) {
                $votes = 0;
            }
            if (!$rating) {
                $rating = 0;
            }

            $output .= '<div class="entity">';
            if ($logo) {
                $output .= '<img src="' . esc_url($logo) . '" alt="' . get_the_title() . '" class="entity-logo" />';
            }
            $output .= '<div class="entity-title"><a href="' . esc_url($link) . '">' . get_the_title() . '</a></div>';
            $output .= '<div class="entity-description">' . get_the_excerpt() . '</div>';
            if ($features) {
                $output .= '<div class="entity-features">' . implode(', ', $features) . '</div>';
            }

            // Add voting buttons
            $output .= '<div class="entity-rating">';
            $output .= '<p>Rating: ' . round($rating, 2) . ' (' . $votes . ' votes)</p>';
            $output .= '<span class="rate" data-post-id="' . get_the_ID() . '" data-rating="1"><i class="dashicons dashicons-arrow-up-alt"></i></span> ';
            $output .= '</div>';

            $output .= '</div>';
        }
        $output .= '</div>';
        wp_reset_postdata();
        return $output;
    } else {
        return '<p>No entities found.</p>';
    }
}
add_shortcode('entities', 'ce_entities_shortcode');

// Enqueue scripts and styles
function ce_enqueue_scripts() {
    wp_enqueue_script('ce-rating', plugin_dir_url(__FILE__) . 'rating.js', array('jquery'), '1.0', true);
    wp_localize_script('ce-rating', 'ceRating', array(
        'ajaxurl' => admin_url('admin-ajax.php')
    ));
    wp_enqueue_style('ce-rating-style', plugin_dir_url(__FILE__) . 'style.css');
    wp_enqueue_media(); // Add support for Media Library
}
add_action('wp_enqueue_scripts', 'ce_enqueue_scripts');

// Handle voting via AJAX
function ce_handle_vote() {
    if (isset($_POST['post_id']) && isset($_POST['rating']) && isset($_POST['list_id'])) {
        $post_id = intval($_POST['post_id']);
        $rating = intval($_POST['rating']);
        $list_id = intval($_POST['list_id']);

        session_start();
        if (isset($_SESSION['voted_' . $post_id])) {
            wp_send_json_error(array('message' => 'You have already voted for this entity.'));
            return;
        }

        // Overall rating
        $current_rating = get_post_meta($post_id, '_entity_rating', true);
        $votes = get_post_meta($post_id, '_entity_votes', true);
        if (!$votes) {
            $votes = 0;
        }
        if (!$current_rating) {
            $current_rating = 0;
        }
        $new_votes = $votes + 1;
        $new_rating = (($current_rating * $votes) + $rating) / $new_votes;
        update_post_meta($post_id, '_entity_rating', $new_rating);
        update_post_meta($post_id, '_entity_votes', $new_votes);

        // Rating in specific list
        $list_current_rating = get_post_meta($post_id, '_entity_rating_' . $list_id, true);
        $list_votes = get_post_meta($post_id, '_entity_votes_' . $list_id, true);
        if (!$list_votes) {
            $list_votes = 0;
        }
        if (!$list_current_rating) {
            $list_current_rating = 0;
        }
        $list_new_votes = $list_votes + 1;
        $list_new_rating = (($list_current_rating * $list_votes) + $rating) / $list_new_votes;
        update_post_meta($post_id, '_entity_rating_' . $list_id, $list_new_rating);
        update_post_meta($post_id, '_entity_votes_' . $list_id, $list_new_votes);

        $_SESSION['voted_' . $post_id] = true;

        wp_send_json_success(array(
            'rating' => $new_rating,
            'votes' => $new_votes,
            'list_rating' => $list_new_rating,
            'list_votes' => $list_new_votes
        ));
    }
    wp_send_json_error();
}
add_action('wp_ajax_ce_handle_vote', 'ce_handle_vote');
add_action('wp_ajax_nopriv_ce_handle_vote', 'ce_handle_vote');

// Add custom columns to entities list in admin panel
function ce_set_custom_columns($columns) {
    $columns['logo'] = 'Logo';
    $columns['link'] = 'Link';
    $columns['features'] = 'Key Features';
    $columns['rating'] = 'Rating';
    return $columns;
}
add_filter('manage_entity_posts_columns', 'ce_set_custom_columns');

function ce_custom_column($column, $post_id) {
    switch ($column) {
        case 'logo':
            $logo = get_post_meta($post_id, '_entity_logo', true);
            if ($logo) {
                echo '<img src="' . esc_url($logo) . '" alt="" style="max-width: 50px; max-height: 50px;" />';
            }
            break;
        case 'link':
            $link = get_post_meta($post_id, '_entity_link', true);
            if ($link) {
                echo '<a href="' . esc_url($link) . '" target="_blank">' . esc_url($link) . '</a>';
            }
            break;
        case 'features':
            $features = wp_get_post_terms($post_id, 'entity_features', array("fields" => "names"));
            echo esc_html(implode(', ', $features));
            break;
        case 'rating':
            $rating = get_post_meta($post_id, '_entity_rating', true);
            $votes = get_post_meta($post_id, '_entity_votes', true);
            if (!$votes) {
                $votes = 0;
            }
            if (!$rating) {
                $rating = 0;
            }
            echo round($rating, 2) . ' (' . $votes . ' votes)';
            break;
    }
}
add_action('manage_entity_posts_custom_column', 'ce_custom_column', 10, 2);

// Add custom columns to entity lists in admin panel
function ce_set_custom_entity_list_columns($columns) {
    $columns['shortcode'] = 'Shortcode';
    return $columns;
}
add_filter('manage_entity_list_posts_columns', 'ce_set_custom_entity_list_columns');

function ce_custom_entity_list_column($column, $post_id) {
    switch ($column) {
        case 'shortcode':
            echo '[entity_list id="' . $post_id . '"]';
            break;
    }
}
add_action('manage_entity_list_posts_custom_column', 'ce_custom_entity_list_column', 10, 2);

// Include shortcode file
require_once plugin_dir_path(__FILE__) . 'shortcodes.php';
?>
