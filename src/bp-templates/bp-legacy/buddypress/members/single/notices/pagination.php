<?php
/**
 * BuddyPress - Member’s Notice Pagination & Filter.
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 * @version 15.0.0
 */
?>
<?php if ( isset( $args['filter'], $args['selected'] ) ) : ?>
	<div class="item-list-tabs no-ajax" aria-label="<?php esc_attr_e( 'Member Notices secondary navigation', 'buddypress' ); ?>">
		<form action="" method="get">
			<ul>
				<li id="notices-filter-select" class="last">
					<select id="notices-filter-by" name="notice-type">
						<?php foreach ( $args['filter'] as $type => $label ) : ?>
							<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $type, $args['selected'] ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
					<button type="submit" id="bp-notice-submit"><?php esc_html_e( 'Filter', 'buddypress' ); ?></button>
				</li>
			</ul>
		</form>
	</div>
<?php endif; ?>

<?php if ( isset( $args['pagination_count'], $args['pagination_links'], $args['pagination_type'] ) ) : ?>
	<div id="pag-<?php echo esc_attr( $args['pagination_type'] ); ?>" class="pagination no-ajax">
		<div id="notifications-count-<?php echo esc_attr( $args['pagination_type'] ); ?>" class="pag-count">
			<?php echo esc_html( $args['pagination_count'] ); ?>
		</div>

		<div id="notifications-pag-<?php echo esc_attr( $args['pagination_type'] ); ?>" class="pagination-links">
			<?php
			// Escaping is done in WordPress's `paginate_links()` function.
			// phpcs:ignore WordPress.Security.EscapeOutput
			echo $args['pagination_links'];
			?>
		</div>
	</div>
<?php endif; ?>
