<?php
/**
 * BuddyPress Admin Settings.
 *
 * @package BuddyPress
 * @subpackage CoreAdministration
 * @since 2.3.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main settings section description for the settings page.
 *
 * @since 1.6.0
 */
function bp_admin_setting_callback_main_section() { }

/**
 * Admin bar for logged out users setting field.
 *
 * @since 1.6.0
 *
 */
function bp_admin_setting_callback_admin_bar() {
?>

	<input id="hide-loggedout-adminbar" name="hide-loggedout-adminbar" type="checkbox" value="1" <?php checked( !bp_hide_loggedout_adminbar( false ) ); ?> />
	<label for="hide-loggedout-adminbar"><?php esc_html_e( 'Show the Toolbar for logged out users', 'buddypress' ); ?></label>

<?php
}

/**
 * Choose whether the community is visible to anyone or only to members.
 *
 * @since 12.0.0
 */
function bp_admin_setting_callback_community_visibility() {
	$visibility = bp_get_community_visibility( 'all' );
?>
	<select name="_bp_community_visibility[global]" id="_bp_community_visibility-global" aria-describedby="_bp_community_visibility_description" autocomplete="off">
		<option value="anyone" <?php echo selected( $visibility['global'], 'anyone' ); ?>><?php esc_html_e( 'Anyone', 'buddypress' ); ?></option>
		<option value="members" <?php echo selected( $visibility['global'], 'members' ); ?>><?php esc_html_e( 'Members Only', 'buddypress' ); ?></option>
	</select>

	<p id="_bp_community_visibility_description" class="description"><?php esc_html_e( 'Choose "Anyone" to allow any visitor access to your community area. Choose "Members Only" to restrict access to your community area to logged-in members only.', 'buddypress' ); ?></p>
<?php
}

/**
 * Sanitize the visibility setting when it is saved.
 *
 * @since 12.0.0
 *
 * @param mixed $saved_value The value passed to the save function.
 */
function bp_admin_sanitize_callback_community_visibility( $saved_value ) {
	$retval = array();

	// Use the global setting, if it has been passed.
	$retval['global'] = isset( $saved_value['global'] ) ? $saved_value['global'] : 'anyone';
	// Ensure the global value is a valid option. Else, assume that the site is open.
	if ( ! in_array( $retval['global'], array( 'anyone', 'members' ), true ) ) {
		$retval['global'] = 'anyone';
	}

	// Keys must be either 'global' or a component ID, but not register or activate.
	$directory_pages = bp_core_get_directory_pages();
	foreach ( $directory_pages as $component_id => $component_page ) {
		if ( in_array( $component_id, array( 'register', 'activate' ), true ) ) {
			continue;
		}

		// Use the global value if a specific value hasn't been set.
		$component_value = isset( $saved_value[ $component_id ] ) ? $saved_value[ $component_id ] : $retval['global'];

		// Valid values are 'anyone' or 'memebers'.
		if ( ! in_array( $component_value, array( 'anyone', 'members' ), true ) ) {
			$component_value = $retval['global'];
		}
		$retval[ $component_id ] = $component_value;
	}

	return $retval;
}

/**
 * Form element to change the active template pack.
 */
function bp_admin_setting_callback_theme_package_id() {
	$options = '';

	/*
	 * Note: This should never be empty. /bp-templates/ is the
	 * canonical backup if no other packages exist. If there's an error here,
	 * something else is wrong.
	 *
	 * See BuddyPress::register_theme_packages()
	 */
	foreach ( (array) buddypress()->theme_compat->packages as $id => $theme ) {
		$options .= sprintf(
			'<option value="%1$s" %2$s>%3$s</option>',
			esc_attr( $id ),
			selected( $theme->id, bp_get_theme_package_id(), false ),
			esc_html( $theme->name )
		);
	}

	// phpcs:disable WordPress.Security.EscapeOutput
	if ( $options ) : ?>
		<select name="_bp_theme_package_id" id="_bp_theme_package_id" aria-describedby="_bp_theme_package_description"><?php echo $options; ?></select>
		<p id="_bp_theme_package_description" class="description"><?php esc_html_e( 'The selected Template Pack will serve all BuddyPress templates.', 'buddypress' ); ?></p>

	<?php else : ?>
		<p><?php esc_html_e( 'No template packages available.', 'buddypress' ); ?></p>

	<?php endif;
	// phpcs:enable
}

/** Activity *******************************************************************/

/**
 * Groups settings section description for the settings page.
 *
 * @since 1.6.0
 */
function bp_admin_setting_callback_activity_section() { }

/**
 * Allow Akismet setting field.
 *
 * @since 1.6.0
 *
 */
