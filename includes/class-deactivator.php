<?php
/**
 * Fired during plugin deactivation.
 *
 * @since      1.0.0
 * @package    Smart_Form_Shield
 * @subpackage Smart_Form_Shield/includes
 */

class Smart_Form_Shield_Deactivator {

	/**
	 * Deactivation hook callback.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		// Unschedule cron events
		$timestamp = wp_next_scheduled( 'smart_form_shield_daily_cleanup' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'smart_form_shield_daily_cleanup' );
		}

		// Clear any transients
		self::clear_transients();

		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Clear all plugin transients.
	 *
	 * @since    1.0.0
	 */
	private static function clear_transients() {
		global $wpdb;
		
		// Delete all transients with our prefix
		$wpdb->query(
			"DELETE FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_sfs_%' 
			OR option_name LIKE '_transient_timeout_sfs_%'"
		);
	}
}