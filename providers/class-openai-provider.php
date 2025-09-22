<?php
/**
 * OpenAI Provider Implementation.
 *
 * @since      1.0.0
 * @package    Smart_Form_Shield
 * @subpackage Smart_Form_Shield/providers
 */

class Smart_Form_Shield_OpenAI_Provider implements Smart_Form_Shield_Provider_Interface {

	/**
	 * API endpoint.
	 *
	 * @var string
	 */
	private $api_url = 'https://api.openai.com/v1/chat/completions';

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
		'gpt-5-nano-2025-08-07' => array(
			'name' => 'GPT-5 Nano',
			'input_price' => 0.0001,  // per 1K tokens
			'output_price' => 0.0003,  // per 1K tokens
			'max_tokens' => 128000,
		),
		'gpt-5-mini-2025-08-07' => array(
			'name' => 'GPT-5 Mini',
			'input_price' => 0.00015,  // per 1K tokens
			'output_price' => 0.0006,  // per 1K tokens
			'max_tokens' => 128000,
		),
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		$settings = get_option( 'smart_form_shield_openai_settings', array() );
		
		// Decrypt API key if needed
		if ( isset( $settings['api_key'] ) && ! empty( $settings['api_key'] ) ) {
			require_once SMART_FORM_SHIELD_PATH . 'includes/class-security.php';
			$decrypted = Smart_Form_Shield_Security::decrypt( $settings['api_key'] );
			$this->api_key = ! empty( $decrypted ) ? $decrypted : $settings['api_key'];
		} else {
			$this->api_key = '';
		}
		
		$this->model = isset( $settings['model'] ) && isset( $this->models[ $settings['model'] ] ) 
			? $settings['model'] 
			: 'gpt-5-nano-2025-08-07';
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
				'error' => __( 'Provider not available', 'smart-form-shield' ),
			);
		}

		$prompt = $this->build_prompt( $submission_data );
		$start_time = microtime( true );

		try {
			// Build request body - GPT-5 models have different requirements
			$body_params = array(
				'model' => $this->model,
				'messages' => array(
					array(
						'role' => 'system',
						'content' => $this->get_system_prompt(),
					),
					array(
						'role' => 'user',
						'content' => $prompt,
					),
				),
				'response_format' => array( 'type' => 'json_object' ),
			);
			
			// Configure parameters based on model
			if ( strpos( $this->model, 'gpt-5' ) === 0 ) {
				// GPT-5 models: use max_completion_tokens and no temperature
				$body_params['max_completion_tokens'] = 150;
				// Temperature must be default (1) for GPT-5, so we don't set it
			} else {
				// Other models: use max_tokens and custom temperature
				$body_params['max_tokens'] = 150;
				$body_params['temperature'] = 0.3;
			}

			$response = wp_remote_post( $this->api_url, array(
				'timeout' => 30,
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->api_key,
					'Content-Type' => 'application/json',
				),
				'body' => wp_json_encode( $body_params ),
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

			if ( ! isset( $data['choices'][0]['message']['content'] ) ) {
				throw new Exception( __( 'Invalid response format', 'smart-form-shield' ) );
			}

			$analysis = json_decode( $data['choices'][0]['message']['content'], true );
			
			if ( ! is_array( $analysis ) ) {
				throw new Exception( __( 'Invalid analysis format', 'smart-form-shield' ) );
			}

			// Calculate cost
			$tokens_used = isset( $data['usage']['total_tokens'] ) ? $data['usage']['total_tokens'] : 0;
			$cost = $this->calculate_cost( $tokens_used );

			// Log API call
			$database = new Smart_Form_Shield_Database();
			$database->log_api_call( array(
				'provider' => 'openai',
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
			$database = new Smart_Form_Shield_Database();
			$database->log_api_call( array(
				'provider' => 'openai',
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
		if ( empty( $this->api_key ) ) {
			return array( 'error' => 'API key is empty' );
		}

		// Build request body - GPT-5 models use max_completion_tokens instead of max_tokens
		$body_params = array(
			'model' => $this->model,
			'messages' => array(
				array(
					'role' => 'user',
					'content' => 'Test connection',
				),
			),
		);
		
		// Use appropriate parameter based on model
		if ( strpos( $this->model, 'gpt-5' ) === 0 ) {
			$body_params['max_completion_tokens'] = 5;
		} else {
			$body_params['max_tokens'] = 5;
		}

		$response = wp_remote_post( $this->api_url, array(
			'timeout' => 10,
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->api_key,
				'Content-Type' => 'application/json',
			),
			'body' => wp_json_encode( $body_params ),
		) );

		if ( is_wp_error( $response ) ) {
			return array( 'error' => 'Request error: ' . $response->get_error_message() );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		
		if ( $response_code !== 200 ) {
			$data = json_decode( $body, true );
			$error_message = 'HTTP ' . $response_code;
			
			// OpenAI API error structure
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

		return true;
	}

	/**
	 * Get provider name.
	 *
	 * @return string Provider name.
	 */
	public function get_name() {
		return 'OpenAI';
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
	 * Check if provider is available.
	 *
	 * @return bool True if available.
	 */
	public function is_available() {
		$settings = get_option( 'smart_form_shield_openai_settings', array() );
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
			"Analyze the following form submission for spam. Consider patterns, suspicious content, and typical spam indicators.\n\nForm data:\n%s",
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
}