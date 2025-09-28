<?php
/**
 * Analytics functionality.
 *
 * @since      1.0.0
 * @package    Spam_Slayer_5000
 * @subpackage Spam_Slayer_5000/admin
 */

class Spam_Slayer_5000_Admin_Analytics {

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
	 * Get analytics data.
	 *
	 * @since    1.0.0
	 * @param    string    $period    Time period (day, week, month).
	 * @return   array                Analytics data.
	 */
	public function get_analytics_data( $period = 'week' ) {
		$database = new Spam_Slayer_5000_Database();
		
		// Set date range
		$date_from = $this->get_date_from( $period );
		$date_to = current_time( 'Y-m-d 23:59:59' );
		
		// Get submission stats
		$total_submissions = $database->get_submissions_count( array(
			'date_from' => $date_from,
			'date_to' => $date_to,
		) );
		
		$spam_submissions = $database->get_submissions_count( array(
			'status' => 'spam',
			'date_from' => $date_from,
			'date_to' => $date_to,
		) );
		
		$approved_submissions = $database->get_submissions_count( array(
			'status' => 'approved',
			'date_from' => $date_from,
			'date_to' => $date_to,
		) );
		
		$whitelist_submissions = $database->get_submissions_count( array(
			'status' => 'whitelist',
			'date_from' => $date_from,
			'date_to' => $date_to,
		) );
		
		// Get API usage stats
		$api_stats = $database->get_api_usage_stats( $period );
		
		// Calculate totals
		$total_api_calls = 0;
		$total_cost = 0;
		$total_tokens = 0;
		$avg_response_time = 0;
		$provider_breakdown = array();
		
		foreach ( $api_stats as $stat ) {
			$total_api_calls += $stat['total_calls'];
			$total_cost += $stat['total_cost'];
			$total_tokens += $stat['total_tokens'];
			
			$provider_breakdown[ $stat['provider'] ] = array(
				'calls' => $stat['total_calls'],
				'cost' => $stat['total_cost'],
				'tokens' => $stat['total_tokens'],
				'success_rate' => $stat['total_calls'] > 0 ? ( $stat['success_count'] / $stat['total_calls'] ) * 100 : 0,
				'avg_response_time' => $stat['avg_response_time'],
			);
			
			$avg_response_time += $stat['avg_response_time'] * $stat['total_calls'];
		}
		
		if ( $total_api_calls > 0 ) {
			$avg_response_time = $avg_response_time / $total_api_calls;
		}
		
		// Get daily breakdown
		$daily_breakdown = $this->get_daily_breakdown( $date_from, $date_to );
		
		// Get form type breakdown
		$form_breakdown = $this->get_form_breakdown( $date_from, $date_to );
		
		// Get spam score distribution
		$spam_distribution = $this->get_spam_score_distribution( $date_from, $date_to );
		
		return array(
			'summary' => array(
				'total_submissions' => $total_submissions,
				'spam_submissions' => $spam_submissions,
				'approved_submissions' => $approved_submissions,
				'whitelist_submissions' => $whitelist_submissions,
				'spam_rate' => $total_submissions > 0 ? ( $spam_submissions / $total_submissions ) * 100 : 0,
				'total_api_calls' => $total_api_calls,
				'total_cost' => $total_cost,
				'total_tokens' => $total_tokens,
				'avg_response_time' => $avg_response_time,
			),
			'providers' => $provider_breakdown,
			'daily_breakdown' => $daily_breakdown,
			'form_breakdown' => $form_breakdown,
			'spam_distribution' => $spam_distribution,
			'period' => $period,
			'date_from' => $date_from,
			'date_to' => $date_to,
		);
	}

