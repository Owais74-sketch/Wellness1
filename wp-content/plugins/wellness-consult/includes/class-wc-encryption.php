<?php
/**
 * Encryption and Secrets Protection Helper
 *
 * Implements AES-256-CBC encryption for gateway secret keys in wp_options,
 * constant overrides from wp-config.php, and secrets masking (Requirement 7).
 *
 * @package Wellness_Consult
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class WC_Encryption {

	const CIPHER_METHOD = 'aes-256-cbc';
	const PREFIX        = 'wc_enc:';

	/**
	 * Get encryption key derived from WordPress security salts.
	 *
	 * @return string
	 */
	protected static function get_key() {
		$salt = defined( 'SECURE_AUTH_KEY' ) ? SECURE_AUTH_KEY : ( defined( 'AUTH_KEY' ) ? AUTH_KEY : wp_salt( 'auth' ) );
		return hash( 'sha256', $salt, true );
	}

	/**
	 * Encrypt a plaintext secret string.
	 *
	 * @param string $plaintext
	 * @return string Encrypted string with wc_enc: prefix, or original if empty.
	 */
	public static function encrypt( $plaintext ) {
		if ( empty( $plaintext ) || ! is_string( $plaintext ) ) {
			return $plaintext;
		}

		// Don't re-encrypt already encrypted string
		if ( strpos( $plaintext, self::PREFIX ) === 0 ) {
			return $plaintext;
		}

		if ( ! function_exists( 'openssl_encrypt' ) ) {
			return $plaintext;
		}

		$iv_len = openssl_cipher_iv_length( self::CIPHER_METHOD );
		$iv     = openssl_random_pseudo_bytes( $iv_len );
		$raw    = openssl_encrypt( $plaintext, self::CIPHER_METHOD, self::get_key(), OPENSSL_RAW_DATA, $iv );

		if ( false === $raw ) {
			return $plaintext;
		}

		return self::PREFIX . base64_encode( $iv . $raw );
	}

	/**
	 * Decrypt an encrypted secret string.
	 *
	 * @param string $ciphertext
	 * @return string Decrypted plaintext.
	 */
	public static function decrypt( $ciphertext ) {
		if ( empty( $ciphertext ) || ! is_string( $ciphertext ) ) {
			return $ciphertext;
		}

		if ( strpos( $ciphertext, self::PREFIX ) !== 0 ) {
			// Plaintext unencrypted legacy key
			return $ciphertext;
		}

		if ( ! function_exists( 'openssl_decrypt' ) ) {
			return $ciphertext;
		}

		$data   = base64_decode( substr( $ciphertext, strlen( self::PREFIX ) ) );
		$iv_len = openssl_cipher_iv_length( self::CIPHER_METHOD );
		$iv     = substr( $data, 0, $iv_len );
		$raw    = substr( $data, $iv_len );

		$decrypted = openssl_decrypt( $raw, self::CIPHER_METHOD, self::get_key(), OPENSSL_RAW_DATA, $iv );

		return ( false !== $decrypted ) ? $decrypted : '';
	}

	/**
	 * Mask a secret string for safe administrative presentation.
	 *
	 * @param string $secret
	 * @return string
	 */
	public static function mask_secret( $secret ) {
		if ( empty( $secret ) ) {
			return '';
		}

		$secret = self::decrypt( $secret );
		$len    = strlen( $secret );
		if ( $len <= 6 ) {
			return str_repeat( '•', $len );
		}

		$prefix = substr( $secret, 0, 4 );
		$suffix = substr( $secret, -4 );
		return $prefix . '••••••••' . $suffix;
	}
}
