<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/** UI ************************************************************************/

function bp_forums_add_admin_menu() {

	if ( ! bp_current_user_can( 'bp_moderate' ) )
		return;

	if ( ! bp_forums_is_repair() )
		return;

	// Add the option pages
	$page = bp_core_do_network_admin()  ? 'settings.php' : 'options-general.php';
	$hook = add_submenu_page( $page, __( 'BuddyPress Forums', 'buddypress' ), __( 'BuddyPress Forums', 'buddypress' ), 'manage_options', 'bb-forums-setup', 'bp_forums_bbpress_admin' );

	// Fudge the highlighted subnav item when on a BuddyPress admin page
	add_action( "admin_head-$hook", 'bp_core_modify_admin_menu_highlight' );
}
add_action( bp_core_admin_hook(), 'bp_forums_add_admin_menu' );

function bp_forums_bbpress_admin() {
?>

	<div class="wrap">

		<?php screen_icon( 'buddypress' ); ?>

		<h2 class="nav-tab-wrapper"><?php bp_core_admin_tabs( __( 'Forums', 'buddypress' ) ); ?></h2>

		<h3><?php _e( 'Forum Repair', 'buddypress' ); ?></h3>

		<?php if ( bp_forums_is_repair() ) : ?>

			<p><?php printf( __( 'The following configuration was successfully saved to: %s', 'buddypress' ), bp_get_option( 'bb-config-location' ) );?></p>

			<div id="template">
				<textarea cols="70" rows="25" name="bb-config" id="bb-config"><?php echo bp_forums_bbpress_install( bp_get_option( 'bb-config-location' ) ); ?></textarea>
			</div>

		<?php elseif ( bp_forums_is_migrate() ) : ?>

			<?php
				add_thickbox();
				wp_enqueue_script( 'plugin-install' );
				wp_admin_css( 'plugin-install' );
			?>

			<a class="button thickbox button-primary" href="<?php bp_admin_url( add_query_arg( array( 'tab' => 'plugin-information', 'plugin' => 'bbpress', 'TB_iframe' => 'true', 'width' => '640', 'height' => '500' ), 'plugin-install.php' ) ); ?>"><?php _e( 'Install Site Wide Forums', 'buddypress' ) ?></a> &nbsp;

		<?php else : ?>

		<?php endif; ?>

	</div>
<?php
}

/** _is_ **********************************************************************/

/**
 * Trying to repair a bbPress 1.x installation
 *
 * @since BuddyPress (1.6)
 * @return boolean True if option exists, false if not
 */
function bp_forums_is_repair() {

	// Bail if bbPress 1.x was never installed
	if ( isset( $_REQUEST['repair'] ) && bp_forums_is_installed_correctly() )
		return true;

	return false;
}

/**
 * bbPress 1.x is installed, and we're moving to bbPress 2.x
 *
 * @since BuddyPress (1.6)
 * @return boolean True if option exists, false if not
 */
function bp_forums_is_migrate() {

	// Bail if bbPress 1.x was never installed
	if ( isset( $_REQUEST['migrate'] ) && bp_forums_is_installed_correctly() )
		return true;

	return false;
}

/**
 * bbPress was never installed before, and bbPress 2.x is not installed yet
 *
 * @since BuddyPress (1.6)
 * @return boolean True if option exists, false if not
 */
function bp_forums_is_fresh_install() {

	// Bail if bbPress 1.x was never installed
	if ( isset( $_REQUEST['install'] ) && ! bp_forums_is_installed_correctly() )
		return true;

	return false;
}

/**
 * Both bbPress 1.x and bbPress 2.x are installed
 *
 * @since BuddyPress (1.6)
 * @return boolean True if option exists, false if not
 */
function bp_forums_is_hybrid_install() {

	// Bail if bbPress 1.x was never installed
	if ( isset( $_REQUEST['install'] ) && bp_forums_is_bbpress_active && bp_forums_is_installed_correctly() )
		return true;

	return false;
}

/** Config ********************************************************************/

function bp_forums_bbpress_install( $location = '' ) {
	global $wpdb, $bp;

	if ( empty( $location ) )
		$location = ABSPATH . 'bb-config.php';

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
	$file .= "\n\n" . '$bb->uri = \'' . BP_PLUGIN_URL . 'bp-forums/bbpress/\';';
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

	return $file;
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
		if ( false !== strpos( $modified_line, '?>' ) ) {
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