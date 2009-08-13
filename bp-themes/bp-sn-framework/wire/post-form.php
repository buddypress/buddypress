<?php do_action( 'bp_before_wire_post_content' ) ?>

<div id="wire-post-new">
	
	<form action="<?php bp_wire_get_action() ?>" id="wire-post-new-form" method="post">
		<div id="wire-post-new-metadata">
			
			<?php do_action( 'bp_before_wire_post_metadata' ) ?>
			
			<?php bp_wire_poster_avatar() ?>
			<?php printf ( __( 'On %1$s %2$s said:', "buddypress" ), bp_wire_poster_date( null, false ), bp_wire_poster_name( false ) ) ?>
			
			<?php if ( bp_wire_show_email_notify() ) : ?>
				<p id="wire-email-notify"><input type="checkbox" name="wire-post-email-notify" id="wire-post-email-notify" value="1" /> <?php _e( 'Notify members via email (will slow down posting)', 'buddypress' ) ?></p>
			<?php endif; ?>
			
			<?php do_action( 'bp_after_wire_post_metadata' ) ?>
			
		</div>
	
		<div id="wire-post-new-input">
			
			<?php do_action( 'bp_before_wire_post_form' ); /* Deprecated -> */ do_action( 'bp_wire_custom_wire_boxes_after' ); ?>
			
			<textarea name="wire-post-textarea" id="wire-post-textarea"></textarea>
			
			<?php do_action( 'bp_after_wire_post_form' ); /* Deprecated -> */ do_action( 'bp_wire_custom_wire_boxes_after' ); ?>
			
			<input type="submit" name="wire-post-submit" id="wire-post-submit" value="<?php _e( 'Post &raquo;', 'buddypress' ) ?>" />
			<input type="hidden" name="bp_wire_item_id" id="bp_wire_item_id" value="<?php bp_wire_item_id() ?>" />

			<?php wp_nonce_field( 'bp_wire_post' ) ?>
			
		</div>
	</form>
	
</div>

<?php do_action( 'bp_after_wire_post_content' ) ?>