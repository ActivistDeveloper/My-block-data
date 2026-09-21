<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =====================================================================
 * MENU
 * ===================================================================*/

add_action( 'admin_menu', 'mbd_register_admin_menu' );
function mbd_register_admin_menu() {
	add_menu_page(
		__( 'Block Data', 'my-block-data' ),
		__( 'Block Data', 'my-block-data' ),
		'manage_options',
		'mbd-datasets',
		'mbd_render_datasets_page',
		'dashicons-list-view',
		26
	);

	add_submenu_page( 'mbd-datasets', __( 'Datasets', 'my-block-data' ), __( 'Datasets', 'my-block-data' ), 'manage_options', 'mbd-datasets', 'mbd_render_datasets_page' );
	add_submenu_page( 'mbd-datasets', __( 'Blocks', 'my-block-data' ), __( 'Blocks', 'my-block-data' ), 'manage_options', 'mbd-blocks', 'mbd_render_blocks_page' );
	add_submenu_page( 'mbd-datasets', __( 'Import', 'my-block-data' ), __( 'Import', 'my-block-data' ), 'manage_options', 'mbd-import', 'mbd_render_import_page' );
}

add_action( 'admin_enqueue_scripts', 'mbd_admin_enqueue_assets' );
function mbd_admin_enqueue_assets( $hook ) {
	if ( strpos( $hook, 'mbd-' ) === false && strpos( $hook, 'page_mbd-' ) === false ) {
		return;
	}
	wp_enqueue_style( 'mbd-admin', MBD_PLUGIN_URL . 'admin/css/admin.css', array(), MBD_VERSION );
	wp_enqueue_script( 'mbd-admin', MBD_PLUGIN_URL . 'admin/js/admin.js', array(), MBD_VERSION, true );
}

/* =====================================================================
 * ADMIN NOTICES (simple transient-based flash messages)
 * ===================================================================*/

function mbd_set_notice( $message, $type = 'success' ) {
	set_transient( 'mbd_admin_notice_' . get_current_user_id(), array( 'message' => $message, 'type' => $type ), 60 );
}

add_action( 'admin_notices', 'mbd_show_notice' );
function mbd_show_notice() {
	$key    = 'mbd_admin_notice_' . get_current_user_id();
	$notice = get_transient( $key );
	if ( ! $notice ) {
		return;
	}
	delete_transient( $key );
	$class = 'success' === $notice['type'] ? 'notice-success' : 'notice-error';
	echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p>' . esc_html( $notice['message'] ) . '</p></div>';
}

/* =====================================================================
 * VIEW LOADERS
 * ===================================================================*/

function mbd_render_datasets_page() {
	require MBD_PLUGIN_DIR . 'admin/views/datasets.php';
}

function mbd_render_blocks_page() {
	require MBD_PLUGIN_DIR . 'admin/views/blocks.php';
}

function mbd_render_import_page() {
	require MBD_PLUGIN_DIR . 'admin/views/import.php';
}

/* =====================================================================
 * FORM HANDLERS (admin-post.php)
 * ===================================================================*/

// ---- Save (create or update) a dataset -------------------------------
add_action( 'admin_post_mbd_save_dataset', 'mbd_handle_save_dataset' );
function mbd_handle_save_dataset() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Not allowed.', 'my-block-data' ) );
	}
	check_admin_referer( 'mbd_save_dataset' );

	$id    = isset( $_POST['dataset_id'] ) ? absint( $_POST['dataset_id'] ) : 0;
	$title = isset( $_POST['title'] ) ? wp_unslash( $_POST['title'] ) : '';
	$slug  = isset( $_POST['slug'] ) ? wp_unslash( $_POST['slug'] ) : '';
	$intro = isset( $_POST['intro'] ) ? wp_unslash( $_POST['intro'] ) : '';

	if ( '' === trim( $title ) ) {
		mbd_set_notice( __( 'Title is required.', 'my-block-data' ), 'error' );
		wp_safe_redirect( admin_url( 'admin.php?page=mbd-datasets' ) );
		exit;
	}

	if ( $id ) {
		mbd_update_dataset( $id, $title, $slug, $intro );
		mbd_set_notice( __( 'Dataset updated.', 'my-block-data' ) );
	} else {
		$id = mbd_create_dataset( $title, $slug, $intro );
		mbd_set_notice( __( 'Dataset created.', 'my-block-data' ) );
	}

	wp_safe_redirect( admin_url( 'admin.php?page=mbd-datasets' ) );
	exit;
}

// ---- Delete a dataset --------------------------------------------------
add_action( 'admin_post_mbd_delete_dataset', 'mbd_handle_delete_dataset' );
function mbd_handle_delete_dataset() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Not allowed.', 'my-block-data' ) );
	}
	$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	check_admin_referer( 'mbd_delete_dataset_' . $id );

	if ( $id ) {
		mbd_delete_dataset( $id );
		mbd_set_notice( __( 'Dataset and its blocks deleted.', 'my-block-data' ) );
	}

	wp_safe_redirect( admin_url( 'admin.php?page=mbd-datasets' ) );
	exit;
}

