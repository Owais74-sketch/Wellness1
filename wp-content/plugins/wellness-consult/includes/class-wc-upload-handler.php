<?php
/**
 * Payment Screenshot Upload & Hardened Storage Handler
 *
 * Implements Rule 2: Secure screenshot upload, MIME verification, image re-encoding,
 * and protected .htaccess storage.
 *
 * @package Wellness_Consult
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class WC_Upload_Handler {

	/**
	 * Whitelist of permitted file extensions and canonical MIME types.
	 */
	const ALLOWED_MIMES = array(
		'jpg'  => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'png'  => 'image/png',
		'pdf'  => 'application/pdf',
	);

	/**
	 * Ensure the protected wc-proofs directory and security files exist.
	 *
	 * @param string $sub_dir Optional YYYY/MM subfolder.
	 * @return string Full directory path.
	 */
	public static function get_storage_dir( $sub_dir = '' ) {
		$base_dir = rtrim( WC_PROOFS_DIR, '/\\' ) . '/';

		if ( ! file_exists( $base_dir ) ) {
			wp_mkdir_p( $base_dir );
		}

		// Ensure root .htaccess exists
		$htaccess_file = $base_dir . '.htaccess';
		if ( ! file_exists( $htaccess_file ) ) {
			$htaccess_content = "# Apache 2.4+\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n\n# Apache 2.2 fallback\n<IfModule !mod_authz_core.c>\n    Deny from all\n</IfModule>\n";
			file_put_contents( $htaccess_file, $htaccess_content );
		}

		// Ensure root index.php exists
		$index_file = $base_dir . 'index.php';
		if ( ! file_exists( $index_file ) ) {
			file_put_contents( $index_file, "<?php\n// Silence is golden.\n" );
		}

		if ( ! empty( $sub_dir ) ) {
			$target_dir = $base_dir . trim( $sub_dir, '/\\' ) . '/';
			if ( ! file_exists( $target_dir ) ) {
				wp_mkdir_p( $target_dir );
			}
			$sub_index = $target_dir . 'index.php';
			if ( ! file_exists( $sub_index ) ) {
				file_put_contents( $sub_index, "<?php\n// Silence is golden.\n" );
			}
			return $target_dir;
		}

		return $base_dir;
	}

	/**
	 * Process and store an uploaded screenshot file.
	 *
	 * @param array $file_array $_FILES item structure.
	 * @return array|WP_Error Array with path, hash, and metadata on success, WP_Error on failure.
	 */
	public static function handle_upload( $file_array ) {
		if ( empty( $file_array ) || ! is_array( $file_array ) || ! isset( $file_array['tmp_name'] ) ) {
			return new WP_Error( 'wc_upload_empty', esc_html__( 'Please select a payment proof screenshot to upload.', 'wellness-consult' ) );
		}

		if ( UPLOAD_ERR_OK !== $file_array['error'] ) {
			switch ( $file_array['error'] ) {
				case UPLOAD_ERR_INI_SIZE:
				case UPLOAD_ERR_FORM_SIZE:
					return new WP_Error( 'wc_upload_oversized', sprintf( esc_html__( 'The file exceeds the maximum allowed size of %s MB.', 'wellness-consult' ), round( WC_MAX_UPLOAD_SIZE / ( 1024 * 1024 ) ) ) );
				case UPLOAD_ERR_NO_FILE:
					return new WP_Error( 'wc_upload_no_file', esc_html__( 'No file was submitted.', 'wellness-consult' ) );
				default:
					return new WP_Error( 'wc_upload_failed', esc_html__( 'File upload error occurred. Please try again.', 'wellness-consult' ) );
			}
		}

		$tmp_path  = $file_array['tmp_name'];
		$file_size = (int) $file_array['size'];
		$raw_name  = sanitize_file_name( $file_array['name'] );

		// 1. Strict Size Check
		if ( $file_size <= 0 || $file_size > WC_MAX_UPLOAD_SIZE ) {
			return new WP_Error( 'wc_upload_oversized', sprintf( esc_html__( 'The uploaded proof exceeds the maximum allowed limit of %s MB.', 'wellness-consult' ), round( WC_MAX_UPLOAD_SIZE / ( 1024 * 1024 ) ) ) );
		}

		// 2. Extension Whitelist Check
		$ext = strtolower( pathinfo( $raw_name, PATHINFO_EXTENSION ) );
		if ( ! array_key_exists( $ext, self::ALLOWED_MIMES ) ) {
			return new WP_Error( 'wc_upload_invalid_extension', esc_html__( 'Invalid file extension. Only JPG, PNG, and PDF files are allowed.', 'wellness-consult' ) );
		}

		// 3. Real Content / MIME Type Verification via Fileinfo (finfo)
		if ( ! function_exists( 'finfo_open' ) ) {
			return new WP_Error( 'wc_upload_finfo_missing', esc_html__( 'Server configuration error: PHP Fileinfo extension is required.', 'wellness-consult' ) );
		}

		$finfo = finfo_open( FILEINFO_MIME_TYPE );
		$real_mime = finfo_file( $finfo, $tmp_path );
		finfo_close( $finfo );

		if ( ! in_array( $real_mime, self::ALLOWED_MIMES, true ) ) {
			return new WP_Error( 'wc_upload_invalid_mime', esc_html__( 'Invalid file content. The real MIME type does not match permitted image or PDF formats.', 'wellness-consult' ) );
		}

		// Verify extension matches real MIME
		$expected_mime = self::ALLOWED_MIMES[ $ext ];
		if ( $real_mime !== $expected_mime && ! ( 'jpg' === $ext && 'image/jpeg' === $real_mime ) ) {
			return new WP_Error( 'wc_upload_mime_mismatch', esc_html__( 'File extension does not match its actual content type.', 'wellness-consult' ) );
		}

		// Disallow SVG explicitly
		if ( false !== stripos( $real_mime, 'svg' ) || 'svg' === $ext ) {
			return new WP_Error( 'wc_upload_svg_rejected', esc_html__( 'Vector SVG files are not permitted for security reasons.', 'wellness-consult' ) );
		}

		$year_month   = gmdate( 'Y/m' );
		$target_dir   = self::get_storage_dir( $year_month );
		$random_token = wp_generate_password( 32, false );

		// 4. Image Security & Re-encoding (strips EXIF, malicious comments, and scripts)
		if ( in_array( $real_mime, array( 'image/jpeg', 'image/png' ), true ) ) {
			$img_info = @getimagesize( $tmp_path );
			if ( false === $img_info || empty( $img_info[0] ) || empty( $img_info[1] ) ) {
				return new WP_Error( 'wc_upload_fake_image', esc_html__( 'Corrupted image file or invalid image structure.', 'wellness-consult' ) );
			}

			// Ensure detected image MIME matches
			if ( ! in_array( $img_info['mime'], array( 'image/jpeg', 'image/png' ), true ) ) {
				return new WP_Error( 'wc_upload_image_mismatch', esc_html__( 'Image metadata does not match permitted formats.', 'wellness-consult' ) );
			}

			$canonical_ext = ( 'image/png' === $real_mime ) ? 'png' : 'jpg';
			$stored_filename = $random_token . '.' . $canonical_ext;
			$destination     = $target_dir . $stored_filename;

			// Re-encode through WordPress image editor
			$editor = wp_get_image_editor( $tmp_path );
			if ( ! is_wp_error( $editor ) ) {
				$save_status = $editor->save( $destination );
				if ( is_wp_error( $save_status ) ) {
					// Fallback to move if editor fails to save
					if ( ! @move_uploaded_file( $tmp_path, $destination ) && ! @copy( $tmp_path, $destination ) ) {
						return new WP_Error( 'wc_upload_storage_failed', esc_html__( 'Failed to write proof file to secure storage.', 'wellness-consult' ) );
					}
				}
			} else {
				// If WP Image Editor cannot parse, reject to be safe
				return new WP_Error( 'wc_upload_unreadable_image', esc_html__( 'Unable to process and sanitize image proof.', 'wellness-consult' ) );
			}
		} elseif ( 'application/pdf' === $real_mime ) {
			// Verify PDF Header (%PDF-)
			$handle = @fopen( $tmp_path, 'rb' );
			if ( ! $handle ) {
				return new WP_Error( 'wc_upload_unreadable_pdf', esc_html__( 'Unable to read uploaded PDF file.', 'wellness-consult' ) );
			}
			$header = fread( $handle, 5 );
			fclose( $handle );

			if ( '%PDF-' !== $header ) {
				return new WP_Error( 'wc_upload_fake_pdf', esc_html__( 'The file does not have a valid PDF header signature.', 'wellness-consult' ) );
			}

			$stored_filename = $random_token . '.pdf';
			$destination     = $target_dir . $stored_filename;

			if ( ! @move_uploaded_file( $tmp_path, $destination ) && ! @copy( $tmp_path, $destination ) ) {
				return new WP_Error( 'wc_upload_storage_failed', esc_html__( 'Failed to write PDF proof to secure storage.', 'wellness-consult' ) );
			}
		} else {
			return new WP_Error( 'wc_upload_unsupported', esc_html__( 'Unsupported file type.', 'wellness-consult' ) );
		}

		// 5. Generate SHA-256 Checksum for proof history
		$sha256 = hash_file( 'sha256', $destination );
		$relative_path = $year_month . '/' . $stored_filename;

		return array(
			'relative_path' => $relative_path,
			'full_path'     => $destination,
			'filename'      => $stored_filename,
			'hash'          => $sha256,
			'size'          => filesize( $destination ),
			'mime'          => $real_mime,
			'uploaded_at'   => current_time( 'mysql', 1 ),
		);
	}

	/**
	 * Delete a stored proof only when its relative path resolves inside wc-proofs.
	 *
	 * @param string $relative_path Stored proof path.
	 * @return bool
	 */
	public static function delete_proof( $relative_path ) {
		if ( ! is_string( $relative_path ) || '' === $relative_path || false !== strpos( $relative_path, '..' ) || path_is_absolute( $relative_path ) ) {
			return false;
		}
		$base = realpath( WC_PROOFS_DIR );
		$file = realpath( rtrim( WC_PROOFS_DIR, '/\\' ) . DIRECTORY_SEPARATOR . ltrim( $relative_path, '/\\' ) );
		if ( false === $base || false === $file || ! is_file( $file ) ) {
			return false;
		}
		$base = trailingslashit( wp_normalize_path( $base ) );
		$file = wp_normalize_path( $file );
		if ( 0 !== strpos( $file, $base ) ) {
			return false;
		}
		return @unlink( $file );
	}
}
