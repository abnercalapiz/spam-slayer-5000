<?php
/**
 * Analytics admin page display.
 *
 * @since      1.0.0
 * @package    Smart_Form_Shield
 * @subpackage Smart_Form_Shield/admin/partials
 */

// Security check
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Get analytics data
$analytics = new Smart_Form_Shield_Admin_Analytics( 'smart-form-shield', SMART_FORM_SHIELD_VERSION );
$period = isset( $_GET['period'] ) ? sanitize_text_field( $_GET['period'] ) : 'week';
$data = $analytics->get_analytics_data( $period );
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Analytics', 'smart-form-shield' ); ?></h1>
	
	<div class="sfs-analytics-header">
		<div class="sfs-period-selector">
			<label for="sfs-analytics-range"><?php esc_html_e( 'Time Period:', 'smart-form-shield' ); ?></label>
			<select id="sfs-analytics-range" onchange="window.location.href='?page=smart-form-shield-analytics&period=' + this.value">
				<option value="day" <?php selected( $period, 'day' ); ?>><?php esc_html_e( 'Today', 'smart-form-shield' ); ?></option>
				<option value="week" <?php selected( $period, 'week' ); ?>><?php esc_html_e( 'Last 7 Days', 'smart-form-shield' ); ?></option>
				<option value="month" <?php selected( $period, 'month' ); ?>><?php esc_html_e( 'Last 30 Days', 'smart-form-shield' ); ?></option>
			</select>
		</div>
	</div>
	
	<!-- Summary Stats -->
	<div class="sfs-stats-boxes">
		<div class="sfs-stat-box">
			<h3><?php esc_html_e( 'Total Submissions', 'smart-form-shield' ); ?></h3>
			<div class="value"><?php echo number_format( $data['summary']['total_submissions'] ); ?></div>
		</div>
		
		<div class="sfs-stat-box">
			<h3><?php esc_html_e( 'Spam Blocked', 'smart-form-shield' ); ?></h3>
			<div class="value"><?php echo number_format( $data['summary']['spam_submissions'] ); ?></div>
			<div class="description"><?php echo number_format( $data['summary']['spam_rate'], 1 ); ?>%</div>
		</div>
		
		<div class="sfs-stat-box">
			<h3><?php esc_html_e( 'Total Cost', 'smart-form-shield' ); ?></h3>
			<div class="value">$<?php echo number_format( $data['summary']['total_cost'], 4 ); ?></div>
		</div>
		
		<div class="sfs-stat-box">
			<h3><?php esc_html_e( 'API Calls', 'smart-form-shield' ); ?></h3>
			<div class="value"><?php echo number_format( $data['summary']['total_api_calls'] ); ?></div>
			<div class="description"><?php echo number_format( $data['summary']['avg_response_time'], 3 ); ?>s avg</div>
		</div>
	</div>
	
	<!-- Daily Breakdown Chart -->
	<div class="sfs-chart-container">
		<h3><?php esc_html_e( 'Daily Submissions', 'smart-form-shield' ); ?></h3>
		<canvas id="daily-submissions-chart" width="400" height="150"></canvas>
	</div>
	
	<!-- Provider Performance -->
	<?php if ( ! empty( $data['providers'] ) ) : ?>
	<div class="sfs-chart-container">
		<h3><?php esc_html_e( 'Provider Performance', 'smart-form-shield' ); ?></h3>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Provider', 'smart-form-shield' ); ?></th>
					<th><?php esc_html_e( 'API Calls', 'smart-form-shield' ); ?></th>
					<th><?php esc_html_e( 'Success Rate', 'smart-form-shield' ); ?></th>
					<th><?php esc_html_e( 'Avg Response Time', 'smart-form-shield' ); ?></th>
					<th><?php esc_html_e( 'Total Cost', 'smart-form-shield' ); ?></th>
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
	<div class="sfs-chart-container">
		<h3><?php esc_html_e( 'Form Breakdown', 'smart-form-shield' ); ?></h3>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Form', 'smart-form-shield' ); ?></th>
					<th><?php esc_html_e( 'Total Submissions', 'smart-form-shield' ); ?></th>
					<th><?php esc_html_e( 'Spam Caught', 'smart-form-shield' ); ?></th>
					<th><?php esc_html_e( 'Spam Rate', 'smart-form-shield' ); ?></th>
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
	<div class="sfs-chart-container">
		<h3><?php esc_html_e( 'Spam Score Distribution', 'smart-form-shield' ); ?></h3>
		<canvas id="spam-distribution-chart" width="400" height="150"></canvas>
	</div>
	
	<!-- Export Options -->
	<div class="sfs-export-section">
		<h3><?php esc_html_e( 'Export Analytics', 'smart-form-shield' ); ?></h3>
		<p>
			<a href="<?php echo wp_nonce_url( admin_url( 'admin-ajax.php?action=sfs_export_data&export_type=analytics&format=csv' ), 'sfs_admin_nonce', 'nonce' ); ?>" 
				class="button">
				<?php esc_html_e( 'Export as CSV', 'smart-form-shield' ); ?>
			</a>
			<a href="<?php echo wp_nonce_url( admin_url( 'admin-ajax.php?action=sfs_export_data&export_type=analytics&format=json' ), 'sfs_admin_nonce', 'nonce' ); ?>" 
				class="button">
				<?php esc_html_e( 'Export as JSON', 'smart-form-shield' ); ?>
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
				label: '<?php esc_html_e( 'Total', 'smart-form-shield' ); ?>',
				data: totalData,
				borderColor: 'rgb(75, 192, 192)',
				backgroundColor: 'rgba(75, 192, 192, 0.2)',
				tension: 0.1
			}, {
				label: '<?php esc_html_e( 'Spam', 'smart-form-shield' ); ?>',
				data: spamData,
				borderColor: 'rgb(255, 99, 132)',
				backgroundColor: 'rgba(255, 99, 132, 0.2)',
				tension: 0.1
			}, {
				label: '<?php esc_html_e( 'Approved', 'smart-form-shield' ); ?>',
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
				label: '<?php esc_html_e( 'Submissions', 'smart-form-shield' ); ?>',
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