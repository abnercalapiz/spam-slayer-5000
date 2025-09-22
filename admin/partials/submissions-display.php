<?php
/**
 * Submissions admin page display.
 *
 * @since      1.0.0
 * @package    Smart_Form_Shield
 * @subpackage Smart_Form_Shield/admin/partials
 */

// Security check
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Get filter parameters
$status_filter = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
$form_type_filter = isset( $_GET['form_type'] ) ? sanitize_text_field( $_GET['form_type'] ) : '';
$search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
$paged = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
$per_page = 20;

// Get submissions
$database = new Smart_Form_Shield_Database();
$args = array(
	'status' => $status_filter,
	'form_type' => $form_type_filter,
	'search' => $search,
	'limit' => $per_page,
	'offset' => ( $paged - 1 ) * $per_page,
);

$submissions = $database->get_submissions( $args );
$total_items = $database->get_submissions_count( $args );
$total_pages = ceil( $total_items / $per_page );

// Get status counts
$all_count = $database->get_submissions_count();
$pending_count = $database->get_submissions_count( array( 'status' => 'pending' ) );
$approved_count = $database->get_submissions_count( array( 'status' => 'approved' ) );
$spam_count = $database->get_submissions_count( array( 'status' => 'spam' ) );
$whitelist_count = $database->get_submissions_count( array( 'status' => 'whitelist' ) );
?>

