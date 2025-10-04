<?php
/**
 * Elementor Forms Integration.
 *
 * @since      1.0.0
 * @package    Spam_Slayer_5000
 * @subpackage Spam_Slayer_5000/integrations
 */

class Spam_Slayer_5000_Elementor {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $plugin_name
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $version
	 */
	private $version;

	/**
	 * Initialize the class.
	 *
	 * @since    1.0.0
	 * @param    string    $plugin_name    The name of this plugin.
	 * @param    string    $version        The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Validate form submission.
	 *
	 * @since    1.0.0
	 * @param    array                                    $record    Form record.
	 * @param    \ElementorPro\Modules\Forms\Classes\Ajax_Handler    $ajax_handler    Ajax handler.
	 */
	public function validate_submission( $record, $ajax_handler ) {
		$form_settings = $record->get( 'form_settings' );
		
		// Check if protection is enabled
		if ( ! $this->is_protection_enabled( $form_settings ) ) {
			return;
		}

		$submission_data = $this->get_submission_data( $record );
		
		// Use the centralized validator
		$threshold = get_option( 'spam_slayer_5000_spam_threshold', 75 );
		$validation_options = array(
			'check_whitelist' => true,
			'check_blocklist' => true,
			'use_cache' => true,
			'provider' => null,
			'threshold' => $threshold,
		);
		
		$analysis = Spam_Slayer_5000_Validator::validate_submission( $submission_data, $validation_options );
		
		// Log submission
		$submission_id = $this->log_submission_to_database( $submission_data, $form_settings, $analysis );
		
		// Apply validation
		$this->apply_validation_result( $analysis, $ajax_handler, $form_settings );
	}

	/**
	 * Log submission after new record.
	 *
	 * @since    1.0.0
	 * @param    array                                    $record    Form record.
	 * @param    \ElementorPro\Modules\Forms\Classes\Ajax_Handler    $ajax_handler    Ajax handler.
	 */
	public function log_submission( $record, $ajax_handler ) {
		// This is called after successful submission
		// We've already logged in validate_submission
		return;
	}

	/**
	 * Register Spam Slayer 5000 action.
	 *
	 * @since    1.0.0
	 * @param    \ElementorPro\Modules\Forms\Registrars\Form_Actions_Registrar    $form_actions_registrar
	 */
	public function register_action( $form_actions_registrar ) {
		include_once SPAM_SLAYER_5000_PATH . 'integrations/elementor/class-elementor-action.php';
		
		$form_actions_registrar->register( new Spam_Slayer_5000_Elementor_Action() );
	}

	/**
	 * Check if protection is enabled for form.
	 *
	 * @since    1.0.0
	 * @param    array    $form_settings    Form settings.
	 * @return   bool                       True if enabled.
	 */
	private function is_protection_enabled( $form_settings ) {
		return isset( $form_settings['spam_slayer_5000_enable'] ) && 
			$form_settings['spam_slayer_5000_enable'] === 'yes';
	}

	/**
	 * Get submission data from record.
	 *
	 * @since    1.0.0
	 * @param    object    $record    Form record.
	 * @return   array                Submission data.
	 */
	private function get_submission_data( $record ) {
		$submission_data = array();
		$fields = $record->get( 'fields' );
		
		foreach ( $fields as $field_id => $field ) {
			if ( ! empty( $field['value'] ) ) {
				$submission_data[ $field['title'] ] = $field['value'];
			}
		}
		
		return $submission_data;
	}

	/**
	 * Extract email from submission data.
	 *
	 * @since    1.0.0
	 * @param    array    $submission_data    Submission data.
	 * @return   string                       Email address.
	 */
	private function extract_email( $submission_data ) {
		foreach ( $submission_data as $key => $value ) {
			if ( stripos( $key, 'email' ) !== false || stripos( $key, 'e-mail' ) !== false ) {
				$email = filter_var( $value, FILTER_VALIDATE_EMAIL );
				if ( $email ) {
					return $email;
				}
			}
		}
		
		// Try to find email in values
		foreach ( $submission_data as $value ) {
			if ( is_string( $value ) ) {
				$email = filter_var( $value, FILTER_VALIDATE_EMAIL );
				if ( $email ) {
					return $email;
				}
			}
		}
		
		return '';
	}

	/**
	 * Get cache key for submission.
	 *
	 * @since    1.0.0
	 * @param    array    $submission_data    Submission data.
	 * @return   string                       Cache key.
	 */
	private function get_cache_key( $submission_data ) {
		return 'ss5k_' . md5( wp_json_encode( $submission_data ) );
	}

	/**
	 * Apply validation result.
	 *
	 * @since    1.0.0
	 * @param    array     $analysis          Analysis result.
	 * @param    object    $ajax_handler      Ajax handler.
	 * @param    array     $form_settings     Form settings.
	 */
	private function apply_validation_result( $analysis, $ajax_handler, $form_settings ) {
		$threshold = isset( $form_settings['spam_slayer_5000_threshold'] ) && $form_settings['spam_slayer_5000_threshold']
			? absint( $form_settings['spam_slayer_5000_threshold'] )
			: get_option( 'spam_slayer_5000_spam_threshold', 75 );
		
		if ( isset( $analysis['spam_score'] ) && $analysis['spam_score'] >= $threshold ) {
			$error_message = isset( $form_settings['spam_slayer_5000_error_message'] ) && $form_settings['spam_slayer_5000_error_message']
				? $form_settings['spam_slayer_5000_error_message']
				: __( 'Your submission has been blocked as potential spam. Please try again or contact support.', 'spam-slayer-5000' );
			
			$ajax_handler->add_error( 'spam-slayer-5000', $error_message );
		}
	}

	/**
	 * Log submission to database.
	 *
	 * @since    1.0.0
	 * @param    array         $submission_data    Submission data.
	 * @param    array         $form_settings      Form settings.
	 * @param    array|string  $analysis           Analysis result or status.
	 * @param    float         $spam_score         Spam score if status provided.
	 * @return   int                               Submission ID.
	 */
	private function log_submission_to_database( $submission_data, $form_settings, $analysis, $spam_score = 0 ) {
		$database = new Spam_Slayer_5000_Database();
		
		$data = array(
			'form_type' => 'elementor_forms',
			'form_id' => isset( $form_settings['form_name'] ) ? $form_settings['form_name'] : 'unknown',
			'submission_data' => $submission_data,
		);
		
		if ( is_string( $analysis ) ) {
			// Status provided (e.g., 'whitelist')
			$data['status'] = $analysis;
			$data['spam_score'] = $spam_score;
		} else {
			// Full analysis provided
			$data['spam_score'] = isset( $analysis['spam_score'] ) ? $analysis['spam_score'] : 0;
			$data['provider_used'] = isset( $analysis['provider'] ) ? $analysis['provider'] : null;
			$data['provider_response'] = $analysis;
			// Get threshold
			$threshold = get_option( 'spam_slayer_5000_spam_threshold', 75 );
			
			// Set status based on threshold, not is_spam
			$spam_score = isset( $analysis['spam_score'] ) ? $analysis['spam_score'] : 0;
			$data['status'] = $spam_score >= $threshold ? 'spam' : 'approved';
		}
		
		return $database->insert_submission( $data );
	}


}