<?php
/**
 * BuddyPress - Activity Stream Comment
 *
 * This template is used by bp_activity_comments() functions to show
 * each activity.
 *
 * @since 3.0.0
 * @version 12.0.0
 */

bp_nouveau_activity_hook( 'before', 'comment' ); ?>

<li id="acomment-<?php bp_activity_comment_id(); ?>" class="comment-item" <?php bp_nouveau_activity_comment_data_attribute_id(); ?>>
	<div class="acomment-avatar item-avatar">
		<a href="<?php bp_activity_comment_user_link(); ?>">
			<?php
			bp_activity_avatar(
				array(
					'type'    => 'thumb',
					'user_id' => bp_get_activity_comment_user_id(),
				)
			);
			?>
		</a>
	</div>

	<div class="acomment-meta">

		<?php bp_nouveau_activity_comment_action(); ?>

	</div>

	<div class="acomment-content"><?php bp_activity_comment_content(); ?></div>

	<?php bp_nouveau_activity_comment_buttons( array( 'container' => 'div' ) ); ?>

	<?php bp_nouveau_activity_recurse_comments( bp_activity_current_comment() ); ?>
</li>

<?php
bp_nouveau_activity_hook( 'after', 'comment' );
