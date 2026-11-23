<?php
/**
 * Patch all adult/sexual wording across theme files.
 * CLI only, run once.
 */
if ('cli' !== php_sapi_name()) die('CLI only.');

$theme = 'd:/laragon/www/wellness/wp-content/themes/wellness-theme';

// Map: filename => array of [search, replace]
$patches = [

    // ===== inc/customizer.php =====
    'inc/customizer.php' => [
        [
            "'Private • Evidence-Based • Sexual Wellness Education'",
            "'Practical • Evidence-informed • Everyday Wellness'",
        ],
        [
            "'Sexual wellness, explained'",
            "'Wellness guidance, explained'",
        ],
        [
            "'Honest, private answers about sexual wellness'",
            "'Everyday wellness, clearly explained'",
        ],
        [
            "'Clear, well-sourced, judgement-free articles on relationships, intimacy and sexual health.'",
            "'Clear, well-sourced, judgement-free articles on nutrition, sleep, fitness and stress.'",
        ],
        [
            "'\"Sexual problems are always purely physical and permanent.\"'",
            "'\"You need to exercise for hours every day to stay healthy.\"'",
        ],
        // Myths fact_1 default in customizer
        [
            "'Intimacy concerns frequently involve psychological factors like stress, performance pressure, or fatigue, and open communication and practical support can provide clarity.'",
            "'Even 20–30 minutes of moderate movement most days delivers significant health benefits. Short walks, stretching and low-impact activity all count.'",
        ],
        // Myths myth_2 default in customizer
        [
            "'\"Desire should always happen naturally and spontaneously in long-term relationships.\"'",
            "'\"Eating healthy is expensive and time-consuming.\"'",
        ],
        // Myths fact_2 default in customizer
        [
            "'Responsive desire is completely normal and common. Intimacy can be nurtured through relaxation, context, and emotional security.'",
            "'Staples like legumes, oats, frozen vegetables and seasonal produce are affordable and nutritious. Meal planning and batch cooking save both time and money.'",
        ],
        // CTA text default
        [
            "'Thoughtful, evidence-based guidance on intimacy, wellness, and relationships in a secure, confidential setting.'",
            "'Thoughtful, evidence-based articles on nutrition, sleep, fitness and stress — all in one place.'",
        ],
        // Footer tagline default
        [
            "'Honest, private information about intimacy, relationships, and sexual wellness in a respectful, non-judgmental space.'",
            "'Your everyday guide to nutrition, sleep, fitness and stress management.'",
        ],
        // "Confidential & Discreet" (share label default)
        [
            "'Confidential & Discreet'",
            "'Share this article'",
        ],
        // Single share label default
        [
            "'Discreet Sharing:'",
            "'Share this article:'",
        ],
        // Consultation info line default
        [
            "'Private and confidential • No account required • Worldwide time zone matching'",
            "'No account required • Worldwide time zone support • Demo consultants'",
        ],
        // Myths subtitle default
        [
            "'Replacing shame and misinformation with evidence-based clarity.'",
            "'Replacing common misconceptions with evidence-based clarity.'",
        ],
    ],

    // ===== header.php =====
    'header.php' => [
        [
            "'Confidential & Discreet Care • Worldwide Telehealth Consultations'",
            "'Demo Consultation Booking • Worldwide Time Zone Support'",
        ],
        [
            ": __( 'Private • Evidence-Based • Sexual Wellness Education'",
            ": __( 'Practical • Evidence-informed • Everyday Wellness'",
        ],
        // Mobile drawer tagline fallback (may be in header.php)
        [
            "'Private Health Information'",
            "'Everyday Wellness Demo'",
        ],
        [
            "'Confidential &amp; Discreet'",
            "'Everyday Wellness'",
        ],
    ],

    // ===== inc/template-tags.php =====
    'inc/template-tags.php' => [
        [
            "'Private Health Information'",
            "'Everyday Wellness'",
        ],
        [
            "'Honest, private information about intimacy, relationships, and sexual wellness in a respectful, non-judgmental space.'",
            "'Your everyday guide to nutrition, sleep, fitness and stress management.'",
        ],
    ],

    // ===== inc/meta-boxes.php =====
    'inc/meta-boxes.php' => [
        [
            "'is an independent educational website about sexual wellness, relationships and intimacy. Our aim is to give adults clear, respectful and well-sourced information in a private, judgement-free space.'",
            "'is a portfolio demo of an everyday wellness education site. Articles cover nutrition, sleep, fitness and stress — all backed by reputable public-health sources.'",
        ],
    ],

    // ===== page-about.php =====
    'page-about.php' => [
        [
            "esc_html_e( 'MBBS, Certified Counselor in Psychosexual Wellness & Intimacy Guidance'",
            "esc_html_e( 'Certified Wellness Coach — Nutrition, Sleep & Fitness (Demo)'",
        ],
        [
            "'Primary clinical licensure in Pakistan; Worldwide educational tele-counseling & psychosexual health coaching.'",
            "'Wellness coaching (demo profile). Worldwide online sessions available.'",
        ],
        [
            "'Over 9+ years providing confidential reproductive wellness counseling, relationship guidance, and clinical health education.'",
            "'Over 9 years providing evidence-based wellness coaching on nutrition, sleep and lifestyle habits.'",
        ],
        [
            "'Book a private, discreet consultation in your own time zone, or ask a free anonymous question today.'",
            "'Book a demo consultation or ask a free anonymous wellness question today.'",
        ],
    ],

    // ===== page-book-consultation.php =====
    'page-book-consultation.php' => [
        [
            "'Private and confidential • Worldwide time zone matching'",
            "'Demo booking • No real payments • Worldwide time zone support'",
        ],
        [
            "'Request a private consultation from the comfort of your home. Use any name or alias you prefer. Your information is handled privately and confidentially.'",
            "'This is a portfolio demo. No real consultations or payments occur. Use any name or alias you prefer.'",
        ],
    ],

    // ===== page-contact.php =====
    'page-contact.php' => [
        [
            "esc_html_e( 'Confidential & Private Support'",
            "esc_html_e( 'Get in Touch'",
        ],
        [
            "'Fill out this confidential form for questions, feedback, or verification queries.'",
            "'Fill out this form for questions or feedback about the demo.'",
        ],
        [
            "'🔒 Strictly confidential. Your details are never shared.'",
            "'🔒 Your details are never shared with third parties.'",
        ],
    ],

    // ===== page-myths-vs-facts.php =====
    'page-myths-vs-facts.php' => [
        [
            "esc_html_e( 'Sexual Wellness: Myths vs Facts'",
            "esc_html_e( 'Wellness: Myths vs Facts'",
        ],
        [
            "'Misinformation can create unnecessary anxiety, guilt, and strain. Explore clear explanations of common sexual wellness topics.'",
            "'Misinformation can create unnecessary worry. Explore clear, evidence-based explanations of common wellness topics.'",
        ],
    ],

    // ===== single.php =====
    'single.php' => [
        [
            "'Discreet Sharing:'",
            "'Share this article:'",
        ],
        [
            "'Link copied discreetly to clipboard'",
            "'Link copied to clipboard'",
        ],
    ],

    // ===== style.css =====
    'style.css' => [
        [
            'Lightweight, mobile-first, discreet and accessible English-language WordPress theme designed for global sexual wellness education and private doctor consultations. Includes emergency quick-exit and WCAG-compliant design.',
            'Lightweight, mobile-first, accessible English-language WordPress theme for everyday wellness education. WCAG-compliant design.',
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
