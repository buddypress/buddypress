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
		
		<h2>BuddyPress Settings</h2>
	
		<form action="<?php $_SERVER['PHPSELF'] ?>" method="post" id="bp-admin-form">
		
			<table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><?php _e('Show admin bar for logged out users', 'buddypress') ?>:</th>
					<td>
						<input type="radio" name="bp-admin[show-loggedout-adminbar]"<?php if ( (int)get_site_option( 'show-loggedout-adminbar' ) ) : ?> checked="checked"<?php endif; ?> id="bp-admin-show-loggedout-adminbar-yes" value="1" /> Yes &nbsp;
						<input type="radio" name="bp-admin[show-loggedout-adminbar]"<?php if ( !(int)get_site_option( 'show-loggedout-adminbar' ) ) : ?> checked="checked"<?php endif; ?> id="bp-admin-show-loggedout-adminbar-yes" value="0" /> No
					</td>			
				</tr>
			</tbody>
			</table>
	
			<p class="submit">
				<input type="submit" name="bp-admin-submit" id="bp-admin-submit" value="<?php _e( 'Save Settings', 'buddypress' ) ?>" />
			</p>
		
			<?php wp_nonce_field( 'bp-admin') ?>
		
		</form>
		
	</div>
	
<?php 
}

?>