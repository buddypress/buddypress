<?php

/**
 * Add an extra update message to the update plugin notification.
 *
 * @package BuddyPress Core
 */
function bp_core_update_message() {
	echo '<p style="color: red; margin: 3px 0 0 0; border-top: 1px solid #ddd; padding-top: 3px">' . __( 'IMPORTANT: <a href="http://codex.buddypress.org/getting-started/upgrading-from-10x/">Read this before attempting to update BuddyPress</a>', 'buddypress' ) . '</p>';
}
add_action( 'in_plugin_update_message-buddypress/bp-loader.php', 'bp_core_update_message' );

/**
 * When BuddyPress is activated we must make sure that mod_rewrite is enabled.
 * We must also make sure a BuddyPress compatible theme is enabled. This function
 * will show helpful messages to the administrator.
 *
 * @package BuddyPress Core
 */
function bp_core_activation_notice() {
	global $wp_rewrite, $current_blog, $bp;

	if ( isset( $_POST['permalink_structure'] ) )
		return false;

	if ( !is_super_admin() )
		return false;

	if ( !empty( $current_blog ) ) {
		if ( $current_blog->blog_id != BP_ROOT_BLOG )
			return false;
	}

	if ( empty( $wp_rewrite->permalink_structure ) ) { ?>

		<div id="message" class="updated fade">
			<p><?php printf( __( '<strong>BuddyPress is almost ready</strong>. You must <a href="%s">update your permalink structure</a> to something other than the default for it to work.', 'buddypress' ), admin_url( 'options-permalink.php' ) ) ?></p>
		</div><?php

	} else {
		// Get current theme info
		$ct = current_theme_info();

		// The best way to remove this notice is to add a "buddypress" tag to
		// your active theme's CSS header.
		if ( !defined( 'BP_SILENCE_THEME_NOTICE' ) && !in_array( 'buddypress', (array)$ct->tags ) ) { ?>

			<div id="message" class="updated fade">
				<p style="line-height: 150%"><?php printf( __( "<strong>BuddyPress is ready</strong>. You'll need to <a href='%s'>activate a BuddyPress compatible theme</a> to take advantage of all of the features. We've bundled a default theme, but you can always <a href='%s'>install some other compatible themes</a> or <a href='%s'>update your existing WordPress theme</a>.", 'buddypress' ), admin_url( 'themes.php' ), admin_url( 'theme-install.php?type=tag&s=buddypress&tab=search' ), admin_url( 'plugin-install.php?type=term&tab=search&s=%22bp-template-pack%22' ) ) ?></p>
			</div>

		<?php
		}
	}
}
add_action( 'admin_notices', 'bp_core_activation_notice' );

/**
 * Renders the main admin panel.
 *
 * @package BuddyPress Core
 * @since {@internal Unknown}}
 */
function bp_core_admin_dashboard() { ?>
	<div class="wrap" id="bp-admin">

		<div id="bp-admin-header">
			<h3><?php _e( 'BuddyPress', 'buddypress' ) ?></h3>
			<h2><?php _e( 'Dashboard',  'buddypress' ) ?></h2>
		</div>

		<?php do_action( 'bp_admin_notices' ) ?>

		<form action="<?php echo site_url( '/wp-admin/admin.php?page=bp-general-settings' ) ?>" method="post" id="bp-admin-form">
			<div id="bp-admin-content">
				<p>[TODO: All sorts of awesome things will go here. Latest plugins and themes, stats, version check, support topics, news, tips]</p>
			</div>
		</form>

	</div>

<?php
}

/**
 * Renders the Settings admin panel.
 *
 * @package BuddyPress Core
 * @since {@internal Unknown}}
 */
