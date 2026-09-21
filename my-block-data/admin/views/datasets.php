<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$edit_id = isset( $_GET['action'], $_GET['id'] ) && 'edit' === $_GET['action'] ? absint( $_GET['id'] ) : 0;
$editing = $edit_id ? mbd_get_dataset( $edit_id ) : null;

$datasets = mbd_get_datasets();
?>
<div class="wrap mbd-wrap">
	<h1><?php esc_html_e( 'Block Data — Datasets', 'my-block-data' ); ?></h1>
	<p><?php esc_html_e( 'Each dataset is one independent set of intro text + blocks. Embed a dataset anywhere with its shortcode.', 'my-block-data' ); ?></p>

	<div class="mbd-columns">
		<div class="mbd-col-list">
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Title', 'my-block-data' ); ?></th>
						<th><?php esc_html_e( 'Shortcode', 'my-block-data' ); ?></th>
						<th><?php esc_html_e( 'Blocks', 'my-block-data' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $datasets ) ) : ?>
						<tr><td colspan="4"><?php esc_html_e( 'No datasets yet — create your first one.', 'my-block-data' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $datasets as $dataset ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $dataset->title ); ?></strong></td>
								<td><code>[myblockdata slug="<?php echo esc_attr( $dataset->slug ); ?>"]</code></td>
								<td><?php echo (int) mbd_count_blocks( $dataset->id ); ?></td>
								<td>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=mbd-datasets&action=edit&id=' . $dataset->id ) ); ?>"><?php esc_html_e( 'Edit', 'my-block-data' ); ?></a>
									|
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=mbd-blocks&dataset_id=' . $dataset->id ) ); ?>"><?php esc_html_e( 'Manage blocks', 'my-block-data' ); ?></a>
									|
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mbd_delete_dataset&id=' . $dataset->id ), 'mbd_delete_dataset_' . $dataset->id ) ); ?>"
									   onclick="return confirm('<?php echo esc_js( __( 'Delete this dataset and ALL of its blocks? This cannot be undone.', 'my-block-data' ) ); ?>');"
									   class="mbd-delete-link"><?php esc_html_e( 'Delete', 'my-block-data' ); ?></a>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

		<div class="mbd-col-form">
			<h2><?php echo $editing ? esc_html__( 'Edit Dataset', 'my-block-data' ) : esc_html__( 'Add New Dataset', 'my-block-data' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'mbd_save_dataset' ); ?>
				<input type="hidden" name="action" value="mbd_save_dataset" />
				<input type="hidden" name="dataset_id" value="<?php echo $editing ? (int) $editing->id : 0; ?>" />

				<table class="form-table">
					<tr>
						<th><label for="mbd-title"><?php esc_html_e( 'Title', 'my-block-data' ); ?></label></th>
						<td><input type="text" id="mbd-title" name="title" class="regular-text" required
							value="<?php echo $editing ? esc_attr( $editing->title ) : ''; ?>" /></td>
					</tr>
					<tr>
						<th><label for="mbd-slug"><?php esc_html_e( 'Slug', 'my-block-data' ); ?></label></th>
						<td>
							<input type="text" id="mbd-slug" name="slug" class="regular-text"
								placeholder="<?php esc_attr_e( 'auto-generated from title if left blank', 'my-block-data' ); ?>"
								value="<?php echo $editing ? esc_attr( $editing->slug ) : ''; ?>" />
							<p class="description"><?php esc_html_e( 'Used in the shortcode: [myblockdata slug="…"]', 'my-block-data' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="mbd-intro"><?php esc_html_e( 'Intro text', 'my-block-data' ); ?></label></th>
						<td>
							<?php
							wp_editor(
								$editing ? $editing->intro : '',
								'mbd-intro',
								array(
									'textarea_name' => 'intro',
									'textarea_rows' => 8,
									'media_buttons' => false,
								)
							);
							?>
						</td>
					</tr>
				</table>

				<p class="submit">
					<button type="submit" class="button button-primary"><?php echo $editing ? esc_html__( 'Update Dataset', 'my-block-data' ) : esc_html__( 'Create Dataset', 'my-block-data' ); ?></button>
					<?php if ( $editing ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=mbd-datasets' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'my-block-data' ); ?></a>
					<?php endif; ?>
				</p>
			</form>
		</div>
	</div>
</div>
