<?php
/**
 * BuddyPress - Members Settings ( General )
 *
 * @since 3.0.0
 * @version 12.0.0
 */

bp_nouveau_member_hook( 'before', 'settings_template' ); ?>

<h2 class="screen-heading general-settings-screen">
	<?php esc_html_e( 'Email & Password', 'buddypress' ); ?>
</h2>

<p class="info email-pwd-info">
	<?php esc_html_e( 'Update your email and or password.', 'buddypress' ); ?>
</p>

<form action="<?php bp_displayed_user_link( array( bp_nouveau_get_component_slug( 'settings' ), 'general' ) ); ?>" method="post" class="standard-form" id="your-profile">

	<?php if ( ! is_super_admin() ) : ?>

		<label for="pwd">
			<?php
			/* translators: %s: email requirement explanations */
			printf( esc_html__( 'Current Password %s', 'buddypress' ), '<span>' . esc_html__( '(required to update email or change current password)', 'buddypress' ) . '</span>' );
			?>
		</label>
		<input type="password" name="pwd" id="pwd" value="" size="24" class="settings-input small" <?php bp_form_field_attributes( 'password' ); ?>/> &nbsp;<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Lost your password?', 'buddypress' ); ?></a>

	<?php endif; ?>

	<label for="email"><?php esc_html_e( 'Account Email', 'buddypress' ); ?></label>
	<input type="email" name="email" id="email" value="<?php echo esc_attr( bp_get_displayed_user_email() ); ?>" class="settings-input" <?php bp_form_field_attributes( 'email' ); ?>/>

	<div class="info bp-feedback">
		<span class="bp-icon" aria-hidden="true"></span>
		<p class="text"><?php esc_html_e( 'Click on the "Generate Password" button to change your password.', 'buddypress' ); ?></p>
	</div>

	<div class="user-pass1-wrap">
		<button type="button" class="button wp-generate-pw">
			<?php esc_html_e( 'Generate Password', 'buddypress' ); ?>
		</button>

		<div class="wp-pwd">
			<label for="pass1"><?php esc_html_e( 'Add Your New Password', 'buddypress' ); ?></label>
			<span class="password-input-wrapper">
				<input type="password" name="pass1" id="pass1" size="24" class="settings-input small password-entry" value="" <?php bp_form_field_attributes( 'password', array( 'data-pw' => wp_generate_password( 24 ), 'aria-describedby' => 'pass-strength-result' ) ); ?> />
			</span>
			<button type="button" class="button wp-hide-pw" data-toggle="0" aria-label="<?php esc_attr_e( 'Hide password', 'buddypress' ); ?>">
				<span class="dashicons dashicons-hidden" aria-hidden="true"></span>
				<span class="text bp-screen-reader-text"><?php esc_html_e( 'Hide', 'buddypress' ); ?></span>
			</button>
			<button type="button" class="button wp-cancel-pw" data-toggle="0" aria-label="<?php esc_attr_e( 'Cancel password change', 'buddypress' ); ?>">
				<span class="text"><?php esc_html_e( 'Cancel', 'buddypress' ); ?></span>
			</button>
			<div id="pass-strength-result" aria-live="polite"></div>
		</div>
	</div>

	<div class="user-pass2-wrap">
		<label class="label" for="pass2"><?php esc_html_e( 'Repeat Your New Password', 'buddypress' ); ?></label>
		<input name="pass2" type="password" id="pass2" size="24" class="settings-input small password-entry-confirm" value="" <?php bp_form_field_attributes( 'password' ); ?> />
	</div>

	<div class="pw-weak">
		<label>
			<input type="checkbox" name="pw_weak" class="pw-checkbox" />
			<span id="pw-weak-text-label"><?php esc_html_e( 'Confirm use of potentially weak password', 'buddypress' ); ?></span>
		</label>
	</div>

	<?php bp_nouveau_submit_button( 'members-general-settings' ); ?>

</form>

<?php
bp_nouveau_member_hook( 'after', 'settings_template' );
