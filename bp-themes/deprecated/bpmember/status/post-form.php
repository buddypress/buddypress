<form action="<?php bp_status_form_action() ?>" id="status-update-form" method="post">
	<label for="status-update-input"><?php _e( 'What are you up to?', 'buddypress' ) ?></label>
	<textarea id="status-update-input" name="status-update-input" tabindex="99"></textarea>
	
	<div id="status-update-buttons">
		<input type="submit" name="status-update-post" id="status-update-post" tabindex="100" value="<?php _e( 'Update', 'buddypress' ) ?>" />
	</div>
	
	<?php wp_nonce_field( 'bp_status_add_status', '_wpnonce_add_status' ) ?>
</form>