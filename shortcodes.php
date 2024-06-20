<?php
// Shortcode for displaying entity lists by ID
function ce_entity_list_shortcode($atts) {
    $atts = shortcode_atts(array(
        'id' => '',
        'show_title' => 'true'
    ), $atts, 'entity_list');

    $list_id = intval($atts['id']);
    if (!$list_id) {
        return '<p>Entity list not found.</p>';
    }

    $show_title = get_post_meta($list_id, '_entity_list_show_title', true);
    $entity_ids = get_post_meta($list_id, '_entity_ids', true);
    $entity_votes = get_post_meta($list_id, '_entity_votes', true);
    if (!$entity_ids) {
        return '<p>Entity list contains no entities.</p>';
    }

    $entity_ids = explode(',', $entity_ids);

    // Query to get all entities by their ID
    $query = new WP_Query(array(
        'post_type' => 'entity',
        'post__in' => $entity_ids,
        'posts_per_page' => -1
    ));

    if ($query->have_posts()) {
        $entities = [];
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $rating = get_post_meta($post_id, '_entity_rating', true);
            if (!$rating) {
                $rating = 0;
            }
            $entities[] = ['id' => $post_id, 'rating' => $rating];
        }

        // Sort entities by rating
        usort($entities, function($a, $b) {
            return $b['rating'] <=> $a['rating'];
        });

        $output = '';
        if ($show_title === '1') {
            $output .= '<h3>' . get_the_title($list_id) . '</h3>';
        }
        $output .= '<div class="entities-list">';
        foreach ($entities as $entity) {
            $post_id = $entity['id'];
            $logo = get_post_meta($post_id, '_entity_logo', true);
            $link = get_post_meta($post_id, '_entity_link', true);
            $features = wp_get_post_terms($post_id, 'entity_features', array("fields" => "names"));
            $rating = get_post_meta($post_id, '_entity_rating', true);
            $votes = get_post_meta($post_id, '_entity_votes', true);
            if (!$votes) {
                $votes = 0;
            }
            if (!$rating) {
                $rating = 0;
            }

            $list_rating = get_post_meta($post_id, '_entity_rating_' . $list_id, true);
            $list_votes = get_post_meta($post_id, '_entity_votes_' . $list_id, true);
            if (!$list_rating) {
                $list_rating = 0;
            }
            if (!$list_votes) {
                $list_votes = 0;
            }

            $output .= '<div class="entity">';
            if ($logo) {
                $output .= '<img src="' . esc_url($logo) . '" alt="' . esc_attr(get_the_title($post_id)) . '" class="entity-logo" />';
            }
            $output .= '<div class="entity-title">' . esc_html(get_the_title($post_id)) . '</div>';
            $output .= '<div class="entity-link"><a href="' . esc_url($link) . '">To Try</a></div>';
            $output .= '<div class="entity-description">' . get_the_excerpt() . '</div>';
            if ($features) {
                $output .= '<div class="entity-features">' . implode(', ', $features) . '</div>';
            }

            $output .= '<div class="entity-rating">';
            $output .= '<p>Overall rating: ' . round($rating, 2) . ' (' . $votes . ' votes)</p>';
            $output .= '<p>List rating: ' . round($list_rating, 2) . ' (' . $list_votes . ' votes)</p>';
            $output .= '<span class="rate" data-post-id="' . $post_id . '" data-list-id="' . $list_id . '" data-rating="1"><i class="dashicons dashicons-arrow-up-alt"></i></span> ';
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
add_shortcode('entity_list', 'ce_entity_list_shortcode');
?>
