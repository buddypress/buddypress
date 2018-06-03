<?php
/**
 * BP Nouveau Group's delete group template.
 *
 * @since 3.0.0
 * @version 3.1.0
 */
?>

<h2 class="bp-screen-title warn">
	<?php esc_html_e( 'Delete this group', 'buddypress' ); ?>
</h2>

<?php bp_nouveau_user_feedback( 'group-delete-warning' ); ?>

<label for="delete-group-understand" class="bp-label-text warn">
	<input type="checkbox" name="delete-group-understand" id="delete-group-understand" value="1" onclick="if(this.checked) { document.getElementById( 'delete-group-button' ).disabled = ''; } else { document.getElementById( 'delete-group-button' ).disabled = 'disabled'; }" />
	<?php esc_html_e( 'I understand the consequences of deleting this group.', 'buddypress' ); ?>
</label>
