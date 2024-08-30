<?php
/**
 * BuddyPress - Member’s Notice Pagination & Filter.
 *
 * @since 15.0.0
 * @version 15.0.0
 */
?>
<?php if ( isset( $args['filter'], $args['selected'] ) ) : ?>
	<div class="subnav-filters filters no-ajax" id="subnav-filters">
		<form action="" method="get">
			<div id="comp-filters" class="component-filters clearfix">
				<button type="submit" id="bp-notice-submit" class="last"><?php esc_html_e( 'Filter', 'buddypress' ); ?></button>
				<div class="last filter">
					<label for="bp-user-notices-select" class="bp-screen-reader-text">
						<span><?php esc_html_e( 'Type', 'buddypress' ); ?></span>
					</label>
					<div class="select-wrap">
						<select id="bp-user-notices-select" name="notice-type">

							<?php foreach ( $args['filter'] as $type => $label ) : ?>
								<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $type, $args['selected'] ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>

						</select>
						<span class="select-arrow" aria-hidden="true"></span>
					</div>
				</div>
			</div>
		</form>
	</div>
<?php endif; ?>

<?php if ( isset( $args['pagination_count'], $args['pagination_links'], $args['pagination_type'] ) ) : ?>
	<div class="bp-pagination <?php echo esc_attr( $args['pagination_type'] ); ?> no-ajax">
		<div class="pag-count <?php echo esc_attr( $args['pagination_type'] ); ?>">
			<p class="pag-data"><?php echo esc_html( $args['pagination_count'] ); ?></p>
		</div>

		<div class="bp-pagination-links <?php echo esc_attr( $args['pagination_type'] ); ?>">
			<p class="pag-data">
				<?php
				// Escaping is done in WordPress's `paginate_links()` function.
				// phpcs:ignore WordPress.Security.EscapeOutput
				echo $args['pagination_links'];
				?>
			</p>
		</div>
	</div>
<?php endif; ?>
