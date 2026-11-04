<?php
/**
 * Options and Settings Data Helper
 *
 * Implements settings persistence, defaults, and export/import helpers.
 *
 * @package Wellness_Consult
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class WC_Options {

	/**
	 * Initialize default options on activation (No invented business data).
	 */
	public static function init_defaults() {
		// Consultation types start EMPTY on fresh install (Rule 6)
		if ( false === get_option( 'wc_consultation_types' ) ) {
			add_option( 'wc_consultation_types', array() );
		}

		// Payment settings default (test mode enabled by default for safety, no invented keys)
		if ( false === get_option( 'wc_payment_settings' ) ) {
			add_option( 'wc_payment_settings', array(
				'test_mode'              => '1',
				'stripe_enabled'         => '0',
				'stripe_test_pk'         => '',
				'stripe_test_sk'         => '',
				'stripe_live_pk'         => '',
				'stripe_live_sk'         => '',
				'stripe_webhook_secret'  => '',
				'paypal_enabled'         => '0',
				'paypal_client_id'       => '',
				'paypal_secret'          => '',
				'paypal_webhook_id'      => '',
			) );
		}

		// Manual transfer methods start EMPTY (Rule 6: No default account numbers)
		if ( false === get_option( 'wc_manual_methods' ) ) {
			add_option( 'wc_manual_methods', array() );
		}

		// Availability settings
		if ( false === get_option( 'wc_availability_settings' ) ) {
			add_option( 'wc_availability_settings', array(
				'base_timezone' => 'UTC',
				'weekly_hours'  => array(
					'mon' => array( 'active' => '1', 'start' => '09:00', 'end' => '17:00' ),
					'tue' => array( 'active' => '1', 'start' => '09:00', 'end' => '17:00' ),
					'wed' => array( 'active' => '1', 'start' => '09:00', 'end' => '17:00' ),
					'thu' => array( 'active' => '1', 'start' => '09:00', 'end' => '17:00' ),
					'fri' => array( 'active' => '1', 'start' => '09:00', 'end' => '17:00' ),
					'sat' => array( 'active' => '0', 'start' => '10:00', 'end' => '14:00' ),
					'sun' => array( 'active' => '0', 'start' => '10:00', 'end' => '14:00' ),
				),
				'breaks'        => array(),
				'blocked_dates' => array(),
			) );
		}

		// Countries configuration (Rule 6: No default countries invented)
		if ( false === get_option( 'wc_countries_settings' ) ) {
			add_option( 'wc_countries_settings', array(
				'mode'         => 'all_except_blocked', // 'all_except_blocked' or 'only_allowed'
				'allowed_list' => array(),
				'blocked_list' => array(),
			) );
		}

		// General Settings
		if ( false === get_option( 'wc_general_settings' ) ) {
			add_option( 'wc_general_settings', array(
				'min_reschedule_hours' => 6,
				'max_reschedules'      => 1,
				'reminder_24h'         => '1',
				'reminder_1h'          => '1',
				'proof_retention_days' => 90,
				'rate_limit_per_hour'  => 3,
				'delete_on_uninstall'  => '0', // Default OFF per Rule 2
			) );
		}

		// Email Templates (Default structures with placeholders)
		if ( false === get_option( 'wc_email_templates' ) ) {
			add_option( 'wc_email_templates', self::get_default_email_templates() );
		}

		// Platforms settings (CR-1: No defaults are active until admin enables them)
		if ( false === get_option( 'wc_platforms' ) ) {
			add_option( 'wc_platforms', self::get_default_platforms() );
		}
	}

	/**
	 * Default Platforms list per consultation mode (CR-1).
	 *
	 * Note: All default platforms start with is_active = 0.
	 *
	 * @return array
	 */
	public static function get_default_platforms() {
		return array(
			'chat' => array(
				'whatsapp_chat' => array(
					'id'           => 'whatsapp_chat',
					'name'         => 'WhatsApp',
					'instructions' => 'Encrypted direct messaging consultation via WhatsApp.',
					'is_active'    => 0,
				),
				'telegram_chat' => array(
					'id'           => 'telegram_chat',
					'name'         => 'Telegram',
					'instructions' => 'Confidential direct chat via Telegram username.',
					'is_active'    => 0,
				),
			),
			'voice' => array(
				'whatsapp_voice' => array(
					'id'           => 'whatsapp_voice',
					'name'         => 'WhatsApp Audio Call',
					'instructions' => 'Encrypted audio voice call via WhatsApp.',
					'is_active'    => 0,
				),
				'phone_call' => array(
					'id'           => 'phone_call',
					'name'         => 'Direct Phone Call',
					'instructions' => 'Direct audio telephone call to patient phone number.',
					'is_active'    => 0,
				),
			),
			'video' => array(
				'google_meet' => array(
					'id'           => 'google_meet',
					'name'         => 'Google Meet',
					'instructions' => 'Private web video room (camera optional).',
					'is_active'    => 0,
				),
				'zoom' => array(
					'id'           => 'zoom',
					'name'         => 'Zoom',
					'instructions' => 'Encrypted video meeting room with waiting room enabled.',
					'is_active'    => 0,
				),
				'whatsapp_video' => array(
					'id'           => 'whatsapp_video',
					'name'         => 'WhatsApp Video',
					'instructions' => 'Direct video consultation via WhatsApp.',
					'is_active'    => 0,
				),
			),
		);
	}

	/**
	 * Get human label for a consultation mode.
	 *
	 * @param string $mode Mode key ('chat', 'voice', 'video').
	 * @return string
	 */
	public static function get_mode_label( $mode ) {
		switch ( $mode ) {
			case 'chat':
				return __( 'Chat', 'wellness-consult' );
			case 'voice':
				return __( 'Voice Call', 'wellness-consult' );
			case 'video':
				return __( 'Video Call', 'wellness-consult' );
			default:
				return ucfirst( (string) $mode );
		}
	}

	/**
	 * Get emoji icon for a consultation mode.
	 *
	 * @param string $mode Mode key.
	 * @return string
	 */
	public static function get_mode_icon( $mode ) {
		switch ( $mode ) {
			case 'chat':
				return '💬';
			case 'voice':
				return '📞';
			case 'video':
				return '📹';
			default:
				return '🩺';
		}
	}

	/**
	 * Get short description for a consultation mode (CR-1 Rule 3).
	 *
	 * @param string $mode Mode key.
	 * @return string
	 */
	public static function get_mode_short_desc( $mode ) {
		switch ( $mode ) {
			case 'chat':
				return __( 'Text consultation, no voice or camera', 'wellness-consult' );
			case 'voice':
				return __( 'Audio only, camera stays off', 'wellness-consult' );
			case 'video':
				return __( 'Face to face by video', 'wellness-consult' );
			default:
				return '';
		}
	}

	/**
	 * Get platforms available for a given mode.
	 *
	 * @param string $mode Mode key ('chat', 'voice', 'video').
	 * @param bool   $active_only Whether to return only active platforms.
	 * @return array
	 */
	public static function get_platforms_for_mode( $mode, $active_only = true ) {
		$all = get_option( 'wc_platforms', self::get_default_platforms() );
		$list = $all[ $mode ] ?? array();
		if ( ! $active_only ) {
			return $list;
		}

		$filtered = array();
		foreach ( $list as $key => $platform ) {
			if ( ! empty( $platform['is_active'] ) ) {
				$filtered[ $key ] = $platform;
			}
		}
		return $filtered;
	}

	/**
	 * Default Email Templates for all Section 4.9 & Phase 8 Events.
	 *
	 * @return array
	 */
	public static function get_default_email_templates() {
		return array(
			'request_submitted' => array(
				'label'   => esc_html__( 'Request Submitted (Awaiting Payment / Verification)', 'wellness-consult' ),
				'subject' => 'Consultation Request Received - Ref: {reference_id}',
				'body'    => "Hello {name},\n\nWe have received your private consultation request (Reference ID: {reference_id}).\n\nYour selected consultation type: {consultation_type}\nMode: {consult_mode}\nFee: {fee_usd}\n\nYou can track the current status of your request at any time here:\n{track_url}\n\nWarm regards,\nConsultation Team",
			),
			'payment_confirmed' => array(
				'label'   => esc_html__( 'Payment Confirmed', 'wellness-consult' ),
				'subject' => 'Payment Confirmed - Ref: {reference_id}',
				'body'    => "Hello {name},\n\nYour payment for consultation request {reference_id} ({consultation_type} - {consult_mode}) has been verified and confirmed.\n\nOur team is currently reviewing doctor availability in your preferred time window. You will receive scheduling details shortly.\n\nTrack your request: {track_url}\n\nWarm regards,\nConsultation Team",
			),
			'request_rejected' => array(
				'label'   => esc_html__( 'Payment Proof Rejected', 'wellness-consult' ),
				'subject' => 'Payment Verification Update - Ref: {reference_id}',
				'body'    => "Hello {name},\n\nWe were unable to verify the payment transfer screenshot for request {reference_id}.\n\nReason: {rejection_reason}\n\nPlease visit your tracking link to upload a new, clear screenshot of your transfer proof:\n{track_url}\n\nWarm regards,\nConsultation Team",
			),
			'appointment_scheduled' => array(
				'label'   => esc_html__( 'Appointment Scheduled', 'wellness-consult' ),
				'subject' => 'Consultation Scheduled - Ref: {reference_id}',
				'body'    => "Hello {name},\n\nYour consultation is confirmed for:\nYour Local Time: {date_time_patient}\nConsultant Time: {date_time_consultant}\nMode: {consult_mode}\nPlatform: {platform}\n\nMeeting Access Link / Details:\n{meeting_link}\n\nPlease join 5 minutes before your scheduled appointment.\nTrack request: {track_url}\n\nWarm regards,\nConsultation Team",
			),
			'reminder_24h' => array(
				'label'   => esc_html__( '24-Hour Reminder', 'wellness-consult' ),
				'subject' => 'Reminder: Consultation Tomorrow - Ref: {reference_id}',
				'body'    => "Hello {name},\n\nThis is a reminder that your private consultation is scheduled for tomorrow at {date_time_patient}.\n\nMode: {consult_mode} via {platform}\nMeeting Link / Details: {meeting_link}\n\nWarm regards,\nConsultation Team",
			),
			'reminder_1h' => array(
				'label'   => esc_html__( '1-Hour Reminder', 'wellness-consult' ),
				'subject' => 'Starting Soon: Consultation in 1 Hour - Ref: {reference_id}',
				'body'    => "Hello {name},\n\nYour private consultation starts in 1 hour at {date_time_patient}.\n\nMode: {consult_mode} via {platform}\nMeeting Link / Details: {meeting_link}\n\nWarm regards,\nConsultation Team",
			),
			'completed_thankyou' => array(
				'label'   => esc_html__( 'Consultation Completed', 'wellness-consult' ),
				'subject' => 'Thank you for your consultation - Ref: {reference_id}',
				'body'    => "Hello {name},\n\nThank you for consulting with us today. We hope our session gave you clarity and support.\n\nIf you would like to book a follow-up review session or share anonymous feedback, please visit:\n{track_url}\n\nWarm regards,\nConsultation Team",
			),
		);
	}

	/**
	 * Full list of standard ISO 3166-1 alpha-2 countries.
	 *
	 * @return array
	 */
	public static function get_all_iso_countries() {
		return array(
			'AF' => 'Afghanistan', 'AL' => 'Albania', 'DZ' => 'Algeria', 'AD' => 'Andorra', 'AO' => 'Angola',
			'AR' => 'Argentina', 'AM' => 'Armenia', 'AU' => 'Australia', 'AT' => 'Austria', 'AZ' => 'Azerbaijan',
			'BH' => 'Bahrain', 'BD' => 'Bangladesh', 'BY' => 'Belarus', 'BE' => 'Belgium', 'BZ' => 'Belize',
			'BJ' => 'Benin', 'BT' => 'Bhutan', 'BO' => 'Bolivia', 'BA' => 'Bosnia and Herzegovina', 'BW' => 'Botswana',
			'BR' => 'Brazil', 'BN' => 'Brunei', 'BG' => 'Bulgaria', 'BF' => 'Burkina Faso', 'BI' => 'Burundi',
			'KH' => 'Cambodia', 'CM' => 'Cameroon', 'CA' => 'Canada', 'CL' => 'Chile', 'CN' => 'China',
			'CO' => 'Colombia', 'CR' => 'Costa Rica', 'HR' => 'Croatia', 'CY' => 'Cyprus', 'CZ' => 'Czech Republic',
			'DK' => 'Denmark', 'DJ' => 'Djibouti', 'EC' => 'Ecuador', 'EG' => 'Egypt', 'EE' => 'Estonia',
			'ET' => 'Ethiopia', 'FI' => 'Finland', 'FR' => 'France', 'GE' => 'Georgia', 'DE' => 'Germany',
			'GH' => 'Ghana', 'GR' => 'Greece', 'HK' => 'Hong Kong', 'HU' => 'Hungary', 'IS' => 'Iceland',
			'IN' => 'India', 'ID' => 'Indonesia', 'IR' => 'Iran', 'IQ' => 'Iraq', 'IE' => 'Ireland',
			'IT' => 'Italy', 'JP' => 'Japan', 'JO' => 'Jordan', 'KZ' => 'Kazakhstan', 'KE' => 'Kenya',
			'KW' => 'Kuwait', 'LV' => 'Latvia', 'LB' => 'Lebanon', 'MY' => 'Malaysia', 'MX' => 'Mexico',
			'MA' => 'Morocco', 'NP' => 'Nepal', 'NL' => 'Netherlands', 'NZ' => 'New Zealand', 'NG' => 'Nigeria',
			'NO' => 'Norway', 'OM' => 'Oman', 'PK' => 'Pakistan', 'PH' => 'Philippines', 'PL' => 'Poland',
			'PT' => 'Portugal', 'QA' => 'Qatar', 'RO' => 'Romania', 'SA' => 'Saudi Arabia', 'SG' => 'Singapore',
			'ZA' => 'South Africa', 'ES' => 'Spain', 'LK' => 'Sri Lanka', 'SE' => 'Sweden', 'CH' => 'Switzerland',
			'TH' => 'Thailand', 'TR' => 'Turkey', 'AE' => 'United Arab Emirates', 'GB' => 'United Kingdom',
			'US' => 'United States', 'VN' => 'Vietnam',
		);
	}

	/**
	 * International Manual Payment Method Types (Upgrade).
	 *
	 * @return array
	 */
	public static function get_manual_payment_types() {
		return array(
			'bank' => array(
				'label' => esc_html__( 'Bank Transfer (SWIFT/IBAN)', 'wellness-consult' ),
				'badge' => esc_html__( 'Bank Wire', 'wellness-consult' ),
				'icon'  => 'dashicons-vault',
			),
			'wise_payoneer' => array(
				'label' => esc_html__( 'Payoneer or Wise', 'wellness-consult' ),
				'badge' => esc_html__( 'Payoneer / Wise', 'wellness-consult' ),
				'icon'  => 'dashicons-money-alt',
			),
			'payment_link' => array(
				'label' => esc_html__( 'Payment Link (Hosted Request URL)', 'wellness-consult' ),
				'badge' => esc_html__( 'Payment Link', 'wellness-consult' ),
				'icon'  => 'dashicons-external',
			),
			'other' => array(
				'label' => esc_html__( 'Other (Custom Instructions)', 'wellness-consult' ),
				'badge' => esc_html__( 'Other Transfer', 'wellness-consult' ),
				'icon'  => 'dashicons-editor-help',
			),
		);
	}

	/**
	 * Get label for a manual payment method type.
	 *
	 * @param string $type
	 * @return string
	 */
	public static function get_manual_payment_type_label( $type ) {
		$types = self::get_manual_payment_types();
		return isset( $types[ $type ]['label'] ) ? $types[ $type ]['label'] : esc_html__( 'Manual Transfer', 'wellness-consult' );
	}

	/**
	 * Get badge text for a manual payment method type.
	 *
	 * @param string $type
	 * @return string
	 */
	public static function get_manual_payment_type_badge( $type ) {
		$types = self::get_manual_payment_types();
		return isset( $types[ $type ]['badge'] ) ? $types[ $type ]['badge'] : esc_html__( 'Manual', 'wellness-consult' );
	}
}
