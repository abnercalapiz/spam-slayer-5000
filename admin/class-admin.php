<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    Smart_Form_Shield
 * @subpackage Smart_Form_Shield/admin
 */

class Smart_Form_Shield_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $plugin_name
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $version
	 */
	private $version;

	/**
	 * Initialize the class.
	 *
	 * @since    1.0.0
	 * @param    string    $plugin_name    The name of this plugin.
	 * @param    string    $version        The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		$screen = get_current_screen();
		
		if ( strpos( $screen->id, 'smart-form-shield' ) !== false ) {
			wp_enqueue_style( 
				$this->plugin_name, 
				SMART_FORM_SHIELD_URL . 'admin/css/admin.css', 
				array(), 
				$this->version, 
				'all' 
			);
			
			wp_enqueue_style( 'wp-color-picker' );
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		$screen = get_current_screen();
		
		if ( strpos( $screen->id, 'smart-form-shield' ) !== false ) {
			wp_enqueue_script( 
				$this->plugin_name, 
				SMART_FORM_SHIELD_URL . 'admin/js/admin.js', 
				array( 'jquery', 'wp-color-picker' ), 
				$this->version, 
				false 
			);
			
			wp_localize_script( $this->plugin_name, 'smart_form_shield_admin', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'sfs_admin_nonce' ),
				'export_url' => admin_url( 'admin-ajax.php?action=sfs_export_submissions&nonce=' . wp_create_nonce( 'sfs_export_nonce' ) ),
				'strings' => array(
					'confirm_delete' => __( 'Are you sure you want to delete this item?', 'smart-form-shield' ),
					'confirm_remove' => __( 'Are you sure you want to remove this email from the whitelist?', 'smart-form-shield' ),
					'confirm_bulk_delete' => __( 'Are you sure you want to delete selected items?', 'smart-form-shield' ),
					'processing' => __( 'Processing...', 'smart-form-shield' ),
					'success' => __( 'Success!', 'smart-form-shield' ),
					'error' => __( 'An error occurred. Please try again.', 'smart-form-shield' ),
				),
			) );
			
			// Chart.js for analytics
			if ( $screen->id === 'smart-form-shield_page_smart-form-shield-analytics' ) {
				wp_enqueue_script( 
					'chartjs', 
					'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', 
					array(), 
					'4.4.0', 
					true 
				);
				
				// Add chart data
				$database = new Smart_Form_Shield_Database();
				$chart_data = $database->get_chart_data( 30 ); // Last 30 days
				$provider_data = $database->get_provider_stats();
				
				wp_add_inline_script( $this->plugin_name, 
					'smart_form_shield_admin.chart_data = ' . wp_json_encode( $chart_data ) . ';' .
					'smart_form_shield_admin.provider_data = ' . wp_json_encode( $provider_data ) . ';',
					'before'
				);
			}
		}
	}

	/**
	 * Add admin menu.
	 *
	 * @since    1.0.0
	 */
	public function add_admin_menu() {
		// Main menu
		add_menu_page(
			__( 'Smart Form Shield', 'smart-form-shield' ),
			__( 'Form Shield', 'smart-form-shield' ),
			'manage_options',
			'smart-form-shield',
			array( $this, 'display_submissions_page' ),
			'dashicons-shield',
			30
		);

		// Submissions submenu
		add_submenu_page(
			'smart-form-shield',
			__( 'Submissions', 'smart-form-shield' ),
			__( 'Submissions', 'smart-form-shield' ),
			'manage_options',
			'smart-form-shield',
			array( $this, 'display_submissions_page' )
		);

		// Analytics submenu
		add_submenu_page(
			'smart-form-shield',
			__( 'Analytics', 'smart-form-shield' ),
			__( 'Analytics', 'smart-form-shield' ),
			'manage_options',
			'smart-form-shield-analytics',
			array( $this, 'display_analytics_page' )
		);

		// Whitelist submenu
		add_submenu_page(
			'smart-form-shield',
			__( 'Whitelist', 'smart-form-shield' ),
			__( 'Whitelist', 'smart-form-shield' ),
			'manage_options',
			'smart-form-shield-whitelist',
			array( $this, 'display_whitelist_page' )
		);

		// Settings submenu
		add_submenu_page(
			'smart-form-shield',
			__( 'Settings', 'smart-form-shield' ),
			__( 'Settings', 'smart-form-shield' ),
			'manage_options',
			'smart-form-shield-settings',
			array( $this, 'display_settings_page' )
		);

		// Logs submenu (for admins only)
		if ( current_user_can( 'manage_network' ) ) {
			add_submenu_page(
				'smart-form-shield',
				__( 'Logs', 'smart-form-shield' ),
				__( 'Logs', 'smart-form-shield' ),
				'manage_network',
				'smart-form-shield-logs',
				array( $this, 'display_logs_page' )
			);
		}
	}

	/**
	 * Register plugin settings.
	 *
	 * @since    1.0.0
	 */
	public function register_settings() {
		// General settings
		register_setting( 'smart_form_shield_general', 'smart_form_shield_spam_threshold' );
		register_setting( 'smart_form_shield_general', 'smart_form_shield_primary_provider' );
		register_setting( 'smart_form_shield_general', 'smart_form_shield_fallback_provider' );
		register_setting( 'smart_form_shield_general', 'smart_form_shield_enable_gravity_forms' );
		register_setting( 'smart_form_shield_general', 'smart_form_shield_enable_elementor_forms' );
		register_setting( 'smart_form_shield_general', 'smart_form_shield_enable_whitelist' );
		register_setting( 'smart_form_shield_general', 'smart_form_shield_enable_logging' );
		register_setting( 'smart_form_shield_general', 'smart_form_shield_log_level' );
		
		// API settings with sanitization callbacks
		register_setting( 'smart_form_shield_api', 'smart_form_shield_openai_settings', array(
			'sanitize_callback' => array( $this, 'sanitize_api_settings' ),
		) );
		register_setting( 'smart_form_shield_api', 'smart_form_shield_claude_settings', array(
			'sanitize_callback' => array( $this, 'sanitize_api_settings' ),
		) );
		
		// Advanced settings
		register_setting( 'smart_form_shield_advanced', 'smart_form_shield_retention_days' );
		register_setting( 'smart_form_shield_advanced', 'smart_form_shield_daily_budget_limit' );
		register_setting( 'smart_form_shield_advanced', 'smart_form_shield_notification_email' );
		register_setting( 'smart_form_shield_advanced', 'smart_form_shield_notification_threshold' );
		register_setting( 'smart_form_shield_advanced', 'smart_form_shield_cache_responses' );
		register_setting( 'smart_form_shield_advanced', 'smart_form_shield_cache_duration' );
		register_setting( 'smart_form_shield_advanced', 'smart_form_shield_daily_report' );
	}

	/**
	 * Display submissions page.
	 *
	 * @since    1.0.0
	 */
	public function display_submissions_page() {
		require_once SMART_FORM_SHIELD_PATH . 'admin/partials/submissions-display.php';
	}

	/**
	 * Display analytics page.
	 *
	 * @since    1.0.0
	 */
	public function display_analytics_page() {
		require_once SMART_FORM_SHIELD_PATH . 'admin/partials/analytics-display.php';
	}

	/**
	 * Display whitelist page.
	 *
	 * @since    1.0.0
	 */
	public function display_whitelist_page() {
		require_once SMART_FORM_SHIELD_PATH . 'admin/partials/whitelist-display.php';
	}

	/**
	 * Display settings page.
	 *
	 * @since    1.0.0
	 */
	public function display_settings_page() {
		require_once SMART_FORM_SHIELD_PATH . 'admin/partials/settings-display.php';
	}

	/**
	 * Display logs page.
	 *
	 * @since    1.0.0
	 */
	public function display_logs_page() {
		require_once SMART_FORM_SHIELD_PATH . 'admin/partials/logs-display.php';
	}

	/**
	 * Add plugin action links.
	 *
	 * @since    1.0.0
	 * @param    array    $links    Action links.
	 * @return   array              Modified action links.
	 */
	public function add_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'admin.php?page=smart-form-shield-settings' ),
			__( 'Settings', 'smart-form-shield' )
		);
		
		array_unshift( $links, $settings_link );
		
		return $links;
	}

	/**
	 * Display admin notices.
	 *
	 * @since    1.0.0
	 */
	public function display_admin_notices() {
		// Check if any API provider is configured
		$providers = array( 'openai', 'claude', 'gemini' );
		$has_provider = false;
		
		foreach ( $providers as $provider ) {
			$settings = get_option( 'smart_form_shield_' . $provider . '_settings', array() );
			if ( ! empty( $settings['api_key'] ) && ! empty( $settings['enabled'] ) ) {
				$has_provider = true;
				break;
			}
		}
		
		if ( ! $has_provider && current_user_can( 'manage_options' ) ) {
			$screen = get_current_screen();
			if ( strpos( $screen->id, 'smart-form-shield' ) !== false ) {
				?>
				<div class="notice notice-warning is-dismissible">
					<p>
						<?php 
						printf(
							__( 'Smart Form Shield requires at least one AI provider to be configured. <a href="%s">Configure providers now</a>.', 'smart-form-shield' ),
							admin_url( 'admin.php?page=smart-form-shield-settings&tab=api' )
						);
						?>
					</p>
				</div>
				<?php
			}
		}
		
		// Check daily budget
		$this->check_budget_notice();
	}

	/**
	 * Add dashboard widget.
	 *
	 * @since    1.0.0
	 */
	public function add_dashboard_widget() {
		if ( current_user_can( 'manage_options' ) ) {
			wp_add_dashboard_widget(
				'smart_form_shield_dashboard',
				__( 'Smart Form Shield Overview', 'smart-form-shield' ),
				array( $this, 'display_dashboard_widget' )
			);
		}
	}

	/**
	 * Display dashboard widget.
	 *
	 * @since    1.0.0
	 */
	public function display_dashboard_widget() {
		$database = new Smart_Form_Shield_Database();
		
		// Get today's stats
		$today_stats = $database->get_submissions_count( array(
			'date_from' => current_time( 'Y-m-d 00:00:00' ),
		) );
		
		$today_spam = $database->get_submissions_count( array(
			'status' => 'spam',
			'date_from' => current_time( 'Y-m-d 00:00:00' ),
		) );
		
		// Get API usage
		$api_stats = $database->get_api_usage_stats( 'day' );
		$total_cost = 0;
		foreach ( $api_stats as $stat ) {
			$total_cost += $stat['total_cost'];
		}
		
		?>
		<div class="sfs-dashboard-widget">
			<div class="sfs-stats-grid">
				<div class="sfs-stat">
					<h4><?php esc_html_e( 'Today\'s Submissions', 'smart-form-shield' ); ?></h4>
					<p class="sfs-stat-number"><?php echo esc_html( $today_stats ); ?></p>
				</div>
				<div class="sfs-stat">
					<h4><?php esc_html_e( 'Spam Blocked', 'smart-form-shield' ); ?></h4>
					<p class="sfs-stat-number"><?php echo esc_html( $today_spam ); ?></p>
				</div>
				<div class="sfs-stat">
					<h4><?php esc_html_e( 'Today\'s Cost', 'smart-form-shield' ); ?></h4>
					<p class="sfs-stat-number">$<?php echo number_format( $total_cost, 4 ); ?></p>
				</div>
			</div>
			<p class="sfs-dashboard-links">
				<a href="<?php echo admin_url( 'admin.php?page=smart-form-shield' ); ?>" class="button">
					<?php esc_html_e( 'View Submissions', 'smart-form-shield' ); ?>
				</a>
				<a href="<?php echo admin_url( 'admin.php?page=smart-form-shield-analytics' ); ?>" class="button">
					<?php esc_html_e( 'View Analytics', 'smart-form-shield' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Check and display budget notice.
	 *
	 * @since    1.0.0
	 */
	private function check_budget_notice() {
		$daily_limit = get_option( 'smart_form_shield_daily_budget_limit', 10.00 );
		
		if ( $daily_limit <= 0 ) {
			return;
		}
		
		$database = new Smart_Form_Shield_Database();
		$api_stats = $database->get_api_usage_stats( 'day' );
		
		$total_cost = 0;
		foreach ( $api_stats as $stat ) {
			$total_cost += $stat['total_cost'];
		}
		
		if ( $total_cost >= $daily_limit * 0.8 ) {
			$percentage = ( $total_cost / $daily_limit ) * 100;
			?>
			<div class="notice notice-warning">
				<p>
					<?php 
					printf(
						__( 'Smart Form Shield: You have used %s%% of your daily budget limit ($%s of $%s).', 'smart-form-shield' ),
						number_format( $percentage, 0 ),
						number_format( $total_cost, 2 ),
						number_format( $daily_limit, 2 )
					);
					?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Sanitize API settings and encrypt API keys.
	 *
	 * @since    1.0.0
	 * @param    array    $settings    Settings to sanitize.
	 * @return   array                 Sanitized settings.
	 */
	public function sanitize_api_settings( $settings ) {
		require_once SMART_FORM_SHIELD_PATH . 'includes/class-security.php';
		
		$sanitized = array();
		
		// Sanitize and encrypt API key
		if ( isset( $settings['api_key'] ) ) {
			// Use sanitize_text_field instead of 'key' type to preserve API key format
			$api_key = sanitize_text_field( $settings['api_key'] );
			if ( ! empty( $api_key ) ) {
				$sanitized['api_key'] = Smart_Form_Shield_Security::encrypt( $api_key );
			} else {
				$sanitized['api_key'] = '';
			}
		}
		
		// Sanitize model
		if ( isset( $settings['model'] ) ) {
			$sanitized['model'] = sanitize_text_field( $settings['model'] );
		}
		
		// Sanitize enabled status
		if ( isset( $settings['enabled'] ) ) {
			$sanitized['enabled'] = (bool) $settings['enabled'];
		}
		
		// Sanitize project_id (for Vertex AI)
		if ( isset( $settings['project_id'] ) ) {
			$sanitized['project_id'] = sanitize_text_field( $settings['project_id'] );
		}
		
		// Sanitize region (for Vertex AI)
		if ( isset( $settings['region'] ) ) {
			$sanitized['region'] = sanitize_text_field( $settings['region'] );
		}
		
		return $sanitized;
	}
}