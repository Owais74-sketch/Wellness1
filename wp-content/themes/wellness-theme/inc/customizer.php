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

	// 1. Theme Options Panel
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
		'default'           => '#2a838e',
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
		'default'           => esc_html__( '100% Confidential • No Account Required • Certified Specialists', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_hero_badge', array(
		'label'    => esc_html__( 'Hero Top Badge Text', 'wellness-theme' ),
		'section'  => 'wellness_hero_section',
		'type'     => 'text',
	) );

	// Hero Main Headline
	$wp_customize->add_setting( 'wellness_hero_headline', array(
		'default'           => esc_html__( 'Honest, private answers about intimacy and sexual health.', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'wellness_hero_headline', array(
		'label'    => esc_html__( 'Hero Headline', 'wellness-theme' ),
		'section'  => 'wellness_hero_section',
		'type'     => 'text',
	) );

	// Hero Subtext
	$wp_customize->add_setting( 'wellness_hero_subtext', array(
		'default'           => esc_html__( 'Compassionate counseling, evidence-based guidance, and paid private consultations with qualified doctors and counselors. Completely confidential, in your own time zone.', 'wellness-theme' ),
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
		'default'           => esc_html__( 'Honest, private answers about intimacy, relationships, and sexual wellness. We offer confidential counseling and education with certified doctors and counselors in a safe, non-judgmental space.', 'wellness-theme' ),
		'sanitize_callback' => 'sanitize_textarea_field',
	) );
	$wp_customize->add_control( 'wellness_footer_about', array(
		'label'    => esc_html__( 'Footer About Description', 'wellness-theme' ),
		'section'  => 'wellness_footer_section',
		'type'     => 'textarea',
	) );

	// Medical Disclaimer Text (FR-4 & Section 10)
	$wp_customize->add_setting( 'wellness_disclaimer_text', array(
		'default'           => esc_html__( 'Medical Disclaimer: The educational content and consultation services provided on this platform are designed for informational and counseling purposes only. They do not constitute formal medical diagnosis, physical examinations, or prescription writing unless explicitly licensed within the patient\'s jurisdiction. If you are experiencing a medical or mental health emergency, please contact your local emergency services (e.g., 911, 112, 999) or crisis helpline immediately.', 'wellness-theme' ),
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
	$secondary_color  = get_theme_mod( 'wellness_secondary_color', '#2a838e' );
	$accent_color     = get_theme_mod( 'wellness_accent_color', '#e07a5f' );
	$quick_exit_color = get_theme_mod( 'wellness_quick_exit_color', '#dc2626' );
	$heading_font     = get_theme_mod( 'wellness_heading_font', 'sans' );

	$heading_family = ( 'serif' === $heading_font ) ? 'var(--font-serif)' : 'var(--font-sans)';

	echo "<style id='wellness-customizer-css'>\n";
	echo ":root {\n";
	if ( $primary_color !== '#0f3b4c' ) {
		echo "  --color-primary: " . esc_attr( $primary_color ) . ";\n";
	}
	if ( $secondary_color !== '#2a838e' ) {
		echo "  --color-secondary: " . esc_attr( $secondary_color ) . ";\n";
	}
	if ( $accent_color !== '#e07a5f' ) {
		echo "  --color-accent: " . esc_attr( $accent_color ) . ";\n";
	}
	if ( $quick_exit_color !== '#dc2626' ) {
		echo "  --color-quick-exit: " . esc_attr( $quick_exit_color ) . ";\n";
	}
	if ( 'serif' === $heading_font ) {
		echo "  --font-heading: " . $heading_family . ";\n";
		echo "  h1, h2, h3, h4, h5, h6 { font-family: var(--font-heading); }\n";
	}
	echo "}\n";
	echo "</style>\n";
}
add_action( 'wp_head', 'wellness_customizer_css', 100 );
