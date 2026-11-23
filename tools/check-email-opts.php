<?php
if ('cli' !== php_sapi_name()) die('CLI only.');
chdir(dirname(__DIR__));
require_once dirname(__DIR__) . '/wp-load.php';

global $wpdb;

// 1. Find email template options
$rows = $wpdb->get_results(
    "SELECT option_name, option_value FROM {$wpdb->options}
     WHERE option_name LIKE 'wc_email%'
     ORDER BY option_name"
);
foreach ($rows as $row) {
    echo "OPTION: " . $row->option_name . "\n";
    $val = maybe_unserialize($row->option_value);
    if (is_array($val)) {
        foreach ($val as $k => $v) {
            if (is_string($v)) echo "  [$k]: " . substr($v, 0, 120) . "\n";
        }
    } else {
        echo "  VALUE: " . substr((string)$val, 0, 120) . "\n";
    }
    echo "\n";
}
