<?php get_header() ?>

	<div class="content-header">
		<?php bp_last_activity() ?>
	</div>

	<div id="content">
		<?php do_action( 'template_notices' ) // (error/success feedback) ?>

		<?php do_action( 'bp_before_friends_activity_content' ) ?>

		<div class="left-menu">
			<!-- Profile Menu (Avatar, Add Friend, Send Message buttons etc) -->
			<?php locate_template( array( 'profile/profile-menu.php' ), true ) ?>
		</div>

		<div class="main-column">

			<?php bp_get_profile_header() ?>

			<div class="bp-widget">
				<h4><?php _e( 'My Friends Activity', 'buddypress' ) ?> <a href="<?php bp_activities_member_rss_link() ?>" title="<?php _e( 'RSS Feed', 'buddypress' ) ?>"><?php _e( 'RSS Feed', 'buddypress' ) ?></a></h4>

				<?php if ( $friend_ids = bp_get_friend_ids() ) : ?>

					<?php if ( bp_has_activities( 'user_id=' . $friend_ids . '&per_page=25&max=500&display_comments=stream' ) ) : ?>

						<div class="pagination">

							<div class="pag-count" id="activity-count">
								<?php bp_activity_pagination_count() ?>
							</div>

							<div class="pagination-links" id="activity-pag">
								&nbsp; <?php bp_activity_pagination_links() ?>
							</div>

						</div>

						<ul id="activity-list" class="item-list activity-list">
						<?php while ( bp_activities() ) : bp_the_activity(); ?>
							<li class="<?php bp_activity_css_class() ?>">
								<div class="activity-avatar">
									<?php bp_activity_avatar() ?>
								</div>

								<?php bp_activity_content() ?>

								<?php do_action( 'bp_friends_activity_item' ) ?>
							</li>
						<?php endwhile; ?>
						</ul>

						<?php do_action( 'bp_friends_activity_content' ) ?>

					<?php endif; ?>

				<?php else: ?>

					<div id="message" class="info">
						<p><?php _e( "Your friends haven't done anything yet.", 'buddypress' )  ?></p>
					</div>

				<?php endif;?>
			</div>

		</div>

		<?php do_action( 'bp_after_friends_activity_content' ) ?>

	</div>

<?php get_footer() ?>