	/**
	 * Generate daily report.
	 *
	 * @since    1.0.0
	 * @return   string    HTML report.
	 */
	public function generate_daily_report() {
		$data = $this->get_analytics_data( 'day' );
		
		ob_start();
		?>
		<h2><?php esc_html_e( 'Spam Slayer 5000 Daily Report', 'spam-slayer-5000' ); ?></h2>
		<p><?php echo esc_html( date_i18n( get_option( 'date_format' ) ) ); ?></p>
		
		<h3><?php esc_html_e( 'Summary', 'spam-slayer-5000' ); ?></h3>
		<ul>
			<li><?php printf( __( 'Total Submissions: %d', 'spam-slayer-5000' ), $data['summary']['total_submissions'] ); ?></li>
			<li><?php printf( __( 'Spam Blocked: %d (%.1f%%)', 'spam-slayer-5000' ), $data['summary']['spam_submissions'], $data['summary']['spam_rate'] ); ?></li>
			<li><?php printf( __( 'Approved: %d', 'spam-slayer-5000' ), $data['summary']['approved_submissions'] ); ?></li>
			<li><?php printf( __( 'API Calls: %d', 'spam-slayer-5000' ), $data['summary']['total_api_calls'] ); ?></li>
			<li><?php printf( __( 'Total Cost: $%.4f', 'spam-slayer-5000' ), $data['summary']['total_cost'] ); ?></li>
		</ul>
		
		<?php if ( ! empty( $data['providers'] ) ) : ?>
			<h3><?php esc_html_e( 'Provider Performance', 'spam-slayer-5000' ); ?></h3>
			<table style="border-collapse: collapse; width: 100%;">
				<tr>
					<th style="border: 1px solid #ddd; padding: 8px;"><?php esc_html_e( 'Provider', 'spam-slayer-5000' ); ?></th>
					<th style="border: 1px solid #ddd; padding: 8px;"><?php esc_html_e( 'Calls', 'spam-slayer-5000' ); ?></th>
					<th style="border: 1px solid #ddd; padding: 8px;"><?php esc_html_e( 'Cost', 'spam-slayer-5000' ); ?></th>
					<th style="border: 1px solid #ddd; padding: 8px;"><?php esc_html_e( 'Success Rate', 'spam-slayer-5000' ); ?></th>
				</tr>
				<?php foreach ( $data['providers'] as $provider => $stats ) : ?>
					<tr>
						<td style="border: 1px solid #ddd; padding: 8px;"><?php echo esc_html( ucfirst( $provider ) ); ?></td>
						<td style="border: 1px solid #ddd; padding: 8px;"><?php echo esc_html( $stats['calls'] ); ?></td>
						<td style="border: 1px solid #ddd; padding: 8px;">$<?php echo number_format( $stats['cost'], 4 ); ?></td>
						<td style="border: 1px solid #ddd; padding: 8px;"><?php echo number_format( $stats['success_rate'], 1 ); ?>%</td>
					</tr>
				<?php endforeach; ?>
			</table>
		<?php endif; ?>
		
		<p style="margin-top: 20px;">
			<a href="<?php echo admin_url( 'admin.php?page=spam-slayer-5000-analytics' ); ?>">
				<?php esc_html_e( 'View Full Analytics', 'spam-slayer-5000' ); ?>
			</a>
		</p>
		<?php
		
		return ob_get_clean();
	}

	/**
	 * Get date from based on period.
	 *
	 * @since    1.0.0
	 * @param    string    $period    Time period.
	 * @return   string               Date string.
	 */
	private function get_date_from( $period ) {
		switch ( $period ) {
			case 'day':
				return current_time( 'Y-m-d 00:00:00' );
			
			case 'week':
				return date( 'Y-m-d 00:00:00', strtotime( '-7 days', current_time( 'timestamp' ) ) );
			
			case 'month':
			default:
				return date( 'Y-m-d 00:00:00', strtotime( '-30 days', current_time( 'timestamp' ) ) );
		}
	}

	/**
	 * Get daily breakdown.
	 *
	 * @since    1.0.0
	 * @param    string    $date_from    Start date.
	 * @param    string    $date_to      End date.
	 * @return   array                   Daily breakdown.
	 */
	private function get_daily_breakdown( $date_from, $date_to ) {
		global $wpdb;
		
		$sql = $wpdb->prepare(
			"SELECT 
				DATE(created_at) as date,
				COUNT(*) as total,
				SUM(CASE WHEN status = 'spam' THEN 1 ELSE 0 END) as spam,
				SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved
			FROM " . SPAM_SLAYER_5000_SUBMISSIONS_TABLE . "
			WHERE created_at BETWEEN %s AND %s
			GROUP BY DATE(created_at)
			ORDER BY date ASC",
			$date_from,
			$date_to
		);
		
		$results = $wpdb->get_results( $sql, ARRAY_A );
		
		// Fill in missing days
		$breakdown = array();
		$current = strtotime( $date_from );
		$end = strtotime( $date_to );
		
		while ( $current <= $end ) {
			$date = date( 'Y-m-d', $current );
			$breakdown[ $date ] = array(
				'total' => 0,
				'spam' => 0,
				'approved' => 0,
			);
			$current = strtotime( '+1 day', $current );
		}
		
		foreach ( $results as $row ) {
			$breakdown[ $row['date'] ] = array(
				'total' => intval( $row['total'] ),
				'spam' => intval( $row['spam'] ),
				'approved' => intval( $row['approved'] ),
			);
		}
		
		return $breakdown;
	}

