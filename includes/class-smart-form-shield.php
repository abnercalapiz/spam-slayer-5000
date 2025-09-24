<?php
/**
 * The core plugin class.
 *
 * @since      1.0.0
 * @package    Smart_Form_Shield
 * @subpackage Smart_Form_Shield/includes
 */

class Smart_Form_Shield {

	/**
	 * The loader that's responsible for maintaining and registering all hooks.
	 *
	 * @since    1.0.0
	 * @var      Smart_Form_Shield_Loader    $loader
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $plugin_name
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $version
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->version = SMART_FORM_SHIELD_VERSION;
		$this->plugin_name = 'smart-form-shield';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_integration_hooks();
		$this->define_api_hooks();
		$this->define_cron_hooks();
		$this->init_github_updater();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * @since    1.0.0
	 */
	private function load_dependencies() {
		// Core classes
		require_once SMART_FORM_SHIELD_PATH . 'includes/class-loader.php';
		require_once SMART_FORM_SHIELD_PATH . 'includes/class-i18n.php';
		
		// Database handler
		require_once SMART_FORM_SHIELD_PATH . 'database/class-database.php';
		
		// Provider classes
		require_once SMART_FORM_SHIELD_PATH . 'providers/class-provider-interface.php';
		require_once SMART_FORM_SHIELD_PATH . 'providers/class-provider-factory.php';
		require_once SMART_FORM_SHIELD_PATH . 'providers/class-openai-provider.php';
		require_once SMART_FORM_SHIELD_PATH . 'providers/class-claude-provider.php';
		
		// Admin classes
		require_once SMART_FORM_SHIELD_PATH . 'admin/class-admin.php';
		require_once SMART_FORM_SHIELD_PATH . 'admin/class-admin-ajax.php';
		require_once SMART_FORM_SHIELD_PATH . 'admin/class-admin-analytics.php';
		
		// Public classes
		require_once SMART_FORM_SHIELD_PATH . 'public/class-public.php';
		
		// Integration classes
		if ( class_exists( 'GFForms' ) ) {
			require_once SMART_FORM_SHIELD_PATH . 'integrations/gravity-forms/class-gravity-forms.php';
		}
		
		if ( did_action( 'elementor/loaded' ) ) {
			require_once SMART_FORM_SHIELD_PATH . 'integrations/elementor/class-elementor.php';
		}
		
		// REST API
		require_once SMART_FORM_SHIELD_PATH . 'includes/class-rest-api.php';
		
		// Utilities
		require_once SMART_FORM_SHIELD_PATH . 'includes/class-validator.php';
		require_once SMART_FORM_SHIELD_PATH . 'includes/class-cache.php';
		require_once SMART_FORM_SHIELD_PATH . 'includes/class-logger.php';
		
		// GitHub Updater
		require_once SMART_FORM_SHIELD_PATH . 'includes/class-github-updater.php';

		$this->loader = new Smart_Form_Shield_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * @since    1.0.0
	 */
	private function set_locale() {
		$plugin_i18n = new Smart_Form_Shield_i18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality.
	 *
	 * @since    1.0.0
	 */
	private function define_admin_hooks() {
		$plugin_admin = new Smart_Form_Shield_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_admin_menu' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );
		
		// Dashboard widgets
		$this->loader->add_action( 'wp_dashboard_setup', $plugin_admin, 'add_dashboard_widget' );
		
		// Plugin action links
		$this->loader->add_filter( 'plugin_action_links_' . SMART_FORM_SHIELD_BASENAME, $plugin_admin, 'add_action_links' );
		
		// Admin notices
		$this->loader->add_action( 'admin_notices', $plugin_admin, 'display_admin_notices' );
		
		// AJAX handlers
		$plugin_ajax = new Smart_Form_Shield_Admin_Ajax( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'wp_ajax_sfs_get_submissions', $plugin_ajax, 'get_submissions' );
		$this->loader->add_action( 'wp_ajax_sfs_get_submission_details', $plugin_ajax, 'get_submission_details' );
		$this->loader->add_action( 'wp_ajax_sfs_update_submission_status', $plugin_ajax, 'update_submission_status' );
		$this->loader->add_action( 'wp_ajax_sfs_bulk_action', $plugin_ajax, 'handle_bulk_action' );
		$this->loader->add_action( 'wp_ajax_sfs_test_provider', $plugin_ajax, 'test_provider' );
		$this->loader->add_action( 'wp_ajax_sfs_export_data', $plugin_ajax, 'export_data' );
		$this->loader->add_action( 'wp_ajax_sfs_get_analytics', $plugin_ajax, 'get_analytics' );
		$this->loader->add_action( 'wp_ajax_sfs_add_to_whitelist', $plugin_ajax, 'add_to_whitelist' );
		$this->loader->add_action( 'wp_ajax_sfs_remove_from_whitelist', $plugin_ajax, 'remove_from_whitelist' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality.
	 *
	 * @since    1.0.0
	 */
	private function define_public_hooks() {
		$plugin_public = new Smart_Form_Shield_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
	}

	/**
	 * Register form integration hooks.
	 *
	 * @since    1.0.0
	 */
	private function define_integration_hooks() {
		// Gravity Forms integration
		if ( class_exists( 'GFForms' ) && class_exists( 'Smart_Form_Shield_Gravity_Forms' ) && get_option( 'smart_form_shield_enable_gravity_forms', true ) ) {
			$gravity_forms = new Smart_Form_Shield_Gravity_Forms( $this->get_plugin_name(), $this->get_version() );
			
			$this->loader->add_filter( 'gform_validation', $gravity_forms, 'validate_submission', 10, 1 );
			$this->loader->add_filter( 'gform_entry_post_save', $gravity_forms, 'log_submission', 10, 2 );
			$this->loader->add_action( 'gform_after_submission', $gravity_forms, 'after_submission', 10, 2 );
			$this->loader->add_filter( 'gform_form_settings_menu', $gravity_forms, 'add_form_settings_menu', 10, 2 );
			$this->loader->add_action( 'gform_form_settings_page_smart_form_shield', $gravity_forms, 'form_settings_page' );
		}
		
		// Elementor Forms integration
		if ( did_action( 'elementor/loaded' ) && class_exists( 'Smart_Form_Shield_Elementor' ) && get_option( 'smart_form_shield_enable_elementor_forms', true ) ) {
			$elementor_forms = new Smart_Form_Shield_Elementor( $this->get_plugin_name(), $this->get_version() );
			
			$this->loader->add_action( 'elementor_pro/forms/validation', $elementor_forms, 'validate_submission', 10, 2 );
			$this->loader->add_action( 'elementor_pro/forms/new_record', $elementor_forms, 'log_submission', 10, 2 );
			$this->loader->add_action( 'elementor_pro/forms/register_action', $elementor_forms, 'register_action', 10, 1 );
		}
	}

	/**
	 * Register REST API endpoints.
	 *
	 * @since    1.0.0
	 */
	private function define_api_hooks() {
		$rest_api = new Smart_Form_Shield_REST_API( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'rest_api_init', $rest_api, 'register_routes' );
	}

	/**
	 * Register cron job hooks.
	 *
	 * @since    1.0.0
	 */
	private function define_cron_hooks() {
		$this->loader->add_action( 'smart_form_shield_daily_cleanup', $this, 'daily_cleanup' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks.
	 *
	 * @since     1.0.0
	 * @return    Smart_Form_Shield_Loader    Orchestrates the hooks.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Daily cleanup cron job.
	 *
	 * @since    1.0.0
	 */
	public function daily_cleanup() {
		$database = new Smart_Form_Shield_Database();
		$retention_days = get_option( 'smart_form_shield_retention_days', 30 );
		
		// Clean old submissions
		$database->cleanup_old_submissions( $retention_days );
		
		// Clean old API logs
		$database->cleanup_old_api_logs( $retention_days );
		
		// Clear expired cache
		$cache = new Smart_Form_Shield_Cache();
		$cache->cleanup();
		
		// Send daily report if enabled
		if ( get_option( 'smart_form_shield_daily_report', false ) ) {
			$this->send_daily_report();
		}
	}

	/**
	 * Send daily analytics report.
	 *
	 * @since    1.0.0
	 */
	private function send_daily_report() {
		$analytics = new Smart_Form_Shield_Admin_Analytics( $this->get_plugin_name(), $this->get_version() );
		$report = $analytics->generate_daily_report();
		
		$to = get_option( 'smart_form_shield_notification_email', get_option( 'admin_email' ) );
		$subject = __( 'Smart Form Shield Daily Report', 'smart-form-shield' );
		
		wp_mail( $to, $subject, $report, array( 'Content-Type: text/html; charset=UTF-8' ) );
	}
	
	/**
	 * Initialize GitHub updater.
	 *
	 * @since    1.1.0
	 */
	private function init_github_updater() {
		new Smart_Form_Shield_GitHub_Updater( SMART_FORM_SHIELD_BASENAME );
	}
}