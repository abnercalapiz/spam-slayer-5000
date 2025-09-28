<?php
/**
 * Blocklist admin page display.
 *
 * @since      1.1.0
 * @package    Spam_Slayer_5000
 * @subpackage Spam_Slayer_5000/admin/partials
 */

// Security check
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Include required classes if not already loaded
if ( ! class_exists( 'Spam_Slayer_5000_Database' ) ) {
	require_once SPAM_SLAYER_5000_PATH . 'database/class-database.php';
}

// Handle form submission
if ( isset( $_POST['ss5k_add_blocklist'] ) && check_admin_referer( 'ss5k_blocklist_nonce' ) ) {
	$type = sanitize_text_field( $_POST['blocklist_type'] );
	$value = sanitize_text_field( $_POST['blocklist_value'] );
	$reason = sanitize_textarea_field( $_POST['blocklist_reason'] );
	
	$is_valid = false;
	$error_message = '';
	
	// Validate based on type
	if ( $type === 'email' ) {
		if ( is_email( $value ) ) {
			$is_valid = true;
		} else {
			$error_message = __( 'Please enter a valid email address.', 'spam-slayer-5000' );
		}
	} elseif ( $type === 'ip' ) {
		// Validate IP address
		if ( filter_var( $value, FILTER_VALIDATE_IP ) ) {
			$is_valid = true;
		} else {
			$error_message = __( 'Please enter a valid IP address.', 'spam-slayer-5000' );
		}
	}
	
	if ( $is_valid ) {
		$database = new Spam_Slayer_5000_Database();
		$result = $database->add_to_blocklist( $type, $value, $reason );
		
		if ( $result ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . 
				sprintf( __( '%s added to blocklist successfully.', 'spam-slayer-5000' ), ucfirst( $type ) ) . 
				'</p></div>';
		} else {
			echo '<div class="notice notice-error is-dismissible"><p>' . 
				__( 'Failed to add to blocklist. It may already exist.', 'spam-slayer-5000' ) . 
				'</p></div>';
		}
	} else {
		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $error_message ) . '</p></div>';
	}
}

// Get current tab
$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'email';

// Get blocklist entries
global $wpdb;
$table_name = Spam_Slayer_5000_Database::get_table_name( 'blocklist' );
$blocklist_entries = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT b.*, u.display_name as added_by_name 
		FROM $table_name b
		LEFT JOIN {$wpdb->users} u ON b.added_by = u.ID
		WHERE b.is_active = 1 AND b.type = %s
		ORDER BY b.created_at DESC",
		$current_tab
	),
	ARRAY_A
);
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Blocklist Management', 'spam-slayer-5000' ); ?></h1>
	
	<p class="description">
		<?php esc_html_e( 'Add email addresses or IP addresses that should be automatically marked as spam.', 'spam-slayer-5000' ); ?>
	</p>
	
	<!-- Tab Navigation -->
	<nav class="nav-tab-wrapper">
		<a href="?page=spam-slayer-5000-blocklist&tab=email" class="nav-tab <?php echo $current_tab === 'email' ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Blocked Emails', 'spam-slayer-5000' ); ?>
		</a>
		<a href="?page=spam-slayer-5000-blocklist&tab=ip" class="nav-tab <?php echo $current_tab === 'ip' ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Blocked IPs', 'spam-slayer-5000' ); ?>
		</a>
	</nav>
	
	<!-- Add to Blocklist Form -->
	<div class="ss5k-add-blocklist-form">
		<h2><?php echo $current_tab === 'email' ? esc_html__( 'Add Email to Blocklist', 'spam-slayer-5000' ) : esc_html__( 'Add IP to Blocklist', 'spam-slayer-5000' ); ?></h2>
		
		<form method="post" action="">
			<?php wp_nonce_field( 'ss5k_blocklist_nonce' ); ?>
			<input type="hidden" name="blocklist_type" value="<?php echo esc_attr( $current_tab ); ?>">
			
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="blocklist_value">
							<?php echo $current_tab === 'email' ? esc_html__( 'Email Address', 'spam-slayer-5000' ) : esc_html__( 'IP Address', 'spam-slayer-5000' ); ?>
						</label>
					</th>
					<td>
						<input type="text" 
							id="blocklist_value" 
							name="blocklist_value" 
							class="regular-text" 
							placeholder="<?php echo $current_tab === 'email' ? 'spammer@example.com' : '192.168.1.1'; ?>"
							required>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="blocklist_reason"><?php esc_html_e( 'Reason (optional)', 'spam-slayer-5000' ); ?></label>
					</th>
					<td>
						<textarea id="blocklist_reason" 
							name="blocklist_reason" 
							class="large-text" 
							rows="3" 
							placeholder="<?php esc_attr_e( 'Why is this being blocked?', 'spam-slayer-5000' ); ?>"></textarea>
					</td>
				</tr>
			</table>
			
			<p class="submit">
				<input type="submit" 
					name="ss5k_add_blocklist" 
					class="button button-primary" 
					value="<?php esc_attr_e( 'Add to Blocklist', 'spam-slayer-5000' ); ?>">
			</p>
		</form>
	</div>
	
	<!-- Blocklist Table -->
	<div class="ss5k-blocklist-table">
		<h2><?php echo $current_tab === 'email' ? esc_html__( 'Blocked Emails', 'spam-slayer-5000' ) : esc_html__( 'Blocked IPs', 'spam-slayer-5000' ); ?></h2>
		
		<?php if ( empty( $blocklist_entries ) ) : ?>
			<p><?php esc_html_e( 'No entries in the blocklist yet.', 'spam-slayer-5000' ); ?></p>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php echo $current_tab === 'email' ? esc_html__( 'Email', 'spam-slayer-5000' ) : esc_html__( 'IP Address', 'spam-slayer-5000' ); ?></th>
						<th><?php esc_html_e( 'Reason', 'spam-slayer-5000' ); ?></th>
						<th><?php esc_html_e( 'Added By', 'spam-slayer-5000' ); ?></th>
						<th><?php esc_html_e( 'Date Added', 'spam-slayer-5000' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'spam-slayer-5000' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $blocklist_entries as $entry ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $entry['value'] ); ?></strong></td>
							<td><?php echo esc_html( $entry['reason'] ?: '-' ); ?></td>
							<td><?php echo esc_html( $entry['added_by_name'] ?: __( 'System', 'spam-slayer-5000' ) ); ?></td>
							<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $entry['created_at'] ) ) ); ?></td>
							<td>
								<a href="#" 
									class="ss5k-remove-blocklist button button-small" 
									data-id="<?php echo esc_attr( $entry['id'] ); ?>">
									<?php esc_html_e( 'Remove', 'spam-slayer-5000' ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
</div>

<style>
.ss5k-add-blocklist-form {
	background: #fff;
	border: 1px solid #ccd0d4;
	padding: 20px;
	margin: 20px 0;
	box-shadow: 0 1px 1px rgba(0,0,0,.04);
}
.ss5k-blocklist-table {
	margin-top: 30px;
}
</style>