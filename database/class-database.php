<?php
/**
 * Database operations handler.
 *
 * @since      1.0.0
 * @package    Spam_Slayer_5000
 * @subpackage Spam_Slayer_5000/database
 */

class Spam_Slayer_5000_Database {

	/**
	 * Insert a new submission record.
	 *
	 * @since    1.0.0
	 * @param    array    $data    Submission data.
	 * @return   int|false         Insert ID or false on failure.
	 */
	public function insert_submission( $data ) {
		global $wpdb;

		$defaults = array(
			'form_type' => '',
			'form_id' => '',
			'submission_data' => '',
			'spam_score' => 0,
			'provider_used' => null,
			'provider_response' => null,
			'status' => 'pending',
			'ip_address' => $this->get_user_ip(),
			'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) : '',
		);

		$data = wp_parse_args( $data, $defaults );

		// Serialize submission data if array
		if ( is_array( $data['submission_data'] ) ) {
			$data['submission_data'] = wp_json_encode( $data['submission_data'] );
		}

		if ( is_array( $data['provider_response'] ) ) {
			$data['provider_response'] = wp_json_encode( $data['provider_response'] );
		}

		$result = $wpdb->insert(
			SPAM_SLAYER_5000_SUBMISSIONS_TABLE,
			$data,
			array( '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s' )
		);

		return $result !== false ? $wpdb->insert_id : false;
	}

