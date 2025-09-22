<?php
/**
 * Elementor Form Action.
 *
 * @since      1.0.0
 * @package    Smart_Form_Shield
 * @subpackage Smart_Form_Shield/integrations
 */

use ElementorPro\Modules\Forms\Classes\Action_Base;

class Smart_Form_Shield_Elementor_Action extends Action_Base {

	/**
	 * Get action name.
	 *
	 * @since    1.0.0
	 * @return   string    Action name.
	 */
	public function get_name() {
		return 'smart_form_shield';
	}

	/**
	 * Get action label.
	 *
	 * @since    1.0.0
	 * @return   string    Action label.
	 */
	public function get_label() {
		return __( 'Smart Form Shield', 'smart-form-shield' );
	}

	/**
	 * Register action controls.
	 *
	 * @since    1.0.0
	 * @param    \Elementor\Widget_Base    $widget
	 */
	public function register_settings_section( $widget ) {
		$widget->start_controls_section(
			'section_smart_form_shield',
			[
				'label' => __( 'Smart Form Shield', 'smart-form-shield' ),
				'condition' => [
					'submit_actions' => $this->get_name(),
				],
			]
		);

		$widget->add_control(
			'smart_form_shield_enable',
			[
				'label' => __( 'Enable Spam Protection', 'smart-form-shield' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => __( 'Yes', 'smart-form-shield' ),
				'label_off' => __( 'No', 'smart-form-shield' ),
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);

		$widget->add_control(
			'smart_form_shield_threshold',
			[
				'label' => __( 'Spam Threshold', 'smart-form-shield' ),
				'type' => \Elementor\Controls_Manager::NUMBER,
				'min' => 0,
				'max' => 100,
				'step' => 5,
				'default' => get_option( 'smart_form_shield_spam_threshold', 75 ),
				'description' => __( 'Submissions with spam score above this threshold will be blocked (0-100)', 'smart-form-shield' ),
				'condition' => [
					'smart_form_shield_enable' => 'yes',
				],
			]
		);

		$widget->add_control(
			'smart_form_shield_error_message',
			[
				'label' => __( 'Error Message', 'smart-form-shield' ),
				'type' => \Elementor\Controls_Manager::TEXTAREA,
				'default' => __( 'Your submission has been blocked as potential spam. Please try again or contact support.', 'smart-form-shield' ),
				'placeholder' => __( 'Enter custom error message', 'smart-form-shield' ),
				'condition' => [
					'smart_form_shield_enable' => 'yes',
				],
			]
		);

		$widget->add_control(
			'smart_form_shield_whitelist_logged_in',
			[
				'label' => __( 'Whitelist Logged-in Users', 'smart-form-shield' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => __( 'Yes', 'smart-form-shield' ),
				'label_off' => __( 'No', 'smart-form-shield' ),
				'return_value' => 'yes',
				'default' => 'no',
				'description' => __( 'Automatically approve submissions from logged-in users', 'smart-form-shield' ),
				'condition' => [
					'smart_form_shield_enable' => 'yes',
				],
			]
		);

		$providers = $this->get_available_providers();
		if ( ! empty( $providers ) ) {
			$widget->add_control(
				'smart_form_shield_provider',
				[
					'label' => __( 'AI Provider', 'smart-form-shield' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'options' => $providers,
					'default' => get_option( 'smart_form_shield_primary_provider', 'openai' ),
					'description' => __( 'Select which AI provider to use for this form', 'smart-form-shield' ),
					'condition' => [
						'smart_form_shield_enable' => 'yes',
					],
				]
			);
		}

		$widget->end_controls_section();
	}

	/**
	 * Run action.
	 *
	 * @since    1.0.0
	 * @param    \ElementorPro\Modules\Forms\Classes\Form_Record    $record
	 * @param    \ElementorPro\Modules\Forms\Classes\Ajax_Handler   $ajax_handler
	 */
	public function run( $record, $ajax_handler ) {
		// Validation is handled in the validate_submission hook
		// This method is called after successful validation
		
		$form_settings = $record->get( 'form_settings' );
		
		if ( isset( $form_settings['smart_form_shield_enable'] ) && 
			$form_settings['smart_form_shield_enable'] === 'yes' ) {
			
			// Add success message if needed
			$ajax_handler->add_response_data( 'smart_form_shield', [
				'status' => 'validated',
				'message' => __( 'Form validated by Smart Form Shield', 'smart-form-shield' ),
			] );
		}
	}

	/**
	 * On export.
	 *
	 * @since    1.0.0
	 * @param    array    $element
	 */
	public function on_export( $element ) {
		// Remove sensitive data when exporting
		unset(
			$element['smart_form_shield_provider']
		);
		
		return $element;
	}

	/**
	 * Get available providers.
	 *
	 * @since    1.0.0
	 * @return   array    Available providers.
	 */
	private function get_available_providers() {
		$providers = array();
		$available = Smart_Form_Shield_Provider_Factory::get_available_providers();
		
		foreach ( $available as $key => $provider ) {
			$providers[ $key ] = $provider->get_name();
		}
		
		return $providers;
	}
}