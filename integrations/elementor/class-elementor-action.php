<?php
/**
 * Elementor Form Action.
 *
 * @since      1.0.0
 * @package    Spam_Slayer_5000
 * @subpackage Spam_Slayer_5000/integrations
 */

use ElementorPro\Modules\Forms\Classes\Action_Base;

class Spam_Slayer_5000_Elementor_Action extends Action_Base {

	/**
	 * Get action name.
	 *
	 * @since    1.0.0
	 * @return   string    Action name.
	 */
	public function get_name() {
		return 'spam_slayer_5000';
	}

	/**
	 * Get action label.
	 *
	 * @since    1.0.0
	 * @return   string    Action label.
	 */
	public function get_label() {
		return __( 'Spam Slayer 5000', 'spam-slayer-5000' );
	}

	/**
	 * Register action controls.
	 *
	 * @since    1.0.0
	 * @param    \Elementor\Widget_Base    $widget
	 */
	public function register_settings_section( $widget ) {
		$widget->start_controls_section(
			'section_spam_slayer_5000',
			[
				'label' => __( 'Spam Slayer 5000', 'spam-slayer-5000' ),
				'condition' => [
					'submit_actions' => $this->get_name(),
				],
			]
		);

		$widget->add_control(
			'spam_slayer_5000_enable',
			[
				'label' => __( 'Enable Spam Protection', 'spam-slayer-5000' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => __( 'Yes', 'spam-slayer-5000' ),
				'label_off' => __( 'No', 'spam-slayer-5000' ),
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);

		$widget->add_control(
			'spam_slayer_5000_threshold',
			[
				'label' => __( 'Spam Threshold', 'spam-slayer-5000' ),
				'type' => \Elementor\Controls_Manager::NUMBER,
				'min' => 0,
				'max' => 100,
				'step' => 5,
				'default' => get_option( 'spam_slayer_5000_spam_threshold', 75 ),
				'description' => __( 'Submissions with spam score above this threshold will be blocked (0-100)', 'spam-slayer-5000' ),
				'condition' => [
					'spam_slayer_5000_enable' => 'yes',
				],
			]
		);

		$widget->add_control(
			'spam_slayer_5000_error_message',
			[
				'label' => __( 'Error Message', 'spam-slayer-5000' ),
				'type' => \Elementor\Controls_Manager::TEXTAREA,
				'default' => __( 'Your submission has been blocked as potential spam. Please try again or contact support.', 'spam-slayer-5000' ),
				'placeholder' => __( 'Enter custom error message', 'spam-slayer-5000' ),
				'condition' => [
					'spam_slayer_5000_enable' => 'yes',
				],
			]
		);

		$widget->add_control(
			'spam_slayer_5000_whitelist_logged_in',
			[
				'label' => __( 'Whitelist Logged-in Users', 'spam-slayer-5000' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => __( 'Yes', 'spam-slayer-5000' ),
				'label_off' => __( 'No', 'spam-slayer-5000' ),
				'return_value' => 'yes',
				'default' => 'no',
				'description' => __( 'Automatically approve submissions from logged-in users', 'spam-slayer-5000' ),
				'condition' => [
					'spam_slayer_5000_enable' => 'yes',
				],
			]
		);

		$providers = $this->get_available_providers();
		if ( ! empty( $providers ) ) {
			$widget->add_control(
				'spam_slayer_5000_provider',
				[
					'label' => __( 'AI Provider', 'spam-slayer-5000' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'options' => $providers,
					'default' => get_option( 'spam_slayer_5000_primary_provider', 'openai' ),
					'description' => __( 'Select which AI provider to use for this form', 'spam-slayer-5000' ),
					'condition' => [
						'spam_slayer_5000_enable' => 'yes',
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
		
		if ( isset( $form_settings['spam_slayer_5000_enable'] ) && 
			$form_settings['spam_slayer_5000_enable'] === 'yes' ) {
			
			// Add success message if needed
			$ajax_handler->add_response_data( 'spam_slayer_5000', [
				'status' => 'validated',
				'message' => __( 'Form validated by Spam Slayer 5000', 'spam-slayer-5000' ),
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
			$element['spam_slayer_5000_provider']
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
		$available = Spam_Slayer_5000_Provider_Factory::get_available_providers();
		
		foreach ( $available as $key => $provider ) {
			$providers[ $key ] = $provider->get_name();
		}
		
		return $providers;
	}
}