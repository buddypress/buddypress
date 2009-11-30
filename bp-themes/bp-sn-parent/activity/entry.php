<?php /* This template is used by AJAX functions to load in new updates */ ?>
<li class="<?php bp_activity_css_class() ?> new-update" id="activity-<?php bp_activity_id() ?>">
	<div class="activity-avatar">
		<?php bp_activity_avatar('width=40&height=40') ?>
	</div>

	<div class="activity-content">
		<?php bp_activity_content() ?>

		<?php if ( is_user_logged_in() ) : ?>
		<div class="activity-meta">
			<a href="#acomment-<?php bp_activity_id() ?>" class="acomment-reply" id="acomment-comment-<?php bp_activity_id() ?>"><?php _e( 'Comment', 'buddypress' ) ?> (<?php bp_activity_comment_count() ?>)</a>
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