<?php
/**
 * Cache handler.
 *
 * @since      1.0.0
 * @package    Smart_Form_Shield
 * @subpackage Smart_Form_Shield/includes
 */

class Smart_Form_Shield_Cache {

	/**
	 * Cache prefix.
	 *
	 * @var string
	 */
	private $prefix = 'sfs_cache_';

	/**
	 * Get cached value.
	 *
	 * @since    1.0.0
	 * @param    string    $key    Cache key.
	 * @return   mixed              Cached value or false.
	 */
	public function get( $key ) {
		if ( ! $this->is_enabled() ) {
			return false;
		}

		return get_transient( $this->prefix . $key );
	}

	/**
	 * Set cache value.
	 *
	 * @since    1.0.0
	 * @param    string    $key         Cache key.
	 * @param    mixed     $value       Value to cache.
	 * @param    int       $expiration  Expiration time in seconds.
	 * @return   bool                   Success status.
	 */
	public function set( $key, $value, $expiration = 3600 ) {
		if ( ! $this->is_enabled() ) {
			return false;
		}

		return set_transient( $this->prefix . $key, $value, $expiration );
	}

	/**
	 * Delete cached value.
	 *
	 * @since    1.0.0
	 * @param    string    $key    Cache key.
	 * @return   bool              Success status.
	 */
	public function delete( $key ) {
		return delete_transient( $this->prefix . $key );
	}

	/**
	 * Flush all cache.
	 *
	 * @since    1.0.0
	 */
	public function flush() {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} 
				WHERE option_name LIKE %s 
				OR option_name LIKE %s",
				'_transient_' . $this->prefix . '%',
				'_transient_timeout_' . $this->prefix . '%'
			)
		);
	}

	/**
	 * Clean up expired cache.
	 *
	 * @since    1.0.0
	 */
	public function cleanup() {
		// WordPress automatically cleans up expired transients
		// This is here for any additional cleanup if needed
		delete_expired_transients();
	}

	/**
	 * Check if caching is enabled.
	 *
	 * @since    1.0.0
	 * @return   bool    True if enabled.
	 */
	private function is_enabled() {
		return (bool) get_option( 'smart_form_shield_cache_responses', true );
	}

	/**
	 * Get cache statistics.
	 *
	 * @since    1.0.0
	 * @return   array    Cache statistics.
	 */
	public function get_stats() {
		global $wpdb;

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->options} 
				WHERE option_name LIKE %s",
				'_transient_' . $this->prefix . '%'
			)
		);

		$size = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} 
				WHERE option_name LIKE %s",
				'_transient_' . $this->prefix . '%'
			)
		);

		return array(
			'entries' => intval( $count ),
			'size' => intval( $size ),
			'size_formatted' => size_format( $size ),
		);
	}
}