function bp_admin_setting_callback_activity_akismet() {
?>

	<input id="_bp_enable_akismet" name="_bp_enable_akismet" type="checkbox" value="1" <?php checked( bp_is_akismet_active( true ) ); ?> />
	<label for="_bp_enable_akismet"><?php esc_html_e( 'Allow Akismet to scan for activity stream spam', 'buddypress' ); ?></label>

<?php
}

/**
 * Allow activity comments on posts and comments.
 *
 * @since 1.6.0
 */
function bp_admin_setting_callback_blogforum_comments() {
	$support = post_type_supports( 'post', 'buddypress-activity' );
?>

	<input id="bp-disable-blogforum-comments" name="bp-disable-blogforum-comments" type="checkbox" value="1" <?php checked( ! bp_disable_blogforum_comments( false ) ); ?> <?php disabled( ! $support ); ?> />
	<label for="bp-disable-blogforum-comments"><?php esc_html_e( 'Allow activity stream commenting on posts and comments', 'buddypress' ); ?></label>
	<?php if ( ! $support ) : ?>
		<p class="description"><?php esc_html_e( 'Activate the BuddyPress Site Tracking component in order to enable this feature.', 'buddypress' ); ?></p>
	<?php endif; ?>

<?php
}

/**
 * Allow Heartbeat to refresh activity stream.
 *
 * @since 2.0.0
 */
function bp_admin_setting_callback_heartbeat() {
?>

	<input id="_bp_enable_heartbeat_refresh" name="_bp_enable_heartbeat_refresh" type="checkbox" value="1" <?php checked( bp_is_activity_heartbeat_active( true ) ); ?> />
	<label for="_bp_enable_heartbeat_refresh"><?php esc_html_e( 'Automatically check for new items while viewing the activity stream', 'buddypress' ); ?></label>

<?php
}

/**
 * Sanitization for bp-disable-blogforum-comments setting.
 *
 * In the UI, a checkbox asks whether you'd like to *enable* post/comment activity comments. For
 * legacy reasons, the option that we store is 1 if these comments are *disabled*. So we use this
 * function to flip the boolean before saving the intval.
 *
 * @since 1.6.0
 *
 * @param bool $value Whether or not to sanitize.
 * @return bool
 */
function bp_admin_sanitize_callback_blogforum_comments( $value = false ) {
	return $value ? 0 : 1;
}

/** Members *******************************************************************/

/**
 * Profile settings section description for the settings page.
 *
 * @since 1.6.0
 */
function bp_admin_setting_callback_members_section() { }

/**
 * Allow members to upload avatars field.
 *
 * @since 1.6.0
 * @since 6.0.0 Setting has been moved into the Members section.
 */
function bp_admin_setting_callback_avatar_uploads() {
?>
	<input id="bp-disable-avatar-uploads" name="bp-disable-avatar-uploads" type="checkbox" value="1" <?php checked( ! bp_disable_avatar_uploads( false ) ); ?> />
	<label for="bp-disable-avatar-uploads"><?php esc_html_e( 'Allow registered members to upload avatars', 'buddypress' ); ?></label>
<?php
}

/**
 * Allow members to upload cover images field.
 *
 * @since 2.4.0
 * @since 6.0.0 Setting has been moved into the Members section.
 */
function bp_admin_setting_callback_cover_image_uploads() {
?>
	<input id="bp-disable-cover-image-uploads" name="bp-disable-cover-image-uploads" type="checkbox" value="1" <?php checked( ! bp_disable_cover_image_uploads() ); ?> />
	<label for="bp-disable-cover-image-uploads"><?php esc_html_e( 'Allow registered members to upload cover images', 'buddypress' ); ?></label>
<?php
}

/**
 * Allow members to invite non-members to the network.
 *
 * @since 8.0.0
 */
function bp_admin_setting_callback_members_invitations() {
?>
	<input id="bp-enable-members-invitations" name="bp-enable-members-invitations" type="checkbox" value="1" <?php checked( bp_get_members_invitations_allowed() ); ?> />
	<label for="bp-enable-members-invitations"><?php esc_html_e( 'Allow registered members to invite people to join this network', 'buddypress' ); ?></label>
	<?php if ( ! bp_get_signup_allowed() ) : ?>
		<p class="description"><?php esc_html_e( 'Public registration is currently disabled. However, invitees will still be able to register if network invitations are enabled.', 'buddypress' ); ?></p>
	<?php endif; ?>
	<?php
	/**
	 * Fires after the output of the invitations settings section.
	 *
	 * @since 8.0.0
	 */
	do_action( 'bp_admin_settings_after_members_invitations' );
}

/**
 * Allow new users to request membership to the network.
 *
 * @since 10.0.0
 */
