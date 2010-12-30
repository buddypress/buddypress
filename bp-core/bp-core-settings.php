<?php

if ( !defined( 'BP_SETTINGS_SLUG' ) )
	define( 'BP_SETTINGS_SLUG', 'settings' );

function bp_core_add_settings_nav() {
	global $bp;

	/* Set up settings as a sudo-component for identification and nav selection */
	$bp->settings->id = 'settings';
	$bp->settings->slug = BP_SETTINGS_SLUG;

	/* Register this in the active components array */
	$bp->active_components[$bp->settings->slug] = $bp->settings->id;

	/* Add the settings navigation item */
	bp_core_new_nav_item( array( 'name' => __('Settings', 'buddypress'), 'slug' => $bp->settings->slug, 'position' => 100, 'show_for_displayed_user' => bp_core_can_edit_settings(), 'screen_function' => 'bp_core_screen_general_settings', 'default_subnav_slug' => 'general' ) );

	$settings_link = $bp->displayed_user->domain . $bp->settings->slug . '/';

	bp_core_new_subnav_item( array( 'name' => __( 'General', 'buddypress' ), 'slug' => 'general', 'parent_url' => $settings_link, 'parent_slug' => $bp->settings->slug, 'screen_function' => 'bp_core_screen_general_settings', 'position' => 10, 'user_has_access' => bp_core_can_edit_settings() ) );
	bp_core_new_subnav_item( array( 'name' => __( 'Notifications', 'buddypress' ), 'slug' => 'notifications', 'parent_url' => $settings_link, 'parent_slug' => $bp->settings->slug, 'screen_function' => 'bp_core_screen_notification_settings', 'position' => 20, 'user_has_access' => bp_core_can_edit_settings() ) );

	if ( !is_super_admin() && empty( $bp->site_options['bp-disable-account-deletion'] ) )
		bp_core_new_subnav_item( array( 'name' => __( 'Delete Account', 'buddypress' ), 'slug' => 'delete-account', 'parent_url' => $settings_link, 'parent_slug' => $bp->settings->slug, 'screen_function' => 'bp_core_screen_delete_account', 'position' => 90, 'user_has_access' => bp_is_my_profile() ) );

	do_action( 'bp_core_settings_setup_nav' );
}
add_action( 'bp_setup_nav', 'bp_core_add_settings_nav' );

function bp_core_can_edit_settings() {
	if ( bp_is_my_profile() )
		return true;
	
	if ( is_super_admin() )
		return true;
	
	return false;
}

/**** GENERAL SETTINGS ****/

function bp_core_screen_general_settings() {
	global $bp, $current_user, $bp_settings_updated, $pass_error, $email_error, $pwd_error;

	$bp_settings_updated = false;
	$pass_error = false;
	$email_error = false;
	$pwd_error = false;

	if ( isset($_POST['submit']) ) {
		check_admin_referer('bp_settings_general');

		require_once( ABSPATH . WPINC . '/registration.php' );

		// Form has been submitted and nonce checks out, lets do it.

 		// Validate the user again for the current password when making a big change
 		if ( is_super_admin() || ( !empty( $_POST['pwd'] ) && $_POST['pwd'] != '' && wp_check_password($_POST['pwd'], $current_user->user_pass, $current_user->ID ) ) ) {
 		
 			$update_user = get_userdata( $bp->displayed_user->id );

 			// Make sure changing an email address does not already exist
 			if ( $_POST['email'] != '' ) {

 				// What is missing from the profile page vs signup - lets double check the goodies
 				$user_email = sanitize_email( esc_html( trim( $_POST['email'] ) ) );

 				if ( !is_email( $user_email ) )
 					$email_error = true;

 				$limited_email_domains = get_site_option( 'limited_email_domains', 'buddypress' );

 				if ( is_array( $limited_email_domains ) && empty( $limited_email_domains ) == false ) {
 					$emaildomain = substr( $user_email, 1 + strpos( $user_email, '@' ) );

 					if ( in_array( $emaildomain, (array)$limited_email_domains ) == false ) {
 						$email_error = true;
 					}
 				}

 				if ( !$email_error && $bp->displayed_user->userdata->user_email != $user_email  ) {

 					//we don't want email dups in the system
 					if ( email_exists( $user_email ) )
 						$email_error = true;

 					if ( !$email_error )
 						$update_user->user_email = $user_email;
 				}
 			}

 			if ( $_POST['pass1'] != '' && $_POST['pass2'] != '' ) {

 				if ( $_POST['pass1'] == $_POST['pass2'] && !strpos( " " . $_POST['pass1'], "\\" ) ) {
 					$update_user->user_pass = $_POST['pass1'];
 				} else {
 					$pass_error = true;
				}

 			} else if ( empty( $_POST['pass1'] ) && !empty( $_POST['pass2'] ) || !empty( $_POST['pass1'] ) && empty( $_POST['pass2'] ) ) {
  				$pass_error = true;
 			} else {
 				unset( $update_user->user_pass );
 			}

 			if ( !$email_error && !$pass_error && wp_update_user( get_object_vars( $update_user ) ) ) {
 				// Make sure these changes are in $bp for the current page load
 				$bp->displayed_user->userdata = bp_core_get_core_userdata( $bp->displayed_user->id );
 				$bp_settings_updated = true;
 			}

  		} else {
 			$pwd_error = true;
  		}

		do_action( 'bp_core_general_settings_after_save' );
	}

	add_action( 'bp_template_title', 'bp_core_screen_general_settings_title' );
	add_action( 'bp_template_content', 'bp_core_screen_general_settings_content' );

	bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
}

