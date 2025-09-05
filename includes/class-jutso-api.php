<?php

defined( 'ABSPATH' ) || exit;

class JUTSO_API {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		register_rest_route(
			'jutso-tracking/v1',
			'/orders/(?P<order_id>\d+)/tracking',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_tracking' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'order_id' => array(
							'required'          => true,
							'validate_callback' => array( $this, 'validate_order_id' ),
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_tracking' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'order_id' => array(
							'required'          => true,
							'validate_callback' => array( $this, 'validate_order_id' ),
						),
						'tracking_number' => array(
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'carrier' => array(
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_tracking' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'order_id' => array(
							'required'          => true,
							'validate_callback' => array( $this, 'validate_order_id' ),
						),
					),
				),
			)
		);

		register_rest_route(
			'jutso-tracking/v1',
			'/orders/batch',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'batch_update_tracking' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'orders' => array(
						'required'          => true,
						'validate_callback' => array( $this, 'validate_batch_orders' ),
					),
				),
			)
		);
	}

	public function get_tracking( $request ) {
		$order_id = $request->get_param( 'order_id' );
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return new WP_Error( 'order_not_found', __( 'Order not found', 'jut-so-shipment-tracking' ), array( 'status' => 404 ) );
		}

		$tracking_number = $order->get_meta( '_jutso_tracking_number' );
		$tracking_carrier = $order->get_meta( '_jutso_tracking_carrier' );
		
		// Handle multiple tracking numbers
		$tracking_urls = JUTSO_Helpers::get_tracking_urls( $order );
		
		$tracking_data = array(
			'order_id'        => $order_id,
			'tracking_number' => $tracking_number,
			'carrier'         => $tracking_carrier,
			'date_added'      => $order->get_meta( '_jutso_tracking_date' ),
			'tracking_urls'   => $tracking_urls,
		);

		return rest_ensure_response( $tracking_data );
	}

	public function update_tracking( $request ) {
		$order_id = $request->get_param( 'order_id' );
		$tracking_number = $request->get_param( 'tracking_number' );
		$carrier = $request->get_param( 'carrier' );

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return new WP_Error( 'order_not_found', __( 'Order not found', 'jut-so-shipment-tracking' ), array( 'status' => 404 ) );
		}

		// Use default carrier if none specified
		if ( empty( $carrier ) ) {
			$carrier = get_option( 'jutso_st_default_carrier', '' );
		}

		// Clean up tracking numbers
		if ( $tracking_number ) {
			$tracking_number = JUTSO_Helpers::format_tracking_numbers( $tracking_number );
			$tracking_numbers_array = JUTSO_Helpers::parse_tracking_numbers( $tracking_number );
		}

		// Validate carrier
		if ( ! JUTSO_Helpers::validate_carrier( $carrier ) ) {
			$carriers = JUTSO_Helpers::get_carriers();
			return new WP_Error( 
				'invalid_carrier', 
				sprintf( __( 'Invalid carrier: %s. Valid carriers are: %s', 'jut-so-shipment-tracking' ), 
					$carrier, 
					implode( ', ', array_keys( $carriers ) )
				), 
				array( 'status' => 400 ) 
			);
		}

		$order->update_meta_data( '_jutso_tracking_number', $tracking_number );
		$order->update_meta_data( '_jutso_tracking_carrier', $carrier );
		$order->update_meta_data( '_jutso_tracking_date', current_time( 'mysql' ) );
		$order->save();

		$carrier_name = JUTSO_Helpers::get_carrier_name( $carrier );
		if ( empty( $carrier_name ) || $carrier_name === $carrier ) {
			$carrier_name = __( 'Not specified', 'jut-so-shipment-tracking' );
		}

		// Get appropriate note text
		$tracking_count = isset( $tracking_numbers_array ) ? count( $tracking_numbers_array ) : 1;
		$note_text = JUTSO_Helpers::get_tracking_note_text( $tracking_count, 'api' );

		$order->add_order_note(
			sprintf(
				$note_text,
				$tracking_number,
				$carrier_name
			)
		);

		// Get tracking URLs for response
		$tracking_urls = JUTSO_Helpers::get_tracking_urls( $order );
		
		return rest_ensure_response( array(
			'success' => true,
			'message' => __( 'Tracking information updated successfully', 'jut-so-shipment-tracking' ),
			'data'    => array(
				'order_id'        => $order_id,
				'tracking_number' => $tracking_number,
				'carrier'         => $carrier,
				'tracking_urls'   => $tracking_urls,
			),
		) );
	}

	public function delete_tracking( $request ) {
		$order_id = $request->get_param( 'order_id' );
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return new WP_Error( 'order_not_found', __( 'Order not found', 'jut-so-shipment-tracking' ), array( 'status' => 404 ) );
		}

		$order->delete_meta_data( '_jutso_tracking_number' );
		$order->delete_meta_data( '_jutso_tracking_carrier' );
		$order->delete_meta_data( '_jutso_tracking_date' );
		$order->save();

		$order->add_order_note( __( 'Tracking information removed via API', 'jut-so-shipment-tracking' ) );

		return rest_ensure_response( array(
			'success' => true,
			'message' => __( 'Tracking information deleted successfully', 'jut-so-shipment-tracking' ),
		) );
	}

	public function batch_update_tracking( $request ) {
		$orders = $request->get_param( 'orders' );
		$results = array();
		$errors = array();
		$carriers = JUTSO_Helpers::get_carriers();

		foreach ( $orders as $order_data ) {
			if ( ! isset( $order_data['order_id'] ) || ! isset( $order_data['tracking_number'] ) ) {
				$errors[] = array(
					'order_id' => isset( $order_data['order_id'] ) ? $order_data['order_id'] : 'unknown',
					'error'    => __( 'Missing required fields', 'jut-so-shipment-tracking' ),
				);
				continue;
			}

			$order = wc_get_order( $order_data['order_id'] );
			
			if ( ! $order ) {
				$errors[] = array(
					'order_id' => $order_data['order_id'],
					'error'    => __( 'Order not found', 'jut-so-shipment-tracking' ),
				);
				continue;
			}

			$carrier = isset( $order_data['carrier'] ) ? $order_data['carrier'] : '';
			
			// Use default carrier if none specified
			if ( empty( $carrier ) ) {
				$carrier = get_option( 'jutso_st_default_carrier', '' );
			}

			// Validate carrier exists in configured carriers
			if ( ! empty( $carrier ) && ! isset( $carriers[ $carrier ] ) ) {
				$errors[] = array(
					'order_id' => $order_data['order_id'],
					'error'    => sprintf( 
						__( 'Invalid carrier: %s. Valid carriers are: %s', 'jut-so-shipment-tracking' ), 
						$carrier, 
						implode( ', ', array_keys( $carriers ) )
					),
				);
				continue;
			}

			$order->update_meta_data( '_jutso_tracking_number', $order_data['tracking_number'] );
			$order->update_meta_data( '_jutso_tracking_carrier', $carrier );
			$order->update_meta_data( '_jutso_tracking_date', current_time( 'mysql' ) );
			$order->save();

			// Add order note
			$carrier_name = ! empty( $carrier ) && isset( $carriers[ $carrier ] ) ? $carriers[ $carrier ]['name'] : __( 'Not specified', 'jut-so-shipment-tracking' );
			$order->add_order_note(
				sprintf(
					__( 'Tracking number added via API (batch): %s (Carrier: %s)', 'jut-so-shipment-tracking' ),
					$order_data['tracking_number'],
					$carrier_name
				)
			);

			$results[] = array(
				'order_id'        => $order_data['order_id'],
				'tracking_number' => $order_data['tracking_number'],
				'carrier'         => $carrier,
			);
		}

		return rest_ensure_response( array(
			'success' => count( $results ) > 0,
			'updated' => $results,
			'errors'  => $errors,
			'message' => sprintf(
				__( '%d orders updated, %d errors', 'jut-so-shipment-tracking' ),
				count( $results ),
				count( $errors )
			),
		) );
	}

	public function check_permission( $request ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to access this resource', 'jut-so-shipment-tracking' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	public function validate_order_id( $param, $request, $key ) {
		return is_numeric( $param );
	}

	public function validate_batch_orders( $param, $request, $key ) {
		return is_array( $param ) && count( $param ) > 0;
	}

	// Helper methods removed - now using JUTSO_Helpers class
}
