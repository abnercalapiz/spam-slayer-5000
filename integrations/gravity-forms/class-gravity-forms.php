<?php
/**
 * Gravity Forms Integration.
 *
 * @since      1.0.0
 * @package    Smart_Form_Shield
 * @subpackage Smart_Form_Shield/integrations
 */

class Smart_Form_Shield_Gravity_Forms {

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
	 * Validate form submission.
	 *
	 * @since    1.0.0
	 * @param    array    $validation_result    Validation result.
	 * @return   array                          Modified validation result.
	 */
	public function validate_submission( $validation_result ) {
		$form = $validation_result['form'];
		$entry = $this->get_entry_data( $form );
		
		// Check if form has spam protection enabled
		if ( ! $this->is_protection_enabled( $form ) ) {
			return $validation_result;
		}

		// Check whitelist first
		$database = new Smart_Form_Shield_Database();
		$email = $this->extract_email( $entry );
		
		if ( ! empty( $email ) && $database->is_whitelisted( $email ) ) {
			$this->log_submission_to_database( $entry, $form, 'whitelist', 0 );
			return $validation_result;
		}

		// Check cache
		$cache_key = $this->get_cache_key( $entry );
		$cache = new Smart_Form_Shield_Cache();
		$cached_result = $cache->get( $cache_key );
		
		if ( $cached_result !== false ) {
			return $this->apply_validation_result( $validation_result, $cached_result );
		}

		// Analyze with AI provider
		$provider = Smart_Form_Shield_Provider_Factory::get_primary_provider();
		
		if ( ! $provider ) {
			// No provider available, allow submission
			return $validation_result;
		}

		$analysis = $provider->analyze( $entry );
		
		// Cache the result
		if ( ! isset( $analysis['error'] ) ) {
			$cache->set( $cache_key, $analysis, get_option( 'smart_form_shield_cache_duration', 3600 ) );
		}

		// Log submission
		$this->log_submission_to_database( $entry, $form, $analysis );

		// Apply validation
		return $this->apply_validation_result( $validation_result, $analysis );
	}

	/**
	 * Log submission after saving.
	 *
	 * @since    1.0.0
	 * @param    array    $entry    Entry data.
	 * @param    array    $form     Form data.
	 * @return   array              Entry data.
	 */
	public function log_submission( $entry, $form ) {
		// This method is called after entry is saved
		// Update our submission record with the entry ID
		$submission_id = gform_get_meta( $entry['id'], 'sfs_submission_id' );
		
		if ( $submission_id ) {
			global $wpdb;
			$wpdb->update(
				SMART_FORM_SHIELD_SUBMISSIONS_TABLE,
				array( 'form_id' => $entry['id'] ),
				array( 'id' => $submission_id ),
				array( '%s' ),
				array( '%d' )
			);
		}
		
		return $entry;
	}

	/**
	 * After submission hook.
	 *
	 * @since    1.0.0
	 * @param    array    $entry    Entry data.
	 * @param    array    $form     Form data.
	 */
	public function after_submission( $entry, $form ) {
		// Send notifications if needed
		$submission_id = gform_get_meta( $entry['id'], 'sfs_submission_id' );
		
		if ( ! $submission_id ) {
			return;
		}

		$database = new Smart_Form_Shield_Database();
		$submissions = $database->get_submissions( array(
			'id' => $submission_id,
			'limit' => 1,
		) );

		if ( empty( $submissions ) ) {
			return;
		}

		$submission = $submissions[0];
		$threshold = get_option( 'smart_form_shield_notification_threshold', 90 );
		
		if ( $submission['spam_score'] >= $threshold ) {
			$this->send_notification( $submission, $entry, $form );
		}
	}

	/**
	 * Add form settings menu item.
	 *
	 * @since    1.0.0
	 * @param    array    $menu_items    Menu items.
	 * @param    int      $form_id       Form ID.
	 * @return   array                   Modified menu items.
	 */
	public function add_form_settings_menu( $menu_items, $form_id ) {
		$menu_items[] = array(
			'name' => 'smart_form_shield',
			'label' => __( 'Smart Form Shield', 'smart-form-shield' ),
			'icon' => 'dashicons-shield',
		);
		
		return $menu_items;
	}

