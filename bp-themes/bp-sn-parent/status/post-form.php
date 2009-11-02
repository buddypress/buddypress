<?php do_action( 'bp_before_status_update_form' ) ?>

<form action="<?php bp_status_form_action() ?>" id="status-update-form" method="post" class="standard-form">
	<?php do_action( 'bp_before_status_update_input' ) ?>

	<label for="status-update-input"><?php _e( 'What are you up to?', 'buddypress' ) ?></label>
	<textarea id="status-update-input" name="status-update-input" tabindex="99"></textarea>

	<?php do_action( 'bp_after_status_update_input' ) ?>

	<div id="status-update-buttons">
		<input type="submit" name="status-update-post" id="status-update-post" tabindex="100" value="<?php _e( 'Update', 'buddypress' ) ?>" />

		<?php do_action( 'bp_status_update_buttons' ) ?>
	</div>

	<?php wp_nonce_field( 'bp_status_add_status', '_wpnonce_add_status' ) ?>
</form>

<?php do_action( 'bp_after_status_update_form' ) ?>
