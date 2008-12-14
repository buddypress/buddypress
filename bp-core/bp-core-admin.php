<?php

function bp_core_admin_settings() { ?>
	
	<?php
	if ( isset( $_POST['bp-admin-submit'] ) && isset( $_POST['bp-admin'] ) ) {
		if ( !check_admin_referer('bp-admin') )
			return false;
		
		// Settings form submitted, now save the settings.
		foreach ( $_POST['bp-admin'] as $key => $value ) {
			update_site_option( $key, $value );
		}
	}
	?>
	
	<div class="wrap">
		
		<h2><?php _e( 'BuddyPress Settings', 'buddypress' ) ?></h2>
	
		<form action="<?php $_SERVER['PHP_SELF'] ?>" method="post" id="bp-admin-form">
		
			<table class="form-table">
			<tbody>
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
			</tbody>
			</table>
	
			<p class="submit">
				<input type="submit" name="bp-admin-submit" id="bp-admin-submit" value="<?php _e( 'Save Settings', 'buddypress' ) ?>" onclick="this.disabled = true; this.value = '<?php _e( 'Loading...', 'buddypress' ) ?>';" />
			</p>
		
			<?php wp_nonce_field( 'bp-admin') ?>
		
		</form>
		
	</div>
	
<?php 
}

?>