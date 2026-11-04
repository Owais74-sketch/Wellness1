<?php
/**
 * Gateway Registry Manager
 *
 * Implements centralized registration and retrieval of payment gateways (Requirement 8).
 *
 * @package Wellness_Consult
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class WC_Gateway_Registry {

	/**
	 * Map of registered gateway classes keyed by gateway ID.
	 *
	 * @var array<string, string>
	 */
	protected static $gateways = array();

	/**
	 * Register a gateway class implementing WC_Gateway_Interface.
	 *
	 * @param string $class_name
	 * @return bool
	 */
	public static function register( $class_name ) {
		if ( ! class_exists( $class_name ) ) {
			return false;
		}

		$implements = class_implements( $class_name );
		if ( ! isset( $implements['WC_Gateway_Interface'] ) ) {
			return false;
		}

		$gateway_id = $class_name::get_id();
		self::$gateways[ $gateway_id ] = $class_name;
		return true;
	}

	/**
	 * Get gateway class by ID.
	 *
	 * @param string $gateway_id
	 * @return string|null Class name or null if not found.
	 */
	public static function get( $gateway_id ) {
		return self::$gateways[ $gateway_id ] ?? null;
	}

	/**
	 * Get all registered gateway classes.
	 *
	 * @return array<string, string>
	 */
	public static function get_all() {
		return self::$gateways;
	}

	/**
	 * Get only registered gateways that are currently available and configured.
	 *
	 * @return array<string, string>
	 */
	public static function get_available() {
		$available = array();
		foreach ( self::$gateways as $id => $class_name ) {
			if ( $class_name::is_available() ) {
				$available[ $id ] = $class_name;
			}
		}
		return $available;
	}
}
