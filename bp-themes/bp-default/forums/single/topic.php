<?php get_header( 'buddypress' ); ?>

	<div id="content">
		<div class="padder">

			<?php do_action( 'bp_before_group_home_content' ) ?>

			<?php if ( bp_has_forum_topic_posts() ) : ?>

				<div id="item-header" role="complementary">

					<?php locate_template( array( 'forums/single/forum-header.php' ), true ); ?>

				</div><!-- #item-header -->

				<div id="item-nav">
					<div class="item-list-tabs no-ajax" id="object-nav" role="navigation">
						<ul>

							<?php bp_get_options_nav(); ?>

							<?php do_action( 'bp_forum_options_nav' ); ?>

						</ul>
					</div>
				</div><!-- #item-nav -->

				<div id="item-body">

					<?php do_action( 'bp_before_group_forum_topic' ); ?>

					<form action="<?php bp_forum_topic_action() ?>" method="post" id="forum-topic-form" class="standard-form">

						<div class="item-list-tabs no-ajax" id="subnav" role="navigation">
							<ul>
								<li>
									<a href="#post-topic-reply" class="show-hide-new"><?php _e( 'New Reply', 'buddypress' ) ?></a>
								</li>

								<?php if ( bp_forums_has_directory() ) : ?>

									<li>
										<a href="<?php bp_forums_directory_permalink() ?>"><?php _e( 'Forum Directory', 'buddypress') ?></a>
									</li>

								<?php endif; ?>

							</ul>
						</div>

						<div id="topic-meta">
							<h3><?php bp_the_topic_title() ?> (<?php bp_the_topic_total_post_count() ?>)</h3>

							<?php if ( is_super_admin() || current_user_can( 'moderate' ) ) : ?>

								<div class="last admin-links">

									<?php bp_the_topic_admin_links(); ?>

								</div>

							<?php endif; ?>

							<?php do_action( 'bp_group_forum_topic_meta' ); ?>

						</div>

						<div class="pagination no-ajax">

							<div id="post-count-top" class="pag-count">

								<?php bp_the_topic_pagination_count() ?>

							</div>

							<div class="pagination-links" id="topic-pag-top">

								<?php bp_the_topic_pagination() ?>

							</div>

						</div>

						<?php do_action( 'bp_before_group_forum_topic_posts' ) ?>

						<ul id="topic-post-list" class="item-list" role="main">
							<?php while ( bp_forum_topic_posts() ) : bp_the_forum_topic_post(); ?>

								<li id="post-<?php bp_the_topic_post_id() ?>" class="<?php bp_the_topic_post_css_class() ?>">
									<div class="poster-meta">
										<a href="<?php bp_the_topic_post_poster_link() ?>">

											<?php bp_the_topic_post_poster_avatar( 'width=40&height=40' ); ?>

										</a>

										<?php echo sprintf( __( '%s said %s ago:', 'buddypress' ), bp_get_the_topic_post_poster_name(), bp_get_the_topic_post_time_since() ) ?>

									</div>

									<div class="post-content">

										<?php bp_the_topic_post_content() ?>

									</div>

									<div class="admin-links">

										<?php if ( is_super_admin() || current_user_can( 'moderate' ) ) : ?>

											<?php bp_the_topic_post_admin_links() ?>

										<?php endif; ?>

										<?php do_action( 'bp_group_forum_post_meta' ); ?>

										<a href="#post-<?php bp_the_topic_post_id() ?>" title="<?php _e( 'Permanent link to this post', 'buddypress' ) ?>">#</a>
									</div>
								</li>

							<?php endwhile; ?>
						</ul><!-- #topic-post-list -->

						<?php do_action( 'bp_after_group_forum_topic_posts' ) ?>

						<div class="pagination no-ajax">

							<div id="post-count-bottom" class="pag-count">

								<?php bp_the_topic_pagination_count() ?>

							</div>

							<div class="pagination-links" id="topic-pag-bottom">

								<?php bp_the_topic_pagination() ?>

							</div>

						</div>

						<?php if ( is_user_logged_in() ) : ?>

							<?php if ( bp_get_the_topic_is_last_page() ) : ?>

								<?php if ( bp_get_the_topic_is_topic_open() ) : ?>

									<div id="post-topic-reply">
										<p id="post-reply"></p>

										<?php do_action( 'groups_forum_new_reply_before' ) ?>

										<h4><?php _e( 'Add a reply:', 'buddypress' ) ?></h4>

										<textarea name="reply_text" id="reply_text"></textarea>

										<div class="submit">
											<input type="submit" name="submit_reply" id="submit" value="<?php _e( 'Post Reply', 'buddypress' ) ?>" />
										</div>

										<?php do_action( 'groups_forum_new_reply_after' ) ?>

										<?php wp_nonce_field( 'bp_forums_new_reply' ) ?>
									</div>

								<?php else : ?>

									<div id="message" class="info">
										<p><?php _e( 'This topic is closed, replies are no longer accepted.', 'buddypress' ) ?></p>
									</div>

								<?php endif; ?>

							<?php endif; ?>

						<?php endif; ?>

					</form><!-- #forum-topic-form -->
				</div>

			<?php else: ?>

				<div id="message" class="info">
					<p><?php _e( 'There are no posts for this topic.', 'buddypress' ) ?></p>
				</div>

			<?php endif;?>

		</div>
	</div>

<?php do_action( 'bp_after_group_forum_topic' ) ?>

<?php get_sidebar( 'buddypress' ); ?>
<?php get_footer( 'buddypress' ); ?>