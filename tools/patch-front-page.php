<?php
if ('cli' !== php_sapi_name()) die('CLI only.');

$file = 'd:/laragon/www/wellness/wp-content/themes/wellness-theme/front-page.php';
$content = file_get_contents($file);

// 1. Consultation how-it-works badge
$content = str_replace(
    "esc_html_e( 'Simple & Confidential', 'wellness-theme' )",
    "esc_html_e( 'Simple & Clear', 'wellness-theme' )",
    $content
);

// 2. How it works section heading
$content = str_replace(
    "esc_html_e( 'How Your Private Consultation Works', 'wellness-theme' )",
    "esc_html_e( 'How a Wellness Consultation Works', 'wellness-theme' )",
    $content
);

// 3. How it works subtitle
$content = str_replace(
    "esc_html_e( 'From booking to private session in 4 easy, safe steps.', 'wellness-theme' )",
    "esc_html_e( 'From booking to your online session in 4 easy steps.', 'wellness-theme' )",
    $content
);

// 4. Articles section placeholder badge "Intimacy Health"
$content = str_replace(
    "esc_html_e( 'Intimacy Health', 'wellness-theme' )",
    "esc_html_e( 'Wellness', 'wellness-theme' )",
    $content
);

// 5. Articles placeholder titles
$content = str_replace(
    "esc_html_e( 'Understanding Performance Anxiety: Causes & Practical Tools', 'wellness-theme' )",
    "esc_html_e( 'Understanding Sleep Cycles: What Happens While You Rest', 'wellness-theme' )",
    $content
);
$content = str_replace(
    "esc_html_e( 'Communication in the Bedroom: Overcoming Embarrassment', 'wellness-theme' )",
    "esc_html_e( 'Building a Balanced Plate: Simple Nutrition Rules That Work', 'wellness-theme' )",
    $content
);
$content = str_replace(
    "esc_html_e( 'Hormonal Fluctuations and Libido: An Evidence-Based Guide', 'wellness-theme' )",
    "esc_html_e( 'Starting a Walking Routine: A Simple Evidence-Based Approach', 'wellness-theme' )",
    $content
);

// 6. Myths defaults
$content = str_replace(
    "__( '\"Sexual problems are always purely physical and permanent.\"', 'wellness-theme' )",
    "__( '\"You need to exercise for hours every day to stay healthy.\"', 'wellness-theme' )",
    $content
);
$content = str_replace(
    "__( 'Intimacy concerns frequently involve psychological factors like stress, performance pressure, or fatigue, and open communication and practical support can provide clarity.', 'wellness-theme' )",
    "__( 'Even 20–30 minutes of moderate movement most days delivers significant health benefits. Short walks, stretching and low-impact activity all count.', 'wellness-theme' )",
    $content
);
$content = str_replace(
    "__( '\"Desire should always happen naturally and spontaneously in long-term relationships.\"', 'wellness-theme' )",
    "__( '\"Eating healthy is expensive and time-consuming.\"', 'wellness-theme' )",
    $content
);
$content = str_replace(
    "__( 'Responsive desire is completely normal and common. Intimacy can be nurtured through relaxation, context, and emotional security.', 'wellness-theme' )",
    "__( 'Staples like legumes, oats, frozen vegetables and seasonal produce are affordable and nutritious. Meal planning and batch cooking save both time and money.', 'wellness-theme' )",
    $content
);

// 7. Consultant card: badge
$content = str_replace(
    "esc_html_e( 'Board-Registered Practitioner', 'wellness-theme' )",
    "esc_html_e( 'Certified Wellness Consultant (Demo)', 'wellness-theme' )",
    $content
);

// 8. Consultant card: name
$content = str_replace(
    "esc_html_e( 'Dr. A. Khan, MD', 'wellness-theme' )",
    "esc_html_e( 'Demo Consultant', 'wellness-theme' )",
    $content
);

