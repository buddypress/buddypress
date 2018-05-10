<?php
/**
 * BP Nouveau Activity Widget template.
 *
 * @since 3.0.0
 * @version 3.0.0
 */
?>

<?php if ( bp_has_activities( bp_nouveau_activity_widget_query() ) ) : ?>

	<div class="activity-list item-list">

		<?php
		while ( bp_activities() ) :
			bp_the_activity();
		?>

			<?php if ( bp_activity_has_content() ) : ?>

				<blockquote>

					<?php bp_activity_content_body(); ?>

					<footer>

						<cite>
							<a href="<?php bp_activity_user_link(); ?>" class="bp-tooltip" data-bp-tooltip="<?php echo esc_attr( bp_activity_member_display_name() ); ?>">
								<?php
								bp_activity_avatar(
									array(
										'type'   => 'thumb',
										'width'  => '40',
										'height' => '40',
									)
								);
								?>
							</a>
						</cite>

						<?php echo bp_insert_activity_meta(); ?>

					</footer>

				</blockquote>

			<?php else : ?>

				<p><?php bp_activity_action(); ?></p>

			<?php endif; ?>

		<?php endwhile; ?>

	</div>

<?php else : ?>

	<div class="widget-error">
		<?php bp_nouveau_user_feedback( 'activity-loop-none' ); ?>
	</div>

<?php endif; ?>