function bp_core_screen_general_settings_title() {
	echo apply_filters( 'bp_core_general_settings_title', __( 'General Settings', 'buddypress' ) );
}

function bp_core_screen_general_settings_content() {
	global $bp, $bp_settings_updated, $pass_error, $pwd_error, $email_error; ?>

	<?php if ( $bp_settings_updated && !$pass_error ) { ?>
		<div id="message" class="updated fade">
			<p><?php _e( 'Changes Saved.', 'buddypress' ) ?></p>
		</div>
	<?php } ?>

	<?php if ( $pass_error && !$bp_settings_updated ) { ?>
		<div id="message" class="error fade">
			<p><?php _e( 'Your passwords did not match', 'buddypress' ) ?></p>
		</div>
	<?php } ?>

	<?php if ( $pwd_error && !$bp_settings_updated ) { ?>
		<div id="message" class="error fade">
			<p><?php _e( 'Your password is incorrect', 'buddypress' ) ?></p>
		</div>
	<?php } ?>

	<?php
	if ( $email_error && !$bp_settings_updated ) { ?>
		<div id="message" class="error fade">
			<p><?php _e( 'Sorry, that email address is already used or is invalid', 'buddypress' ) ?></p>
		</div>
	<?php } ?>


	<form action="<?php echo $bp->displayed_user->domain . BP_SETTINGS_SLUG . '/general' ?>" method="post" class="standard-form" id="settings-form">

		<?php if ( empty( $bp->loggedin_user->is_super_admin ) ) : ?>
			<label for="pwd"><?php _e( 'Current Password <span>(required to update email or change current password)</span>', 'buddypress' ) ?></label>
			<input type="password" name="pwd" id="pwd" size="16" value="" class="settings-input small" /> &nbsp;<a href="<?php echo site_url('wp-login.php?action=lostpassword', 'login') ?>" title="<?php _e('Password Lost and Found') ?>"><?php _e('Lost your password?') ?></a>
		<?php endif ?>

		<label for="email"><?php _e( 'Account Email', 'buddypress' ) ?></label>
		<input type="text" name="email" id="email" value="<?php echo esc_attr( $bp->displayed_user->userdata->user_email ); ?>" class="settings-input" />

		<label for="pass1"><?php _e( 'Change Password <span>(leave blank for no change)</span>', 'buddypress' ) ?></label>
		<input type="password" name="pass1" id="pass1" size="16" value="" class="settings-input small" /> &nbsp;<?php _e( 'New Password', 'buddypress' ) ?><br />
		<input type="password" name="pass2" id="pass2" size="16" value="" class="settings-input small" /> &nbsp;<?php _e( 'Repeat New Password', 'buddypress' ) ?>

		<?php do_action( 'bp_core_general_settings_before_submit' ) ?>

		<div class="submit">
			<input type="submit" name="submit" value="<?php _e( 'Save Changes', 'buddypress' ) ?>" id="submit" class="auto" />
		</div>

		<?php do_action( 'bp_core_general_settings_after_submit' ) ?>

		<?php wp_nonce_field('bp_settings_general') ?>
	</form>
<?php
}

