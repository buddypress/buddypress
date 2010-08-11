<?php do_action( 'bp_before_group_forum_content' ) ?>

<?php if ( bp_is_group_forum_topic_edit() ) : ?>
	<?php locate_template( array( 'groups/single/forum/edit.php' ), true ) ?>

<?php elseif ( bp_is_group_forum_topic() ) : ?>
	<?php locate_template( array( 'groups/single/forum/topic.php' ), true ) ?>

<?php else : ?>

	<div class="forums single-forum">
		<?php locate_template( array( 'forums/forums-loop.php' ), true ) ?>
	</div><!-- .forums.single-forum -->

<?php endif; ?>

<?php do_action( 'bp_after_group_forum_content' ) ?>

<?php if ( !bp_is_group_forum_topic_edit() && !bp_is_group_forum_topic() ) : ?>

	<?php if ( ( is_user_logged_in() && 'public' == bp_get_group_status() ) || bp_group_is_member() ) : ?>

		<form action="" method="post" id="forum-topic-form" class="standard-form">
			<div id="post-new-topic">

				<?php do_action( 'bp_before_group_forum_post_new' ) ?>

				<?php if ( bp_groups_auto_join() && !bp_group_is_member() ) : ?>
					<p><?php _e( 'You will auto join this group when you start a new topic.', 'buddypress' ) ?></p>
				<?php endif; ?>

				<p id="post-new"></p>
				<h4><?php _e( 'Post a New Topic:', 'buddypress' ) ?></h4>

				<label><?php _e( 'Title:', 'buddypress' ) ?></label>
				<input type="text" name="topic_title" id="topic_title" value="" />

				<label><?php _e( 'Content:', 'buddypress' ) ?></label>
				<textarea name="topic_text" id="topic_text"></textarea>

				<label><?php _e( 'Tags (comma separated):', 'buddypress' ) ?></label>
				<input type="text" name="topic_tags" id="topic_tags" value="" />

				<?php do_action( 'bp_after_group_forum_post_new' ) ?>

				<div class="submit">
					<input type="submit" name="submit_topic" id="submit" value="<?php _e( 'Post Topic', 'buddypress' ) ?>" />
				</div>

				<?php wp_nonce_field( 'bp_forums_new_topic' ) ?>
			</div><!-- #post-new-topic -->
		</form><!-- #forum-topic-form -->

	<?php endif; ?>

<?php endif; ?>

