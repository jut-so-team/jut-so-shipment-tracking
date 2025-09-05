<?php

defined( 'ABSPATH' ) || exit;

class JUTSO_Emails {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->init_hooks();
	}

	private function init_hooks() {
		$position = get_option( 'jutso_st_email_position', 'after_order_table' );

		switch ( $position ) {
			case 'before_order_table':
				add_action( 'woocommerce_email_before_order_table', array( $this, 'add_tracking_to_email' ), 10, 4 );
				break;
			case 'after_customer_details':
				add_action( 'woocommerce_email_customer_details', array( $this, 'add_tracking_to_customer_details' ), 30, 4 );
				break;
			case 'after_order_table':
			default:
				add_action( 'woocommerce_email_after_order_table', array( $this, 'add_tracking_to_email' ), 10, 4 );
				break;
		}

		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'add_tracking_to_order_page' ), 10, 1 );
	}

	public function add_tracking_to_email( $order, $sent_to_admin, $plain_text, $email ) {
		if ( 'yes' !== get_option( 'jutso_st_enable_email', 'yes' ) ) {
			return;
		}

		if ( ! in_array( $email->id, array( 'customer_completed_order', 'customer_invoice', 'customer_processing_order' ), true ) ) {
			return;
		}

		$this->display_tracking_info( $order, $plain_text );
	}

	public function add_tracking_to_customer_details( $order, $sent_to_admin, $plain_text, $email ) {
		if ( 'yes' !== get_option( 'jutso_st_enable_email', 'yes' ) ) {
			return;
		}

		if ( ! in_array( $email->id, array( 'customer_completed_order', 'customer_invoice', 'customer_processing_order' ), true ) ) {
			return;
		}

		$this->display_tracking_info( $order, $plain_text );
	}

	public function add_tracking_to_order_page( $order ) {
		$this->display_tracking_info( $order, false );
	}

	private function display_tracking_info( $order, $plain_text = false ) {
		$tracking_number = $order->get_meta( '_jutso_tracking_number' );
		$tracking_carrier = $order->get_meta( '_jutso_tracking_carrier' );

		if ( empty( $tracking_number ) ) {
			return;
		}

		// Handle multiple tracking numbers
		$tracking_numbers = array_map( 'trim', explode( ',', $tracking_number ) );
		$email_text = get_option( 'jutso_st_email_text', __( 'Track your shipment:', 'jut-so-shipment-tracking' ) );

		if ( $plain_text ) {
			echo "\n\n" . esc_html( $email_text ) . "\n";
			
			if ( count( $tracking_numbers ) > 1 ) {
				echo esc_html__( 'Tracking Numbers:', 'jut-so-shipment-tracking' ) . "\n";
				foreach ( $tracking_numbers as $single_tracking_number ) {
					if ( ! empty( $single_tracking_number ) ) {
						echo '  • ' . esc_html( $single_tracking_number );
						$tracking_url = $this->get_tracking_url( $single_tracking_number, $tracking_carrier );
						if ( $tracking_url ) {
							echo ' - ' . esc_url( $tracking_url );
						}
						echo "\n";
					}
				}
			} else {
				echo esc_html__( 'Tracking Number:', 'jut-so-shipment-tracking' ) . ' ' . esc_html( $tracking_number ) . "\n";
				$tracking_url = $this->get_tracking_url( $tracking_number, $tracking_carrier );
				if ( $tracking_url ) {
					echo esc_html__( 'Track your package:', 'jut-so-shipment-tracking' ) . ' ' . esc_url( $tracking_url ) . "\n";
				}
			}
			
			if ( $tracking_carrier ) {
				echo esc_html__( 'Carrier:', 'jut-so-shipment-tracking' ) . ' ' . esc_html( $this->get_carrier_name( $tracking_carrier ) ) . "\n";
			}
		} else {
			?>
			<div style="margin: 20px 0; padding: 15px; background-color: #f7f7f7; border: 1px solid #e5e5e5; border-radius: 3px;">
				<h3 style="margin-top: 0; color: #333;"><?php echo esc_html( $email_text ); ?></h3>
				
				<?php if ( count( $tracking_numbers ) > 1 ) : ?>
					<p style="margin: 10px 0;">
						<strong><?php esc_html_e( 'Tracking Numbers:', 'jut-so-shipment-tracking' ); ?></strong>
					</p>
					<ul style="margin: 10px 0; padding-left: 20px;">
						<?php foreach ( $tracking_numbers as $single_tracking_number ) : 
							if ( ! empty( $single_tracking_number ) ) :
						?>
							<li style="margin: 5px 0;"><?php echo esc_html( $single_tracking_number ); ?></li>
						<?php 
							endif;
						endforeach; ?>
					</ul>
				<?php else : ?>
					<p style="margin: 10px 0;">
						<strong><?php esc_html_e( 'Tracking Number:', 'jut-so-shipment-tracking' ); ?></strong> 
						<?php echo esc_html( $tracking_number ); ?>
					</p>
				<?php endif; ?>
				
				<?php if ( $tracking_carrier ) : ?>
					<p style="margin: 10px 0;">
						<strong><?php esc_html_e( 'Carrier:', 'jut-so-shipment-tracking' ); ?></strong> 
						<?php echo esc_html( $this->get_carrier_name( $tracking_carrier ) ); ?>
					</p>
				<?php endif; ?>
				
				<p style="margin: 15px 0 5px;">
					<?php foreach ( $tracking_numbers as $single_tracking_number ) : 
						if ( ! empty( $single_tracking_number ) ) :
							$tracking_url = $this->get_tracking_url( $single_tracking_number, $tracking_carrier );
							if ( $tracking_url ) :
					?>
						<a href="<?php echo esc_url( $tracking_url ); ?>" 
						   target="_blank" 
						   style="display: inline-block; padding: 8px 16px; background-color: #2271b1; color: #fff; text-decoration: none; border-radius: 3px; margin-right: 10px; margin-bottom: 10px;">
							<?php echo count( $tracking_numbers ) > 1 ? esc_html( $single_tracking_number ) : esc_html__( 'Track Your Package', 'jut-so-shipment-tracking' ); ?>
						</a>
					<?php 
							endif;
						endif;
					endforeach; ?>
				</p>
			</div>
			<?php
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

	private function get_carrier_name( $carrier ) {
		$carriers = $this->get_carriers();
		return isset( $carriers[ $carrier ] ) ? $carriers[ $carrier ]['name'] : $carrier;
	}
}