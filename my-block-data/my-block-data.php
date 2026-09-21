<?php
/**
 * Plugin Name:       My Block Data
 * Description:       Manage multiple "datasets" of date/headline/links blocks and embed each one on a page via shortcode. Supports manual entry and plain-text file import.
 * Version:            1.0.0
 * Requires at least: 5.6
 * Requires PHP:       7.4
 * Author:             Custom
 * License:            GPL-2.0-or-later
 * Text Domain:        my-block-data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

// ---------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------
define( 'MBD_VERSION', '1.1.0' );
define( 'MBD_DB_VERSION', '1.1' );
define( 'MBD_PLUGIN_FILE', __FILE__ );
define( 'MBD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MBD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Table names (helper wrappers around $wpdb->prefix so they are always
 * resolved at call time, never cached before $wpdb exists).
 */
function mbd_table_datasets() {
	global $wpdb;
	return $wpdb->prefix . 'mbd_datasets';
}
function mbd_table_blocks() {
	global $wpdb;
	return $wpdb->prefix . 'mbd_blocks';
}

// ---------------------------------------------------------------------
// Includes
// ---------------------------------------------------------------------
require_once MBD_PLUGIN_DIR . 'includes/activator.php';
require_once MBD_PLUGIN_DIR . 'includes/db.php';
require_once MBD_PLUGIN_DIR . 'includes/parser.php';
require_once MBD_PLUGIN_DIR . 'includes/shortcode.php';

if ( is_admin() ) {
	require_once MBD_PLUGIN_DIR . 'admin/admin.php';
}

// ---------------------------------------------------------------------
// Activation / Deactivation
// ---------------------------------------------------------------------
register_activation_hook( __FILE__, 'mbd_activate_plugin' );

/**
 * On every load, make sure the DB schema is up to date (covers the case
 * where the plugin is updated by overwriting files without a deactivate
 * / reactivate cycle).
 */
add_action( 'plugins_loaded', 'mbd_maybe_upgrade_db' );
function mbd_maybe_upgrade_db() {
	if ( get_option( 'mbd_db_version' ) !== MBD_DB_VERSION ) {
		mbd_create_or_update_tables();
	}
}
