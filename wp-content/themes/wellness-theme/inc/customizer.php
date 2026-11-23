<?php
/**
 * Wellness Theme Customizer Settings and Controls
 *
 * Implements Customizer settings for: Logo, Colours, Fonts, Hero Text, Footer Text,
 * Social Links, Quick Exit URL, and Medical Disclaimer Text.
 *
 * @package Wellness_Theme
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Customizer Settings, Sections, and Controls.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function wellness_customize_register( $wp_customize ) {

	// =========================================================================
	// PANEL: Wellness Content
	// =========================================================================
	$wp_customize->add_panel( 'wellness_content_panel', array(
		'title'       => esc_html__( 'Wellness Content', 'wellness-theme' ),
		'description' => esc_html__( 'Manage all homepage, footer, and top-bar content directly from WordPress.', 'wellness-theme' ),
		'priority'    => 25,
	) );

	// 1. Top Bar
	$wp_customize->add_section( 'wellness_content_topbar', array(
		'title'    => esc_html__( 'Top Bar', 'wellness-theme' ),
		'panel'    => 'wellness_content_panel',
		'priority' => 10,
	) );
	$wp_customize->add_setting( 'wellness_topbar_text', array(
		'default'           => esc_html__( 'Practical • Evidence-informed • Everyday Wellness', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_topbar_text', array(
		'label'    => esc_html__( 'Top Bar Text Line', 'wellness-theme' ),
		'section'  => 'wellness_content_topbar',
		'type'     => 'text',
	) );

	// Demo Notice Bar
	$wp_customize->add_setting( 'wellness_demo_notice_on', array(
		'default'           => true,
		'sanitize_callback' => 'rest_sanitize_boolean',
	) );
	$wp_customize->add_control( 'wellness_demo_notice_on', array(
		'label'   => esc_html__( 'Show demo notice bar', 'wellness-theme' ),
		'section' => 'wellness_content_topbar',
		'type'    => 'checkbox',
	) );
	$wp_customize->add_setting( 'wellness_demo_notice_text', array(
		'default'           => esc_html__( 'Portfolio demo — content and payments are for demonstration only. No real consultations occur.', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_demo_notice_text', array(
		'label'   => esc_html__( 'Demo Notice Bar Text', 'wellness-theme' ),
		'section' => 'wellness_content_topbar',
		'type'    => 'text',
	) );

	// Quick Exit Toggle
	$wp_customize->add_setting( 'wellness_show_quick_exit', array(
		'default'           => false,
		'sanitize_callback' => 'rest_sanitize_boolean',
	) );
	$wp_customize->add_control( 'wellness_show_quick_exit', array(
		'label'       => esc_html__( 'Show Quick Exit button', 'wellness-theme' ),
		'description' => esc_html__( 'Adds a Quick Exit button to the header on every page.', 'wellness-theme' ),
		'section'     => 'wellness_content_topbar',
		'type'        => 'checkbox',
	) );

	// 2. Home Hero
	$wp_customize->add_section( 'wellness_content_hero', array(
		'title'    => esc_html__( 'Home Hero', 'wellness-theme' ),
		'panel'    => 'wellness_content_panel',
		'priority' => 20,
	) );
	$wp_customize->add_setting( 'wellness_hero_label', array(
		'default'           => esc_html__( 'Wellness guidance, explained', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_hero_label', array(
		'label'    => esc_html__( 'Kicker Label', 'wellness-theme' ),
		'section'  => 'wellness_content_hero',
		'type'     => 'text',
	) );
	$wp_customize->add_setting( 'wellness_hero_title', array(
		'default'           => esc_html__( 'Everyday wellness, clearly explained', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_hero_title', array(
		'label'       => esc_html__( 'Hero Title (H1)', 'wellness-theme' ),
		'description' => esc_html__( 'Leave empty to hide the hero section.', 'wellness-theme' ),
		'section'     => 'wellness_content_hero',
		'type'        => 'text',
	) );
	$wp_customize->add_setting( 'wellness_hero_subtitle', array(
		'default'           => esc_html__( 'Clear, well-sourced, judgement-free articles on nutrition, sleep, fitness and stress.', 'wellness-theme' ),
		'sanitize_callback' => 'wp_kses_post',
	) );
	$wp_customize->add_control( 'wellness_hero_subtitle', array(
		'label'    => esc_html__( 'Subtitle', 'wellness-theme' ),
		'section'  => 'wellness_content_hero',
		'type'     => 'textarea',
	) );
	$wp_customize->add_setting( 'wellness_hero_btn1_text', array(
		'default'           => esc_html__( 'Read our articles', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_hero_btn1_text', array(
		'label'    => esc_html__( 'Primary Button Text', 'wellness-theme' ),
		'section'  => 'wellness_content_hero',
		'type'     => 'text',
	) );
	$wp_customize->add_setting( 'wellness_hero_btn1_url', array(
		'default'           => '/articles/',
		'sanitize_callback' => 'esc_url_raw',
	) );
	$wp_customize->add_control( 'wellness_hero_btn1_url', array(
		'label'    => esc_html__( 'Primary Button URL', 'wellness-theme' ),
		'section'  => 'wellness_content_hero',
		'type'     => 'url',
	) );
	$wp_customize->add_setting( 'wellness_hero_btn2_text', array(
		'default'           => esc_html__( 'Browse topics', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_hero_btn2_text', array(
		'label'    => esc_html__( 'Secondary Button Text', 'wellness-theme' ),
		'section'  => 'wellness_content_hero',
		'type'     => 'text',
	) );

	// Hero image chips
	$wp_customize->add_setting( 'wellness_hero_chips_on', array(
		'default'           => true,
		'sanitize_callback' => 'rest_sanitize_boolean',
	) );
	$wp_customize->add_control( 'wellness_hero_chips_on', array(
		'label'   => esc_html__( 'Show floating info chips on hero image', 'wellness-theme' ),
		'section' => 'wellness_content_hero',
		'type'    => 'checkbox',
	) );

	// Hero image caption
	$wp_customize->add_setting( 'wellness_hero_caption_on', array(
		'default'           => true,
		'sanitize_callback' => 'rest_sanitize_boolean',
	) );
	$wp_customize->add_control( 'wellness_hero_caption_on', array(
		'label'   => esc_html__( 'Show caption below hero image', 'wellness-theme' ),
		'section' => 'wellness_content_hero',
		'type'    => 'checkbox',
	) );
	$wp_customize->add_setting( 'wellness_hero_caption_text', array(
		'default'           => esc_html__( 'Illustrative photo', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_hero_caption_text', array(
		'label'   => esc_html__( 'Hero image caption text', 'wellness-theme' ),
		'section' => 'wellness_content_hero',
		'type'    => 'text',
	) );

	// 3. Trust Points
	$wp_customize->add_section( 'wellness_content_trust', array(
		'title'    => esc_html__( 'Trust Points', 'wellness-theme' ),
		'panel'    => 'wellness_content_panel',
		'priority' => 30,
	) );
	$wp_customize->add_setting( 'wellness_trust_1', array(
		'default'           => esc_html__( 'Evidence-based', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_trust_1', array(
		'label'    => esc_html__( 'Trust Point 1 Label', 'wellness-theme' ),
		'section'  => 'wellness_content_trust',
		'type'     => 'text',
	) );
	$wp_customize->add_setting( 'wellness_trust_2', array(
		'default'           => esc_html__( 'Private and respectful', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_trust_2', array(
		'label'    => esc_html__( 'Trust Point 2 Label', 'wellness-theme' ),
		'section'  => 'wellness_content_trust',
		'type'     => 'text',
	) );
	$wp_customize->add_setting( 'wellness_trust_3', array(
		'default'           => esc_html__( 'Judgement-free', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_trust_3', array(
		'label'    => esc_html__( 'Trust Point 3 Label', 'wellness-theme' ),
		'section'  => 'wellness_content_trust',
		'type'     => 'text',
	) );

	// 4. Topics Section
	$wp_customize->add_section( 'wellness_content_topics', array(
		'title'    => esc_html__( 'Topics Section', 'wellness-theme' ),
		'panel'    => 'wellness_content_panel',
		'priority' => 40,
	) );
	$wp_customize->add_setting( 'wellness_topics_label', array(
		'default'           => esc_html__( 'Explore Health Topics', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_topics_label', array(
		'label'    => esc_html__( 'Topics Kicker Label', 'wellness-theme' ),
		'section'  => 'wellness_content_topics',
		'type'     => 'text',
	) );
	$wp_customize->add_setting( 'wellness_topics_title', array(
		'default'           => esc_html__( 'Browse by topic', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_topics_title', array(
		'label'       => esc_html__( 'Topics Section Title', 'wellness-theme' ),
		'description' => esc_html__( 'Leave empty to hide the topics section.', 'wellness-theme' ),
		'section'     => 'wellness_content_topics',
		'type'        => 'text',
	) );
	$wp_customize->add_setting( 'wellness_topics_subtitle', array(
		'default'           => esc_html__( 'Respectful, scientifically grounded articles on the subjects that matter most.', 'wellness-theme' ),
		'sanitize_callback' => 'wp_kses_post',
	) );
	$wp_customize->add_control( 'wellness_topics_subtitle', array(
		'label'    => esc_html__( 'Topics Section Subtitle', 'wellness-theme' ),
		'section'  => 'wellness_content_topics',
		'type'     => 'textarea',
	) );

	// 5. Latest Articles Section
	$wp_customize->add_section( 'wellness_content_articles', array(
		'title'    => esc_html__( 'Latest Articles Section', 'wellness-theme' ),
		'panel'    => 'wellness_content_panel',
		'priority' => 50,
	) );
	$wp_customize->add_setting( 'wellness_articles_label', array(
		'default'           => esc_html__( 'Clear Health Education', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_articles_label', array(
		'label'    => esc_html__( 'Articles Kicker Label', 'wellness-theme' ),
		'section'  => 'wellness_content_articles',
		'type'     => 'text',
	) );
	$wp_customize->add_setting( 'wellness_articles_title', array(
		'default'           => esc_html__( 'Latest Articles', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_articles_title', array(
		'label'       => esc_html__( 'Articles Section Title', 'wellness-theme' ),
		'description' => esc_html__( 'Leave empty to hide the latest articles section.', 'wellness-theme' ),
		'section'     => 'wellness_content_articles',
		'type'        => 'text',
	) );
	$wp_customize->add_setting( 'wellness_articles_btn_text', array(
		'default'           => esc_html__( 'View All Articles', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_articles_btn_text', array(
		'label'    => esc_html__( 'View All Button Text', 'wellness-theme' ),
		'section'  => 'wellness_content_articles',
		'type'     => 'text',
	) );

	// 6. Myths vs Facts Section
	$wp_customize->add_section( 'wellness_content_myths', array(
		'title'    => esc_html__( 'Myths vs Facts Section', 'wellness-theme' ),
		'panel'    => 'wellness_content_panel',
		'priority' => 60,
	) );
	$wp_customize->add_setting( 'wellness_myths_label', array(
		'default'           => esc_html__( 'MYTHS AND FACTS', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_myths_label', array(
		'label'    => esc_html__( 'Section Kicker Label', 'wellness-theme' ),
		'section'  => 'wellness_content_myths',
		'type'     => 'text',
	) );
	$wp_customize->add_setting( 'wellness_myths_title', array(
		'default'           => esc_html__( 'Common Myths vs Scientific Facts', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_myths_title', array(
		'label'       => esc_html__( 'Section Title', 'wellness-theme' ),
		'description' => esc_html__( 'Leave empty to hide the myths vs facts section.', 'wellness-theme' ),
		'section'     => 'wellness_content_myths',
		'type'        => 'text',
	) );
	$wp_customize->add_setting( 'wellness_myths_subtitle', array(
		'default'           => esc_html__( 'Replacing common misconceptions with evidence-based clarity.', 'wellness-theme' ),
		'sanitize_callback' => 'wp_kses_post',
	) );
	$wp_customize->add_control( 'wellness_myths_subtitle', array(
		'label'    => esc_html__( 'Section Subtitle', 'wellness-theme' ),
		'section'  => 'wellness_content_myths',
		'type'     => 'textarea',
	) );
	$wp_customize->add_setting( 'wellness_myth_1_text', array(
		'default'           => esc_html__( '"You need to exercise for hours every day to stay healthy."', 'wellness-theme' ),
		'sanitize_callback' => 'wp_kses_post',
	) );
	$wp_customize->add_control( 'wellness_myth_1_text', array(
		'label'    => esc_html__( 'Card 1 Myth Text', 'wellness-theme' ),
		'section'  => 'wellness_content_myths',
		'type'     => 'textarea',
	) );
	$wp_customize->add_setting( 'wellness_fact_1_text', array(
		'default'           => esc_html__( 'Even 20–30 minutes of moderate movement most days delivers significant health benefits. Short walks, stretching and low-impact activity all count.', 'wellness-theme' ),
		'sanitize_callback' => 'wp_kses_post',
	) );
	$wp_customize->add_control( 'wellness_fact_1_text', array(
		'label'    => esc_html__( 'Card 1 Fact Text', 'wellness-theme' ),
		'section'  => 'wellness_content_myths',
		'type'     => 'textarea',
	) );
	$wp_customize->add_setting( 'wellness_myth_2_text', array(
		'default'           => esc_html__( '"Eating healthy is expensive and time-consuming."', 'wellness-theme' ),
		'sanitize_callback' => 'wp_kses_post',
	) );
	$wp_customize->add_control( 'wellness_myth_2_text', array(
		'label'    => esc_html__( 'Card 2 Myth Text', 'wellness-theme' ),
		'section'  => 'wellness_content_myths',
		'type'     => 'textarea',
	) );
	$wp_customize->add_setting( 'wellness_fact_2_text', array(
		'default'           => esc_html__( 'Staples like legumes, oats, frozen vegetables and seasonal produce are affordable and nutritious. Meal planning and batch cooking save both time and money.', 'wellness-theme' ),
		'sanitize_callback' => 'wp_kses_post',
	) );
	$wp_customize->add_control( 'wellness_fact_2_text', array(
		'label'    => esc_html__( 'Card 2 Fact Text', 'wellness-theme' ),
		'section'  => 'wellness_content_myths',
		'type'     => 'textarea',
	) );

	// 7. FAQ Section
	$wp_customize->add_section( 'wellness_content_faq', array(
		'title'    => esc_html__( 'FAQ Section', 'wellness-theme' ),
		'panel'    => 'wellness_content_panel',
		'priority' => 70,
	) );
	$wp_customize->add_setting( 'wellness_faq_label', array(
		'default'           => esc_html__( 'Frequently Asked Questions', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_faq_label', array(
		'label'    => esc_html__( 'FAQ Kicker Label', 'wellness-theme' ),
		'section'  => 'wellness_content_faq',
		'type'     => 'text',
	) );
	$wp_customize->add_setting( 'wellness_faq_title', array(
		'default'           => esc_html__( 'Clear Answers to Common Questions', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_faq_title', array(
		'label'       => esc_html__( 'FAQ Section Title', 'wellness-theme' ),
		'description' => esc_html__( 'Leave empty to hide the FAQ section.', 'wellness-theme' ),
		'section'     => 'wellness_content_faq',
		'type'        => 'text',
	) );
	$wp_customize->add_setting( 'wellness_faq_intro', array(
		'default'           => esc_html__( 'Everything you need to know about our content and privacy.', 'wellness-theme' ),
		'sanitize_callback' => 'wp_kses_post',
	) );
	$wp_customize->add_control( 'wellness_faq_intro', array(
		'label'    => esc_html__( 'FAQ Subtitle / Intro', 'wellness-theme' ),
		'section'  => 'wellness_content_faq',
		'type'     => 'textarea',
	) );
	// Q1 / A1
	$wp_customize->add_setting( 'wellness_faq_q1', array(
		'default'           => esc_html__( 'Is this website a substitute for a doctor?', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_faq_q1', array(
		'label'    => esc_html__( 'Question 1', 'wellness-theme' ),
		'section'  => 'wellness_content_faq',
		'type'     => 'text',
	) );
	$wp_customize->add_setting( 'wellness_faq_a1', array(
		'default'           => esc_html__( 'No. All articles, answers, and educational materials provided on this website are for educational and informational purposes only. They do not constitute formal medical diagnosis, individualized treatment, or personal care. If you are experiencing persistent or severe symptoms, please consult a qualified healthcare provider.', 'wellness-theme' ),
		'sanitize_callback' => 'wp_kses_post',
	) );
	$wp_customize->add_control( 'wellness_faq_a1', array(
		'label'    => esc_html__( 'Answer 1', 'wellness-theme' ),
		'section'  => 'wellness_content_faq',
		'type'     => 'textarea',
	) );
	// Q2 / A2
	$wp_customize->add_setting( 'wellness_faq_q2', array(
		'default'           => esc_html__( 'Who writes the content?', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_faq_q2', array(
		'label'    => esc_html__( 'Question 2', 'wellness-theme' ),
		'section'  => 'wellness_content_faq',
		'type'     => 'text',
	) );
	$wp_customize->add_setting( 'wellness_faq_a2', array(
		'default'           => esc_html__( 'Our editorial team, using reputable public-health and medical sources.', 'wellness-theme' ),
		'sanitize_callback' => 'wp_kses_post',
	) );
	$wp_customize->add_control( 'wellness_faq_a2', array(
		'label'    => esc_html__( 'Answer 2', 'wellness-theme' ),
		'section'  => 'wellness_content_faq',
		'type'     => 'textarea',
	) );
	// Q3 / A3
	$wp_customize->add_setting( 'wellness_faq_q3', array(
		'default'           => esc_html__( 'How is my privacy handled?', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_faq_q3', array(
		'label'    => esc_html__( 'Question 3', 'wellness-theme' ),
		'section'  => 'wellness_content_faq',
		'type'     => 'text',
	) );
	$wp_customize->add_setting( 'wellness_faq_a3', array(
		'default'           => esc_html__( 'Your privacy is paramount. We do not require accounts to read our articles, personal identifying data is never harvested, and quick exit tools are available on every page for immediate privacy.', 'wellness-theme' ),
		'sanitize_callback' => 'wp_kses_post',
	) );
	$wp_customize->add_control( 'wellness_faq_a3', array(
		'label'    => esc_html__( 'Answer 3', 'wellness-theme' ),
		'section'  => 'wellness_content_faq',
		'type'     => 'textarea',
	) );

	// 8. Bottom Call to Action
	$wp_customize->add_section( 'wellness_content_cta', array(
		'title'    => esc_html__( 'Bottom Call to Action', 'wellness-theme' ),
		'panel'    => 'wellness_content_panel',
		'priority' => 80,
	) );
	$wp_customize->add_setting( 'wellness_cta_title', array(
		'default'           => esc_html__( 'Explore Our Health & Wellness Articles', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_cta_title', array(
		'label'       => esc_html__( 'Bottom CTA Title', 'wellness-theme' ),
		'description' => esc_html__( 'Leave empty to hide the bottom CTA banner.', 'wellness-theme' ),
		'section'     => 'wellness_content_cta',
		'type'        => 'text',
	) );
	$wp_customize->add_setting( 'wellness_cta_text', array(
		'default'           => esc_html__( 'Thoughtful, evidence-based articles on nutrition, sleep, fitness and stress — all in one place.', 'wellness-theme' ),
		'sanitize_callback' => 'wp_kses_post',
	) );
	$wp_customize->add_control( 'wellness_cta_text', array(
		'label'    => esc_html__( 'Bottom CTA Paragraph', 'wellness-theme' ),
		'section'  => 'wellness_content_cta',
		'type'     => 'textarea',
	) );
	$wp_customize->add_setting( 'wellness_cta_btn_text', array(
		'default'           => esc_html__( 'Read Our Articles', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_cta_btn_text', array(
		'label'    => esc_html__( 'Bottom CTA Button Text', 'wellness-theme' ),
		'section'  => 'wellness_content_cta',
		'type'     => 'text',
	) );

	// 9. Footer Content
	$wp_customize->add_section( 'wellness_content_footer', array(
		'title'    => esc_html__( 'Footer Content', 'wellness-theme' ),
		'panel'    => 'wellness_content_panel',
		'priority' => 90,
	) );
	$wp_customize->add_setting( 'wellness_footer_tagline', array(
		'default'           => esc_html__( 'Your everyday guide to nutrition, sleep, fitness and stress management.', 'wellness-theme' ),
		'sanitize_callback' => 'wp_kses_post',
	) );
	$wp_customize->add_control( 'wellness_footer_tagline', array(
		'label'    => esc_html__( 'Footer Tagline / About Paragraph', 'wellness-theme' ),
		'section'  => 'wellness_content_footer',
		'type'     => 'textarea',
	) );
	$wp_customize->add_setting( 'wellness_footer_disclaimer', array(
		'default'           => esc_html__( 'Medical disclaimer: The content on this website is for general education only. It does not replace professional medical advice, diagnosis or treatment. If you have a medical concern or emergency, contact your local emergency services or a qualified healthcare professional.', 'wellness-theme' ),
		'sanitize_callback' => 'wp_kses_post',
	) );
	$wp_customize->add_control( 'wellness_footer_disclaimer', array(
		'label'    => esc_html__( 'Footer Medical Disclaimer Paragraph', 'wellness-theme' ),
		'section'  => 'wellness_content_footer',
		'type'     => 'textarea',
	) );
	$wp_customize->add_setting( 'wellness_footer_emergency', array(
		'default'           => esc_html__( 'This website does not provide emergency medical services. If you are experiencing acute medical symptoms or distress, please reach out to your local emergency medical services or nearest hospital immediately.', 'wellness-theme' ),
		'sanitize_callback' => 'wp_kses_post',
	) );
	$wp_customize->add_control( 'wellness_footer_emergency', array(
		'label'    => esc_html__( 'Emergency Warning Line', 'wellness-theme' ),
		'section'  => 'wellness_content_footer',
		'type'     => 'textarea',
	) );
	$wp_customize->add_setting( 'wellness_footer_copyright', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_footer_copyright', array(
		'label'       => esc_html__( 'Custom Copyright Notice', 'wellness-theme' ),
		'description' => esc_html__( 'Leave empty to automatically display: © [Current Year] [Site Title]. All rights reserved.', 'wellness-theme' ),
		'section'     => 'wellness_content_footer',
		'type'        => 'text',
	) );

	// =========================================================================
	// SECTION: Header & Navigation
	// =========================================================================
	$wp_customize->add_section( 'wellness_content_header', array(
		'title'    => esc_html__( 'Header & Navigation', 'wellness-theme' ),
		'panel'    => 'wellness_content_panel',
		'priority' => 95,
	) );
	$wp_customize->add_setting( 'wellness_header_search_placeholder', array(
		'default'           => esc_html__( 'Search questions, symptoms, articles...', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_header_search_placeholder', array(
		'label'   => esc_html__( 'Search Field Placeholder', 'wellness-theme' ),
		'section' => 'wellness_content_header',
		'type'    => 'text',
	) );
	$wp_customize->add_setting( 'wellness_mobile_tagline', array(
		'default'           => esc_html__( 'Share this article', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_mobile_tagline', array(
		'label'       => esc_html__( 'Mobile Drawer Tagline', 'wellness-theme' ),
		'description' => esc_html__( 'Shown below the site name in the mobile menu drawer.', 'wellness-theme' ),
		'section'     => 'wellness_content_header',
		'type'        => 'text',
	) );
	$wp_customize->add_setting( 'wellness_quick_exit_label', array(
		'default'           => esc_html__( 'Quick Exit', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_quick_exit_label', array(
		'label'   => esc_html__( 'Quick Exit Button Label', 'wellness-theme' ),
		'section' => 'wellness_content_header',
		'type'    => 'text',
	) );

	// =========================================================================
	// SECTION: Articles Archive
	// =========================================================================
	$wp_customize->add_section( 'wellness_content_archive', array(
		'title'    => esc_html__( 'Articles Archive', 'wellness-theme' ),
		'panel'    => 'wellness_content_panel',
		'priority' => 100,
	) );
	$wp_customize->add_setting( 'wellness_archive_badge_all', array(
		'default'           => esc_html__( 'All Articles', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_archive_badge_all', array(
		'label'   => esc_html__( '"All Articles" Chip Label', 'wellness-theme' ),
		'section' => 'wellness_content_archive',
		'type'    => 'text',
	) );
	$wp_customize->add_setting( 'wellness_archive_empty_title', array(
		'default'           => esc_html__( 'No Articles Found', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_archive_empty_title', array(
		'label'   => esc_html__( 'Empty State Title', 'wellness-theme' ),
		'section' => 'wellness_content_archive',
		'type'    => 'text',
	) );
	$wp_customize->add_setting( 'wellness_archive_empty_text', array(
		'default'           => esc_html__( 'There are currently no articles in this category. Check back soon as our team regularly publishes new educational content.', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_archive_empty_text', array(
		'label'   => esc_html__( 'Empty State Text', 'wellness-theme' ),
		'section' => 'wellness_content_archive',
		'type'    => 'textarea',
	) );
	$wp_customize->add_setting( 'wellness_archive_return_home', array(
		'default'           => esc_html__( 'Return Home', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_archive_return_home', array(
		'label'   => esc_html__( 'Return Home Button Text', 'wellness-theme' ),
		'section' => 'wellness_content_archive',
		'type'    => 'text',
	) );
	$wp_customize->add_setting( 'wellness_archive_fallback_desc', array(
		'default'           => esc_html__( 'Explore health information and compassionate guidance in clear, accessible language.', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_archive_fallback_desc', array(
		'label'       => esc_html__( 'Archive Fallback Description', 'wellness-theme' ),
		'description' => esc_html__( 'Shown when no archive description is set for a category.', 'wellness-theme' ),
		'section'     => 'wellness_content_archive',
		'type'        => 'textarea',
	) );

	// =========================================================================
	// SECTION: Single Article
	// =========================================================================
	$wp_customize->add_section( 'wellness_content_single', array(
		'title'    => esc_html__( 'Single Article', 'wellness-theme' ),
		'panel'    => 'wellness_content_panel',
		'priority' => 110,
	) );
	$wp_customize->add_setting( 'wellness_single_key_points_title', array(
		'default'           => esc_html__( 'Key Takeaways at a Glance', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_single_key_points_title', array(
		'label'   => esc_html__( 'Key Takeaways Box Title', 'wellness-theme' ),
		'section' => 'wellness_content_single',
		'type'    => 'text',
	) );
	$wp_customize->add_setting( 'wellness_single_key_points_text', array(
		'default'           => esc_html__( 'This educational article covers physiological and psychological factors, scientifically proven strategies, and clear signs of when you should consult a licensed doctor.', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_single_key_points_text', array(
		'label'   => esc_html__( 'Key Takeaways Box Text', 'wellness-theme' ),
		'section' => 'wellness_content_single',
		'type'    => 'textarea',
	) );
	$wp_customize->add_setting( 'wellness_single_doctor_box_title', array(
		'default'           => esc_html__( 'When to Consult a Qualified Doctor', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_single_doctor_box_title', array(
		'label'   => esc_html__( '"When to See a Doctor" Box Title', 'wellness-theme' ),
		'section' => 'wellness_content_single',
		'type'    => 'text',
	) );
	$wp_customize->add_setting( 'wellness_single_doctor_box_text', array(
		'default'           => esc_html__( 'If symptoms persist for over 3 months, cause emotional or relationship distress, or are accompanied by severe pain or sudden changes, an individual private consultation is strongly recommended.', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_single_doctor_box_text', array(
		'label'   => esc_html__( '"When to See a Doctor" Box Text', 'wellness-theme' ),
		'section' => 'wellness_content_single',
		'type'    => 'textarea',
	) );
	$wp_customize->add_setting( 'wellness_single_author_label', array(
		'default'           => esc_html__( 'Consultant Author', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_single_author_label', array(
		'label'   => esc_html__( 'Author Box Label', 'wellness-theme' ),
		'section' => 'wellness_content_single',
		'type'    => 'text',
	) );
	$wp_customize->add_setting( 'wellness_single_author_bio', array(
		'default'           => esc_html__( 'Educational & wellness guidance based on evidence-based sources.', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_single_author_bio', array(
		'label'   => esc_html__( 'Author Box Byline', 'wellness-theme' ),
		'section' => 'wellness_content_single',
		'type'    => 'text',
	) );
	$wp_customize->add_setting( 'wellness_single_share_label', array(
		'default'           => esc_html__( 'Share this article:', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_single_share_label', array(
		'label'   => esc_html__( 'Share Strip Label', 'wellness-theme' ),
		'section' => 'wellness_content_single',
		'type'    => 'text',
	) );
	$wp_customize->add_setting( 'wellness_single_cta_articles_title', array(
		'default'           => esc_html__( 'Want to Learn More?', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_single_cta_articles_title', array(
		'label'       => esc_html__( 'Article CTA Title (articles-only mode)', 'wellness-theme' ),
		'description' => esc_html__( 'Shown when consultations are off.', 'wellness-theme' ),
		'section'     => 'wellness_content_single',
		'type'        => 'text',
	) );

	// =========================================================================
	// SECTION: Search & 404
	// =========================================================================
	$wp_customize->add_section( 'wellness_content_search404', array(
		'title'    => esc_html__( 'Search & 404', 'wellness-theme' ),
		'panel'    => 'wellness_content_panel',
		'priority' => 120,
	) );
	$wp_customize->add_setting( 'wellness_search_placeholder', array(
		'default'           => esc_html__( 'Try another search term...', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_search_placeholder', array(
		'label'   => esc_html__( 'Search Results: Input Placeholder', 'wellness-theme' ),
		'section' => 'wellness_content_search404',
		'type'    => 'text',
	) );
	$wp_customize->add_setting( 'wellness_no_results_title', array(
		'default'           => esc_html__( 'No Matches Found', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_no_results_title', array(
		'label'   => esc_html__( 'No Results Title', 'wellness-theme' ),
		'section' => 'wellness_content_search404',
		'type'    => 'text',
	) );
	$wp_customize->add_setting( 'wellness_404_title', array(
		'default'           => esc_html__( '404 - Page Not Found', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_404_title', array(
		'label'   => esc_html__( '404 Heading', 'wellness-theme' ),
		'section' => 'wellness_content_search404',
		'type'    => 'text',
	) );
	$wp_customize->add_setting( 'wellness_404_text', array(
		'default'           => esc_html__( 'The page or article you are looking for might have been moved, renamed, or is temporarily unavailable.', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_404_text', array(
		'label'   => esc_html__( '404 Body Text', 'wellness-theme' ),
		'section' => 'wellness_content_search404',
		'type'    => 'textarea',
	) );
	$wp_customize->add_setting( 'wellness_404_search_placeholder', array(
		'default'           => esc_html__( 'Search wellness topics or articles...', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_404_search_placeholder', array(
		'label'   => esc_html__( '404 Search Placeholder', 'wellness-theme' ),
		'section' => 'wellness_content_search404',
		'type'    => 'text',
	) );
	$wp_customize->add_setting( 'wellness_404_btn_home', array(
		'default'           => esc_html__( 'Return Home', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_404_btn_home', array(
		'label'   => esc_html__( '404 "Return Home" Button Text', 'wellness-theme' ),
		'section' => 'wellness_content_search404',
		'type'    => 'text',
	) );
	$wp_customize->add_setting( 'wellness_404_btn_articles', array(
		'default'           => esc_html__( 'Browse Articles', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_404_btn_articles', array(
		'label'   => esc_html__( '404 "Browse Articles" Button Text', 'wellness-theme' ),
		'section' => 'wellness_content_search404',
		'type'    => 'text',
	) );
	$wp_customize->add_setting( 'wellness_404_privacy_note', array(
		'default'           => esc_html__( 'Need immediate privacy?', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_404_privacy_note', array(
		'label'   => esc_html__( '404 Quick-Exit Note', 'wellness-theme' ),
		'section' => 'wellness_content_search404',
		'type'    => 'text',
	) );

	// =========================================================================
	// SECTION: Footer Column Headings
	// =========================================================================
	$wp_customize->add_section( 'wellness_content_footer_cols', array(
		'title'    => esc_html__( 'Footer Column Headings', 'wellness-theme' ),
		'panel'    => 'wellness_content_panel',
		'priority' => 90,
	) );
	$wp_customize->add_setting( 'wellness_footer_col2_heading_learn', array(
		'default'           => esc_html__( 'Explore & Learn', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_footer_col2_heading_learn', array(
		'label'       => esc_html__( 'Col 2 Heading (Articles-only mode)', 'wellness-theme' ),
		'description' => esc_html__( 'Used when consultations are disabled.', 'wellness-theme' ),
		'section'     => 'wellness_content_footer_cols',
		'type'        => 'text',
	) );
	$wp_customize->add_setting( 'wellness_footer_col3_heading', array(
		'default'           => esc_html__( 'Health Categories', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_footer_col3_heading', array(
		'label'   => esc_html__( 'Col 3 Heading (Categories)', 'wellness-theme' ),
		'section' => 'wellness_content_footer_cols',
		'type'    => 'text',
	) );
	$wp_customize->add_setting( 'wellness_footer_col4_heading', array(
		'default'           => esc_html__( 'Legal & Compliance', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_footer_col4_heading', array(
		'label'   => esc_html__( 'Col 4 Heading (Legal)', 'wellness-theme' ),
		'section' => 'wellness_content_footer_cols',
		'type'    => 'text',
	) );
	$wp_customize->add_setting( 'wellness_footer_col1_subtext', array(
		'default'           => esc_html__( 'Private, non-judgmental education for adults worldwide.', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_footer_col1_subtext', array(
		'label'   => esc_html__( 'Col 1 Subtext (Under About text)', 'wellness-theme' ),
		'section' => 'wellness_content_footer_cols',
		'type'    => 'text',
	) );
	$wp_customize->add_setting( 'wellness_footer_backtotop', array(
		'default'           => esc_html__( 'Back to Top', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_footer_backtotop', array(
		'label'   => esc_html__( 'Back to Top Button Label', 'wellness-theme' ),
		'section' => 'wellness_content_footer_cols',
		'type'    => 'text',
	) );

	// 1. Theme Options Panel (Styling & Configuration)
	$wp_customize->add_panel( 'wellness_theme_panel', array(
		'title'       => esc_html__( 'Wellness Theme Options', 'wellness-theme' ),
		'description' => esc_html__( 'Configure theme colors, typography, hero section, footer, and safety settings.', 'wellness-theme' ),
		'priority'    => 30,
	) );

	// -------------------------------------------------------------------------
	// SECTION 1: Brand Colours
	// -------------------------------------------------------------------------
	$wp_customize->add_section( 'wellness_colors_section', array(
		'title'    => esc_html__( 'Theme Colours', 'wellness-theme' ),
		'panel'    => 'wellness_theme_panel',
		'priority' => 10,
	) );

	// Primary Color
	$wp_customize->add_setting( 'wellness_primary_color', array(
		'default'           => '#0f3b4c',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'refresh',
	) );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'wellness_primary_color', array(
		'label'    => esc_html__( 'Primary Slate Navy', 'wellness-theme' ),
		'section'  => 'wellness_colors_section',
		'settings' => 'wellness_primary_color',
	) ) );

	// Secondary Color
	$wp_customize->add_setting( 'wellness_secondary_color', array(
		'default'           => '#287b85',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'refresh',
	) );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'wellness_secondary_color', array(
		'label'    => esc_html__( 'Secondary Medical Teal', 'wellness-theme' ),
		'section'  => 'wellness_colors_section',
		'settings' => 'wellness_secondary_color',
	) ) );

	// Accent Color
	$wp_customize->add_setting( 'wellness_accent_color', array(
		'default'           => '#e07a5f',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'refresh',
	) );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'wellness_accent_color', array(
		'label'    => esc_html__( 'Accent Terracotta', 'wellness-theme' ),
		'section'  => 'wellness_colors_section',
		'settings' => 'wellness_accent_color',
	) ) );

	// Quick Exit Urgent Red Color
	$wp_customize->add_setting( 'wellness_quick_exit_color', array(
		'default'           => '#dc2626',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'refresh',
	) );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'wellness_quick_exit_color', array(
		'label'    => esc_html__( 'Quick Exit Alert Colour', 'wellness-theme' ),
		'section'  => 'wellness_colors_section',
		'settings' => 'wellness_quick_exit_color',
	) ) );

	$wp_customize->add_setting( 'wellness_default_color_mode', array(
		'default'           => 'dark',
		'sanitize_callback' => 'wellness_sanitize_select',
	) );
	$wp_customize->add_control( 'wellness_default_color_mode', array(
		'label'    => esc_html__( 'Default colour mode', 'wellness-theme' ),
		'section'  => 'wellness_colors_section',
		'type'     => 'select',
		'choices'  => array(
			'dark'  => esc_html__( 'Dark', 'wellness-theme' ),
			'light' => esc_html__( 'Light', 'wellness-theme' ),
		),
	) );

	// -------------------------------------------------------------------------
	// SECTION 2: Typography & Fonts
	// -------------------------------------------------------------------------
	$wp_customize->add_section( 'wellness_fonts_section', array(
		'title'    => esc_html__( 'Typography & Fonts', 'wellness-theme' ),
		'panel'    => 'wellness_theme_panel',
		'priority' => 20,
	) );

	// Body Font Family
	$wp_customize->add_setting( 'wellness_body_font', array(
		'default'           => 'sans-system',
		'sanitize_callback' => 'wellness_sanitize_select',
	) );
	$wp_customize->add_control( 'wellness_body_font', array(
		'label'    => esc_html__( 'Body Font Family', 'wellness-theme' ),
		'section'  => 'wellness_fonts_section',
		'type'     => 'select',
		'choices'  => array(
			'sans-system' => esc_html__( 'System Sans-Serif (Fastest & Native)', 'wellness-theme' ),
			'inter'       => esc_html__( 'Inter / Modern Clean Sans', 'wellness-theme' ),
			'serif'       => esc_html__( 'Editorial Serif (Georgia / Cambria)', 'wellness-theme' ),
		),
	) );

	// Heading Font Style
	$wp_customize->add_setting( 'wellness_heading_font', array(
		'default'           => 'sans',
		'sanitize_callback' => 'wellness_sanitize_select',
	) );
	$wp_customize->add_control( 'wellness_heading_font', array(
		'label'    => esc_html__( 'Headings Font Style', 'wellness-theme' ),
		'section'  => 'wellness_fonts_section',
		'type'     => 'select',
		'choices'  => array(
			'sans'  => esc_html__( 'Modern Sans-Serif', 'wellness-theme' ),
			'serif' => esc_html__( 'Classic Editorial Serif', 'wellness-theme' ),
		),
	) );

	// -------------------------------------------------------------------------
	// SECTION 3: Hero Section Text & CTAs
	// -------------------------------------------------------------------------
	$wp_customize->add_section( 'wellness_hero_section', array(
		'title'    => esc_html__( 'Homepage Hero Section', 'wellness-theme' ),
		'panel'    => 'wellness_theme_panel',
		'priority' => 30,
	) );

	// Hero Badge Text
	$wp_customize->add_setting( 'wellness_hero_badge', array(
		'default'           => esc_html__( 'No account required • Worldwide time zone support • Demo consultants', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_hero_badge', array(
		'label'    => esc_html__( 'Hero Top Badge Text', 'wellness-theme' ),
		'section'  => 'wellness_hero_section',
		'type'     => 'text',
	) );

	// Hero Main Headline
	$wp_customize->add_setting( 'wellness_hero_headline', array(
		'default'           => esc_html__( 'Your everyday guide to nutrition, sleep, fitness and stress management.', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_hero_headline', array(
		'label'    => esc_html__( 'Hero Headline', 'wellness-theme' ),
		'section'  => 'wellness_hero_section',
		'type'     => 'text',
	) );

	// Hero Subtext
	$wp_customize->add_setting( 'wellness_hero_subtext', array(
		'default'           => esc_html__( 'Private consultations and clear health education, with appointments shown in your time zone.', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_textarea_field',
	) );
	$wp_customize->add_control( 'wellness_hero_subtext', array(
		'label'    => esc_html__( 'Hero Subtext / Lead', 'wellness-theme' ),
		'section'  => 'wellness_hero_section',
		'type'     => 'textarea',
	) );

	// Hero CTA 1 Text & Link
	$wp_customize->add_setting( 'wellness_hero_cta1_text', array(
		'default'           => esc_html__( 'Book a Private Consultation', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_hero_cta1_text', array(
		'label'    => esc_html__( 'Primary CTA Button Label', 'wellness-theme' ),
		'section'  => 'wellness_hero_section',
		'type'     => 'text',
	) );

	$wp_customize->add_setting( 'wellness_hero_cta1_url', array(
		'default'           => '/book-consultation/',
		'sanitize_callback' => 'esc_url_raw',
	) );
	$wp_customize->add_control( 'wellness_hero_cta1_url', array(
		'label'    => esc_html__( 'Primary CTA URL', 'wellness-theme' ),
		'section'  => 'wellness_hero_section',
		'type'     => 'url',
	) );

	// Hero CTA 2 Text & Link
	$wp_customize->add_setting( 'wellness_hero_cta2_text', array(
		'default'           => esc_html__( 'Ask Anonymously (Free)', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_hero_cta2_text', array(
		'label'    => esc_html__( 'Secondary CTA Button Label', 'wellness-theme' ),
		'section'  => 'wellness_hero_section',
		'type'     => 'text',
	) );

	$wp_customize->add_setting( 'wellness_hero_cta2_url', array(
		'default'           => '/ask-a-question/',
		'sanitize_callback' => 'esc_url_raw',
	) );
	$wp_customize->add_control( 'wellness_hero_cta2_url', array(
		'label'    => esc_html__( 'Secondary CTA URL', 'wellness-theme' ),
		'section'  => 'wellness_hero_section',
		'type'     => 'url',
	) );

	// Home Hero Image (Optional)
	$wp_customize->add_setting( 'wellness_home_hero_image', array(
		'default'           => 0,
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'wellness_home_hero_image', array(
		'label'       => esc_html__( 'Home Hero Image (Optional)', 'wellness-theme' ),
		'description' => esc_html__( 'Optional custom hero image. If not set, a responsive calm abstract SVG will display automatically.', 'wellness-theme' ),
		'section'     => 'wellness_hero_section',
		'mime_type'   => 'image',
	) ) );

	// -------------------------------------------------------------------------
	// SECTION 4: Quick Exit & Safety
	// -------------------------------------------------------------------------
	$wp_customize->add_section( 'wellness_quick_exit_section', array(
		'title'    => esc_html__( 'Safety & Quick Exit', 'wellness-theme' ),
		'panel'    => 'wellness_theme_panel',
		'priority' => 40,
	) );

	$wp_customize->add_setting( 'wellness_quick_exit_url', array(
		'default'           => 'https://www.google.com',
		'sanitize_callback' => 'esc_url_raw',
	) );
	$wp_customize->add_control( 'wellness_quick_exit_url', array(
		'label'       => esc_html__( 'Quick Exit Neutral URL', 'wellness-theme' ),
		'description' => esc_html__( 'The destination URL when a patient clicks the Quick Exit safety button or presses 3x Esc.', 'wellness-theme' ),
		'section'     => 'wellness_quick_exit_section',
		'type'        => 'url',
	) );

	// -------------------------------------------------------------------------
	// SECTION 5: Footer & Medical Disclaimer Text
	// -------------------------------------------------------------------------
	$wp_customize->add_section( 'wellness_footer_section', array(
		'title'    => esc_html__( 'Footer & Medical Disclaimer', 'wellness-theme' ),
		'panel'    => 'wellness_theme_panel',
		'priority' => 50,
	) );

	// Footer About Paragraph
	$wp_customize->add_setting( 'wellness_footer_about', array(
		'default'           => esc_html__( 'Your everyday guide to nutrition, sleep, fitness and stress management.', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_textarea_field',
	) );
	$wp_customize->add_control( 'wellness_footer_about', array(
		'label'    => esc_html__( 'Footer About Description', 'wellness-theme' ),
		'section'  => 'wellness_footer_section',
		'type'     => 'textarea',
	) );

	// Medical Disclaimer Text (FR-4 & Section 10)
	$wp_customize->add_setting( 'wellness_disclaimer_text', array(
		'default'           => esc_html__( 'Medical Disclaimer: The educational content and consultation services provided on this platform are designed for informational and counseling purposes only. They do not constitute formal medical diagnosis, physical examinations, or prescription writing unless explicitly licensed within the patient\'s jurisdiction. If you are experiencing a medical or mental health emergency, please contact your local emergency medical services or healthcare provider immediately.', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_textarea_field',
	) );
	$wp_customize->add_control( 'wellness_disclaimer_text', array(
		'label'       => esc_html__( 'Mandatory Medical Disclaimer Text', 'wellness-theme' ),
		'description' => esc_html__( 'Displayed in the footer and beneath medical articles per compliance requirements.', 'wellness-theme' ),
		'section'     => 'wellness_footer_section',
		'type'        => 'textarea',
	) );

	// Footer Custom Copyright
	$wp_customize->add_setting( 'wellness_footer_copyright', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_footer_copyright', array(
		'label'       => esc_html__( 'Custom Copyright Notice', 'wellness-theme' ),
		'description' => esc_html__( 'Leave empty to automatically display: © [Current Year] [Site Title]. All rights reserved.', 'wellness-theme' ),
		'section'     => 'wellness_footer_section',
		'type'        => 'text',
	) );

	// -------------------------------------------------------------------------
	// SECTION 6: Social & Support Links
	// -------------------------------------------------------------------------
	$wp_customize->add_section( 'wellness_social_section', array(
		'title'    => esc_html__( 'Support & Contact Links', 'wellness-theme' ),
		'panel'    => 'wellness_theme_panel',
		'priority' => 60,
	) );

	$wp_customize->add_setting( 'wellness_contact_whatsapp', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_contact_whatsapp', array(
		'label'       => esc_html__( 'Support WhatsApp Number (International format)', 'wellness-theme' ),
		'description' => esc_html__( 'e.g. +1234567890', 'wellness-theme' ),
		'section'     => 'wellness_social_section',
		'type'        => 'text',
	) );

	$wp_customize->add_setting( 'wellness_contact_email', array(
		'default'           => get_option( 'admin_email' ),
		'sanitize_callback' => 'sanitize_email',
	) );
	$wp_customize->add_control( 'wellness_contact_email', array(
		'label'    => esc_html__( 'Support Email Address', 'wellness-theme' ),
		'section'  => 'wellness_social_section',
		'type'     => 'email',
	) );
}
add_action( 'customize_register', 'wellness_customize_register' );

/**
 * Sanitize Select callback.
 */
