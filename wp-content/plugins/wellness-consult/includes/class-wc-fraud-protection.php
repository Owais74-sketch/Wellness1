<?php
/**
 * Contact blocking and payment fraud indicators.
 *
 * @package Wellness_Consult
 */

defined( 'ABSPATH' ) || exit;

class WC_Fraud_Protection {

	/**
	 * Normalize and hash a contact or transaction identifier.
	 *
	 * @param string $type  Value type.
	 * @param string $value Raw value.
	 * @return string
	 */
	public static function hash_value( $type, $value ) {
		$type  = sanitize_key( $type );
		$value = is_scalar( $value ) ? trim( (string) $value ) : '';

		if ( 'email' === $type ) {
			$value = strtolower( sanitize_email( $value ) );
		} elseif ( 'phone' === $type ) {
			$value = preg_replace( '/\D+/', '', $value );
		} else {
			$value = strtolower( $value );
		}

		return '' === $value ? '' : hash_hmac( 'sha256', $type . ':' . $value, wp_salt() );
	}

	/**
	 * Add a blocked contact using only its hash, or a country code.
	 *
	 * @param string $type  email, phone, or country.
	 * @param string $value Raw value or ISO country code.
	 * @return bool
	 */
	public static function add_block( $type, $value ) {
		$type = sanitize_key( $type );
		$value = is_scalar( $value ) ? (string) $value : '';
		if ( 'country' === $type ) {
			$value = strtoupper( sanitize_text_field( $value ) );
			if ( ! isset( WC_Options::get_all_iso_countries()[ $value ] ) ) {
				return false;
			}
		} elseif ( in_array( $type, array( 'email', 'phone' ), true ) ) {
			$value = self::hash_value( $type, $value );
			if ( '' === $value ) {
				return false;
			}
		} else {
			return false;
		}

		$blocks = get_option( 'wc_blocked_contacts', array() );
		if ( ! is_array( $blocks ) ) {
			$blocks = array();
		}
		$key = $type . ':' . $value;
		if ( isset( $blocks[ $key ] ) ) {
			return true;
		}
		$blocks[ $key ] = array(
			'type'       => $type,
			'value'      => $value,
			'created_at' => current_time( 'mysql', true ),
		);
		update_option( 'wc_blocked_contacts', $blocks, false );
		return true;
	}

