<?php get_header() ?>

	<div class="content-header">
		<?php bp_last_activity() ?>
	</div>

	<div id="content">
		<?php do_action( 'template_notices' ) // (error/success feedback) ?>

		<?php do_action( 'bp_before_my_activity_content' ) ?>

		<div class="left-menu">
			<!-- Profile Menu (Avatar, Add Friend, Send Message buttons etc) -->
			<?php locate_template( array( 'profile/profile-menu.php' ), true ) ?>
		</div>

		<div class="main-column">

			<?php bp_get_profile_header() ?>

			<?php if ( function_exists( 'bp_activity_install')) : ?>

				<?php do_action( 'bp_before_profile_activity_widget' ) ?>

				<div class="bp-widget">
					<h4><?php echo bp_word_or_name( __( "My Activity", 'buddypress' ), __( "%s's Activity", 'buddypress' ), true, false ) ?> <span><a href="<?php bp_activities_member_rss_link() ?>" title="<?php _e( 'RSS Feed', 'buddypress' ) ?>"><?php _e( 'RSS Feed', 'buddypress' ) ?></a></span></h4>

					<div class="activity">

					<?php if ( bp_has_activities( 'display_comments=threaded&include=' . bp_get_activity_permalink_id() ) ) : ?>

						<ul id="activity-list" class="activity-list item-list">
						<?php while ( bp_activities() ) : bp_the_activity(); ?>

							<li class="<?php bp_activity_css_class() ?>" id="activity-<?php bp_activity_id() ?>">
								<div class="activity-avatar">
									<?php bp_activity_avatar('width=40&height=40') ?>
								</div>

								<div class="activity-content">
									<?php bp_activity_content() ?>

									<?php if ( is_user_logged_in() && 'activity_comment' != bp_get_activity_action_name() ) : ?>
										<div class="activity-meta">
											<a href="#acomment-<?php bp_activity_id() ?>" class="acomment-reply" id="acomment-comment-<?php bp_activity_id() ?>"><?php _e( 'Comment', 'buddypress' ) ?> (<?php bp_activity_comment_count() ?>)</a>
										</div>
									<?php endif; ?>
								</div>

								<?php if ( 'activity_comment' == bp_get_activity_action_name() ) : ?>
									<div class="activity-inreplyto">
										<strong><?php _e( 'In reply to', 'buddypress' ) ?></strong> - <?php bp_activity_parent_content() ?>
									</div>
								<?php endif; ?>

								<div class="activity-comments">
									<?php bp_activity_comments() ?>

									<?php if ( is_user_logged_in() ) : ?>
										<form action="" method="post" name="activity-comment-form" id="ac-form-<?php bp_activity_id() ?>" class="ac-form">
											<div class="ac-reply-avatar"><?php bp_loggedin_user_avatar( 'width=25&height=25' ) ?></div>
											<div class="ac-reply-content">
												<div class="ac-textarea">
													<textarea id="ac-input-<?php bp_activity_id() ?>" class="ac-input" name="ac-input-<?php bp_activity_id() ?>"></textarea>
												</div>
												<input type="submit" name="ac-form-submit" value="<?php _e( 'Post', 'buddypress' ) ?> &rarr;" />
											</div>
											<?php wp_nonce_field( 'new_activity_comment', '_wpnonce_new_activity_comment' ) ?>
										</form>
									<?php endif; ?>
								</div>

							</li>

						<?php endwhile; ?>
						</ul>

					<?php else: ?>

						<div id="message" class="info">
							<p><?php _e( 'There was a problem fetching that activity item.', 'buddypress' ) ?></p>
						</div>

					<?php endif; ?>

					</div>

				</div>

				<?php do_action( 'bp_after_profile_activity_widget' ) ?>

			<?php endif; ?>

			<?php do_action( 'bp_after_profile_activity_loop' ) ?>

		</div>

		<?php do_action( 'bp_after_my_activity_content' ) ?>

	</div>

<?php get_footer() ?>