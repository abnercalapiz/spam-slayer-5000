<?php
/**
 * Settings admin page display.
 *
 * @since      1.0.0
 * @package    Smart_Form_Shield
 * @subpackage Smart_Form_Shield/admin/partials
 */

// Security check
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Get active tab
$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general';
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	
	<nav class="nav-tab-wrapper">
		<a href="?page=smart-form-shield-settings&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'General', 'smart-form-shield' ); ?>
		</a>
		<a href="?page=smart-form-shield-settings&tab=api" class="nav-tab <?php echo $active_tab === 'api' ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'API Settings', 'smart-form-shield' ); ?>
		</a>
		<a href="?page=smart-form-shield-settings&tab=advanced" class="nav-tab <?php echo $active_tab === 'advanced' ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Advanced', 'smart-form-shield' ); ?>
		</a>
	</nav>
	
	<form method="post" action="options.php">
		<?php
		// Add hidden field to preserve tab after save
		if ( $active_tab !== 'general' ) {
			echo '<input type="hidden" name="_wp_http_referer" value="' . esc_attr( admin_url( 'admin.php?page=smart-form-shield-settings&tab=' . $active_tab ) ) . '" />';
		}
		
		switch ( $active_tab ) {
			case 'api':
				settings_fields( 'smart_form_shield_api' );
				?>
				<div class="sfs-settings-section">
					<h2><?php esc_html_e( 'API Provider Settings', 'smart-form-shield' ); ?></h2>
					<p><?php esc_html_e( 'Configure your AI provider API keys and settings.', 'smart-form-shield' ); ?></p>
					
					<?php
					$providers = array(
						'openai' => __( 'OpenAI', 'smart-form-shield' ),
						'claude' => __( 'Claude', 'smart-form-shield' ),
					);
					
					foreach ( $providers as $provider_key => $provider_name ) :
						$settings = get_option( 'smart_form_shield_' . $provider_key . '_settings', array() );
						$is_configured = ! empty( $settings['api_key'] );
						?>
						<div class="sfs-provider-section">
							<h3>
								<span class="sfs-provider-status <?php echo $is_configured ? 'active' : 'inactive'; ?>"></span>
								<?php echo esc_html( $provider_name ); ?>
							</h3>
							
							<table class="form-table">
								<tr>
									<th scope="row">
										<label for="<?php echo esc_attr( $provider_key ); ?>_api_key">
											<?php esc_html_e( 'API Key', 'smart-form-shield' ); ?>
										</label>
									</th>
									<td>
										<input type="password" 
											id="<?php echo esc_attr( $provider_key ); ?>_api_key" 
											name="smart_form_shield_<?php echo esc_attr( $provider_key ); ?>_settings[api_key]" 
											value="<?php echo ! empty( $settings['api_key'] ) ? $settings['api_key'] : ''; ?>" 
											class="regular-text" 
											placeholder="<?php esc_attr_e( 'Enter API key', 'smart-form-shield' ); ?>" />
										<p class="description">
											<?php
											if ( $provider_key === 'openai' ) {
												printf(
													__( 'Get your API key from <a href="%s" target="_blank">OpenAI Dashboard</a>', 'smart-form-shield' ),
													'https://platform.openai.com/api-keys'
												);
											} elseif ( $provider_key === 'claude' ) {
												printf(
													__( 'Get your API key from <a href="%s" target="_blank">Anthropic Console</a>', 'smart-form-shield' ),
													'https://console.anthropic.com/'
												);
											}
											?>
										</p>
									</td>
								</tr>
								
								<?php if ( $provider_key === 'gemini' ) : ?>
								<tr>
									<th scope="row">
										<label for="<?php echo esc_attr( $provider_key ); ?>_project_id">
											<?php esc_html_e( 'Project ID', 'smart-form-shield' ); ?>
										</label>
									</th>
									<td>
										<input type="text" 
											id="<?php echo esc_attr( $provider_key ); ?>_project_id" 
											name="smart_form_shield_<?php echo esc_attr( $provider_key ); ?>_settings[project_id]" 
											value="<?php echo isset( $settings['project_id'] ) ? esc_attr( $settings['project_id'] ) : ''; ?>" 
											class="regular-text" 
											placeholder="<?php esc_attr_e( 'your-gcp-project-id', 'smart-form-shield' ); ?>" />
										<p class="description">
											<?php esc_html_e( 'Your Google Cloud Project ID', 'smart-form-shield' ); ?>
										</p>
									</td>
								</tr>
								
								<tr>
									<th scope="row">
										<label for="<?php echo esc_attr( $provider_key ); ?>_region">
											<?php esc_html_e( 'Region', 'smart-form-shield' ); ?>
										</label>
									</th>
									<td>
										<select id="<?php echo esc_attr( $provider_key ); ?>_region" 
											name="smart_form_shield_<?php echo esc_attr( $provider_key ); ?>_settings[region]">
											<?php
											$regions = array(
												'us-central1' => 'US Central (Iowa)',
												'us-east4' => 'US East (Virginia)',
												'us-west1' => 'US West (Oregon)',
												'us-west4' => 'US West (Nevada)',
												'europe-west1' => 'Europe West (Belgium)',
												'europe-west4' => 'Europe West (Netherlands)',
												'asia-northeast1' => 'Asia Northeast (Tokyo)',
												'asia-southeast1' => 'Asia Southeast (Singapore)',
											);
											$current_region = isset( $settings['region'] ) ? $settings['region'] : 'us-central1';
											foreach ( $regions as $region_key => $region_name ) :
												?>
												<option value="<?php echo esc_attr( $region_key ); ?>" <?php selected( $current_region, $region_key ); ?>>
													<?php echo esc_html( $region_name ); ?>
												</option>
											<?php endforeach; ?>
										</select>
									</td>
								</tr>
								<?php endif; ?>
								
								<tr>
									<th scope="row">
										<label for="<?php echo esc_attr( $provider_key ); ?>_model">
											<?php esc_html_e( 'Model', 'smart-form-shield' ); ?>
										</label>
									</th>
									<td>
										<select id="<?php echo esc_attr( $provider_key ); ?>_model" 
											name="smart_form_shield_<?php echo esc_attr( $provider_key ); ?>_settings[model]">
											<?php
											// Dynamically load models from provider class
											$models = array();
											$provider = Smart_Form_Shield_Provider_Factory::create( $provider_key );
											if ( $provider && method_exists( $provider, 'get_models' ) ) {
												$provider_models = $provider->get_models();
												foreach ( $provider_models as $model_key => $model_info ) {
													$models[ $model_key ] = $model_info['name'];
												}
											} else {
												// Fallback if provider can't be created
												if ( $provider_key === 'openai' ) {
													$models = array(
														'gpt-5-nano-2025-08-07' => 'GPT-5 Nano',
														'gpt-5-mini-2025-08-07' => 'GPT-5 Mini',
													);
												} elseif ( $provider_key === 'claude' ) {
													$models = array(
														'claude-3-5-haiku-latest' => 'Claude 3.5 Haiku Latest',
														'claude-3-7-sonnet-latest' => 'Claude 3.7 Sonnet Latest',
														'claude-3-haiku-20240307' => 'Claude 3 Haiku',
													);
												} elseif ( $provider_key === 'gemini' ) {
													$models = array(
														'gemini-2.5-flash' => 'Gemini 2.5 Flash',
														'gemini-2.5-flash-lite' => 'Gemini 2.5 Flash Lite',
													);
												}
											}
											
											$current_model = isset( $settings['model'] ) ? $settings['model'] : '';
											foreach ( $models as $model_key => $model_name ) :
												?>
												<option value="<?php echo esc_attr( $model_key ); ?>" <?php selected( $current_model, $model_key ); ?>>
													<?php echo esc_html( $model_name ); ?>
												</option>
											<?php endforeach; ?>
										</select>
									</td>
								</tr>
								
								<tr>
									<th scope="row">
										<label for="<?php echo esc_attr( $provider_key ); ?>_enabled">
											<?php esc_html_e( 'Enable Provider', 'smart-form-shield' ); ?>
										</label>
									</th>
									<td>
										<input type="checkbox" 
											id="<?php echo esc_attr( $provider_key ); ?>_enabled" 
											name="smart_form_shield_<?php echo esc_attr( $provider_key ); ?>_settings[enabled]" 
											value="1" 
											<?php checked( ! empty( $settings['enabled'] ) ); ?> />
										<label for="<?php echo esc_attr( $provider_key ); ?>_enabled">
											<?php esc_html_e( 'Enable this AI provider', 'smart-form-shield' ); ?>
										</label>
									</td>
								</tr>
								
								<tr>
									<th scope="row"></th>
									<td>
										<button type="button" class="button sfs-test-provider" data-provider="<?php echo esc_attr( $provider_key ); ?>">
											<?php esc_html_e( 'Test Connection', 'smart-form-shield' ); ?>
										</button>
										<span class="sfs-test-status"></span>
									</td>
								</tr>
							</table>
						</div>
						<hr>
					<?php endforeach; ?>
				</div>
				<?php
				break;
				
			case 'advanced':
				settings_fields( 'smart_form_shield_advanced' );
				?>
				<div class="sfs-settings-section">
					<h2><?php esc_html_e( 'Advanced Settings', 'smart-form-shield' ); ?></h2>
					
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="retention_days">
									<?php esc_html_e( 'Data Retention', 'smart-form-shield' ); ?>
								</label>
							</th>
							<td>
								<input type="number" id="retention_days" name="smart_form_shield_retention_days" 
									value="<?php echo esc_attr( get_option( 'smart_form_shield_retention_days', 30 ) ); ?>" 
									min="1" max="365" />
								<span><?php esc_html_e( 'days', 'smart-form-shield' ); ?></span>
								<p class="description">
									<?php esc_html_e( 'Number of days to retain submission data and logs.', 'smart-form-shield' ); ?>
								</p>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="daily_budget_limit">
									<?php esc_html_e( 'Daily Budget Limit', 'smart-form-shield' ); ?>
								</label>
							</th>
							<td>
								<span>$</span>
								<input type="number" id="daily_budget_limit" name="smart_form_shield_daily_budget_limit" 
									value="<?php echo esc_attr( get_option( 'smart_form_shield_daily_budget_limit', 10.00 ) ); ?>" 
									min="0" step="0.01" style="width: 100px;" />
								<p class="description">
									<?php esc_html_e( 'Maximum daily spend on AI API calls. Set to 0 to disable limit.', 'smart-form-shield' ); ?>
								</p>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="notification_email">
									<?php esc_html_e( 'Notification Email', 'smart-form-shield' ); ?>
								</label>
							</th>
							<td>
								<input type="email" id="notification_email" name="smart_form_shield_notification_email" 
									value="<?php echo esc_attr( get_option( 'smart_form_shield_notification_email', get_option( 'admin_email' ) ) ); ?>" 
									class="regular-text" />
								<p class="description">
									<?php esc_html_e( 'Email address for high spam score notifications.', 'smart-form-shield' ); ?>
								</p>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="notification_threshold">
									<?php esc_html_e( 'Notification Threshold', 'smart-form-shield' ); ?>
								</label>
							</th>
							<td>
								<input type="number" id="notification_threshold" name="smart_form_shield_notification_threshold" 
									value="<?php echo esc_attr( get_option( 'smart_form_shield_notification_threshold', 90 ) ); ?>" 
									min="0" max="100" />
								<span>%</span>
								<p class="description">
									<?php esc_html_e( 'Send notification when spam score exceeds this threshold.', 'smart-form-shield' ); ?>
								</p>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<?php esc_html_e( 'Caching', 'smart-form-shield' ); ?>
							</th>
							<td>
								<label>
									<input type="checkbox" name="smart_form_shield_cache_responses" value="1" 
										<?php checked( get_option( 'smart_form_shield_cache_responses', true ) ); ?> />
									<?php esc_html_e( 'Enable response caching', 'smart-form-shield' ); ?>
								</label>
								<p class="description">
									<?php esc_html_e( 'Cache AI responses to reduce API costs for duplicate submissions.', 'smart-form-shield' ); ?>
								</p>
								<br>
								<label>
									<?php esc_html_e( 'Cache Duration:', 'smart-form-shield' ); ?>
									<input type="number" name="smart_form_shield_cache_duration" 
										value="<?php echo esc_attr( get_option( 'smart_form_shield_cache_duration', 3600 ) ); ?>" 
										min="60" max="86400" style="width: 100px;" />
									<?php esc_html_e( 'seconds', 'smart-form-shield' ); ?>
								</label>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<?php esc_html_e( 'Reports', 'smart-form-shield' ); ?>
							</th>
							<td>
								<label>
									<input type="checkbox" name="smart_form_shield_daily_report" value="1" 
										<?php checked( get_option( 'smart_form_shield_daily_report', false ) ); ?> />
									<?php esc_html_e( 'Send daily analytics report', 'smart-form-shield' ); ?>
								</label>
								<p class="description">
									<?php esc_html_e( 'Receive a daily email with spam filtering statistics.', 'smart-form-shield' ); ?>
								</p>
							</td>
						</tr>
					</table>
				</div>
				<?php
				break;
				
			case 'general':
			default:
				settings_fields( 'smart_form_shield_general' );
				?>
				<div class="sfs-settings-section">
					<h2><?php esc_html_e( 'General Settings', 'smart-form-shield' ); ?></h2>
					
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="spam_threshold">
									<?php esc_html_e( 'Spam Threshold', 'smart-form-shield' ); ?>
								</label>
							</th>
							<td>
								<input type="number" id="spam_threshold" name="smart_form_shield_spam_threshold" 
									value="<?php echo esc_attr( get_option( 'smart_form_shield_spam_threshold', 75 ) ); ?>" 
									min="0" max="100" />
								<span>%</span>
								<p class="description">
									<?php esc_html_e( 'Submissions with spam score above this threshold will be blocked.', 'smart-form-shield' ); ?>
								</p>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="primary_provider">
									<?php esc_html_e( 'Primary AI Provider', 'smart-form-shield' ); ?>
								</label>
							</th>
							<td>
								<select id="primary_provider" name="smart_form_shield_primary_provider">
									<?php
									$primary = get_option( 'smart_form_shield_primary_provider', 'openai' );
									?>
									<option value="openai" <?php selected( $primary, 'openai' ); ?>><?php esc_html_e( 'OpenAI', 'smart-form-shield' ); ?></option>
									<option value="claude" <?php selected( $primary, 'claude' ); ?>><?php esc_html_e( 'Claude', 'smart-form-shield' ); ?></option>
								</select>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="fallback_provider">
									<?php esc_html_e( 'Fallback AI Provider', 'smart-form-shield' ); ?>
								</label>
							</th>
							<td>
								<select id="fallback_provider" name="smart_form_shield_fallback_provider">
									<?php
									$fallback = get_option( 'smart_form_shield_fallback_provider', 'claude' );
									?>
									<option value="openai" <?php selected( $fallback, 'openai' ); ?>><?php esc_html_e( 'OpenAI', 'smart-form-shield' ); ?></option>
									<option value="claude" <?php selected( $fallback, 'claude' ); ?>><?php esc_html_e( 'Claude', 'smart-form-shield' ); ?></option>
								</select>
								<p class="description">
									<?php esc_html_e( 'Provider to use if primary provider fails.', 'smart-form-shield' ); ?>
								</p>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<?php esc_html_e( 'Form Integrations', 'smart-form-shield' ); ?>
							</th>
							<td>
								<fieldset>
									<label>
										<input type="checkbox" name="smart_form_shield_enable_gravity_forms" value="1" 
											<?php checked( get_option( 'smart_form_shield_enable_gravity_forms', true ) ); ?>
											<?php echo class_exists( 'GFForms' ) ? '' : 'disabled'; ?> />
										<?php esc_html_e( 'Enable Gravity Forms Protection', 'smart-form-shield' ); ?>
										<?php if ( ! class_exists( 'GFForms' ) ) : ?>
											<span class="description"><?php esc_html_e( '(Gravity Forms not installed)', 'smart-form-shield' ); ?></span>
										<?php endif; ?>
									</label>
									<br>
									<label>
										<input type="checkbox" name="smart_form_shield_enable_elementor_forms" value="1" 
											<?php checked( get_option( 'smart_form_shield_enable_elementor_forms', true ) ); ?>
											<?php echo did_action( 'elementor/loaded' ) ? '' : 'disabled'; ?> />
										<?php esc_html_e( 'Enable Elementor Forms Protection', 'smart-form-shield' ); ?>
										<?php if ( ! did_action( 'elementor/loaded' ) ) : ?>
											<span class="description"><?php esc_html_e( '(Elementor Pro not installed)', 'smart-form-shield' ); ?></span>
										<?php endif; ?>
									</label>
								</fieldset>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<?php esc_html_e( 'Features', 'smart-form-shield' ); ?>
							</th>
							<td>
								<fieldset>
									<label>
										<input type="checkbox" name="smart_form_shield_enable_whitelist" value="1" 
											<?php checked( get_option( 'smart_form_shield_enable_whitelist', true ) ); ?> />
										<?php esc_html_e( 'Enable Email Whitelist', 'smart-form-shield' ); ?>
									</label>
									<p class="description">
										<?php esc_html_e( 'Allow whitelisting trusted email addresses.', 'smart-form-shield' ); ?>
									</p>
									<br>
									<label>
										<input type="checkbox" name="smart_form_shield_enable_logging" value="1" 
											<?php checked( get_option( 'smart_form_shield_enable_logging', true ) ); ?> />
										<?php esc_html_e( 'Enable Debug Logging', 'smart-form-shield' ); ?>
									</label>
									<p class="description">
										<?php esc_html_e( 'Log debug information for troubleshooting.', 'smart-form-shield' ); ?>
									</p>
								</fieldset>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="log_level">
									<?php esc_html_e( 'Log Level', 'smart-form-shield' ); ?>
								</label>
							</th>
							<td>
								<select id="log_level" name="smart_form_shield_log_level">
									<?php
									$log_level = get_option( 'smart_form_shield_log_level', 'info' );
									$levels = array(
										'debug' => __( 'Debug', 'smart-form-shield' ),
										'info' => __( 'Info', 'smart-form-shield' ),
										'warning' => __( 'Warning', 'smart-form-shield' ),
										'error' => __( 'Error', 'smart-form-shield' ),
										'critical' => __( 'Critical', 'smart-form-shield' ),
									);
									foreach ( $levels as $level => $label ) :
										?>
										<option value="<?php echo esc_attr( $level ); ?>" <?php selected( $log_level, $level ); ?>>
											<?php echo esc_html( $label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
					</table>
				</div>
				<?php
				break;
		}
		
		submit_button();
		?>
	</form>
</div>