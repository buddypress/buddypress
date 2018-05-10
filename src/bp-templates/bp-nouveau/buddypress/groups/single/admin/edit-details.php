<?php
/**
 * BP Nouveau Group's edit details template.
 *
 * @since 3.0.0
 * @version 3.0.0
 */
?>

<?php if ( bp_is_group_create() ) : ?>

	<h3 class="bp-screen-title creation-step-name">
		<?php _e( 'Enter Group Name &amp; Description', 'buddypress' ); ?>
	</h3>

<?php else : ?>

	<h2 class="bp-screen-title">
		<?php _e( 'Edit Group Name &amp; Description', 'buddypress' ); ?>
	</h2>

<?php endif; ?>

<label for="group-name"><?php _e( 'Group Name (required)', 'buddypress' ); ?></label>
<input type="text" name="group-name" id="group-name" value="<?php bp_is_group_create() ? bp_new_group_name() : bp_group_name(); ?>" aria-required="true" />

<label for="group-desc"><?php _e( 'Group Description (required)', 'buddypress' ); ?></label>
<textarea name="group-desc" id="group-desc" aria-required="true"><?php bp_is_group_create() ? bp_new_group_description() : bp_group_description_editable(); ?></textarea>

<?php if ( ! bp_is_group_create() ) : ?>
	<p class="bp-controls-wrap">
		<label for="group-notify-members" class="bp-label-text">
			<input type="checkbox" name="group-notify-members" id="group-notify-members" value="1" /> <?php _e( 'Notify group members of these changes via email', 'buddypress' ); ?>
		</label>
	</p>
<?php endif; ?>
