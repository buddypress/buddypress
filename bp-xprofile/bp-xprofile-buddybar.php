<?php

/**
 * Creates the administration interface menus and checks to see if the DB
 * tables are set up.
 *
 * @package BuddyPress XProfile
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @global $wpdb WordPress DB access object.
 * @uses is_super_admin() returns true if the current user is a site admin, false if not
 * @uses bp_xprofile_install() runs the installation of DB tables for the xprofile component
 * @uses wp_enqueue_script() Adds a JS file to the JS queue ready for output
 * @uses add_submenu_page() Adds a submenu tab to a top level tab in the admin area
 * @uses xprofile_install() Runs the DB table installation function
 * @return
 */
function xprofile_add_admin_menu() {
	global $wpdb, $bp;

	if ( !is_super_admin() )
		return false;

	// Add the administration tab under the "Site Admin" tab for site administrators
	add_submenu_page( 'bp-general-settings', __( 'Profile Field Setup', 'buddypress' ), __( 'Profile Field Setup', 'buddypress' ), 'manage_options', 'bp-profile-setup', 'xprofile_admin' );
}
add_action( is_multisite() ? 'network_admin_menu' : 'admin_menu', 'xprofile_add_admin_menu' );

/**
 * Adds an admin bar menu to any profile page providing site admin options
 * for that user.
 *
 * @package BuddyPress XProfile
 * @global $bp BuddyPress
 */
function xprofile_setup_adminbar_menu() {
	global $bp;

	if ( !$bp->displayed_user->id )
		return false;

	// Don't show this menu to non site admins or if you're viewing your own profile
	if ( !is_super_admin() || bp_is_my_profile() )
		return false; ?>

	<li id="bp-adminbar-adminoptions-menu">
		<a href=""><?php _e( 'Admin Options', 'buddypress' ) ?></a>

		<ul>
			<li><a href="<?php echo $bp->displayed_user->domain . $bp->profile->slug; ?>/edit/"><?php printf( __( "Edit %s's Profile", 'buddypress' ), esc_attr( $bp->displayed_user->fullname ) ) ?></a></li>
			<li><a href="<?php echo $bp->displayed_user->domain . $bp->profile->slug; ?>/change-avatar/"><?php printf( __( "Edit %s's Avatar", 'buddypress' ), esc_attr( $bp->displayed_user->fullname ) ) ?></a></li>

			<?php if ( bp_is_active( 'settings' ) ) : ?>

				<li><a href="<?php echo $bp->displayed_user->domain . $bp->settings->slug; ?>/general/"><?php printf( __( "Edit %s's Settings", 'buddypress' ), esc_attr( $bp->displayed_user->fullname ) ) ?></a></li>

			<?php endif; ?>

			<?php if ( !bp_core_is_user_spammer( $bp->displayed_user->id ) ) : ?>

				<li><a href="<?php echo wp_nonce_url( $bp->displayed_user->domain . 'admin/mark-spammer/', 'mark-unmark-spammer' ) ?>" class="confirm"><?php _e( "Mark as Spammer", 'buddypress' ) ?></a></li>

			<?php else : ?>

				<li><a href="<?php echo wp_nonce_url( $bp->displayed_user->domain . 'admin/unmark-spammer/', 'mark-unmark-spammer' ) ?>" class="confirm"><?php _e( "Not a Spammer", 'buddypress' ) ?></a></li>

			<?php endif; ?>

			<li><a href="<?php echo wp_nonce_url( $bp->displayed_user->domain . 'admin/delete-user/', 'delete-user' ) ?>" class="confirm"><?php printf( __( "Delete %s", 'buddypress' ), esc_attr( $bp->displayed_user->fullname ) ) ?></a></li>

			<?php do_action( 'xprofile_adminbar_menu_items' ) ?>

		</ul>
	</li>

	<?php
}
add_action( 'bp_adminbar_menus', 'xprofile_setup_adminbar_menu', 20 );

?>