function wellness_sanitize_select( $input, $setting ) {
	$input   = sanitize_key( $input );
	$choices = $setting->manager->get_control( $setting->id )->choices;
	return ( array_key_exists( $input, $choices ) ? $input : $setting->default );
}

/**
 * Output Dynamic CSS in Head based on Customizer choices.
 */
function wellness_customizer_css() {
	$primary_color    = get_theme_mod( 'wellness_primary_color', '#0f3b4c' );
	$secondary_color  = get_theme_mod( 'wellness_secondary_color', '#287b85' );
	$accent_color     = get_theme_mod( 'wellness_accent_color', '#e07a5f' );
	$quick_exit_color = get_theme_mod( 'wellness_quick_exit_color', '#dc2626' );
	$heading_font     = get_theme_mod( 'wellness_heading_font', 'sans' );

	$heading_family = ( 'serif' === $heading_font ) ? 'var(--font-serif)' : 'var(--font-sans)';

	echo "<style id='wellness-customizer-css'>\n";
	echo ":root[data-theme=\"light\"], :root[data-theme=\"dark\"] {\n";
	if ( $primary_color !== '#0f3b4c' ) {
		echo "  --heading: " . esc_attr( $primary_color ) . ";\n";
	}
	if ( $secondary_color !== '#287b85' ) {
		echo "  --primary: " . esc_attr( $secondary_color ) . ";\n";
	}
	if ( $accent_color !== '#e07a5f' ) {
		echo "  --accent: " . esc_attr( $accent_color ) . ";\n";
	}
	if ( $quick_exit_color !== '#dc2626' ) {
		echo "  --danger-button: " . esc_attr( $quick_exit_color ) . ";\n";
	}
	if ( 'serif' === $heading_font ) {
		echo "  --font-heading: " . $heading_family . ";\n";
	}
	echo "}\n";
	if ( 'serif' === $heading_font ) {
		echo "h1, h2, h3, h4, h5, h6 { font-family: " . $heading_family . "; }\n";
	}
	if ( $quick_exit_color !== '#dc2626' ) {
		echo ":root[data-theme=\"light\"], :root[data-theme=\"dark\"] { --color-quick-exit: var(--danger-button); --color-quick-exit-hover: var(--danger-button); }\n";
	}
	echo "</style>\n";
}
add_action( 'wp_head', 'wellness_customizer_css', 100 );