function bp_core_admin_settings() {
	global $wpdb, $bp, $current_blog;

	$ud = get_userdata( $bp->loggedin_user->id );

	if ( isset( $_POST['bp-admin-submit'] ) && isset( $_POST['bp-admin'] ) ) {
		if ( !check_admin_referer('bp-admin') )
			return false;

		// Settings form submitted, now save the settings.
		foreach ( (array)$_POST['bp-admin'] as $key => $value ) {

			if ( bp_is_active( 'profile' ) ) {
				if ( 'bp-xprofile-base-group-name' == $key )
					$wpdb->query( $wpdb->prepare( "UPDATE {$bp->profile->table_name_groups} SET name = %s WHERE id = 1", stripslashes( $value ) ) );
				elseif ( 'bp-xprofile-fullname-field-name' == $key )
					$wpdb->query( $wpdb->prepare( "UPDATE {$bp->profile->table_name_fields} SET name = %s WHERE group_id = 1 AND id = 1", stripslashes( $value ) ) );
			}

			update_site_option( $key, $value );
		}
	}
	?>

	<div class="wrap">

		<h2><?php _e( 'BuddyPress Settings', 'buddypress' ) ?></h2>

		<?php if ( isset( $_POST['bp-admin'] ) ) : ?>

			<div id="message" class="updated fade">
				<p><?php _e( 'Settings Saved', 'buddypress' ) ?></p>
			</div>

		<?php endif; ?>

		<form action="" method="post" id="bp-admin-form">

			<table class="form-table">
				<tbody>

					<?php if ( bp_is_active( 'profile' ) ) : ?>

						<tr>
							<th scope="row"><?php _e( 'Base profile group name', 'buddypress' ) ?>:</th>
							<td>
								<input name="bp-admin[bp-xprofile-base-group-name]" id="bp-xprofile-base-group-name" value="<?php echo esc_attr( stripslashes( get_site_option( 'bp-xprofile-base-group-name' ) ) ) ?>" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php _e( 'Full Name field name', 'buddypress' ) ?>:</th>
							<td>
								<input name="bp-admin[bp-xprofile-fullname-field-name]" id="bp-xprofile-fullname-field-name" value="<?php echo esc_attr( stripslashes( get_site_option( 'bp-xprofile-fullname-field-name' ) ) ) ?>" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php _e( 'Disable BuddyPress to WordPress profile syncing?', 'buddypress' ) ?>:</th>
							<td>
								<input type="radio" name="bp-admin[bp-disable-profile-sync]"<?php if ( (int)get_site_option( 'bp-disable-profile-sync' ) ) : ?> checked="checked"<?php endif; ?> id="bp-disable-profile-sync" value="1" /> <?php _e( 'Yes', 'buddypress' ) ?> &nbsp;
								<input type="radio" name="bp-admin[bp-disable-profile-sync]"<?php if ( !(int)get_site_option( 'bp-disable-profile-sync' ) || '' == get_site_option( 'bp-disable-profile-sync' ) ) : ?> checked="checked"<?php endif; ?> id="bp-disable-profile-sync" value="0" /> <?php _e( 'No', 'buddypress' ) ?>
							</td>
						</tr>

					<?php endif; ?>

					<tr>
						<th scope="row"><?php _e( 'Hide admin bar for logged out users?', 'buddypress' ) ?>:</th>
						<td>
							<input type="radio" name="bp-admin[hide-loggedout-adminbar]"<?php if ( (int)get_site_option( 'hide-loggedout-adminbar' ) ) : ?> checked="checked"<?php endif; ?> id="bp-admin-hide-loggedout-adminbar-yes" value="1" /> <?php _e( 'Yes', 'buddypress' ) ?> &nbsp;
							<input type="radio" name="bp-admin[hide-loggedout-adminbar]"<?php if ( !(int)get_site_option( 'hide-loggedout-adminbar' ) ) : ?> checked="checked"<?php endif; ?> id="bp-admin-hide-loggedout-adminbar-no" value="0" /> <?php _e( 'No', 'buddypress' ) ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e( 'Disable avatar uploads? (Gravatars will still work)', 'buddypress' ) ?>:</th>
						<td>
							<input type="radio" name="bp-admin[bp-disable-avatar-uploads]"<?php if ( (int)get_site_option( 'bp-disable-avatar-uploads' ) ) : ?> checked="checked"<?php endif; ?> id="bp-admin-disable-avatar-uploads-yes" value="1" /> <?php _e( 'Yes', 'buddypress' ) ?> &nbsp;
							<input type="radio" name="bp-admin[bp-disable-avatar-uploads]"<?php if ( !(int)get_site_option( 'bp-disable-avatar-uploads' ) ) : ?> checked="checked"<?php endif; ?> id="bp-admin-disable-avatar-uploads-no" value="0" /> <?php _e( 'No', 'buddypress' ) ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e( 'Disable user account deletion?', 'buddypress' ) ?>:</th>
						<td>
							<input type="radio" name="bp-admin[bp-disable-account-deletion]"<?php if ( (int)get_site_option( 'bp-disable-account-deletion' ) ) : ?> checked="checked"<?php endif; ?> id="bp-disable-account-deletion" value="1" /> <?php _e( 'Yes', 'buddypress' ) ?> &nbsp;
							<input type="radio" name="bp-admin[bp-disable-account-deletion]"<?php if ( !(int)get_site_option( 'bp-disable-account-deletion' ) ) : ?> checked="checked"<?php endif; ?> id="bp-disable-account-deletion" value="0" /> <?php _e( 'No', 'buddypress' ) ?>
						</td>
					</tr>

					<?php if ( function_exists( 'bp_forums_setup') ) : ?>

						<tr>
							<th scope="row"><?php _e( 'Disable global forum directory?', 'buddypress' ) ?>:</th>
							<td>
								<input type="radio" name="bp-admin[bp-disable-forum-directory]"<?php if ( (int)get_site_option( 'bp-disable-forum-directory' ) ) : ?> checked="checked"<?php endif; ?> id="bp-disable-forum-directory" value="1" /> <?php _e( 'Yes', 'buddypress' ) ?> &nbsp;
								<input type="radio" name="bp-admin[bp-disable-forum-directory]"<?php if ( !(int)get_site_option( 'bp-disable-forum-directory' ) ) : ?> checked="checked"<?php endif; ?> id="bp-disable-forum-directory" value="0" /> <?php _e( 'No', 'buddypress' ) ?>
							</td>
						</tr>

					<?php endif; ?>

					<?php if ( bp_is_active( 'activity' ) ) : ?>

						<tr>
							<th scope="row"><?php _e( 'Disable activity stream commenting on blog and forum posts?', 'buddypress' ) ?>:</th>
							<td>
								<input type="radio" name="bp-admin[bp-disable-blogforum-comments]"<?php if ( (int)get_site_option( 'bp-disable-blogforum-comments' ) || false === get_site_option( 'bp-disable-blogforum-comments' ) ) : ?> checked="checked"<?php endif; ?> id="bp-disable-blogforum-comments" value="1" /> <?php _e( 'Yes', 'buddypress' ) ?> &nbsp;
								<input type="radio" name="bp-admin[bp-disable-blogforum-comments]"<?php if ( !(int)get_site_option( 'bp-disable-blogforum-comments' ) ) : ?> checked="checked"<?php endif; ?> id="bp-disable-blogforum-comments" value="0" /> <?php _e( 'No', 'buddypress' ) ?>
							</td>
						</tr>

					<?php endif; ?>

					<?php do_action( 'bp_core_admin_screen_fields' ) ?>

				</tbody>
			</table>

			<?php do_action( 'bp_core_admin_screen' ) ?>

			<p class="submit">
				<input class="button-primary" type="submit" name="bp-admin-submit" id="bp-admin-submit" value="<?php _e( 'Save Settings', 'buddypress' ) ?>"/>
			</p>

			<?php wp_nonce_field( 'bp-admin' ) ?>

		</form>

	</div>

<?php
}

