<?php
if ('cli' !== php_sapi_name()) die('CLI only.');
chdir(dirname(__DIR__));
require_once dirname(__DIR__) . '/wp-load.php';

global $wpdb;

// 1. Update nav menu item that still says "Intimacy & Relationships" pointing to old slug
$menu_item = get_post(101);
if ($menu_item && $menu_item->post_title === 'Intimacy &amp; Relationships') {
    wp_update_post(['ID' => 101, 'post_title' => 'Nutrition &amp; Eating']);
    // Also update the _menu_item_url meta if it points to /category/intimacy/
    $url_meta = get_post_meta(101, '_menu_item_url', true);
    if (strpos($url_meta, 'intimacy') !== false) {
        update_post_meta(101, '_menu_item_url', str_replace('intimacy', 'nutrition-eating', $url_meta));
    }
    // Update object slug
    $obj_id = get_post_meta(101, '_menu_item_object_id', true);
    echo "Updated nav menu item 101. Object ID: $obj_id\n";
} else {
    // Search more broadly
    $items = $wpdb->get_results("SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type='nav_menu_item' AND post_status='publish' AND post_title REGEXP 'intimacy|sexual|mens-health|womens-health|lifestyle'");
    foreach ($items as $item) {
        $new_title = str_ireplace(
            ['Intimacy & Relationships', 'Intimacy &amp; Relationships', 'Sexual', 'Men\'s Health', 'Women\'s Health', 'Lifestyle & Wellness'],
            ['Nutrition &amp; Eating', 'Nutrition &amp; Eating', 'Wellness', 'Fitness &amp; Movement', 'Stress &amp; Mind', 'Sleep &amp; Recovery'],
            $item->post_title
        );
        if ($new_title !== $item->post_title) {
            wp_update_post(['ID' => $item->ID, 'post_title' => $new_title]);
            echo "Updated nav menu item {$item->ID}: {$item->post_title} -> {$new_title}\n";
        }
        // Fix URL meta
        $url = get_post_meta($item->ID, '_menu_item_url', true);
        if ($url && preg_match('/intimacy|mens-health|womens-health|lifestyle/', $url)) {
            $new_url = str_replace(
                ['intimacy', 'mens-health', 'womens-health', 'lifestyle'],
                ['nutrition-eating', 'fitness-movement', 'stress-mind', 'sleep-recovery'],
                $url
            );
            update_post_meta($item->ID, '_menu_item_url', $new_url);
            echo "  URL meta updated: $url -> $new_url\n";
        }
        // Fix object_id meta if pointing to a category
        $obj_id = (int) get_post_meta($item->ID, '_menu_item_object_id', true);
        if ($obj_id) {
            $term = get_term($obj_id, 'category');
            if ($term && !is_wp_error($term)) {
                $new_slug = str_replace(
                    ['intimacy', 'mens-health', 'womens-health', 'lifestyle'],
                    ['nutrition-eating', 'fitness-movement', 'stress-mind', 'sleep-recovery'],
                    $term->slug
                );
                if ($new_slug !== $term->slug) {
                    // Find the new category by slug
                    $new_term = get_term_by('slug', $new_slug, 'category');
                    if ($new_term) {
                        update_post_meta($item->ID, '_menu_item_object_id', $new_term->term_id);
                        echo "  Object ID updated to {$new_term->term_id} ($new_slug)\n";
                    }
                }
            }
        }
    }
}

// 2. Delete old revisions with adult content
$rev_ids = [54, 55, 56, 87];
foreach ($rev_ids as $id) {
    $post = get_post($id);
    if ($post && $post->post_type === 'revision') {
        wp_delete_post($id, true);
        echo "Deleted revision $id\n";
    } else {
        echo "Skip $id (type: " . ($post ? $post->post_type : 'not found') . ")\n";
    }
}

// 3. Update the attachment title
$att = get_post(53);
if ($att && $att->post_type === 'attachment') {
    wp_update_post(['ID' => 53, 'post_title' => 'Wellness and Healthy Living']);
    echo "Updated attachment 53 title.\n";
}

echo "\nDone.\n";
