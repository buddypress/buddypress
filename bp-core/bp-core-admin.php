<?php

function bp_core_admin_settings() {
	global $wpdb, $bp;
?>
	
	<?php
	if ( isset( $_POST['bp-admin-submit'] ) && isset( $_POST['bp-admin'] ) ) {
		if ( !check_admin_referer('bp-admin') )
			return false;
		
		// Settings form submitted, now save the settings.
		foreach ( $_POST['bp-admin'] as $key => $value ) {
			
			if ( function_exists( 'xprofile_install' ) ) {
				if ( 'bp-xprofile-base-group-name' == $key ) {
					$wpdb->query( $wpdb->prepare( "UPDATE {$bp->profile->table_name_groups} SET name = %s WHERE name = %s AND id = 1", $value, stripslashes( get_site_option('bp-xprofile-base-group-name') ) ) );
				}
				
				if ( 'bp-xprofile-fullname-field-name' == $key ) {
					$wpdb->query( $wpdb->prepare( "UPDATE {$bp->profile->table_name_fields} SET name = %s WHERE name = %s AND group_id = 1", $value, stripslashes( get_site_option('bp-xprofile-fullname-field-name') ) ) );
				}
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

		<form action="<?php $_SERVER['PHP_SELF'] ?>" method="post" id="bp-admin-form">
		
			<table class="form-table">
			<tbody>
				<?php if ( function_exists( 'xprofile_install' ) ) :?>
				<tr>
					<th scope="row"><?php _e( 'Base profile group name', 'buddypress' ) ?>:</th>
					<td>
						<input name="bp-admin[bp-xprofile-base-group-name]" id="bp-xprofile-base-group-name" value="<?php echo get_site_option('bp-xprofile-base-group-name') ?>" />
					</td>			
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Full Name field name', 'buddypress' ) ?>:</th>
					<td>
						<input name="bp-admin[bp-xprofile-fullname-field-name]" id="bp-xprofile-fullname-field-name" value="<?php echo get_site_option('bp-xprofile-fullname-field-name') ?>" />
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
				<?php if ( function_exists('bp_wire_install') ) { ?>
				<tr>
					<th scope="row"><?php _e( 'Allow non-friends to post on profile wires?', 'buddypress' ) ?>:</th>
					<td>
						<input type="radio" name="bp-admin[non-friend-wire-posting]"<?php if ( (int)get_site_option( 'non-friend-wire-posting' ) ) : ?> checked="checked"<?php endif; ?> id="bp-admin-non-friend-wire-post" value="1" /> <?php _e( 'Yes', 'buddypress' ) ?> &nbsp;
						<input type="radio" name="bp-admin[non-friend-wire-posting]"<?php if ( !(int)get_site_option( 'non-friend-wire-posting' ) ) : ?> checked="checked"<?php endif; ?> id="bp-admin-non-friend-wire-post" value="0" /> <?php _e( 'No', 'buddypress' ) ?>
					</td>			
				</tr>
				<?php } ?>
				<tr>
					<th scope="row"><?php _e('Select theme to use for BuddyPress generated pages', 'buddypress' ) ?>:</th>
					<td>
						<?php $themes = bp_core_get_member_themes() ?>
						<?php if ( $themes ) : ?>
						<select name="bp-admin[active-member-theme]" id="active-member-theme">
							<?php 
							for ( $i = 0; $i < count($themes); $i++ ) { 
								if ( $themes[$i]['template'] == get_site_option( 'active-member-theme' ) ) {
									$selected = ' selected="selected"';
								} else {
									$selected = '';
								}
							?>
							<option<?php echo $selected ?> value="<?php echo $themes[$i]['template'] ?>"><?php echo $themes[$i]['name'] ?> (<?php echo $themes[$i]['version'] ?>)</option>
							<?php } ?>
						</select>
						<?php else : ?>
							<div class="error">
								<p><?php printf( __( '<strong>You do not have any BuddyPress themes installed.</strong><p style="line-height: 150%%">Please move the default BuddyPress themes to their correct location (move %s to %s) and reload this page. You can <a href="http://buddypress.org/extend/themes" title="Download">download more themes here</a>.</p>', 'buddypress' ), BP_PLUGIN_DIR . '/bp-themes/', WP_CONTENT_DIR . '/bp-themes/' ) ?></p>
							</div>
							<p><?php _e( 'No Themes Installed.', 'buddypress' ) ?></p>
						<?php endif; ?>
					</td>			
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Default User Avatar', 'buddypress' ) ?></th>
					<td>
						<p><?php _e( 'For users without a custom avatar of their own, you can either display a generic logo or a generated one based on their email address', 'buddypress' ) ?></p>

						<label><input name="bp-admin[user-avatar-default]" id="avatar_mystery" value="mystery" type="radio" <?php if ( get_site_option( 'user-avatar-default' ) == 'mystery' ) : ?> checked="checked"<?php endif; ?> /> &nbsp;<img alt="" src="http://www.gravatar.com/avatar/<?php md5( $ud->user_email ) ?>&amp;?s=32&amp;d=<?php echo BP_PLUGIN_URL . '/bp-core/images/mystery-man.jpg' ?>&amp;r=PG&amp;forcedefault=1" class="avatar avatar-32" height="32" width="32"> &nbsp;<?php _e( 'Mystery Man', 'buddypress' ) ?></label><br>
						<label><input name="bp-admin[user-avatar-default]" id="avatar_identicon" value="identicon" type="radio" <?php if ( get_site_option( 'user-avatar-default' ) == 'identicon' ) : ?> checked="checked"<?php endif; ?> /> &nbsp;<img alt="" src="http://www.gravatar.com/avatar/<?php md5( $ud->user_email ) ?>?s=32&amp;d=identicon&amp;r=PG&amp;forcedefault=1" class="avatar avatar-32" height="32" width="32"> &nbsp;<?php _e( 'Identicon (Generated)', 'buddypress' ) ?></label><br>
						<label><input name="bp-admin[user-avatar-default]" id="avatar_wavatar" value="wavatar" type="radio" <?php if ( get_site_option( 'user-avatar-default' ) == 'wavatar' ) : ?> checked="checked"<?php endif; ?> /> &nbsp;<img alt="" src="http://www.gravatar.com/avatar/<?php md5( $ud->user_email ) ?>?s=32&amp;d=wavatar&amp;r=PG&amp;forcedefault=1" class="avatar avatar-32" height="32" width="32"> &nbsp;<?php _e( 'Wavatar (Generated)', 'buddypress' ) ?> </label><br>
						<label><input name="bp-admin[user-avatar-default]" id="avatar_monsterid" value="monsterid" type="radio" <?php if ( get_site_option( 'user-avatar-default' ) == 'monsterid' ) : ?> checked="checked"<?php endif; ?> /> &nbsp;<img alt="" src="http://www.gravatar.com/avatar/<?php md5( $ud->user_email ) ?>?s=32&amp;d=monsterid&amp;r=PG&amp;forcedefault=1" class="avatar avatar-32" height="32" width="32"> &nbsp;<?php _e( 'MonsterID (Generated)', 'buddypress' ) ?></label>
					</td>
				</tr>

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

function bp_core_admin_component_setup() {
	global $wpdb, $bp;
?>
	
	<?php
	if ( isset( $_POST['bp-admin-component-submit'] ) && isset( $_POST['bp_components'] ) ) {
		if ( !check_admin_referer('bp-admin-component-setup') )
			return false;
		
		// Settings form submitted, now save the settings.
		foreach ( $_POST['bp_components'] as $key => $value ) {
			if ( !(int) $value )
				$disabled[$key] = 1;	
		}
		update_site_option( 'bp-deactivated-components', $disabled );
	}
	?>
	
	<div class="wrap">
		
		<h2><?php _e( 'BuddyPress Component Setup', 'buddypress' ) ?></h2>
		
		<?php if ( isset( $_POST['bp-admin-component-submit'] ) ) : ?>
			<div id="message" class="updated fade">
				<p><?php _e( 'Settings Saved', 'buddypress' ) ?></p>
			</div>
		<?php endif; ?>
	
		<form action="<?php $_SERVER['PHP_SELF'] ?>" method="post" id="bp-admin-component-form">
		
			<p>
			<?php _e( 
				'By default, all BuddyPress components are enabled. You can selectively disable any of the 
				components by using the form below. Your BuddyPress installation will continue to function, however
				the features of the disabled components will no longer be accessible to
				anyone using the site.
				', 'buddypress' )?>
			</p>
			
			<?php $disabled_components = get_site_option( 'bp-deactivated-components' ); ?>
			
			<table class="form-table" style="width: 80%">
			<tbody>
				<?php if ( file_exists( BP_PLUGIN_DIR . '/bp-activity.php') ) : ?>
				<tr>
					<td><h3><?php _e( 'Activity Streams', 'buddypress' ) ?></h3><p><?php _e( 'Tracks user activity across the entire site.', 'buddypress' ) ?></p></td>
					<td>
						<input type="radio" name="bp_components[bp-activity.php]" value="1"<?php if ( !isset( $disabled_components['bp-activity.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Enabled', 'buddypress' ) ?> &nbsp;
						<input type="radio" name="bp_components[bp-activity.php]" value="0"<?php if ( isset( $disabled_components['bp-activity.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Disabled', 'buddypress' ) ?>
					</td>			
				</tr>
				<?php endif; ?>
				<?php if ( file_exists( BP_PLUGIN_DIR . '/bp-blogs.php') ) : ?>
				<tr>
					<td><h3><?php _e( 'Blog Tracking', 'buddypress' ) ?></h3><p><?php _e( 'Tracks blogs, blog posts and blogs comments for a user across a WPMU installation.', 'buddypress' ) ?></p></td>
					<td>
						<input type="radio" name="bp_components[bp-blogs.php]" value="1"<?php if ( !isset( $disabled_components['bp-blogs.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Enabled', 'buddypress' ) ?>  &nbsp;
						<input type="radio" name="bp_components[bp-blogs.php]" value="0"<?php if ( isset( $disabled_components['bp-blogs.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Disabled', 'buddypress' ) ?>
					</td>			
				</tr>
				<?php endif; ?>
				<?php if ( file_exists( BP_PLUGIN_DIR . '/bp-forums.php') ) : ?>
				<tr>
					<td><h3><?php _e( 'bbPress Forums', 'buddypress' ) ?></h3><p><?php _e( 'Activates bbPress forum support within BuddyPress groups or any other custom component.', 'buddypress' ) ?></p></td>
					<td>
						<input type="radio" name="bp_components[bp-forums.php]" value="1"<?php if ( !isset( $disabled_components['bp-forums.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Enabled', 'buddypress' ) ?>  &nbsp;
						<input type="radio" name="bp_components[bp-forums.php]" value="0"<?php if ( isset( $disabled_components['bp-forums.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Disabled', 'buddypress' ) ?>
					</td>			
				</tr>
				<?php endif; ?>
				<?php if ( file_exists( BP_PLUGIN_DIR . '/bp-friends.php') ) : ?>
				<tr>
					<td><h3><?php _e( 'Friends', 'buddypress' ) ?></h3><p><?php _e( 'Allows the creation of friend connections between users.', 'buddypress' ) ?></p></td>
					<td>
						<input type="radio" name="bp_components[bp-friends.php]" value="1"<?php if ( !isset( $disabled_components['bp-friends.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Enabled', 'buddypress' ) ?>  &nbsp;
						<input type="radio" name="bp_components[bp-friends.php]" value="0"<?php if ( isset( $disabled_components['bp-friends.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Disabled', 'buddypress' ) ?>
					</td>			
				</tr>
				<?php endif; ?>
				<?php if ( file_exists( BP_PLUGIN_DIR . '/bp-groups.php') ) : ?>
				<tr>
					<td><h3><?php _e( 'Groups', 'buddypress' ) ?></h3><p><?php _e( 'Let users create, join and participate in groups.', 'buddypress' ) ?></p></td>
					<td>
						<input type="radio" name="bp_components[bp-groups.php]" value="1"<?php if ( !isset( $disabled_components['bp-groups.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Enabled', 'buddypress' ) ?>  &nbsp;
						<input type="radio" name="bp_components[bp-groups.php]" value="0"<?php if ( isset( $disabled_components['bp-groups.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Disabled', 'buddypress' ) ?>
					</td>			
				</tr>
				<?php endif; ?>
				<?php if ( file_exists( BP_PLUGIN_DIR . '/bp-messages.php') ) : ?>
				<tr>
					<td><h3><?php _e( 'Private Messaging', 'buddypress' ) ?></h3><p><?php _e( 'Let users send private messages to one another. Site admins can also send site-wide notices.', 'buddypress' ) ?></p></td>
					<td>
						<input type="radio" name="bp_components[bp-messages.php]" value="1"<?php if ( !isset( $disabled_components['bp-messages.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Enabled', 'buddypress' ) ?>  &nbsp;
						<input type="radio" name="bp_components[bp-messages.php]" value="0"<?php if ( isset( $disabled_components['bp-messages.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Disabled', 'buddypress' ) ?>
					</td>			
				</tr>
				<?php endif; ?>
				<?php if ( file_exists( BP_PLUGIN_DIR . '/bp-wire.php') ) : ?>
				<tr>
					<td><h3><?php _e( 'Comment Wire', 'buddypress' ) ?></h3><p><?php _e( 'Let users leave a comment on groups, profiles and custom components.', 'buddypress' ) ?></p></td>
					<td>
						<input type="radio" name="bp_components[bp-wire.php]" value="1"<?php if ( !isset( $disabled_components['bp-wire.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Enabled', 'buddypress' ) ?>  &nbsp;
						<input type="radio" name="bp_components[bp-wire.php]" value="0"<?php if ( isset( $disabled_components['bp-wire.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Disabled', 'buddypress' ) ?>
					</td>			
				</tr>
				<?php endif; ?>
				<?php if ( file_exists( BP_PLUGIN_DIR . '/bp-xprofile.php') ) : ?>
				<tr>
					<td><h3><?php _e( 'Extended Profiles', 'buddypress' ) ?></h3><p><?php _e( 'Activates customizable profiles and avatars for site users.', 'buddypress' ) ?></p></td>
					<td width="45%">
						<input type="radio" name="bp_components[bp-xprofile.php]" value="1"<?php if ( !isset( $disabled_components['bp-xprofile.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Enabled', 'buddypress' ) ?>  &nbsp;
						<input type="radio" name="bp_components[bp-xprofile.php]" value="0"<?php if ( isset( $disabled_components['bp-xprofile.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Disabled', 'buddypress' ) ?>
					</td>
				</tr>
				<?php endif; ?>
			</tbody>
			</table>
			
			<p class="submit">
				<input class="button-primary" type="submit" name="bp-admin-component-submit" id="bp-admin-component-submit" value="<?php _e( 'Save Settings', 'buddypress' ) ?>"/>
			</p>
			
			<?php wp_nonce_field( 'bp-admin-component-setup' ) ?>
		
		</form>

	</div>
	
<?php 
}

?>