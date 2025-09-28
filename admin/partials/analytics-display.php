<?php
/**
 * Analytics admin page display.
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
if ( ! class_exists( 'Spam_Slayer_5000_Admin_Analytics' ) ) {
	require_once SPAM_SLAYER_5000_PATH . 'admin/class-admin-analytics.php';
}
if ( ! class_exists( 'Spam_Slayer_5000_Database' ) ) {
	require_once SPAM_SLAYER_5000_PATH . 'database/class-database.php';
}

// Ensure database tables exist
global $wpdb;
$tables_exist = $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}ss5k_submissions'" );
if ( ! $tables_exist ) {
	// Tables don't exist, try to create them
	if ( ! class_exists( 'Spam_Slayer_5000_Activator' ) ) {
		require_once SPAM_SLAYER_5000_PATH . 'includes/class-activator.php';
	}
	Spam_Slayer_5000_Activator::activate();
}

// Get analytics data
try {
	$analytics = new Spam_Slayer_5000_Admin_Analytics( 'spam-slayer-5000', SPAM_SLAYER_5000_VERSION );
	$period = isset( $_GET['period'] ) ? sanitize_text_field( $_GET['period'] ) : 'week';
	$data = $analytics->get_analytics_data( $period );
} catch ( Exception $e ) {
	// If there's an error, provide default empty data structure
	$data = array(
		'summary' => array(
			'total_submissions' => 0,
			'spam_submissions' => 0,
			'spam_rate' => 0,
			'total_cost' => 0,
			'total_api_calls' => 0,
			'avg_response_time' => 0,
		),
		'daily_breakdown' => array(),
		'providers' => array(),
		'form_breakdown' => array(),
		'spam_distribution' => array(
			'0-20' => 0,
			'21-40' => 0,
			'41-60' => 0,
			'61-80' => 0,
			'81-100' => 0,
		),
	);
	
	// Show error message
	echo '<div class="notice notice-error"><p>' . esc_html__( 'Error loading analytics data: ', 'spam-slayer-5000' ) . esc_html( $e->getMessage() ) . '</p></div>';
}
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Analytics', 'spam-slayer-5000' ); ?></h1>
	
	<div class="ss5k-analytics-header">
		<div class="ss5k-period-selector">
			<label for="ss5k-analytics-range"><?php esc_html_e( 'Time Period:', 'spam-slayer-5000' ); ?></label>
			<select id="ss5k-analytics-range" onchange="window.location.href='?page=spam-slayer-5000-analytics&period=' + this.value">
				<option value="day" <?php selected( $period, 'day' ); ?>><?php esc_html_e( 'Today', 'spam-slayer-5000' ); ?></option>
				<option value="week" <?php selected( $period, 'week' ); ?>><?php esc_html_e( 'Last 7 Days', 'spam-slayer-5000' ); ?></option>
				<option value="month" <?php selected( $period, 'month' ); ?>><?php esc_html_e( 'Last 30 Days', 'spam-slayer-5000' ); ?></option>
			</select>
		</div>
	</div>
	
	<!-- Summary Stats -->
	<div class="ss5k-stats-boxes">
		<div class="ss5k-stat-box">
			<h3><?php esc_html_e( 'Total Submissions', 'spam-slayer-5000' ); ?></h3>
			<div class="value"><?php echo number_format( $data['summary']['total_submissions'] ); ?></div>
		</div>
		
		<div class="ss5k-stat-box">
			<h3><?php esc_html_e( 'Spam Blocked', 'spam-slayer-5000' ); ?></h3>
			<div class="value"><?php echo number_format( $data['summary']['spam_submissions'] ); ?></div>
			<div class="description"><?php echo number_format( $data['summary']['spam_rate'], 1 ); ?>%</div>
		</div>
		
		<div class="ss5k-stat-box">
			<h3><?php esc_html_e( 'Total Cost', 'spam-slayer-5000' ); ?></h3>
			<div class="value">$<?php echo number_format( $data['summary']['total_cost'], 4 ); ?></div>
		</div>
		
		<div class="ss5k-stat-box">
			<h3><?php esc_html_e( 'API Calls', 'spam-slayer-5000' ); ?></h3>
			<div class="value"><?php echo number_format( $data['summary']['total_api_calls'] ); ?></div>
			<div class="description"><?php echo number_format( $data['summary']['avg_response_time'], 3 ); ?>s avg</div>
		</div>
	</div>
	
	<!-- Daily Breakdown Chart -->
	<div class="ss5k-chart-container">
		<h3><?php esc_html_e( 'Daily Submissions', 'spam-slayer-5000' ); ?></h3>
		<canvas id="daily-submissions-chart" width="400" height="150"></canvas>
	</div>
	
	<!-- Provider Performance -->
	<?php if ( ! empty( $data['providers'] ) ) : ?>
	<div class="ss5k-chart-container">
		<h3><?php esc_html_e( 'Provider Performance', 'spam-slayer-5000' ); ?></h3>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Provider', 'spam-slayer-5000' ); ?></th>
					<th><?php esc_html_e( 'API Calls', 'spam-slayer-5000' ); ?></th>
					<th><?php esc_html_e( 'Success Rate', 'spam-slayer-5000' ); ?></th>
					<th><?php esc_html_e( 'Avg Response Time', 'spam-slayer-5000' ); ?></th>
					<th><?php esc_html_e( 'Total Cost', 'spam-slayer-5000' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $data['providers'] as $provider => $stats ) : ?>
				<tr>
					<td><strong><?php echo esc_html( ucfirst( $provider ) ); ?></strong></td>
					<td><?php echo number_format( $stats['calls'] ); ?></td>
					<td><?php echo number_format( $stats['success_rate'], 1 ); ?>%</td>
					<td><?php echo number_format( $stats['avg_response_time'], 3 ); ?>s</td>
					<td>$<?php echo number_format( $stats['cost'], 4 ); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php endif; ?>
	
	<!-- Form Breakdown -->
	<?php if ( ! empty( $data['form_breakdown'] ) ) : ?>
	<div class="ss5k-chart-container">
		<h3><?php esc_html_e( 'Form Breakdown', 'spam-slayer-5000' ); ?></h3>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Form', 'spam-slayer-5000' ); ?></th>
					<th><?php esc_html_e( 'Total Submissions', 'spam-slayer-5000' ); ?></th>
					<th><?php esc_html_e( 'Spam Caught', 'spam-slayer-5000' ); ?></th>
					<th><?php esc_html_e( 'Spam Rate', 'spam-slayer-5000' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $data['form_breakdown'] as $form ) : ?>
				<tr>
					<td>
						<strong><?php echo esc_html( ucwords( str_replace( '_', ' ', $form['form_type'] ) ) ); ?></strong>
						<br>
						<small><?php echo esc_html( $form['form_id'] ); ?></small>
					</td>
					<td><?php echo number_format( $form['total'] ); ?></td>
					<td><?php echo number_format( $form['spam'] ); ?></td>
					<td>
						<?php 
						$spam_rate = $form['total'] > 0 ? ( $form['spam'] / $form['total'] ) * 100 : 0;
						echo number_format( $spam_rate, 1 ) . '%'; 
						?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php endif; ?>
	
	<!-- Spam Score Distribution -->
	<div class="ss5k-chart-container">
		<h3><?php esc_html_e( 'Spam Score Distribution', 'spam-slayer-5000' ); ?></h3>
		<canvas id="spam-distribution-chart" width="400" height="150"></canvas>
	</div>
	
	<!-- Export Options -->
	<div class="ss5k-export-section">
		<h3><?php esc_html_e( 'Export Analytics', 'spam-slayer-5000' ); ?></h3>
		<p>
			<a href="<?php echo wp_nonce_url( admin_url( 'admin-ajax.php?action=ss5k_export_data&export_type=analytics&format=csv' ), 'ss5k_admin_nonce', 'nonce' ); ?>" 
				class="button">
				<?php esc_html_e( 'Export as CSV', 'spam-slayer-5000' ); ?>
			</a>
			<a href="<?php echo wp_nonce_url( admin_url( 'admin-ajax.php?action=ss5k_export_data&export_type=analytics&format=json' ), 'ss5k_admin_nonce', 'nonce' ); ?>" 
				class="button">
				<?php esc_html_e( 'Export as JSON', 'spam-slayer-5000' ); ?>
			</a>
		</p>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	// Daily Submissions Chart
	var dailyCtx = document.getElementById('daily-submissions-chart').getContext('2d');
	var dailyData = <?php echo wp_json_encode( $data['daily_breakdown'] ); ?>;
	
	var labels = Object.keys(dailyData);
	var totalData = labels.map(function(date) { return dailyData[date].total; });
	var spamData = labels.map(function(date) { return dailyData[date].spam; });
	var approvedData = labels.map(function(date) { return dailyData[date].approved; });
	
	new Chart(dailyCtx, {
		type: 'line',
		data: {
			labels: labels,
			datasets: [{
				label: '<?php esc_html_e( 'Total', 'spam-slayer-5000' ); ?>',
				data: totalData,
				borderColor: 'rgb(75, 192, 192)',
				backgroundColor: 'rgba(75, 192, 192, 0.2)',
				tension: 0.1
			}, {
				label: '<?php esc_html_e( 'Spam', 'spam-slayer-5000' ); ?>',
				data: spamData,
				borderColor: 'rgb(255, 99, 132)',
				backgroundColor: 'rgba(255, 99, 132, 0.2)',
				tension: 0.1
			}, {
				label: '<?php esc_html_e( 'Approved', 'spam-slayer-5000' ); ?>',
				data: approvedData,
				borderColor: 'rgb(54, 162, 235)',
				backgroundColor: 'rgba(54, 162, 235, 0.2)',
				tension: 0.1
			}]
		},
		options: {
			responsive: true,
			maintainAspectRatio: false,
			plugins: {
				legend: {
					display: true,
					position: 'top',
				}
			},
			scales: {
				y: {
					beginAtZero: true
				}
			}
		}
	});
	
	// Spam Distribution Chart
	var distCtx = document.getElementById('spam-distribution-chart').getContext('2d');
	var distribution = <?php echo wp_json_encode( $data['spam_distribution'] ); ?>;
	
	new Chart(distCtx, {
		type: 'bar',
		data: {
			labels: Object.keys(distribution),
			datasets: [{
				label: '<?php esc_html_e( 'Submissions', 'spam-slayer-5000' ); ?>',
				data: Object.values(distribution),
				backgroundColor: [
					'rgba(75, 192, 192, 0.6)',
					'rgba(54, 162, 235, 0.6)',
					'rgba(255, 206, 86, 0.6)',
					'rgba(255, 159, 64, 0.6)',
					'rgba(255, 99, 132, 0.6)'
				],
				borderColor: [
					'rgb(75, 192, 192)',
					'rgb(54, 162, 235)',
					'rgb(255, 206, 86)',
					'rgb(255, 159, 64)',
					'rgb(255, 99, 132)'
				],
				borderWidth: 1
			}]
		},
		options: {
			responsive: true,
			maintainAspectRatio: false,
			plugins: {
				legend: {
					display: false
				}
			},
			scales: {
				y: {
					beginAtZero: true
				}
			}
		}
	});
});
</script>