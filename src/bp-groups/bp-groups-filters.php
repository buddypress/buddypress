<?php
/**
 * BuddyPress Groups Filters.
 *
 * @package BuddyPress
 * @subpackage GroupsFilters
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Filter BuddyPress template locations.
add_filter( 'bp_groups_get_directory_template', 'bp_add_template_locations' );
add_filter( 'bp_get_single_group_template',    'bp_add_template_locations' );

/* Apply WordPress defined filters */
add_filter( 'bp_get_group_description',         'wptexturize' );
add_filter( 'bp_get_group_description_excerpt', 'wptexturize' );
add_filter( 'bp_get_group_name',                'wptexturize' );

add_filter( 'bp_get_group_description',         'convert_smilies' );
add_filter( 'bp_get_group_description_excerpt', 'convert_smilies' );

add_filter( 'bp_get_group_description',         'convert_chars' );
add_filter( 'bp_get_group_description_excerpt', 'convert_chars' );
add_filter( 'bp_get_group_name',                'convert_chars' );

add_filter( 'bp_get_group_description',         'wpautop' );
add_filter( 'bp_get_group_description_excerpt', 'wpautop' );

add_filter( 'bp_get_group_description',         'make_clickable', 9 );
add_filter( 'bp_get_group_description_excerpt', 'make_clickable', 9 );

add_filter( 'bp_get_group_name',                    'wp_filter_kses',        1 );
add_filter( 'bp_get_group_permalink',               'wp_filter_kses',        1 );
add_filter( 'bp_get_group_description',             'bp_groups_filter_kses', 1 );
add_filter( 'bp_get_group_description_excerpt',     'wp_filter_kses',        1 );
add_filter( 'groups_group_name_before_save',        'wp_filter_kses',        1 );
add_filter( 'groups_group_description_before_save', 'wp_filter_kses',        1 );

add_filter( 'bp_get_group_description',         'stripslashes' );
add_filter( 'bp_get_group_description_excerpt', 'stripslashes' );
add_filter( 'bp_get_group_name',                'stripslashes' );
add_filter( 'bp_get_group_member_name',         'stripslashes' );
add_filter( 'bp_get_group_member_link',         'stripslashes' );

add_filter( 'groups_group_name_before_save',        'force_balance_tags' );
add_filter( 'groups_group_description_before_save', 'force_balance_tags' );

// Trim trailing spaces from name and description when saving.
add_filter( 'groups_group_name_before_save',        'trim' );
add_filter( 'groups_group_description_before_save', 'trim' );

// Support emoji.
if ( function_exists( 'wp_encode_emoji' ) ) {
	add_filter( 'groups_group_description_before_save', 'wp_encode_emoji' );
}

// Escape output of new group creation details.
add_filter( 'bp_get_new_group_name',        'esc_attr'     );
add_filter( 'bp_get_new_group_description', 'esc_textarea' );

// Format numerical output.
add_filter( 'bp_get_total_group_count',          'bp_core_number_format' );
add_filter( 'bp_get_group_total_for_member',     'bp_core_number_format' );
add_filter( 'bp_get_group_total_members',        'bp_core_number_format' );
add_filter( 'bp_get_total_group_count_for_user', 'bp_core_number_format' );

// Activity component integration.
add_filter( 'bp_activity_at_name_do_notifications', 'bp_groups_disable_at_mention_notification_for_non_public_groups', 10, 4 );

// Default group avatar.
add_filter( 'bp_core_avatar_default',       'bp_groups_default_avatar', 10, 3 );
add_filter( 'bp_core_avatar_default_thumb', 'bp_groups_default_avatar', 10, 3 );

// Personal data export.
add_filter( 'wp_privacy_personal_data_exporters', 'bp_groups_register_personal_data_exporters' );

/**
 * Filter output of Group Description through WordPress's KSES API.
 *
 * @since 1.1.0
 *
 * @param string $content Content to filter.
 * @return string
 */
function bp_groups_filter_kses( $content = '' ) {

	/**
	 * Note that we don't immediately bail if $content is empty. This is because
	 * WordPress's KSES API calls several other filters that might be relevant
	 * to someone's workflow (like `pre_kses`)
	 */

	// Get allowed tags using core WordPress API allowing third party plugins
	// to target the specific `buddypress-groups` context.
	$allowed_tags = wp_kses_allowed_html( 'buddypress-groups' );

	// Add our own tags allowed in group descriptions.
	$allowed_tags['a']['class']    = array();
	$allowed_tags['img']           = array();
	$allowed_tags['img']['src']    = array();
	$allowed_tags['img']['alt']    = array();
	$allowed_tags['img']['width']  = array();
	$allowed_tags['img']['height'] = array();
	$allowed_tags['img']['class']  = array();
	$allowed_tags['img']['id']     = array();
	$allowed_tags['code']          = array();

	/**
	 * Filters the HTML elements allowed for a given context.
	 *
	 * @since 1.2.0
	 *
	 * @param string $allowed_tags Allowed tags, attributes, and/or entities.
	 */
	$tags = apply_filters( 'bp_groups_filter_kses', $allowed_tags );

	// Return KSES'ed content, allowing the above tags.
	return wp_kses( $content, $tags );
}

