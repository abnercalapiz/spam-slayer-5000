<?php
/**
 * REST API endpoints.
 *
 * @since      1.0.0
 * @package    Spam_Slayer_5000
 * @subpackage Spam_Slayer_5000/includes
 */

class Spam_Slayer_5000_REST_API {

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
	 * Register REST API routes.
	 *
	 * @since    1.0.0
	 */
	public function register_routes() {
		$namespace = 'spam-slayer-5000/v1';

		// Submissions endpoints
		register_rest_route( $namespace, '/submissions', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_submissions' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => $this->get_submissions_params(),
			),
		) );

		register_rest_route( $namespace, '/submissions/(?P<id>\d+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_submission' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => array(
					'id' => array(
						'validate_callback' => function( $param ) {
							return is_numeric( $param );
						}
					),
				),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_submission' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => array(
					'id' => array(
						'validate_callback' => function( $param ) {
							return is_numeric( $param );
						}
					),
					'status' => array(
						'required' => true,
						'validate_callback' => function( $param ) {
							return in_array( $param, array( 'pending', 'approved', 'spam', 'whitelist' ), true );
						}
					),
				),
			),
		) );

		// Validation endpoint
		register_rest_route( $namespace, '/validate', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'validate_submission' ),
			'permission_callback' => array( $this, 'check_api_key' ),
			'args'                => array(
				'data' => array(
					'required' => true,
					'validate_callback' => function( $param ) {
						return is_array( $param );
					}
				),
				'form_type' => array(
					'default' => 'api',
				),
				'form_id' => array(
					'default' => '',
				),
			),
		) );

		// Analytics endpoints
		register_rest_route( $namespace, '/analytics', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_analytics' ),
			'permission_callback' => array( $this, 'check_permissions' ),
			'args'                => array(
				'period' => array(
					'default' => 'week',
					'validate_callback' => function( $param ) {
						return in_array( $param, array( 'day', 'week', 'month' ), true );
					}
				),
			),
		) );

		// Whitelist endpoints
		register_rest_route( $namespace, '/whitelist', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_whitelist' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'add_to_whitelist' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => array(
					'email' => array(
						'required' => true,
						'validate_callback' => function( $param ) {
							return is_email( $param );
						}
					),
					'reason' => array(
						'default' => '',
					),
				),
			),
		) );

		// Provider status endpoint
		register_rest_route( $namespace, '/providers', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_providers_status' ),
			'permission_callback' => array( $this, 'check_permissions' ),
		) );
	}

	/**
	 * Get submissions.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    Request object.
	 * @return   WP_REST_Response              Response object.
	 */
	public function get_submissions( $request ) {
		$params = $request->get_params();
		$database = new Spam_Slayer_5000_Database();
		
		$args = array(
			'status'    => $params['status'],
			'form_type' => $params['form_type'],
			'form_id'   => $params['form_id'],
			'date_from' => $params['date_from'],
			'date_to'   => $params['date_to'],
			'search'    => $params['search'],
			'orderby'   => $params['orderby'],
			'order'     => $params['order'],
			'limit'     => $params['per_page'],
			'offset'    => ( $params['page'] - 1 ) * $params['per_page'],
		);

		$submissions = $database->get_submissions( $args );
		$total = $database->get_submissions_count( $args );

		$response = new WP_REST_Response( $submissions );
		$response->header( 'X-WP-Total', $total );
		$response->header( 'X-WP-TotalPages', ceil( $total / $params['per_page'] ) );

		return $response;
	}

	/**
	 * Get single submission.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    Request object.
	 * @return   WP_REST_Response              Response object.
	 */
	public function get_submission( $request ) {
		$id = $request->get_param( 'id' );
		$database = new Spam_Slayer_5000_Database();
		
		$submissions = $database->get_submissions( array(
			'id' => $id,
			'limit' => 1,
		) );

		if ( empty( $submissions ) ) {
			return new WP_Error( 'not_found', __( 'Submission not found', 'spam-slayer-5000' ), array( 'status' => 404 ) );
		}

		return new WP_REST_Response( $submissions[0] );
	}

	/**
	 * Update submission.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    Request object.
	 * @return   WP_REST_Response              Response object.
	 */
	public function update_submission( $request ) {
		$id = $request->get_param( 'id' );
		$status = $request->get_param( 'status' );
		
		$database = new Spam_Slayer_5000_Database();
		$result = $database->update_submission_status( $id, $status );

		if ( ! $result ) {
			return new WP_Error( 'update_failed', __( 'Failed to update submission', 'spam-slayer-5000' ), array( 'status' => 500 ) );
		}

		return new WP_REST_Response( array( 'success' => true ) );
	}

	/**
	 * Validate submission via API.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    Request object.
	 * @return   WP_REST_Response              Response object.
	 */
	public function validate_submission( $request ) {
		$data = $request->get_param( 'data' );
		$form_type = $request->get_param( 'form_type' );
		$form_id = $request->get_param( 'form_id' );

		$result = Spam_Slayer_5000_Validator::validate_submission( $data );

		// Log submission
		$database = new Spam_Slayer_5000_Database();
		$database->insert_submission( array(
			'form_type' => $form_type,
			'form_id' => $form_id,
			'submission_data' => $data,
			'spam_score' => $result['spam_score'],
			'provider_used' => isset( $result['provider'] ) ? $result['provider'] : null,
			'provider_response' => $result,
			'status' => $result['is_spam'] ? 'spam' : 'approved',
		) );

		return new WP_REST_Response( array(
			'is_spam' => $result['is_spam'],
			'spam_score' => $result['spam_score'],
			'reason' => isset( $result['reason'] ) ? $result['reason'] : '',
		) );
	}

	/**
	 * Get analytics data.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    Request object.
	 * @return   WP_REST_Response              Response object.
	 */
	public function get_analytics( $request ) {
		$period = $request->get_param( 'period' );
		
		$analytics = new Spam_Slayer_5000_Admin_Analytics( $this->plugin_name, $this->version );
		$data = $analytics->get_analytics_data( $period );

		return new WP_REST_Response( $data );
	}

	/**
	 * Get whitelist entries.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    Request object.
	 * @return   WP_REST_Response              Response object.
	 */
	public function get_whitelist( $request ) {
		global $wpdb;
		
		$results = $wpdb->get_results(
			"SELECT * FROM " . SPAM_SLAYER_5000_WHITELIST_TABLE . " 
			WHERE is_active = 1 
			ORDER BY created_at DESC",
			ARRAY_A
		);

		return new WP_REST_Response( $results );
	}

	/**
	 * Add email to whitelist.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    Request object.
	 * @return   WP_REST_Response              Response object.
	 */
	public function add_to_whitelist( $request ) {
		$email = $request->get_param( 'email' );
		$reason = $request->get_param( 'reason' );
		
		$database = new Spam_Slayer_5000_Database();
		$result = $database->add_to_whitelist( $email, $reason );

		if ( ! $result ) {
			return new WP_Error( 'add_failed', __( 'Failed to add to whitelist', 'spam-slayer-5000' ), array( 'status' => 500 ) );
		}

		return new WP_REST_Response( array( 'success' => true, 'id' => $result ) );
	}

	/**
	 * Get providers status.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    Request object.
	 * @return   WP_REST_Response              Response object.
	 */
	public function get_providers_status( $request ) {
		$providers = Spam_Slayer_5000_Provider_Factory::get_available_providers();
		$status = array();

		foreach ( $providers as $key => $provider ) {
			$status[ $key ] = array(
				'name' => $provider->get_name(),
				'available' => true,
				'models' => $provider->get_models(),
				'current_model' => $provider->get_current_model(),
			);
		}

		return new WP_REST_Response( $status );
	}

	/**
	 * Check permissions for authenticated endpoints.
	 *
	 * @since    1.0.0
	 * @return   bool    True if authorized.
	 */
	public function check_permissions() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Check API key for public endpoints.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    Request object.
	 * @return   bool                          True if authorized.
	 */
	public function check_api_key( $request ) {
		$api_key = $request->get_header( 'X-SFS-API-Key' );
		
		if ( empty( $api_key ) ) {
			return new WP_Error( 'missing_api_key', __( 'API key required', 'spam-slayer-5000' ), array( 'status' => 401 ) );
		}

		$stored_key = get_option( 'spam_slayer_5000_api_key' );
		
		if ( empty( $stored_key ) ) {
			// Generate API key if not exists
			$stored_key = wp_generate_password( 32, false );
			update_option( 'spam_slayer_5000_api_key', $stored_key );
		}

		return hash_equals( $stored_key, $api_key );
	}

	/**
	 * Get submission parameters.
	 *
	 * @since    1.0.0
	 * @return   array    Parameters.
	 */
	private function get_submissions_params() {
		return array(
			'status' => array(
				'default' => '',
				'validate_callback' => function( $param ) {
					return empty( $param ) || in_array( $param, array( 'pending', 'approved', 'spam', 'whitelist' ), true );
				}
			),
			'form_type' => array(
				'default' => '',
			),
			'form_id' => array(
				'default' => '',
			),
			'date_from' => array(
				'default' => '',
			),
			'date_to' => array(
				'default' => '',
			),
			'search' => array(
				'default' => '',
			),
			'orderby' => array(
				'default' => 'created_at',
				'validate_callback' => function( $param ) {
					return in_array( $param, array( 'id', 'created_at', 'spam_score', 'status' ), true );
				}
			),
			'order' => array(
				'default' => 'DESC',
				'validate_callback' => function( $param ) {
					return in_array( strtoupper( $param ), array( 'ASC', 'DESC' ), true );
				}
			),
			'page' => array(
				'default' => 1,
				'validate_callback' => function( $param ) {
					return is_numeric( $param ) && $param > 0;
				}
			),
			'per_page' => array(
				'default' => 20,
				'validate_callback' => function( $param ) {
					return is_numeric( $param ) && $param > 0 && $param <= 100;
				}
			),
		);
	}
}