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
	bp_core_new_nav_item( array( 'name' => __('Settings', 'buddypress'), 'slug' => $bp->settings->slug, 'position' => 100, 'show_for_displayed_user' => false, 'screen_function' => 'bp_core_screen_general_settings', 'default_subnav_slug' => 'general' ) );

	$settings_link = $bp->loggedin_user->domain . 'settings/';
	
	bp_core_new_subnav_item( array( 'name' => __( 'General', 'buddypress' ), 'slug' => 'general', 'parent_url' => $settings_link, 'parent_slug' => $bp->settings->slug, 'screen_function' => 'bp_core_screen_general_settings', 'position' => 10, 'user_has_access' => bp_is_home() ) );
	bp_core_new_subnav_item( array( 'name' => __( 'Notifications', 'buddypress' ), 'slug' => 'notifications', 'parent_url' => $settings_link, 'parent_slug' => $bp->settings->slug, 'screen_function' => 'bp_core_screen_notification_settings', 'position' => 20, 'user_has_access' => bp_is_home() ) );
	
	if ( !is_site_admin() )
		bp_core_new_subnav_item( array( 'name' => __( 'Delete Account', 'buddypress' ), 'slug' => 'delete-account', 'parent_url' => $settings_link, 'parent_slug' => $bp->settings->slug, 'screen_function' => 'bp_core_screen_delete_account', 'position' => 90, 'user_has_access' => bp_is_home() ) );
}
add_action( 'wp', 'bp_core_add_settings_nav', 2 );
add_action( 'admin_menu', 'bp_core_add_settings_nav', 2 );

/**** GENERAL SETTINGS ****/

function bp_core_screen_general_settings() {
	global $current_user, $bp_settings_updated, $pass_error;
	
	$bp_settings_updated = false;
	$pass_error = false;
	
	if ( isset($_POST['submit']) && check_admin_referer('bp_settings_general') ) {
		require_once( WPINC . '/registration.php' );
		
		// Form has been submitted and nonce checks out, lets do it.
		
		if ( $_POST['email'] != '' )
			$current_user->user_email = wp_specialchars( trim( $_POST['email'] ) );

		if ( $_POST['pass1'] != '' && $_POST['pass2'] != '' ) {
			if ( $_POST['pass1'] == $_POST['pass2'] && !strpos( " " . $_POST['pass1'], "\\" ) )
				$current_user->user_pass = $_POST['pass1'];
			else
				$pass_error = true;
		} else if ( empty( $_POST['pass1'] ) && !empty( $_POST['pass2'] ) || !empty( $_POST['pass1'] ) && empty( $_POST['pass2'] ) ) {
			$pass_error = true;
		} else {
			unset( $current_user->user_pass );
		}
		
		if ( !$pass_error && wp_update_user( get_object_vars( $current_user ) ) )
			$bp_settings_updated = true;
	}
	
	add_action( 'bp_template_title', 'bp_core_screen_general_settings_title' );
	add_action( 'bp_template_content', 'bp_core_screen_general_settings_content' );
	
	bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'plugin-template' ) );
}

function bp_core_screen_general_settings_title() {
	_e( 'General Settings', 'buddypress' );
}

function bp_core_screen_general_settings_content() {
	global $bp, $current_user, $bp_settings_updated, $pass_error; ?>

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

	<form action="<?php echo $bp->loggedin_user->domain . 'settings/general' ?>" method="post" class="standard-form" id="settings-form">
		<label for="email"><?php _e( 'Account Email', 'buddypress' ) ?></label>
		<input type="text" name="email" id="email" value="<?php echo attribute_escape( $current_user->user_email ); ?>" class="settings-input" />
			
		<label for="pass1"><?php _e( 'Change Password <span>(leave blank for no change)</span>', 'buddypress' ) ?></label>
		<input type="password" name="pass1" id="pass1" size="16" value="" class="settings-input small" /> &nbsp;<?php _e( 'New Password', 'buddypress' ) ?>
		<input type="password" name="pass2" id="pass2" size="16" value="" class="settings-input small" /> &nbsp;<?php _e( 'Repeat New Password', 'buddypress' ) ?>
	
		<p class="submit"><input type="submit" name="submit" value="<?php _e( 'Save Changes', 'buddypress' ) ?>" id="submit" class="auto"/></p>
		<?php wp_nonce_field('bp_settings_general') ?>
	</form>
<?php
}

/***** NOTIFICATION SETTINGS ******/