/**
 * Should BuddyPress load the mentions scripts and related assets, including results to prime the
 * mentions suggestions?
 *
 * @since 2.2.0
 *
 * @param bool $load_mentions    True to load mentions assets, false otherwise.
 * @param bool $mentions_enabled True if mentions are enabled.
 * @return bool True if mentions scripts should be loaded.
 */
function bp_groups_maybe_load_mentions_scripts( $load_mentions, $mentions_enabled ) {
	if ( ! $mentions_enabled ) {
		return $load_mentions;
	}

	if ( $load_mentions || bp_is_group_activity() ) {
		return true;
	}

	return $load_mentions;
}
add_filter( 'bp_activity_maybe_load_mentions_scripts', 'bp_groups_maybe_load_mentions_scripts', 10, 2 );

/**
 * Disable at-mention notifications for users who are not a member of the non-public group where the activity appears.
 *
 * @since 2.5.0
 *
 * @param bool                 $send      Whether to send the notification.
 * @param array                $usernames Array of all usernames being notified.
 * @param int                  $user_id   ID of the user to be notified.
 * @param BP_Activity_Activity $activity  Activity object.
 * @return bool
 */
function bp_groups_disable_at_mention_notification_for_non_public_groups( $send, $usernames, $user_id, BP_Activity_Activity $activity ) {
	// Skip the check for administrators, who can get notifications from non-public groups.
	if ( bp_user_can( $user_id, 'bp_moderate' ) ) {
		return $send;
	}

	if ( 'groups' === $activity->component && ! bp_user_can( $user_id, 'groups_access_group', array( 'group_id' => $activity->item_id ) ) ) {
		$send = false;
	}

	return $send;
}

/**
 * Use the mystery group avatar for groups.
 *
 * @since 2.6.0
 *
 * @param string $avatar Current avatar src.
 * @param array  $params Avatar params.
 * @return string
 */
function bp_groups_default_avatar( $avatar, $params ) {
	if ( isset( $params['object'] ) && 'group' === $params['object'] ) {
		if ( isset( $params['type'] ) && 'thumb' === $params['type'] ) {
			$file = 'mystery-group-50.png';
		} else {
			$file = 'mystery-group.png';
		}

		$avatar = buddypress()->plugin_url . "bp-core/images/$file";
	}

	return $avatar;
}

/**
 * Filter the bp_user_can value to determine what the user can do
 * with regards to a specific group.
 *
 * @since 3.0.0
 *
 * @param bool   $retval     Whether or not the current user has the capability.
 * @param int    $user_id
 * @param string $capability The capability being checked for.
 * @param int    $site_id    Site ID. Defaults to the BP root blog.
 * @param array  $args       Array of extra arguments passed.
 *
 * @return bool
 */
