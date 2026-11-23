<?php
if ('cli' !== php_sapi_name()) die('CLI only.');
chdir(dirname(__DIR__));
require_once dirname(__DIR__) . '/wp-load.php';

global $wpdb;

// Get the raw serialised value
$raw = $wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name = 'wc_email_templates'");
echo "RAW (first 2000 chars):\n";
echo substr($raw, 0, 2000) . "\n";
