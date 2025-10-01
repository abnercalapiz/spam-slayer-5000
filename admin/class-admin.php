<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    Spam_Slayer_5000
 * @subpackage Spam_Slayer_5000/admin
 */

class Spam_Slayer_5000_Admin {

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
		
		if ( strpos( $screen->id, 'spam-slayer-5000' ) !== false ) {
			wp_enqueue_style( 
				$this->plugin_name, 
				SPAM_SLAYER_5000_URL . 'admin/css/admin.css', 
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
		
		if ( strpos( $screen->id, 'spam-slayer-5000' ) !== false ) {
			wp_enqueue_script( 
				$this->plugin_name, 
				SPAM_SLAYER_5000_URL . 'admin/js/admin.js', 
				array( 'jquery', 'wp-color-picker' ), 
				$this->version, 
				false 
			);
			
			wp_localize_script( $this->plugin_name, 'spam_slayer_5000_admin', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'ss5k_admin_nonce' ),
				'export_url' => admin_url( 'admin-ajax.php?action=ss5k_export_submissions&nonce=' . wp_create_nonce( 'ss5k_export_nonce' ) ),
				'strings' => array(
					'confirm_delete' => __( 'Are you sure you want to delete this item?', 'spam-slayer-5000' ),
					'confirm_remove' => __( 'Are you sure you want to remove this email from the whitelist?', 'spam-slayer-5000' ),
					'confirm_bulk_delete' => __( 'Are you sure you want to delete selected items?', 'spam-slayer-5000' ),
					'processing' => __( 'Processing...', 'spam-slayer-5000' ),
					'success' => __( 'Success!', 'spam-slayer-5000' ),
					'error' => __( 'An error occurred. Please try again.', 'spam-slayer-5000' ),
				),
			) );
			
			// Chart.js for analytics
			if ( $screen->id === 'spam-slayer-5000_page_spam-slayer-5000-analytics' ) {
				wp_enqueue_script( 
					'chartjs', 
					'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', 
					array(), 
					'4.4.0', 
					true 
				);
				
				// Add chart data - methods need to be implemented
				// TODO: Implement get_chart_data and get_provider_stats methods in Database class
				/*
				$database = new Spam_Slayer_5000_Database();
				$chart_data = $database->get_chart_data( 30 ); // Last 30 days
				$provider_data = $database->get_provider_stats();
				
				wp_add_inline_script( $this->plugin_name, 
					'spam_slayer_5000_admin.chart_data = ' . wp_json_encode( $chart_data ) . ';' .
					'spam_slayer_5000_admin.provider_data = ' . wp_json_encode( $provider_data ) . ';',
					'before'
				);
				*/
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
			__( 'Spam Slayer', 'spam-slayer-5000' ),
			__( 'Spam Slayer', 'spam-slayer-5000' ),
			'manage_options',
			'spam-slayer-5000',
			array( $this, 'display_submissions_page' ),
			'dashicons-shield',
			30
		);

		// Submissions submenu
		add_submenu_page(
			'spam-slayer-5000',
			__( 'Submissions', 'spam-slayer-5000' ),
			__( 'Submissions', 'spam-slayer-5000' ),
			'manage_options',
			'spam-slayer-5000',
			array( $this, 'display_submissions_page' )
		);

		// Analytics submenu
		add_submenu_page(
			'spam-slayer-5000',
			__( 'Analytics', 'spam-slayer-5000' ),
			__( 'Analytics', 'spam-slayer-5000' ),
			'manage_options',
			'spam-slayer-5000-analytics',
			array( $this, 'display_analytics_page' )
		);

		// Whitelist submenu
		add_submenu_page(
			'spam-slayer-5000',
			__( 'Whitelist', 'spam-slayer-5000' ),
			__( 'Whitelist', 'spam-slayer-5000' ),
			'manage_options',
			'spam-slayer-5000-whitelist',
			array( $this, 'display_whitelist_page' )
		);

		// Blocklist submenu
		add_submenu_page(
			'spam-slayer-5000',
			__( 'Blocklist', 'spam-slayer-5000' ),
			__( 'Blocklist', 'spam-slayer-5000' ),
			'manage_options',
			'spam-slayer-5000-blocklist',
			array( $this, 'display_blocklist_page' )
		);

		// Settings submenu
		add_submenu_page(
			'spam-slayer-5000',
			__( 'Settings', 'spam-slayer-5000' ),
			__( 'Settings', 'spam-slayer-5000' ),
			'manage_options',
			'spam-slayer-5000-settings',
			array( $this, 'display_settings_page' )
		);

		// Logs submenu (for admins only)
		if ( current_user_can( 'manage_network' ) ) {
			add_submenu_page(
				'spam-slayer-5000',
				__( 'Logs', 'spam-slayer-5000' ),
				__( 'Logs', 'spam-slayer-5000' ),
				'manage_network',
				'spam-slayer-5000-logs',
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
		// Add action to show success message after settings save
		add_action( 'admin_notices', array( $this, 'settings_updated_notice' ) );
		
		// General settings
		register_setting( 'spam_slayer_5000_general', 'spam_slayer_5000_spam_threshold' );
		register_setting( 'spam_slayer_5000_general', 'spam_slayer_5000_primary_provider' );
		register_setting( 'spam_slayer_5000_general', 'spam_slayer_5000_fallback_provider' );
		register_setting( 'spam_slayer_5000_general', 'spam_slayer_5000_enable_gravity_forms' );
		register_setting( 'spam_slayer_5000_general', 'spam_slayer_5000_enable_elementor_forms' );
		register_setting( 'spam_slayer_5000_general', 'spam_slayer_5000_enable_whitelist' );
		register_setting( 'spam_slayer_5000_general', 'spam_slayer_5000_enable_logging' );
		register_setting( 'spam_slayer_5000_general', 'spam_slayer_5000_log_level' );
		
		// API settings with sanitization callbacks
		register_setting( 'spam_slayer_5000_api', 'spam_slayer_5000_openai_settings', array(
			'sanitize_callback' => array( $this, 'sanitize_api_settings' ),
		) );
		register_setting( 'spam_slayer_5000_api', 'spam_slayer_5000_claude_settings', array(
			'sanitize_callback' => array( $this, 'sanitize_api_settings' ),
		) );
		
		// Advanced settings
		register_setting( 'spam_slayer_5000_advanced', 'spam_slayer_5000_retention_days' );
		register_setting( 'spam_slayer_5000_advanced', 'spam_slayer_5000_daily_budget_limit' );
		register_setting( 'spam_slayer_5000_advanced', 'spam_slayer_5000_notification_email' );
		register_setting( 'spam_slayer_5000_advanced', 'spam_slayer_5000_notification_threshold' );
		register_setting( 'spam_slayer_5000_advanced', 'spam_slayer_5000_cache_responses' );
		register_setting( 'spam_slayer_5000_advanced', 'spam_slayer_5000_cache_duration' );
		register_setting( 'spam_slayer_5000_advanced', 'spam_slayer_5000_daily_report' );
	}

	/**
	 * Display submissions page.
	 *
	 * @since    1.0.0
	 */
	public function display_submissions_page() {
		require_once SPAM_SLAYER_5000_PATH . 'admin/partials/submissions-display.php';
	}

	/**
	 * Display analytics page.
	 *
	 * @since    1.0.0
	 */
	public function display_analytics_page() {
		require_once SPAM_SLAYER_5000_PATH . 'admin/partials/analytics-display.php';
	}

	/**
	 * Display whitelist page.
	 *
	 * @since    1.0.0
	 */
	public function display_whitelist_page() {
		require_once SPAM_SLAYER_5000_PATH . 'admin/partials/whitelist-display.php';
	}

	/**
	 * Display blocklist page.
	 *
	 * @since    1.1.0
	 */
	public function display_blocklist_page() {
		require_once SPAM_SLAYER_5000_PATH . 'admin/partials/blocklist-display.php';
	}

	/**
	 * Display settings page.
	 *
	 * @since    1.0.0
	 */
	public function display_settings_page() {
		require_once SPAM_SLAYER_5000_PATH . 'admin/partials/settings-display.php';
	}

	/**
	 * Display logs page.
	 *
	 * @since    1.0.0
	 */
	public function display_logs_page() {
		require_once SPAM_SLAYER_5000_PATH . 'admin/partials/logs-display.php';
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
			admin_url( 'admin.php?page=spam-slayer-5000-settings' ),
			__( 'Settings', 'spam-slayer-5000' )
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
			$settings = get_option( 'spam_slayer_5000_' . $provider . '_settings', array() );
			if ( ! empty( $settings['api_key'] ) && ! empty( $settings['enabled'] ) ) {
				$has_provider = true;
				break;
			}
		}
		
		if ( ! $has_provider && current_user_can( 'manage_options' ) ) {
			$screen = get_current_screen();
			if ( strpos( $screen->id, 'spam-slayer-5000' ) !== false ) {
				?>
				<div class="notice notice-warning is-dismissible">
					<p>
						<?php 
						printf(
							__( 'Spam Slayer 5000 requires at least one AI provider to be configured. <a href="%s">Configure providers now</a>.', 'spam-slayer-5000' ),
							admin_url( 'admin.php?page=spam-slayer-5000-settings&tab=api' )
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
				'spam_slayer_5000_dashboard',
				__( 'Spam Slayer 5000 Overview', 'spam-slayer-5000' ),
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
		$database = new Spam_Slayer_5000_Database();
		
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
					<h4><?php esc_html_e( 'Today\'s Submissions', 'spam-slayer-5000' ); ?></h4>
					<p class="sfs-stat-number"><?php echo esc_html( $today_stats ); ?></p>
				</div>
				<div class="sfs-stat">
					<h4><?php esc_html_e( 'Spam Blocked', 'spam-slayer-5000' ); ?></h4>
					<p class="sfs-stat-number"><?php echo esc_html( $today_spam ); ?></p>
				</div>
				<div class="sfs-stat">
					<h4><?php esc_html_e( 'Today\'s Cost', 'spam-slayer-5000' ); ?></h4>
					<p class="sfs-stat-number">$<?php echo number_format( $total_cost, 4 ); ?></p>
				</div>
			</div>
			<p class="sfs-dashboard-links">
				<a href="<?php echo admin_url( 'admin.php?page=spam-slayer-5000' ); ?>" class="button">
					<?php esc_html_e( 'View Submissions', 'spam-slayer-5000' ); ?>
				</a>
				<a href="<?php echo admin_url( 'admin.php?page=spam-slayer-5000-analytics' ); ?>" class="button">
					<?php esc_html_e( 'View Analytics', 'spam-slayer-5000' ); ?>
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
		$daily_limit = get_option( 'spam_slayer_5000_daily_budget_limit', 10.00 );
		
		if ( $daily_limit <= 0 ) {
			return;
		}
		
		$database = new Spam_Slayer_5000_Database();
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
						__( 'Spam Slayer 5000: You have used %s%% of your daily budget limit ($%s of $%s).', 'spam-slayer-5000' ),
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
		require_once SPAM_SLAYER_5000_PATH . 'includes/class-security.php';
		
		$sanitized = array();
		
		// Sanitize and encrypt API key
		if ( isset( $settings['api_key'] ) ) {
			// Use sanitize_text_field instead of 'key' type to preserve API key format
			$api_key = sanitize_text_field( $settings['api_key'] );
			if ( ! empty( $api_key ) ) {
				$sanitized['api_key'] = Spam_Slayer_5000_Security::encrypt( $api_key );
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

	/**
	 * Display settings updated notice.
	 *
	 * @since    1.0.0
	 */
	public function settings_updated_notice() {
		// Only show on our settings page
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'spam-slayer-5000-settings' ) {
			return;
		}

		// Check if settings were just updated
		if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] === 'true' ) {
			add_settings_error(
				'spam_slayer_5000_messages',
				'spam_slayer_5000_message',
				__( 'Settings saved successfully!', 'spam-slayer-5000' ),
				'updated'
			);
		}
	}
}