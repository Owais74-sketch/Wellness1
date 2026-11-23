<?php
if ('cli' !== php_sapi_name()) die('CLI only.');
chdir(dirname(__DIR__));
require_once dirname(__DIR__) . '/wp-load.php';

global $wpdb;
$pattern = 'sexual|intimacy|libido|erectile|vaginismus|pelvic|orgasm|discreet';
$rows = $wpdb->get_results(
    "SELECT ID, post_title, post_type, post_status FROM {$wpdb->posts}
     WHERE (post_content REGEXP '{$pattern}' OR post_title REGEXP '{$pattern}')
     AND post_status NOT IN ('auto-draft','revision','trash')"
);
if (empty($rows)) {
    echo "No hits in DB post content.\n";
} else {
    foreach ($rows as $r) {
        echo "ID:{$r->ID} [{$r->post_type}/{$r->post_status}] {$r->post_title}\n";
    }
}

// Also check options (customizer mods, etc.)
$opt_rows = $wpdb->get_results(
    "SELECT option_name, SUBSTR(option_value,1,200) as val FROM {$wpdb->options}
     WHERE option_value REGEXP '{$pattern}'
     AND option_name NOT IN ('_site_transient_update_core','_site_transient_update_plugins','_site_transient_update_themes')
     LIMIT 20"
);
if (empty($opt_rows)) {
    echo "No hits in DB options.\n";
} else {
    echo "\nOptions with adult words:\n";
    foreach ($opt_rows as $r) {
        echo "  {$r->option_name}: {$r->val}\n";
    }
}
