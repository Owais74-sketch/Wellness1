<?php
if ('cli' !== php_sapi_name()) die('CLI only.');

$theme = 'd:/laragon/www/wellness/wp-content/themes/wellness-theme';

// footer.php — replace old category links
$file = $theme . '/footer.php';
$content = file_get_contents($file);
$old = "home_url( '/category/intimacy/' ) ); ?>\"><?php esc_html_e( 'Intimacy & Relationships', 'wellness-theme' ); ?></a></li>
\t\t\t\t\t\t\t\t<li><a href=\"<?php echo esc_url( home_url( '/category/mens-health/' ) ); ?>\"><?php esc_html_e( \"Men's Health\", 'wellness-theme' ); ?></a></li>
\t\t\t\t\t\t\t\t<li><a href=\"<?php echo esc_url( home_url( '/category/womens-health/' ) ); ?>\"><?php esc_html_e( \"Women's Health\", 'wellness-theme' ); ?></a></li>
\t\t\t\t\t\t\t\t<li><a href=\"<?php echo esc_url( home_url( '/category/lifestyle/' ) ); ?>\"><?php esc_html_e( 'Lifestyle & Wellness', 'wellness-theme' ); ?></a></li>";
$new = "home_url( '/category/nutrition-eating/' ) ); ?>\"><?php esc_html_e( 'Nutrition &amp; Eating', 'wellness-theme' ); ?></a></li>
\t\t\t\t\t\t\t\t<li><a href=\"<?php echo esc_url( home_url( '/category/fitness-movement/' ) ); ?>\"><?php esc_html_e( 'Fitness &amp; Movement', 'wellness-theme' ); ?></a></li>
\t\t\t\t\t\t\t\t<li><a href=\"<?php echo esc_url( home_url( '/category/sleep-recovery/' ) ); ?>\"><?php esc_html_e( 'Sleep &amp; Recovery', 'wellness-theme' ); ?></a></li>
\t\t\t\t\t\t\t\t<li><a href=\"<?php echo esc_url( home_url( '/category/stress-mind/' ) ); ?>\"><?php esc_html_e( 'Stress &amp; Mind', 'wellness-theme' ); ?></a></li>";
$new_content = str_replace($old, $new, $content);
if ($new_content !== $content) {
    file_put_contents($file, $new_content);
    echo "Patched: footer.php\n";
} else {
    // Try alternate: use regex
    $new_content = preg_replace(
        "#<li><a href=\"<\?php echo esc_url\( home_url\( '/category/intimacy/' \) \); \?>\">.*?</li>\s*" .
        "<li><a href=\"<\?php echo esc_url\( home_url\( '/category/mens-health/' \) \); \?>\">.*?</li>\s*" .
        "<li><a href=\"<\?php echo esc_url\( home_url\( '/category/womens-health/' \) \); \?>\">.*?</li>\s*" .
        "<li><a href=\"<\?php echo esc_url\( home_url\( '/category/lifestyle/' \) \); \?>\">.*?</li>#s",
        '<li><a href="<?php echo esc_url( home_url( \'/category/nutrition-eating/\' ) ); ?>"><?php esc_html_e( \'Nutrition &amp; Eating\', \'wellness-theme\' ); ?></a></li>' . "\n\t\t\t\t\t\t\t\t" .
        '<li><a href="<?php echo esc_url( home_url( \'/category/fitness-movement/\' ) ); ?>"><?php esc_html_e( \'Fitness &amp; Movement\', \'wellness-theme\' ); ?></a></li>' . "\n\t\t\t\t\t\t\t\t" .
        '<li><a href="<?php echo esc_url( home_url( \'/category/sleep-recovery/\' ) ); ?>"><?php esc_html_e( \'Sleep &amp; Recovery\', \'wellness-theme\' ); ?></a></li>' . "\n\t\t\t\t\t\t\t\t" .
        '<li><a href="<?php echo esc_url( home_url( \'/category/stress-mind/\' ) ); ?>"><?php esc_html_e( \'Stress &amp; Mind\', \'wellness-theme\' ); ?></a></li>',
        $content
    );
    if ($new_content !== $content) {
        file_put_contents($file, $new_content);
        echo "Patched (regex): footer.php\n";
    } else {
        echo "Could not patch footer.php — manual fix needed at lines 78-81\n";
    }
}

// functions.php — same replacement, two occurrences
$file = $theme . '/functions.php';
$content = file_get_contents($file);
$new_content = preg_replace(
    "#<li><a href=\"<\?php echo esc_url\( home_url\( '/category/intimacy/' \) \); \?>\">.*?</li>#",
    '<li><a href="<?php echo esc_url( home_url( \'/category/nutrition-eating/\' ) ); ?>"><?php esc_html_e( \'Nutrition &amp; Eating\', \'wellness-theme\' ); ?></a></li>',
    $content
);
// Also fix mens-health and womens-health in functions.php
$new_content = preg_replace(
    "#<li><a href=\"<\?php echo esc_url\( home_url\( '/category/mens-health/' \) \); \?>\">.*?</li>#",
    '<li><a href="<?php echo esc_url( home_url( \'/category/fitness-movement/\' ) ); ?>"><?php esc_html_e( \'Fitness &amp; Movement\', \'wellness-theme\' ); ?></a></li>',
    $new_content
);
$new_content = preg_replace(
    "#<li><a href=\"<\?php echo esc_url\( home_url\( '/category/womens-health/' \) \); \?>\">.*?</li>#",
    '<li><a href="<?php echo esc_url( home_url( \'/category/stress-mind/\' ) ); ?>"><?php esc_html_e( \'Stress &amp; Mind\', \'wellness-theme\' ); ?></a></li>',
    $new_content
);
$new_content = preg_replace(
    "#<li><a href=\"<\?php echo esc_url\( home_url\( '/category/lifestyle/' \) \); \?>\">.*?</li>#",
    '<li><a href="<?php echo esc_url( home_url( \'/category/sleep-recovery/\' ) ); ?>"><?php esc_html_e( \'Sleep &amp; Recovery\', \'wellness-theme\' ); ?></a></li>',
    $new_content
);
if ($new_content !== $content) {
    file_put_contents($file, $new_content);
    echo "Patched: functions.php\n";
} else {
    echo "No change: functions.php\n";
}
