<?php
if ('cli' !== php_sapi_name()) die('CLI only.');
chdir(dirname(__DIR__));
require_once dirname(__DIR__) . '/wp-load.php';
$pages = get_pages(['post_status' => ['publish','private'], 'post_type' => 'page']);
foreach ($pages as $p) {
    echo $p->post_status . ' | ' . $p->post_name . "\n";
}
// Also check switches
echo "\nwc_consultations_enabled: " . get_option('wc_consultations_enabled') . "\n";
echo "wc_questions_enabled: " . get_option('wc_questions_enabled') . "\n";
