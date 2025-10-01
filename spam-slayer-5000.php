<?php
/**
 * Plugin Name:       Spam Slayer 5000
 * Plugin URI:        https://jezweb.com.au/
 * Description:       Intelligent AI-powered spam filtering for Gravity Forms and Elementor contact forms using OpenAI, Claude, and Gemini APIs.
 * Version:           1.1.5
 * Author:            Jezweb
 * Author URI:        https://jezweb.com.au/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       spam-slayer-5000
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Update URI:        https://github.com/abnercalapiz/spam-slayer-5000
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Current plugin version.
 */
define( 'SPAM_SLAYER_5000_VERSION', '1.1.5' );

/**
 * Plugin base name.
 */
define( 'SPAM_SLAYER_5000_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Plugin directory path.
 */
define( 'SPAM_SLAYER_5000_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 */
define( 'SPAM_SLAYER_5000_URL', plugin_dir_url( __FILE__ ) );

/**
 * Database table names.
 * We'll define these with a function to ensure $wpdb is available
 */
function spam_slayer_5000_define_tables() {
	global $wpdb;
	
	if ( ! defined( 'SPAM_SLAYER_5000_SUBMISSIONS_TABLE' ) && isset( $wpdb->prefix ) ) {
		define( 'SPAM_SLAYER_5000_SUBMISSIONS_TABLE', $wpdb->prefix . 'ss5k_submissions' );
		define( 'SPAM_SLAYER_5000_API_LOGS_TABLE', $wpdb->prefix . 'ss5k_api_logs' );
		define( 'SPAM_SLAYER_5000_WHITELIST_TABLE', $wpdb->prefix . 'ss5k_whitelist' );
		define( 'SPAM_SLAYER_5000_BLOCKLIST_TABLE', $wpdb->prefix . 'ss5k_blocklist' );
	}
}

// Try to define tables immediately if $wpdb is available
if ( isset( $GLOBALS['wpdb'] ) && is_object( $GLOBALS['wpdb'] ) ) {
	spam_slayer_5000_define_tables();
} else {
	// Otherwise, hook into 'init' to ensure WordPress is fully loaded
	add_action( 'init', 'spam_slayer_5000_define_tables', 1 );
}

/**
 * The code that runs during plugin activation.
 */
function spam_slayer_5000_activate() {
	try {
		// Check PHP version
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( esc_html__( 'Spam Slayer 5000 requires PHP 7.4 or higher. Please upgrade your PHP version.', 'spam-slayer-5000' ) );
		}
		
		// Check if activator class file exists
		$activator_file = plugin_dir_path( __FILE__ ) . 'includes/class-activator.php';
		if ( ! file_exists( $activator_file ) ) {
			wp_die( 'Spam Slayer 5000 Error: Activator class file not found.' );
		}
		
		require_once $activator_file;
		
		// Check if class exists before calling
		if ( ! class_exists( 'Spam_Slayer_5000_Activator' ) ) {
			wp_die( 'Spam Slayer 5000 Error: Activator class not found.' );
		}
		
		Spam_Slayer_5000_Activator::activate();
	} catch ( Exception $e ) {
		error_log( 'Spam Slayer 5000 Activation Error: ' . $e->getMessage() );
		wp_die( 'Plugin activation failed: ' . esc_html( $e->getMessage() ) );
	} catch ( Error $e ) {
		error_log( 'Spam Slayer 5000 Fatal Error: ' . $e->getMessage() );
		wp_die( 'Plugin activation failed with fatal error: ' . esc_html( $e->getMessage() ) );
	}
}

/**
 * The code that runs during plugin deactivation.
 */
function spam_slayer_5000_deactivate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-deactivator.php';
	Spam_Slayer_5000_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'spam_slayer_5000_activate' );
