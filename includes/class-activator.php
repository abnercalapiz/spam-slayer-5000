<?php
/**
 * Fired during plugin activation.
 *
 * @since      1.0.0
 * @package    Smart_Form_Shield
 * @subpackage Smart_Form_Shield/includes
 */

class Smart_Form_Shield_Activator {

	/**
	 * Activation hook callback.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		self::create_database_tables();
		self::set_default_options();
		self::create_upload_directory();
		
		// Schedule cron events
		if ( ! wp_next_scheduled( 'smart_form_shield_daily_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'smart_form_shield_daily_cleanup' );
		}
		
		// Flush rewrite rules for REST API endpoints
		flush_rewrite_rules();
	}

	/**
	 * Create database tables.
	 *
	 * @since    1.0.0
	 */
	private static function create_database_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		// Ensure table names are defined
		if ( function_exists( 'smart_form_shield_define_tables' ) ) {
			smart_form_shield_define_tables();
		}
		
		// Use direct table names as fallback if constants aren't defined
		$submissions_table = defined( 'SMART_FORM_SHIELD_SUBMISSIONS_TABLE' ) 
			? SMART_FORM_SHIELD_SUBMISSIONS_TABLE 
			: $wpdb->prefix . 'sfs_submissions';
		$sql_submissions = "CREATE TABLE IF NOT EXISTS $submissions_table (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			form_type varchar(50) NOT NULL,
			form_id varchar(100) NOT NULL,
			submission_data longtext NOT NULL,
			spam_score float DEFAULT 0,
			provider_used varchar(50) DEFAULT NULL,
			provider_response longtext DEFAULT NULL,
			status enum('pending','approved','spam','whitelist') DEFAULT 'pending',
			ip_address varchar(45) DEFAULT NULL,
			user_agent text DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			INDEX idx_form_type (form_type),
			INDEX idx_form_id (form_id),
			INDEX idx_status (status),
			INDEX idx_created_at (created_at)
		) $charset_collate;";

		// API logs table
		$api_logs_table = defined( 'SMART_FORM_SHIELD_API_LOGS_TABLE' ) 
			? SMART_FORM_SHIELD_API_LOGS_TABLE 
			: $wpdb->prefix . 'sfs_api_logs';
		$sql_api_logs = "CREATE TABLE IF NOT EXISTS $api_logs_table (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			provider varchar(50) NOT NULL,
			model varchar(100) DEFAULT NULL,
			request_data text NOT NULL,
			response_data text DEFAULT NULL,
			tokens_used int DEFAULT 0,
			cost decimal(10,6) DEFAULT 0.000000,
			response_time float DEFAULT 0,
			status enum('success','error') DEFAULT 'success',
			error_message text DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			INDEX idx_provider (provider),
			INDEX idx_status (status),
			INDEX idx_created_at (created_at)
		) $charset_collate;";

		// Whitelist table
		$whitelist_table = defined( 'SMART_FORM_SHIELD_WHITELIST_TABLE' ) 
			? SMART_FORM_SHIELD_WHITELIST_TABLE 
			: $wpdb->prefix . 'sfs_whitelist';
		$sql_whitelist = "CREATE TABLE IF NOT EXISTS $whitelist_table (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			email varchar(255) NOT NULL,
			domain varchar(255) DEFAULT NULL,
			reason text DEFAULT NULL,
			added_by bigint(20) UNSIGNED NOT NULL,
			is_active tinyint(1) DEFAULT 1,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY idx_email (email),
			INDEX idx_domain (domain),
			INDEX idx_is_active (is_active)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$results = array();
		$results[] = dbDelta( $sql_submissions );
		$results[] = dbDelta( $sql_api_logs );
		$results[] = dbDelta( $sql_whitelist );

		// Check for errors
		global $wpdb;
		if ( ! empty( $wpdb->last_error ) ) {
			error_log( 'Smart Form Shield DB Error: ' . $wpdb->last_error );
		}

		// Store database version
		$version = defined( 'SMART_FORM_SHIELD_VERSION' ) ? SMART_FORM_SHIELD_VERSION : '1.0.0';
		update_option( 'smart_form_shield_db_version', $version );
	}

	/**
	 * Set default plugin options.
	 *
	 * @since    1.0.0
	 */
	private static function set_default_options() {
		$default_options = array(
			'spam_threshold' => 75,
			'primary_provider' => 'openai',
			'fallback_provider' => 'claude',
			'enable_gravity_forms' => true,
			'enable_elementor_forms' => true,
			'enable_whitelist' => true,
			'enable_logging' => true,
			'retention_days' => 30,
			'daily_budget_limit' => 10.00,
			'notification_email' => get_option( 'admin_email' ),
			'notification_threshold' => 90,
			'cache_responses' => true,
			'cache_duration' => 3600,
		);

		foreach ( $default_options as $option => $value ) {
			if ( get_option( 'smart_form_shield_' . $option ) === false ) {
				update_option( 'smart_form_shield_' . $option, $value );
			}
		}

		// Initialize API provider settings
		$providers = array( 'openai', 'claude' );
		foreach ( $providers as $provider ) {
			if ( get_option( 'smart_form_shield_' . $provider . '_settings' ) === false ) {
				update_option( 'smart_form_shield_' . $provider . '_settings', array(
					'api_key' => '',
					'model' => '',
					'enabled' => false,
				) );
			}
		}
	}

	/**
	 * Create upload directory for logs and exports.
	 *
	 * @since    1.0.0
	 */
	private static function create_upload_directory() {
		$upload_dir = wp_upload_dir();
		$plugin_upload_dir = $upload_dir['basedir'] . '/smart-form-shield';
		
		if ( ! file_exists( $plugin_upload_dir ) ) {
			wp_mkdir_p( $plugin_upload_dir );
			
			// Create .htaccess to protect directory
			$htaccess_content = "Options -Indexes\n";
			$htaccess_content .= "<Files \"*.log\">\n";
			$htaccess_content .= "Order Allow,Deny\n";
			$htaccess_content .= "Deny from all\n";
			$htaccess_content .= "</Files>\n";
			
			file_put_contents( $plugin_upload_dir . '/.htaccess', $htaccess_content );
		}
	}
}