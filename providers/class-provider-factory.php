<?php
/**
 * AI Provider Factory.
 *
 * @since      1.0.0
 * @package    Spam_Slayer_5000
 * @subpackage Spam_Slayer_5000/providers
 */

class Spam_Slayer_5000_Provider_Factory {

	/**
	 * Create provider instance.
	 *
	 * @since    1.0.0
	 * @param    string   $provider    Provider name.
	 * @return   Spam_Slayer_5000_Provider_Interface|null    Provider instance or null.
	 */
	public static function create( $provider ) {
		switch ( $provider ) {
			case 'openai':
				return new Spam_Slayer_5000_OpenAI_Provider();
			
			case 'claude':
				return new Spam_Slayer_5000_Claude_Provider();
			
			default:
				return null;
		}
	}

	/**
	 * Get available providers.
	 *
	 * @since    1.0.0
	 * @return   array    Available providers.
	 */
	public static function get_available_providers() {
		$providers = array();
		
		$available = array( 'openai', 'claude' );
		
		foreach ( $available as $provider_name ) {
			$provider = self::create( $provider_name );
			if ( $provider && $provider->is_available() ) {
				$providers[ $provider_name ] = $provider;
			}
		}
		
		return $providers;
	}

	/**
	 * Get primary provider.
	 *
	 * @since    1.0.0
	 * @return   Spam_Slayer_5000_Provider_Interface|null    Provider instance or null.
	 */
	public static function get_primary_provider() {
		$primary = get_option( 'spam_slayer_5000_primary_provider', 'openai' );
		$provider = self::create( $primary );
		
		if ( $provider && $provider->is_available() ) {
			return $provider;
		}
		
		// Try fallback
		return self::get_fallback_provider();
	}

	/**
	 * Get fallback provider.
	 *
	 * @since    1.0.0
	 * @return   Spam_Slayer_5000_Provider_Interface|null    Provider instance or null.
	 */
	public static function get_fallback_provider() {
		$fallback = get_option( 'spam_slayer_5000_fallback_provider', 'claude' );
		$provider = self::create( $fallback );
		
		if ( $provider && $provider->is_available() ) {
			return $provider;
		}
		
		// Try any available provider
		$available = self::get_available_providers();
		return ! empty( $available ) ? reset( $available ) : null;
	}
}