<?php
/**
 * Final wording cleanup patch — round 2.
 * CLI only.
 */
if ('cli' !== php_sapi_name()) die('CLI only.');

$theme = 'd:/laragon/www/wellness/wp-content/themes/wellness-theme';

$patches = [

    'inc/customizer.php' => [
        [
            "'Honest, private answers about intimacy and sexual health.'",
            "'Your everyday guide to nutrition, sleep, fitness and stress management.'",
        ],
    ],

    'template-parts/content-card.php' => [
        [
            "if ( 'intimacy' === \$cat_slug )",
            "if ( 'nutrition-eating' === \$cat_slug )",
        ],
    ],

    'footer.php' => [
        [
            "home_url( '/category/intimacy/' ); ?>\"><?php esc_html_e( 'Intimacy & Relationships'",
            "home_url( '/category/nutrition-eating/' ); ?>\"><?php esc_html_e( 'Nutrition & Eating'",
        ],
    ],

    'functions.php' => [
        // Two occurrences — str_replace handles both
        [
            "home_url( '/category/intimacy/' ); ?>\"><?php esc_html_e( 'Intimacy & Relationships'",
            "home_url( '/category/nutrition-eating/' ); ?>\"><?php esc_html_e( 'Nutrition & Eating'",
        ],
    ],

    'header.php' => [
        [
            "'Confidential & Discreet'",
            "'Everyday Wellness'",
        ],
    ],

    'index.php' => [
        [
            "esc_html_e( 'Private • Discreet • Worldwide time zone matching'",
            "esc_html_e( 'Practical • Evidence-informed • Everyday Wellness'",
        ],
        [
            "esc_html_e( 'Honest, Private Answers About Intimacy and Sexual Health'",
            "esc_html_e( 'Everyday Wellness, Clearly Explained'",
        ],
    ],

    'page-about.php' => [
        [
            "esc_html_e( 'Intimacy, sexual health, and emotional connection are fundamental aspects of human well-being, yet they remain shadowed by cultural taboos, widespread misinformation, and unnecessary anxiety. Our clinic was founded to dismantle these barriers by providing individuals and couples worldwide with accessible, scientifically verified guidance in an atmosphere of dignity, empathy, and absolute confidentiality.'",
            "esc_html_e( 'Everyday wellness — nutrition, sleep, fitness and stress management — forms the foundation of a healthy, fulfilling life, yet it is often overshadowed by misinformation and conflicting advice. This site was founded to cut through the noise by providing clear, evidence-informed guidance in an accessible and supportive environment.'",
        ],
        [
            "esc_html_e( 'We believe that healthcare guidance must be compassionate, stigma-free, and respectful of personal values. By combining professional clinical counseling with rigorously reviewed educational resources, we empower you to understand your body, communicate openly with your partner, and make informed decisions about your intimacy and reproductive wellness without fear of judgment.'",
            "esc_html_e( 'We believe wellness guidance should be practical, stigma-free, and respectful of individual needs. By combining evidence-based content with accessible expert consultations, we help you understand your body, build healthy habits, and make informed decisions about your wellbeing.'",
        ],
    ],

    'page-ask-a-question.php' => [
        [
            "'Have a private question about intimacy, physical symptoms, or emotional connection? Ask without sharing your identity.'",
            "'Have a question about nutrition, sleep, fitness or stress? Ask anonymously — no account required.'",
        ],
    ],

    'page-myths-vs-facts.php' => [
        [
            "esc_html_e( '\"Intimacy concerns always indicate a loss of love or physical attraction.\"'",
            "esc_html_e( '\"You need to drink 8 glasses of water every day, no exceptions.\"'",
        ],
        [
            "esc_html_e( 'Intimacy responses are heavily dictated by physical exhaustion, neurochemical balance, medications, and general life stress. Deep love can coexist with temporary intimacy challenges.'",
            "esc_html_e( 'Hydration needs vary by body size, activity level and climate. While staying well-hydrated is important, rigid \"8 glasses\" rules are not evidence-based for everyone.'",
        ],
        [
            "esc_html_e( '\"Discomfort or pain during intimacy is something women just have to tolerate.\"'",
            "esc_html_e( '\"Stretching before exercise prevents all muscle soreness and injury.\"'",
        ],
        [
            "esc_html_e( 'Pain during intimacy is never normal and should never be endured. Common causes like pelvic floor hypertonicity, hormonal shifts, and vaginismus are highly treatable.'",
            "esc_html_e( 'Static stretching before exercise has limited evidence for injury prevention. Dynamic warm-ups are more effective. Some muscle soreness after new exercise is normal and not a sign of injury.'",
        ],
        [
            "'Explore our evidence-based articles covering intimacy, emotional wellness, physical health, and relationships.'",
            "'Explore our evidence-based articles covering nutrition, sleep, fitness, stress management and everyday health.'",
        ],
    ],

    'category.php' => [
        // Just a comment — update it
        [
            " * Implements SRS v2.0 Section 8.3 Category Pages (Intimacy, Myths vs Facts, Men's Health, Women's Health, Lifestyle).",
            " * Implements SRS v2.0 Section 8.3 Category Pages (Nutrition, Sleep, Fitness, Stress, Myths vs Facts).",
        ],
    ],
];

$changed = [];
foreach ($patches as $rel => $reps) {
    $file = $theme . '/' . $rel;
    if (!file_exists($file)) {
        echo "SKIP (not found): $rel\n";
        continue;
    }
    $content = file_get_contents($file);
    $original = $content;
    foreach ($reps as [$search, $replace]) {
        $content = str_replace($search, $replace, $content);
    }
    if ($content !== $original) {
        file_put_contents($file, $content);
        $changed[] = $rel;
        echo "Patched: $rel\n";
    } else {
        echo "No change: $rel\n";
    }
}
echo "\nDone. Changed " . count($changed) . " files.\n";
