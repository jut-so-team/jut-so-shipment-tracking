<?php
/**
 * Helper functions for jut-so Shipment Tracking
 *
 * @package JUTSO_Shipment_Tracking
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Helper class containing shared utility methods
 */
class JUTSO_Helpers {

	/**
	 * Cached carriers array
	 *
	 * @var array|null
	 */
	private static $carriers_cache = null;

	/**
	 * Get configured carriers
	 *
	 * @return array Array of carriers with their configuration
	 */
	public static function get_carriers(): array {
		if ( null === self::$carriers_cache ) {
			if ( ! class_exists( 'JUTSO_Settings' ) ) {
				require_once JUTSO_ST_PLUGIN_DIR . 'includes/class-jutso-settings.php';
			}
			self::$carriers_cache = JUTSO_Settings::get_instance()->get_carriers();
		}
		return self::$carriers_cache;
	}

	/**
	 * Clear carriers cache
	 */
	public static function clear_carriers_cache(): void {
		self::$carriers_cache = null;
	}

	/**
	 * Get tracking URL for a single tracking number
	 *
	 * @param string $tracking_number The tracking number
	 * @param string $carrier The carrier key
	 * @return string The tracking URL or empty string if not available
	 */
	public static function get_tracking_url( string $tracking_number, string $carrier = '' ): string {
		if ( empty( $tracking_number ) ) {
			return '';
		}

		$carriers = self::get_carriers();
		
		if ( isset( $carriers[ $carrier ] ) && ! empty( $carriers[ $carrier ]['url'] ) ) {
			return str_replace( '{tracking_number}', $tracking_number, $carriers[ $carrier ]['url'] );
		}

		return '';
	}

	/**
	 * Get tracking URLs for multiple tracking numbers
	 *
	 * @param \WC_Order $order The order object
	 * @return array Array of tracking URLs keyed by tracking number
	 */
	public static function get_tracking_urls( \WC_Order $order ): array {
		$tracking_number = $order->get_meta( '_jutso_tracking_number' );
		$carrier = $order->get_meta( '_jutso_tracking_carrier' );
		
		if ( ! $tracking_number ) {
			return array();
		}

		$tracking_urls = array();
		$tracking_numbers = self::parse_tracking_numbers( $tracking_number );
		
		foreach ( $tracking_numbers as $single_tracking_number ) {
			$url = self::get_tracking_url( $single_tracking_number, $carrier );
			if ( $url ) {
				$tracking_urls[ $single_tracking_number ] = $url;
			}
		}

		return $tracking_urls;
	}

	/**
	 * Get carrier name by key
	 *
	 * @param string $carrier The carrier key
	 * @return string The carrier name or the key if not found
	 */
	public static function get_carrier_name( string $carrier ): string {
		$carriers = self::get_carriers();
		return isset( $carriers[ $carrier ] ) ? $carriers[ $carrier ]['name'] : $carrier;
	}

	/**
	 * Parse tracking numbers from comma-separated string
	 *
	 * @param string $tracking_numbers Comma-separated tracking numbers
	 * @return array Array of cleaned tracking numbers
	 */
	public static function parse_tracking_numbers( string $tracking_numbers ): array {
		$numbers = array_map( 'trim', explode( ',', $tracking_numbers ) );
		return array_filter( $numbers ); // Remove empty values
	}

	/**
	 * Format tracking numbers for storage
	 *
	 * @param string $tracking_numbers Raw tracking numbers input
	 * @return string Formatted tracking numbers
	 */
	public static function format_tracking_numbers( string $tracking_numbers ): string {
		$numbers = self::parse_tracking_numbers( $tracking_numbers );
		return implode( ', ', $numbers );
	}

	/**
	 * Validate carrier exists
	 *
	 * @param string $carrier The carrier key to validate
	 * @return bool True if carrier exists or is empty, false otherwise
	 */
	public static function validate_carrier( string $carrier ): bool {
		if ( empty( $carrier ) ) {
			return true; // Empty carrier is allowed
		}
		
		$carriers = self::get_carriers();
		return isset( $carriers[ $carrier ] );
	}

	/**
	 * Get tracking note text based on number of tracking numbers
	 *
	 * @param int $count Number of tracking numbers
	 * @param string $context Context for the note (admin, api, etc.)
	 * @return string The appropriate note text
	 */
	public static function get_tracking_note_text( int $count, string $context = 'admin' ): string {
		if ( $context === 'api' ) {
			return $count > 1 
				? __( 'Tracking numbers added via API: %s (Carrier: %s)', 'jut-so-shipment-tracking' )
				: __( 'Tracking number added via API: %s (Carrier: %s)', 'jut-so-shipment-tracking' );
		}
		
		return $count > 1 
			? __( 'Tracking numbers added: %s (Carrier: %s)', 'jut-so-shipment-tracking' )
			: __( 'Tracking number added: %s (Carrier: %s)', 'jut-so-shipment-tracking' );
	}
}