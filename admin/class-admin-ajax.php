<?php
/**
 * Admin AJAX handlers.
 *
 * @since      1.0.0
 * @package    Spam_Slayer_5000
 * @subpackage Spam_Slayer_5000/admin
 */

class Spam_Slayer_5000_Admin_Ajax {

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
	 * Get submissions via AJAX.
	 *
	 * @since    1.0.0
	 */
	public function get_submissions() {
		check_ajax_referer( 'ss5k_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		$args = array(
			'status' => sanitize_text_field( $_POST['status'] ?? '' ),
			'form_type' => sanitize_text_field( $_POST['form_type'] ?? '' ),
			'search' => sanitize_text_field( $_POST['search'] ?? '' ),
			'orderby' => sanitize_text_field( $_POST['orderby'] ?? 'created_at' ),
			'order' => sanitize_text_field( $_POST['order'] ?? 'DESC' ),
			'limit' => absint( $_POST['per_page'] ?? 20 ),
			'offset' => absint( $_POST['offset'] ?? 0 ),
		);

		$database = new Spam_Slayer_5000_Database();
		$submissions = $database->get_submissions( $args );
		$total = $database->get_submissions_count( $args );

		wp_send_json_success( array(
			'submissions' => $submissions,
			'total' => $total,
		) );
	}

