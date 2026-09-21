<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mbd_activate_plugin() {
	mbd_create_or_update_tables();
}

function mbd_create_or_update_tables() {
	global $wpdb;

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$charset_collate = $wpdb->get_charset_collate();

	$table_datasets = mbd_table_datasets();
	$table_blocks   = mbd_table_blocks();

	$sql_datasets = "CREATE TABLE {$table_datasets} (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		slug VARCHAR(200) NOT NULL,
		title VARCHAR(255) NOT NULL,
		intro LONGTEXT NULL,
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		UNIQUE KEY slug (slug)
	) {$charset_collate};";

	$sql_blocks = "CREATE TABLE {$table_blocks} (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		dataset_id BIGINT(20) UNSIGNED NOT NULL,
		block_date VARCHAR(100) NOT NULL DEFAULT '',
		block_date_norm DATE NULL DEFAULT NULL,
		headline TEXT NOT NULL,
		links LONGTEXT NULL,
		sort_order INT(11) NOT NULL DEFAULT 0,
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY dataset_id (dataset_id),
		KEY block_date_norm (block_date_norm)
	) {$charset_collate};";

	dbDelta( $sql_datasets );
	dbDelta( $sql_blocks );

	// Backfill block_date_norm for rows saved before this column existed
	// (or before a normalized value was computed), wherever block_date
	// matches the supported YYYY/MM/DD pattern.
	$wpdb->query(
		"UPDATE {$table_blocks}
		 SET block_date_norm = STR_TO_DATE(block_date, '%Y/%m/%d')
		 WHERE block_date_norm IS NULL
		 AND block_date REGEXP '^[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}$'"
	);

	update_option( 'mbd_db_version', MBD_DB_VERSION );
}
