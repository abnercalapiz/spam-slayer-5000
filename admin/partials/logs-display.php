<?php
/**
 * Logs admin page display.
 *
 * @since      1.0.0
 * @package    Smart_Form_Shield
 * @subpackage Smart_Form_Shield/admin/partials
 */

// Security check
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Check permissions
if ( ! current_user_can( 'manage_network' ) ) {
	wp_die( esc_html__( 'Sorry, you do not have permission to access this page.', 'smart-form-shield' ) );
}

// Handle clear logs action
if ( isset( $_POST['sfs_clear_logs'] ) && check_admin_referer( 'sfs_logs_nonce' ) ) {
	$logger = new Smart_Form_Shield_Logger();
	if ( $logger->clear_log() ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Logs cleared successfully.', 'smart-form-shield' ) . '</p></div>';
	} else {
		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Failed to clear logs.', 'smart-form-shield' ) . '</p></div>';
	}
}

// Get log content
$logger = new Smart_Form_Shield_Logger();
$lines = isset( $_GET['lines'] ) ? absint( $_GET['lines'] ) : 100;
$log_content = $logger->get_log( $lines );
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Debug Logs', 'smart-form-shield' ); ?></h1>
	
	<div class="sfs-logs-container">
		<div class="sfs-logs-header">
			<form method="get" style="display: inline;">
				<input type="hidden" name="page" value="smart-form-shield-logs" />
				<label for="log-lines"><?php esc_html_e( 'Show last', 'smart-form-shield' ); ?></label>
				<select name="lines" id="log-lines" onchange="this.form.submit()">
					<option value="50" <?php selected( $lines, 50 ); ?>>50</option>
					<option value="100" <?php selected( $lines, 100 ); ?>>100</option>
					<option value="200" <?php selected( $lines, 200 ); ?>>200</option>
					<option value="500" <?php selected( $lines, 500 ); ?>>500</option>
					<option value="1000" <?php selected( $lines, 1000 ); ?>>1000</option>
				</select>
				<label><?php esc_html_e( 'lines', 'smart-form-shield' ); ?></label>
			</form>
			
			<form method="post" style="display: inline; margin-left: 20px;">
				<?php wp_nonce_field( 'sfs_logs_nonce' ); ?>
				<input type="submit" name="sfs_clear_logs" class="button" value="<?php esc_attr_e( 'Clear Logs', 'smart-form-shield' ); ?>" 
					onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to clear all logs? This action cannot be undone.', 'smart-form-shield' ); ?>');" />
			</form>
			
			<a href="<?php echo wp_nonce_url( admin_url( 'admin-ajax.php?action=sfs_export_data&export_type=logs' ), 'sfs_admin_nonce', 'nonce' ); ?>" 
				class="button" style="margin-left: 10px;">
				<?php esc_html_e( 'Download Full Log', 'smart-form-shield' ); ?>
			</a>
		</div>
		
		<div class="sfs-log-info">
			<p><?php esc_html_e( 'Current log level:', 'smart-form-shield' ); ?> 
				<strong><?php echo esc_html( ucfirst( get_option( 'smart_form_shield_log_level', 'info' ) ) ); ?></strong>
			</p>
			<p><?php esc_html_e( 'Logging enabled:', 'smart-form-shield' ); ?> 
				<strong><?php echo get_option( 'smart_form_shield_enable_logging', true ) ? esc_html__( 'Yes', 'smart-form-shield' ) : esc_html__( 'No', 'smart-form-shield' ); ?></strong>
			</p>
		</div>
		
		<h2><?php esc_html_e( 'Log Output', 'smart-form-shield' ); ?></h2>
		
		<?php if ( empty( $log_content ) ) : ?>
			<p><?php esc_html_e( 'No log entries found.', 'smart-form-shield' ); ?></p>
		<?php else : ?>
			<div class="sfs-log-viewer">
				<pre><?php echo esc_html( $log_content ); ?></pre>
			</div>
		<?php endif; ?>
		
		<div class="sfs-log-legend">
			<h3><?php esc_html_e( 'Log Level Legend', 'smart-form-shield' ); ?></h3>
			<ul>
				<li><strong>DEBUG:</strong> <?php esc_html_e( 'Detailed debug information', 'smart-form-shield' ); ?></li>
				<li><strong>INFO:</strong> <?php esc_html_e( 'General informational messages', 'smart-form-shield' ); ?></li>
				<li><strong>WARNING:</strong> <?php esc_html_e( 'Warning messages about potential issues', 'smart-form-shield' ); ?></li>
				<li><strong>ERROR:</strong> <?php esc_html_e( 'Error messages that need attention', 'smart-form-shield' ); ?></li>
				<li><strong>CRITICAL:</strong> <?php esc_html_e( 'Critical errors that may cause functionality issues', 'smart-form-shield' ); ?></li>
			</ul>
		</div>
	</div>
</div>

<style>
.sfs-log-viewer {
	background: #f0f0f0;
	border: 1px solid #ccc;
	padding: 15px;
	max-height: 600px;
	overflow-y: auto;
	font-family: Consolas, Monaco, monospace;
	font-size: 12px;
	line-height: 1.5;
}

.sfs-log-viewer pre {
	margin: 0;
	white-space: pre-wrap;
	word-wrap: break-word;
}

.sfs-logs-header {
	margin-bottom: 20px;
	padding: 15px;
	background: #fff;
	border: 1px solid #ccd0d4;
	box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.sfs-log-info {
	margin: 20px 0;
	padding: 10px 15px;
	background: #f8f9fa;
	border-left: 4px solid #007cba;
}

.sfs-log-legend {
	margin-top: 20px;
	padding: 15px;
	background: #fff;
	border: 1px solid #ccd0d4;
	box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.sfs-log-legend h3 {
	margin-top: 0;
}

.sfs-log-legend ul {
	list-style-type: disc;
	margin-left: 20px;
}
</style>