<?php
/**
 * Validator handler.
 *
 * @since      1.0.0
 * @package    Spam_Slayer_5000
 * @subpackage Spam_Slayer_5000/includes
 */

class Spam_Slayer_5000_Validator {

	/**
	 * Validate submission data with AI.
	 *
	 * @since    1.0.0
	 * @param    array    $submission_data    Submission data.
	 * @param    array    $options           Validation options.
	 * @return   array                       Validation result.
	 */
	public static function validate_submission( $submission_data, $options = array() ) {
		$defaults = array(
			'check_whitelist' => true,
			'check_blocklist' => true,
			'use_cache' => true,
			'provider' => null,
			'threshold' => null,
			'check_australian_data' => get_option( 'sfs_enable_australian_validation', false ),
		);

		$options = wp_parse_args( $options, $defaults );

		$database = new Spam_Slayer_5000_Database();
		$email = self::extract_email( $submission_data );
		$ip_address = $database->get_user_ip();

		// Check for duplicate submissions first (before blocklist)
		// Use a shorter time window (60 seconds) to avoid flagging legitimate resubmissions
		$duplicate_check = $database->check_duplicate_submission( $submission_data, 60 );
		if ( $duplicate_check !== false && $duplicate_check['count'] > 0 ) {
			// If there are many duplicates in a very short time, it's likely spam
			if ( $duplicate_check['count'] >= 5 ) {
				return array(
					'is_spam' => true,
					'spam_score' => 95,
					'reason' => sprintf( 'Duplicate submission detected (%d similar submissions in %d seconds)', 
						$duplicate_check['count'], 
						$duplicate_check['time_window'] 
					),
					'status' => 'spam',
					'duplicate_info' => $duplicate_check,
				);
			}
			// For fewer duplicates, only add a small penalty
			else if ( $duplicate_check['count'] >= 3 ) {
				// We'll add this to the spam score later in the validation
				$options['duplicate_penalty'] = 20;
				$options['duplicate_info'] = $duplicate_check;
			}
		}

		// Check blocklist first - blocklist takes precedence over everything
		if ( $options['check_blocklist'] ) {
			// Check if email is blocked
			if ( ! empty( $email ) && $database->is_email_blocked( $email ) ) {
				return array(
					'is_spam' => true,
					'spam_score' => 100,
					'reason' => 'Email is in blocklist',
					'status' => 'spam',
					'blocked_by' => 'blocklist_email',
				);
			}

			// Check if IP is blocked
			if ( ! empty( $ip_address ) && $database->is_ip_blocked( $ip_address ) ) {
				return array(
					'is_spam' => true,
					'spam_score' => 100,
					'reason' => 'IP address is in blocklist',
					'status' => 'spam',
					'blocked_by' => 'blocklist_ip',
				);
			}
		}

		// Check whitelist second
		if ( $options['check_whitelist'] ) {
			if ( ! empty( $email ) && $database->is_whitelisted( $email ) ) {
				return array(
					'is_spam' => false,
					'spam_score' => 0,
					'reason' => 'Whitelisted email',
					'status' => 'whitelist',
				);
			}
		}

		// Check Australian data validation
		if ( $options['check_australian_data'] ) {
			$aus_validator = new Spam_Slayer_5000_Australian_Validator();
			
			$aus_validation = $aus_validator->validate_all( $submission_data );
			
			if ( ! $aus_validation['valid'] ) {
				return array(
					'is_spam' => true,
					'spam_score' => 100,
					'reason' => 'Failed Australian data validation: ' . implode( ', ', $aus_validation['errors'] ),
					'status' => 'invalid_data',
					'validation_errors' => $aus_validation['errors'],
				);
			}
		}

		// Check cache
		if ( $options['use_cache'] ) {
			$cache_key = self::get_cache_key( $submission_data );
			$cache = new Spam_Slayer_5000_Cache();
			$cached_result = $cache->get( $cache_key );

			if ( $cached_result !== false ) {
				$cached_result['from_cache'] = true;
				return $cached_result;
			}
		}

		// Get provider
		if ( $options['provider'] instanceof Spam_Slayer_5000_Provider_Interface ) {
			$provider = $options['provider'];
		} else {
			$provider = Spam_Slayer_5000_Provider_Factory::get_primary_provider();
		}

		if ( ! $provider ) {
			return array(
				'is_spam' => false,
				'spam_score' => 0,
				'reason' => 'No AI provider available',
				'error' => true,
			);
		}

		// Validate with AI
		$result = $provider->analyze( $submission_data );

		// Apply duplicate penalty if exists
		if ( isset( $options['duplicate_penalty'] ) && isset( $result['spam_score'] ) ) {
			$original_score = $result['spam_score'];
			$result['spam_score'] = min( 100, $result['spam_score'] + $options['duplicate_penalty'] );
			$result['original_spam_score'] = $original_score;
			$result['duplicate_info'] = $options['duplicate_info'];
			
			// Update reason to mention duplicates only if it significantly affected the score
			if ( $options['duplicate_penalty'] >= 20 ) {
				if ( ! empty( $result['reason'] ) ) {
					$result['reason'] .= sprintf( '; Multiple similar submissions detected (+%d points)', $options['duplicate_penalty'] );
				} else {
					$result['reason'] = sprintf( 'Multiple similar submissions detected (%d similar submissions)', $options['duplicate_info']['count'] );
				}
			}
		}

		// Apply threshold
		$threshold = $options['threshold'] !== null 
			? $options['threshold'] 
			: get_option( 'spam_slayer_5000_spam_threshold', 75 );

		if ( isset( $result['spam_score'] ) ) {
			$result['is_spam'] = $result['spam_score'] >= $threshold;
		}

		// Cache result
		if ( $options['use_cache'] && ! isset( $result['error'] ) ) {
			$cache = new Spam_Slayer_5000_Cache();
			$cache->set( 
				$cache_key, 
				$result, 
				get_option( 'spam_slayer_5000_cache_duration', 3600 ) 
			);
		}

		return $result;
	}