// 9. Consultant card: credentials line
$content = str_replace(
    "esc_html_e( 'Clinical Sexual Wellness Specialist & Telehealth Counselor (Reg #WC-78401)', 'wellness-theme' )",
    "esc_html_e( 'Wellness Coach — Nutrition, Sleep & Fitness (Demo Profile)', 'wellness-theme' )",
    $content
);

// 10. Consultant card: bio
$content = str_replace(
    "esc_html_e( 'Over 12 years of specialized clinical experience providing compassionate, confidential care for individuals and couples worldwide. Consultations focus on evidence-based counseling and education.', 'wellness-theme' )",
    "esc_html_e( 'Experienced wellness coach specialising in nutrition, sleep and everyday fitness. This is a demo profile showing the consultant preview card.', 'wellness-theme' )",
    $content
);

// 11. Consult FAQ: "Is my identity and consultation 100% confidential?"
$content = str_replace(
    "esc_html_e( 'Is my identity and consultation 100% confidential?', 'wellness-theme' )",
    "esc_html_e( 'Is my information kept private?', 'wellness-theme' )",
    $content
);

// 12. Consult FAQ: confidential answer
$content = str_replace(
    "esc_html_e( 'Yes. You are completely welcome to use an alias or nickname. No account creation is required on this website. Your contact details and medical messages are encrypted, never shared with third parties, and direct URLs to payment proofs are locked down.', 'wellness-theme' )",
    "esc_html_e( 'Yes. You may use an alias. No account is required. Your contact details are encrypted and never shared with third parties.', 'wellness-theme' )",
    $content
);

// 13. FAQ: "Who will I be speaking with?" answer
$content = str_replace(
    "esc_html_e( 'You will consult 1-on-1 with a qualified, board-registered doctor and clinical counselor specializing in intimate health and relationship wellness. All sessions take place in an encrypted, respectful, and strictly non-judgmental environment.', 'wellness-theme' )",
    "esc_html_e( 'You will work 1-on-1 with a certified wellness coach. This is a portfolio demo — no real consultations or payments occur.', 'wellness-theme' )",
    $content
);

// 14. CTA defaults: consult-on branch
$content = str_replace(
    "__( 'Ready to ask a private question?', 'wellness-theme' )",
    "__( 'Ready to try the demo booking?', 'wellness-theme' )",
    $content
);
$content = str_replace(
    "__( 'No judgment, no embarrassing waiting rooms. Just thoughtful answers in a secure, confidential setting.', 'wellness-theme' )",
    "__( 'Experience the full booking flow with demo consultants and test payments.', 'wellness-theme' )",
    $content
);
$content = str_replace(
    "__( 'Book Your Private Consultation', 'wellness-theme' )",
    "__( 'Try the Demo Booking', 'wellness-theme' )",
    $content
);

// 15. CTA defaults: consult-off branch
$content = str_replace(
    "__( 'Thoughtful, evidence-based guidance on intimacy, wellness, and relationships in a secure, confidential setting.', 'wellness-theme' )",
    "__( 'Thoughtful, evidence-based articles on nutrition, sleep, fitness and stress — all in one place.', 'wellness-theme' )",
    $content
);

// 16. myths_subtitle default
$content = str_replace(
    "__( 'Replacing shame and misinformation with evidence-based clarity.', 'wellness-theme' )",
    "__( 'Replacing common misconceptions with evidence-based clarity.', 'wellness-theme' )",
    $content
);

// 17. cat_icons: update old slugs to new ones
$content = str_replace(
    "'intimacy'       => 'heart',",
    "'nutrition-eating' => 'heart',",
    $content
);
$content = str_replace(
    "'mens-health'    => 'user',",
    "'fitness-movement' => 'activity',",
    $content
);
$content = str_replace(
    "'womens-health'  => 'smile',",
    "'stress-mind'    => 'smile',",
    $content
);
$content = str_replace(
    "'lifestyle'      => 'activity',",
    "'sleep-recovery' => 'moon',",
    $content
);

file_put_contents($file, $content);
echo "Done — front-page.php patched.\n";
