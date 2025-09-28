<?php
/**
 * Claude Provider Implementation.
 *
 * @since      1.0.0
 * @package    Spam_Slayer_5000
 * @subpackage Spam_Slayer_5000/providers
 */

class Spam_Slayer_5000_Claude_Provider implements Spam_Slayer_5000_Provider_Interface {

	/**
	 * API endpoint.
	 *
	 * @var string
	 */
	private $api_url = 'https://api.anthropic.com/v1/messages';

	/**
	 * API key.
	 *
	 * @var string
	 */
	private $api_key;

	/**
	 * Current model.
	 *
	 * @var string
	 */
	private $model;

	/**
	 * Available models with pricing.
	 *
	 * @var array
	 */
	private $models = array(
		'claude-3-5-haiku-latest' => array(
			'name' => 'Claude 3.5 Haiku Latest',
			'input_price' => 0.001,  // per 1K tokens
			'output_price' => 0.005,  // per 1K tokens
			'max_tokens' => 200000,  // Context window size
		),
		'claude-3-7-sonnet-latest' => array(
			'name' => 'Claude 3.7 Sonnet Latest',
			'input_price' => 0.003,  // per 1K tokens
			'output_price' => 0.015,  // per 1K tokens
			'max_tokens' => 200000,  // Context window size
		),
		'claude-3-haiku-20240307' => array(
			'name' => 'Claude 3 Haiku',
			'input_price' => 0.00025,  // $0.25 per 1M input tokens
			'output_price' => 0.00125,  // $1.25 per 1M output tokens
			'max_tokens' => 200000,  // Context window size
		),
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		$settings = get_option( 'spam_slayer_5000_claude_settings', array() );
		
		// Debug logging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Claude constructor - Settings: ' . print_r( $settings, true ) );
		}
		