function bp_admin_setting_callback_membership_requests() {
?>
	<input id="bp-enable-membership-requests" name="bp-enable-membership-requests" type="checkbox" value="1" <?php checked( bp_get_membership_requests_required( 'raw' ) ); ?> <?php disabled( bp_get_signup_allowed() ); ?> />
	<label for="bp-enable-membership-requests"><?php esc_html_e( 'Allow visitors to request site membership. If enabled, an administrator must approve each new site membership request.', 'buddypress' ); ?></label>
	<?php if ( bp_get_signup_allowed() ) : ?>
		<?php if ( is_multisite() ) : ?>
			<p class="description"><?php esc_html_e( 'With a WP multisite setup, to require membership requests for new signups, choose one of the following two options from the Network Settings > Registration Settings pane:', 'buddypress' ); ?><p>
				<ul>
					<li><p class="description"><?php esc_html_e( 'To allow the submission of membership requests but not allow site creation requests, select "Registration is disabled".', 'buddypress' ) ?></p></li>
					<li><p class="description"><?php esc_html_e( 'To allow the submission of membership requests and to allow new sites to be created by your users, choose "Logged in users may register new sites".', 'buddypress' ) ?></p></li>
				</ul>
			<p class="description"><?php esc_html_e( 'The other two options, "User accounts may be registered" and "Both sites and user accounts can be registered," are open in nature and membership requests will not be enabled if one of those options is selected.', 'buddypress' ); ?><p>
		<?php else : ?>
			<p class="description"><?php esc_html_e( 'Public registration is currently enabled. If you wish to require approval for new memberships, disable public registration and enable the membership requests feature.', 'buddypress' ); ?></p>
		<?php endif; ?>
	<?php endif; ?>
	<?php
	/**
	 * Fires after the output of the membership requests settings section.
	 *
	 * @since 10.0.0
	 */
	do_action( 'bp_admin_settings_after_membership_requests' );
}

/** XProfile ******************************************************************/

/**
 * Profile settings section description for the settings page.
 *
 * @since 1.6.0
 */
function bp_admin_setting_callback_xprofile_section() { }

/**
 * Enable BP->WP profile syncing field.
 *
 * @since 1.6.0
 *
 */
function bp_admin_setting_callback_profile_sync() {
?>

	<input id="bp-disable-profile-sync" name="bp-disable-profile-sync" type="checkbox" value="1" <?php checked( !bp_disable_profile_sync( false ) ); ?> />
	<label for="bp-disable-profile-sync"><?php esc_html_e( 'Enable BuddyPress to WordPress profile syncing', 'buddypress' ); ?></label>

<?php
}

/** Groups Section ************************************************************/

/**
 * Groups settings section description for the settings page.
 *
 * @since 1.6.0
 */
function bp_admin_setting_callback_groups_section() { }

/**
 * Allow all users to create groups field.
 *
 * @since 1.6.0
 *
 */
function bp_admin_setting_callback_group_creation() {
?>

	<input id="bp_restrict_group_creation" name="bp_restrict_group_creation" type="checkbox" aria-describedby="bp_group_creation_description" value="1" <?php checked( !bp_restrict_group_creation( false ) ); ?> />
	<label for="bp_restrict_group_creation"><?php esc_html_e( 'Enable group creation for all users', 'buddypress' ); ?></label>
	<p class="description" id="bp_group_creation_description"><?php esc_html_e( 'Administrators can always create groups, regardless of this setting.', 'buddypress' ); ?></p>

<?php
}

/**
 * 'Enable group avatars' field markup.
 *
 * @since 2.3.0
 */
function bp_admin_setting_callback_group_avatar_uploads() {
?>
	<input id="bp-disable-group-avatar-uploads" name="bp-disable-group-avatar-uploads" type="checkbox" value="1" <?php checked( ! bp_disable_group_avatar_uploads() ); ?> />
	<label for="bp-disable-group-avatar-uploads"><?php esc_html_e( 'Allow customizable avatars for groups', 'buddypress' ); ?></label>
<?php
}

/**
 * 'Enable group cover images' field markup.
 *
 * @since 2.4.0
 */
function bp_admin_setting_callback_group_cover_image_uploads() {
?>
	<input id="bp-disable-group-cover-image-uploads" name="bp-disable-group-cover-image-uploads" type="checkbox" value="1" <?php checked( ! bp_disable_group_cover_image_uploads() ); ?> />
	<label for="bp-disable-group-cover-image-uploads"><?php esc_html_e( 'Allow customizable cover images for groups', 'buddypress' ); ?></label>
<?php
}

/** Account settings Section ************************************************************/

/**
 * Account settings section description for the settings page.
 *
 * @since 12.0.0
 */
function bp_admin_setting_callback_settings_section() { }

/**
 * Allow members to delete their accounts setting field.
 *
 * @since 1.6.0
 */
