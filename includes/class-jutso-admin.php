<?php

defined( 'ABSPATH' ) || exit;

class JUTSO_Admin {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_tracking_meta_box' ) );
		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'save_tracking_code' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	public function add_tracking_meta_box() {
		add_meta_box(
			'jutso_shipment_tracking',
			__( 'Shipment Tracking', 'jut-so-shipment-tracking' ),
			array( $this, 'render_tracking_meta_box' ),
			'shop_order',
			'side',
			'default'
		);

		$screen = class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' ) 
			&& wc_get_container()->get( '\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' )->custom_orders_table_usage_is_enabled()
			? wc_get_page_screen_id( 'shop-order' )
			: 'shop_order';

		add_meta_box(
			'jutso_shipment_tracking',
			__( 'Shipment Tracking', 'jut-so-shipment-tracking' ),
			array( $this, 'render_tracking_meta_box' ),
			$screen,
			'side',
			'default'
		);
	}

	public function render_tracking_meta_box( $post_or_order ) {
		$order = ( $post_or_order instanceof WP_Post ) ? wc_get_order( $post_or_order->ID ) : $post_or_order;
		
		if ( ! $order ) {
			return;
		}

		$tracking_number = $order->get_meta( '_jutso_tracking_number' );
		$tracking_carrier = $order->get_meta( '_jutso_tracking_carrier' );
		$tracking_date = $order->get_meta( '_jutso_tracking_date' );
		$carriers = $this->get_carriers();

		wp_nonce_field( 'jutso_save_tracking', 'jutso_tracking_nonce' );
		?>
		<div class="jutso-tracking-fields">
			<p class="form-field">
				<label for="jutso_tracking_carrier"><?php esc_html_e( 'Carrier', 'jut-so-shipment-tracking' ); ?></label>
				<select id="jutso_tracking_carrier" name="jutso_tracking_carrier" class="widefat">
					<option value=""><?php esc_html_e( 'Select carrier...', 'jut-so-shipment-tracking' ); ?></option>
					<?php foreach ( $carriers as $key => $carrier ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $tracking_carrier, $key ); ?>>
							<?php echo esc_html( $carrier['name'] ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>

			<p class="form-field">
				<label for="jutso_tracking_number"><?php esc_html_e( 'Tracking Numbers', 'jut-so-shipment-tracking' ); ?></label>
				<input type="text" id="jutso_tracking_number" name="jutso_tracking_number" value="<?php echo esc_attr( $tracking_number ); ?>" class="widefat" />
				<span class="description"><?php esc_html_e( 'Enter one or more tracking numbers separated by commas', 'jut-so-shipment-tracking' ); ?></span>
			</p>

			<?php if ( $tracking_date ) : ?>
				<p class="form-field">
					<label><?php esc_html_e( 'Date Added', 'jut-so-shipment-tracking' ); ?></label>
					<span><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $tracking_date ) ) ); ?></span>
				</p>
			<?php endif; ?>

			<?php if ( $tracking_number ) : ?>
				<?php
				// Handle multiple tracking numbers
				$tracking_numbers = array_map( 'trim', explode( ',', $tracking_number ) );
				$has_tracking_urls = false;
				foreach ( $tracking_numbers as $single_tracking_number ) {
					if ( ! empty( $single_tracking_number ) ) {
						$tracking_url = $this->get_tracking_url( $single_tracking_number, $tracking_carrier );
						if ( $tracking_url ) {
							$has_tracking_urls = true;
							break;
						}
					}
				}
				
				if ( $has_tracking_urls ) :
				?>
					<p class="form-field">
						<label><?php esc_html_e( 'View Tracking', 'jut-so-shipment-tracking' ); ?></label>
						<?php foreach ( $tracking_numbers as $single_tracking_number ) : 
							if ( ! empty( $single_tracking_number ) ) :
								$tracking_url = $this->get_tracking_url( $single_tracking_number, $tracking_carrier );
								if ( $tracking_url ) :
						?>
							<a href="<?php echo esc_url( $tracking_url ); ?>" target="_blank" class="button" style="margin-right: 5px; margin-bottom: 5px;">
								<?php echo esc_html( $single_tracking_number ); ?>
							</a>
						<?php 
								endif;
							endif;
						endforeach; ?>
					</p>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	public function save_tracking_code( $order_id ) {
		if ( ! isset( $_POST['jutso_tracking_nonce'] ) || ! wp_verify_nonce( $_POST['jutso_tracking_nonce'], 'jutso_save_tracking' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$tracking_number = isset( $_POST['jutso_tracking_number'] ) ? sanitize_text_field( $_POST['jutso_tracking_number'] ) : '';
		$tracking_carrier = isset( $_POST['jutso_tracking_carrier'] ) ? sanitize_text_field( $_POST['jutso_tracking_carrier'] ) : '';

		// Clean up tracking numbers - remove spaces around commas
		if ( $tracking_number ) {
			$tracking_numbers_array = array_map( 'trim', explode( ',', $tracking_number ) );
			$tracking_numbers_array = array_filter( $tracking_numbers_array ); // Remove empty values
			$tracking_number = implode( ', ', $tracking_numbers_array );
		}

		if ( $tracking_number ) {
			// Validate carrier exists in configured carriers
			$carriers = $this->get_carriers();
			if ( ! empty( $tracking_carrier ) && ! isset( $carriers[ $tracking_carrier ] ) ) {
				// Invalid carrier, don't save
				return;
			}

			$order->update_meta_data( '_jutso_tracking_number', $tracking_number );
			$order->update_meta_data( '_jutso_tracking_carrier', $tracking_carrier );
			$order->update_meta_data( '_jutso_tracking_date', current_time( 'mysql' ) );

			$carrier_name = isset( $carriers[ $tracking_carrier ] ) ? $carriers[ $tracking_carrier ]['name'] : __( 'Not specified', 'jut-so-shipment-tracking' );
			
			// Update order note to reflect multiple tracking numbers
			$tracking_count = count( $tracking_numbers_array );
			$note_text = $tracking_count > 1 
				? __( 'Tracking numbers added: %s (Carrier: %s)', 'jut-so-shipment-tracking' )
				: __( 'Tracking number added: %s (Carrier: %s)', 'jut-so-shipment-tracking' );
			
			$order->add_order_note(
				sprintf(
					$note_text,
					$tracking_number,
					$carrier_name
				)
			);
		} else {
			$order->delete_meta_data( '_jutso_tracking_number' );
			$order->delete_meta_data( '_jutso_tracking_carrier' );
			$order->delete_meta_data( '_jutso_tracking_date' );
		}

		$order->save();
	}

	public function enqueue_admin_scripts( $hook ) {
		global $post_type;
		
		if ( 'shop_order' === $post_type || ( isset( $_GET['page'] ) && 'wc-orders' === $_GET['page'] ) ) {
			wp_enqueue_style(
				'jutso-admin-styles',
				JUTSO_ST_PLUGIN_URL . 'assets/css/admin.css',
				array(),
				JUTSO_ST_VERSION
			);
		}
	}

	private function get_carriers() {
		if ( ! class_exists( 'JUTSO_Settings' ) ) {
			require_once JUTSO_ST_PLUGIN_DIR . 'includes/class-jutso-settings.php';
		}
		return JUTSO_Settings::get_instance()->get_carriers();
	}

	private function get_tracking_url( $tracking_number, $carrier = '' ) {
		$carriers = $this->get_carriers();
		
		if ( isset( $carriers[ $carrier ] ) && ! empty( $carriers[ $carrier ]['url'] ) ) {
			return str_replace( '{tracking_number}', $tracking_number, $carriers[ $carrier ]['url'] );
		}

		return '';
	}
}