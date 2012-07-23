<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

function bp_forums_add_admin_menu() {
	global $bp;

	if ( !is_super_admin() )
		return false;

	$page  = bp_core_do_network_admin()  ? 'settings.php' : 'options-general.php';

	// Add the administration tab under the "Site Admin" tab for site administrators
	$hook = add_submenu_page( $page, __( 'Forums', 'buddypress' ), __( 'Forums', 'buddypress' ), 'manage_options', 'bb-forums-setup', "bp_forums_bbpress_admin" );

	// Fudge the highlighted subnav item when on the BuddyPress Forums admin page
	add_action( "admin_head-$hook", 'bp_core_modify_admin_menu_highlight' );
}
add_action( bp_core_admin_hook(), 'bp_forums_add_admin_menu' );

/**
 * Outputs the markup for the bb-forums-admin panel
 */
function bp_forums_bbpress_admin() {
	global $bp;

	// The text and URL of the Site Wide Forums button differs depending on whether bbPress
	// is running
	if ( is_plugin_active( 'bbpress/bbpress.php' ) ) {
		// The bbPress admin page will always be on the root blog. switch_to_blog() will
		// pass through if we're already there.
		switch_to_blog( bp_get_root_blog_id() );
		$button_url = admin_url( add_query_arg( array( 'page' => 'bbpress' ), 'options-general.php' ) );
		restore_current_blog();

		$button_text = __( 'Configure Site Wide Forums', 'buddypress' );
	} else {
		$button_url = bp_get_admin_url( add_query_arg( array( 'tab' => 'plugin-information', 'plugin' => 'bbpress', 'TB_iframe' => 'true', 'width' => '640', 'height' => '500' ), 'plugin-install.php' ) );
		$button_text = __( 'Install Site Wide Forums', 'buddypress' );
	}

	$action = bp_get_admin_url( 'admin.php?page=bb-forums-setup&reinstall=1' ); ?>

	<div class="wrap">
		<?php screen_icon( 'buddypress' ); ?>

		<h2 class="nav-tab-wrapper"><?php bp_core_admin_tabs( __( 'Forums', 'buddypress' ) ); ?></h2>

		<?php if ( isset( $_POST['submit'] ) ) : ?>

			<div id="message" class="updated fade">
				<p><?php _e( 'Settings Saved.', 'buddypress' ) ?></p>
			</div>

		<?php endif; ?>

		<?php

		if ( isset( $_REQUEST['reinstall'] ) || !bp_forums_is_installed_correctly() ) :

			// Delete the bb-config.php location option
			bp_delete_option( 'bb-config-location' );
			bp_forums_bbpress_install_wizard();

		else : ?>

			<div style="width: 45%; float: left; margin-top: 20px;">
				<h3><?php _e( '(Installed)', 'buddypress' ); ?> <?php _e( 'Forums for Groups', 'buddypress' ) ?></h3>

				<p><?php _e( 'Give each individual group its own discussion forum. Choose this if you\'d like to keep your members\' conversations separated into distinct areas.' , 'buddypress' ); ?></p>
				<p class="description"><?php _e( 'You may use an existing bbPress installation if you have one.', 'buddypress' ); ?></p>

				<h4 style="margin-bottom: 10px;"><?php _e( 'Features', 'buddypress' ); ?></h4>
				<ul class="description" style="list-style: square; margin-left: 30px;">
					<li><?php _e( 'Group Integration',           'buddypress' ); ?></p></li>
					<li><?php _e( 'Member Profile Integration',  'buddypress' ); ?></p></li>
					<li><?php _e( 'Activity Stream Integration', 'buddypress' ); ?></p></li>
					<li><?php _e( '@ Mention Integration',       'buddypress' ); ?></p></li>
				</ul>

				<div>
					<a class="button button-primary" href="<?php echo $action ?>"><?php _e( 'Uninstall Group Forums', 'buddypress' ) ?></a> &nbsp;
				</div>
			</div>

			<div style="width: 45%; float: left; margin: 20px 0 20px 20px; padding: 0 20px 20px 20px; border: 1px solid #ddd; background-color: #fff;">
				<h3><?php _e( 'New! Site Wide Forums', 'buddypress' ) ?></h3>
				<p><?php _e( 'Your site will have central forums that are not isolated to any specific group. Choose this if you\'d like to have a central forum area for your members.', 'buddypress' ) ?></p>
				<p class="description"><?php _e( 'You may activate both Group and Site Wide forums, but this may create a poor experience for your members.', 'buddypress' ) ?></p>

				<h4 style="margin-bottom: 10px;"><?php _e( 'Features', 'buddypress' ); ?></h4>
				<ul class="description" style="list-style: square; margin-left: 30px;">
					<li><?php _e( 'Central Discussion Area',     'buddypress' ); ?></p></li>
					<li><?php _e( 'Forum Plugins Available',     'buddypress' ); ?></p></li>
					<li><?php _e( 'Activity Stream Integration', 'buddypress' ); ?></p></li>
					<li><?php _e( '@ Mention Integration',       'buddypress' ); ?></p></li>
				</ul>

				<div>
					<a class="button thickbox button-primary" href="<?php echo esc_attr( $button_url ) ?>"><?php echo esc_html( $button_text ) ?></a> &nbsp;
				</div>
			</div>

		<?php endif; ?>

		<p class="clear description"><?php printf( __( 'Need help deciding between Group Forums and Site Wide Forums? Visit <a href="%s">the BuddyPress codex</a> for more information.', 'buddypress' ), 'http://codex.buddypress.org/getting-started/installing-group-and-sitewide-forums/' ) ?></p>
	</div>
<?php
}

