<?php
/**
 * BuddyPress - Members Single Profile
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 * @version 12.0.0
 */

/** This action is documented in bp-templates/bp-legacy/buddypress/members/single/settings/profile.php */
do_action( 'bp_before_member_settings_template' ); ?>

<h2 class="bp-screen-reader-text">
	<?php
		/* translators: accessibility text */
		esc_html_e( 'Account settings', 'buddypress' );
	?>
</h2>

<form action="<?php bp_displayed_user_link( array( bp_get_settings_slug(), 'general' ) ); ?>" method="post" class="standard-form" id="settings-form">

	<?php if ( ! is_super_admin() ) : ?>

		<label for="pwd">
			<?php
			/* translators: %s: the required text information. */
			printf( esc_html__( 'Current Password %s', 'buddypress' ), '<span>' . esc_html_x( '(required to update email or change current password)', 'required text information', 'buddypress' ) . '</span>' );
			?>
		</label>
		<input type="password" name="pwd" id="pwd" size="16" value="" class="settings-input small" <?php bp_form_field_attributes( 'password' ); ?>/> &nbsp;<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Lost your password?', 'buddypress' ); ?></a>

	<?php endif; ?>

	<label for="email"><?php esc_html_e( 'Account Email', 'buddypress' ); ?></label>
	<input type="email" name="email" id="email" value="<?php echo esc_attr( bp_get_displayed_user_email() ); ?>" class="settings-input" <?php bp_form_field_attributes( 'email' ); ?>/>

	<label for="pass1">
		<?php
		/* translators: %s: Information about how to keep password unchanged. */
		printf( esc_html__( 'Change Password %s', 'buddypress' ), '<span>' . esc_html__( '(leave blank for no change)', 'buddypress' ) . '</span>' );
		?>
	</label>
	<input type="password" name="pass1" id="pass1" size="16" value="" class="settings-input small password-entry" <?php bp_form_field_attributes( 'password' ); ?>/>
	<div id="pass-strength-result"></div>
	<label for="pass2"><?php esc_html_e( 'Repeat New Password', 'buddypress' );
	?></label>
	<input type="password" name="pass2" id="pass2" size="16" value="" class="settings-input small password-entry-confirm" <?php bp_form_field_attributes( 'password' ); ?>/>

	<?php

	/**
	 * Fires before the display of the submit button for user general settings saving.
	 *
	 * @since 1.5.0
	 */
	do_action( 'bp_core_general_settings_before_submit' ); ?>

	<div class="submit">
		<input type="submit" name="submit" value="<?php esc_attr_e( 'Save Changes', 'buddypress' ); ?>" id="submit" class="auto" />
	</div>

	<?php

	/**
	 * Fires after the display of the submit button for user general settings saving.
	 *
	 * @since 1.5.0
	 */
	do_action( 'bp_core_general_settings_after_submit' ); ?>

	<?php wp_nonce_field( 'bp_settings_general' ); ?>

</form>

<?php

/** This action is documented in bp-templates/bp-legacy/buddypress/members/single/settings/profile.php */
do_action( 'bp_after_member_settings_template' );
