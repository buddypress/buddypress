<?php
/**
 * BP Nouveau Group's edit details template.
 *
 * @since 3.0.0
 * @version 3.1.0
 * @version 4.0.0 Removed 'Notify group members' checkbox in favor of hooked callback.
 */
?>

<?php if ( bp_is_group_create() ) : ?>

	<h3 class="bp-screen-title creation-step-name">
		<?php esc_html_e( 'Enter Group Name &amp; Description', 'buddypress' ); ?>
	</h3>

<?php else : ?>

	<h2 class="bp-screen-title">
		<?php esc_html_e( 'Edit Group Name &amp; Description', 'buddypress' ); ?>
	</h2>

<?php endif; ?>

<label for="group-name"><?php esc_html_e( 'Group Name (required)', 'buddypress' ); ?></label>
<input type="text" name="group-name" id="group-name" value="<?php if ( bp_is_group_create() ) : echo esc_attr( bp_get_new_group_name() ); else : echo esc_attr( bp_get_group_name() ); endif; ?>" aria-required="true" />

<label for="group-desc"><?php esc_html_e( 'Group Description (required)', 'buddypress' ); ?></label>
<textarea name="group-desc" id="group-desc" aria-required="true"><?php bp_is_group_create() ? bp_new_group_description() : bp_group_description_editable(); ?></textarea>
