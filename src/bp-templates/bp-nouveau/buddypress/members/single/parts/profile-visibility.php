<?php
/**
 * BuddyPress - Members Single Profile Edit Field visibility
 *
 * @since 3.0.0
 * @version 3.0.0
 */

if ( empty( $GLOBALS['profile_template'] ) ) {
	return;
}
?>

<?php if ( bp_current_user_can( 'bp_xprofile_change_field_visibility' ) ) : ?>

	<p class="field-visibility-settings-toggle field-visibility-settings-header" id="field-visibility-settings-toggle-<?php bp_the_profile_field_id(); ?>">

		<?php
		printf(
			__( 'This field may be seen by: %s', 'buddypress' ),
			'<span class="current-visibility-level">' . bp_get_the_profile_field_visibility_level_label() . '</span>'
		);
		?>
		<button class="visibility-toggle-link text-button" type="button"><?php _e( 'Change', 'buddypress' ); ?></button>

	</p>

	<div class="field-visibility-settings" id="field-visibility-settings-<?php bp_the_profile_field_id(); ?>">
		<fieldset>
			<legend><?php _e( 'Who is allowed to see this field?', 'buddypress' ); ?></legend>

			<?php bp_profile_visibility_radio_buttons(); ?>

		</fieldset>
		<button class="field-visibility-settings-close button" type="button"><?php _e( 'Close', 'buddypress' ); ?></button>
	</div>

<?php else : ?>

	<p class="field-visibility-settings-notoggle field-visibility-settings-header" id="field-visibility-settings-toggle-<?php bp_the_profile_field_id(); ?>">
		<?php
		printf(
			__( 'This field may be seen by: %s', 'buddypress' ),
			'<span class="current-visibility-level">' . bp_get_the_profile_field_visibility_level_label() . '</span>'
		);
		?>
	</p>

<?php endif;
