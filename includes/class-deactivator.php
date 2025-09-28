<?php
/**
 * Fired during plugin deactivation.
 *
 * @since      1.0.0
 * @package    Spam_Slayer_5000
 * @subpackage Spam_Slayer_5000/includes
 */

class Spam_Slayer_5000_Deactivator {

	/**
	 * Deactivation hook callback.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		// Unschedule cron events
		$timestamp = wp_next_scheduled( 'spam_slayer_5000_daily_cleanup' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'spam_slayer_5000_daily_cleanup' );
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
			WHERE option_name LIKE '_transient_ss5k_%' 
			OR option_name LIKE '_transient_timeout_ss5k_%'"
		);
	}
}