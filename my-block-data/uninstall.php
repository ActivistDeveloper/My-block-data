<?php
// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$table_datasets = $wpdb->prefix . 'mbd_datasets';
$table_blocks   = $wpdb->prefix . 'mbd_blocks';

// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- table names cannot be parameterised.
$wpdb->query( "DROP TABLE IF EXISTS {$table_blocks}" );
$wpdb->query( "DROP TABLE IF EXISTS {$table_datasets}" );
// phpcs:enable

delete_option( 'mbd_db_version' );