function bp_admin_setting_callback_account_deletion() {
?>

	<input id="bp-disable-account-deletion" name="bp-disable-account-deletion" type="checkbox" value="1" <?php checked( ! bp_disable_account_deletion( false ) ); ?> />
	<label for="bp-disable-account-deletion"><?php esc_html_e( 'Allow registered members to delete their own accounts', 'buddypress' ); ?></label>

<?php
}

/** Settings Page *************************************************************/

/**
 * The main settings page
 *
 * @since 1.6.0
 *
 */
function bp_core_admin_settings() {

	// We're saving our own options, until the WP Settings API is updated to work with Multisite.
	$form_action = add_query_arg( 'page', 'bp-settings', bp_get_admin_url( 'admin.php' ) );
	bp_core_admin_tabbed_screen_header( __( 'BuddyPress Settings', 'buddypress' ), __( 'Options', 'buddypress' ) );
	?>

	<div class="buddypress-body">
		<form action="<?php echo esc_url( $form_action ) ?>" method="post">

			<?php settings_fields( 'buddypress' ); ?>

			<?php do_settings_sections( 'buddypress' ); ?>

			<p class="submit">
				<input type="submit" name="submit" class="button-primary" value="<?php esc_attr_e( 'Save Settings', 'buddypress' ); ?>" />
			</p>
		</form>
	</div>

<?php
}

/**
 * Save our settings.
 *
 * @since 1.6.0
 */
function bp_core_admin_settings_save() {
	global $wp_settings_fields;

	if ( isset( $_GET['page'] ) && 'bp-settings' == $_GET['page'] && !empty( $_POST['submit'] ) ) {
		check_admin_referer( 'buddypress-options' );

		// Because many settings are saved with checkboxes, and thus will have no values
		// in the $_POST array when unchecked, we loop through the registered settings.
		if ( isset( $wp_settings_fields['buddypress'] ) ) {
			foreach( (array) $wp_settings_fields['buddypress'] as $section => $settings ) {
				foreach( $settings as $setting_name => $setting ) {
					$value = isset( $_POST[$setting_name] ) ? $_POST[$setting_name] : '';

					bp_update_option( $setting_name, $value );
				}
			}
		}

		// Some legacy options are not registered with the Settings API, or are reversed in the UI.
		$legacy_options = array(
			'bp-disable-account-deletion',
			'bp-disable-avatar-uploads',
			'bp-disable-cover-image-uploads',
			'bp-disable-group-avatar-uploads',
			'bp-disable-group-cover-image-uploads',
			'bp_disable_blogforum_comments',
			'bp-disable-profile-sync',
			'bp_restrict_group_creation',
			'hide-loggedout-adminbar',
		);

		foreach( $legacy_options as $legacy_option ) {
			// Note: Each of these options is represented by its opposite in the UI
			// Ie, the Profile Syncing option reads "Enable Sync", so when it's checked,
			// the corresponding option should be unset.
			$value = isset( $_POST[$legacy_option] ) ? '' : 1;
			bp_update_option( $legacy_option, $value );
		}

		bp_core_redirect( add_query_arg( array( 'page' => 'bp-settings', 'updated' => 'true' ), bp_get_admin_url( 'admin.php' ) ) );
	}
}
add_action( 'bp_admin_init', 'bp_core_admin_settings_save', 100 );

/**
 * Output settings API option.
 *
 * @since 1.6.0
 *
 * @param string $option  Form option to echo.
 * @param string $default Form option default.
 * @param bool   $slug    Form option slug.
 */
function bp_form_option( $option, $default = '' , $slug = false ) {
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_form_option( $option, $default, $slug );
}

/**
 * Return settings API option
 *
 * @since 1.6.0
 *
 *
 * @param string $option  Form option to return.
 * @param string $default Form option default.
 * @param bool   $slug    Form option slug.
 * @return string
 */
function bp_get_form_option( $option, $default = '', $slug = false ) {

	// Get the option and sanitize it.
	$value = bp_get_option( $option, $default );

	// Slug?
	if ( true === $slug ) {

		/**
		 * Filters the slug value in the form field.
		 *
		 * @since 1.6.0
		 *
		 * @param string $value Value being returned for the requested option.
		 */
		$value = esc_attr( apply_filters( 'editable_slug', $value ) );
	} else { // Not a slug.
		$value = esc_attr( $value );
	}

	// Fallback to default.
	if ( empty( $value ) ) {
		$value = $default;
	}

	/**
	 * Filters the settings API option.
	 *
	 * @since 1.6.0
	 *
	 * @param string $value  Value being returned for the requested option.
	 * @param string $option Option whose value is being requested.
	 */
	return apply_filters( 'bp_get_form_option', $value, $option );
}