	/**
	 * Update submission status.
	 *
	 * @since    1.0.0
	 * @param    int      $submission_id    Submission ID.
	 * @param    string   $status          New status.
	 * @return   bool                      Success or failure.
	 */
	public function update_submission_status( $submission_id, $status ) {
		global $wpdb;

		$valid_statuses = array( 'pending', 'approved', 'spam', 'whitelist' );
		if ( ! in_array( $status, $valid_statuses, true ) ) {
			return false;
		}

		$result = $wpdb->update(
			SPAM_SLAYER_5000_SUBMISSIONS_TABLE,
			array( 'status' => $status ),
			array( 'id' => $submission_id ),
			array( '%s' ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Get submissions with filters.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Query arguments.
	 * @return   array             Submissions data.
	 */
	public function get_submissions( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'status' => '',
			'form_type' => '',
			'form_id' => '',
			'date_from' => '',
			'date_to' => '',
			'search' => '',
			'orderby' => 'created_at',
			'order' => 'DESC',
			'limit' => 20,
			'offset' => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		$where_clauses = array( '1=1' );
		$where_values = array();

		if ( ! empty( $args['status'] ) ) {
			$where_clauses[] = 'status = %s';
			$where_values[] = $args['status'];
		}

		if ( ! empty( $args['form_type'] ) ) {
			$where_clauses[] = 'form_type = %s';
			$where_values[] = $args['form_type'];
		}

		if ( ! empty( $args['form_id'] ) ) {
			$where_clauses[] = 'form_id = %s';
			$where_values[] = $args['form_id'];
		}

		if ( ! empty( $args['date_from'] ) ) {
			$where_clauses[] = 'created_at >= %s';
			$where_values[] = $args['date_from'];
		}

		if ( ! empty( $args['date_to'] ) ) {
			$where_clauses[] = 'created_at <= %s';
			$where_values[] = $args['date_to'];
		}

		if ( ! empty( $args['search'] ) ) {
			$where_clauses[] = 'submission_data LIKE %s';
			$where_values[] = '%' . $wpdb->esc_like( $args['search'] ) . '%';
		}

		$where_sql = implode( ' AND ', $where_clauses );

		// Build the query with proper table name escaping
		$table_name = SPAM_SLAYER_5000_SUBMISSIONS_TABLE;
		$sql = "SELECT * FROM `{$table_name}` WHERE $where_sql";
		
		if ( ! empty( $where_values ) ) {
			$sql = $wpdb->prepare( $sql, $where_values );
		}

		// Add ORDER BY
		$allowed_orderby = array( 'id', 'created_at', 'spam_score', 'status' );
		$orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
		$order = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';
		$sql .= " ORDER BY $orderby $order";

		// Add LIMIT
		$sql .= $wpdb->prepare( " LIMIT %d OFFSET %d", $args['limit'], $args['offset'] );

		$results = $wpdb->get_results( $sql, ARRAY_A );

		// Decode JSON fields
		foreach ( $results as &$result ) {
			$result['submission_data'] = json_decode( $result['submission_data'], true );
			$result['provider_response'] = json_decode( $result['provider_response'], true );
		}

		return $results;
	}

	/**
	 * Get submission count.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Query arguments.
	 * @return   int               Total count.
	 */
	public function get_submissions_count( $args = array() ) {
		global $wpdb;

		$where_clauses = array( '1=1' );
		$where_values = array();

		if ( ! empty( $args['status'] ) ) {
			$where_clauses[] = 'status = %s';
			$where_values[] = $args['status'];
		}

		if ( ! empty( $args['form_type'] ) ) {
			$where_clauses[] = 'form_type = %s';
			$where_values[] = $args['form_type'];
		}

		if ( ! empty( $args['date_from'] ) ) {
			$where_clauses[] = 'created_at >= %s';
			$where_values[] = $args['date_from'];
		}

		if ( ! empty( $args['date_to'] ) ) {
			$where_clauses[] = 'created_at <= %s';
			$where_values[] = $args['date_to'];
		}

		$where_sql = implode( ' AND ', $where_clauses );
		$sql = "SELECT COUNT(*) FROM " . SPAM_SLAYER_5000_SUBMISSIONS_TABLE . " WHERE $where_sql";

		if ( ! empty( $where_values ) ) {
			$sql = $wpdb->prepare( $sql, $where_values );
		}

		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Log API call.
	 *
	 * @since    1.0.0
	 * @param    array    $data    API log data.
	 * @return   int|false         Insert ID or false on failure.
	 */
	public function log_api_call( $data ) {
		global $wpdb;

		$defaults = array(
			'provider' => '',
			'model' => '',
			'request_data' => '',
			'response_data' => '',
			'tokens_used' => 0,
			'cost' => 0.000000,
			'response_time' => 0,
			'status' => 'success',
			'error_message' => null,
		);

		$data = wp_parse_args( $data, $defaults );

		// Serialize data if array
		if ( is_array( $data['request_data'] ) ) {
			$data['request_data'] = wp_json_encode( $data['request_data'] );
		}

		if ( is_array( $data['response_data'] ) ) {
			$data['response_data'] = wp_json_encode( $data['response_data'] );
		}

		$result = $wpdb->insert(
			SPAM_SLAYER_5000_API_LOGS_TABLE,
			$data,
			array( '%s', '%s', '%s', '%s', '%d', '%f', '%f', '%s', '%s' )
		);

		return $result !== false ? $wpdb->insert_id : false;
	}

	/**
	 * Get API usage statistics.
	 *
	 * @since    1.0.0
	 * @param    string   $period    Period (day, week, month).
	 * @return   array               Usage statistics.
	 */
	public function get_api_usage_stats( $period = 'day' ) {
		global $wpdb;

		$date_format = '%Y-%m-%d';
		$interval = '1 DAY';

		switch ( $period ) {
			case 'week':
				$interval = '7 DAY';
				break;
			case 'month':
				$interval = '30 DAY';
				break;
		}

		$sql = $wpdb->prepare(
			"SELECT 
				provider,
				COUNT(*) as total_calls,
				SUM(tokens_used) as total_tokens,
				SUM(cost) as total_cost,
				AVG(response_time) as avg_response_time,
				SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_count,
				SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as error_count
			FROM " . SPAM_SLAYER_5000_API_LOGS_TABLE . "
			WHERE created_at >= DATE_SUB(NOW(), INTERVAL %s)
			GROUP BY provider",
			$interval
		);

		return $wpdb->get_results( $sql, ARRAY_A );
	}

	/**
	 * Check if email is whitelisted.
	 *
	 * @since    1.0.0
	 * @param    string   $email    Email address.
	 * @return   bool               True if whitelisted.
	 */
	public function is_whitelisted( $email ) {
		global $wpdb;

		$email = sanitize_email( $email );
		$domain = substr( strrchr( $email, '@' ), 1 );

		$sql = $wpdb->prepare(
			"SELECT COUNT(*) FROM " . SPAM_SLAYER_5000_WHITELIST_TABLE . "
			WHERE (email = %s OR domain = %s) AND is_active = 1",
			$email,
			$domain
		);

		return (int) $wpdb->get_var( $sql ) > 0;
	}

	/**
	 * Add email to whitelist.
	 *
	 * @since    1.0.0
	 * @param    string   $email     Email address.
	 * @param    string   $reason    Reason for whitelisting.
	 * @return   int|false          Insert ID or false on failure.
	 */
	public function add_to_whitelist( $email, $reason = '' ) {
		global $wpdb;

		$email = sanitize_email( $email );
		$domain = substr( strrchr( $email, '@' ), 1 );

		$data = array(
			'email' => $email,
			'domain' => $domain,
			'reason' => sanitize_textarea_field( $reason ),
			'added_by' => get_current_user_id(),
		);

		$result = $wpdb->insert(
			SPAM_SLAYER_5000_WHITELIST_TABLE,
			$data,
			array( '%s', '%s', '%s', '%d' )
		);

		return $result !== false ? $wpdb->insert_id : false;
	}

	/**
	 * Remove from whitelist.
	 *
	 * @since    1.0.0
	 * @param    int      $id    Whitelist entry ID.
	 * @return   bool            Success or failure.
	 */
	public function remove_from_whitelist( $id ) {
		global $wpdb;

		$result = $wpdb->update(
			SPAM_SLAYER_5000_WHITELIST_TABLE,
			array( 'is_active' => 0 ),
			array( 'id' => $id ),
			array( '%d' ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Clean up old submissions.
	 *
	 * @since    1.0.0
	 * @param    int      $days    Days to retain.
	 * @return   int               Number of deleted rows.
	 */
	public function cleanup_old_submissions( $days ) {
		global $wpdb;

		$sql = $wpdb->prepare(
			"DELETE FROM " . SPAM_SLAYER_5000_SUBMISSIONS_TABLE . "
			WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
			$days
		);

		return $wpdb->query( $sql );
	}

	/**
	 * Clean up old API logs.
	 *
	 * @since    1.0.0
	 * @param    int      $days    Days to retain.
	 * @return   int               Number of deleted rows.
	 */
	public function cleanup_old_api_logs( $days ) {
		global $wpdb;

		$sql = $wpdb->prepare(
			"DELETE FROM " . SPAM_SLAYER_5000_API_LOGS_TABLE . "
			WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
			$days
		);

		return $wpdb->query( $sql );
	}

	/**
	 * Get user IP address.
	 *
	 * @since    1.0.0
	 * @return   string    IP address.
	 */
	private function get_user_ip() {
		$ip = '';

		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( $_SERVER['HTTP_CLIENT_IP'] );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field( $_SERVER['HTTP_X_FORWARDED_FOR'] );
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( $_SERVER['REMOTE_ADDR'] );
		}

		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
	}

	/**
	 * Get table name with proper prefix.
	 *
	 * @since    1.0.0
	 * @param    string    $table    Table name (submissions, api_logs, whitelist).
	 * @return   string              Full table name with prefix.
	 */
	public static function get_table_name( $table ) {
		global $wpdb;
		
		switch ( $table ) {
			case 'submissions':
				return defined( 'SPAM_SLAYER_5000_SUBMISSIONS_TABLE' ) 
					? SPAM_SLAYER_5000_SUBMISSIONS_TABLE 
					: $wpdb->prefix . 'ss5k_submissions';
				
			case 'api_logs':
				return defined( 'SPAM_SLAYER_5000_API_LOGS_TABLE' ) 
					? SPAM_SLAYER_5000_API_LOGS_TABLE 
					: $wpdb->prefix . 'ss5k_api_logs';
				
			case 'whitelist':
				return defined( 'SPAM_SLAYER_5000_WHITELIST_TABLE' ) 
					? SPAM_SLAYER_5000_WHITELIST_TABLE 
					: $wpdb->prefix . 'ss5k_whitelist';
				
			default:
				return $wpdb->prefix . 'ss5k_' . $table;
		}
	}
}