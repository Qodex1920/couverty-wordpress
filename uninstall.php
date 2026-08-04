<?php
/**
 * Uninstall hook for Couverty plugin
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Load required files for cleanup.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-couverty-sync.php';

// Delete all synced CPT data, taxonomies, and options.
Couverty_Sync::delete_all_data();

// Delete settings and version options.
delete_option( 'couverty_settings' );
delete_option( 'couverty_version' );
delete_option( 'couverty_sync_status' );
delete_option( 'couverty_menu_updated_at' );

// Clear all transients.
global $wpdb;
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_couverty_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_couverty_' ) . '%'
	)
);