function bp_forums_bbpress_install_wizard() {
	$post_url                 = network_admin_url( 'admin.php?page=bb-forums-setup' );
	$bbpress_plugin_is_active = false;

	$step = isset( $_REQUEST['step'] ) ? $_REQUEST['step'] : '';

	// The text and URL of the Site Wide Forums button differs depending on whether bbPress
	// is running
	if ( is_plugin_active( 'bbpress/bbpress.php' ) ) {
		$bbpress_plugin_is_active = true;

		// The bbPress admin page will always be on the root blog. switch_to_blog() will
		// pass through if we're already there.
		switch_to_blog( bp_get_root_blog_id() );
		$button_url = admin_url( add_query_arg( array( 'page' => 'bbpress' ), 'options-general.php' ) );
		restore_current_blog();

		$button_text = __( 'Configure Site Wide Forums', 'buddypress' );
	} else {
		$button_url = bp_get_admin_url( add_query_arg( array( 'tab' => 'plugin-information', 'plugin' => 'bbpress', 'TB_iframe' => 'true', 'width' => '640', 'height' => '500' ), 'plugin-install.php' ) );
		$button_text = __( 'Install Site Wide Forums', 'buddypress' );
	}

	switch( $step ) {
		case 'existing':
			if ( isset( $_REQUEST['doinstall'] ) && ( 1 == (int) $_REQUEST['doinstall'] ) ) {
				if ( !bp_forums_configure_existing_install() ) {
					_e( 'The bb-config.php file was not found at that location, please try again.', 'buddypress' );
				} else {
					?>
					<h3><?php _e( 'Forums were set up correctly using your existing bbPress install!', 'buddypress' ) ?></h3>
					<p><?php _e( 'BuddyPress will now use its internal copy of bbPress to run the forums on your site. If you wish, you can remove your old bbPress installation files, as long as you keep the bb-config.php file in the same location.', 'buddypress' ) ?></p><?php
				}
			} else { ?>

					<form action="" method="post">
						<h3><?php _e( 'Existing bbPress Installation', 'buddypress' ) ?></h3>
						<p><?php _e( "BuddyPress can make use of your existing bbPress install. Just provide the location of your <code>bb-config.php</code> file, and BuddyPress will do the rest.", 'buddypress' ) ?></p>
						<p><label><code>bb-config.php</code> file location:</label><br /><input style="width: 50%" type="text" name="bbconfigloc" id="bbconfigloc" value="<?php echo str_replace( 'buddypress', '', $_SERVER['DOCUMENT_ROOT'] ) ?>" /></p>
						<p><input type="submit" class="button-primary" value="<?php _e( 'Complete Installation', 'buddypress' ) ?>" /></p>
						<input type="hidden" name="step" value="existing" />
						<input type="hidden" name="doinstall" value="1" />
						<?php wp_nonce_field( 'bp_forums_existing_install_init' ) ?>
					</form>

				<?php
			}
		break;

		case 'new':
			if ( isset( $_REQUEST['doinstall'] ) && 1 == (int)$_REQUEST['doinstall'] ) {
				$result = bp_forums_bbpress_install();

				switch ( $result ) {
					case 1:
						_e( '<p>All done! Configuration settings have been saved to the file <code>bb-config.php</code> in the root of your WordPress install.</p>', 'buddypress' );
						break;
					default:
						// Just write the contents to screen
						_e( '<p>A configuration file could not be created. No problem, but you will need to save the text shown below into a file named <code>bb-config.php</code> in the root directory of your WordPress installation before you can start using the forum functionality.</p>', 'buddypress' ); ?>

						<textarea style="display:block; margin-top: 30px; width: 80%;" rows="50"><?php echo htmlspecialchars( $result ); ?></textarea>

					<?php
						break;
				}
			} else { ?>

				<h3><?php _e( 'New bbPress Installation', 'buddypress' ) ?></h3>
				<p><?php _e( "You've decided to set up a new installation of bbPress for forum management in BuddyPress. This is very simple and is usually just a one click
				process. When you're ready, hit the link below.", 'buddypress' ) ?></p>
				<p><a class="button-primary" href="<?php echo wp_nonce_url( $post_url . '&step=new&doinstall=1', 'bp_forums_new_install_init' ) ?>"><?php _e( 'Complete Installation', 'buddypress' ) ?></a></p>

				<?php
			}
		break;

		default:
			if ( !file_exists( BP_PLUGIN_DIR . '/bp-forums/bbpress/' ) ) { ?>

				<div id="message" class="error">
					<p><?php printf( __( 'bbPress files were not found. To install the forums component you must download a copy of bbPress and make sure it is in the folder: "%s"', 'buddypress' ), 'wp-content/plugins/buddypress/bp-forums/bbpress/' ) ?></p>
				</div>

			<?php } else {

				// Include the plugin install

				add_thickbox();
				wp_enqueue_script( 'plugin-install' );
				wp_admin_css( 'plugin-install' );
			?>

				<div style="width: 45%; float: left;  margin-top: 20px;">
					<h3><?php _e( 'Forums for Groups', 'buddypress' ) ?></h3>

					<p><?php _e( 'Give each individual group its own discussion forum. Choose this if you\'d like to keep your members\' conversations separated into distinct areas.' , 'buddypress' ); ?></p>
					<p class="description"><?php _e( 'You may use an existing bbPress installation if you have one.', 'buddypress' ); ?></p>

					<h4 style="margin-bottom: 10px;"><?php _e( 'Features', 'buddypress' ); ?></h4>
					<ul class="description" style="list-style: square; margin-left: 30px;">
						<li><?php _e( 'Group Integration',           'buddypress' ); ?></p></li>
						<li><?php _e( 'Member Profile Integration',  'buddypress' ); ?></p></li>
						<li><?php _e( 'Activity Stream Integration', 'buddypress' ); ?></p></li>
						<li><?php _e( '@ Mention Integration',       'buddypress' ); ?></p></li>
					</ul>

					<div>
						<a class="button button-primary" href="<?php echo $post_url . '&step=new' ?>"><?php _e( 'Install Group Forums', 'buddypress' ) ?></a> &nbsp;
						<a class="button" href="<?php echo $post_url . '&step=existing' ?>"><?php _e( 'Use Existing Installation', 'buddypress' ) ?></a>
					</div>
				</div>

				<div style="width: 45%; float: left; margin: 20px 0 20px 20px; padding: 0 20px 20px 20px; border: 1px solid #ddd; background-color: #fff;">
					<h3><?php _e( 'New! Site Wide Forums', 'buddypress' ) ?></h3>
					<p><?php _e( 'Your site will have central forums that are not isolated to any specific group. Choose this if you\'d like to have a central forum area for your members.', 'buddypress' ) ?></p>
					<p class="description"><?php _e( 'You may activate both Group and Site Wide forums, but this may create a poor experience for your members.', 'buddypress' ) ?></p>

					<h4 style="margin-bottom: 10px;"><?php _e( 'Features', 'buddypress' ); ?></h4>
					<ul class="description" style="list-style: square; margin-left: 30px;">
						<li><?php _e( 'Central Discussion Area',     'buddypress' ); ?></p></li>
						<li><?php _e( 'Forum Plugins Available',     'buddypress' ); ?></p></li>
						<li><?php _e( 'Activity Stream Integration', 'buddypress' ); ?></p></li>
						<li><?php _e( '@ Mention Integration',       'buddypress' ); ?></p></li>
					</ul>
					<div>
						<a class="button button-primary <?php if ( ! $bbpress_plugin_is_active ) { echo esc_attr( 'thickbox' ); }?>" href="<?php echo esc_attr( $button_url ) ?>"><?php echo esc_html( $button_text ) ?></a> &nbsp;
					</div>
				</div>

			<?php }
		break;
	}
}

function bp_forums_configure_existing_install() {
	global $wpdb, $bbdb;

	check_admin_referer( 'bp_forums_existing_install_init' );

	// Sanitize $_REQUEST['bbconfigloc']
	$_REQUEST['bbconfigloc'] = apply_filters( 'bp_forums_bbconfig_location', $_REQUEST['bbconfigloc'] );

	if ( false === strpos( $_REQUEST['bbconfigloc'], 'bb-config.php' ) ) {
		if ( '/' != substr( $_REQUEST['bbconfigloc'], -1, 1 ) )
			$_REQUEST['bbconfigloc'] .= '/';

		$_REQUEST['bbconfigloc'] .= 'bb-config.php';
	}

	bp_update_option( 'bb-config-location', $_REQUEST['bbconfigloc'] );

	if ( !file_exists( $_REQUEST['bbconfigloc'] ) ) {
		return false;
	}

	return true;
}

function bp_forums_bbpress_install( $location = '' ) {
	global $wpdb, $bbdb, $bp;

	check_admin_referer( 'bp_forums_new_install_init' );

	if ( empty( $location ) ) {
		$location = ABSPATH . 'bb-config.php';
	}

	// Create the bb-config.php file
	$initial_write = bp_forums_bbpress_write(
		BP_PLUGIN_DIR . '/bp-forums/bbpress/bb-config-sample.php',
		$location,
		array(
			"define( 'BBDB_NAME',"  => array( "'bbpress'",                     	"'" . DB_NAME . "'" ),
			"define( 'BBDB_USER',"  => array( "'username'",                    	"'" . DB_USER . "'" ),
			"define( 'BBDB_PASSWO"  => array( "'password'",                    	"'" . DB_PASSWORD . "'" ),
			"define( 'BBDB_HOST',"  => array( "'localhost'",                   	"'" . DB_HOST . "'" ),
			"define( 'BBDB_CHARSE"  => array( "'utf8'",                        	"'" . DB_CHARSET . "'" ),
			"define( 'BBDB_COLLAT"  => array( "''",                            	"'" . DB_COLLATE . "'" ),
			"define( 'BB_AUTH_KEY"  => array( "'put your unique phrase here'",  "'" . addslashes( AUTH_KEY ) . "'" ),
			"define( 'BB_SECURE_A"  => array( "'put your unique phrase here'",  "'" . addslashes( SECURE_AUTH_KEY ) . "'" ),
			"define( 'BB_LOGGED_I"  => array( "'put your unique phrase here'",  "'" . addslashes( LOGGED_IN_KEY ) . "'" ),
			"define( 'BB_NONCE_KE"  => array( "'put your unique phrase here'",  "'" . addslashes( NONCE_KEY ) . "'" ),
			"\$bb_table_prefix = '" => array( "'bb_'",                          "'" . $bp->table_prefix . "bb_'" ),
			"define( 'BB_LANG', '"  => array( "''",                             "'" . get_locale() . "'" )
		)
	);

	// Add the custom user and usermeta entries to the config file
	if ( $initial_write == 1 ) {
		$file = file_get_contents( $location );
	} else {
		$file = &$initial_write;
	}

	$file = trim( $file );
	if ( '?>' == substr( $file, -2, 2 ) ) {
		$file = substr( $file, 0, -2 );
	}

	$file .= "\n" .   '$bb->custom_user_table = \'' . $wpdb->users . '\';';
	$file .= "\n" .   '$bb->custom_user_meta_table = \'' . $wpdb->usermeta . '\';';
	$file .= "\n\n" . '$bb->uri = \'' . BP_PLUGIN_URL . '/bp-forums/bbpress/\';';
	$file .= "\n" .   '$bb->name = \'' . get_blog_option( bp_get_root_blog_id(), 'blogname' ) . ' ' . __( 'Forums', 'buddypress' ) . '\';';

	if ( is_multisite() ) {
		$file .= "\n" .   '$bb->wordpress_mu_primary_blog_id = ' . bp_get_root_blog_id() . ';';
	}

	if ( defined( 'AUTH_SALT' ) ) {
		$file .= "\n\n" . 'define(\'BB_AUTH_SALT\', \'' . addslashes( AUTH_SALT ) . '\');';
	}

	if ( defined( 'LOGGED_IN_SALT' ) ) {
		$file .= "\n" .   'define(\'BB_LOGGED_IN_SALT\', \'' . addslashes( LOGGED_IN_SALT ) . '\');';
	}

	if ( defined( 'SECURE_AUTH_SALT' ) ) {
		$file .= "\n" .   'define(\'BB_SECURE_AUTH_SALT\', \'' . addslashes( SECURE_AUTH_SALT ) . '\');';
	}

	$file .= "\n\n" . 'define(\'WP_AUTH_COOKIE_VERSION\', 2);';
	$file .= "\n\n" . '?>';

	if ( $initial_write == 1 ) {
		$file_handle = fopen( $location, 'w' );
		fwrite( $file_handle, $file );
		fclose( $file_handle );
	} else {
		$initial_write = $file;
	}

	bp_update_option( 'bb-config-location', $location );
	return $initial_write;
}

function bp_forums_bbpress_write( $file_source, $file_target, $alterations ) {

	if ( empty( $file_source ) || !file_exists( $file_source ) || !is_file( $file_source ) ) {
		return -1;
	}

	if ( empty( $file_target ) ) {
		$file_target = $file_source;
	}

	if ( empty( $alterations ) || !is_array( $alterations ) ) {
		return -2;
	}

	// Get the existing lines in the file
	$lines = file( $file_source );

	// Initialise an array to store the modified lines
	$modified_lines = array();

	// Loop through the lines and modify them
	foreach ( (array) $lines as $line ) {
		if ( isset( $alterations[substr( $line, 0, 20 )] ) ) {
			$alteration = $alterations[substr( $line, 0, 20 )];
			$modified_lines[] = str_replace( $alteration[0], $alteration[1], $line );
		} else {
			$modified_lines[] = $line;
		}
	}

	$writable = true;
	if ( file_exists( $file_target ) ) {
		if ( !is_writable( $file_target ) ) {
			$writable = false;
		}
	} else {
		$dir_target = dirname( $file_target );

		if ( file_exists( $dir_target ) ) {
			if ( !is_writable( $dir_target ) || !is_dir( $dir_target ) ) {
				$writable = false;
			}
		} else {
			$writable = false;
		}
	}

	if ( empty( $writable ) ) {
		return trim( join( null, $modified_lines ) );
	}

	// Open the file for writing - rewrites the whole file
	$file_handle = fopen( $file_target, 'w' );

	// Write lines one by one to avoid OS specific newline hassles
	foreach ( (array) $modified_lines as $modified_line ) {
		if ( strlen( $modified_line ) - 2 === strrpos( $modified_line, '?>' ) ) {
			$modified_line = '?>';
		}

		fwrite( $file_handle, $modified_line );
		if ( $modified_line == '?>' ) {
			break;
		}
	}

	// Close the config file
	fclose( $file_handle );

	@chmod( $file_target, 0666 );

	return 1;
}

?>