/***** NOTIFICATION SETTINGS ******/

function bp_core_screen_notification_settings() {
	global $bp, $bp_settings_updated;

	$bp_settings_updated = false;

	if ( isset( $_POST['submit'] ) ) {
		check_admin_referer('bp_settings_notifications');

		if ( isset( $_POST['notifications'] ) ) {
			foreach ( (array)$_POST['notifications'] as $key => $value ) {
				update_user_meta( (int)$bp->displayed_user->id, $key, $value );
			}
		}

		$bp_settings_updated = true;

		do_action( 'bp_core_notification_settings_after_save' );
	}

	add_action( 'bp_template_title', 'bp_core_screen_notification_settings_title' );
	add_action( 'bp_template_content', 'bp_core_screen_notification_settings_content' );

	bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
}

function bp_core_screen_notification_settings_title() {
	echo apply_filters( 'bp_core_notification_settings_title', __( 'Email Notifications', 'buddypress' ) );
}

function bp_core_screen_notification_settings_content() {
	global $bp, $bp_settings_updated; ?>

	<?php if ( $bp_settings_updated ) { ?>
		<div id="message" class="updated fade">
			<p><?php _e( 'Changes Saved.', 'buddypress' ) ?></p>
		</div>
	<?php } ?>

	<form action="<?php echo $bp->displayed_user->domain . BP_SETTINGS_SLUG . '/notifications' ?>" method="post" id="settings-form">
		<p><?php _e( 'Send a notification by email when:', 'buddypress' ) ?></p>

		<?php do_action( 'bp_notification_settings' ) ?>

		<div class="submit">
			<input type="submit" name="submit" value="<?php _e( 'Save Changes', 'buddypress' ) ?>" id="submit" class="auto" />
		</div>

		<?php do_action( 'bp_core_notification_settings_after_submit' ) ?>

		<?php wp_nonce_field('bp_settings_notifications') ?>

	</form>
<?php
}

/**** DELETE ACCOUNT ****/

function bp_core_screen_delete_account() {
	global $bp;
	
	if ( isset( $_POST['delete-account-understand'] ) ) {
		check_admin_referer( 'delete-account' );

		// delete the users account
		if ( bp_core_delete_account( $bp->displayed_user->id ) )
			bp_core_redirect( site_url() );
	}

	add_action( 'bp_template_title', 'bp_core_screen_delete_account_title' );
	add_action( 'bp_template_content', 'bp_core_screen_delete_account_content' );

	bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
}

function bp_core_screen_delete_account_title() {
	echo apply_filters( 'bp_core_delete_account_title', __( 'Delete Account', 'buddypress' ) );
}

function bp_core_screen_delete_account_content() {
	global $bp, $bp_settings_updated, $pass_error; 	?>

	<form action="<?php echo $bp->displayed_user->domain .  BP_SETTINGS_SLUG . '/delete-account'; ?>" name="account-delete-form" id="account-delete-form" class="standard-form" method="post">

		<div id="message" class="info">
			<p><?php _e( 'WARNING: Deleting your account will completely remove ALL content associated with it. There is no way back, please be careful with this option.', 'buddypress' ); ?></p>
		</div>

		<input type="checkbox" name="delete-account-understand" id="delete-account-understand" value="1" onclick="if(this.checked) { document.getElementById('delete-account-button').disabled = ''; } else { document.getElementById('delete-account-button').disabled = 'disabled'; }" /> <?php _e( 'I understand the consequences of deleting my account.', 'buddypress' ); ?>

		<?php do_action( 'bp_core_delete_account_before_submit' ) ?>

		<div class="submit">
			<input type="submit" disabled="disabled" value="<?php _e( 'Delete My Account', 'buddypress' ) ?> &rarr;" id="delete-account-button" name="delete-account-button" />
		</div>

		<?php do_action( 'bp_core_delete_account_after_submit' ) ?>

		<?php wp_nonce_field('delete-account') ?>
	</form>
<?php
}
