<?php
/**
 * BP Nouveau Activity Comment form template.
 *
 * @since 3.0.0
 * @version 3.0.0
 */

if ( ! bp_nouveau_current_user_can( 'comment_activity' ) || ! bp_activity_can_comment() ) {
	return;
} ?>

<form action="<?php bp_activity_comment_form_action(); ?>" method="post" id="ac-form-<?php bp_activity_id(); ?>" class="ac-form"<?php bp_activity_comment_form_nojs_display(); ?>>

	<div class="ac-reply-avatar"><?php bp_loggedin_user_avatar( array( 'type' => 'thumb' ) ); ?></div>
	<div class="ac-reply-content">
		<div class="ac-textarea">
			<label for="ac-input-<?php bp_activity_id(); ?>" class="bp-screen-reader-text">
				<?php echo esc_html( _x( 'Comment', 'heading', 'buddypress' ) ); ?>
			</label>
			<textarea id="ac-input-<?php bp_activity_id(); ?>" class="ac-input bp-suggestions" name="ac_input_<?php bp_activity_id(); ?>"></textarea>
		</div>
		<?php
		printf(
			'<input type="submit" name="ac_form_submit" value="%s" /> &nbsp; <button type="button" class="ac-reply-cancel">%s</button>',
			esc_attr( _x( 'Post', 'button', 'buddypress' ) ),
			esc_html( _x( 'Cancel', 'button', 'buddypress' ) )
		);
		?>
		<input type="hidden" name="comment_form_id" value="<?php bp_activity_id(); ?>" />
	</div>

	<?php wp_nonce_field( 'new_activity_comment', '_wpnonce_new_activity_comment' ); ?>

</form>
