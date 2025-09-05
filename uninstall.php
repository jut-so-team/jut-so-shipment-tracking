<?php
/**
 * Uninstall script for jut-so Shipment Tracking
 *
 * This file is executed when the plugin is deleted.
 * It removes all plugin data from the database.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$options_to_delete = array(
	'jutso_st_email_text',
	'jutso_st_enable_email',
	'jutso_st_email_position',
	'jutso_st_carriers',
	'jutso_st_default_carrier',
);

foreach ( $options_to_delete as $option ) {
	delete_option( $option );
}

global $wpdb;

// Check if HPOS is enabled
$hpos_enabled = class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) 
	&& \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();

$meta_keys = array(
	'_jutso_tracking_number',
	'_jutso_tracking_carrier',
	'_jutso_tracking_date',
);

if ( $hpos_enabled ) {
	// For HPOS, delete from order meta table
	foreach ( $meta_keys as $meta_key ) {
		$wpdb->delete(
			$wpdb->prefix . 'wc_orders_meta',
			array( 'meta_key' => $meta_key ),
			array( '%s' )
		);
	}
} else {
	// For legacy storage, delete from postmeta table
	foreach ( $meta_keys as $meta_key ) {
		$wpdb->delete(
			$wpdb->postmeta,
			array( 'meta_key' => $meta_key ),
			array( '%s' )
		);
	}
}

wp_cache_flush();