register_deactivation_hook( __FILE__, 'spam_slayer_5000_deactivate' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-spam-slayer-5000.php';

/**
 * Initialize the Plugin Update Checker
 */
if ( file_exists( plugin_dir_path( __FILE__ ) . 'includes/plugin-update-checker/plugin-update-checker.php' ) ) {
	require plugin_dir_path( __FILE__ ) . 'includes/plugin-update-checker/plugin-update-checker.php';
	
	// Initialize update checker after WordPress is loaded
	add_action( 'init', function() {
		// Check if the factory class exists
		if ( class_exists( 'YahnisElsts\PluginUpdateChecker\v5\PucFactory' ) ) {
			try {
				$myUpdateChecker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
					'https://github.com/abnercalapiz/spam-slayer-5000',
					__FILE__,
					'spam-slayer-5000'
				);
				
				// Set the branch that contains the stable release
				$myUpdateChecker->setBranch( 'main' );
				
				// Optional: Enable release assets
				$myUpdateChecker->getVcsApi()->enableReleaseAssets();
				
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'Spam Slayer 5000: Update checker initialized successfully' );
				}
				
			} catch ( Exception $e ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'Spam Slayer 5000 Update Checker Exception: ' . $e->getMessage() );
					error_log( 'Stack trace: ' . $e->getTraceAsString() );
				}
			} catch ( Error $e ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'Spam Slayer 5000 Update Checker Fatal Error: ' . $e->getMessage() );
					error_log( 'Stack trace: ' . $e->getTraceAsString() );
				}
			}
		} else {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Spam Slayer 5000: PucFactory class not found' );
			}
		}
	}, 10 );
} else {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'Spam Slayer 5000: Plugin Update Checker library not found' );
	}
}

// Fix directory name when updating from GitHub
add_filter( 'upgrader_source_selection', function( $source, $remote_source, $upgrader, $extra ) {
	global $wp_filesystem;
	
	// Check if this is our plugin update
	if ( isset( $extra['plugin'] ) && strpos( $extra['plugin'], 'spam-slayer-5000.php' ) !== false ) {
		// The source will be something like /path/to/spam-slayer-5000-main/
		// We want to rename it to just 'spam-slayer-5000'
		$desired_folder_name = 'spam-slayer-5000';
		$desired_source = trailingslashit( dirname( $source ) ) . $desired_folder_name . '/';
		
		// Only move if source and desired are different
		if ( $source !== $desired_source ) {
			// Remove the desired directory if it exists (shouldn't happen during update)
			if ( $wp_filesystem->exists( $desired_source ) ) {
				$wp_filesystem->delete( $desired_source, true );
			}
			
			// Move the source to the desired directory name
			if ( $wp_filesystem->move( $source, $desired_source ) ) {
				return $desired_source;
			}
		}
	}
	
	return $source;
}, 10, 4 );

/**
 * Begins execution of the plugin.
 */
function spam_slayer_5000_run() {
	try {
		$plugin = new Spam_Slayer_5000();
		$plugin->run();
	} catch ( Exception $e ) {
		error_log( 'Spam Slayer 5000 Runtime Error: ' . $e->getMessage() );
		// Don't die here as it would break the site, just log the error
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			echo '<!-- Spam Slayer 5000 Error: ' . esc_html( $e->getMessage() ) . ' -->';
		}
	} catch ( Error $e ) {
		error_log( 'Spam Slayer 5000 Fatal Runtime Error: ' . $e->getMessage() );
		// Don't die here as it would break the site, just log the error
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			echo '<!-- Spam Slayer 5000 Fatal Error: ' . esc_html( $e->getMessage() ) . ' -->';
		}
	}
}

// Only run after plugins are loaded to ensure all dependencies are available
add_action( 'plugins_loaded', 'spam_slayer_5000_init' );

/**
 * Initialize the plugin after WordPress has loaded.
 */
function spam_slayer_5000_init() {
	// Ensure table constants are defined
	spam_slayer_5000_define_tables();
	
	// Run the plugin
	spam_slayer_5000_run();
}