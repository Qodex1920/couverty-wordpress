<?php
/**
 * Plugin Name: Couverty
 * Plugin URI: https://couverty.ch
 * Update URI: https://github.com/Qodex1920/couverty-wordpress/
 * Description: Intégrez facilement le menu et les réservations de votre restaurant depuis Couverty
 * Version: 1.8.0
 * Author: Couverty
 * Author URI: https://couverty.ch
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: couverty
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires at least: 6.0
 */

defined( 'ABSPATH' ) || exit;

// Define plugin constants
define( 'COUVERTY_VERSION', '1.8.0' );
define( 'COUVERTY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'COUVERTY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'COUVERTY_PLUGIN_FILE', __FILE__ );

// Check PHP version
if ( version_compare( phpversion(), '7.4', '<' ) ) {
	add_action( 'admin_notices', function() {
		echo '<div class="notice notice-error"><p>';
		esc_html_e( 'Couverty requires PHP 7.4 or higher.', 'couverty' );
		echo '</p></div>';
	} );
	return;
}

// Check WordPress version
if ( version_compare( get_bloginfo( 'version' ), '6.0', '<' ) ) {
	add_action( 'admin_notices', function() {
		echo '<div class="notice notice-error"><p>';
		esc_html_e( 'Couverty requires WordPress 6.0 or higher.', 'couverty' );
		echo '</p></div>';
	} );
	return;
}

// Load required files
require_once COUVERTY_PLUGIN_DIR . 'includes/class-couverty.php';
require_once COUVERTY_PLUGIN_DIR . 'includes/class-couverty-api.php';
require_once COUVERTY_PLUGIN_DIR . 'includes/class-couverty-admin.php';
require_once COUVERTY_PLUGIN_DIR . 'includes/class-couverty-shortcodes.php';
require_once COUVERTY_PLUGIN_DIR . 'includes/class-couverty-blocks.php';
require_once COUVERTY_PLUGIN_DIR . 'includes/class-couverty-rest.php';
require_once COUVERTY_PLUGIN_DIR . 'includes/class-couverty-sync.php';
require_once COUVERTY_PLUGIN_DIR . 'includes/functions.php';

// Activation / deactivation hooks.
register_activation_hook( __FILE__, array( 'Couverty_Sync', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Couverty_Sync', 'deactivate' ) );

/**
 * Handle plugin updates without a deactivate/reactivate cycle.
 *
 * Kept deliberately cheap: the actual re-sync runs in the background so the
 * first visitor after an update never waits on the Couverty API.
 */
add_action( 'init', function() {
	$stored = get_option( 'couverty_version', '' );

	if ( COUVERTY_VERSION === $stored ) {
		return;
	}

	update_option( 'couverty_version', COUVERTY_VERSION, true );

	// Prices became a single pre-formatted string in 1.7.0 — drop the old keys.
	if ( $stored && version_compare( $stored, '1.7.0', '<' ) ) {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->postmeta} WHERE meta_key IN (%s, %s, %s)",
				'couverty_prix2',
				'couverty_prix3',
				'couverty_prix_affichage'
			)
		);
	}

	// Force the next sync to be a full one, off the request thread.
	delete_option( 'couverty_menu_updated_at' );
	Couverty_Sync::schedule_resync();
}, 99 );

// Auto-updates from GitHub Releases.
if ( file_exists( COUVERTY_PLUGIN_DIR . 'vendor/plugin-update-checker/plugin-update-checker.php' ) ) {
	require_once COUVERTY_PLUGIN_DIR . 'vendor/plugin-update-checker/plugin-update-checker.php';

	$couverty_update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		'https://github.com/Qodex1920/couverty-wordpress/',
		__FILE__,
		'couverty'
	);

	// Only download the couverty.zip asset (ignore other files attached to releases).
	$couverty_update_checker->getVcsApi()->enableReleaseAssets( '/^couverty\.zip$/' );

	// Plugin icons shown on the updates screen.
	$couverty_update_checker->addResultFilter( function( $info ) {
		$info->icons = array(
			'1x'      => COUVERTY_PLUGIN_URL . 'assets/images/icon-128x128.png',
			'2x'      => COUVERTY_PLUGIN_URL . 'assets/images/icon-256x256.png',
			'default' => COUVERTY_PLUGIN_URL . 'assets/images/icon-256x256.png',
		);
		return $info;
	} );
}

// Initialize plugin on plugins_loaded hook.
add_action( 'plugins_loaded', function() {
	Couverty::get_instance();
} );
