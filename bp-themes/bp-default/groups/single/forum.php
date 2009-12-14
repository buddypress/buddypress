<?php do_action( 'template_notices' ) ?>

<?php if ( bp_is_group_forum_topic() ) : ?>

	<?php locate_template( array( 'groups/single/forum/topic.php' ), true ) ?>

<?php else : ?>

	<div class="forums single-forum">
		<?php // 'forums/topic-loop.php' loaded here via AJAX. ?>
	</div>

	<?php if ( ( is_user_logged_in() && 'public' == bp_get_group_status() ) || bp_group_is_member() ) : ?>

		<form action="" method="post" id="forum-topic-form" class="standard-form">
			<div id="post-new-topic">

				<?php do_action( 'groups_forum_new_topic_before' ) ?>

				<?php if ( !bp_group_is_member() ) : ?>
					<p><?php _e( 'You will auto join this group when you start a new topic.', 'buddypress' ) ?></p>
				<?php endif; ?>

				<a name="post-new"></a>
				<h3><?php _e( 'Post a New Topic:', 'buddypress' ) ?></h3>

				<label><?php _e( 'Title:', 'buddypress' ) ?></label>
				<input type="text" name="topic_title" id="topic_title" value="" />

				<label><?php _e( 'Content:', 'buddypress' ) ?></label>
				<textarea name="topic_text" id="topic_text"></textarea>

				<label><?php _e( 'Tags (comma separated):', 'buddypress' ) ?></label>
				<input type="text" name="topic_tags" id="topic_tags" value="" />

				<?php do_action( 'groups_forum_new_topic_after' ) ?>

				<div class="submit">
					<input type="submit" name="submit_topic" id="submit" value="<?php _e( 'Post Topic', 'buddypress' ) ?>" />
				</div>

				<?php wp_nonce_field( 'bp_forums_new_topic' ) ?>
			</div>
		</form>

	<?php endif; ?>

<?php endif; ?>

