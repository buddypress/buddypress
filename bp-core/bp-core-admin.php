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
					$wpdb->query( $wpdb->prepare( "UPDATE $bp->profile->table_name_groups SET name = %s WHERE name = %s AND id = 1", $value, stripslashes( get_site_option('bp-xprofile-base-group-name') ) ) );
				}
				
				if ( 'bp-xprofile-fullname-field-name' == $key ) {
					$wpdb->query( $wpdb->prepare( "UPDATE $bp->profile->table_name_fields SET name = %s WHERE name = %s AND group_id = 1", $value, stripslashes( get_site_option('bp-xprofile-fullname-field-name') ) ) );
				}
			}
			
			update_site_option( $key, $value );
		}
	}
	?>
	
	<div class="wrap">
		
		<h2><?php _e( 'BuddyPress Settings', 'buddypress' ) ?></h2>
	
		<form action="<?php $_SERVER['PHP_SELF'] ?>" method="post" id="bp-admin-form">
		
			<table class="form-table">
			<tbody>
				<?php if ( function_exists( 'xprofile_install' ) ) :?>
				<tr>
					<th scope="row"><?php _e('Base profile group name', 'buddypress') ?>:</th>
					<td>
						<input name="bp-admin[bp-xprofile-base-group-name]" id="bp-xprofile-base-group-name" value="<?php echo get_site_option('bp-xprofile-base-group-name') ?>" />
					</td>			
				</tr>
				<tr>
					<th scope="row"><?php _e('Full Name field name', 'buddypress') ?>:</th>
					<td>
						<input name="bp-admin[bp-xprofile-fullname-field-name]" id="bp-xprofile-fullname-field-name" value="<?php echo get_site_option('bp-xprofile-fullname-field-name') ?>" />
					</td>
				</tr>
				<?php endif; ?>
				<tr>
					<th scope="row"><?php _e('Show admin bar for logged out users', 'buddypress') ?>:</th>
					<td>
						<input type="radio" name="bp-admin[show-loggedout-adminbar]"<?php if ( (int)get_site_option( 'show-loggedout-adminbar' ) ) : ?> checked="checked"<?php endif; ?> id="bp-admin-show-loggedout-adminbar-yes" value="1" /> Yes &nbsp;
						<input type="radio" name="bp-admin[show-loggedout-adminbar]"<?php if ( !(int)get_site_option( 'show-loggedout-adminbar' ) ) : ?> checked="checked"<?php endif; ?> id="bp-admin-show-loggedout-adminbar-no" value="0" /> No
					</td>			
				</tr>
				<?php if ( function_exists('bp_wire_install') ) { ?>
				<tr>
					<th scope="row"><?php _e('Allow non-friends to post on profile wires', 'buddypress') ?>:</th>
					<td>
						<input type="radio" name="bp-admin[non-friend-wire-posting]"<?php if ( (int)get_site_option( 'non-friend-wire-posting' ) ) : ?> checked="checked"<?php endif; ?> id="bp-admin-non-friend-wire-post" value="1" /> Yes &nbsp;
						<input type="radio" name="bp-admin[non-friend-wire-posting]"<?php if ( !(int)get_site_option( 'non-friend-wire-posting' ) ) : ?> checked="checked"<?php endif; ?> id="bp-admin-non-friend-wire-post" value="0" /> No
					</td>			
				</tr>
				<?php } ?>
				<tr>
					<th scope="row"><?php _e('Select theme to use for member pages', 'buddypress') ?>:</th>
					<td>
						<select name="bp-admin[active-member-theme]" id="active-member-theme">
							<?php $themes = bp_core_get_member_themes() ?>
							<?php 
								if ( $themes ) { 
									for ( $i = 0; $i < count($themes); $i++ ) { 
										if ( $themes[$i]['template'] == get_site_option( 'active-member-theme' ) ) {
											$selected = ' selected="selected"';
										} else {
											$selected = '';
										}
							?>
										<option<?php echo $selected ?> value="<?php echo $themes[$i]['template'] ?>"><?php echo $themes[$i]['name'] ?></option>
							<?php	
									}
								}
							?>

						</select>
					</td>			
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Default User Avatar', 'buddypress' ) ?></th>
					<td>
						<p><?php _e( 'For users without a custom avatar of their own, you can either display a generic logo or a generated one based on their email address', 'buddypress' ) ?></p>

						<label><input name="bp-admin[user-avatar-default]" id="avatar_mystery" value="mystery" type="radio" <?php if ( get_site_option( 'user-avatar-default' ) == 'mystery' ) : ?> checked="checked"<?php endif; ?> /> &nbsp;<img alt="" src="http://www.gravatar.com/avatar/<?php md5( $ud->user_email ) ?>&amp;?s=32&amp;d=<?php echo site_url( MUPLUGINDIR . '/bp-core/images/mystery-man.jpg') ?>&amp;r=PG&amp;forcedefault=1" class="avatar avatar-32" height="32" width="32"> &nbsp;<?php _e( 'Mystery Man', 'buddypress' ) ?></label><br>
						<label><input name="bp-admin[user-avatar-default]" id="avatar_identicon" value="identicon" type="radio" <?php if ( get_site_option( 'user-avatar-default' ) == 'identicon' ) : ?> checked="checked"<?php endif; ?> /> &nbsp;<img alt="" src="http://www.gravatar.com/avatar/<?php md5( $ud->user_email ) ?>?s=32&amp;d=identicon&amp;r=PG&amp;forcedefault=1" class="avatar avatar-32" height="32" width="32"> &nbsp;<?php _e( 'Identicon (Generated)', 'buddypress' ) ?></label><br>
						<label><input name="bp-admin[user-avatar-default]" id="avatar_wavatar" value="wavatar" type="radio" <?php if ( get_site_option( 'user-avatar-default' ) == 'wavatar' ) : ?> checked="checked"<?php endif; ?> /> &nbsp;<img alt="" src="http://www.gravatar.com/avatar/<?php md5( $ud->user_email ) ?>?s=32&amp;d=wavatar&amp;r=PG&amp;forcedefault=1" class="avatar avatar-32" height="32" width="32"> &nbsp;<?php _e( 'Wavatar (Generated)', 'buddypress' ) ?> </label><br>
						<label><input name="bp-admin[user-avatar-default]" id="avatar_monsterid" value="monsterid" type="radio" <?php if ( get_site_option( 'user-avatar-default' ) == 'monsterid' ) : ?> checked="checked"<?php endif; ?> /> &nbsp;<img alt="" src="http://www.gravatar.com/avatar/<?php md5( $ud->user_email ) ?>?s=32&amp;d=monsterid&amp;r=PG&amp;forcedefault=1" class="avatar avatar-32" height="32" width="32"> &nbsp;<?php _e( 'MonsterID (Generated)', 'buddypress' ) ?></label>
					</td>
				</tr>
			</tbody>
			</table>
	
			<p class="submit">
				<input type="submit" name="bp-admin-submit" id="bp-admin-submit" value="<?php _e( 'Save Settings', 'buddypress' ) ?>"/>
			</p>
			
			<h4><?php _e( 'BuddyPress Version Numbers', 'buddypress' ) ?></h4>
			
			<?php bp_core_print_version_numbers() ?>
		
			<?php wp_nonce_field( 'bp-admin') ?>
		
		</form>
		
	</div>
	
<?php 
}

?>