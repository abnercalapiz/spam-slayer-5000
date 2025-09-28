<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @since      1.0.0
 * @package    Spam_Slayer_5000
 * @subpackage Spam_Slayer_5000/public
 */

class Spam_Slayer_5000_Public {

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
	 * @param    string    $plugin_name    The name of the plugin.
	 * @param    string    $version        The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		// Only enqueue if needed (e.g., for specific form styling)
		if ( $this->should_enqueue_assets() ) {
			wp_enqueue_style( 
				$this->plugin_name, 
				SPAM_SLAYER_5000_URL . 'public/css/spam-slayer-5000-public.css', 
				array(), 
				$this->version, 
				'all' 
			);
		}
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		// Only enqueue if needed (e.g., for client-side validation)
		if ( $this->should_enqueue_assets() ) {
			wp_enqueue_script( 
				$this->plugin_name, 
				SPAM_SLAYER_5000_URL . 'public/js/spam-slayer-5000-public.js', 
				array( 'jquery' ), 
				$this->version, 
				false 
			);

			wp_localize_script( $this->plugin_name, 'ss5k_public', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'ss5k_public_nonce' ),
				'strings' => array(
					'validating' => __( 'Validating submission...', 'spam-slayer-5000' ),
					'error' => __( 'An error occurred. Please try again.', 'spam-slayer-5000' ),
				),
			) );
		}
	}

	/**
	 * Check if assets should be enqueued.
	 *
	 * @since    1.0.0
	 * @return   bool    True if assets should be enqueued.
	 */
	private function should_enqueue_assets() {
		// Check if current page has forms that need our assets
		// This helps with performance by not loading unnecessary assets
		
		// Check for Gravity Forms
		if ( class_exists( 'GFForms' ) && get_option( 'spam_slayer_5000_enable_gravity_forms', true ) ) {
			if ( is_singular() ) {
				global $post;
				if ( has_shortcode( $post->post_content, 'gravityform' ) ) {
					return true;
				}
			}
		}

		// Check for Elementor Forms
		if ( did_action( 'elementor/loaded' ) && get_option( 'spam_slayer_5000_enable_elementor_forms', true ) ) {
			if ( \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
				return true;
			}
			
			if ( is_singular() ) {
				$document = \Elementor\Plugin::$instance->documents->get( get_the_ID() );
				if ( $document && $document->is_built_with_elementor() ) {
					return true;
				}
			}
		}

		// Allow filtering
		return apply_filters( 'spam_slayer_5000_enqueue_public_assets', false );
	}

	/**
	 * Add honeypot field to forms.
	 *
	 * @since    1.0.0
	 * @param    string    $form_html    Form HTML.
	 * @return   string                  Modified form HTML.
	 */
	public function add_honeypot_field( $form_html ) {
		if ( ! get_option( 'spam_slayer_5000_enable_honeypot', true ) ) {
			return $form_html;
		}

		$honeypot_field = '<div style="position: absolute; left: -9999px;">';
		$honeypot_field .= '<label for="ss5k_website">' . __( 'Website', 'spam-slayer-5000' ) . '</label>';
		$honeypot_field .= '<input type="text" name="ss5k_website" id="ss5k_website" value="" tabindex="-1" autocomplete="off">';
		$honeypot_field .= '</div>';

		// Insert before closing form tag
		$form_html = str_replace( '</form>', $honeypot_field . '</form>', $form_html );

		return $form_html;
	}

	/**
	 * Check honeypot field.
	 *
	 * @since    1.0.0
	 * @return   bool    True if honeypot is empty (legitimate).
	 */
	public static function check_honeypot() {
		if ( ! get_option( 'spam_slayer_5000_enable_honeypot', true ) ) {
			return true;
		}

		return empty( $_POST['ss5k_website'] );
	}

	/**
	 * Add noscript message.
	 *
	 * @since    1.0.0
	 */
	public function add_noscript_message() {
		if ( ! $this->should_enqueue_assets() ) {
			return;
		}

		?>
		<noscript>
			<style>
				.ss5k-js-required { display: none !important; }
				.ss5k-noscript-message { 
					display: block !important; 
					padding: 15px; 
					background: #fff3cd; 
					border: 1px solid #ffeaa7;
					color: #856404;
					margin: 20px 0;
					border-radius: 4px;
				}
			</style>
			<div class="sfs-noscript-message" style="display: none;">
				<?php esc_html_e( 'JavaScript is required for form spam protection. Please enable JavaScript in your browser.', 'spam-slayer-5000' ); ?>
			</div>
		</noscript>
		<?php
	}

	/**
	 * Rate limit check.
	 *
	 * @since    1.0.0
	 * @return   bool    True if within rate limit.
	 */
	public static function check_rate_limit() {
		if ( ! get_option( 'spam_slayer_5000_enable_rate_limit', true ) ) {
			return true;
		}

		$ip = self::get_user_ip();
		if ( empty( $ip ) ) {
			return true;
		}

		$transient_key = 'ss5k_rate_limit_' . md5( $ip );
		$submissions = get_transient( $transient_key );

		if ( $submissions === false ) {
			set_transient( $transient_key, 1, MINUTE_IN_SECONDS );
			return true;
		}

		$max_submissions = get_option( 'spam_slayer_5000_rate_limit_max', 5 );
		
		if ( $submissions >= $max_submissions ) {
			return false;
		}

		set_transient( $transient_key, $submissions + 1, MINUTE_IN_SECONDS );
		return true;
	}

	/**
	 * Get user IP address.
	 *
	 * @since    1.0.0
	 * @return   string    IP address.
	 */
	private static function get_user_ip() {
		$ip = '';

		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( $_SERVER['HTTP_CLIENT_IP'] );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field( $_SERVER['HTTP_X_FORWARDED_FOR'] );
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( $_SERVER['REMOTE_ADDR'] );
		}

		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
	}

	/**
	 * Add custom body class.
	 *
	 * @since    1.0.0
	 * @param    array    $classes    Body classes.
	 * @return   array                Modified body classes.
	 */
	public function add_body_class( $classes ) {
		if ( $this->should_enqueue_assets() ) {
			$classes[] = 'spam-slayer-5000-active';
		}

		return $classes;
	}
}