		// Decrypt API key if needed
		if ( isset( $settings['api_key'] ) && ! empty( $settings['api_key'] ) ) {
			require_once SPAM_SLAYER_5000_PATH . 'includes/class-security.php';
			$decrypted = Spam_Slayer_5000_Security::decrypt( $settings['api_key'] );
			$this->api_key = ! empty( $decrypted ) ? $decrypted : $settings['api_key'];
			
			// Debug logging
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Claude constructor - Settings API key length: ' . strlen( $settings['api_key'] ) );
				error_log( 'Claude constructor - Decrypted API key length: ' . strlen( $decrypted ) );
				error_log( 'Claude constructor - Using decrypted: ' . ( ! empty( $decrypted ) ? 'yes' : 'no' ) );
				error_log( 'Claude constructor - Final API key length: ' . strlen( $this->api_key ) );
			}
		} else {
			$this->api_key = '';
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Claude constructor - No API key in settings' );
			}
		}
		
		$this->model = isset( $settings['model'] ) && isset( $this->models[ $settings['model'] ] ) 
			? $settings['model'] 
			: 'claude-3-5-haiku-latest';
	}

	/**
	 * Analyze submission for spam.
	 *
	 * @param array $submission_data Form submission data.
	 * @return array Analysis result.
	 */
	public function analyze( $submission_data ) {
		if ( ! $this->is_available() ) {
			return array(
				'is_spam' => false,
				'spam_score' => 0,
				'provider' => $this->get_name(),
				'error' => __( 'Provider not available', 'spam-slayer-5000' ),
			);
		}

		$prompt = $this->build_prompt( $submission_data );
		$start_time = microtime( true );

		try {
			$response = wp_remote_post( $this->api_url, array(
				'timeout' => 30,
				'headers' => array(
					'x-api-key' => $this->api_key,
					'anthropic-version' => '2023-06-01',
					'Content-Type' => 'application/json',
				),
				'body' => wp_json_encode( array(
					'model' => $this->model,
					'messages' => array(
						array(
							'role' => 'user',
							'content' => $prompt,
						),
					),
					'system' => $this->get_system_prompt(),
					'max_tokens' => 150,
					'temperature' => 0.3,
				) ),
			) );

			$response_time = microtime( true ) - $start_time;

			if ( is_wp_error( $response ) ) {
				throw new Exception( $response->get_error_message() );
			}

			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );

			if ( isset( $data['error'] ) ) {
				throw new Exception( $data['error']['message'] );
			}

			if ( ! isset( $data['content'][0]['text'] ) ) {
				throw new Exception( __( 'Invalid response format', 'spam-slayer-5000' ) );
			}

			// Extract JSON from response
			$response_text = $data['content'][0]['text'];
			preg_match( '/\{[^}]+\}/', $response_text, $matches );
			
			if ( empty( $matches[0] ) ) {
				throw new Exception( __( 'Could not parse response', 'spam-slayer-5000' ) );
			}

			$analysis = json_decode( $matches[0], true );
			
			if ( ! is_array( $analysis ) ) {
				throw new Exception( __( 'Invalid analysis format', 'spam-slayer-5000' ) );
			}

			// Get actual token counts from API response
			$input_tokens = isset( $data['usage']['input_tokens'] ) ? $data['usage']['input_tokens'] : 0;
			$output_tokens = isset( $data['usage']['output_tokens'] ) ? $data['usage']['output_tokens'] : 0;
			$tokens_used = $input_tokens + $output_tokens;
			
			// Calculate cost using actual token counts
			$cost = $this->calculate_cost_with_actual_tokens( $input_tokens, $output_tokens );

			// Log API call
			$database = new Spam_Slayer_5000_Database();
			$database->log_api_call( array(
				'provider' => 'claude',
				'model' => $this->model,
				'request_data' => array( 'prompt' => $prompt ),
				'response_data' => $analysis,
				'tokens_used' => $tokens_used,
				'cost' => $cost,
				'response_time' => $response_time,
				'status' => 'success',
			) );

			return array(
				'is_spam' => isset( $analysis['is_spam'] ) ? (bool) $analysis['is_spam'] : false,
				'spam_score' => isset( $analysis['spam_score'] ) ? (float) $analysis['spam_score'] : 0,
				'reason' => isset( $analysis['reason'] ) ? $analysis['reason'] : '',
				'provider' => $this->get_name(),
				'model' => $this->model,
				'tokens_used' => $tokens_used,
				'cost' => $cost,
				'response_time' => $response_time,
			);

		} catch ( Exception $e ) {
			// Log error
			$database = new Spam_Slayer_5000_Database();
			$database->log_api_call( array(
				'provider' => 'claude',
				'model' => $this->model,
				'request_data' => array( 'prompt' => $prompt ),
				'response_data' => null,
				'tokens_used' => 0,
				'cost' => 0,
				'response_time' => microtime( true ) - $start_time,
				'status' => 'error',
				'error_message' => $e->getMessage(),
			) );

			return array(
				'is_spam' => false,
				'spam_score' => 0,
				'provider' => $this->get_name(),
				'error' => $e->getMessage(),
			);
		}
	}

	/**
	 * Test provider connection.
	 *
	 * @return bool|array True if connection successful, or array with error details.
	 */
	public function test_connection() {
		try {
			// Enhanced debug logging
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Claude test_connection - Starting test' );
				error_log( 'Claude test_connection - API key exists: ' . ( ! empty( $this->api_key ) ? 'yes' : 'no' ) );
				error_log( 'Claude test_connection - API key length: ' . strlen( $this->api_key ) );
				error_log( 'Claude test_connection - API key first 10 chars: ' . substr( $this->api_key, 0, 10 ) . '...' );
				error_log( 'Claude test_connection - Model: ' . $this->model );
			}

			if ( empty( $this->api_key ) ) {
				return array( 'error' => 'API key is empty' );
			}

		$response = wp_remote_post( $this->api_url, array(
			'timeout' => 10,
			'headers' => array(
				'x-api-key' => $this->api_key,
				'anthropic-version' => '2023-06-01',
				'Content-Type' => 'application/json',
			),
			'body' => wp_json_encode( array(
				'model' => $this->model,
				'messages' => array(
					array(
						'role' => 'user',
						'content' => 'Test connection',
					),
				),
				'max_tokens' => 5,
			) ),
		) );

		if ( is_wp_error( $response ) ) {
			return array( 'error' => 'Request error: ' . $response->get_error_message() );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		
		// Debug response
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Claude test_connection - Response code: ' . $response_code );
			error_log( 'Claude test_connection - Response body (first 200 chars): ' . substr( $body, 0, 200 ) );
		}
		
		if ( $response_code !== 200 ) {
			$data = json_decode( $body, true );
			$error_message = 'HTTP ' . $response_code;
			
			// Claude API error structure
			if ( isset( $data['error']['message'] ) ) {
				$error_message = $data['error']['message'];
			} else if ( isset( $data['error'] ) && is_string( $data['error'] ) ) {
				$error_message = $data['error'];
			} else if ( isset( $data['message'] ) ) {
				$error_message = $data['message'];
			}
			
			// Add response body for debugging if no clear error message
			if ( $error_message === 'HTTP ' . $response_code && ! empty( $body ) ) {
				$error_message .= ' - Response: ' . substr( $body, 0, 200 );
			}
			
			return array( 'error' => 'API error: ' . $error_message );
		}

		// Log success before returning
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Claude test_connection - Success, returning true' );
		}

		return true;
		
		} catch ( Exception $e ) {
			// Catch any unexpected exceptions
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Claude test_connection - Exception: ' . $e->getMessage() );
			}
			return array( 'error' => 'Unexpected error: ' . $e->getMessage() );
		}
		
		// This should never be reached, but just in case
		return array( 'error' => 'Unknown error occurred' );
	}

	/**
	 * Get provider name.
	 *
	 * @return string Provider name.
	 */
	public function get_name() {
		return 'Claude';
	}

	/**
	 * Get available models.
	 *
	 * @return array Available models.
	 */
	public function get_models() {
		return $this->models;
	}

	/**
	 * Get current model.
	 *
	 * @return string Current model.
	 */
	public function get_current_model() {
		return $this->model;
	}

	/**
	 * Set model.
	 *
	 * @param string $model Model identifier.
	 */
	public function set_model( $model ) {
		if ( isset( $this->models[ $model ] ) ) {
			$this->model = $model;
		}
	}

	/**
	 * Calculate cost for the request.
	 *
	 * @param int $tokens_used Number of tokens used.
	 * @return float Cost in USD.
	 */
	public function calculate_cost( $tokens_used ) {
		if ( ! isset( $this->models[ $this->model ] ) ) {
			return 0;
		}

		$model_info = $this->models[ $this->model ];
		
		// Rough estimate: 75% input, 25% output
		$input_tokens = $tokens_used * 0.75;
		$output_tokens = $tokens_used * 0.25;
		
		$input_cost = ( $input_tokens / 1000 ) * $model_info['input_price'];
		$output_cost = ( $output_tokens / 1000 ) * $model_info['output_price'];
		
		return round( $input_cost + $output_cost, 6 );
	}

	/**
	 * Calculate cost with actual token counts.
	 *
	 * @param int $input_tokens Number of input tokens.
	 * @param int $output_tokens Number of output tokens.
	 * @return float Cost in USD.
	 */
	public function calculate_cost_with_actual_tokens( $input_tokens, $output_tokens ) {
		if ( ! isset( $this->models[ $this->model ] ) ) {
			return 0;
		}

		$model_info = $this->models[ $this->model ];
		
		$input_cost = ( $input_tokens / 1000 ) * $model_info['input_price'];
		$output_cost = ( $output_tokens / 1000 ) * $model_info['output_price'];
		
		return round( $input_cost + $output_cost, 6 );
	}

	/**
	 * Check if provider is available.
	 *
	 * @return bool True if available.
	 */
	public function is_available() {
		$settings = get_option( 'spam_slayer_5000_claude_settings', array() );
		return ! empty( $settings['api_key'] ) && ! empty( $settings['enabled'] );
	}

	/**
	 * Build prompt for analysis.
	 *
	 * @param array $submission_data Submission data.
	 * @return string Prompt.
	 */
	private function build_prompt( $submission_data ) {
		$fields = array();
		
		foreach ( $submission_data as $key => $value ) {
			if ( is_array( $value ) ) {
				$value = implode( ', ', $value );
			}
			$fields[] = sprintf( '%s: %s', $key, $value );
		}
		
		return sprintf(
			"Please analyze the following form submission for spam. Consider patterns, suspicious content, and typical spam indicators.\n\nForm data:\n%s\n\nReturn your analysis as a JSON object.",
			implode( "\n", $fields )
		);
	}

	/**
	 * Get system prompt.
	 *
	 * @return string System prompt.
	 */
	private function get_system_prompt() {
		return 'You are an expert spam detection system specialized in identifying spam in contact form submissions. 
		
		Analyze the submission and return ONLY a JSON object with these exact fields:
		{
			"is_spam": boolean,
			"spam_score": number (0-100),
			"reason": string
		}
		
		Spam indicators to check:
		1. Generic/template messages ("I want to increase your traffic", "Great website")
		2. Excessive URLs or promotional content
		3. SEO/marketing service offers
		4. Cryptocurrency/investment schemes
		5. Adult content or inappropriate language
		6. Gibberish text or random character strings
		7. Suspicious email patterns (temporary emails, numeric sequences)
		8. Form field misuse (URLs in name fields, keywords stuffing)
		9. Non-contextual or irrelevant content
		10. Poor grammar combined with promotional intent
		
		Legitimate indicators:
		- Specific questions or requests related to services
		- Personal details and context
		- Professional inquiries with clear intent
		- Proper use of form fields
		
		Be strict with obvious spam but careful not to flag legitimate business inquiries.
		Score 70+ for likely spam, 90+ for definite spam.';
	}

	/**
	 * Estimate tokens for Claude.
	 *
	 * @param string $text Text to estimate.
	 * @return int Estimated token count.
	 */
	private function estimate_tokens( $text ) {
		// Rough estimate: 1 token per 4 characters
		return ceil( strlen( $text ) / 4 );
	}
}