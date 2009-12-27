<?php /* Querystring is set via AJAX in _inc/ajax.php - bp_dtheme_activity_loop() */ ?>
<?php if ( bp_has_activities( bp_ajax_querystring() ) ) : ?>

	<?php if ( empty( $_POST['page'] ) ) : ?>
		<ul id="activity-stream" class="activity-list item-list">
	<?php endif; ?>

	<?php while ( bp_activities() ) : bp_the_activity(); ?>

		<li class="<?php bp_activity_css_class() ?>" id="activity-<?php bp_activity_id() ?>">
			<div class="activity-avatar">
				<?php bp_activity_avatar( 'type=full&width=60&height=60' ) ?>
			</div>

			<div class="activity-content">
				<?php bp_activity_content() ?>

				<div class="activity-meta">
					<?php if ( is_user_logged_in() && bp_activity_can_comment() ) : ?>
						<a href="#acomment-<?php bp_activity_id() ?>" class="acomment-reply" id="acomment-comment-<?php bp_activity_id() ?>"><?php _e( 'Comment', 'buddypress' ) ?> (<?php bp_activity_comment_count() ?>)</a>
					<?php endif; ?>

					<?php if ( !bp_is_activity_permalink() ) : ?>
						<a href="<?php bp_activity_thread_permalink() ?>" class="view" title="<?php _e( 'View Thread', 'buddypress' ) ?>"><?php _e( 'View Thread', 'buddypress' ) ?></a>
					<?php endif; ?>

					<?php if ( !bp_get_activity_is_favorite() ) : ?>
						<a href="" class="fav" title="<?php _e( 'Mark Favorite', 'buddypress' ) ?>"><?php _e( 'Mark Favorite', 'buddypress' ) ?></a>
					<?php else : ?>
						<a href="" class="unfav" title="<?php _e( 'Remove Favorite', 'buddypress' ) ?>"><?php _e( 'Remove Favorite', 'buddypress' ) ?></a>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( 'activity_comment' == bp_get_activity_action_name() ) : ?>
				<div class="activity-inreplyto">
					<strong><?php _e( 'In reply to', 'buddypress' ) ?></strong> - <?php bp_activity_parent_content() ?>
				</div>
			<?php endif; ?>

			<?php if ( bp_activity_can_comment() ) : ?>
				<div class="activity-comments">
					<?php bp_activity_comments() ?>

					<?php if ( is_user_logged_in() ) : ?>
					<form action="" method="post" name="activity-comment-form" id="ac-form-<?php bp_activity_id() ?>" class="ac-form">
						<div class="ac-reply-avatar"><?php bp_loggedin_user_avatar( 'width=25&height=25' ) ?></div>
						<div class="ac-reply-content">
							<div class="ac-textarea">
								<textarea id="ac-input-<?php bp_activity_id() ?>" class="ac-input" name="ac-input-<?php bp_activity_id() ?>"></textarea>
							</div>
							<input type="submit" name="ac-form-submit" value="<?php _e( 'Post', 'buddypress' ) ?> &rarr;" /> &nbsp; <?php _e( 'or press esc to cancel.', 'buddypress' ) ?>
						</div>
						<?php wp_nonce_field( 'new_activity_comment', '_wpnonce_new_activity_comment' ) ?>
					</form>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</li>

	<?php endwhile; ?>

		<li class="load-more">
			<a href="#more"><?php _e( 'Load More', 'buddypress' ) ?></a> &nbsp; <span class="ajax-loader"></span>
		</li>

	<?php if ( empty( $_POST['page'] ) ) : ?>
		</ul>
	<?php endif; ?>

<?php else : ?>
	<div id="message" class="info">
		<p><?php _e( 'No activity found.', 'buddypress' ) ?></p>
	</div>
<?php endif; ?>

<form action="" name="activity-loop-form" id="activity-loop-form" method="post">
	<?php wp_nonce_field( 'activity_filter', '_wpnonce_activity_filter' ) ?>
</form>