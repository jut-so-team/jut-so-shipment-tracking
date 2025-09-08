<?php

defined( 'ABSPATH' ) || exit;

class JUTSO_Settings {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( JUTSO_ST_PLUGIN_FILE ), array( $this, 'add_settings_link' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	public function add_settings_page() {
		add_submenu_page(
			'woocommerce',
			__( 'Shipment Tracking Settings', 'jut-so-shipment-tracking' ),
			__( 'Shipment Tracking', 'jut-so-shipment-tracking' ),
			'manage_woocommerce',
			'jutso-shipment-tracking',
			array( $this, 'render_settings_page' )
		);
	}

	public function register_settings() {
		register_setting(
			'jutso_shipment_tracking_settings',
			'jutso_st_enable_email',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
				'default'           => 'yes',
			)
		);

		register_setting(
			'jutso_shipment_tracking_settings',
			'jutso_st_email_text',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => __( 'Track your shipment:', 'jut-so-shipment-tracking' ),
			)
		);

		register_setting(
			'jutso_shipment_tracking_settings',
			'jutso_st_email_position',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'after_order_table',
			)
		);

		register_setting(
			'jutso_shipment_tracking_settings',
			'jutso_st_carriers',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_carriers' ),
				'default'           => $this->get_default_carriers(),
			)
		);

		register_setting(
			'jutso_shipment_tracking_settings',
			'jutso_st_default_carrier',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		add_settings_section(
			'jutso_st_general_section',
			__( 'General Settings', 'jut-so-shipment-tracking' ),
			array( $this, 'render_general_section' ),
			'jutso_shipment_tracking_settings'
		);

		add_settings_field(
			'jutso_st_enable_email',
			__( 'Enable Email Integration', 'jut-so-shipment-tracking' ),
			array( $this, 'render_enable_email_field' ),
			'jutso_shipment_tracking_settings',
			'jutso_st_general_section'
		);

		add_settings_field(
			'jutso_st_email_text',
			__( 'Email Text', 'jut-so-shipment-tracking' ),
			array( $this, 'render_email_text_field' ),
			'jutso_shipment_tracking_settings',
			'jutso_st_general_section'
		);

		add_settings_field(
			'jutso_st_email_position',
			__( 'Email Position', 'jut-so-shipment-tracking' ),
			array( $this, 'render_email_position_field' ),
			'jutso_shipment_tracking_settings',
			'jutso_st_general_section'
		);

		add_settings_section(
			'jutso_st_carriers_section',
			__( 'Carrier Settings', 'jut-so-shipment-tracking' ),
			array( $this, 'render_carriers_section' ),
			'jutso_shipment_tracking_settings'
		);

		add_settings_field(
			'jutso_st_default_carrier',
			__( 'Default Carrier', 'jut-so-shipment-tracking' ),
			array( $this, 'render_default_carrier_field' ),
			'jutso_shipment_tracking_settings',
			'jutso_st_carriers_section'
		);

		add_settings_field(
			'jutso_st_carriers',
			__( 'Shipping Carriers', 'jut-so-shipment-tracking' ),
			array( $this, 'render_carriers_field' ),
			'jutso_shipment_tracking_settings',
			'jutso_st_carriers_section'
		);
	}

	public function render_settings_page() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			
			<form action="options.php" method="post">
				<?php
				settings_fields( 'jutso_shipment_tracking_settings' );
				do_settings_sections( 'jutso_shipment_tracking_settings' );
				submit_button( __( 'Save Settings', 'jut-so-shipment-tracking' ) );
				?>
			</form>

			<div class="jutso-st-info" style="margin-top: 40px; padding: 20px; background: #f8f9fa; border-left: 4px solid #2271b1;">
				<h2><?php esc_html_e( 'API Information', 'jut-so-shipment-tracking' ); ?></h2>
				<p><?php esc_html_e( 'REST API Endpoints:', 'jut-so-shipment-tracking' ); ?></p>
				<ul style="list-style: disc; margin-left: 20px;">
					<li>
						<strong>GET</strong> <code>/wp-json/jutso-tracking/v1/orders/{order_id}/tracking</code><br>
						<em><?php esc_html_e( 'Retrieve tracking information for an order', 'jut-so-shipment-tracking' ); ?></em><br>
						<?php esc_html_e( 'Returns tracking_urls object with tracking numbers as keys and URLs as values', 'jut-so-shipment-tracking' ); ?>
					</li>
					<li>
						<strong>POST</strong> <code>/wp-json/jutso-tracking/v1/orders/{order_id}/tracking</code><br>
						<em><?php esc_html_e( 'Add or update tracking information', 'jut-so-shipment-tracking' ); ?></em><br>
						<?php esc_html_e( 'Required:', 'jut-so-shipment-tracking' ); ?> tracking_number <?php esc_html_e( '(supports comma-separated values)', 'jut-so-shipment-tracking' ); ?><br>
						<?php esc_html_e( 'Optional:', 'jut-so-shipment-tracking' ); ?> carrier <?php esc_html_e( '(uses default if not specified)', 'jut-so-shipment-tracking' ); ?>
					</li>
					<li>
						<strong>DELETE</strong> <code>/wp-json/jutso-tracking/v1/orders/{order_id}/tracking</code><br>
						<em><?php esc_html_e( 'Remove tracking information', 'jut-so-shipment-tracking' ); ?></em>
					</li>
					<li>
						<strong>POST</strong> <code>/wp-json/jutso-tracking/v1/orders/batch</code><br>
						<em><?php esc_html_e( 'Batch update tracking for multiple orders', 'jut-so-shipment-tracking' ); ?></em><br>
						<?php esc_html_e( 'Supports multiple tracking numbers per order', 'jut-so-shipment-tracking' ); ?>
					</li>
				</ul>
				<p><?php esc_html_e( 'Authentication: Requires manage_woocommerce capability', 'jut-so-shipment-tracking' ); ?></p>
				<p style="margin-top: 15px;"><strong><?php esc_html_e( 'Multiple Tracking Numbers:', 'jut-so-shipment-tracking' ); ?></strong><br>
				<?php esc_html_e( 'You can add multiple tracking numbers by separating them with commas. Example: ABC123, DEF456, GHI789', 'jut-so-shipment-tracking' ); ?><br>
				<?php esc_html_e( 'All tracking numbers will use the same carrier. Each number gets its own tracking link.', 'jut-so-shipment-tracking' ); ?></p>
			</div>

			<div class="jutso-st-shortcuts" style="margin-top: 20px; padding: 20px; background: #f8f9fa; border-left: 4px solid #2271b1;">
				<h2><?php esc_html_e( 'Tracking URL Variables', 'jut-so-shipment-tracking' ); ?></h2>
				<p><?php esc_html_e( 'You can use the following variables in your custom tracking URL:', 'jut-so-shipment-tracking' ); ?></p>
				<ul style="list-style: disc; margin-left: 20px;">
					<li><code>{tracking_number}</code> - <?php esc_html_e( 'The tracking number', 'jut-so-shipment-tracking' ); ?></li>
				</ul>
				<p><strong><?php esc_html_e( 'Example:', 'jut-so-shipment-tracking' ); ?></strong><br>
				<code>https://track.example.com/?tracking={tracking_number}</code></p>
			</div>
		</div>
		<?php
	}

	public function render_general_section() {
		echo '<p>' . esc_html__( 'Configure the shipment tracking settings below.', 'jut-so-shipment-tracking' ) . '</p>';
	}

	public function render_enable_email_field() {
		$value = get_option( 'jutso_st_enable_email', 'yes' );
		?>
		<input type="checkbox" id="jutso_st_enable_email" name="jutso_st_enable_email" value="yes" <?php checked( $value, 'yes' ); ?> />
		<label for="jutso_st_enable_email"><?php esc_html_e( 'Add tracking information to order confirmation emails', 'jut-so-shipment-tracking' ); ?></label>
		<?php
	}

	public function render_email_text_field() {
		$value = get_option( 'jutso_st_email_text', __( 'Track your shipment:', 'jut-so-shipment-tracking' ) );
		?>
		<input type="text" id="jutso_st_email_text" name="jutso_st_email_text" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
		<p class="description"><?php esc_html_e( 'Text displayed before the tracking link in emails', 'jut-so-shipment-tracking' ); ?></p>
		<?php
	}

	public function render_email_position_field() {
		$value = get_option( 'jutso_st_email_position', 'after_order_table' );
		?>
		<select id="jutso_st_email_position" name="jutso_st_email_position">
			<option value="after_order_table" <?php selected( $value, 'after_order_table' ); ?>><?php esc_html_e( 'After order table', 'jut-so-shipment-tracking' ); ?></option>
			<option value="before_order_table" <?php selected( $value, 'before_order_table' ); ?>><?php esc_html_e( 'Before order table', 'jut-so-shipment-tracking' ); ?></option>
			<option value="after_customer_details" <?php selected( $value, 'after_customer_details' ); ?>><?php esc_html_e( 'After customer details', 'jut-so-shipment-tracking' ); ?></option>
		</select>
		<p class="description"><?php esc_html_e( 'Where to display tracking information in the email', 'jut-so-shipment-tracking' ); ?></p>
		<?php
	}

	public function render_carriers_section() {
		echo '<p>' . esc_html__( 'Configure shipping carriers and their tracking URL templates.', 'jut-so-shipment-tracking' ) . '</p>';
	}

	public function render_default_carrier_field() {
		$carriers = $this->get_carriers();
		$default_carrier = get_option( 'jutso_st_default_carrier', '' );
		?>
		<select id="jutso_st_default_carrier" name="jutso_st_default_carrier">
			<option value=""><?php esc_html_e( '— No default —', 'jut-so-shipment-tracking' ); ?></option>
			<?php foreach ( $carriers as $key => $carrier ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $default_carrier, $key ); ?>>
					<?php echo esc_html( $carrier['name'] ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description"><?php esc_html_e( 'Default carrier to use when none is specified (e.g., via API).', 'jut-so-shipment-tracking' ); ?></p>
		<?php
	}

	public function render_carriers_field() {
		$carriers = $this->get_carriers();
		?>
		<div id="jutso-carriers-container">
			<table class="widefat jutso-carriers-table" id="jutso-carriers-table">
				<thead>
					<tr>
						<th class="jutso-carrier-key"><?php esc_html_e( 'Carrier Key', 'jut-so-shipment-tracking' ); ?></th>
						<th class="jutso-carrier-name"><?php esc_html_e( 'Carrier Name', 'jut-so-shipment-tracking' ); ?></th>
						<th class="jutso-carrier-url"><?php esc_html_e( 'Tracking URL Template', 'jut-so-shipment-tracking' ); ?></th>
						<th class="jutso-carrier-actions"><?php esc_html_e( 'Actions', 'jut-so-shipment-tracking' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! empty( $carriers ) ) : ?>
						<?php foreach ( $carriers as $key => $carrier ) : ?>
							<tr>
								<td class="jutso-carrier-key"><input type="text" name="jutso_st_carriers[<?php echo esc_attr( $key ); ?>][key]" value="<?php echo esc_attr( $key ); ?>" /></td>
								<td class="jutso-carrier-name"><input type="text" name="jutso_st_carriers[<?php echo esc_attr( $key ); ?>][name]" value="<?php echo esc_attr( $carrier['name'] ); ?>" /></td>
								<td class="jutso-carrier-url"><input type="text" name="jutso_st_carriers[<?php echo esc_attr( $key ); ?>][url]" value="<?php echo esc_attr( $carrier['url'] ); ?>" /></td>
								<td class="jutso-carrier-actions"><button type="button" class="button jutso-remove-carrier"><?php esc_html_e( 'Remove', 'jut-so-shipment-tracking' ); ?></button></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
			<p>
				<button type="button" id="jutso-add-carrier" class="button button-secondary"><?php esc_html_e( 'Add Carrier', 'jut-so-shipment-tracking' ); ?></button>
			</p>
			<p class="description">
				<?php esc_html_e( 'Use {tracking_number} as a placeholder in the tracking URL template.', 'jut-so-shipment-tracking' ); ?><br>
				<?php esc_html_e( 'Example: https://track.example.com/?tracking={tracking_number}', 'jut-so-shipment-tracking' ); ?>
			</p>
		</div>
		<?php
	}

	public function get_carriers() {
		$carriers = get_option( 'jutso_st_carriers' );
		if ( false === $carriers || empty( $carriers ) ) {
			$carriers = $this->get_default_carriers();
			// Update the option with default carriers
			update_option( 'jutso_st_carriers', $carriers );
		}
		
		// Fix any URLs that might have lost their curly braces
		$needs_update = false;
		foreach ( $carriers as $key => &$carrier ) {
			if ( ! empty( $carrier['url'] ) && strpos( $carrier['url'], 'tracking_number' ) !== false && strpos( $carrier['url'], '{tracking_number}' ) === false ) {
				// Replace tracking_number without braces to {tracking_number}
				$carrier['url'] = str_replace( 'tracking_number', '{tracking_number}', $carrier['url'] );
				$needs_update = true;
			}
		}
		
		if ( $needs_update ) {
			update_option( 'jutso_st_carriers', $carriers );
		}
		
		return $carriers;
	}

	public function get_default_carriers() {
		return array(
			'dhl' => array(
				'name' => 'DHL',
				'url' => 'https://www.dhl.com/en/express/tracking.html?AWB={tracking_number}'
			),
			'fedex' => array(
				'name' => 'FedEx',
				'url' => 'https://www.fedex.com/fedextrack/?tracknumbers={tracking_number}'
			)
		);
	}

	public function sanitize_carriers( $carriers ) {
		if ( ! is_array( $carriers ) ) {
			return $this->get_default_carriers();
		}

		$sanitized = array();
		foreach ( $carriers as $key => $carrier ) {
			// Skip empty entries
			if ( empty( $carrier['key'] ) && empty( $carrier['name'] ) && empty( $carrier['url'] ) ) {
				continue;
			}
			
			// Determine the actual key to use
			// For new carriers (key starts with "new_"), use the value from carrier['key']
			// For existing carriers, use the array key
			$carrier_key = ( strpos( $key, 'new_' ) === 0 && ! empty( $carrier['key'] ) ) 
				? $carrier['key'] 
				: ( ! empty( $carrier['key'] ) ? $carrier['key'] : $key );
			
			// Only require key and name to be non-empty
			if ( ! empty( $carrier_key ) && ! empty( $carrier['name'] ) ) {
				$sanitized_key = sanitize_key( $carrier_key );
				
				// Special handling for URL to preserve {tracking_number} placeholder
				$url = $carrier['url'];
				if ( ! empty( $url ) ) {
					// Don't use esc_url_raw as it removes curly braces
					// Just do basic sanitization while preserving the placeholder
					$url = sanitize_text_field( $url );
				}
				
				$sanitized[ $sanitized_key ] = array(
					'name' => sanitize_text_field( $carrier['name'] ),
					'url' => $url
				);
			}
		}

		// Clear the carriers cache when settings are updated
		if ( class_exists( 'JUTSO_Helpers' ) ) {
			JUTSO_Helpers::clear_carriers_cache();
		}

		return ! empty( $sanitized ) ? $sanitized : $this->get_default_carriers();
	}

	public function sanitize_checkbox( $value ) {
		return ( 'yes' === $value ) ? 'yes' : 'no';
	}

	public function enqueue_admin_scripts( $hook ) {
		if ( 'woocommerce_page_jutso-shipment-tracking' !== $hook ) {
			return;
		}

		wp_enqueue_script(
			'jutso-settings-js',
			JUTSO_ST_PLUGIN_URL . 'assets/js/settings.js',
			array( 'jquery' ),
			JUTSO_ST_VERSION,
			true
		);

		wp_enqueue_style(
			'jutso-settings-styles',
			JUTSO_ST_PLUGIN_URL . 'assets/css/settings.css',
			array(),
			JUTSO_ST_VERSION
		);
	}

	public function add_settings_link( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'admin.php?page=jutso-shipment-tracking' ),
			__( 'Settings', 'jut-so-shipment-tracking' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}
}