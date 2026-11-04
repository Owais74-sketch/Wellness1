<?php
/**
 * User Roles and Capabilities Management
 *
 * Implements roles: wc_consultant and wc_editor_no_patient_data
 *
 * @package Wellness_Consult
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class WC_Roles {

	/**
	 * Register custom roles and assign capabilities.
	 */
	public static function create_roles() {
		// 1. Consultant Role
		add_role( 'wc_consultant', esc_html__( 'Consultant', 'wellness-consult' ), array(
			'read'                         => true,
			'upload_files'                 => true,
			'wc_manage_assigned_requests' => true,
			'wc_manage_own_availability'   => true,
			'wc_view_calendar'             => true,
			// Explicitly no access to administrative menus
			'wc_manage_settings'           => false,
			'wc_manage_payments'           => false,
			'wc_manage_countries'          => false,
			'wc_manage_email_templates'    => false,
			'wc_view_reports'              => false,
			'wc_manage_all_requests'       => false,
		) );

		// 2. Editor without patient data access
		// Clone standard editor caps and ensure zero wc capabilities
		$editor_role = get_role( 'editor' );
		$editor_caps = $editor_role ? $editor_role->capabilities : array();
		
		// Remove any potential consultation access
		$editor_caps['wc_manage_consultations']     = false;
		$editor_caps['wc_manage_requests']          = false;
		$editor_caps['wc_manage_all_requests']      = false;
		$editor_caps['wc_manage_assigned_requests'] = false;
		$editor_caps['wc_manage_settings']          = false;
		$editor_caps['wc_manage_payments']          = false;
		$editor_caps['wc_manage_countries']         = false;
		$editor_caps['wc_manage_email_templates']   = false;
		$editor_caps['wc_view_reports']             = false;
		$editor_caps['wc_view_activity_log']        = false;

		add_role( 'wc_editor_no_patient_data', esc_html__( 'Editor (No Patient Data)', 'wellness-consult' ), $editor_caps );

		// 3. Add all custom capabilities to Administrator
		$admin_role = get_role( 'administrator' );
		if ( $admin_role ) {
			$admin_caps = array(
				'wc_manage_consultations',
				'wc_manage_requests',
				'wc_manage_all_requests',
				'wc_manage_assigned_requests',
				'wc_manage_types',
				'wc_manage_payments',
				'wc_manage_availability',
				'wc_manage_own_availability',
				'wc_manage_countries',
				'wc_manage_email_templates',
				'wc_manage_settings',
				'wc_view_reports',
				'wc_view_activity_log',
				'wc_view_calendar',
			);

			foreach ( $admin_caps as $cap ) {
				$admin_role->add_cap( $cap );
			}
		}
	}

	/**
	 * Remove custom roles on complete uninstallation.
	 */
	public static function remove_roles() {
		remove_role( 'wc_consultant' );
		remove_role( 'wc_editor_no_patient_data' );

		// Clean up admin custom capabilities
		$admin_role = get_role( 'administrator' );
		if ( $admin_role ) {
			$admin_caps = array(
				'wc_manage_consultations',
				'wc_manage_requests',
				'wc_manage_all_requests',
				'wc_manage_assigned_requests',
				'wc_manage_types',
				'wc_manage_payments',
				'wc_manage_availability',
				'wc_manage_own_availability',
				'wc_manage_countries',
				'wc_manage_email_templates',
				'wc_manage_settings',
				'wc_view_reports',
				'wc_view_activity_log',
				'wc_view_calendar',
			);

			foreach ( $admin_caps as $cap ) {
				$admin_role->remove_cap( $cap );
			}
		}
	}
}