// ---- Save (create or update) a block -----------------------------------
add_action( 'admin_post_mbd_save_block', 'mbd_handle_save_block' );
function mbd_handle_save_block() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Not allowed.', 'my-block-data' ) );
	}
	check_admin_referer( 'mbd_save_block' );

	$block_id   = isset( $_POST['block_id'] ) ? absint( $_POST['block_id'] ) : 0;
	$dataset_id = isset( $_POST['dataset_id'] ) ? absint( $_POST['dataset_id'] ) : 0;
	$date       = isset( $_POST['block_date'] ) ? wp_unslash( $_POST['block_date'] ) : '';
	$headline   = isset( $_POST['headline'] ) ? wp_unslash( $_POST['headline'] ) : '';
	$raw_links  = isset( $_POST['links'] ) && is_array( $_POST['links'] ) ? wp_unslash( $_POST['links'] ) : array();

	if ( ! $dataset_id || '' === trim( $headline ) ) {
		mbd_set_notice( __( 'Dataset and headline are required.', 'my-block-data' ), 'error' );
		wp_safe_redirect( admin_url( 'admin.php?page=mbd-blocks&dataset_id=' . $dataset_id ) );
		exit;
	}

	if ( $block_id ) {
		mbd_update_block( $block_id, $date, $headline, $raw_links );
		mbd_set_notice( __( 'Block updated.', 'my-block-data' ) );
	} else {
		mbd_create_block( $dataset_id, $date, $headline, $raw_links );
		mbd_set_notice( __( 'Block added.', 'my-block-data' ) );
	}

	wp_safe_redirect( admin_url( 'admin.php?page=mbd-blocks&dataset_id=' . $dataset_id ) );
	exit;
}

// ---- Delete a block ------------------------------------------------------
add_action( 'admin_post_mbd_delete_block', 'mbd_handle_delete_block' );
function mbd_handle_delete_block() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Not allowed.', 'my-block-data' ) );
	}
	$id         = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	$dataset_id = isset( $_GET['dataset_id'] ) ? absint( $_GET['dataset_id'] ) : 0;
	check_admin_referer( 'mbd_delete_block_' . $id );

	if ( $id ) {
		mbd_delete_block( $id );
		mbd_set_notice( __( 'Block deleted.', 'my-block-data' ) );
	}

	wp_safe_redirect( admin_url( 'admin.php?page=mbd-blocks&dataset_id=' . $dataset_id ) );
	exit;
}

// ---- Import ---------------------------------------------------------------
add_action( 'admin_post_mbd_import', 'mbd_handle_import' );
function mbd_handle_import() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Not allowed.', 'my-block-data' ) );
	}
	check_admin_referer( 'mbd_import' );

	$dataset_id     = isset( $_POST['dataset_id'] ) ? absint( $_POST['dataset_id'] ) : 0;
	$new_title      = isset( $_POST['new_dataset_title'] ) ? wp_unslash( $_POST['new_dataset_title'] ) : '';
	$update_intro   = isset( $_POST['update_intro'] ) && '1' === $_POST['update_intro'];
	$pasted_text    = isset( $_POST['import_text'] ) ? wp_unslash( $_POST['import_text'] ) : '';

	// Resolve dataset: either an existing one, or create a new one from the title field.
	if ( ! $dataset_id && '' !== trim( $new_title ) ) {
		$dataset_id = mbd_create_dataset( $new_title, '', '' );
	}

	if ( ! $dataset_id ) {
		mbd_set_notice( __( 'Please choose an existing dataset or enter a name for a new one.', 'my-block-data' ), 'error' );
		wp_safe_redirect( admin_url( 'admin.php?page=mbd-import' ) );
		exit;
	}

	// Prefer an uploaded file over pasted text, if present.
	$text = $pasted_text;
	if ( ! empty( $_FILES['import_file']['tmp_name'] ) && is_uploaded_file( $_FILES['import_file']['tmp_name'] ) ) {
		$contents = file_get_contents( $_FILES['import_file']['tmp_name'] );
		if ( false !== $contents && '' !== trim( $contents ) ) {
			$text = $contents;
		}
	}

	if ( '' === trim( $text ) ) {
		mbd_set_notice( __( 'No import text or file provided.', 'my-block-data' ), 'error' );
		wp_safe_redirect( admin_url( 'admin.php?page=mbd-import' ) );
		exit;
	}

	$parsed = mbd_parse_import_text( $text );

	$sort_order = mbd_next_sort_order( $dataset_id );
	$created    = 0;
	foreach ( $parsed['blocks'] as $block ) {
		mbd_create_block( $dataset_id, $block['date'], $block['headline'], $block['links'], $sort_order );
		$sort_order++;
		$created++;
	}

	$intro_updated = false;
	if ( $update_intro && '' !== $parsed['intro'] ) {
		$dataset = mbd_get_dataset( $dataset_id );
		mbd_update_dataset( $dataset_id, $dataset->title, $dataset->slug, $parsed['intro'] );
		$intro_updated = true;
	}

	$message = sprintf(
		/* translators: 1: number of blocks imported, 2: number of skipped lines */
		__( 'Import complete: %1$d block(s) added. %2$d line(s) inside blocks were skipped because they did not look like links.', 'my-block-data' ),
		$created,
		$parsed['skipped_lines']
	);
	if ( $intro_updated ) {
		$message .= ' ' . __( 'Dataset intro text was updated.', 'my-block-data' );
	}
	mbd_set_notice( $message );

	wp_safe_redirect( admin_url( 'admin.php?page=mbd-blocks&dataset_id=' . $dataset_id ) );
	exit;
}