/**
 * Renders the Component Setup admin panel.
 *
 * @package BuddyPress Core
 * @since {@internal Unknown}}
 * @uses bp_core_admin_component_options()
 */
function bp_core_admin_component_setup() {
	global $wpdb, $bp;

	if ( isset( $_POST['bp-admin-component-submit'] ) && isset( $_POST['bp_components'] ) ) {
		if ( !check_admin_referer('bp-admin-component-setup') )
			return false;

		// Settings form submitted, now save the settings.
		foreach ( (array)$_POST['bp_components'] as $key => $value ) {
			if ( !(int) $value )
				$disabled[$key] = 1;
		}
		update_site_option( 'bp-deactivated-components', $disabled );
	} ?>

	<div class="wrap">

		<h2><?php _e( 'BuddyPress Component Setup', 'buddypress' ) ?></h2>

		<?php if ( isset( $_POST['bp-admin-component-submit'] ) ) : ?>

			<div id="message" class="updated fade">
				<p><?php _e( 'Settings Saved', 'buddypress' ) ?></p>
			</div>

		<?php endif; ?>

		<form action="" method="post" id="bp-admin-component-form">

			<p><?php _e('By default, all BuddyPress components are enabled. You can selectively disable any of the components by using the form below. Your BuddyPress installation will continue to function, however the features of the disabled components will no longer be accessible to anyone using the site.', 'buddypress' ) ?></p>

			<?php $disabled_components = get_site_option( 'bp-deactivated-components' ); ?>
			
			<?php bp_core_admin_component_options() ?>

			<p class="submit clear">
				<input class="button-primary" type="submit" name="bp-admin-component-submit" id="bp-admin-component-submit" value="<?php _e( 'Save Settings', 'buddypress' ) ?>"/>
			</p>

			<?php wp_nonce_field( 'bp-admin-component-setup' ) ?>

		</form>
	</div>

<?php
}

