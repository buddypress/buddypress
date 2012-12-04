<?php
/**
 * Deprecated Functions
 *
 * @package BuddyPress
 * @subpackage Core
 * @deprecated Since 1.7
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Output the BuddyPress maintenance mode
 *
 * @since BuddyPress (1.6)
 * @uses bp_get_maintenance_mode() To get the BuddyPress maintenance mode
 */
function bp_maintenance_mode() {
	echo bp_get_maintenance_mode();
}
	/**
	 * Return the BuddyPress maintenance mode
	 *
	 * @since BuddyPress (1.6)
	 * @return string The BuddyPress maintenance mode
	 */
	function bp_get_maintenance_mode() {
		return buddypress()->maintenance_mode;
	}

function xprofile_get_profile() {
	_deprecated_function( __FUNCTION__, '1.7' );
	bp_locate_template( array( 'profile/profile-loop.php' ), true );
}

function bp_get_profile_header() {
	_deprecated_function( __FUNCTION__, '1.7' );
	bp_locate_template( array( 'profile/profile-header.php' ), true );
}

function bp_exists( $component_name ) {
	_deprecated_function( __FUNCTION__, '1.7' );
	if ( function_exists( $component_name . '_install' ) )
		return true;

	return false;
}

function bp_get_plugin_sidebar() {
	_deprecated_function( __FUNCTION__, '1.7' );
	bp_locate_template( array( 'plugin-sidebar.php' ), true );
}

/**
 * On multiblog installations you must first allow themes to be activated and
 * show up on the theme selection screen. This function will let the BuddyPress
 * bundled themes show up on the root blog selection screen and bypass this
 * step. It also means that the themes won't show for selection on other blogs.
 *
 * @package BuddyPress Core
 */
function bp_core_allow_default_theme( $themes ) {
	_deprecated_function( __FUNCTION__, '1.7' );

	if ( !bp_current_user_can( 'bp_moderate' ) )
		return $themes;

	if ( bp_get_root_blog_id() != get_current_blog_id() )
		return $themes;

	if ( isset( $themes['bp-default'] ) )
		return $themes;

	$themes['bp-default'] = true;

	return $themes;
}

/** Admin *********************************************************************/


/**
 * Verify that some BP prerequisites are set up properly, and notify the admin if not
 *
 * On every Dashboard page, this function checks the following:
 *   - that pretty permalinks are enabled
 *   - that a BP-compatible theme is activated
 *   - that every BP component that needs a WP page for a directory has one
 *   - that no WP page has multiple BP components associated with it
 * The administrator will be shown a notice for each check that fails.
 *
 * @package BuddyPress Core
 */
function bp_core_activation_notice() {
	global $wp_rewrite, $wpdb;

	$bp = buddypress();

	// Only the super admin gets warnings
	if ( !bp_current_user_can( 'bp_moderate' ) )
		return;

	// On multisite installs, don't load on a non-root blog, unless do_network_admin is
	// overridden
	if ( is_multisite() && bp_core_do_network_admin() && !bp_is_root_blog() )
		return;

	// Don't show these messages during setup or upgrade
	if ( !empty( $bp->maintenance_mode ) )
		return;

	/**
	 * Check to make sure that the blog setup routine has run. This can't happen during the
	 * wizard because of the order which the components are loaded. We check for multisite here
	 * on the off chance that someone has activated the blogs component and then disabled MS
	 */
	if ( bp_is_active( 'blogs' ) ) {
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$bp->blogs->table_name}" );

		if ( empty( $count ) ) {
			bp_blogs_record_existing_blogs();
		}
	}

	/**
	 * Are pretty permalinks enabled?
	 */
	if ( isset( $_POST['permalink_structure'] ) )
		return false;

	if ( empty( $wp_rewrite->permalink_structure ) ) {
		bp_core_add_admin_notice( sprintf( __( '<strong>BuddyPress is almost ready</strong>. You must <a href="%s">update your permalink structure</a> to something other than the default for it to work.', 'buddypress' ), admin_url( 'options-permalink.php' ) ) );
	}

	/**
	 * Check for orphaned BP components (BP component is enabled, no WP page exists)
	 */
	$orphaned_components = array();
	$wp_page_components  = array();

	// Only components with 'has_directory' require a WP page to function
	foreach( array_keys( $bp->loaded_components ) as $component_id ) {
		if ( !empty( $bp->{$component_id}->has_directory ) ) {
			$wp_page_components[] = array(
				'id'   => $component_id,
				'name' => isset( $bp->{$component_id}->name ) ? $bp->{$component_id}->name : ucwords( $bp->{$component_id}->id )
			);
		}
	}

	// Activate and Register are special cases. They are not components but they need WP pages.
	// If user registration is disabled, we can skip this step.
	if ( bp_get_signup_allowed() ) {
		$wp_page_components[] = array(
			'id'   => 'activate',
			'name' => __( 'Activate', 'buddypress' )
		);

		$wp_page_components[] = array(
			'id'   => 'register',
			'name' => __( 'Register', 'buddypress' )
		);
	}

	foreach( $wp_page_components as $component ) {
		if ( !isset( $bp->pages->{$component['id']} ) ) {
			$orphaned_components[] = $component['name'];
		}
	}

	// Special case: If the Forums component is orphaned, but the bbPress 1.x installation is
	// not correctly set up, don't show a nag. (In these cases, it's probably the case that the
	// user is using bbPress 2.x; see https://buddypress.trac.wordpress.org/ticket/4292
	if ( isset( $bp->forums->name ) && in_array( $bp->forums->name, $orphaned_components ) && !bp_forums_is_installed_correctly() ) {
		$forum_key = array_search( $bp->forums->name, $orphaned_components );
		unset( $orphaned_components[$forum_key] );
		$orphaned_components = array_values( $orphaned_components );
	}

	if ( !empty( $orphaned_components ) ) {
		$admin_url = bp_get_admin_url( add_query_arg( array( 'page' => 'bp-page-settings' ), 'admin.php' ) );
		$notice    = sprintf( __( 'The following active BuddyPress Components do not have associated WordPress Pages: %2$s. <a href="%1$s" class="button-secondary">Repair</a>', 'buddypress' ), $admin_url, '<strong>' . implode( '</strong>, <strong>', $orphaned_components ) . '</strong>' );

		bp_core_add_admin_notice( $notice );
	}

	/**
	 * BP components cannot share a single WP page. Check for duplicate assignments, and post
	 * a message if found.
	 */
	$dupe_names = array();
	$page_ids   = (array)bp_core_get_directory_page_ids();
	$dupes      = array_diff_assoc( $page_ids, array_unique( $page_ids ) );

	if ( !empty( $dupes ) ) {
		foreach( array_keys( $dupes ) as $dupe_component ) {
			$dupe_names[] = $bp->pages->{$dupe_component}->title;
		}

		// Make sure that there are no duplicate duplicates :)
		$dupe_names = array_unique( $dupe_names );
	}

	// If there are duplicates, post a message about them
	if ( !empty( $dupe_names ) ) {
		$admin_url = bp_get_admin_url( add_query_arg( array( 'page' => 'bp-page-settings' ), 'admin.php' ) );
		$notice    = sprintf( __( 'Each BuddyPress Component needs its own WordPress page. The following WordPress Pages have more than one component associated with them: %2$s. <a href="%1$s" class="button-secondary">Repair</a>', 'buddypress' ), $admin_url, '<strong>' . implode( '</strong>, <strong>', $dupe_names ) . '</strong>' );

		bp_core_add_admin_notice( $notice );
	}
}
