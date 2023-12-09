<?php
/**
 * BuddyPress - Groups Loop
 *
 * @since 3.0.0
 * @version 12.0.0
 */

bp_nouveau_before_loop(); ?>

<?php if ( bp_get_current_group_directory_type() ) : ?>
	<p class="current-group-type"><?php bp_current_group_directory_type_message(); ?></p>
<?php endif; ?>

<?php if ( bp_has_groups( bp_ajax_querystring( 'groups' ) ) ) : ?>

	<?php bp_nouveau_pagination( 'top' ); ?>

	<ul id="groups-list" class="<?php bp_nouveau_loop_classes(); ?>">

		<?php while ( bp_groups() ) : bp_the_group(); ?>

			<li <?php bp_group_class( array( 'item-entry' ) ); ?> data-bp-item-id="<?php bp_group_id(); ?>" data-bp-item-component="groups">
				<div class="list-wrap">

					<?php if ( ! bp_disable_group_avatar_uploads() ) : ?>
						<div class="item-avatar">
							<a href="<?php bp_group_url(); ?>"><?php bp_group_avatar( bp_nouveau_avatar_args() ); ?></a>
						</div>
					<?php endif; ?>

					<div class="item">

						<div class="item-block">

							<h2 class="list-title groups-title"><?php bp_group_link(); ?></h2>

							<?php if ( bp_nouveau_group_has_meta() ) : ?>

								<p class="item-meta group-details"><?php bp_nouveau_the_group_meta( array( 'keys' => array( 'status', 'count' ) ) ); ?></p>

							<?php endif; ?>

							<p class="last-activity item-meta">
								<?php
									printf(
										/* translators: %s: last activity timestamp (e.g. "Active 1 hour ago") */
										esc_html__( 'Active %s', 'buddypress' ),
										sprintf(
											'<span data-livestamp="%1$s">%2$s</span>',
											bp_core_get_iso8601_date( bp_get_group_last_active( 0, array( 'relative' => false ) ) ),
											esc_html( bp_get_group_last_active() )
										)
									);
								?>
							</p>

							<?php bp_nouveau_groups_loop_buttons(); ?>
						</div>

						<div class="group-desc"><p><?php bp_nouveau_group_description_excerpt(); ?></p></div>

						<?php bp_nouveau_groups_loop_item(); ?>

					</div>

				</div>

			</li>

		<?php endwhile; ?>

	</ul>

	<?php bp_nouveau_pagination( 'bottom' ); ?>

<?php else : ?>

	<?php bp_nouveau_user_feedback( 'groups-loop-none' ); ?>

<?php endif; ?>

<?php
bp_nouveau_after_loop();
