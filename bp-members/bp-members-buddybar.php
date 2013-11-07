<?php

/**
 * BuddyPress Members BuddyBar
 *
 * Handles the member functions related to the BuddyBar
 *
 * @package BuddyPress
 * @subpackage MembersBuddyBar
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Notifications Menu
 */
function bp_adminbar_notifications_menu() {

	// Bail if notifications is not active
	if ( ! bp_is_active( 'notifications' ) ) {
		return false;
	}

	bp_notifications_buddybar_menu();
}
add_action( 'bp_adminbar_menus', 'bp_adminbar_notifications_menu', 8 );

/**
 * Blog Authors Menu (visible when not logged in)
 */
function bp_adminbar_authors_menu() {
	global $wpdb;

	// Only for multisite
	if ( !is_multisite() )
		return false;

	// Hide on root blog
	if ( $wpdb->blogid == bp_get_root_blog_id() || !bp_is_active( 'blogs' ) )
		return false;

	$blog_prefix = $wpdb->get_blog_prefix( $wpdb->blogid );
	$authors     = $wpdb->get_results( "SELECT user_id, user_login, user_nicename, display_name, user_email, meta_value as caps FROM $wpdb->users u, $wpdb->usermeta um WHERE u.ID = um.user_id AND meta_key = '{$blog_prefix}capabilities' ORDER BY um.user_id" );

	if ( !empty( $authors ) ) {
		// This is a blog, render a menu with links to all authors
		echo '<li id="bp-adminbar-authors-menu"><a href="/">';
		_e('Blog Authors', 'buddypress');
		echo '</a>';

		echo '<ul class="author-list">';
		foreach( (array) $authors as $author ) {
			$caps = maybe_unserialize( $author->caps );
			if ( isset( $caps['subscriber'] ) || isset( $caps['contributor'] ) ) continue;

			echo '<li>';
			echo '<a href="' . bp_core_get_user_domain( $author->user_id, $author->user_nicename, $author->user_login ) . '">';
			echo bp_core_fetch_avatar( array(
				'item_id' => $author->user_id,
				'email'   => $author->user_email,
				'width'   => 15,
				'height'  => 15,
				'alt'     => sprintf( __( 'Profile picture of %s', 'buddypress' ), $author->display_name )
			) );
 			echo ' ' . $author->display_name . '</a>';
			echo '<div class="admin-bar-clear"></div>';
			echo '</li>';
		}
		echo '</ul>';
		echo '</li>';
	}
}
add_action( 'bp_adminbar_menus', 'bp_adminbar_authors_menu', 12 );

/**
 * Adds an Toolbar menu to any profile page providing site moderator actions
 * that allow capable users to clean up a users account.
 *
 * @package BuddyPress XProfile
 */
function bp_members_adminbar_admin_menu() {

	// Only show if viewing a user
	if ( !bp_displayed_user_id() )
		return false;

	// Don't show this menu to non site admins or if you're viewing your own profile
	if ( !current_user_can( 'edit_users' ) || bp_is_my_profile() )
		return false; ?>

	<li id="bp-adminbar-adminoptions-menu">

		<a href=""><?php _e( 'Admin Options', 'buddypress' ) ?></a>

		<ul>
			<?php if ( bp_is_active( 'xprofile' ) ) : ?>

				<li><a href="<?php bp_members_component_link( 'profile', 'edit' ); ?>"><?php printf( __( "Edit %s's Profile", 'buddypress' ), esc_attr( bp_get_displayed_user_fullname() ) ) ?></a></li>

			<?php endif ?>

			<li><a href="<?php bp_members_component_link( 'profile', 'change-avatar' ); ?>"><?php printf( __( "Edit %s's Avatar", 'buddypress' ), esc_attr( bp_get_displayed_user_fullname() ) ) ?></a></li>

			<li><a href="<?php bp_members_component_link( 'settings', 'capabilities' ); ?>"><?php _e( 'User Capabilities', 'buddypress' ); ?></a></li>

			<li><a href="<?php bp_members_component_link( 'settings', 'delete-account' ); ?>"><?php printf( __( "Delete %s's Account", 'buddypress' ), esc_attr( bp_get_displayed_user_fullname() ) ); ?></a></li>

			<?php do_action( 'bp_members_adminbar_admin_menu' ) ?>

		</ul>
	</li>

	<?php
}
add_action( 'bp_adminbar_menus', 'bp_members_adminbar_admin_menu', 20 );
