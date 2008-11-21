<div id="wire-post-new">
	<form action="<?php bp_wire_get_action() ?>" id="wire-post-new-form" method="post">
		<div id="wire-post-new-metadata">
			<?php printf ( __( 'On %1$s %2$s said:', "buddypress" ), bp_wire_poster_date( null, false ), bp_wire_poster_name( false ) ) ?>
		</div>
	
		<div id="wire-post-new-input">
			<textarea name="wire-post-textarea" id="wire-post-textarea"></textarea>

			<?php if ( bp_wire_show_email_notify() ) : ?>
				<p><input type="checkbox" name="wire-post-email-notify" id="wire-post-email-notify" value="1" /> <?php _e( 'Notify members via email (will slow down posting)', 'buddypress' ) ?></p>
			<?php endif; ?>
			
			<input type="submit" name="wire-post-submit" id="wire-post-submit" value="Post &raquo;" />
		</div>
		
	</form>
</div>