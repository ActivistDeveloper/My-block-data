<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$datasets   = mbd_get_datasets();
$dataset_id = isset( $_GET['dataset_id'] ) ? absint( $_GET['dataset_id'] ) : ( isset( $datasets[0] ) ? (int) $datasets[0]->id : 0 );
$dataset    = $dataset_id ? mbd_get_dataset( $dataset_id ) : null;

$edit_block_id = isset( $_GET['action'], $_GET['id'] ) && 'edit' === $_GET['action'] ? absint( $_GET['id'] ) : 0;
$editing_block = $edit_block_id ? mbd_get_block( $edit_block_id ) : null;

$blocks = $dataset ? mbd_get_blocks( $dataset->id ) : array();
?>
<div class="wrap mbd-wrap">
	<h1><?php esc_html_e( 'Block Data — Blocks', 'my-block-data' ); ?></h1>

	<?php if ( empty( $datasets ) ) : ?>
		<p>
			<?php esc_html_e( 'You need to create a dataset first.', 'my-block-data' ); ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mbd-datasets' ) ); ?>"><?php esc_html_e( 'Create one now', 'my-block-data' ); ?></a>
		</p>
	<?php else : ?>

	<form method="get" class="mbd-dataset-switcher">
		<input type="hidden" name="page" value="mbd-blocks" />
		<label for="mbd-dataset-select"><strong><?php esc_html_e( 'Dataset:', 'my-block-data' ); ?></strong></label>
		<select id="mbd-dataset-select" name="dataset_id" onchange="this.form.submit()">
			<?php foreach ( $datasets as $d ) : ?>
				<option value="<?php echo (int) $d->id; ?>" <?php selected( $dataset_id, $d->id ); ?>><?php echo esc_html( $d->title ); ?></option>
			<?php endforeach; ?>
		</select>
	</form>

	<?php if ( $dataset ) : ?>
		<p><?php esc_html_e( 'Shortcode for this dataset:', 'my-block-data' ); ?> <code>[myblockdata slug="<?php echo esc_attr( $dataset->slug ); ?>"]</code></p>

		<div class="mbd-columns">
			<div class="mbd-col-list">
				<table class="widefat striped">
					<thead>
						<tr>
							<th style="width:120px;"><?php esc_html_e( 'Date', 'my-block-data' ); ?></th>
							<th><?php esc_html_e( 'Headline', 'my-block-data' ); ?></th>
							<th><?php esc_html_e( 'Links', 'my-block-data' ); ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $blocks ) ) : ?>
							<tr><td colspan="4"><?php esc_html_e( 'No blocks yet.', 'my-block-data' ); ?></td></tr>
						<?php else : ?>
							<?php foreach ( $blocks as $block ) : ?>
								<tr>
									<td><?php echo esc_html( $block->block_date ); ?></td>
									<td><?php echo esc_html( $block->headline ); ?></td>
									<td><?php echo (int) count( $block->links ); ?></td>
									<td>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=mbd-blocks&dataset_id=' . $dataset->id . '&action=edit&id=' . $block->id ) ); ?>"><?php esc_html_e( 'Edit', 'my-block-data' ); ?></a>
										|
										<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mbd_delete_block&id=' . $block->id . '&dataset_id=' . $dataset->id ), 'mbd_delete_block_' . $block->id ) ); ?>"
										   onclick="return confirm('<?php echo esc_js( __( 'Delete this block?', 'my-block-data' ) ); ?>');"><?php esc_html_e( 'Delete', 'my-block-data' ); ?></a>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

			<div class="mbd-col-form">
				<h2><?php echo $editing_block ? esc_html__( 'Edit Block', 'my-block-data' ) : esc_html__( 'Add New Block', 'my-block-data' ); ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="mbd-block-form">
					<?php wp_nonce_field( 'mbd_save_block' ); ?>
					<input type="hidden" name="action" value="mbd_save_block" />
					<input type="hidden" name="dataset_id" value="<?php echo (int) $dataset->id; ?>" />
					<input type="hidden" name="block_id" value="<?php echo $editing_block ? (int) $editing_block->id : 0; ?>" />

					<table class="form-table">
						<tr>
							<th><label for="mbd-block-date"><?php esc_html_e( 'Date', 'my-block-data' ); ?></label></th>
							<td>
								<input type="text" id="mbd-block-date" name="block_date" class="regular-text"
									placeholder="<?php esc_attr_e( 'e.g. 2024/01/15', 'my-block-data' ); ?>"
									value="<?php echo $editing_block ? esc_attr( $editing_block->block_date ) : ''; ?>" />
							</td>
						</tr>
						<tr>
							<th><label for="mbd-block-headline"><?php esc_html_e( 'Headline', 'my-block-data' ); ?></label></th>
							<td>
								<input type="text" id="mbd-block-headline" name="headline" class="regular-text" required
									value="<?php echo $editing_block ? esc_attr( $editing_block->headline ) : ''; ?>" />
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Links', 'my-block-data' ); ?></th>
							<td>
								<div id="mbd-links-wrap">
									<?php
									$existing_links = $editing_block ? $editing_block->links : array( '' );
									if ( empty( $existing_links ) ) {
										$existing_links = array( '' );
									}
									foreach ( $existing_links as $link ) :
										?>
										<div class="mbd-link-row">
											<input type="url" name="links[]" class="regular-text" placeholder="https://…" value="<?php echo esc_attr( $link ); ?>" />
											<button type="button" class="button mbd-remove-link">&times;</button>
										</div>
									<?php endforeach; ?>
								</div>
								<button type="button" class="button" id="mbd-add-link"><?php esc_html_e( '+ Add another link', 'my-block-data' ); ?></button>
							</td>
						</tr>
					</table>

					<p class="submit">
						<button type="submit" class="button button-primary"><?php echo $editing_block ? esc_html__( 'Update Block', 'my-block-data' ) : esc_html__( 'Add Block', 'my-block-data' ); ?></button>
						<?php if ( $editing_block ) : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=mbd-blocks&dataset_id=' . $dataset->id ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'my-block-data' ); ?></a>
						<?php endif; ?>
					</p>
				</form>
			</div>
		</div>
	<?php endif; ?>

	<?php endif; ?>
</div>

<template id="mbd-link-row-template">
	<div class="mbd-link-row">
		<input type="url" name="links[]" class="regular-text" placeholder="https://…" value="" />
		<button type="button" class="button mbd-remove-link">&times;</button>
	</div>
</template>