/**
 * Creates reusable markup for the component setup dashboard panel.
 *
 * This markup has been abstracted so that it can be used both during the setup wizard as well as
 * when BP has been fully installed.
 *
 * @package BuddyPress Core
 * @since 1.3
 */
function bp_core_admin_component_options() {
	$disabled_components = apply_filters( 'bp_deactivated_components', get_site_option( 'bp-deactivated-components' ) ); 
	?>
	
	<div class="component">
		<h5><?php _e( "Extended Profiles", 'buddypress' ) ?></h5>

		<div class="radio">
			<input type="radio" name="bp_components[bp-xprofile.php]" value="1"<?php if ( !isset( $disabled_components['bp-xprofile.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Enabled', 'buddypress' ) ?> &nbsp;
			<input type="radio" name="bp_components[bp-xprofile.php]" value="0"<?php if ( isset( $disabled_components['bp-xprofile.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Disabled', 'buddypress' ) ?>
		</div>

		<img src="<?php echo plugins_url( 'buddypress/screenshot-2.gif' ) ?>" alt="Activity Streams" />
		<p><?php _e( "Fully editable profile fields allow you to define the fields users can fill in to describe themselves. Tailor profile fields to suit your audience.", 'buddypress' ) ?></p>
	</div>

	<div class="component">
		<h5><?php _e( "Friend Connections", 'buddypress' ) ?></h5>

		<div class="radio">
			<input type="radio" name="bp_components[bp-friends.php]" value="1"<?php if ( !isset( $disabled_components['bp-friends.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Enabled', 'buddypress' ) ?> &nbsp;
			<input type="radio" name="bp_components[bp-friends.php]" value="0"<?php if ( isset( $disabled_components['bp-friends.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Disabled', 'buddypress' ) ?>
		</div>

		<img src="<?php echo plugins_url( 'buddypress/screenshot-4.gif' ) ?>" alt="Activity Streams" />
		<p><?php _e( "Let your users make connections so they can track the activity of others, or filter on only those users they care about the most.", 'buddypress' ) ?></p>
	</div>

	<div class="component">
		<h5><?php _e( "Discussion Forums", 'buddypress' ) ?></h5>

		<div class="radio">
			<input type="radio" name="bp_components[bp-forums.php]" value="1"<?php if ( !isset( $disabled_components['bp-forums.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Enabled', 'buddypress' ) ?> &nbsp;
			<input type="radio" name="bp_components[bp-forums.php]" value="0"<?php if ( isset( $disabled_components['bp-forums.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Disabled', 'buddypress' ) ?>
		</div>

		<img src="<?php echo plugins_url( 'buddypress/screenshot-6.gif' ) ?>" alt="Activity Streams" />
		<p><?php _e( "Full powered discussion forums built directly into groups allow for more conventional in-depth conversations. <strong>NOTE: This will require an extra (but easy) setup step.</strong>", 'buddypress' ) ?></p>
	</div>

	<div class="component">
		<h5><?php _e( "Activity Streams", 'buddypress' ) ?></h5>

		<div class="radio">
			<input type="radio" name="bp_components[bp-activity.php]" value="1"<?php if ( !isset( $disabled_components['bp-activity.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Enabled', 'buddypress' ) ?> &nbsp;
			<input type="radio" name="bp_components[bp-activity.php]" value="0"<?php if ( isset( $disabled_components['bp-activity.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Disabled', 'buddypress' ) ?>
		</div>

		<img src="<?php echo plugins_url( 'buddypress/screenshot-1.gif' ) ?>" alt="Activity Streams" />
		<p><?php _e( "Global, personal and group activity streams with threaded commenting, direct posting, favoriting and @mentions. All with full RSS feed and email notification support.", 'buddypress' ) ?></p>
	</div>

	<div class="component">
		<h5><?php _e( "Extensible Groups", 'buddypress' ) ?></h5>

		<div class="radio">
			<input type="radio" name="bp_components[bp-groups.php]" value="1"<?php if ( !isset( $disabled_components['bp-groups.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Enabled', 'buddypress' ) ?> &nbsp;
			<input type="radio" name="bp_components[bp-groups.php]" value="0"<?php if ( isset( $disabled_components['bp-groups.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Disabled', 'buddypress' ) ?>
		</div>

		<img src="<?php echo plugins_url( 'buddypress/screenshot-3.gif' ) ?>" alt="Activity Streams" />
		<p><?php _e( "Powerful public, private or hidden groups allow your users to break the discussion down into specific topics with a separate activity stream and member listing.", 'buddypress' ) ?></p>
	</div>

	<div class="component">
		<h5><?php _e( "Private Messaging", 'buddypress' ) ?></h5>

		<div class="radio">
			<input type="radio" name="bp_components[bp-messages.php]" value="1"<?php if ( !isset( $disabled_components['bp-messages.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Enabled', 'buddypress' ) ?> &nbsp;
			<input type="radio" name="bp_components[bp-messages.php]" value="0"<?php if ( isset( $disabled_components['bp-messages.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Disabled', 'buddypress' ) ?>
		</div>

		<img src="<?php echo plugins_url( 'buddypress/screenshot-5.gif' ) ?>" alt="Activity Streams" />
		<p><?php _e( "Private messaging will allow your users to talk to each other directly, and in private. Not just limited to one on one discussions, your users can send messages to multiple recipients.", 'buddypress' ) ?></p>
	</div>

	<?php if ( is_multisite() ) : ?>

		<div class="component">
			<h5><?php _e( "Blog Tracking", 'buddypress' ) ?></h5>

			<div class="radio">
				<input type="radio" name="bp_components[bp-blogs.php]" value="1"<?php if ( !isset( $disabled_components['bp-blogs.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Enabled', 'buddypress' ) ?> &nbsp;
				<input type="radio" name="bp_components[bp-blogs.php]" value="0"<?php if ( isset( $disabled_components['bp-blogs.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Disabled', 'buddypress' ) ?>
			</div>

			<img src="<?php echo plugins_url( 'buddypress/screenshot-7.gif' ) ?>" alt="Activity Streams" />
				<p><?php _e( "Track new blogs, new posts and new comments across your entire blog network.", 'buddypress' ) ?></p>
		</div>

	<?php else: ?>

		<input type="hidden" name="bp_components[bp-blogs.php]" value="0" />

	<?php endif; ?>
	
	<?php
}

/**
 * Loads admin panel styles and scripts.
 *
 * @package BuddyPress Core
 * @since {@internal Unknown}}
 */
function bp_core_add_admin_menu_styles() {
	if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG )
		wp_enqueue_style( 'bp-admin-css', apply_filters( 'bp_core_admin_css', BP_PLUGIN_URL . '/bp-core/css/admin.dev.css' ), array(), BP_VERSION );
	else
		wp_enqueue_style( 'bp-admin-css', apply_filters( 'bp_core_admin_css', BP_PLUGIN_URL . '/bp-core/css/admin.css' ), array(), BP_VERSION );

	wp_enqueue_script( 'thickbox' );
	wp_enqueue_style( 'thickbox' );
}

?>