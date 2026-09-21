<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Try to turn a raw date string (expected format: YYYY/MM/DD, optionally
 * still wrapped in parentheses, 1 or 2 digit month/day) into a MySQL-ready
 * 'YYYY-MM-DD' string for sorting. Returns null if it can't be parsed.
 */
function mbd_normalize_date( $raw ) {
	$raw = trim( (string) $raw );
	$raw = trim( $raw, "() \t\n\r\0\x0B" );

	if ( preg_match( '/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $raw, $m ) ) {
		$y  = (int) $m[1];
		$mo = (int) $m[2];
		$d  = (int) $m[3];
		if ( checkdate( $mo, $d, $y ) ) {
			return sprintf( '%04d-%02d-%02d', $y, $mo, $d );
		}
	}

	return null;
}

/* =====================================================================
 * DATASETS
 * ===================================================================*/

/**
 * Get all datasets, ordered by title.
 */
function mbd_get_datasets() {
	global $wpdb;
	$table = mbd_table_datasets();
	return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY title ASC" );
}

/**
 * Get a single dataset by numeric ID.
 */
function mbd_get_dataset( $id ) {
	global $wpdb;
	$table = mbd_table_datasets();
	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );
}

/**
 * Get a single dataset by slug.
 */
function mbd_get_dataset_by_slug( $slug ) {
	global $wpdb;
	$table = mbd_table_datasets();
	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE slug = %s", $slug ) );
}

/**
 * Create a unique slug from a title, avoiding collisions with existing rows.
 */
function mbd_unique_slug( $title, $ignore_id = 0 ) {
	global $wpdb;
	$table = mbd_table_datasets();

	$base = sanitize_title( $title );
	if ( '' === $base ) {
		$base = 'dataset';
	}
	$slug = $base;
	$i    = 2;

	$existing = true;
	while ( $existing ) {
		$sql = "SELECT id FROM {$table} WHERE slug = %s AND id != %d";
		$existing = $wpdb->get_var( $wpdb->prepare( $sql, $slug, $ignore_id ) );
		if ( $existing ) {
			$slug = $base . '-' . $i;
		    $i++;	
		}
	}

	return $slug;
}

/**
 * Insert a new dataset. Returns the new dataset ID or WP_Error.
 */
function mbd_create_dataset( $title, $slug, $intro ) {
	global $wpdb;
	$table = mbd_table_datasets();

	$title = sanitize_text_field( $title );
	$slug  = $slug ? sanitize_title( $slug ) : sanitize_title( $title );
	$slug  = mbd_unique_slug( $slug ? $slug : $title );
	$intro = wp_kses_post( $intro );

	$result = $wpdb->insert(
		$table,
		array(
			'slug'       => $slug,
			'title'      => $title,
			'intro'      => $intro,
			'created_at' => current_time( 'mysql' ),
			'updated_at' => current_time( 'mysql' ),
		),
		array( '%s', '%s', '%s', '%s', '%s' )
	);

	if ( false === $result ) {
		return new WP_Error( 'mbd_db_error', 'Could not create dataset.' );
	}

	return (int) $wpdb->insert_id;
}

/**
 * Update an existing dataset.
 */
function mbd_update_dataset( $id, $title, $slug, $intro ) {
	global $wpdb;
	$table = mbd_table_datasets();

	$title = sanitize_text_field( $title );
	$slug  = $slug ? mbd_unique_slug( sanitize_title( $slug ), $id ) : mbd_unique_slug( $title, $id );
	$intro = wp_kses_post( $intro );

	return $wpdb->update(
		$table,
		array(
			'slug'       => $slug,
			'title'      => $title,
			'intro'      => $intro,
			'updated_at' => current_time( 'mysql' ),
		),
		array( 'id' => $id ),
		array( '%s', '%s', '%s', '%s' ),
		array( '%d' )
	);
}

/**
 * Delete a dataset and all of its blocks.
 */
function mbd_delete_dataset( $id ) {
	global $wpdb;
	$table_datasets = mbd_table_datasets();
	$table_blocks   = mbd_table_blocks();

	$wpdb->delete( $table_blocks, array( 'dataset_id' => $id ), array( '%d' ) );
	return $wpdb->delete( $table_datasets, array( 'id' => $id ), array( '%d' ) );
}