<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Form Submissions', 'smart-form-shield' ); ?></h1>
	
	<hr class="wp-header-end">
	
	<ul class="subsubsub">
		<li><a href="?page=smart-form-shield" class="<?php echo empty( $status_filter ) ? 'current' : ''; ?>">
			<?php esc_html_e( 'All', 'smart-form-shield' ); ?> 
			<span class="count">(<?php echo esc_html( $all_count ); ?>)</span>
		</a> |</li>
		<li><a href="?page=smart-form-shield&status=pending" class="<?php echo $status_filter === 'pending' ? 'current' : ''; ?>">
			<?php esc_html_e( 'Pending', 'smart-form-shield' ); ?> 
			<span class="count">(<?php echo esc_html( $pending_count ); ?>)</span>
		</a> |</li>
		<li><a href="?page=smart-form-shield&status=approved" class="<?php echo $status_filter === 'approved' ? 'current' : ''; ?>">
			<?php esc_html_e( 'Approved', 'smart-form-shield' ); ?> 
			<span class="count">(<?php echo esc_html( $approved_count ); ?>)</span>
		</a> |</li>
		<li><a href="?page=smart-form-shield&status=spam" class="<?php echo $status_filter === 'spam' ? 'current' : ''; ?>">
			<?php esc_html_e( 'Spam', 'smart-form-shield' ); ?> 
			<span class="count">(<?php echo esc_html( $spam_count ); ?>)</span>
		</a> |</li>
		<li><a href="?page=smart-form-shield&status=whitelist" class="<?php echo $status_filter === 'whitelist' ? 'current' : ''; ?>">
			<?php esc_html_e( 'Whitelist', 'smart-form-shield' ); ?> 
			<span class="count">(<?php echo esc_html( $whitelist_count ); ?>)</span>
		</a></li>
	</ul>
	
	<form method="get">
		<input type="hidden" name="page" value="smart-form-shield">
		<?php if ( $status_filter ) : ?>
			<input type="hidden" name="status" value="<?php echo esc_attr( $status_filter ); ?>">
		<?php endif; ?>
		
		<p class="search-box">
			<label class="screen-reader-text" for="submission-search-input"><?php esc_html_e( 'Search submissions', 'smart-form-shield' ); ?>:</label>
			<input type="search" id="submission-search-input" name="s" value="<?php echo esc_attr( $search ); ?>">
			<input type="submit" id="search-submit" class="button" value="<?php esc_attr_e( 'Search', 'smart-form-shield' ); ?>">
		</p>
	</form>
	
	<form method="post" id="submissions-form">
		<div class="tablenav top">
			<div class="alignleft actions bulkactions">
				<label for="bulk-action-selector-top" class="screen-reader-text"><?php esc_html_e( 'Select bulk action', 'smart-form-shield' ); ?></label>
				<select name="action" id="bulk-action-selector-top">
					<option value="-1"><?php esc_html_e( 'Bulk actions', 'smart-form-shield' ); ?></option>
					<option value="approve"><?php esc_html_e( 'Mark as Approved', 'smart-form-shield' ); ?></option>
					<option value="spam"><?php esc_html_e( 'Mark as Spam', 'smart-form-shield' ); ?></option>
					<option value="delete"><?php esc_html_e( 'Delete', 'smart-form-shield' ); ?></option>
				</select>
				<input type="submit" id="doaction" class="button action" value="<?php esc_attr_e( 'Apply', 'smart-form-shield' ); ?>">
			</div>
			
			<div class="alignleft actions">
				<label for="form-type-filter" class="screen-reader-text"><?php esc_html_e( 'Filter by form type', 'smart-form-shield' ); ?></label>
				<select name="form_type" id="form-type-filter">
					<option value=""><?php esc_html_e( 'All form types', 'smart-form-shield' ); ?></option>
					<option value="gravity_forms" <?php selected( $form_type_filter, 'gravity_forms' ); ?>><?php esc_html_e( 'Gravity Forms', 'smart-form-shield' ); ?></option>
					<option value="elementor_forms" <?php selected( $form_type_filter, 'elementor_forms' ); ?>><?php esc_html_e( 'Elementor Forms', 'smart-form-shield' ); ?></option>
					<option value="api" <?php selected( $form_type_filter, 'api' ); ?>><?php esc_html_e( 'API', 'smart-form-shield' ); ?></option>
				</select>
				<input type="submit" name="filter_action" id="post-query-submit" class="button" value="<?php esc_attr_e( 'Filter', 'smart-form-shield' ); ?>">
			</div>
			
			<?php if ( $total_pages > 1 ) : ?>
			<div class="tablenav-pages">
				<span class="displaying-num"><?php printf( esc_html__( '%s items', 'smart-form-shield' ), $total_items ); ?></span>
				<?php
				echo paginate_links( array(
					'base' => add_query_arg( 'paged', '%#%' ),
					'format' => '',
					'prev_text' => __( '&laquo;' ),
					'next_text' => __( '&raquo;' ),
					'total' => $total_pages,
					'current' => $paged,
				) );
				?>
			</div>
			<?php endif; ?>
		</div>
		
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<td class="manage-column column-cb check-column">
						<label class="screen-reader-text" for="cb-select-all-1"><?php esc_html_e( 'Select All', 'smart-form-shield' ); ?></label>
						<input id="cb-select-all-1" type="checkbox">
					</td>
					<th scope="col" class="manage-column"><?php esc_html_e( 'Date', 'smart-form-shield' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'Form', 'smart-form-shield' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'Submission', 'smart-form-shield' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'Spam Score', 'smart-form-shield' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'Provider', 'smart-form-shield' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'Status', 'smart-form-shield' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'Actions', 'smart-form-shield' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $submissions ) ) : ?>
					<tr>
						<td colspan="8" style="text-align: center;">
							<?php esc_html_e( 'No submissions found.', 'smart-form-shield' ); ?>
						</td>
					</tr>
				<?php else : ?>
					<?php foreach ( $submissions as $submission ) : ?>
						<tr>
							<th scope="row" class="check-column">
								<input type="checkbox" name="submission[]" value="<?php echo esc_attr( $submission['id'] ); ?>">
							</th>
							<td>
								<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $submission['created_at'] ) ) ); ?>
							</td>
							<td>
								<?php 
								echo esc_html( ucwords( str_replace( '_', ' ', $submission['form_type'] ) ) );
								if ( $submission['form_id'] ) {
									echo '<br><small>' . esc_html( $submission['form_id'] ) . '</small>';
								}
								?>
							</td>
							<td>
								<?php
								$data = is_array( $submission['submission_data'] ) ? $submission['submission_data'] : array();
								$display = array();
								foreach ( $data as $key => $value ) {
									if ( is_string( $value ) && strlen( $value ) > 50 ) {
										$value = substr( $value, 0, 50 ) . '...';
									}
									$display[] = '<strong>' . esc_html( $key ) . ':</strong> ' . esc_html( $value );
								}
								echo implode( '<br>', array_slice( $display, 0, 3 ) );
								if ( count( $display ) > 3 ) {
									echo '<br><em>' . sprintf( esc_html__( '... and %d more fields', 'smart-form-shield' ), count( $display ) - 3 ) . '</em>';
								}
								?>
							</td>
							<td>
								<?php
								$score = floatval( $submission['spam_score'] );
								$class = 'low';
								if ( $score >= 80 ) {
									$class = 'high';
								} elseif ( $score >= 50 ) {
									$class = 'medium';
								}
								?>
								<span class="sfs-spam-score <?php echo esc_attr( $class ); ?>">
									<?php echo number_format( $score, 1 ); ?>%
								</span>
							</td>
							<td><?php echo esc_html( $submission['provider_used'] ?? '-' ); ?></td>
							<td>
								<span class="sfs-status <?php echo esc_attr( $submission['status'] ); ?>">
									<?php echo esc_html( ucfirst( $submission['status'] ) ); ?>
								</span>
							</td>
							<td>
								<button type="button" class="button sfs-action-btn" data-action="view" data-id="<?php echo esc_attr( $submission['id'] ); ?>">
									<?php esc_html_e( 'View', 'smart-form-shield' ); ?>
								</button>
								<?php if ( $submission['status'] !== 'approved' ) : ?>
									<button type="button" class="button sfs-action-btn" data-action="approve" data-id="<?php echo esc_attr( $submission['id'] ); ?>">
										<?php esc_html_e( 'Approve', 'smart-form-shield' ); ?>
									</button>
								<?php endif; ?>
								<?php if ( $submission['status'] !== 'spam' ) : ?>
									<button type="button" class="button sfs-action-btn" data-action="spam" data-id="<?php echo esc_attr( $submission['id'] ); ?>">
										<?php esc_html_e( 'Spam', 'smart-form-shield' ); ?>
									</button>
								<?php endif; ?>
								<?php
								$email = Smart_Form_Shield_Validator::extract_email( $data );
								if ( $email && ! $database->is_whitelisted( $email ) ) :
								?>
									<button type="button" class="button sfs-add-whitelist" data-email="<?php echo esc_attr( $email ); ?>">
										<?php esc_html_e( 'Whitelist', 'smart-form-shield' ); ?>
									</button>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
			<tfoot>
				<tr>
					<td class="manage-column column-cb check-column">
						<label class="screen-reader-text" for="cb-select-all-2"><?php esc_html_e( 'Select All', 'smart-form-shield' ); ?></label>
						<input id="cb-select-all-2" type="checkbox">
					</td>
					<th scope="col" class="manage-column"><?php esc_html_e( 'Date', 'smart-form-shield' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'Form', 'smart-form-shield' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'Submission', 'smart-form-shield' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'Spam Score', 'smart-form-shield' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'Provider', 'smart-form-shield' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'Status', 'smart-form-shield' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'Actions', 'smart-form-shield' ); ?></th>
				</tr>
			</tfoot>
		</table>
		
		<div class="tablenav bottom">
			<div class="alignleft actions bulkactions">
				<label for="bulk-action-selector-bottom" class="screen-reader-text"><?php esc_html_e( 'Select bulk action', 'smart-form-shield' ); ?></label>
				<select name="action2" id="bulk-action-selector-bottom">
					<option value="-1"><?php esc_html_e( 'Bulk actions', 'smart-form-shield' ); ?></option>
					<option value="approve"><?php esc_html_e( 'Mark as Approved', 'smart-form-shield' ); ?></option>
					<option value="spam"><?php esc_html_e( 'Mark as Spam', 'smart-form-shield' ); ?></option>
					<option value="delete"><?php esc_html_e( 'Delete', 'smart-form-shield' ); ?></option>
				</select>
				<input type="submit" id="doaction2" class="button action" value="<?php esc_attr_e( 'Apply', 'smart-form-shield' ); ?>">
			</div>
			
			<?php if ( $total_pages > 1 ) : ?>
			<div class="tablenav-pages">
				<span class="displaying-num"><?php printf( esc_html__( '%s items', 'smart-form-shield' ), $total_items ); ?></span>
				<?php
				echo paginate_links( array(
					'base' => add_query_arg( 'paged', '%#%' ),
					'format' => '',
					'prev_text' => __( '&laquo;' ),
					'next_text' => __( '&raquo;' ),
					'total' => $total_pages,
					'current' => $paged,
				) );
				?>
			</div>
			<?php endif; ?>
		</div>
	</form>
</div>

<!-- View Submission Modal -->
<div id="sfs-view-modal" class="sfs-modal">
	<div class="sfs-modal-content">
		<span class="sfs-modal-close">&times;</span>
		<h2><?php esc_html_e( 'Submission Details', 'smart-form-shield' ); ?></h2>
		<div id="sfs-modal-body">
			<!-- Content loaded via AJAX -->
		</div>
	</div>
</div>