	/**
	 * Form settings page.
	 *
	 * @since    1.0.0
	 */
	public function form_settings_page() {
		GFFormSettings::page_header();
		
		$form = $this->get_current_form();
		$settings = $this->get_form_settings( $form['id'] );
		
		if ( $this->is_save_postback() ) {
			$settings = $this->save_form_settings( $form['id'] );
			GFCommon::add_message( __( 'Settings saved successfully.', 'smart-form-shield' ) );
		}
		?>
		
		<h3><?php esc_html_e( 'Smart Form Shield Settings', 'smart-form-shield' ); ?></h3>
		
		<form method="post">
			<?php wp_nonce_field( 'sfs_form_settings', 'sfs_form_settings_nonce' ); ?>
			
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Enable Spam Protection', 'smart-form-shield' ); ?></th>
					<td>
						<input type="checkbox" name="sfs_enabled" id="sfs_enabled" value="1" 
							<?php checked( $settings['enabled'], true ); ?> />
						<label for="sfs_enabled">
							<?php esc_html_e( 'Enable AI-powered spam detection for this form', 'smart-form-shield' ); ?>
						</label>
					</td>
				</tr>
				
				<tr>
					<th><?php esc_html_e( 'Custom Spam Threshold', 'smart-form-shield' ); ?></th>
					<td>
						<input type="checkbox" name="sfs_use_custom_threshold" id="sfs_use_custom_threshold" value="1" 
							<?php checked( $settings['use_custom_threshold'], true ); ?> />
						<label for="sfs_use_custom_threshold">
							<?php esc_html_e( 'Use custom threshold for this form', 'smart-form-shield' ); ?>
						</label>
						<br><br>
						<input type="number" name="sfs_custom_threshold" id="sfs_custom_threshold" 
							value="<?php echo esc_attr( $settings['custom_threshold'] ); ?>" 
							min="0" max="100" style="width: 80px;" />
						<label for="sfs_custom_threshold">
							<?php esc_html_e( 'Spam threshold (0-100)', 'smart-form-shield' ); ?>
						</label>
					</td>
				</tr>
				
				<tr>
					<th><?php esc_html_e( 'Custom Error Message', 'smart-form-shield' ); ?></th>
					<td>
						<textarea name="sfs_error_message" id="sfs_error_message" rows="3" class="large-text">
<?php echo esc_textarea( $settings['error_message'] ); ?></textarea>
						<p class="description">
							<?php esc_html_e( 'Message shown when submission is blocked as spam. Leave empty for default.', 'smart-form-shield' ); ?>
						</p>
					</td>
				</tr>
			</table>
			
			<p class="submit">
				<input type="submit" name="submit" class="button-primary" 
					value="<?php esc_attr_e( 'Save Settings', 'smart-form-shield' ); ?>" />
			</p>
		</form>
		
		<?php
		GFFormSettings::page_footer();
	}

	/**
	 * Check if protection is enabled for form.
	 *
	 * @since    1.0.0
	 * @param    array    $form    Form data.
	 * @return   bool              True if enabled.
	 */
	private function is_protection_enabled( $form ) {
		$settings = $this->get_form_settings( $form['id'] );
		return $settings['enabled'];
	}

	/**
	 * Get form settings.
	 *
	 * @since    1.0.0
	 * @param    int      $form_id    Form ID.
	 * @return   array                Settings.
	 */
	private function get_form_settings( $form_id ) {
		$defaults = array(
			'enabled' => true,
			'use_custom_threshold' => false,
			'custom_threshold' => get_option( 'smart_form_shield_spam_threshold', 75 ),
			'error_message' => '',
		);
		
		$settings = get_option( 'sfs_gf_settings_' . $form_id, array() );
		return wp_parse_args( $settings, $defaults );
	}

	/**
	 * Save form settings.
	 *
	 * @since    1.0.0
	 * @param    int      $form_id    Form ID.
	 * @return   array                Saved settings.
	 */
	private function save_form_settings( $form_id ) {
		check_admin_referer( 'sfs_form_settings', 'sfs_form_settings_nonce' );
		
		$settings = array(
			'enabled' => isset( $_POST['sfs_enabled'] ),
			'use_custom_threshold' => isset( $_POST['sfs_use_custom_threshold'] ),
			'custom_threshold' => absint( $_POST['sfs_custom_threshold'] ),
			'error_message' => sanitize_textarea_field( $_POST['sfs_error_message'] ),
		);
		
		update_option( 'sfs_gf_settings_' . $form_id, $settings );
		
		return $settings;
	}

	/**
	 * Get entry data from form.
	 *
	 * @since    1.0.0
	 * @param    array    $form    Form data.
	 * @return   array             Entry data.
	 */
	private function get_entry_data( $form ) {
		$entry = array();
		
		foreach ( $form['fields'] as $field ) {
			$value = rgpost( 'input_' . $field->id );
			
			if ( ! empty( $value ) ) {
				$entry[ $field->label ] = $value;
			}
		}
		
		return $entry;
	}

	/**
	 * Extract email from entry data.
	 *
	 * @since    1.0.0
	 * @param    array    $entry    Entry data.
	 * @return   string             Email address.
	 */
	private function extract_email( $entry ) {
		foreach ( $entry as $key => $value ) {
			if ( stripos( $key, 'email' ) !== false || stripos( $key, 'e-mail' ) !== false ) {
				$email = filter_var( $value, FILTER_VALIDATE_EMAIL );
				if ( $email ) {
					return $email;
				}
			}
		}
		
		// Try to find email in values
		foreach ( $entry as $value ) {
			if ( is_string( $value ) ) {
				$email = filter_var( $value, FILTER_VALIDATE_EMAIL );
				if ( $email ) {
					return $email;
				}
			}
		}
		
		return '';
	}