	/**
	 * Get form type breakdown.
	 *
	 * @since    1.0.0
	 * @param    string    $date_from    Start date.
	 * @param    string    $date_to      End date.
	 * @return   array                   Form breakdown.
	 */
	private function get_form_breakdown( $date_from, $date_to ) {
		global $wpdb;
		
		$sql = $wpdb->prepare(
			"SELECT 
				form_type,
				form_id,
				COUNT(*) as total,
				SUM(CASE WHEN status = 'spam' THEN 1 ELSE 0 END) as spam
			FROM " . SPAM_SLAYER_5000_SUBMISSIONS_TABLE . "
			WHERE created_at BETWEEN %s AND %s
			GROUP BY form_type, form_id
			ORDER BY total DESC",
			$date_from,
			$date_to
		);
		
		return $wpdb->get_results( $sql, ARRAY_A );
	}

	/**
	 * Get spam score distribution.
	 *
	 * @since    1.0.0
	 * @param    string    $date_from    Start date.
	 * @param    string    $date_to      End date.
	 * @return   array                   Distribution data.
	 */
	private function get_spam_score_distribution( $date_from, $date_to ) {
		global $wpdb;
		
		$sql = $wpdb->prepare(
			"SELECT 
				CASE 
					WHEN spam_score < 20 THEN '0-20'
					WHEN spam_score < 40 THEN '20-40'
					WHEN spam_score < 60 THEN '40-60'
					WHEN spam_score < 80 THEN '60-80'
					ELSE '80-100'
				END as score_range,
				COUNT(*) as count
			FROM " . SPAM_SLAYER_5000_SUBMISSIONS_TABLE . "
			WHERE created_at BETWEEN %s AND %s
			GROUP BY score_range
			ORDER BY score_range",
			$date_from,
			$date_to
		);
		
		$results = $wpdb->get_results( $sql, ARRAY_A );
		
		// Ensure all ranges are present
		$distribution = array(
			'0-20' => 0,
			'20-40' => 0,
			'40-60' => 0,
			'60-80' => 0,
			'80-100' => 0,
		);
		
		foreach ( $results as $row ) {
			$distribution[ $row['score_range'] ] = intval( $row['count'] );
		}
		
		return $distribution;
	}

	/**
	 * Export analytics data.
	 *
	 * @since    1.0.0
	 * @param    string    $format      Export format (csv, json).
	 * @param    string    $date_from   Start date.
	 * @param    string    $date_to     End date.
	 * @return   void
	 */
	public function export_analytics( $format = 'csv', $date_from = null, $date_to = null ) {
		if ( ! $date_from ) {
			$date_from = date( 'Y-m-d', strtotime( '-30 days' ) );
		}
		
		if ( ! $date_to ) {
			$date_to = date( 'Y-m-d' );
		}
		
		$data = $this->get_analytics_data( 'custom' );
		
		switch ( $format ) {
			case 'json':
				$this->export_json( $data );
				break;
			
			case 'csv':
			default:
				$this->export_csv( $data );
				break;
		}
	}

	/**
	 * Export data as CSV.
	 *
	 * @since    1.0.0
	 * @param    array    $data    Data to export.
	 */
	private function export_csv( $data ) {
		$filename = 'spam-slayer-5000-analytics-' . date( 'Y-m-d' ) . '.csv';
		
		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		
		$output = fopen( 'php://output', 'w' );
		
		// Summary
		fputcsv( $output, array( 'Spam Slayer 5000 Analytics Report' ) );
		fputcsv( $output, array( 'Period:', $data['date_from'] . ' to ' . $data['date_to'] ) );
		fputcsv( $output, array() );
		
		// Summary stats
		fputcsv( $output, array( 'Summary Statistics' ) );
		fputcsv( $output, array( 'Metric', 'Value' ) );
		foreach ( $data['summary'] as $key => $value ) {
			$label = ucwords( str_replace( '_', ' ', $key ) );
			if ( strpos( $key, 'cost' ) !== false ) {
				$value = '$' . number_format( $value, 4 );
			} elseif ( strpos( $key, 'rate' ) !== false ) {
				$value = number_format( $value, 2 ) . '%';
			} elseif ( strpos( $key, 'time' ) !== false ) {
				$value = number_format( $value, 3 ) . 's';
			}
			fputcsv( $output, array( $label, $value ) );
		}
		
		fputcsv( $output, array() );
		
		// Daily breakdown
		fputcsv( $output, array( 'Daily Breakdown' ) );
		fputcsv( $output, array( 'Date', 'Total', 'Spam', 'Approved' ) );
		foreach ( $data['daily_breakdown'] as $date => $stats ) {
			fputcsv( $output, array( $date, $stats['total'], $stats['spam'], $stats['approved'] ) );
		}
		
		fclose( $output );
		exit;
	}

	/**
	 * Export data as JSON.
	 *
	 * @since    1.0.0
	 * @param    array    $data    Data to export.
	 */
	private function export_json( $data ) {
		$filename = 'spam-slayer-5000-analytics-' . date( 'Y-m-d' ) . '.json';
		
		header( 'Content-Type: application/json' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		
		echo wp_json_encode( $data, JSON_PRETTY_PRINT );
		exit;
	}
}