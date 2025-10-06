<?php
/**
 * Australian data validation class
 *
 * @link       https://jezweb.com.au
 * @since      1.2.0
 *
 * @package    Spam_Slayer_5000
 * @subpackage Spam_Slayer_5000/includes
 */

/**
 * Australian data validation class.
 *
 * This class handles validation of Australian addresses, phone numbers, and business ABNs.
 *
 * @since      1.2.0
 * @package    Spam_Slayer_5000
 * @subpackage Spam_Slayer_5000/includes
 * @author     JezWeb <support@jezweb.com.au>
 */
class Spam_Slayer_5000_Australian_Validator {

	/**
	 * Validate Australian street address components.
	 *
	 * @since    1.2.0
	 * @param    array $address Address components.
	 * @return   array Validation result.
	 */
	public function validate_address( $address ) {
		$result = array(
			'valid' => true,
			'errors' => array(),
		);

		// Validate street address
		if ( ! empty( $address['street'] ) ) {
			if ( strlen( $address['street'] ) < 3 || strlen( $address['street'] ) > 100 ) {
				$result['valid'] = false;
				$result['errors'][] = 'Invalid street address length';
			}
		} else {
			$result['valid'] = false;
			$result['errors'][] = 'Street address is required';
		}

		// Validate suburb
		if ( ! empty( $address['suburb'] ) ) {
			if ( ! preg_match( '/^[a-zA-Z\s\-\']{2,50}$/', $address['suburb'] ) ) {
				$result['valid'] = false;
				$result['errors'][] = 'Invalid suburb format';
			}
		} else {
			$result['valid'] = false;
			$result['errors'][] = 'Suburb is required';
		}

		// Validate state
		if ( ! empty( $address['state'] ) ) {
			$valid_states = array( 'NSW', 'VIC', 'QLD', 'SA', 'WA', 'TAS', 'NT', 'ACT' );
			if ( ! in_array( strtoupper( $address['state'] ), $valid_states ) ) {
				$result['valid'] = false;
				$result['errors'][] = 'Invalid Australian state';
			}
		} else {
			$result['valid'] = false;
			$result['errors'][] = 'State is required';
		}

		// Validate postcode
		if ( ! empty( $address['postcode'] ) ) {
			if ( ! preg_match( '/^(0[289][0-9]{2}|[1-9][0-9]{3})$/', $address['postcode'] ) ) {
				$result['valid'] = false;
				$result['errors'][] = 'Invalid Australian postcode';
			} else {
				// Check postcode matches state
				$postcode_valid = $this->validate_postcode_state( $address['postcode'], $address['state'] );
				if ( ! $postcode_valid ) {
					$result['valid'] = false;
					$result['errors'][] = 'Postcode does not match state';
				}
			}
		} else {
			$result['valid'] = false;
			$result['errors'][] = 'Postcode is required';
		}

		return $result;
	}

	/**
	 * Validate Australian phone number.
	 *
	 * @since    1.2.0
	 * @param    string $phone Phone number.
	 * @return   array Validation result.
	 */
	public function validate_phone( $phone ) {
		$result = array(
			'valid' => true,
			'errors' => array(),
		);

		// Remove spaces, hyphens, and parentheses
		$phone_clean = preg_replace( '/[\s\-\(\)]/', '', $phone );

		// Check for Australian mobile (04xx xxx xxx)
		$mobile_pattern = '/^(\+?61|0)?4\d{8}$/';
		
		// Check for Australian landline (area code + 8 digits)
		$landline_pattern = '/^(\+?61|0)?[2378]\d{8}$/';
		
		// Check for 13/1300/1800 numbers
		$special_pattern = '/^(13\d{4}|1300\d{6}|1800\d{6})$/';

		if ( ! preg_match( $mobile_pattern, $phone_clean ) && 
		     ! preg_match( $landline_pattern, $phone_clean ) && 
		     ! preg_match( $special_pattern, $phone_clean ) ) {
			$result['valid'] = false;
			$result['errors'][] = 'Invalid Australian phone number format';
		}

		return $result;
	}