/* =====================================================================
 * BLOCKS
 * ===================================================================*/

/**
 * Get all blocks for a dataset, in display order.
 */
function mbd_get_blocks( $dataset_id ) {
	global $wpdb;
	$table = mbd_table_blocks();
	$rows  = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$table} WHERE dataset_id = %d
			 ORDER BY (block_date_norm IS NULL) ASC, block_date_norm DESC, sort_order ASC, id ASC",
			$dataset_id
		)
	);
	foreach ( $rows as $row ) {
		$row->links = mbd_decode_links( $row->links );
	}
	return $rows;
}

/**
 * Get a single block by ID.
 */
function mbd_get_block( $id ) {
	global $wpdb;
	$table = mbd_table_blocks();
	$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );
	if ( $row ) {
		$row->links = mbd_decode_links( $row->links );
	}
	return $row;
}

/**
 * Decode a JSON-encoded links column into a plain array of URLs.
 */
function mbd_decode_links( $json ) {
	$links = json_decode( (string) $json, true );
	return is_array( $links ) ? $links : array();
}

/**
 * Sanitize an array of raw link strings into a clean array of URLs.
 */
function mbd_sanitize_links( $raw_links ) {
	$clean = array();
	foreach ( (array) $raw_links as $link ) {
		$link = trim( $link );
		if ( '' === $link ) {
			continue;
		}
		$url = esc_url_raw( $link );
		if ( $url ) {
			$clean[] = $url;
		}
	}
	return $clean;
}

/**
 * Get the next sort_order value for a dataset (append to end).
 */
function mbd_next_sort_order( $dataset_id ) {
	global $wpdb;
	$table = mbd_table_blocks();
	$max   = $wpdb->get_var( $wpdb->prepare( "SELECT MAX(sort_order) FROM {$table} WHERE dataset_id = %d", $dataset_id ) );
	return is_null( $max ) ? 0 : ( (int) $max + 1 );
}

/**
 * Create a new block. $raw_links is an array of raw URL strings.
 */
function mbd_create_block( $dataset_id, $date, $headline, $raw_links, $sort_order = null ) {
	global $wpdb;
	$table = mbd_table_blocks();

	if ( is_null( $sort_order ) ) {
		$sort_order = mbd_next_sort_order( $dataset_id );
	}

	$result = $wpdb->insert(
		$table,
		array(
			'dataset_id'      => $dataset_id,
			'block_date'      => sanitize_text_field( $date ),
			'block_date_norm' => mbd_normalize_date( $date ),
			'headline'        => sanitize_text_field( $headline ),
			'links'           => wp_json_encode( mbd_sanitize_links( $raw_links ) ),
			'sort_order'      => $sort_order,
			'created_at'      => current_time( 'mysql' ),
			'updated_at'      => current_time( 'mysql' ),
		),
		array( '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
	);

	if ( false === $result ) {
		return new WP_Error( 'mbd_db_error', 'Could not create block.' );
	}

	return (int) $wpdb->insert_id;
}

/**
 * Update an existing block.
 */
function mbd_update_block( $id, $date, $headline, $raw_links ) {
	global $wpdb;
	$table = mbd_table_blocks();

	return $wpdb->update(
		$table,
		array(
			'block_date'      => sanitize_text_field( $date ),
			'block_date_norm' => mbd_normalize_date( $date ),
			'headline'        => sanitize_text_field( $headline ),
			'links'           => wp_json_encode( mbd_sanitize_links( $raw_links ) ),
			'updated_at'      => current_time( 'mysql' ),
		),
		array( 'id' => $id ),
		array( '%s', '%s', '%s', '%s', '%s' ),
		array( '%d' )
	);
}

/**
 * Delete a block.
 */
function mbd_delete_block( $id ) {
	global $wpdb;
	$table = mbd_table_blocks();
	return $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );
}

/**
 * Count blocks in a dataset.
 */
function mbd_count_blocks( $dataset_id ) {
	global $wpdb;
	$table = mbd_table_blocks();
	return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE dataset_id = %d", $dataset_id ) );
}
