<?php
function bp_forums_add_admin_menu() {
	global $wpdb, $bp;

	if ( is_site_admin() ) {
		/* Add the administration tab under the "Site Admin" tab for site administrators */
		add_submenu_page( 'wpmu-admin.php', __( 'bbPress Forums', 'buddypress' ), __( 'bbPress Forums', 'buddypress' ), 1, "bp_forums_settings", "bp_forums_bbpress_admin" );
	}
}
add_action( 'admin_menu', 'bp_forums_add_admin_menu' );

function bp_forums_bbpress_admin() { 
	global $bp, $bbpress_live;
	
	if ( !is_object( $bbpress_live ) ) {
		include_once( ABSPATH . WPINC . '/class-IXR.php' );
		$bbpress_live = new bbPress_Live();
	}
	
	if ( isset( $_POST['submit'] ) ) {
		check_admin_referer('bbpress-settings');

		$_fetch_options = array(
			'target_uri' => stripslashes((string) $_POST['target_uri']),
			'username' => stripslashes((string) $_POST['username']),
			'password' => stripslashes((string) $_POST['password']),
			'always_use_auth' => (bool) $_POST['always_use_auth']
		);
		update_option( 'bbpress_live_fetch', $_fetch_options );

		$_options = array(
			'cache_enabled' => (bool) $_POST['cache_enabled'],
			'cache_timeout' => (int) $_POST['cache_timeout'],
			'widget_forums' => (bool) $_POST['widget_forums'],
			'widget_topics' => (bool) $_POST['widget_topics'],
			'post_to_topic' => (bool) $_POST['post_to_topic'],
			'post_to_topic_forum' => stripslashes((string) $_POST['post_to_topic_forum']),
			'post_to_topic_delay' => (int) $_POST['post_to_topic_delay']
		);
		update_option( 'bbpress_live', $_options );		
	
		$fetch_options = $_fetch_options;
		$options = $_options;
		
 		do_action( 'bp_forums_bbpress_admin', $_fetch_options, $_options );
		
	} else {
		$fetch_options = $bbpress_live->fetch->options;
		$options = $bbpress_live->options;
	}
?>
	<div class="wrap">

		<h2><?php _e( 'Group Forum Settings', 'buddypress' ) ?></h2>
		<br />
		
		<?php if ( isset($path_success) ) : ?><?php echo "<p id='message' class='updated fade'>$path_success</p>" ?><?php endif; ?>
			
		<p><?php _e( 'To enable forums for each group in a BuddyPress installation, you must first download, install, and setup bbPress and integrate it with WordPress MU.', 'buddypress' ) ?></p>
		<p><?php _e( 'Once you have bbPress set up correctly, enter the options below so that BuddyPress can connect.', 'buddypress' ) ?></p>
		
		<form action="<?php echo site_url() . '/wp-admin/admin.php?page=bp_forums_settings' ?>" name="bbpress-path-form" id="bbpress-path-form" method="post">				
			<input type="hidden" name="option_page" value="bbpress-live" />
			
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="target_uri"><?php _e( 'bbPress URL', 'buddypress' ) ?></label></th>
					<td>
						<input name="target_uri" type="text" id="target_uri" value="<?php echo attribute_escape( $fetch_options['target_uri'] ); ?>" size="60" /><br />
						<?php _e( 'The URL of the location you installed bbPress. For example, http://example.com/forums/', 'buddypress' ); ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="username"><?php _e( 'bbPress username', 'buddypress' ) ?></label></th>
					<td>
						<input name="username" type="text" id="username" value="<?php echo attribute_escape( $fetch_options['username'] ); ?>" size="20" /><br />
						<?php _e( 'The username for the user (with admin rights) that you created for BuddyPress integration', 'buddypress' ); ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="password"><?php _e( 'bbPress password', 'buddypress' ) ?></label></th>
					<td>
						<input name="password" type="password" id="password" value="<?php echo attribute_escape( $fetch_options['password'] ); ?>" size="20" /><br />
						<?php _e( 'The password for the user (with admin rights) that you created for BuddyPress integration', 'buddypress' ); ?>
					</td>
				</tr>
			</table>
			<br />
			<h3><?php _e( 'Cache requests', 'buddypress' ) ?></h3>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="cache_enabled"><?php _e( 'Caching enabled', 'buddypress' ) ?></label></th>
					<td>
						<input name="cache_enabled" type="checkbox" id="cache_enabled" value="1"<?php echo( $options['cache_enabled'] ? ' checked="checked"' : '' ); ?> />
						<?php _e( 'Turn on caching of requests to reduce latency and load.', 'buddypress' ); ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="cache_timeout"><?php _e( 'Cache timeout', 'buddypress' ) ?></label></th>
					<td>
						<input name="cache_timeout" type="text" id="cache_timeout" value="<?php echo attribute_escape( $options['cache_timeout'] ); ?>" size="10" /> <?php _e( '(seconds)', 'buddypress' ) ?><br />
						<?php _e( 'The amount of time in seconds that a cached request is valid for.', 'buddypress' ); ?>
					</td>
				</tr>
			</table>
			<br />
			<p class="submit">
				<input type="submit" name="submit" value="<?php _e('Save Settings', 'buddypress') ?>"/>
			</p>
			<?php wp_nonce_field('bbpress-settings') ?>
		</form>
	</div>
<?php
}
?>