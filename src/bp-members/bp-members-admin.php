<?php
/**
 * BuddyPress Members Admin
 *
 * @package BuddyPress
 * @subpackage MembersAdmin
 * @since 2.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Load the BP Members admin.
add_action( 'bp_init', array( 'BP_Members_Admin', 'register_members_admin' ) );

/**
 * Create Users submenu to manage BuddyPress types.
 *
 * @since 7.0.0
 */
function bp_members_type_admin_menu() {
	if ( ! bp_is_root_blog() ) {
		return;
	}

	if ( bp_is_network_activated() && ! bp_is_multiblog_mode() && is_network_admin() ) {
		// Adds a users.php submenu to go to the root blog Member types screen.
		$member_type_admin_url = add_query_arg( 'taxonomy', bp_get_member_type_tax_name(), get_admin_url( bp_get_root_blog_id(), 'edit-tags.php' ) );

		add_submenu_page(
			'users.php',
			__( 'Member Types', 'buddypress' ),
			__( 'Member Types', 'buddypress' ),
			'bp_moderate',
			esc_url( $member_type_admin_url )
		);

	} elseif ( ! is_network_admin() ) {
		add_submenu_page(
			'users.php',
			__( 'Member Types', 'buddypress' ),
			__( 'Member Types', 'buddypress' ),
			'bp_moderate',
			basename( add_query_arg( 'taxonomy', bp_get_member_type_tax_name(), bp_get_admin_url( 'edit-tags.php' ) ) )
		);
	}
}
add_action( 'bp_admin_menu', 'bp_members_type_admin_menu' );

/**
 * Checks whether a member type already exists.
 *
 * @since 7.0.0
 *
 * @param  boolean $exists  True if the member type already exists. False otherwise.
 * @param  string  $type_id The member type identifier.
 * @return boolean          True if the member type already exists. False otherwise.
 */
function bp_members_type_admin_type_exists( $exists = false, $type_id = '' ) {
	if ( ! $type_id ) {
		return $exists;
	}

	return ! is_null( bp_get_member_type_object( $type_id ) );
}
add_filter( bp_get_member_type_tax_name() . '_check_existing_type', 'bp_members_type_admin_type_exists', 1, 2 );

/**
 * Set the feedback messages for the Member Types Admin actions.
 *
 * @since 7.0.0
 *
 * @param array  $messages The feedback messages.
 * @return array           The feedback messages including the ones for the Member Types Admin actions.
 */
function bp_members_type_admin_updated_messages( $messages = array() ) {
	$type_taxonomy = bp_get_member_type_tax_name();

	$messages[ $type_taxonomy ] = array(
		0  => '',
		1  => __( 'Please define the Member Type ID field.', 'buddypress' ),
		2  => __( 'Member type successfully added.', 'buddypress' ),
		3  => __( 'Sorry, there was an error and the Member type wasn’t added.', 'buddypress' ),
		// The following one needs to be != 5.
		4  => __( 'Member type successfully updated.', 'buddypress' ),
		5  => __( 'Sorry, this Member type already exists.', 'buddypress' ),
		6  => __( 'Sorry, the Member type was not deleted: it does not exist.', 'buddypress' ),
		7  => __( 'Sorry, This Member type is registered using code, deactivate the plugin or remove the custom code before trying to delete it again.', 'buddypress' ),
		8  => __( 'Sorry, there was an error while trying to delete this Member type.', 'buddypress' ),
		9  => __( 'Member type successfully deleted.', 'buddypress' ),
		10 => __( 'Member type could not be updated due to missing required information.', 'buddypress' ),
	);

	return $messages;
}
add_filter( 'term_updated_messages', 'bp_members_type_admin_updated_messages' );
