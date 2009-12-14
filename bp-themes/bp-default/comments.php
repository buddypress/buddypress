	<?php
		if ( post_password_required() ) :
			echo '<h3 class="comments-header">' . __('Password Protected', 'buddypress') . '</h3>';
			echo '<p class="alert password-protected">' . __('Enter the password to view comments.', 'buddypress') . '</p>';
			return;
		endif;

		if ( is_page() && !have_comments() && !comments_open() && !pings_open() )
			return;
	?>

	<div id="comments-template">

		<?php if ( have_comments() ) : ?>

			<div id="comments">

				<h3 id="comments-number" class="comments-header"><?php comments_number( sprintf( __('No responses to %1$s', 'buddypress'), the_title( '&#8220;', '&#8221;', false ) ), sprintf( __('One response to %1$s', 'buddypress'), the_title( '&#8220;', '&#8221;', false ) ), sprintf( __('%1$s responses to %2$s', 'buddypress'), '%', the_title( '&#8220;', '&#8221;', false ) ) ); ?></h3>

				<?php do_action( 'bp_before_blog_comment_list' ) ?>

				<ol class="commentlist">
					<?php wp_list_comments( array( 'style' => 'ol', 'type' => 'all' ) ); ?>
				</ol><!-- .comment-list -->

				<?php do_action( 'bp_after_blog_comment_list' ) ?>

				<?php if ( get_option( 'page_comments' ) ) : ?>

					<div class="comment-navigation paged-navigation">

						<?php paginate_comments_links(); ?>

					</div>
				<?php endif; ?>

			</div><!-- #comments -->

		<?php else : ?>

			<?php if ( pings_open() && !comments_open() && is_single() ) : ?>

				<p class="comments-closed pings-open">
					<?php printf( __('Comments are closed, but <a href="%1$s" title="Trackback URL for this post">trackbacks</a> and pingbacks are open.', 'buddypress'), trackback_url( '0' ) ); ?>
				</p>

			<?php elseif ( !comments_open() && is_single() ) : ?>

				<p class="comments-closed">
					<?php _e('Comments are closed.', 'buddypress'); ?>
				</p>

			<?php endif; ?>

		<?php endif; ?>

		<?php if ( comments_open() ) : ?>

			<div id="respond">

				<h3 id="reply" class="comments-header">
					<?php comment_form_title( __('Leave a Reply', 'buddypress'), __('Leave a Reply to %s', 'buddypress'), true ); ?>
				</h3>

				<p id="cancel-comment-reply">
					<?php cancel_comment_reply_link( __('Click here to cancel reply.', 'buddypress') ); ?>
				</p>

				<?php if ( get_option( 'comment_registration' ) && !$user_ID ) : ?>

					<p class="alert">
						<?php printf( __('You must be <a href="%1$s" title="Log in">logged in</a> to post a comment.', 'buddypress'), wp_login_url( get_permalink() ) ); ?>
					</p>

				<?php else : ?>

					<?php do_action( 'bp_before_blog_comment_form' ) ?>

					<form action="<?php echo get_option( 'siteurl' ); ?>/wp-comments-post.php" method="post" id="commentform" class="standard-form">

						<?php if ( $user_ID ) : ?>

							<p class="log-in-out">
								<?php printf( __('Logged in as <a href="%1$s" title="%2$s">%2$s</a>.', 'buddypress'), bp_loggedin_user_domain(), $user_identity ); ?> <a href="<?php echo wp_logout_url( get_permalink() ); ?>" title="<?php _e('Log out of this account', 'buddypress'); ?>"><?php _e('Log out &raquo;', 'buddypress'); ?></a>
							</p>

						<?php else : ?>

							<?php $req = get_option( 'require_name_email' ); ?>

							<p class="form-author">
								<label for="author"><?php _e('Name', 'buddypress'); ?> <?php if ( $req ) : ?><span class="required"><?php _e('*', 'buddypress'); ?></span><?php endif; ?></label>
								<input type="text" class="text-input" name="author" id="author" value="<?php echo $comment_author; ?>" size="40" tabindex="1" />
							</p>

							<p class="form-email">
								<label for="email"><?php _e('Email', 'buddypress'); ?>  <?php if ( $req ) : ?><span class="required"><?php _e('*', 'buddypress'); ?></span><?php endif; ?></label>
								<input type="text" class="text-input" name="email" id="email" value="<?php echo $comment_author_email; ?>" size="40" tabindex="2" />
							</p>

							<p class="form-url">
								<label for="url"><?php _e('Website', 'buddypress'); ?></label>
								<input type="text" class="text-input" name="url" id="url" value="<?php echo $comment_author_url; ?>" size="40" tabindex="3" />
							</p>

						<?php endif; ?>

						<p class="form-textarea">
							<label for="comment"><?php _e('Comment', 'buddypress'); ?></label>
							<textarea name="comment" id="comment" cols="60" rows="10" tabindex="4"></textarea>
						</p>

						<?php do_action( 'bp_blog_comment_form' ) ?>

						<p class="form-submit">
							<input class="submit-comment button" name="submit" type="submit" id="submit" tabindex="5" value="<?php _e('Submit', 'buddypress'); ?>" />
							<?php comment_id_fields(); ?>
						</p>

						<div class="comment-action">
							<?php do_action( 'comment_form', $post->ID ); ?>
						</div>

					</form>

					<?php do_action( 'bp_after_blog_comment_form' ) ?>

				<?php endif; ?>

			</div>

		<?php endif; ?>

	</div>
