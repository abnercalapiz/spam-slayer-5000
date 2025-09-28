<?php
/**
 * Settings admin page display.
 *
 * @since      1.0.0
 * @package    Spam_Slayer_5000
 * @subpackage Spam_Slayer_5000/admin/partials
 */

// Security check
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Include required classes if not already loaded
if ( ! class_exists( 'Spam_Slayer_5000_Provider_Factory' ) ) {
	require_once SPAM_SLAYER_5000_PATH . 'providers/class-provider-factory.php';
}

// Get active tab
$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general';
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	
	<?php
	// Display settings messages
	settings_errors( 'spam_slayer_5000_messages' );
	?>
	
	<nav class="nav-tab-wrapper">
		<a href="?page=spam-slayer-5000-settings&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'General', 'spam-slayer-5000' ); ?>
		</a>
		<a href="?page=spam-slayer-5000-settings&tab=api" class="nav-tab <?php echo $active_tab === 'api' ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'API Settings', 'spam-slayer-5000' ); ?>
		</a>
		<a href="?page=spam-slayer-5000-settings&tab=advanced" class="nav-tab <?php echo $active_tab === 'advanced' ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Advanced', 'spam-slayer-5000' ); ?>
		</a>
	</nav>
	
	<form method="post" action="options.php">
		<?php
		// Add hidden field to preserve tab after save
		if ( $active_tab !== 'general' ) {
			echo '<input type="hidden" name="_wp_http_referer" value="' . esc_attr( admin_url( 'admin.php?page=spam-slayer-5000-settings&tab=' . $active_tab ) ) . '" />';
		}
		
		switch ( $active_tab ) {
			case 'api':
				settings_fields( 'spam_slayer_5000_api' );
				?>
				<div class="ss5k-settings-section">
					<h2><?php esc_html_e( 'API Provider Settings', 'spam-slayer-5000' ); ?></h2>
					<p><?php esc_html_e( 'Configure your AI provider API keys and settings.', 'spam-slayer-5000' ); ?></p>
					
					<?php
					$providers = array(
						'openai' => __( 'OpenAI', 'spam-slayer-5000' ),
						'claude' => __( 'Claude', 'spam-slayer-5000' ),
					);
					
					foreach ( $providers as $provider_key => $provider_name ) :
						$settings = get_option( 'spam_slayer_5000_' . $provider_key . '_settings', array() );
						$is_configured = ! empty( $settings['api_key'] );
						?>
						<div class="ss5k-provider-section">
							<h3>
								<span class="ss5k-provider-status <?php echo $is_configured ? 'active' : 'inactive'; ?>"></span>
								<?php echo esc_html( $provider_name ); ?>
							</h3>
							
							<table class="form-table">
								<tr>
									<th scope="row">
										<label for="<?php echo esc_attr( $provider_key ); ?>_api_key">
											<?php esc_html_e( 'API Key', 'spam-slayer-5000' ); ?>
										</label>
									</th>
									<td>
										<input type="password" 
											id="<?php echo esc_attr( $provider_key ); ?>_api_key" 
											name="spam_slayer_5000_<?php echo esc_attr( $provider_key ); ?>_settings[api_key]" 
											value="<?php echo ! empty( $settings['api_key'] ) ? $settings['api_key'] : ''; ?>" 
											class="regular-text" 
											placeholder="<?php esc_attr_e( 'Enter API key', 'spam-slayer-5000' ); ?>" />
										<p class="description">
											<?php
											if ( $provider_key === 'openai' ) {
												printf(
													__( 'Get your API key from <a href="%s" target="_blank">OpenAI Dashboard</a>', 'spam-slayer-5000' ),
													'https://platform.openai.com/api-keys'
												);
											} elseif ( $provider_key === 'claude' ) {
												printf(
													__( 'Get your API key from <a href="%s" target="_blank">Anthropic Console</a>', 'spam-slayer-5000' ),
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
											<?php esc_html_e( 'Project ID', 'spam-slayer-5000' ); ?>
										</label>
									</th>
									<td>
										<input type="text" 
											id="<?php echo esc_attr( $provider_key ); ?>_project_id" 
											name="spam_slayer_5000_<?php echo esc_attr( $provider_key ); ?>_settings[project_id]" 
											value="<?php echo isset( $settings['project_id'] ) ? esc_attr( $settings['project_id'] ) : ''; ?>" 
											class="regular-text" 
											placeholder="<?php esc_attr_e( 'your-gcp-project-id', 'spam-slayer-5000' ); ?>" />
										<p class="description">
											<?php esc_html_e( 'Your Google Cloud Project ID', 'spam-slayer-5000' ); ?>
										</p>
									</td>
								</tr>
								
								<tr>
									<th scope="row">
										<label for="<?php echo esc_attr( $provider_key ); ?>_region">
											<?php esc_html_e( 'Region', 'spam-slayer-5000' ); ?>
										</label>
									</th>
									<td>
										<select id="<?php echo esc_attr( $provider_key ); ?>_region" 
											name="spam_slayer_5000_<?php echo esc_attr( $provider_key ); ?>_settings[region]">
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
											<?php esc_html_e( 'Model', 'spam-slayer-5000' ); ?>
										</label>
									</th>
									<td>
										<select id="<?php echo esc_attr( $provider_key ); ?>_model" 
											name="spam_slayer_5000_<?php echo esc_attr( $provider_key ); ?>_settings[model]">
											<?php
											// Dynamically load models from provider class
											$models = array();
											$provider = Spam_Slayer_5000_Provider_Factory::create( $provider_key );
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
											<?php esc_html_e( 'Enable Provider', 'spam-slayer-5000' ); ?>
										</label>
									</th>
									<td>
										<input type="checkbox" 
											id="<?php echo esc_attr( $provider_key ); ?>_enabled" 
											name="spam_slayer_5000_<?php echo esc_attr( $provider_key ); ?>_settings[enabled]" 
											value="1" 
											<?php checked( ! empty( $settings['enabled'] ) ); ?> />
										<label for="<?php echo esc_attr( $provider_key ); ?>_enabled">
											<?php esc_html_e( 'Enable this AI provider', 'spam-slayer-5000' ); ?>
										</label>
									</td>
								</tr>
								
								<tr>
									<th scope="row"></th>
									<td>
										<button type="button" class="button ss5k-test-provider" data-provider="<?php echo esc_attr( $provider_key ); ?>">
											<?php esc_html_e( 'Test Connection', 'spam-slayer-5000' ); ?>
										</button>
										<span class="ss5k-test-status"></span>
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
				settings_fields( 'spam_slayer_5000_advanced' );
				?>
				<div class="ss5k-settings-section">
					<h2><?php esc_html_e( 'Advanced Settings', 'spam-slayer-5000' ); ?></h2>
					
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="retention_days">
									<?php esc_html_e( 'Data Retention', 'spam-slayer-5000' ); ?>
								</label>
							</th>
							<td>
								<input type="number" id="retention_days" name="spam_slayer_5000_retention_days" 
									value="<?php echo esc_attr( get_option( 'spam_slayer_5000_retention_days', 30 ) ); ?>" 
									min="1" max="365" />
								<span><?php esc_html_e( 'days', 'spam-slayer-5000' ); ?></span>
								<p class="description">
									<?php esc_html_e( 'Number of days to retain submission data and logs.', 'spam-slayer-5000' ); ?>
								</p>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="daily_budget_limit">
									<?php esc_html_e( 'Daily Budget Limit', 'spam-slayer-5000' ); ?>
								</label>
							</th>
							<td>
								<span>$</span>
								<input type="number" id="daily_budget_limit" name="spam_slayer_5000_daily_budget_limit" 
									value="<?php echo esc_attr( get_option( 'spam_slayer_5000_daily_budget_limit', 10.00 ) ); ?>" 
									min="0" step="0.01" style="width: 100px;" />
								<p class="description">
									<?php esc_html_e( 'Maximum daily spend on AI API calls. Set to 0 to disable limit.', 'spam-slayer-5000' ); ?>
								</p>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="notification_email">
									<?php esc_html_e( 'Notification Email', 'spam-slayer-5000' ); ?>
								</label>
							</th>
							<td>
								<input type="email" id="notification_email" name="spam_slayer_5000_notification_email" 
									value="<?php echo esc_attr( get_option( 'spam_slayer_5000_notification_email', get_option( 'admin_email' ) ) ); ?>" 
									class="regular-text" />
								<p class="description">
									<?php esc_html_e( 'Email address for high spam score notifications.', 'spam-slayer-5000' ); ?>
								</p>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="notification_threshold">
									<?php esc_html_e( 'Notification Threshold', 'spam-slayer-5000' ); ?>
								</label>
							</th>
							<td>
								<input type="number" id="notification_threshold" name="spam_slayer_5000_notification_threshold" 
									value="<?php echo esc_attr( get_option( 'spam_slayer_5000_notification_threshold', 90 ) ); ?>" 
									min="0" max="100" />
								<span>%</span>
								<p class="description">
									<?php esc_html_e( 'Send notification when spam score exceeds this threshold.', 'spam-slayer-5000' ); ?>
								</p>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<?php esc_html_e( 'Caching', 'spam-slayer-5000' ); ?>
							</th>
							<td>
								<label>
									<input type="checkbox" name="spam_slayer_5000_cache_responses" value="1" 
										<?php checked( get_option( 'spam_slayer_5000_cache_responses', true ) ); ?> />
									<?php esc_html_e( 'Enable response caching', 'spam-slayer-5000' ); ?>
								</label>
								<p class="description">
									<?php esc_html_e( 'Cache AI responses to reduce API costs for duplicate submissions.', 'spam-slayer-5000' ); ?>
								</p>
								<br>
								<label>
									<?php esc_html_e( 'Cache Duration:', 'spam-slayer-5000' ); ?>
									<input type="number" name="spam_slayer_5000_cache_duration" 
										value="<?php echo esc_attr( get_option( 'spam_slayer_5000_cache_duration', 3600 ) ); ?>" 
										min="60" max="86400" style="width: 100px;" />
									<?php esc_html_e( 'seconds', 'spam-slayer-5000' ); ?>
								</label>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<?php esc_html_e( 'Reports', 'spam-slayer-5000' ); ?>
							</th>
							<td>
								<label>
									<input type="checkbox" name="spam_slayer_5000_daily_report" value="1" 
										<?php checked( get_option( 'spam_slayer_5000_daily_report', false ) ); ?> />
									<?php esc_html_e( 'Send daily analytics report', 'spam-slayer-5000' ); ?>
								</label>
								<p class="description">
									<?php esc_html_e( 'Receive a daily email with spam filtering statistics.', 'spam-slayer-5000' ); ?>
								</p>
							</td>
						</tr>
					</table>
				</div>
				<?php
				break;
				
			case 'general':
			default:
				settings_fields( 'spam_slayer_5000_general' );
				?>
				<div class="ss5k-settings-section">
					<h2><?php esc_html_e( 'General Settings', 'spam-slayer-5000' ); ?></h2>
					
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="spam_threshold">
									<?php esc_html_e( 'Spam Threshold', 'spam-slayer-5000' ); ?>
								</label>
							</th>
							<td>
								<input type="number" id="spam_threshold" name="spam_slayer_5000_spam_threshold" 
									value="<?php echo esc_attr( get_option( 'spam_slayer_5000_spam_threshold', 75 ) ); ?>" 
									min="0" max="100" />
								<span>%</span>
								<p class="description">
									<?php esc_html_e( 'Submissions with spam score above this threshold will be blocked.', 'spam-slayer-5000' ); ?>
								</p>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="primary_provider">
									<?php esc_html_e( 'Primary AI Provider', 'spam-slayer-5000' ); ?>
								</label>
							</th>
							<td>
								<select id="primary_provider" name="spam_slayer_5000_primary_provider">
									<?php
									$primary = get_option( 'spam_slayer_5000_primary_provider', 'openai' );
									?>
									<option value="openai" <?php selected( $primary, 'openai' ); ?>><?php esc_html_e( 'OpenAI', 'spam-slayer-5000' ); ?></option>
									<option value="claude" <?php selected( $primary, 'claude' ); ?>><?php esc_html_e( 'Claude', 'spam-slayer-5000' ); ?></option>
								</select>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="fallback_provider">
									<?php esc_html_e( 'Fallback AI Provider', 'spam-slayer-5000' ); ?>
								</label>
							</th>
							<td>
								<select id="fallback_provider" name="spam_slayer_5000_fallback_provider">
									<?php
									$fallback = get_option( 'spam_slayer_5000_fallback_provider', 'claude' );
									?>
									<option value="openai" <?php selected( $fallback, 'openai' ); ?>><?php esc_html_e( 'OpenAI', 'spam-slayer-5000' ); ?></option>
									<option value="claude" <?php selected( $fallback, 'claude' ); ?>><?php esc_html_e( 'Claude', 'spam-slayer-5000' ); ?></option>
								</select>
								<p class="description">
									<?php esc_html_e( 'Provider to use if primary provider fails.', 'spam-slayer-5000' ); ?>
								</p>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<?php esc_html_e( 'Form Integrations', 'spam-slayer-5000' ); ?>
							</th>
							<td>
								<fieldset>
									<label>
										<input type="checkbox" name="spam_slayer_5000_enable_gravity_forms" value="1" 
											<?php checked( get_option( 'spam_slayer_5000_enable_gravity_forms', true ) ); ?>
											<?php echo class_exists( 'GFForms' ) ? '' : 'disabled'; ?> />
										<?php esc_html_e( 'Enable Gravity Forms Protection', 'spam-slayer-5000' ); ?>
										<?php if ( ! class_exists( 'GFForms' ) ) : ?>
											<span class="description"><?php esc_html_e( '(Gravity Forms not installed)', 'spam-slayer-5000' ); ?></span>
										<?php endif; ?>
									</label>
									<br>
									<label>
										<input type="checkbox" name="spam_slayer_5000_enable_elementor_forms" value="1" 
											<?php checked( get_option( 'spam_slayer_5000_enable_elementor_forms', true ) ); ?>
											<?php echo did_action( 'elementor/loaded' ) ? '' : 'disabled'; ?> />
										<?php esc_html_e( 'Enable Elementor Forms Protection', 'spam-slayer-5000' ); ?>
										<?php if ( ! did_action( 'elementor/loaded' ) ) : ?>
											<span class="description"><?php esc_html_e( '(Elementor Pro not installed)', 'spam-slayer-5000' ); ?></span>
										<?php endif; ?>
									</label>
								</fieldset>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<?php esc_html_e( 'Features', 'spam-slayer-5000' ); ?>
							</th>
							<td>
								<fieldset>
									<label>
										<input type="checkbox" name="spam_slayer_5000_enable_whitelist" value="1" 
											<?php checked( get_option( 'spam_slayer_5000_enable_whitelist', true ) ); ?> />
										<?php esc_html_e( 'Enable Email Whitelist', 'spam-slayer-5000' ); ?>
									</label>
									<p class="description">
										<?php esc_html_e( 'Allow whitelisting trusted email addresses.', 'spam-slayer-5000' ); ?>
									</p>
									<br>
									<label>
										<input type="checkbox" name="spam_slayer_5000_enable_logging" value="1" 
											<?php checked( get_option( 'spam_slayer_5000_enable_logging', true ) ); ?> />
										<?php esc_html_e( 'Enable Debug Logging', 'spam-slayer-5000' ); ?>
									</label>
									<p class="description">
										<?php esc_html_e( 'Log debug information for troubleshooting.', 'spam-slayer-5000' ); ?>
									</p>
								</fieldset>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="log_level">
									<?php esc_html_e( 'Log Level', 'spam-slayer-5000' ); ?>
								</label>
							</th>
							<td>
								<select id="log_level" name="spam_slayer_5000_log_level">
									<?php
									$log_level = get_option( 'spam_slayer_5000_log_level', 'info' );
									$levels = array(
										'debug' => __( 'Debug', 'spam-slayer-5000' ),
										'info' => __( 'Info', 'spam-slayer-5000' ),
										'warning' => __( 'Warning', 'spam-slayer-5000' ),
										'error' => __( 'Error', 'spam-slayer-5000' ),
										'critical' => __( 'Critical', 'spam-slayer-5000' ),
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