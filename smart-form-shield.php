<?php
/**
 * Plugin Name:       Smart Form Shield
 * Plugin URI:        https://jezweb.com.au/
 * Description:       Intelligent AI-powered spam filtering for Gravity Forms and Elementor contact forms using OpenAI, Claude, and Gemini APIs.
 * Version:           1.0.0
 * Author:            Jezweb
 * Author URI:        https://jezweb.com.au/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       smart-form-shield
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Current plugin version.
 */
define( 'SMART_FORM_SHIELD_VERSION', '1.0.0' );

/**
 * Plugin base name.
 */
define( 'SMART_FORM_SHIELD_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Plugin directory path.
 */
define( 'SMART_FORM_SHIELD_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 */
define( 'SMART_FORM_SHIELD_URL', plugin_dir_url( __FILE__ ) );

/**
 * Database table names.
 * We'll define these with a function to ensure $wpdb is available
 */
function smart_form_shield_define_tables() {
	global $wpdb;
	
	if ( ! defined( 'SMART_FORM_SHIELD_SUBMISSIONS_TABLE' ) && isset( $wpdb->prefix ) ) {
		define( 'SMART_FORM_SHIELD_SUBMISSIONS_TABLE', $wpdb->prefix . 'sfs_submissions' );
		define( 'SMART_FORM_SHIELD_API_LOGS_TABLE', $wpdb->prefix . 'sfs_api_logs' );
		define( 'SMART_FORM_SHIELD_WHITELIST_TABLE', $wpdb->prefix . 'sfs_whitelist' );
	}
}

// Try to define tables immediately if $wpdb is available
if ( isset( $GLOBALS['wpdb'] ) && is_object( $GLOBALS['wpdb'] ) ) {
	smart_form_shield_define_tables();
} else {
	// Otherwise, hook into 'init' to ensure WordPress is fully loaded
	add_action( 'init', 'smart_form_shield_define_tables', 1 );
}

/**
 * The code that runs during plugin activation.
 */
function smart_form_shield_activate() {
	try {
		// Check PHP version
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( esc_html__( 'Smart Form Shield requires PHP 7.4 or higher. Please upgrade your PHP version.', 'smart-form-shield' ) );
		}
		
		// Check if activator class file exists
		$activator_file = plugin_dir_path( __FILE__ ) . 'includes/class-activator.php';
		if ( ! file_exists( $activator_file ) ) {
			wp_die( 'Smart Form Shield Error: Activator class file not found.' );
		}
		
		require_once $activator_file;
		
		// Check if class exists before calling
		if ( ! class_exists( 'Smart_Form_Shield_Activator' ) ) {
			wp_die( 'Smart Form Shield Error: Activator class not found.' );
		}
		
		Smart_Form_Shield_Activator::activate();
	} catch ( Exception $e ) {
		error_log( 'Smart Form Shield Activation Error: ' . $e->getMessage() );
		wp_die( 'Plugin activation failed: ' . esc_html( $e->getMessage() ) );
	} catch ( Error $e ) {
		error_log( 'Smart Form Shield Fatal Error: ' . $e->getMessage() );
		wp_die( 'Plugin activation failed with fatal error: ' . esc_html( $e->getMessage() ) );
	}
}

/**
 * The code that runs during plugin deactivation.
 */
function smart_form_shield_deactivate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-deactivator.php';
	Smart_Form_Shield_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'smart_form_shield_activate' );
register_deactivation_hook( __FILE__, 'smart_form_shield_deactivate' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-smart-form-shield.php';

/**
 * Begins execution of the plugin.
 */
function smart_form_shield_run() {
	try {
		$plugin = new Smart_Form_Shield();
		$plugin->run();
	} catch ( Exception $e ) {
		error_log( 'Smart Form Shield Runtime Error: ' . $e->getMessage() );
		// Don't die here as it would break the site, just log the error
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			echo '<!-- Smart Form Shield Error: ' . esc_html( $e->getMessage() ) . ' -->';
		}
	} catch ( Error $e ) {
		error_log( 'Smart Form Shield Fatal Runtime Error: ' . $e->getMessage() );
		// Don't die here as it would break the site, just log the error
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			echo '<!-- Smart Form Shield Fatal Error: ' . esc_html( $e->getMessage() ) . ' -->';
		}
	}
}

// Only run after plugins are loaded to ensure all dependencies are available
add_action( 'plugins_loaded', 'smart_form_shield_init' );

/**
 * Initialize the plugin after WordPress has loaded.
 */
function smart_form_shield_init() {
	// Ensure table constants are defined
	smart_form_shield_define_tables();
	
	// Run the plugin
	smart_form_shield_run();
}