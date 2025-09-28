<?php
/**
 * Logs admin page display.
 *
 * @since      1.0.0
 * @package    Spam_Slayer_5000
 * @subpackage Spam_Slayer_5000/admin/partials
 */

// Security check
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Check permissions
if ( ! current_user_can( 'manage_network' ) ) {
	wp_die( esc_html__( 'Sorry, you do not have permission to access this page.', 'spam-slayer-5000' ) );
}

// Handle clear logs action
if ( isset( $_POST['ss5k_clear_logs'] ) && check_admin_referer( 'ss5k_logs_nonce' ) ) {
	$logger = new Spam_Slayer_5000_Logger();
	if ( $logger->clear_log() ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Logs cleared successfully.', 'spam-slayer-5000' ) . '</p></div>';
	} else {
		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Failed to clear logs.', 'spam-slayer-5000' ) . '</p></div>';
	}
}

// Get log content
$logger = new Spam_Slayer_5000_Logger();
$lines = isset( $_GET['lines'] ) ? absint( $_GET['lines'] ) : 100;
$log_content = $logger->get_log( $lines );
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Debug Logs', 'spam-slayer-5000' ); ?></h1>
	
	<div class="sfs-logs-container">
		<div class="sfs-logs-header">
			<form method="get" style="display: inline;">
				<input type="hidden" name="page" value="spam-slayer-5000-logs" />
				<label for="log-lines"><?php esc_html_e( 'Show last', 'spam-slayer-5000' ); ?></label>
				<select name="lines" id="log-lines" onchange="this.form.submit()">
					<option value="50" <?php selected( $lines, 50 ); ?>>50</option>
					<option value="100" <?php selected( $lines, 100 ); ?>>100</option>
					<option value="200" <?php selected( $lines, 200 ); ?>>200</option>
					<option value="500" <?php selected( $lines, 500 ); ?>>500</option>
					<option value="1000" <?php selected( $lines, 1000 ); ?>>1000</option>
				</select>
				<label><?php esc_html_e( 'lines', 'spam-slayer-5000' ); ?></label>
			</form>
			
			<form method="post" style="display: inline; margin-left: 20px;">
				<?php wp_nonce_field( 'ss5k_logs_nonce' ); ?>
				<input type="submit" name="ss5k_clear_logs" class="button" value="<?php esc_attr_e( 'Clear Logs', 'spam-slayer-5000' ); ?>" 
					onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to clear all logs? This action cannot be undone.', 'spam-slayer-5000' ); ?>');" />
			</form>
			
			<a href="<?php echo wp_nonce_url( admin_url( 'admin-ajax.php?action=ss5k_export_data&export_type=logs' ), 'ss5k_admin_nonce', 'nonce' ); ?>" 
				class="button" style="margin-left: 10px;">
				<?php esc_html_e( 'Download Full Log', 'spam-slayer-5000' ); ?>
			</a>
		</div>
		
		<div class="sfs-log-info">
			<p><?php esc_html_e( 'Current log level:', 'spam-slayer-5000' ); ?> 
				<strong><?php echo esc_html( ucfirst( get_option( 'spam_slayer_5000_log_level', 'info' ) ) ); ?></strong>
			</p>
			<p><?php esc_html_e( 'Logging enabled:', 'spam-slayer-5000' ); ?> 
				<strong><?php echo get_option( 'spam_slayer_5000_enable_logging', true ) ? esc_html__( 'Yes', 'spam-slayer-5000' ) : esc_html__( 'No', 'spam-slayer-5000' ); ?></strong>
			</p>
		</div>
		
		<h2><?php esc_html_e( 'Log Output', 'spam-slayer-5000' ); ?></h2>
		
		<?php if ( empty( $log_content ) ) : ?>
			<p><?php esc_html_e( 'No log entries found.', 'spam-slayer-5000' ); ?></p>
		<?php else : ?>
			<div class="sfs-log-viewer">
				<pre><?php echo esc_html( $log_content ); ?></pre>
			</div>
		<?php endif; ?>
		
		<div class="sfs-log-legend">
			<h3><?php esc_html_e( 'Log Level Legend', 'spam-slayer-5000' ); ?></h3>
			<ul>
				<li><strong>DEBUG:</strong> <?php esc_html_e( 'Detailed debug information', 'spam-slayer-5000' ); ?></li>
				<li><strong>INFO:</strong> <?php esc_html_e( 'General informational messages', 'spam-slayer-5000' ); ?></li>
				<li><strong>WARNING:</strong> <?php esc_html_e( 'Warning messages about potential issues', 'spam-slayer-5000' ); ?></li>
				<li><strong>ERROR:</strong> <?php esc_html_e( 'Error messages that need attention', 'spam-slayer-5000' ); ?></li>
				<li><strong>CRITICAL:</strong> <?php esc_html_e( 'Critical errors that may cause functionality issues', 'spam-slayer-5000' ); ?></li>
			</ul>
		</div>
	</div>
</div>

<style>
.ss5k-log-viewer {
	background: #f0f0f0;
	border: 1px solid #ccc;
	padding: 15px;
	max-height: 600px;
	overflow-y: auto;
	font-family: Consolas, Monaco, monospace;
	font-size: 12px;
	line-height: 1.5;
}

.ss5k-log-viewer pre {
	margin: 0;
	white-space: pre-wrap;
	word-wrap: break-word;
}

.ss5k-logs-header {
	margin-bottom: 20px;
	padding: 15px;
	background: #fff;
	border: 1px solid #ccd0d4;
	box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.ss5k-log-info {
	margin: 20px 0;
	padding: 10px 15px;
	background: #f8f9fa;
	border-left: 4px solid #007cba;
}

.ss5k-log-legend {
	margin-top: 20px;
	padding: 15px;
	background: #fff;
	border: 1px solid #ccd0d4;
	box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.ss5k-log-legend h3 {
	margin-top: 0;
}

.ss5k-log-legend ul {
	list-style-type: disc;
	margin-left: 20px;
}
</style>