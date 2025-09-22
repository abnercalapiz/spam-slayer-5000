<?php
/**
 * Validator handler.
 *
 * @since      1.0.0
 * @package    Smart_Form_Shield
 * @subpackage Smart_Form_Shield/includes
 */

class Smart_Form_Shield_Validator {

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
			'use_cache' => true,
			'provider' => null,
			'threshold' => null,
		);

		$options = wp_parse_args( $options, $defaults );

		// Check whitelist first
		if ( $options['check_whitelist'] ) {
			$email = self::extract_email( $submission_data );
			if ( ! empty( $email ) ) {
				$database = new Smart_Form_Shield_Database();
				if ( $database->is_whitelisted( $email ) ) {
					return array(
						'is_spam' => false,
						'spam_score' => 0,
						'reason' => 'Whitelisted email',
						'status' => 'whitelist',
					);
				}
			}
		}

		// Check cache
		if ( $options['use_cache'] ) {
			$cache_key = self::get_cache_key( $submission_data );
			$cache = new Smart_Form_Shield_Cache();
			$cached_result = $cache->get( $cache_key );

			if ( $cached_result !== false ) {
				$cached_result['from_cache'] = true;
				return $cached_result;
			}
		}

		// Get provider
		if ( $options['provider'] instanceof Smart_Form_Shield_Provider_Interface ) {
			$provider = $options['provider'];
		} else {
			$provider = Smart_Form_Shield_Provider_Factory::get_primary_provider();
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

		// Apply threshold
		$threshold = $options['threshold'] !== null 
			? $options['threshold'] 
			: get_option( 'smart_form_shield_spam_threshold', 75 );

		if ( isset( $result['spam_score'] ) ) {
			$result['is_spam'] = $result['spam_score'] >= $threshold;
		}

		// Cache result
		if ( $options['use_cache'] && ! isset( $result['error'] ) ) {
			$cache = new Smart_Form_Shield_Cache();
			$cache->set( 
				$cache_key, 
				$result, 
				get_option( 'smart_form_shield_cache_duration', 3600 ) 
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