<?php
/**
 * Plugin Name: jut-so Shipment Tracking
 * Plugin URI: https://jut-so.de
 * Description: Add shipment tracking codes to WooCommerce orders with email integration and API support
 * Version: 1.0.0
 * Author: Christopher Carus
 * Author URI: https://jut-so.de
 * License: Proprietary
 * License URI: https://jut-so.de/license
 * Text Domain: jut-so-shipment-tracking
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.5
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'JUTSO_ST_VERSION' ) ) {
	define( 'JUTSO_ST_VERSION', '1.0.0' );
}

if ( ! defined( 'JUTSO_ST_PLUGIN_FILE' ) ) {
	define( 'JUTSO_ST_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'JUTSO_ST_PLUGIN_DIR' ) ) {
	define( 'JUTSO_ST_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'JUTSO_ST_PLUGIN_URL' ) ) {
	define( 'JUTSO_ST_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

class JUT_SO_Shipment_Tracking {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
		add_action( 'before_woocommerce_init', array( $this, 'declare_compatibility' ) );
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
	}

	public function init() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			return;
		}

		$this->load_textdomain();
		$this->load_dependencies();
		$this->init_hooks();
	}

	private function load_textdomain() {
		load_plugin_textdomain(
			'jut-so-shipment-tracking',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages/'
		);
	}

	private function load_dependencies() {
		require_once JUTSO_ST_PLUGIN_DIR . 'includes/class-jutso-admin.php';
		require_once JUTSO_ST_PLUGIN_DIR . 'includes/class-jutso-api.php';
		require_once JUTSO_ST_PLUGIN_DIR . 'includes/class-jutso-emails.php';
		require_once JUTSO_ST_PLUGIN_DIR . 'includes/class-jutso-settings.php';
	}

	private function init_hooks() {
		JUTSO_Admin::get_instance();
		JUTSO_API::get_instance();
		JUTSO_Emails::get_instance();
		JUTSO_Settings::get_instance();
	}

	public function activate() {
		$default_options = array(
			'jutso_st_email_text' => __( 'Track your shipment:', 'jut-so-shipment-tracking' ),
			'jutso_st_enable_email' => 'yes',
			'jutso_st_default_carrier' => '',
		);

		foreach ( $default_options as $option_name => $option_value ) {
			if ( false === get_option( $option_name ) ) {
				add_option( $option_name, $option_value );
			}
		}

		// Set default carriers if not already set
		if ( false === get_option( 'jutso_st_carriers' ) ) {
			$default_carriers = array(
				'dhl' => array(
					'name' => 'DHL',
					'url' => 'https://www.dhl.com/en/express/tracking.html?AWB={tracking_number}'
				),
				'fedex' => array(
					'name' => 'FedEx',
					'url' => 'https://www.fedex.com/fedextrack/?tracknumbers={tracking_number}'
				)
			);
			add_option( 'jutso_st_carriers', $default_carriers );
		}

		flush_rewrite_rules();
	}

	public function deactivate() {
		flush_rewrite_rules();
	}

	public function declare_compatibility() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}

	public function woocommerce_missing_notice() {
		?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'JUT-SO Shipment Tracking requires WooCommerce to be installed and activated.', 'jut-so-shipment-tracking' ); ?></p>
		</div>
		<?php
	}
}

JUT_SO_Shipment_Tracking::get_instance();