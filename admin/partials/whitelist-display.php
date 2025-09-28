<?php
/**
 * Whitelist admin page display.
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
if ( ! class_exists( 'Spam_Slayer_5000_Database' ) ) {
	require_once SPAM_SLAYER_5000_PATH . 'database/class-database.php';
}

// Handle form submission
if ( isset( $_POST['ss5k_add_whitelist'] ) && check_admin_referer( 'ss5k_whitelist_nonce' ) ) {
	$email = sanitize_email( $_POST['whitelist_email'] );
	$reason = sanitize_textarea_field( $_POST['whitelist_reason'] );
	
	if ( is_email( $email ) ) {
		$database = new Spam_Slayer_5000_Database();
		$result = $database->add_to_whitelist( $email, $reason );
		
		if ( $result ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Email added to whitelist successfully.', 'spam-slayer-5000' ) . '</p></div>';
		} else {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Failed to add email to whitelist. It may already exist.', 'spam-slayer-5000' ) . '</p></div>';
		}
	} else {
		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Please enter a valid email address.', 'spam-slayer-5000' ) . '</p></div>';
	}
}

// Get whitelist entries
global $wpdb;
$whitelist_entries = $wpdb->get_results(
	"SELECT w.*, u.display_name as added_by_name 
	FROM " . SPAM_SLAYER_5000_WHITELIST_TABLE . " w
	LEFT JOIN {$wpdb->users} u ON w.added_by = u.ID
	WHERE w.is_active = 1
	ORDER BY w.created_at DESC",
	ARRAY_A
);
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Email Whitelist', 'spam-slayer-5000' ); ?></h1>
	
	<div class="ss5k-whitelist-form">
		<h2><?php esc_html_e( 'Add Email to Whitelist', 'spam-slayer-5000' ); ?></h2>
		<form method="post" action="">
			<?php wp_nonce_field( 'ss5k_whitelist_nonce' ); ?>
			
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="whitelist_email"><?php esc_html_e( 'Email Address', 'spam-slayer-5000' ); ?></label>
					</th>
					<td>
						<input type="email" id="whitelist_email" name="whitelist_email" class="regular-text" required />
						<p class="description"><?php esc_html_e( 'Enter the email address to whitelist.', 'spam-slayer-5000' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="whitelist_reason"><?php esc_html_e( 'Reason (Optional)', 'spam-slayer-5000' ); ?></label>
					</th>
					<td>
						<textarea id="whitelist_reason" name="whitelist_reason" rows="3" class="large-text"></textarea>
						<p class="description"><?php esc_html_e( 'Add a note about why this email is being whitelisted.', 'spam-slayer-5000' ); ?></p>
					</td>
				</tr>
			</table>
			
			<p class="submit">
				<input type="submit" name="ss5k_add_whitelist" class="button button-primary" value="<?php esc_attr_e( 'Add to Whitelist', 'spam-slayer-5000' ); ?>" />
			</p>
		</form>
	</div>
	
	<hr />
	
	<h2><?php esc_html_e( 'Whitelisted Emails', 'spam-slayer-5000' ); ?></h2>
	
	<?php if ( empty( $whitelist_entries ) ) : ?>
		<p><?php esc_html_e( 'No emails have been whitelisted yet.', 'spam-slayer-5000' ); ?></p>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th scope="col" class="column-email"><?php esc_html_e( 'Email', 'spam-slayer-5000' ); ?></th>
					<th scope="col" class="column-domain"><?php esc_html_e( 'Domain', 'spam-slayer-5000' ); ?></th>
					<th scope="col" class="column-reason"><?php esc_html_e( 'Reason', 'spam-slayer-5000' ); ?></th>
					<th scope="col" class="column-added-by"><?php esc_html_e( 'Added By', 'spam-slayer-5000' ); ?></th>
					<th scope="col" class="column-date"><?php esc_html_e( 'Date Added', 'spam-slayer-5000' ); ?></th>
					<th scope="col" class="column-actions"><?php esc_html_e( 'Actions', 'spam-slayer-5000' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $whitelist_entries as $entry ) : ?>
					<tr>
						<td class="column-email">
							<strong><?php echo esc_html( $entry['email'] ); ?></strong>
						</td>
						<td class="column-domain">
							<?php echo esc_html( $entry['domain'] ); ?>
						</td>
						<td class="column-reason">
							<?php echo esc_html( $entry['reason'] ?: '-' ); ?>
						</td>
						<td class="column-added-by">
							<?php echo esc_html( $entry['added_by_name'] ?: __( 'Unknown', 'spam-slayer-5000' ) ); ?>
						</td>
						<td class="column-date">
							<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $entry['created_at'] ) ) ); ?>
						</td>
						<td class="column-actions">
							<button type="button" class="button button-small ss5k-remove-whitelist" data-id="<?php echo esc_attr( $entry['id'] ); ?>">
								<?php esc_html_e( 'Remove', 'spam-slayer-5000' ); ?>
							</button>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
	
	<div class="ss5k-whitelist-info">
		<h3><?php esc_html_e( 'About Whitelisting', 'spam-slayer-5000' ); ?></h3>
		<p><?php esc_html_e( 'Whitelisted email addresses will automatically pass spam checks without being sent to AI providers. This is useful for:', 'spam-slayer-5000' ); ?></p>
		<ul style="list-style-type: disc; margin-left: 20px;">
			<li><?php esc_html_e( 'Known customers or contacts', 'spam-slayer-5000' ); ?></li>
			<li><?php esc_html_e( 'Internal team members', 'spam-slayer-5000' ); ?></li>
			<li><?php esc_html_e( 'Trusted partners or vendors', 'spam-slayer-5000' ); ?></li>
		</ul>
		<p><?php esc_html_e( 'Note: Whitelisting is based on exact email match. Domain-wide whitelisting is not currently supported for security reasons.', 'spam-slayer-5000' ); ?></p>
	</div>
</div>