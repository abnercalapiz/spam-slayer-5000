<?php
/**
 * Security utilities for the plugin.
 *
 * @since      1.0.0
 * @package    Spam_Slayer_5000
 * @subpackage Spam_Slayer_5000/includes
 */

class Spam_Slayer_5000_Security {

	/**
	 * Encrypt sensitive data.
	 *
	 * @since    1.0.0
	 * @param    string    $data    Data to encrypt.
	 * @return   string             Encrypted data.
	 */
	public static function encrypt( $data ) {
		if ( empty( $data ) ) {
			return '';
		}

		$key = self::get_encryption_key();
		$iv_length = openssl_cipher_iv_length( 'aes-256-cbc' );
		$iv = openssl_random_pseudo_bytes( $iv_length );
		
		$encrypted = openssl_encrypt( $data, 'aes-256-cbc', $key, 0, $iv );
		
		return base64_encode( $iv . $encrypted );
	}

	/**
	 * Decrypt sensitive data.
	 *
	 * @since    1.0.0
	 * @param    string    $data    Data to decrypt.
	 * @return   string             Decrypted data.
	 */
	public static function decrypt( $data ) {
		if ( empty( $data ) ) {
			return '';
		}

		$key = self::get_encryption_key();
		$data = base64_decode( $data );
		$iv_length = openssl_cipher_iv_length( 'aes-256-cbc' );
		$iv = substr( $data, 0, $iv_length );
		$encrypted = substr( $data, $iv_length );
		
		return openssl_decrypt( $encrypted, 'aes-256-cbc', $key, 0, $iv );
	}

	/**
	 * Get or generate encryption key.
	 *
	 * @since    1.0.0
	 * @return   string    Encryption key.
	 */
	private static function get_encryption_key() {
		$key = get_option( 'spam_slayer_5000_encryption_key' );
		
		if ( empty( $key ) ) {
			$key = wp_generate_password( 32, true, true );
			update_option( 'spam_slayer_5000_encryption_key', $key );
		}
		
		return $key;
	}

	/**
	 * Validate IP address (including IPv6).
	 *
	 * @since    1.0.0
	 * @param    string    $ip    IP address to validate.
	 * @return   string           Validated IP or empty string.
	 */
	public static function validate_ip( $ip ) {
		// Remove any port information
		if ( strpos( $ip, ':' ) !== false && strpos( $ip, '[' ) === false ) {
			// IPv4 with port
			$parts = explode( ':', $ip );
			$ip = $parts[0];
		} elseif ( strpos( $ip, ']' ) !== false ) {
			// IPv6 with port
			$ip = preg_replace( '/\[([^\]]+)\].*/', '$1', $ip );
		}
		
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 ) ) {
			return $ip;
		}
		
		return '';
	}

	/**
	 * Get user IP address with better validation.
	 *
	 * @since    1.0.0
	 * @return   string    IP address.
	 */
	public static function get_user_ip() {
		$ip_keys = array( 
			'HTTP_CF_CONNECTING_IP',     // Cloudflare
			'HTTP_X_REAL_IP',            // Nginx proxy
			'HTTP_X_FORWARDED_FOR',      // General proxy
			'HTTP_CLIENT_IP',            // Proxy
			'REMOTE_ADDR'                // Standard
		);

		foreach ( $ip_keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				
				// Handle comma-separated IPs
				if ( strpos( $ip, ',' ) !== false ) {
					$ips = explode( ',', $ip );
					$ip = trim( $ips[0] );
				}
				
				$validated_ip = self::validate_ip( $ip );
				if ( ! empty( $validated_ip ) ) {
					return $validated_ip;
				}
			}
		}

		return '';
	}

	/**
	 * Check rate limit with improved tracking.
	 *
	 * @since    1.0.0
	 * @param    string    $identifier    Rate limit identifier.
	 * @param    int       $max_attempts  Maximum attempts allowed.
	 * @param    int       $window        Time window in seconds.
	 * @return   bool                     True if within rate limit.
	 */
	public static function check_rate_limit( $identifier, $max_attempts = 5, $window = 60 ) {
		$transient_key = 'ss5k_rate_' . md5( $identifier );
		$attempts = get_transient( $transient_key );
		
		if ( $attempts === false ) {
			set_transient( $transient_key, 1, $window );
			return true;
		}
		
		if ( $attempts >= $max_attempts ) {
			return false;
		}
		
		set_transient( $transient_key, $attempts + 1, $window );
		return true;
	}

	/**
	 * Sanitize and validate input length.
	 *
	 * @since    1.0.0
	 * @param    string    $input       Input to sanitize.
	 * @param    int       $max_length  Maximum allowed length.
	 * @param    string    $type        Type of sanitization.
	 * @return   string                 Sanitized input.
	 */
	public static function sanitize_input( $input, $max_length = 500, $type = 'text' ) {
		switch ( $type ) {
			case 'email':
				$input = sanitize_email( $input );
				break;
			
			case 'url':
				$input = esc_url_raw( $input );
				break;
			
			case 'textarea':
				$input = sanitize_textarea_field( $input );
				break;
			
			case 'key':
				$input = sanitize_key( $input );
				break;
			
			case 'text':
			default:
				$input = sanitize_text_field( $input );
				break;
		}
		
		// Enforce maximum length
		if ( strlen( $input ) > $max_length ) {
			$input = substr( $input, 0, $max_length );
		}
		
		return $input;
	}

	/**
	 * Create secure nonce with additional validation.
	 *
	 * @since    1.0.0
	 * @param    string    $action    Nonce action.
	 * @return   string               Nonce value.
	 */
	public static function create_nonce( $action ) {
		$user_id = get_current_user_id();
		$session_token = wp_get_session_token();
		
		// Add additional entropy
		$action = $action . '_' . $user_id . '_' . substr( $session_token, 0, 10 );
		
		return wp_create_nonce( $action );
	}

	/**
	 * Verify nonce with additional validation.
	 *
	 * @since    1.0.0
	 * @param    string    $nonce     Nonce value.
	 * @param    string    $action    Nonce action.
	 * @return   bool                 True if valid.
	 */
	public static function verify_nonce( $nonce, $action ) {
		$user_id = get_current_user_id();
		$session_token = wp_get_session_token();
		
		// Add same entropy as creation
		$action = $action . '_' . $user_id . '_' . substr( $session_token, 0, 10 );
		
		return wp_verify_nonce( $nonce, $action );
	}
}