function bp_groups_user_can_filter( $retval, $user_id, $capability, $site_id, $args ) {
	if ( empty( $args['group_id'] ) ) {
		$group_id = bp_get_current_group_id();
	} else {
		$group_id = (int) $args['group_id'];
	}

	switch ( $capability ) {
		case 'groups_join_group':
			// Return early if the user isn't logged in or the group ID is unknown.
			if ( ! $user_id || ! $group_id ) {
				break;
			}

			// Set to false to begin with.
			$retval = false;

			// The group must allow joining, and the user should not currently be a member.
			$group = groups_get_group( $group_id );
			if ( ( 'public' === bp_get_group_status( $group )
				&& ! groups_is_user_member( $user_id, $group->id )
				&& ! groups_is_user_banned( $user_id, $group->id ) )
				// Site admins can join any group they are not a member of.
				|| ( bp_user_can( $user_id, 'bp_moderate' )
				&& ! groups_is_user_member( $user_id, $group->id ) )
			) {
				$retval = true;
			}
			break;

		case 'groups_request_membership':
			// Return early if the user isn't logged in or the group ID is unknown.
			if ( ! $user_id || ! $group_id ) {
				break;
			}

			// Set to false to begin with.
			$retval = false;

			/*
			* The group must accept membership requests, and the user should not
			* currently be a member, have an active request, or be banned.
			*/
			$group = groups_get_group( $group_id );
			if ( 'private' === bp_get_group_status( $group )
				&& ! groups_is_user_member( $user_id, $group->id )
				&& ! groups_check_for_membership_request( $user_id, $group->id )
				&& ! groups_is_user_banned( $user_id, $group->id )
			) {
				$retval = true;
			}
			break;

		case 'groups_send_invitation':
			// Return early if the user isn't logged in or the group ID is unknown.
			if ( ! $user_id || ! $group_id ) {
				break;
			}

			/*
			* The group must allow invitations, and the user should not
			* currently be a member or be banned from the group.
			*/
			// Users with the 'bp_moderate' cap can always send invitations.
			if ( bp_user_can( $user_id, 'bp_moderate' ) ) {
				$retval = true;
			} else {
				$invite_status = bp_group_get_invite_status( $group_id );

				switch ( $invite_status ) {
					case 'admins' :
						if ( groups_is_user_admin( $user_id, $group_id ) ) {
							$retval = true;
						}
						break;

					case 'mods' :
						if ( groups_is_user_mod( $user_id, $group_id ) || groups_is_user_admin( $user_id, $group_id ) ) {
							$retval = true;
						}
						break;

					case 'members' :
						if ( groups_is_user_member( $user_id, $group_id ) ) {
							$retval = true;
						}
						break;
				}
			}
			break;

		case 'groups_receive_invitation':
			// Return early if the user isn't logged in or the group ID is unknown.
			if ( ! $user_id || ! $group_id ) {
				break;
			}

			// Set to false to begin with.
			$retval = false;

			/*
			* The group must allow invitations, and the user should not
			* currently be a member or be banned from the group.
			*/
			$group = groups_get_group( $group_id );
			if ( in_array( bp_get_group_status( $group ), array( 'private', 'hidden' ), true )
				&& ! groups_is_user_member( $user_id, $group->id )
				&& ! groups_is_user_banned( $user_id, $group->id )
			) {
				$retval = true;
			}
			break;

		case 'groups_access_group':
			// Return early if the group ID is unknown.
			if ( ! $group_id ) {
				break;
			}

			$group = groups_get_group( $group_id );

			// If the check is for the logged-in user, use the BP_Groups_Group property.
			if ( $user_id === bp_loggedin_user_id() ) {
				$retval = $group->user_has_access;

			/*
			 * If the check is for a specified user who is not the logged-in user
			 * run the check manually.
			 */
			} elseif ( 'public' === bp_get_group_status( $group ) || groups_is_user_member( $user_id, $group->id ) ) {
				$retval = true;
			}
			break;

		case 'groups_see_group':
			// Return early if the group ID is unknown.
			if ( ! $group_id ) {
				break;
			}

			$group = groups_get_group( $group_id );

			// If the check is for the logged-in user, use the BP_Groups_Group property.
			if ( $user_id === bp_loggedin_user_id() ) {
				$retval = $group->is_visible;

			/*
			 * If the check is for a specified user who is not the logged-in user
			 * run the check manually.
			 */
			} elseif ( 'hidden' !== bp_get_group_status( $group ) || groups_is_user_member( $user_id, $group->id ) ) {
				$retval = true;
			}
			break;
	}

	return $retval;

}
add_filter( 'bp_user_can', 'bp_groups_user_can_filter', 10, 5 );

/**
 * Registers Groups personal data exporters.
 *
 * @since 4.0.0
 *
 * @param array $exporters  An array of personal data exporters.
 * @return array An array of personal data exporters.
 */
function bp_groups_register_personal_data_exporters( $exporters ) {
	$exporters['buddypress-groups-memberships'] = array(
		'exporter_friendly_name' => __( 'BuddyPress Group Memberships', 'buddypress' ),
		'callback'               => 'bp_groups_memberships_personal_data_exporter',
	);

	$exporters['buddypress-groups-pending-requests'] = array(
		'exporter_friendly_name' => __( 'BuddyPress Pending Group Membership Requests', 'buddypress' ),
		'callback'               => 'bp_groups_pending_requests_personal_data_exporter',
	);

	$exporters['buddypress-groups-pending-received-invitations'] = array(
		'exporter_friendly_name' => __( 'BuddyPress Pending Group Invitations (Received)', 'buddypress' ),
		'callback'               => 'bp_groups_pending_received_invitations_personal_data_exporter',
	);

	$exporters['buddypress-groups-pending-sent-invitations'] = array(
		'exporter_friendly_name' => __( 'BuddyPress Pending Group Invitations (Sent)', 'buddypress' ),
		'callback'               => 'bp_groups_pending_sent_invitations_personal_data_exporter',
	);

	return $exporters;
}
