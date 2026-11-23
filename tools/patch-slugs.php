<?php
if ('cli' !== php_sapi_name()) die('CLI only.');
$file = 'd:/laragon/www/wellness/wp-content/themes/wellness-theme/template-parts/content-card.php';
$content = file_get_contents($file);
$content = str_replace("'mens-health' === \$cat_slug", "'fitness-movement' === \$cat_slug", $content);
$content = str_replace("'womens-health' === \$cat_slug", "'stress-mind' === \$cat_slug", $content);
file_put_contents($file, $content);
echo "Patched content-card.php\n";
