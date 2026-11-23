<?php
if ('cli' !== php_sapi_name()) die('CLI only.');
chdir(dirname(__DIR__));
require_once dirname(__DIR__) . '/wp-load.php';

$templates = get_option('wc_email_templates', []);
$bad_words = ['sexual', 'intimate', 'intimacy', 'libido', 'doctor', 'physician', 'clinical', 'counseling', 'counselling', 'discreet', 'confidential'];
$changed = false;

foreach ($templates as $key => &$tpl) {
    foreach (['subject', 'body'] as $field) {
        if (!isset($tpl[$field])) continue;
        $orig = $tpl[$field];
        // Replace problematic words
        $tpl[$field] = str_ireplace(
            ['doctor availability', 'doctor', 'physician', 'clinical counselor', 'clinical', 'counseling', 'counselling',
             'your private consultation', 'private consultation', 'discreet', 'confidential care', 'confidential'],
            ['consultant availability', 'wellness consultant (demo)', 'wellness coach', 'wellness coach', 'wellness-focused',
             'support', 'support',
             'your wellness consultation (demo)', 'wellness consultation (demo)', 'private', 'professional care', 'private'],
            $tpl[$field]
        );
        if ($tpl[$field] !== $orig) {
            echo "CHANGED [$key][$field]\n";
            $changed = true;
        }
    }
    // Add demo footer to body if not already there
    if (isset($tpl['body']) && strpos($tpl['body'], 'DEMO') === false && strpos($tpl['body'], 'demo') === false) {
        $tpl['body'] .= "\n\n---\nPortfolio demo — no real consultations or payments occur.";
        $changed = true;
        echo "Added demo footer to [$key]\n";
    }
}
unset($tpl);

if ($changed) {
    update_option('wc_email_templates', $templates);
    echo "Email templates updated.\n";
} else {
    echo "No changes needed.\n";
}
