<?php get_header() ?>

	<div class="content-header">

	</div>

	<div id="content">

		<?php do_action( 'template_notices' ) // (error/success feedback) ?>

		<?php if ( bp_has_groups() ) : while ( bp_groups() ) : bp_the_group(); ?>

			<?php do_action( 'bp_before_group_content' ) ?>

			<div class="left-menu">
				<?php locate_template( array( 'groups/single/menu.php' ), true ) ?>
			</div>

			<div class="main-column">
				<div class="inner-tube">

					<?php do_action( 'bp_before_group_name' ) ?>

					<div id="group-name">
						<h1><a href="<?php bp_group_permalink() ?>" title="<?php bp_group_name() ?>"><?php bp_group_name() ?></a></h1>
						<p class="status"><?php bp_group_type() ?></p>
					</div>

					<?php do_action( 'bp_after_group_name' ) ?>

					<?php if ( !bp_group_is_visible() ) : ?>

						<?php do_action( 'bp_before_group_status_message' ) ?>

						<div id="message" class="info">
							<p><?php bp_group_status_message() ?></p>
						</div>

						<?php do_action( 'bp_after_group_status_message' ) ?>

					<?php endif; ?>

					<?php if ( bp_group_is_visible() && bp_group_has_news() ) : ?>

						<?php do_action( 'bp_before_group_news' ) ?>

						<div class="bp-widget">
							<h4><?php _e( 'News', 'buddypress' ); ?></h4>
							<p><?php bp_group_news() ?></p>
						</div>

						<?php do_action( 'bp_after_group_news' ) ?>

					<?php endif; ?>

					<?php if ( function_exists( 'bp_has_activities' ) && bp_group_is_visible() ) : ?>

						<div class="bp-widget">
							<h4><?php _e( 'Group Activity', 'buddypress' ); ?></h4>

							<?php if ( is_user_logged_in() && bp_group_is_member() ) : ?>

							<form action="" method="post" id="whats-new-form" name="whats-new-form">
								<div id="whats-new-avatar">
									<?php bp_loggedin_user_avatar('width=40&height=40') ?>
									<span class="loading"></span>
								</div>

								<h5><?php printf( __( "What's new in %s?", 'buddypress' ), bp_get_group_name() ) ?></h5>

								<div id="whats-new-content">
									<div id="whats-new-textarea">
										<textarea name="whats-new" id="whats-new" value="" /></textarea>
									</div>

									<div id="whats-new-submit">
										<span class="ajax-loader"></span>
										<input type="submit" name="whats-new-submit" id="whats-new-submit" value="<?php _e( 'Post Update', 'buddypress' ) ?>" />
									</div>

									<input type="hidden" name="whats-new-post-in" id="whats-new-post-in" value="<?php bp_group_id() ?>" />

								</div>

								<?php wp_nonce_field( 'post_update', '_wpnonce_post_update' ); ?>
							</form>

							<?php endif; ?>

						<div class="activity">
						<?php if ( bp_has_activities( 'object=groups&primary_id=' . bp_get_group_id() . '&max=150&per_page=20&display_comments=threaded&show_hidden=1' ) ) : ?>

							<?php do_action( 'bp_before_group_activity' ) ?>

							<ul id="activity-list" class="activity-list item-list">
							<?php while ( bp_activities() ) : bp_the_activity(); ?>
								<li class="<?php bp_activity_css_class() ?>" id="activity-<?php bp_activity_id() ?>">
									<div class="activity-avatar">
										<?php bp_activity_avatar('width=40&height=40') ?>
									</div>

									<div class="activity-content">
										<?php bp_activity_content() ?>

										<?php if ( is_user_logged_in() ) : ?>
										<div class="activity-meta">
											<a href="#acomment-' . $comment->id . '" class="acomment-reply" id="acomment-comment-<?php bp_activity_id() ?>"><?php _e( 'Comment', 'buddypress' ) ?> (<?php bp_activity_comment_count() ?>)</a>
										</div>
										<?php endif; ?>
									</div>

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

							<?php do_action( 'bp_after_group_activity' ) ?>

						<?php endif; ?>
						</div> <!-- .activity -->

					<?php endif; ?>
					</div> <!-- .bp-widget -->

					<?php do_action( 'groups_custom_group_boxes' ) ?>

				</div>

			</div>

			<?php do_action( 'bp_after_group_content' ) ?>

		<?php endwhile; else: ?>

			<div id="message" class="error">
				<p><?php _e("Sorry, the group does not exist.", "buddypress"); ?></p>
			</div>

		<?php endif;?>

	</div>

<?php get_footer() ?>