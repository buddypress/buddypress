<?php
/*
 * /wire/post-form.php
 * This is the wire post box, where users can post to a wire that is being displayed on any page.
 * 
 * Loaded by: 'wire/post-list.php'
 */
?>
<div id="wire-post-new">
	
	<form action="<?php bp_wire_get_action() ?>" id="wire-post-new-form" method="post">
		
		<div id="wire-post-new-metadata">
			<?php bp_wire_poster_avatar() ?>
			<?php printf ( __( 'On %1$s %2$s said:', "buddypress" ), bp_get_wire_poster_date(), bp_get_wire_poster_name() ) ?>
		</div>
	
		<div id="wire-post-new-input">
			
			<?php do_action( 'bp_wire_custom_wire_boxes_before' ) ?>
			
			<textarea name="wire-post-textarea" id="wire-post-textarea"></textarea>

			<?php if ( bp_wire_show_email_notify() ) : ?>
				
				<p><input type="checkbox" name="wire-post-email-notify" id="wire-post-email-notify" value="1" /> <?php _e( 'Notify members via email (will slow down posting)', 'buddypress' ) ?></p>
			
			<?php endif; ?>
			
			<?php do_action( 'bp_wire_custom_wire_boxes_after' ) ?>
			
			<input type="submit" name="wire-post-submit" id="wire-post-submit" value="<?php _e( 'Post &raquo;', 'buddypress' ) ?>" />
			
			<?php wp_nonce_field( 'bp_wire_post' ) ?>
			
			<input type="hidden" name="bp_wire_item_id" id="bp_wire_item_id" value="<?php bp_wire_item_id() ?>" />
			
		</div>
		
	</form>
	
</div>