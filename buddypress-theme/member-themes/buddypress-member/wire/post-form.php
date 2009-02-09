<div id="wire-post-new">
	<form action="<?php bp_wire_get_action() ?>" id="wire-post-new-form" method="post">
		<div id="wire-post-new-metadata">
			<?php bp_wire_poster_avatar() ?>
			<?php printf ( __( 'On %1$s %2$s said:', "buddypress" ), bp_wire_poster_date( null, false ), bp_wire_poster_name( false ) ) ?>
		</div>
	
		<div id="wire-post-new-input">
			
			<?php bp_custom_wire_boxes_before() ?>
			
			<textarea name="wire-post-textarea" id="wire-post-textarea"></textarea>

			<?php if ( bp_wire_show_email_notify() ) : ?>
				<p><input type="checkbox" name="wire-post-email-notify" id="wire-post-email-notify" value="1" /> <?php _e( 'Notify members via email (will slow down posting)', 'buddypress' ) ?></p>
			<?php endif; ?>
			
			<?php bp_custom_wire_boxes_after() ?>
			
			<input type="submit" name="wire-post-submit" id="wire-post-submit" value="Post &raquo;" />
			
			<?php wp_nonce_field( 'bp_wire_post' ) ?>
			
		</div>
		
	</form>
</div>