	/**
	 * Check booking contact information against the block list.
	 *
	 * @param string $email   Email address.
	 * @param string $phone   Phone number.
	 * @param string $country ISO country code.
	 * @return bool
	 */
	public static function is_blocked( $email, $phone, $country ) {
		$blocks = get_option( 'wc_blocked_contacts', array() );
		if ( ! is_array( $blocks ) ) {
			return false;
		}

		$checks = array(
			'email:' . self::hash_value( 'email', $email ),
			'phone:' . self::hash_value( 'phone', $phone ),
			'country:' . strtoupper( sanitize_text_field( $country ) ),
		);
		foreach ( $checks as $key ) {
			if ( ':' !== substr( $key, -1 ) && isset( $blocks[ $key ] ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Return fraud indicators and related request IDs.
	 *
	 * @param int $request_id Request post ID.
	 * @return array
	 */
	public static function get_request_flags( $request_id ) {
		static $requests = null;
		$request_id = absint( $request_id );
		if ( null === $requests ) {
			$requests = array();
			$ids = get_posts( array(
				'post_type'        => 'wc_request',
				'post_status'      => 'any',
				'numberposts'      => -1,
				'fields'           => 'ids',
				'suppress_filters' => true,
			) );
			foreach ( $ids as $id ) {
				$transaction_id = get_post_meta( $id, '_wc_transaction_id', true );
				$history        = get_post_meta( $id, '_wc_proof_history', true );
				$requests[ $id ] = array(
					'transaction_hash' => self::hash_value( 'transaction', $transaction_id ),
					'proof_hashes'     => self::get_proof_hashes( $history ),
					'email_hash'       => get_post_meta( $id, '_wc_contact_email_hash', true ) ?: self::hash_value( 'email', get_post_meta( $id, '_wc_contact_email', true ) ),
					'phone_hash'       => get_post_meta( $id, '_wc_contact_phone_hash', true ) ?: self::hash_value( 'phone', get_post_meta( $id, '_wc_contact_phone', true ) ),
					'ip_hash'          => get_post_meta( $id, '_wc_ip_hash', true ),
					'created_at'       => get_post_meta( $id, '_wc_created_at', true ) ?: get_post_field( 'post_date_gmt', $id ),
					'status'           => get_post_meta( $id, '_wc_status', true ),
					'expected'         => (float) ( get_post_meta( $id, '_wc_amount_expected', true ) ?: get_post_meta( $id, '_wc_fee_usd', true ) ),
					'received'         => get_post_meta( $id, '_wc_amount_received', true ),
				);
			}
		}

		if ( ! isset( $requests[ $request_id ] ) ) {
			return array( 'flags' => array(), 'request_ids' => array() );
		}

		$current = $requests[ $request_id ];
		$flags = array();
		$related = array();
		$match_ids = array();
		foreach ( $requests as $other_id => $other ) {
			if ( $other_id === $request_id ) {
				continue;
			}
			if ( $current['transaction_hash'] && $current['transaction_hash'] === $other['transaction_hash'] ) {
				$match_ids[] = $other_id;
			}
		}
		if ( $match_ids ) {
			$flags[] = __( 'Transaction ID used on another request', 'wellness-consult' );
			$related = array_merge( $related, $match_ids );
		}

		$match_ids = array();
		foreach ( $requests as $other_id => $other ) {
			if ( $other_id !== $request_id && array_intersect( $current['proof_hashes'], $other['proof_hashes'] ) ) {
				$match_ids[] = $other_id;
			}
		}
		if ( $match_ids ) {
			$flags[] = __( 'Screenshot file hash used on another request', 'wellness-consult' );
			$related = array_merge( $related, $match_ids );
		}

		$rejected_ids = array();
		foreach ( array( 'email_hash', 'phone_hash' ) as $contact_key ) {
			if ( empty( $current[ $contact_key ] ) ) {
				continue;
			}
			foreach ( $requests as $other_id => $other ) {
				if ( $other_id !== $request_id && WC_Status::STATUS_REJECTED === $other['status'] && $current[ $contact_key ] === $other[ $contact_key ] ) {
					$rejected_ids[] = $other_id;
				}
			}
		}
		$rejected_ids = array_values( array_unique( $rejected_ids ) );
		if ( count( $rejected_ids ) >= 2 ) {
			$flags[] = __( 'Contact is linked to 2 or more rejected requests', 'wellness-consult' );
			$related = array_merge( $related, $rejected_ids );
		}

		$recent_ip_ids = array();
		$since = time() - DAY_IN_SECONDS;
		if ( ! empty( $current['ip_hash'] ) ) {
			foreach ( $requests as $other_id => $other ) {
				$created = strtotime( (string) $other['created_at'] . ' UTC' );
				if ( $current['ip_hash'] === $other['ip_hash'] && false !== $created && $created >= $since ) {
					$recent_ip_ids[] = $other_id;
				}
			}
		}
		if ( count( $recent_ip_ids ) >= 3 ) {
			$flags[] = __( '3 or more requests from this network in 24 hours', 'wellness-consult' );
			$related = array_merge( $related, array_diff( $recent_ip_ids, array( $request_id ) ) );
		}

		if ( '' !== (string) $current['received'] && abs( (float) $current['received'] - $current['expected'] ) >= 0.01 ) {
			$flags[] = __( 'Amount received differs from expected amount', 'wellness-consult' );
		}

		return array(
			'flags'       => array_values( array_unique( $flags ) ),
			'request_ids' => array_values( array_unique( $related ) ),
		);
	}

	/**
	 * Safely collect stored SHA-256 proof hashes.
	 *
	 * @param mixed $history Proof history metadata.
	 * @return array
	 */
	private static function get_proof_hashes( $history ) {
		$hashes = array();
		if ( ! is_array( $history ) ) {
			return $hashes;
		}
		foreach ( $history as $entry ) {
			if ( is_array( $entry ) && isset( $entry['hash'] ) && is_string( $entry['hash'] ) && '' !== $entry['hash'] ) {
				$hashes[] = sanitize_text_field( $entry['hash'] );
			}
		}
		return $hashes;
	}
}
