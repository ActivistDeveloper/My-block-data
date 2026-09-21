<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$datasets = mbd_get_datasets();
?>
<div class="wrap mbd-wrap">
	<h1><?php esc_html_e( 'Block Data — Import', 'my-block-data' ); ?></h1>

	<p><?php esc_html_e( 'Paste text or upload a .txt file with this structure, repeated as many times as you like:', 'my-block-data' ); ?></p>
	<pre class="mbd-format-example">(2024/01/15) Your headline here
https://example.com/link-1
https://example.com/link-2

(2024/01/20) Another headline
https://example.com/link-1</pre>
	<p class="description">
		<?php esc_html_e( 'Any text before the first recognised date line is treated as intro text. The date must be wrapped in round parentheses in YYYY/MM/DD format, e.g. (2024/01/15).', 'my-block-data' ); ?>
	</p>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
		<?php wp_nonce_field( 'mbd_import' ); ?>
		<input type="hidden" name="action" value="mbd_import" />

		<table class="form-table">
			<tr>
				<th><label for="mbd-import-dataset"><?php esc_html_e( 'Import into', 'my-block-data' ); ?></label></th>
				<td>
					<select id="mbd-import-dataset" name="dataset_id">
						<option value=""><?php esc_html_e( '— Create a new dataset —', 'my-block-data' ); ?></option>
						<?php foreach ( $datasets as $d ) : ?>
							<option value="<?php echo (int) $d->id; ?>"><?php echo esc_html( $d->title ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr id="mbd-new-dataset-row">
				<th><label for="mbd-new-dataset-title"><?php esc_html_e( 'New dataset name', 'my-block-data' ); ?></label></th>
				<td><input type="text" id="mbd-new-dataset-title" name="new_dataset_title" class="regular-text" placeholder="<?php esc_attr_e( 'Only needed if creating a new dataset', 'my-block-data' ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="mbd-import-file"><?php esc_html_e( 'Upload .txt file', 'my-block-data' ); ?></label></th>
				<td><input type="file" id="mbd-import-file" name="import_file" accept=".txt,text/plain" /></td>
			</tr>
			<tr>
				<th><label for="mbd-import-text"><?php esc_html_e( 'Or paste text', 'my-block-data' ); ?></label></th>
				<td><textarea id="mbd-import-text" name="import_text" rows="12" class="large-text code"></textarea></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Intro text', 'my-block-data' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="update_intro" value="1" />
						<?php esc_html_e( 'Replace the dataset\'s existing intro text with the text found before the first block in this import (only applied if not empty).', 'my-block-data' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Import', 'my-block-data' ); ?></button>
		</p>
		<p class="description"><?php esc_html_e( 'New blocks are appended after any existing blocks in the chosen dataset — nothing is overwritten or removed.', 'my-block-data' ); ?></p>
	</form>
</div>
