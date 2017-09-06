
		<div id="bp-embed-header">
			<div class="bp-embed-avatar">
				<a href="<?php bp_displayed_user_link(); ?>">
					<?php bp_displayed_user_avatar( 'type=thumb&width=45&height=45' ); ?>
				</a>
			</div>

			<?php if ( bp_activity_embed_has_activity( bp_current_action() ) ) : ?>

				<?php while ( bp_activities() ) : bp_the_activity(); ?>
					<p class="bp-embed-activity-action">
						<?php bp_activity_action( array( 'no_timestamp' => true ) ); ?>
					</p>
				<?php endwhile; ?>

			<?php endif; ?>

			<p class="bp-embed-header-meta">
				<?php if ( bp_is_active( 'activity' ) && bp_activity_do_mentions() ) : ?>
					<span class="bp-embed-mentionname">@<?php bp_displayed_user_mentionname(); ?> &middot; </span>
				<?php endif; ?>

				<span class="bp-embed-timestamp"><a href="<?php bp_activity_thread_permalink(); ?>"><?php echo date_i18n( get_option( 'time_format' ) . ' - ' . get_option( 'date_format' ), strtotime( bp_get_activity_date_recorded() ) ); ?></a></span>
			</p>
		</div>