	/**
	 * Perform rule-based validation.
	 *
	 * @since    1.0.0
	 * @param    array    $submission_data    Submission data.
	 * @return   array                       Validation result.
	 */
	public static function rule_based_validation( $submission_data ) {
		$spam_score = 0;
		$reasons = array();

		// Check for excessive links
		$link_count = 0;
		foreach ( $submission_data as $value ) {
			if ( is_string( $value ) ) {
				$link_count += preg_match_all( '/https?:\/\/[^\s]+/i', $value );
			}
		}

		if ( $link_count > 3 ) {
			$spam_score += 30;
			$reasons[] = 'Excessive links';
		}

		// Check for spam keywords
		$spam_keywords = array(
			'viagra', 'cialis', 'casino', 'poker', 'lottery',
			'weight loss', 'diet pills', 'make money', 'work from home',
			'click here', 'buy now', 'free trial', 'risk free',
			'seo services', 'boost your', 'increase traffic', 'rank higher',
			'guest post', 'link exchange', 'sponsored post', 'backlinks',
			'digital marketing', 'web design services', 'grow your business',
			'limited time offer', 'act now', 'don\'t miss out',
		);

		$content = implode( ' ', array_values( $submission_data ) );
		$content_lower = strtolower( $content );

		foreach ( $spam_keywords as $keyword ) {
			if ( stripos( $content_lower, $keyword ) !== false ) {
				$spam_score += 20;
				$reasons[] = 'Spam keyword: ' . $keyword;
			}
		}

		// Check for gibberish
		if ( self::is_gibberish( $content ) ) {
			$spam_score += 40;
			$reasons[] = 'Gibberish content detected';
		}

		// Check for all caps
		$words = str_word_count( $content, 1 );
		$caps_words = 0;
		foreach ( $words as $word ) {
			if ( strlen( $word ) > 2 && $word === strtoupper( $word ) ) {
				$caps_words++;
			}
		}

		if ( $caps_words > count( $words ) * 0.3 ) {
			$spam_score += 15;
			$reasons[] = 'Excessive capital letters';
		}

		// Check for generic greetings without context
		$generic_patterns = array(
			'/^(hi|hello|hey)\s*(there|admin|webmaster|team)?[\s\.,!]*$/i',
			'/^(good\s*(morning|afternoon|evening|day))[\s\.,!]*$/i',
			'/^(greetings|dear\s*(sir|madam|admin))[\s\.,!]*$/i',
		);

		$message_field = '';
		foreach ( $submission_data as $key => $value ) {
			if ( is_string( $value ) && ( 
				stripos( $key, 'message' ) !== false || 
				stripos( $key, 'comment' ) !== false ||
				stripos( $key, 'content' ) !== false
			) ) {
				$message_field = trim( $value );
				break;
			}
		}

		if ( ! empty( $message_field ) ) {
			// Check for generic greetings
			$first_line = explode( "\n", $message_field )[0];
			foreach ( $generic_patterns as $pattern ) {
				if ( preg_match( $pattern, trim( $first_line ) ) ) {
					$spam_score += 25;
					$reasons[] = 'Generic greeting without context';
					break;
				}
			}

			// Check for very short vague messages
			if ( strlen( $message_field ) < 50 && count( $words ) < 10 ) {
				// Only penalize if it's a vague compliment without any context
				if ( preg_match( '/(great|nice|good|love|like)\s*(website|site|content|blog|article)/i', $message_field ) &&
					 ! preg_match( '/(test|testing|form test|contact test)/i', $message_field ) ) {
					$spam_score += 30;
					$reasons[] = 'Short vague compliment';
				}
			}
		}

		return array(
			'is_spam' => $spam_score >= 75,
			'spam_score' => min( $spam_score, 100 ),
			'reason' => implode( ', ', $reasons ),
			'provider' => 'rule-based',
		);
	}

