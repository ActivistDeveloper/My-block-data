<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'mbd_register_shortcode' );
function mbd_register_shortcode() {
	add_shortcode( 'myblockdata', 'mbd_render_shortcode' );
}

add_action( 'wp_enqueue_scripts', 'mbd_register_frontend_assets' );
function mbd_register_frontend_assets() {
	wp_register_style( 'mbd-frontend', MBD_PLUGIN_URL . 'assets/css/frontend.css', array(), MBD_VERSION );
}

/**
 * [myblockdata slug="your-dataset-slug"]
 */
function mbd_render_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'slug' => '',
		),
		$atts,
		'myblockdata'
	);

	if ( empty( $atts['slug'] ) ) {
		return mbd_shortcode_notice( __( 'my-block-data: no "slug" attribute given.', 'my-block-data' ) );
	}

	$dataset = mbd_get_dataset_by_slug( sanitize_title( $atts['slug'] ) );
	if ( ! $dataset ) {
		return mbd_shortcode_notice( __( 'my-block-data: no dataset found for this slug.', 'my-block-data' ) );
	}

	wp_enqueue_style( 'mbd-frontend' );

	$blocks = mbd_get_blocks( $dataset->id );

	ob_start();
	?>
	<div class="mbd-dataset" data-mbd-slug="<?php echo esc_attr( $dataset->slug ); ?>">
		<?php if ( ! empty( $dataset->intro ) ) : ?>
			<div class="mbd-intro"><?php echo wp_kses_post( wpautop( $dataset->intro ) ); ?></div>
		<?php endif; ?>

		<?php if ( empty( $blocks ) ) : ?>
			<p class="mbd-empty"><?php esc_html_e( 'No entries yet.', 'my-block-data' ); ?></p>
		<?php else : ?>
			<div class="mbd-blocks">
				<?php foreach ( $blocks as $block ) : ?>
					<div class="mbd-block">
						<div class="mbd-block-header">
							<span class="mbd-date"><?php echo esc_html( $block->block_date ); ?></span>
							<span class="mbd-headline"><?php echo esc_html( $block->headline ); ?></span>
						</div>
						<?php if ( ! empty( $block->links ) ) : ?>
							<ul class="mbd-links">
								<?php foreach ( $block->links as $url ) : ?>
									<li>
										<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer">
											<?php echo esc_html( $url ); ?>
										</a>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

function mbd_shortcode_notice( $message ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return '';
	}
	return '<p class="mbd-notice" style="color:#a00;border:1px solid #a00;padding:8px;">' . esc_html( $message ) . '</p>';
}