function bp_core_screen_notification_settings() {
	global $current_user, $bp_settings_updated;
	
	$bp_settings_updated = false;
	
	if ( $_POST['submit'] && check_admin_referer('bp_settings_notifications') ) {
		if ( $_POST['notifications'] ) {
			foreach ( $_POST['notifications'] as $key => $value ) {
				update_usermeta( (int)$current_user->id, $key, $value );
			}
		}
		
		$bp_settings_updated = true;
	}
		
	add_action( 'bp_template_title', 'bp_core_screen_notification_settings_title' );
	add_action( 'bp_template_content', 'bp_core_screen_notification_settings_content' );
	
	bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'plugin-template' ) );
}

function bp_core_screen_notification_settings_title() {
	_e( 'Notification Settings', 'buddypress' );
}

function bp_core_screen_notification_settings_content() {
	global $bp, $current_user, $bp_settings_updated; ?>

	<?php if ( $bp_settings_updated ) { ?>
		<div id="message" class="updated fade">
			<p><?php _e( 'Changes Saved.', 'buddypress' ) ?></p>
		</div>
	<?php } ?>
	
	<form action="<?php echo $bp->loggedin_user->domain . 'settings/notifications' ?>" method="post" id="settings-form">
		<h3><?php _e( 'Email Notifications', 'buddypress' ) ?></h3>
		<p><?php _e( 'Send a notification by email when:', 'buddypress' ) ?></p>
		
		<?php do_action( 'bp_notification_settings' ) ?>
		
		<p class="submit"><input type="submit" name="submit" value="<?php _e( 'Save Changes', 'buddypress' ) ?>" id="submit" class="auto"/></p>		
		
		<?php wp_nonce_field('bp_settings_notifications') ?>
		
	</form>
<?php
}

/**** DELETE ACCOUNT ****/

function bp_core_screen_delete_account() {
	global $current_user, $bp_settings_updated, $pass_error;
	
	if ( isset( $_POST['delete-account-button'] ) && check_admin_referer('delete-account') ) {
		if ( !check_admin_referer( 'delete-account' ) )
			return false;
		
		// delete the users account
		if ( bp_core_delete_account() )
			bp_core_redirect( site_url() );
	}
	
	$bp_settings_updated = false;
	$pass_error = false;
	
	if ( isset($_POST['submit']) && check_admin_referer('bp_settings_general') ) {
		require_once( WPINC . '/registration.php' );
		
		// Form has been submitted and nonce checks out, lets do it.
		
		if ( $_POST['email'] != '' ) {
			$current_user->user_email = wp_specialchars( trim( $_POST['email'] ));
		}

		if ( $_POST['pass1'] != '' && $_POST['pass2'] != '' ) {
			if ( $_POST['pass1'] == $_POST['pass2'] && !strpos( " " . $_POST['pass1'], "\\" ) ) {
				$current_user->user_pass = $_POST['pass1'];
			} else {
				$pass_error = true;
			}
		} else if ( empty( $_POST['pass1'] ) && !empty( $_POST['pass2'] ) || !empty( $_POST['pass1'] ) && empty( $_POST['pass2'] ) ) {
			$pass_error = true;
		} else {
			unset( $current_user->user_pass );
		}
		
		if ( !$pass_error && wp_update_user( get_object_vars( $current_user ) ) )
			$bp_settings_updated = true;
	}
	
	add_action( 'bp_template_title', 'bp_core_screen_delete_account_title' );
	add_action( 'bp_template_content', 'bp_core_screen_delete_account_content' );
	
	bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'plugin-template' ) );
}

function bp_core_screen_delete_account_title() {
	_e( 'Delete Account', 'buddypress' );
}

function bp_core_screen_delete_account_content() {
	global $bp, $current_user, $bp_settings_updated, $pass_error; 	?>

	<form action="<?php echo $bp->loggedin_user->domain . 'settings/delete-account'; ?>" name="account-delete-form" id="account-delete-form" class="standard-form" method="post">
		
		<div id="message" class="info">
			<p><?php _e( 'WARNING: Deleting your account will completely remove ALL content associated with it. There is no way back, please be careful with this option.', 'buddypress' ); ?></p>
		</div>
		
		<input type="checkbox" name="delete-account-understand" id="delete-account-understand" value="1" onclick="if(this.checked) { document.getElementById('delete-account-button').disabled = ''; } else { document.getElementById('delete-account-button').disabled = 'disabled'; }" /> <?php _e( 'I understand the consequences of deleting my account.', 'buddypress' ); ?>
		<p><input type="submit" disabled="disabled" value="<?php _e( 'Delete My Account', 'buddypress' ) ?> &raquo;" id="delete-account-button" name="delete-account-button" /></p>
		<?php wp_nonce_field('delete-account') ?>
	</form>
<?php
}
