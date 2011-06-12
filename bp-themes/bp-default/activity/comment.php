<?php

/**
 * BuddyPress - Activity Stream Comment
 *
 * This template is used by bp_activity_comments() functions to show
 * each activity.
 *
 * @package BuddyPress
 * @subpackage bp-default
 */
 
?>

<?php do_action( 'bp_before_activity_comment' ); ?>

<li id="acomment-<?php bp_activity_comment_id() ?>">
	<div class="acomment-avatar">
		<a href="<?php bp_activity_comment_user_link() ?>">
			<?php bp_activity_avatar( 'type=full&width=30&height=30&user_id=' . bp_get_activity_comment_user_id() ); ?>
		</a>
	</div>

	<div class="acomment-meta">
		<a href="<?php bp_activity_comment_user_link() ?>"><?php bp_activity_comment_name() ?></a> &middot; <?php bp_activity_comment_date_recorded() ?>

		<?php if ( is_user_logged_in() && bp_activity_can_comment_reply( bp_activity_current_comment() ) ) : ?>
			<span class="acomment-replylink"> &middot; <a href="#acomment-<?php bp_activity_comment_id() ?>" class="acomment-reply" id="acomment-reply-<?php bp_activity_id() ?>"><?php _e( 'Reply', 'buddypress' ) ?></a></span>
		<?php endif ?>
	
		<?php if ( bp_activity_user_can_delete() ) : ?>
			&middot; <a href="<?php bp_activity_comment_delete_link() ?>" class="delete acomment-delete confirm" rel="nofollow"><?php _e( 'Delete', 'buddypress' ) ?></a>
		<?php endif ?>

	</div>
	
	
	<div class="acomment-content">
		<?php bp_activity_comment_content() ?>
	</div>
	
	<?php bp_activity_recurse_comments( bp_activity_current_comment() ) ?>
</li>

<?php do_action( 'bp_after_activity_comment' ); ?>
