<?php
/**
 * Elementor Forms Integration.
 *
 * @since      1.0.0
 * @package    Smart_Form_Shield
 * @subpackage Smart_Form_Shield/integrations
 */

class Smart_Form_Shield_Elementor {

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
		
		// Check whitelist first
		$database = new Smart_Form_Shield_Database();
		$email = $this->extract_email( $submission_data );
		
		if ( ! empty( $email ) && $database->is_whitelisted( $email ) ) {
			$this->log_submission_to_database( $submission_data, $form_settings, 'whitelist', 0 );
			return;
		}

		// Check cache
		$cache_key = $this->get_cache_key( $submission_data );
		$cache = new Smart_Form_Shield_Cache();
		$cached_result = $cache->get( $cache_key );
		
		if ( $cached_result !== false ) {
			$this->apply_validation_result( $cached_result, $ajax_handler, $form_settings );
			return;
		}

		// Analyze with AI provider
		$provider = Smart_Form_Shield_Provider_Factory::get_primary_provider();
		
		if ( ! $provider ) {
			// No provider available, allow submission
			return;
		}

		$analysis = $provider->analyze( $submission_data );
		
		// Cache the result
		if ( ! isset( $analysis['error'] ) ) {
			$cache->set( $cache_key, $analysis, get_option( 'smart_form_shield_cache_duration', 3600 ) );
		}

		// Log submission
		$submission_id = $this->log_submission_to_database( $submission_data, $form_settings, $analysis );
		
		// Apply validation
		$this->apply_validation_result( $analysis, $ajax_handler, $form_settings );
		
		// Send notification if needed
		$this->maybe_send_notification( $analysis, $submission_data, $form_settings );
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
	 * Register Smart Form Shield action.
	 *
	 * @since    1.0.0
	 * @param    \ElementorPro\Modules\Forms\Registrars\Form_Actions_Registrar    $form_actions_registrar
	 */
	public function register_action( $form_actions_registrar ) {
		include_once SMART_FORM_SHIELD_PATH . 'integrations/elementor/class-elementor-action.php';
		
		$form_actions_registrar->register( new Smart_Form_Shield_Elementor_Action() );
	}

	/**
	 * Check if protection is enabled for form.
	 *
	 * @since    1.0.0
	 * @param    array    $form_settings    Form settings.
	 * @return   bool                       True if enabled.
	 */
	private function is_protection_enabled( $form_settings ) {
		return isset( $form_settings['smart_form_shield_enable'] ) && 
			$form_settings['smart_form_shield_enable'] === 'yes';
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
		return 'sfs_' . md5( wp_json_encode( $submission_data ) );
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
		$threshold = isset( $form_settings['smart_form_shield_threshold'] ) && $form_settings['smart_form_shield_threshold']
			? absint( $form_settings['smart_form_shield_threshold'] )
			: get_option( 'smart_form_shield_spam_threshold', 75 );
		
		if ( isset( $analysis['spam_score'] ) && $analysis['spam_score'] >= $threshold ) {
			$error_message = isset( $form_settings['smart_form_shield_error_message'] ) && $form_settings['smart_form_shield_error_message']
				? $form_settings['smart_form_shield_error_message']
				: __( 'Your submission has been blocked as potential spam. Please try again or contact support.', 'smart-form-shield' );
			
			$ajax_handler->add_error( 'smart-form-shield', $error_message );
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
		$database = new Smart_Form_Shield_Database();
		
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
			$threshold = get_option( 'smart_form_shield_spam_threshold', 75 );
			
			// Set status based on threshold, not is_spam
			$spam_score = isset( $analysis['spam_score'] ) ? $analysis['spam_score'] : 0;
			$data['status'] = $spam_score >= $threshold ? 'spam' : 'approved';
		}
		
		return $database->insert_submission( $data );
	}

	/**
	 * Maybe send notification email.
	 *
	 * @since    1.0.0
	 * @param    array    $analysis           Analysis result.
	 * @param    array    $submission_data    Submission data.
	 * @param    array    $form_settings      Form settings.
	 */
	private function maybe_send_notification( $analysis, $submission_data, $form_settings ) {
		if ( ! isset( $analysis['spam_score'] ) ) {
			return;
		}
		
		$threshold = get_option( 'smart_form_shield_notification_threshold', 90 );
		
		if ( $analysis['spam_score'] >= $threshold ) {
			$this->send_notification( $analysis, $submission_data, $form_settings );
		}
	}

	/**
	 * Send notification email.
	 *
	 * @since    1.0.0
	 * @param    array    $analysis           Analysis result.
	 * @param    array    $submission_data    Submission data.
	 * @param    array    $form_settings      Form settings.
	 */
	private function send_notification( $analysis, $submission_data, $form_settings ) {
		$to = get_option( 'smart_form_shield_notification_email', get_option( 'admin_email' ) );
		$form_name = isset( $form_settings['form_name'] ) ? $form_settings['form_name'] : __( 'Unknown Form', 'smart-form-shield' );
		
		$subject = sprintf(
			__( '[Smart Form Shield] High spam score detected on form: %s', 'smart-form-shield' ),
			$form_name
		);
		
		$message = sprintf(
			__( "A submission with a high spam score has been detected.\n\nForm: %s\nSpam Score: %d%%\nProvider: %s\n\nSubmission Data:\n%s\n\nView all submissions: %s", 'smart-form-shield' ),
			$form_name,
			$analysis['spam_score'],
			isset( $analysis['provider'] ) ? $analysis['provider'] : 'Unknown',
			print_r( $submission_data, true ),
			admin_url( 'admin.php?page=smart-form-shield-submissions' )
		);
		
		wp_mail( $to, $subject, $message );
	}
}