	/**
	 * Get cache key for entry.
	 *
	 * @since    1.0.0
	 * @param    array    $entry    Entry data.
	 * @return   string             Cache key.
	 */
	private function get_cache_key( $entry ) {
		return 'sfs_' . md5( wp_json_encode( $entry ) );
	}

	/**
	 * Apply validation result.
	 *
	 * @since    1.0.0
	 * @param    array    $validation_result    Original validation.
	 * @param    array    $analysis             Analysis result.
	 * @return   array                          Modified validation.
	 */
	private function apply_validation_result( $validation_result, $analysis ) {
		$form = $validation_result['form'];
		$settings = $this->get_form_settings( $form['id'] );
		
		$threshold = $settings['use_custom_threshold'] 
			? $settings['custom_threshold'] 
			: get_option( 'smart_form_shield_spam_threshold', 75 );
		
		if ( isset( $analysis['spam_score'] ) && $analysis['spam_score'] >= $threshold ) {
			$validation_result['is_valid'] = false;
			
			// Mark first field as invalid
			foreach ( $form['fields'] as &$field ) {
				if ( ! $field->is_administrative() && ! $field->isRequired ) {
					$field->failed_validation = true;
					$field->validation_message = ! empty( $settings['error_message'] ) 
						? $settings['error_message']
						: __( 'Your submission has been blocked as potential spam. Please try again or contact support.', 'smart-form-shield' );
					break;
				}
			}
			
			$validation_result['form'] = $form;
		}
		
		// Store submission ID for later reference
		if ( isset( $analysis['submission_id'] ) ) {
			gform_update_meta( $form['id'], 'sfs_submission_id', $analysis['submission_id'] );
		}
		
		return $validation_result;
	}

	/**
	 * Log submission to database.
	 *
	 * @since    1.0.0
	 * @param    array         $entry       Entry data.
	 * @param    array         $form        Form data.
	 * @param    array|string  $analysis    Analysis result or status.
	 * @param    float         $spam_score  Spam score if status provided.
	 * @return   int                        Submission ID.
	 */
	private function log_submission_to_database( $entry, $form, $analysis, $spam_score = 0 ) {
		$database = new Smart_Form_Shield_Database();
		
		$data = array(
			'form_type' => 'gravity_forms',
			'form_id' => $form['id'],
			'submission_data' => $entry,
		);
		
		if ( is_string( $analysis ) ) {
			// Status provided (e.g., 'whitelist')
			$data['status'] = $analysis;
			$data['spam_score'] = $spam_score;
		} else {
			// Full analysis provided
			$data['spam_score'] = isset( $analysis['spam_score'] ) ? $analysis['spam_score'] : 0;
			$data['provider_used'] = isset( $analysis['provider'] ) ? $analysis['provider'] : null;
			$data['provider_response'] = $analysis;
			// Get threshold
			$settings = $this->get_form_settings( $form['id'] );
			$threshold = $settings['use_custom_threshold'] 
				? $settings['custom_threshold'] 
				: get_option( 'smart_form_shield_spam_threshold', 75 );
			
			// Set status based on threshold, not is_spam
			$spam_score = isset( $analysis['spam_score'] ) ? $analysis['spam_score'] : 0;
			$data['status'] = $spam_score >= $threshold ? 'spam' : 'approved';
		}
		
		$submission_id = $database->insert_submission( $data );
		
		if ( is_array( $analysis ) ) {
			$analysis['submission_id'] = $submission_id;
		}
		
		return $submission_id;
	}

	/**
	 * Send notification email.
	 *
	 * @since    1.0.0
	 * @param    array    $submission    Submission data.
	 * @param    array    $entry         Entry data.
	 * @param    array    $form          Form data.
	 */
	private function send_notification( $submission, $entry, $form ) {
		$to = get_option( 'smart_form_shield_notification_email', get_option( 'admin_email' ) );
		$subject = sprintf(
			__( '[Smart Form Shield] High spam score detected on form: %s', 'smart-form-shield' ),
			$form['title']
		);
		
		$message = sprintf(
			__( "A submission with a high spam score has been detected.\n\nForm: %s\nSpam Score: %d%%\nProvider: %s\n\nView details: %s", 'smart-form-shield' ),
			$form['title'],
			$submission['spam_score'],
			$submission['provider_used'],
			admin_url( 'admin.php?page=smart-form-shield-submissions&id=' . $submission['id'] )
		);
		
		wp_mail( $to, $subject, $message );
	}

	/**
	 * Check if this is a save postback.
	 *
	 * @since    1.0.0
	 * @return   bool    True if save postback.
	 */
	private function is_save_postback() {
		return isset( $_POST['sfs_form_settings_nonce'] ) && 
			wp_verify_nonce( $_POST['sfs_form_settings_nonce'], 'sfs_form_settings' );
	}

	/**
	 * Get current form.
	 *
	 * @since    1.0.0
	 * @return   array    Form data.
	 */
	private function get_current_form() {
		return rgget( 'id' ) ? GFAPI::get_form( rgget( 'id' ) ) : array();
	}
}