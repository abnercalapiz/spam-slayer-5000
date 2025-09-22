<?php
/**
 * AI Provider Interface.
 *
 * @since      1.0.0
 * @package    Smart_Form_Shield
 * @subpackage Smart_Form_Shield/providers
 */

interface Smart_Form_Shield_Provider_Interface {

	/**
	 * Analyze submission for spam.
	 *
	 * @since    1.0.0
	 * @param    array    $submission_data    Form submission data.
	 * @return   array                        Analysis result.
	 */
	public function analyze( $submission_data );

	/**
	 * Test provider connection.
	 *
	 * @since    1.0.0
	 * @return   bool    True if connection successful.
	 */
	public function test_connection();

	/**
	 * Get provider name.
	 *
	 * @since    1.0.0
	 * @return   string    Provider name.
	 */
	public function get_name();

	/**
	 * Get available models.
	 *
	 * @since    1.0.0
	 * @return   array    Available models.
	 */
	public function get_models();

	/**
	 * Get current model.
	 *
	 * @since    1.0.0
	 * @return   string    Current model.
	 */
	public function get_current_model();

	/**
	 * Set model.
	 *
	 * @since    1.0.0
	 * @param    string   $model    Model identifier.
	 * @return   void
	 */
	public function set_model( $model );

	/**
	 * Calculate cost for the request.
	 *
	 * @since    1.0.0
	 * @param    int      $tokens_used    Number of tokens used.
	 * @return   float                    Cost in USD.
	 */
	public function calculate_cost( $tokens_used );

	/**
	 * Check if provider is available.
	 *
	 * @since    1.0.0
	 * @return   bool    True if available.
	 */
	public function is_available();
}