	/**
	 * Update submission status.
	 *
	 * @since    1.0.0
	 */
	public function update_submission_status() {
		check_ajax_referer( 'ss5k_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		$submission_id = absint( $_POST['submission_id'] ?? 0 );
		$status = sanitize_text_field( $_POST['status'] ?? '' );

		if ( ! $submission_id ) {
			wp_send_json_error( __( 'Invalid submission ID', 'spam-slayer-5000' ) );
		}

		if ( $status === 'delete' ) {
			global $wpdb;
			$result = $wpdb->delete(
				SPAM_SLAYER_5000_SUBMISSIONS_TABLE,
				array( 'id' => $submission_id ),
				array( '%d' )
			);
		} else {
			$database = new Spam_Slayer_5000_Database();
			$result = $database->update_submission_status( $submission_id, $status );
		}

		if ( $result ) {
			wp_send_json_success();
		} else {
			wp_send_json_error( __( 'Failed to update submission', 'spam-slayer-5000' ) );
		}
	}

	/**
	 * Handle bulk actions.
	 *
	 * @since    1.0.0
	 */
	public function handle_bulk_action() {
		check_ajax_referer( 'ss5k_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		$action = sanitize_text_field( $_POST['bulk_action'] ?? '' );
		$ids = array_map( 'absint', $_POST['ids'] ?? array() );

		if ( empty( $ids ) ) {
			wp_send_json_error( __( 'No items selected', 'spam-slayer-5000' ) );
		}

		$database = new Spam_Slayer_5000_Database();
		$success_count = 0;

		foreach ( $ids as $id ) {
			if ( $action === 'delete' ) {
				global $wpdb;
				$result = $wpdb->delete(
					SPAM_SLAYER_5000_SUBMISSIONS_TABLE,
					array( 'id' => $id ),
					array( '%d' )
				);
			} else {
				$result = $database->update_submission_status( $id, $action );
			}
			
			if ( $result ) {
				$success_count++;
			}
		}

		wp_send_json_success( array(
			'updated' => $success_count,
			'total' => count( $ids ),
		) );
	}

	/**
	 * Test AI provider.
	 *
	 * @since    1.0.0
	 */
	public function test_provider() {
		check_ajax_referer( 'ss5k_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		$provider_name = sanitize_text_field( $_POST['provider'] ?? '' );
		$api_key = sanitize_text_field( $_POST['api_key'] ?? '' );
		$model = sanitize_text_field( $_POST['model'] ?? '' );
		$project_id = sanitize_text_field( $_POST['project_id'] ?? '' );
		$region = sanitize_text_field( $_POST['region'] ?? '' );
		
		if ( empty( $api_key ) ) {
			wp_send_json_error( array(
				'message' => __( 'API key is required', 'spam-slayer-5000' )
			) );
		}
		
		// Create provider instance
		$provider = Spam_Slayer_5000_Provider_Factory::create( $provider_name );
		
		if ( ! $provider ) {
			wp_send_json_error( array(
				'message' => __( 'Invalid provider', 'spam-slayer-5000' )
			) );
		}
		
		// For testing, we'll temporarily set the API key and model directly
		// This avoids any caching issues with WordPress options
		$reflection = new ReflectionClass( $provider );
		
		// Set API key
		$api_key_property = $reflection->getProperty( 'api_key' );
		$api_key_property->setAccessible( true );
		$api_key_property->setValue( $provider, $api_key );
		
		// Set model
		$model_property = $reflection->getProperty( 'model' );
		$model_property->setAccessible( true );
		$model_property->setValue( $provider, $model );
		
		// For Gemini/Vertex AI, set project_id and region
		if ( $provider_name === 'gemini' ) {
			if ( ! empty( $project_id ) ) {
				$project_property = $reflection->getProperty( 'project_id' );
				$project_property->setAccessible( true );
				$project_property->setValue( $provider, $project_id );
			}
			
			if ( ! empty( $region ) ) {
				$region_property = $reflection->getProperty( 'region' );
				$region_property->setAccessible( true );
				$region_property->setValue( $provider, $region );
			}
		}
		
		// Debug logging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Spam Slayer 5000 test_provider - Provider created: ' . ( $provider ? 'yes' : 'no' ) );
			if ( $provider ) {
				error_log( 'Spam Slayer 5000 test_provider - Provider class: ' . get_class( $provider ) );
			}
		}
		
		try {
			// Force type safety - if test_connection returns false, convert to error array
			$raw_result = $provider->test_connection();
			
			// Convert false to error array for consistency
			if ( $raw_result === false ) {
				$result = array( 'error' => 'Connection test failed (returned false)' );
			} else {
				$result = $raw_result;
			}
			
			// Debug logging
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Spam Slayer 5000 test_connection result for ' . $provider_name . ': ' . print_r( $result, true ) );
			}
			
			if ( $result === true ) {
				wp_send_json_success( array(
					'message' => __( 'Connection successful!', 'spam-slayer-5000' )
				) );
			} else if ( is_array( $result ) && isset( $result['error'] ) ) {
				wp_send_json_error( array(
					'message' => sprintf( __( 'Connection failed: %s', 'spam-slayer-5000' ), $result['error'] )
				) );
			} else {
				// Log unexpected result type
				$debug_message = 'Result type: ' . gettype( $result );
				if ( $result === false ) {
					$debug_message .= ' (false)';
				} elseif ( $result === null ) {
					$debug_message .= ' (null)';
				} elseif ( is_array( $result ) ) {
					$debug_message .= ' - Array: ' . json_encode( $result );
				}
				
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'Spam Slayer 5000 unexpected test_connection result: ' . $debug_message );
				}
				
				// For now, show the debug info directly in the error message
				wp_send_json_error( array(
					'message' => 'Connection test returned: ' . $debug_message . '. This usually means the provider returned false instead of an error array.'
				) );
			}
		} catch ( Exception $e ) {
			wp_send_json_error( array(
				'message' => sprintf( __( 'Connection error: %s', 'spam-slayer-5000' ), $e->getMessage() )
			) );
		}
	}

	/**
	 * Get submission details.
	 *
	 * @since    1.0.0
	 */
	public function get_submission_details() {
		check_ajax_referer( 'ss5k_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		$id = absint( $_POST['id'] ?? 0 );
		
		if ( ! $id ) {
			wp_send_json_error( __( 'Invalid submission ID', 'spam-slayer-5000' ) );
		}

		$database = new Spam_Slayer_5000_Database();
		$submissions = $database->get_submissions( array( 'id' => $id, 'limit' => 1 ) );
		
		if ( empty( $submissions ) ) {
			wp_send_json_error( __( 'Submission not found', 'spam-slayer-5000' ) );
		}
		
		$submission = $submissions[0];
		
		// Build HTML output
		ob_start();
		?>
		<div class="sfs-submission-details">
			<table class="widefat">
				<tr>
					<th><?php esc_html_e( 'Date', 'spam-slayer-5000' ); ?></th>
					<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $submission['created_at'] ) ) ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Form Type', 'spam-slayer-5000' ); ?></th>
					<td><?php echo esc_html( ucwords( str_replace( '_', ' ', $submission['form_type'] ) ) ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Form ID', 'spam-slayer-5000' ); ?></th>
					<td><?php echo esc_html( $submission['form_id'] ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Status', 'spam-slayer-5000' ); ?></th>
					<td><span class="sfs-status <?php echo esc_attr( $submission['status'] ); ?>"><?php echo esc_html( ucfirst( $submission['status'] ) ); ?></span></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Spam Score', 'spam-slayer-5000' ); ?></th>
					<td><?php echo number_format( $submission['spam_score'], 1 ); ?>%</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Provider Used', 'spam-slayer-5000' ); ?></th>
					<td><?php echo esc_html( $submission['provider_used'] ?? '-' ); ?></td>
				</tr>
				<?php if ( ! empty( $submission['ip_address'] ) ) : ?>
				<tr>
					<th><?php esc_html_e( 'IP Address', 'spam-slayer-5000' ); ?></th>
					<td><?php echo esc_html( $submission['ip_address'] ); ?></td>
				</tr>
				<?php endif; ?>
			</table>
			
			<h3><?php esc_html_e( 'Submission Data', 'spam-slayer-5000' ); ?></h3>
			<table class="widefat">
				<?php
				$data = is_array( $submission['submission_data'] ) ? $submission['submission_data'] : array();
				foreach ( $data as $key => $value ) :
					?>
					<tr>
						<th><?php echo esc_html( $key ); ?></th>
						<td><?php echo is_array( $value ) ? esc_html( wp_json_encode( $value ) ) : esc_html( $value ); ?></td>
					</tr>
				<?php endforeach; ?>
			</table>
			
			<?php if ( ! empty( $submission['provider_response'] ) ) : ?>
			<h3><?php esc_html_e( 'AI Provider Response', 'spam-slayer-5000' ); ?></h3>
			<pre style="background: #f0f0f0; padding: 10px; overflow-x: auto;"><?php 
				$response = is_array( $submission['provider_response'] ) ? $submission['provider_response'] : array();
				echo esc_html( wp_json_encode( $response, JSON_PRETTY_PRINT ) ); 
			?></pre>
			<?php endif; ?>
		</div>
		<?php
		$html = ob_get_clean();
		
		wp_send_json_success( $html );
	}

	/**
	 * Export data.
	 *
	 * @since    1.0.0
	 */
	public function export_data() {
		check_ajax_referer( 'ss5k_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		$export_type = sanitize_text_field( $_GET['export_type'] ?? 'submissions' );
		$format = sanitize_text_field( $_GET['format'] ?? 'csv' );
		$date_from = sanitize_text_field( $_GET['date_from'] ?? '' );
		$date_to = sanitize_text_field( $_GET['date_to'] ?? '' );

		switch ( $export_type ) {
			case 'submissions':
				$this->export_submissions( $format, $date_from, $date_to );
				break;
			
			case 'analytics':
				$analytics = new Spam_Slayer_5000_Admin_Analytics( $this->plugin_name, $this->version );
				$analytics->export_analytics( $format, $date_from, $date_to );
				break;
			
			case 'logs':
				$this->export_logs( $format );
				break;
		}
	}

	/**
	 * Get analytics data.
	 *
	 * @since    1.0.0
	 */
	public function get_analytics() {
		check_ajax_referer( 'ss5k_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		$range = sanitize_text_field( $_POST['range'] ?? 'week' );
		$start_date = sanitize_text_field( $_POST['start_date'] ?? '' );
		$end_date = sanitize_text_field( $_POST['end_date'] ?? '' );

		$analytics = new Spam_Slayer_5000_Admin_Analytics( $this->plugin_name, $this->version );
		
		if ( $range === 'custom' && $start_date && $end_date ) {
			// Custom date range handling
			$data = $analytics->get_analytics_data( $range );
		} else {
			$data = $analytics->get_analytics_data( $range );
		}

		wp_send_json_success( $data );
	}

	/**
	 * Add email to whitelist.
	 *
	 * @since    1.0.0
	 */
	public function add_to_whitelist() {
		check_ajax_referer( 'ss5k_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		$email = sanitize_email( $_POST['email'] ?? '' );
		$reason = sanitize_textarea_field( $_POST['reason'] ?? '' );

		if ( ! is_email( $email ) ) {
			wp_send_json_error( __( 'Invalid email address', 'spam-slayer-5000' ) );
		}

		$database = new Spam_Slayer_5000_Database();
		$result = $database->add_to_whitelist( $email, $reason );

		if ( $result ) {
			wp_send_json_success( array( 'id' => $result ) );
		} else {
			wp_send_json_error( __( 'Failed to add to whitelist', 'spam-slayer-5000' ) );
		}
	}

	/**
	 * Remove from whitelist.
	 *
	 * @since    1.0.0
	 */
	public function remove_from_whitelist() {
		check_ajax_referer( 'ss5k_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		$id = absint( $_POST['id'] ?? 0 );

		if ( ! $id ) {
			wp_send_json_error( __( 'Invalid ID', 'spam-slayer-5000' ) );
		}

		$database = new Spam_Slayer_5000_Database();
		$result = $database->remove_from_whitelist( $id );

		if ( $result ) {
			wp_send_json_success();
		} else {
			wp_send_json_error( __( 'Failed to remove from whitelist', 'spam-slayer-5000' ) );
		}
	}

	/**
	 * Remove from blocklist.
	 *
	 * @since    1.1.0
	 */
	public function remove_from_blocklist() {
		check_ajax_referer( 'ss5k_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}
		
		$id = absint( $_POST['id'] ?? 0 );
		
		if ( ! $id ) {
			wp_send_json_error( __( 'Invalid ID', 'spam-slayer-5000' ) );
		}
		
		$database = new Spam_Slayer_5000_Database();
		$result = $database->remove_from_blocklist( $id );
		
		if ( $result ) {
			wp_send_json_success();
		} else {
			wp_send_json_error( __( 'Failed to remove from blocklist', 'spam-slayer-5000' ) );
		}
	}

	/**
	 * Clear logs.
	 *
	 * @since    1.0.0
	 */
	public function clear_logs() {
		check_ajax_referer( 'ss5k_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_network' ) ) {
			wp_die( -1 );
		}

		$logger = new Spam_Slayer_5000_Logger();
		$result = $logger->clear_log();

		if ( $result ) {
			wp_send_json_success();
		} else {
			wp_send_json_error( __( 'Failed to clear logs', 'spam-slayer-5000' ) );
		}
	}

	/**
	 * Export submissions.
	 *
	 * @since    1.0.0
	 * @param    string    $format       Export format.
	 * @param    string    $date_from    Start date.
	 * @param    string    $date_to      End date.
	 */
	private function export_submissions( $format, $date_from, $date_to ) {
		$database = new Spam_Slayer_5000_Database();
		
		$args = array(
			'limit' => 9999,
		);
		
		if ( $date_from ) {
			$args['date_from'] = $date_from . ' 00:00:00';
		}
		
		if ( $date_to ) {
			$args['date_to'] = $date_to . ' 23:59:59';
		}
		
		$submissions = $database->get_submissions( $args );
		
		if ( $format === 'json' ) {
			$this->export_json( $submissions, 'submissions' );
		} else {
			$this->export_csv( $submissions, 'submissions' );
		}
	}

	/**
	 * Export logs.
	 *
	 * @since    1.0.0
	 * @param    string    $format    Export format.
	 */
	private function export_logs( $format ) {
		$logger = new Spam_Slayer_5000_Logger();
		$logs = $logger->get_log( 10000 );
		
		$filename = 'spam-slayer-5000-logs-' . date( 'Y-m-d' ) . '.txt';
		
		header( 'Content-Type: text/plain' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		
		echo $logs;
		exit;
	}

	/**
	 * Export as CSV.
	 *
	 * @since    1.0.0
	 * @param    array     $data    Data to export.
	 * @param    string    $type    Export type.
	 */
	private function export_csv( $data, $type ) {
		$filename = 'spam-slayer-5000-' . $type . '-' . date( 'Y-m-d' ) . '.csv';
		
		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		
		$output = fopen( 'php://output', 'w' );
		
		if ( ! empty( $data ) ) {
			// Headers
			$headers = array_keys( $data[0] );
			fputcsv( $output, $headers );
			
			// Data
			foreach ( $data as $row ) {
				// Handle nested data
				foreach ( $row as $key => $value ) {
					if ( is_array( $value ) || is_object( $value ) ) {
						$row[ $key ] = wp_json_encode( $value );
					}
				}
				fputcsv( $output, $row );
			}
		}
		
		fclose( $output );
		exit;
	}

	/**
	 * Export as JSON.
	 *
	 * @since    1.0.0
	 * @param    array     $data    Data to export.
	 * @param    string    $type    Export type.
	 */
	private function export_json( $data, $type ) {
		$filename = 'spam-slayer-5000-' . $type . '-' . date( 'Y-m-d' ) . '.json';
		
		header( 'Content-Type: application/json' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		
		echo wp_json_encode( $data, JSON_PRETTY_PRINT );
		exit;
	}

	/**
	 * Clear the spam detection cache.
	 *
	 * @since    1.1.5
	 */
	public function clear_cache() {
		check_ajax_referer( 'ss5k_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		$cache = new Spam_Slayer_5000_Cache();
		$cache->flush();

		// Get cache stats after clearing
		$stats = $cache->get_stats();

		wp_send_json_success( array(
			'message' => __( 'Cache cleared successfully.', 'spam-slayer-5000' ),
			'stats' => $stats
		) );
	}
}