	/**
	 * Extract email from submission data.
	 *
	 * @since    1.0.0
	 * @param    array    $submission_data    Submission data.
	 * @return   string                       Email address.
	 */
	public static function extract_email( $submission_data ) {
		foreach ( $submission_data as $key => $value ) {
			if ( is_string( $value ) && ( 
				stripos( $key, 'email' ) !== false || 
				stripos( $key, 'e-mail' ) !== false 
			) ) {
				$email = filter_var( $value, FILTER_VALIDATE_EMAIL );
				if ( $email ) {
					return $email;
				}
			}
		}

		// Try to find email in values
		foreach ( $submission_data as $value ) {
			if ( is_string( $value ) ) {
				if ( preg_match( '/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/i', $value, $matches ) ) {
					$email = filter_var( $matches[0], FILTER_VALIDATE_EMAIL );
					if ( $email ) {
						return $email;
					}
				}
			}
		}

		return '';
	}

	/**
	 * Get cache key for submission data.
	 *
	 * @since    1.0.0
	 * @param    array    $submission_data    Submission data.
	 * @return   string                       Cache key.
	 */
	private static function get_cache_key( $submission_data ) {
		// Sort data to ensure consistent key
		ksort( $submission_data );
		return md5( wp_json_encode( $submission_data ) );
	}

	/**
	 * Check if content is gibberish.
	 *
	 * @since    1.0.0
	 * @param    string    $content    Content to check.
	 * @return   bool                  True if gibberish.
	 */
	private static function is_gibberish( $content ) {
		// Simple gibberish detection
		// Check for excessive consonants
		$words = str_word_count( $content, 1 );
		$gibberish_count = 0;

		foreach ( $words as $word ) {
			if ( strlen( $word ) > 3 ) {
				$consonants = preg_replace( '/[aeiouAEIOU\s]/', '', $word );
				if ( strlen( $consonants ) / strlen( $word ) > 0.8 ) {
					$gibberish_count++;
				}
			}
		}

		return $gibberish_count > count( $words ) * 0.3;
	}
}