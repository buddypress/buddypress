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

					<?php if ( is_user_logged_in() && bp_is_home() ) : ?>

					<form action="" method="post" id="whats-new-form" name="whats-new-form">
						<div id="whats-new-avatar">
							<?php bp_loggedin_user_avatar('width=40&height=40') ?>
							<span class="loading"></span>
						</div>

						<h5><?php printf( __( "What's new %s?", 'buddypress' ), bp_dtheme_firstname() ) ?></h5>

						<div id="whats-new-content">
							<div id="whats-new-textarea">
								<textarea name="whats-new" id="whats-new" value="" /></textarea>
							</div>

								<div id="whats-new-submit">
									<span class="ajax-loader"></span>
									<input type="submit" name="whats-new-submit" id="whats-new-submit" value="<?php _e( 'Post Update', 'buddypress' ) ?>" />
								</div>
						</div>

						<?php wp_nonce_field( 'post_update', '_wpnonce_post_update' ); ?>
					</form>

					<?php endif; ?>

					<div class="activity">

					<?php if ( bp_has_activities( 'user_id=' . bp_displayed_user_id() . '&per_page=25&max=500&display_comments=stream&show_hidden=' . bp_is_home() ) ) : ?>

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
							<p><?php echo bp_word_or_name( __( "You haven't done anything recently.", 'buddypress' ), __( "%s hasn't done anything recently.", 'buddypress' ), true, false ) ?></p>
						</div>

					<?php endif;?>

					</div>

				</div>

				<?php do_action( 'bp_after_profile_activity_widget' ) ?>

			<?php endif; ?>

			<?php do_action( 'bp_after_profile_activity_loop' ) ?>

		</div>

		<?php do_action( 'bp_after_my_activity_content' ) ?>

	</div>

<?php get_footer() ?>