	/**
	 * Validate ABN using Australian Business Register API.
	 *
	 * @since    1.2.0
	 * @param    string $abn ABN number.
	 * @param    string $business_name Business name to verify.
	 * @return   array Validation result.
	 */
	public function validate_abn( $abn, $business_name = '' ) {
		$result = array(
			'valid' => false,
			'errors' => array(),
			'business_details' => array(),
		);

		// Clean ABN (remove spaces)
		$abn_clean = preg_replace( '/\s/', '', $abn );

		// Basic ABN format validation (11 digits)
		if ( ! preg_match( '/^\d{11}$/', $abn_clean ) ) {
			$result['errors'][] = 'ABN must be 11 digits';
			return $result;
		}

		// ABN checksum validation
		if ( ! $this->validate_abn_checksum( $abn_clean ) ) {
			$result['errors'][] = 'Invalid ABN checksum';
			return $result;
		}

		// Get ABN API key from settings
		$api_key = get_option( 'sfs_abn_api_key' );
		if ( empty( $api_key ) ) {
			$result['errors'][] = 'ABN API key not configured';
			return $result;
		}

		// Query ABN Lookup API
		$api_url = 'https://abr.business.gov.au/json/AbnDetails.aspx';
		$response = wp_remote_get( $api_url . '?' . http_build_query( array(
			'abn' => $abn_clean,
			'guid' => $api_key,
		) ) );

		if ( is_wp_error( $response ) ) {
			$result['errors'][] = 'Failed to connect to ABN Lookup API';
			return $result;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( empty( $data ) || ! empty( $data['Message'] ) ) {
			$result['errors'][] = 'ABN not found or invalid';
			return $result;
		}

		// Check if ABN is active
		if ( ! empty( $data['EntityStatusCode'] ) && $data['EntityStatusCode'] !== 'Active' ) {
			$result['errors'][] = 'ABN is not active';
			return $result;
		}

		// Extract business details
		$result['business_details'] = array(
			'abn' => $data['Abn'] ?? '',
			'entity_name' => $data['EntityName'] ?? '',
			'entity_type' => $data['EntityTypeName'] ?? '',
			'status' => $data['EntityStatusCode'] ?? '',
		);

		// If business name provided, verify it matches
		if ( ! empty( $business_name ) && ! empty( $data['EntityName'] ) ) {
			$similarity = similar_text( 
				strtolower( $business_name ), 
				strtolower( $data['EntityName'] ), 
				$percent 
			);
			
			if ( $percent < 70 ) {
				$result['errors'][] = 'Business name does not match ABN record';
				return $result;
			}
		}

		$result['valid'] = true;
		return $result;
	}

	/**
	 * Validate ABN checksum.
	 *
	 * @since    1.2.0
	 * @param    string $abn ABN number.
	 * @return   bool True if valid.
	 */
	private function validate_abn_checksum( $abn ) {
		$weights = array( 10, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19 );
		$sum = 0;

		// Subtract 1 from first digit
		$abn_array = str_split( $abn );
		$abn_array[0] = (int) $abn_array[0] - 1;

		// Calculate weighted sum
		for ( $i = 0; $i < 11; $i++ ) {
			$sum += (int) $abn_array[ $i ] * $weights[ $i ];
		}

		return ( $sum % 89 ) === 0;
	}

	/**
	 * Validate postcode matches state.
	 *
	 * @since    1.2.0
	 * @param    string $postcode Postcode.
	 * @param    string $state State.
	 * @return   bool True if valid.
	 */
	private function validate_postcode_state( $postcode, $state ) {
		$postcode_ranges = array(
			'NSW' => array( array( 1000, 2599 ), array( 2619, 2899 ), array( 2921, 2999 ) ),
			'ACT' => array( array( 200, 299 ), array( 2600, 2618 ), array( 2900, 2920 ) ),
			'VIC' => array( array( 3000, 3999 ), array( 8000, 8999 ) ),
			'QLD' => array( array( 4000, 4999 ), array( 9000, 9999 ) ),
			'SA'  => array( array( 5000, 5799 ), array( 5800, 5999 ) ),
			'WA'  => array( array( 6000, 6797 ), array( 6800, 6999 ) ),
			'TAS' => array( array( 7000, 7799 ), array( 7800, 7999 ) ),
			'NT'  => array( array( 800, 899 ), array( 900, 999 ) ),
		);

		$state_upper = strtoupper( $state );
		$postcode_int = (int) $postcode;

		if ( ! isset( $postcode_ranges[ $state_upper ] ) ) {
			return false;
		}

		foreach ( $postcode_ranges[ $state_upper ] as $range ) {
			if ( $postcode_int >= $range[0] && $postcode_int <= $range[1] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Validate company name using ABN search.
	 *
	 * @since    1.2.0
	 * @param    string $company_name Company name to search.
	 * @return   array Validation result.
	 */
	public function validate_company_name( $company_name ) {
		$result = array(
			'valid' => false,
			'errors' => array(),
			'business_details' => array(),
		);

		if ( empty( $company_name ) || strlen( trim( $company_name ) ) < 2 ) {
			$result['errors'][] = 'Company name is too short';
			return $result;
		}

		// Get ABN API key from settings
		$api_key = get_option( 'sfs_abn_api_key' );
		if ( empty( $api_key ) ) {
			$result['errors'][] = 'ABN API key not configured';
			return $result;
		}

		// Search for matching business names
		$api_url = 'https://abr.business.gov.au/json/MatchingNames.aspx';
		$response = wp_remote_get( $api_url . '?' . http_build_query( array(
			'name' => $company_name,
			'guid' => $api_key,
			'maxResults' => 10,
		) ) );

		if ( is_wp_error( $response ) ) {
			$result['errors'][] = 'Failed to connect to ABN Lookup API';
			return $result;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( empty( $data ) || ! empty( $data['Message'] ) ) {
			$result['errors'][] = 'Error searching for business name';
			return $result;
		}

		// Check if any names were found
		if ( empty( $data['Names'] ) || count( $data['Names'] ) === 0 ) {
			$result['errors'][] = 'Company name not found in Australian Business Register';
			return $result;
		}

		// Check for exact or very close matches
		$found_match = false;
		$active_matches = 0;
		
		foreach ( $data['Names'] as $business ) {
			// Only consider active businesses
			if ( ! empty( $business['AbnStatus'] ) && strtolower( $business['AbnStatus'] ) === 'active' ) {
				$active_matches++;
				
				// Calculate similarity
				similar_text( 
					strtolower( trim( $company_name ) ), 
					strtolower( trim( $business['Name'] ) ), 
					$percent 
				);
				
				// If we find a very close match (85%+), consider it valid
				if ( $percent >= 85 ) {
					$found_match = true;
					$result['business_details'] = array(
						'abn' => $business['Abn'] ?? '',
						'entity_name' => $business['Name'] ?? '',
						'entity_type' => $business['EntityTypeName'] ?? '',
						'status' => $business['AbnStatus'] ?? '',
						'similarity' => $percent,
					);
					break;
				}
			}
		}

		if ( ! $found_match ) {
			if ( $active_matches === 0 ) {
				$result['errors'][] = 'No active business found with this name';
			} else if ( $active_matches > 5 ) {
				$result['errors'][] = 'Company name is too generic (multiple businesses found)';
			} else {
				$result['errors'][] = 'Company name does not closely match any registered business';
			}
			return $result;
		}

		$result['valid'] = true;
		return $result;
	}

	/**
	 * Perform all validations on form data.
	 *
	 * @since    1.2.0
	 * @param    array $form_data Form submission data.
	 * @return   array Combined validation results.
	 */
	public function validate_all( $form_data ) {
		$results = array(
			'valid' => true,
			'errors' => array(),
			'validations' => array(),
		);

		// Validate address if fields present
		if ( isset( $form_data['street'] ) || isset( $form_data['suburb'] ) || 
		     isset( $form_data['state'] ) || isset( $form_data['postcode'] ) ) {
			
			$address_result = $this->validate_address( array(
				'street' => $form_data['street'] ?? '',
				'suburb' => $form_data['suburb'] ?? '',
				'state' => $form_data['state'] ?? '',
				'postcode' => $form_data['postcode'] ?? '',
			) );
			
			$results['validations']['address'] = $address_result;
			if ( ! $address_result['valid'] ) {
				$results['valid'] = false;
				$results['errors'] = array_merge( $results['errors'], $address_result['errors'] );
			}
		}

		// Validate phone if present
		if ( ! empty( $form_data['phone'] ) ) {
			$phone_result = $this->validate_phone( $form_data['phone'] );
			$results['validations']['phone'] = $phone_result;
			if ( ! $phone_result['valid'] ) {
				$results['valid'] = false;
				$results['errors'] = array_merge( $results['errors'], $phone_result['errors'] );
			}
		}

		// Validate ABN if present
		if ( ! empty( $form_data['abn'] ) ) {
			$business_name = $form_data['business_name'] ?? $form_data['company'] ?? '';
			$abn_result = $this->validate_abn( $form_data['abn'], $business_name );
			$results['validations']['abn'] = $abn_result;
			if ( ! $abn_result['valid'] ) {
				$results['valid'] = false;
				$results['errors'] = array_merge( $results['errors'], $abn_result['errors'] );
			}
		} else {
			// If no ABN provided, validate company name if present
			$company_name = $form_data['business_name'] ?? $form_data['company'] ?? $form_data['business'] ?? '';
			if ( ! empty( $company_name ) ) {
				$company_result = $this->validate_company_name( $company_name );
				$results['validations']['company'] = $company_result;
				if ( ! $company_result['valid'] ) {
					$results['valid'] = false;
					$results['errors'] = array_merge( $results['errors'], $company_result['errors'] );
				}
			}
		}

		return $results;
	}
}