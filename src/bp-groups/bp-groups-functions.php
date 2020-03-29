<?php
/**
 * BuddyPress Groups Functions.
 *
 * Functions are where all the magic happens in BuddyPress. They will
 * handle the actual saving or manipulation of information. Usually they will
 * hand off to a database class for data access, then return
 * true or false on success or failure.
 *
 * @package BuddyPress
 * @subpackage GroupsFunctions
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Check whether there is a Groups directory page in the $bp global.
 *
 * @since 1.5.0
 *
 * @return bool True if set, False if empty.
 */
function bp_groups_has_directory() {
	$bp = buddypress();

	return (bool) !empty( $bp->pages->groups->id );
}

/**
 * Fetch a single group object.
 *
 * When calling up a group object, you should always use this function instead
 * of instantiating BP_Groups_Group directly, so that you will inherit cache
 * support and pass through the groups_get_group filter.
 *
 * @since 1.2.0
 * @since 2.7.0 The function signature was changed to accept a group ID only,
 *              instead of an array containing the group ID.
 *
 * @param int $group_id ID of the group.
 * @return BP_Groups_Group $group The group object.
 */
function groups_get_group( $group_id ) {
	/*
	 * Backward compatibilty.
	 * Old-style arguments take the form of an array or a query string.
	 */
	if ( ! is_numeric( $group_id ) ) {
		$r = bp_parse_args( $group_id, array(
			'group_id'        => false,
			'load_users'      => false,
			'populate_extras' => false,
		), 'groups_get_group' );

		$group_id = $r['group_id'];
	}

	$group = new BP_Groups_Group( $group_id );

	/**
	 * Filters a single group object.
	 *
	 * @since 1.2.0
	 *
	 * @param BP_Groups_Group $group Single group object.
	 */
	return apply_filters( 'groups_get_group', $group );
}

/** Group Creation, Editing & Deletion ****************************************/

/**
 * Create a group.
 *
 * @since 1.0.0
 *
 * @param array|string $args {
 *     An array of arguments.
 *     @type int|bool $group_id     Pass a group ID to update an existing item, or
 *                                  0 / false to create a new group. Default: 0.
 *     @type int      $creator_id   The user ID that creates the group.
 *     @type string   $name         The group name.
 *     @type string   $description  Optional. The group's description.
 *     @type string   $slug         The group slug.
 *     @type string   $status       The group's status. Accepts 'public', 'private' or
 *                                  'hidden'. Defaults to 'public'.
 *     @type int      $parent_id    The ID of the parent group. Default: 0.
 *     @type int      $enable_forum Optional. Whether the group has a forum enabled.
 *                                  If a bbPress forum is enabled for the group,
 *                                  set this to 1. Default: 0.
 *     @type string   $date_created The GMT time, in Y-m-d h:i:s format, when the group
 *                                  was created. Defaults to the current time.
 * }
 * @return int|bool The ID of the group on success. False on error.
 */
function groups_create_group( $args = '' ) {

	$args = bp_parse_args( $args, array(
		'group_id'     => 0,
		'creator_id'   => 0,
		'name'         => '',
		'description'  => '',
		'slug'         => '',
		'status'       => null,
		'parent_id'    => null,
		'enable_forum' => null,
		'date_created' => null
	), 'groups_create_group' );

	extract( $args, EXTR_SKIP );

	// Pass an existing group ID.
	if ( ! empty( $group_id ) ) {
		$group = groups_get_group( $group_id );
		$name  = ! empty( $name ) ? $name : $group->name;
		$slug  = ! empty( $slug ) ? $slug : $group->slug;
		$creator_id  = ! empty( $creator_id ) ? $creator_id : $group->creator_id;
		$description = ! empty( $description ) ? $description : $group->description;
		$status = ! is_null( $status ) ? $status : $group->status;
		$parent_id = ! is_null( $parent_id ) ? $parent_id : $group->parent_id;
		$enable_forum = ! is_null( $enable_forum ) ? $enable_forum : $group->enable_forum;
		$date_created = ! is_null( $date_created ) ? $date_created : $group->date_created;

		// Groups need at least a name.
		if ( empty( $name ) ) {
			return false;
		}

	// Create a new group.
	} else {
		// Instantiate new group object.
		$group = new BP_Groups_Group;

		// Check for null values, reset to sensible defaults.
		$status = ! is_null( $status ) ? $status : 'public';
		$parent_id = ! is_null( $parent_id ) ? $parent_id : 0;
		$enable_forum = ! is_null( $enable_forum ) ? $enable_forum : 0;
		$date_created = ! is_null( $date_created ) ? $date_created : bp_core_current_time();
	}

	// Set creator ID.
	if ( $creator_id ) {
		$group->creator_id = (int) $creator_id;
	} elseif ( is_user_logged_in() ) {
		$group->creator_id = bp_loggedin_user_id();
	}

	if ( ! $group->creator_id ) {
		return false;
	}

	// Validate status.
	if ( ! groups_is_valid_status( $status ) ) {
		return false;
	}

	// Set group name.
	$group->name         = $name;
	$group->description  = $description;
	$group->slug         = $slug;
	$group->status       = $status;
	$group->parent_id    = $parent_id;
	$group->enable_forum = (int) $enable_forum;
	$group->date_created = $date_created;

	// Save group.
	if ( ! $group->save() ) {
		return false;
	}

	// If this is a new group, set up the creator as the first member and admin.
	if ( empty( $group_id ) ) {
		$member                = new BP_Groups_Member;
		$member->group_id      = $group->id;
		$member->user_id       = $group->creator_id;
		$member->is_admin      = 1;
		$member->user_title    = __( 'Group Admin', 'buddypress' );
		$member->is_confirmed  = 1;
		$member->date_modified = bp_core_current_time();
		$member->save();

		/**
		 * Fires after the creation of a new group and a group creator needs to be made.
		 *
		 * @since 1.5.0
		 *
		 * @param int              $id     ID of the newly created group.
		 * @param BP_Groups_Member $member Instance of the member who is assigned
		 *                                 as group creator.
		 * @param BP_Groups_Group  $group  Instance of the group being created.
		 */
		do_action( 'groups_create_group', $group->id, $member, $group );

	} else {

		/**
		 * Fires after the update of a group.
		 *
		 * @since 1.5.0
		 *
		 * @param int             $id    ID of the updated group.
		 * @param BP_Groups_Group $group Instance of the group being updated.
		 */
		do_action( 'groups_update_group', $group->id, $group );
	}

	/**
	 * Fires after the creation or update of a group.
	 *
	 * @since 1.0.0
	 *
	 * @param int             $id    ID of the newly created group.
	 * @param BP_Groups_Group $group Instance of the group being updated.
	 */
	do_action( 'groups_created_group', $group->id, $group );

	return $group->id;
}

/**
 * Edit the base details for a group.
 *
 * These are the settings that appear on the first page of the group's Admin
 * section (Name, Description, and "Notify members...").
 *
 * @since 1.0.0
 *
 * @param array $args {
 *     An array of optional arguments.
 *     @type int    $group_id       ID of the group.
 *     @type string $name           Name of the group.
 *     @type string $slug           Slug of the group.
 *     @type string $description    Description of the group.
 *     @type bool   $notify_members Whether to send an email notification to group
 *                                  members about changes in these details.
 * }
 * @return bool True on success, false on failure.
 */
function groups_edit_base_group_details( $args = array() ) {
	$function_args = func_get_args();

	// Backward compatibility with old method of passing arguments.
	if ( ! is_array( $args ) || count( $function_args ) > 1 ) {
		_deprecated_argument( __METHOD__, '2.9.0', sprintf( __( 'Arguments passed to %1$s should be in an associative array. See the inline documentation at %2$s for more details.', 'buddypress' ), __METHOD__, __FILE__ ) );

		$old_args_keys = array(
			0 => 'group_id',
			1 => 'name',
			2 => 'description',
			3 => 'notify_members',
		);

		$args = bp_core_parse_args_array( $old_args_keys, $function_args );
	}

	$r = bp_parse_args( $args, array(
		'group_id'       => bp_get_current_group_id(),
		'name'           => null,
		'slug'           => null,
		'description'    => null,
		'notify_members' => false,
	), 'groups_edit_base_group_details' );

	if ( ! $r['group_id'] ) {
		return false;
	}

	$group     = groups_get_group( $r['group_id'] );
	$old_group = clone $group;

	// Group name, slug and description can never be empty. Update only if provided.
	if ( $r['name'] ) {
		$group->name = $r['name'];
	}
	if ( $r['slug'] && $r['slug'] != $group->slug ) {
		$group->slug = groups_check_slug( $r['slug'] );
	}
	if ( $r['description'] ) {
		$group->description = $r['description'];
	}

	if ( ! $group->save() ) {
		return false;
	}

	// Maybe update the "previous_slug" groupmeta.
	if ( $group->slug != $old_group->slug ) {
		/*
		 * If the old slug exists in this group's past, delete that entry.
		 * Recent previous_slugs are preferred when selecting the current group
		 * from an old group slug, so we want the previous slug to be
		 * saved "now" in the groupmeta table and don't need the old record.
		 */
		groups_delete_groupmeta( $group->id, 'previous_slug', $old_group->slug );
		groups_add_groupmeta( $group->id, 'previous_slug', $old_group->slug );
	}

	if ( $r['notify_members'] ) {
		groups_notification_group_updated( $group->id, $old_group );
	}

	/**
	 * Fired after a group's details are updated.
	 *
	 * @since 2.2.0
	 *
	 * @param int             $value          ID of the group.
	 * @param BP_Groups_Group $old_group      Group object, before being modified.
	 * @param bool            $notify_members Whether to send an email notification to members about the change.
	 */
	do_action( 'groups_details_updated', $group->id, $old_group, $r['notify_members'] );

	return true;
}

/**
 * Edit the base details for a group.
 *
 * These are the settings that appear on the Settings page of the group's Admin
 * section (privacy settings, "enable forum", invitation status).
 *
 * @since 1.0.0
 *
 * @param int         $group_id      ID of the group.
 * @param bool        $enable_forum  Whether to enable a forum for the group.
 * @param string      $status        Group status. 'public', 'private', 'hidden'.
 * @param string|bool $invite_status Optional. Who is allowed to send invitations
 *                                   to the group. 'members', 'mods', or 'admins'.
 * @param int|bool    $parent_id     Parent group ID.
 * @return bool True on success, false on failure.
 */
function groups_edit_group_settings( $group_id, $enable_forum, $status, $invite_status = false, $parent_id = false ) {

	$group = groups_get_group( $group_id );
	$group->enable_forum = $enable_forum;

	/**
	 * Before we potentially switch the group status, if it has been changed to public
	 * from private and there are outstanding membership requests, auto-accept those requests.
	 */
	if ( 'private' == $group->status && 'public' == $status )
		groups_accept_all_pending_membership_requests( $group->id );

	// Now update the status.
	$group->status = $status;

	// Update the parent ID if necessary.
	if ( false !== $parent_id ) {
		$group->parent_id = $parent_id;
	}

	if ( !$group->save() )
		return false;

	// Set the invite status.
	if ( $invite_status )
		groups_update_groupmeta( $group->id, 'invite_status', $invite_status );

	groups_update_groupmeta( $group->id, 'last_activity', bp_core_current_time() );

	/**
	 * Fires after the update of a groups settings.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id ID of the group that was updated.
	 */
	do_action( 'groups_settings_updated', $group->id );

	return true;
}

/**
 * Delete a group and all of its associated metadata.
 *
 * @since 1.0.0
 *
 * @param int $group_id ID of the group to delete.
 * @return bool True on success, false on failure.
 */
function groups_delete_group( $group_id ) {

	/**
	 * Fires before the deletion of a group.
	 *
	 * @since 1.5.0
	 *
	 * @param int $group_id ID of the group to be deleted.
	 */
	do_action( 'groups_before_delete_group', $group_id );

	// Get the group object.
	$group = groups_get_group( $group_id );

	// Bail if group cannot be deleted.
	if ( ! $group->delete() ) {
		return false;
	}

	// Remove all outstanding invites for this group.
	groups_delete_all_group_invites( $group_id );

	/**
	 * Fires after the deletion of a group.
	 *
	 * @since 1.0.0
	 *
	 * @param int $group_id ID of the group that was deleted.
	 */
	do_action( 'groups_delete_group', $group_id );

	return true;
}

/**
 * Check a group status (eg 'private') against the whitelist of registered statuses.
 *
 * @since 1.1.0
 *
 * @param string $status Status to check.
 * @return bool True if status is allowed, otherwise false.
 */
function groups_is_valid_status( $status ) {
	$bp = buddypress();

	return in_array( $status, (array) $bp->groups->valid_status );
}

/**
 * Provide a unique, sanitized version of a group slug.
 *
 * @since 1.0.0
 *
 * @param string $slug Group slug to check.
 * @return string $slug A unique and sanitized slug.
 */
function groups_check_slug( $slug ) {
	$bp = buddypress();

	// First, make the proposed slug work in a URL.
	$slug = sanitize_title( $slug );

	if ( 'wp' == substr( $slug, 0, 2 ) )
		$slug = substr( $slug, 2, strlen( $slug ) - 2 );

	if ( in_array( $slug, (array) $bp->groups->forbidden_names ) )
		$slug = $slug . '-' . rand();

	if ( BP_Groups_Group::check_slug( $slug ) ) {
		do {
			$slug = $slug . '-' . rand();
		}
		while ( BP_Groups_Group::check_slug( $slug ) );
	}

	return $slug;
}

/**
 * Get a group slug by its ID.
 *
 * @since 1.0.0
 *
 * @param int $group_id The numeric ID of the group.
 * @return string The group's slug.
 */
function groups_get_slug( $group_id ) {
	$group = groups_get_group( $group_id );
	return !empty( $group->slug ) ? $group->slug : '';
}

/**
 * Get a group ID by its slug.
 *
 * @since 1.6.0
 *
 * @param string $group_slug The group's slug.
 * @return int|null The group ID on success; null on failure.
 */
function groups_get_id( $group_slug ) {
	return BP_Groups_Group::group_exists( $group_slug );
}

/**
 * Get a group ID by checking against old (not currently active) slugs.
 *
 * @since 2.9.0
 *
 * @param string $group_slug The group's slug.
 * @return int|null The group ID on success; null on failure.
 */
function groups_get_id_by_previous_slug( $group_slug ) {
	return BP_Groups_Group::get_id_by_previous_slug( $group_slug );
}

/** User Actions **************************************************************/

/**
 * Remove a user from a group.
 *
 * @since 1.0.0
 *
 * @param int $group_id ID of the group.
 * @param int $user_id  Optional. ID of the user. Defaults to the currently
 *                      logged-in user.
 * @return bool True on success, false on failure.
 */
function groups_leave_group( $group_id, $user_id = 0 ) {

	if ( empty( $user_id ) )
		$user_id = bp_loggedin_user_id();

	// Don't let single admins leave the group.
	if ( count( groups_get_group_admins( $group_id ) ) < 2 ) {
		if ( groups_is_user_admin( $user_id, $group_id ) ) {
			bp_core_add_message( __( 'As the only admin, you cannot leave the group.', 'buddypress' ), 'error' );
			return false;
		}
	}

	if ( ! BP_Groups_Member::delete( $user_id, $group_id ) ) {
		return false;
	}

	bp_core_add_message( __( 'You successfully left the group.', 'buddypress' ) );

	/**
	 * Fires after a user leaves a group.
	 *
	 * @since 1.0.0
	 *
	 * @param int $group_id ID of the group.
	 * @param int $user_id  ID of the user leaving the group.
	 */
	do_action( 'groups_leave_group', $group_id, $user_id );

	return true;
}

/**
 * Add a user to a group.
 *
 * @since 1.0.0
 *
 * @param int $group_id ID of the group.
 * @param int $user_id  Optional. ID of the user. Defaults to the currently
 *                      logged-in user.
 * @return bool True on success, false on failure.
 */
function groups_join_group( $group_id, $user_id = 0 ) {

	if ( empty( $user_id ) )
		$user_id = bp_loggedin_user_id();

	// Check if the user has an outstanding invite. If so, delete it.
	if ( groups_check_user_has_invite( $user_id, $group_id ) )
		groups_delete_invite( $user_id, $group_id );

	// Check if the user has an outstanding request. If so, delete it.
	if ( groups_check_for_membership_request( $user_id, $group_id ) )
		groups_delete_membership_request( null, $user_id, $group_id );

	// User is already a member, just return true.
	if ( groups_is_user_member( $user_id, $group_id ) )
		return true;

	$new_member                = new BP_Groups_Member;
	$new_member->group_id      = $group_id;
	$new_member->user_id       = $user_id;
	$new_member->inviter_id    = 0;
	$new_member->is_admin      = 0;
	$new_member->user_title    = '';
	$new_member->date_modified = bp_core_current_time();
	$new_member->is_confirmed  = 1;

	if ( !$new_member->save() )
		return false;

	$bp = buddypress();

	if ( !isset( $bp->groups->current_group ) || !$bp->groups->current_group || $group_id != $bp->groups->current_group->id )
		$group = groups_get_group( $group_id );
	else
		$group = $bp->groups->current_group;

	// Record this in activity streams.
	if ( bp_is_active( 'activity' ) ) {
		groups_record_activity( array(
			'type'    => 'joined_group',
			'item_id' => $group_id,
			'user_id' => $user_id,
		) );
	}

	/**
	 * Fires after a user joins a group.
	 *
	 * @since 1.0.0
	 *
	 * @param int $group_id ID of the group.
	 * @param int $user_id  ID of the user joining the group.
	 */
	do_action( 'groups_join_group', $group_id, $user_id );

	return true;
}

/**
 * Update the last_activity meta value for a given group.
 *
 * @since 1.0.0
 *
 * @param int $group_id Optional. The ID of the group whose last_activity is
 *                      being updated. Default: the current group's ID.
 * @return false|null False on failure.
 */
function groups_update_last_activity( $group_id = 0 ) {

	if ( empty( $group_id ) ) {
		$group_id = buddypress()->groups->current_group->id;
	}

	if ( empty( $group_id ) ) {
		return false;
	}

	groups_update_groupmeta( $group_id, 'last_activity', bp_core_current_time() );
}
add_action( 'groups_join_group',           'groups_update_last_activity' );
add_action( 'groups_leave_group',          'groups_update_last_activity' );
add_action( 'groups_created_group',        'groups_update_last_activity' );

/** General Group Functions ***************************************************/

/**
 * Get a list of group administrators.
 *
 * @since 1.0.0
 *
 * @param int $group_id ID of the group.
 * @return array Info about group admins (user_id + date_modified).
 */
function groups_get_group_admins( $group_id ) {
	return BP_Groups_Member::get_group_administrator_ids( $group_id );
}

/**
 * Get a list of group moderators.
 *
 * @since 1.0.0
 *
 * @param int $group_id ID of the group.
 * @return array Info about group admins (user_id + date_modified).
 */
function groups_get_group_mods( $group_id ) {
	return BP_Groups_Member::get_group_moderator_ids( $group_id );
}

/**
 * Fetch the members of a group.
 *
 * Since BuddyPress 1.8, a procedural wrapper for BP_Group_Member_Query.
 * Previously called BP_Groups_Member::get_all_for_group().
 *
 * To use the legacy query, filter 'bp_use_legacy_group_member_query',
 * returning true.
 *
 * @since 1.0.0
 * @since 3.0.0 $group_id now supports multiple values. Only works if legacy query is not
 *              in use.
 *
 * @param array $args {
 *     An array of optional arguments.
 *     @type int|array|string $group_id            ID of the group to limit results to. Also accepts multiple values
 *                                                 either as an array or as a comma-delimited string.
 *     @type int              $page                Page of results to be queried. Default: 1.
 *     @type int              $per_page            Number of items to return per page of results. Default: 20.
 *     @type int              $max                 Optional. Max number of items to return.
 *     @type array            $exclude             Optional. Array of user IDs to exclude.
 *     @type bool|int         $exclude_admins_mods True (or 1) to exclude admins and mods from results. Default: 1.
 *     @type bool|int         $exclude_banned      True (or 1) to exclude banned users from results. Default: 1.
 *     @type array            $group_role          Optional. Array of group roles to include.
 *     @type string           $search_terms        Optional. Filter results by a search string.
 *     @type string           $type                Optional. Sort the order of results. 'last_joined', 'first_joined', or
 *                                                 any of the $type params available in {@link BP_User_Query}. Default:
 *                                                 'last_joined'.
 * }
 * @return false|array Multi-d array of 'members' list and 'count'.
 */
function groups_get_group_members( $args = array() ) {
	$function_args = func_get_args();

	// Backward compatibility with old method of passing arguments.
	if ( ! is_array( $args ) || count( $function_args ) > 1 ) {
		/* translators: 1: the name of the method. 2: the name of the file. */
		_deprecated_argument( __METHOD__, '2.0.0', sprintf( __( 'Arguments passed to %1$s should be in an associative array. See the inline documentation at %2$s for more details.', 'buddypress' ), __METHOD__, __FILE__ ) );

		$old_args_keys = array(
			0 => 'group_id',
			1 => 'per_page',
			2 => 'page',
			3 => 'exclude_admins_mods',
			4 => 'exclude_banned',
			5 => 'exclude',
			6 => 'group_role',
		);

		$args = bp_core_parse_args_array( $old_args_keys, $function_args );
	}

	$r = bp_parse_args( $args, array(
		'group_id'            => bp_get_current_group_id(),
		'per_page'            => false,
		'page'                => false,
		'exclude_admins_mods' => true,
		'exclude_banned'      => true,
		'exclude'             => false,
		'group_role'          => array(),
		'search_terms'        => false,
		'type'                => 'last_joined',
	), 'groups_get_group_members' );

	// For legacy users. Use of BP_Groups_Member::get_all_for_group() is deprecated.
	if ( apply_filters( 'bp_use_legacy_group_member_query', false, __FUNCTION__, $function_args ) ) {
		$retval = BP_Groups_Member::get_all_for_group( $r['group_id'], $r['per_page'], $r['page'], $r['exclude_admins_mods'], $r['exclude_banned'], $r['exclude'] );
	} else {

		// Both exclude_admins_mods and exclude_banned are legacy arguments.
		// Convert to group_role.
		if ( empty( $r['group_role'] ) ) {
			$r['group_role'] = array( 'member' );

			if ( ! $r['exclude_admins_mods'] ) {
				$r['group_role'][] = 'mod';
				$r['group_role'][] = 'admin';
			}

			if ( ! $r['exclude_banned'] ) {
				$r['group_role'][] = 'banned';
			}
		}

		// Perform the group member query (extends BP_User_Query).
		$members = new BP_Group_Member_Query( array(
			'group_id'       => $r['group_id'],
			'per_page'       => $r['per_page'],
			'page'           => $r['page'],
			'group_role'     => $r['group_role'],
			'exclude'        => $r['exclude'],
			'search_terms'   => $r['search_terms'],
			'type'           => $r['type'],
		) );

		// Structure the return value as expected by the template functions.
		$retval = array(
			'members' => array_values( $members->results ),
			'count'   => $members->total_users,
		);
	}

	return $retval;
}

/**
 * Get the member count for a group.
 *
 * @since 1.2.3
 *
 * @param int $group_id Group ID.
 * @return int Count of confirmed members for the group.
 */
function groups_get_total_member_count( $group_id ) {
	return BP_Groups_Group::get_total_member_count( $group_id );
}

/** Group Fetching, Filtering & Searching  ************************************/

/**
 * Get a collection of groups, based on the parameters passed.
 *
 * @since 1.2.0
 * @since 2.6.0 Added `$group_type`, `$group_type__in`, and `$group_type__not_in` parameters.
 * @since 2.7.0 Added `$update_admin_cache` and `$parent_id` parameters.
 *
 * @param array|string $args {
 *     Array of arguments. Supports all arguments of
 *     {@link BP_Groups_Group::get()}. Where the default values differ, they
 *     have been described here.
 *     @type int $per_page Default: 20.
 *     @type int $page Default: 1.
 * }
 * @return array See {@link BP_Groups_Group::get()}.
 */
function groups_get_groups( $args = '' ) {

	$defaults = array(
		'type'               => false,          // Active, newest, alphabetical, random, popular.
		'order'              => 'DESC',         // 'ASC' or 'DESC'
		'orderby'            => 'date_created', // date_created, last_activity, total_member_count, name, random, meta_id.
		'user_id'            => false,          // Pass a user_id to limit to only groups that this user is a member of.
		'include'            => false,          // Only include these specific groups (group_ids).
		'exclude'            => false,          // Do not include these specific groups (group_ids).
		'parent_id'          => null,           // Get groups that are children of the specified group(s).
		'slug'               => array(),        // Find a group or groups by slug.
		'search_terms'       => false,          // Limit to groups that match these search terms.
		'search_columns'     => array(),        // Select which columns to search.
		'group_type'         => '',             // Array or comma-separated list of group types to limit results to.
		'group_type__in'     => '',             // Array or comma-separated list of group types to limit results to.
		'group_type__not_in' => '',             // Array or comma-separated list of group types that will be excluded from results.
		'meta_query'         => false,          // Filter by groupmeta. See WP_Meta_Query for syntax.
		'show_hidden'        => false,          // Show hidden groups to non-admins.
		'status'             => array(),        // Array or comma-separated list of group statuses to limit results to.
		'per_page'           => 20,             // The number of results to return per page.
		'page'               => 1,              // The page to return if limiting per page.
		'update_meta_cache'  => true,           // Pre-fetch groupmeta for queried groups.
		'update_admin_cache' => false,
		'fields'             => 'all',          // Return BP_Groups_Group objects or a list of ids.
	);

	$r = bp_parse_args( $args, $defaults, 'groups_get_groups' );

	$groups = BP_Groups_Group::get( array(
		'type'               => $r['type'],
		'user_id'            => $r['user_id'],
		'include'            => $r['include'],
		'exclude'            => $r['exclude'],
		'slug'               => $r['slug'],
		'parent_id'          => $r['parent_id'],
		'search_terms'       => $r['search_terms'],
		'search_columns'     => $r['search_columns'],
		'group_type'         => $r['group_type'],
		'group_type__in'     => $r['group_type__in'],
		'group_type__not_in' => $r['group_type__not_in'],
		'meta_query'         => $r['meta_query'],
		'show_hidden'        => $r['show_hidden'],
		'status'             => $r['status'],
		'per_page'           => $r['per_page'],
		'page'               => $r['page'],
		'update_meta_cache'  => $r['update_meta_cache'],
		'update_admin_cache' => $r['update_admin_cache'],
		'order'              => $r['order'],
		'orderby'            => $r['orderby'],
		'fields'             => $r['fields'],
	) );

	/**
	 * Filters the collection of groups based on parsed parameters.
	 *
	 * @since 1.2.0
	 *
	 * @param BP_Groups_Group $groups Object of found groups based on parameters.
	 *                                Passed by reference.
	 * @param array           $r      Array of parsed arguments used for group query.
	 *                                Passed by reference.
	 */
	return apply_filters_ref_array( 'groups_get_groups', array( &$groups, &$r ) );
}

/**
 * Get the total group count for the site.
 *
 * @since 1.2.0
 *
 * @return int
 */
function groups_get_total_group_count() {
	$count = wp_cache_get( 'bp_total_group_count', 'bp' );

	if ( false === $count ) {
		$count = BP_Groups_Group::get_total_group_count();
		wp_cache_set( 'bp_total_group_count', $count, 'bp' );
	}

	return $count;
}

/**
 * Get the IDs of the groups of which a specified user is a member.
 *
 * @since 1.0.0
 *
 * @param int $user_id  ID of the user.
 * @param int $pag_num  Optional. Max number of results to return.
 *                      Default: false (no limit).
 * @param int $pag_page Optional. Page offset of results to return.
 *                      Default: false (no limit).
 * @return array {
 *     @type array $groups Array of groups returned by paginated query.
 *     @type int   $total Count of groups matching query.
 * }
 */
function groups_get_user_groups( $user_id = 0, $pag_num = 0, $pag_page = 0 ) {

	if ( empty( $user_id ) )
		$user_id = bp_displayed_user_id();

	return BP_Groups_Member::get_group_ids( $user_id, $pag_num, $pag_page );
}

/**
 * Get a list of groups of which the specified user is a member.
 *
 * Get a list of the groups to which this member belongs,
 * filtered by group membership status and role.
 * Usage examples: Used with no arguments specified,
 *
 *    bp_get_user_groups( bp_loggedin_user_id() );
 *
 * returns an array of the groups in which the logged-in user
 * is an unpromoted member. To fetch an array of all groups that
 * the current user belongs to, in any membership role,
 * member, moderator or administrator, use
 *
 *    bp_get_user_groups( $user_id, array(
 *        'is_admin' => null,
 *        'is_mod' => null,
 *    ) );
 *
 * @since 2.6.0
 *
 * @param int $user_id ID of the user.
 * @param array $args {
 *     Array of optional args.
 *     @param bool|null   $is_confirmed Whether to return only confirmed memberships. Pass `null` to disable this
 *                                      filter. Default: true.
 *     @param bool|null   $is_banned    Whether to return only banned memberships. Pass `null` to disable this filter.
 *                                      Default: false.
 *     @param bool|null   $is_admin     Whether to return only admin memberships. Pass `null` to disable this filter.
 *                                      Default: false.
 *     @param bool|null   $is_mod       Whether to return only mod memberships. Pass `null` to disable this filter.
 *                                      Default: false.
 *     @param bool|null   $invite_sent  Whether to return only memberships with 'invite_sent'. Pass `null` to disable
 *                                      this filter. Default: false.
 *     @param string      $orderby      Field to order by. Accepts 'id' (membership ID), 'group_id', 'date_modified'.
 *                                      Default: 'group_id'.
 *     @param string      $order        Sort order. Accepts 'ASC' or 'DESC'. Default: 'ASC'.
 * }
 * @return array Array of matching group memberships, keyed by group ID.
 */
function bp_get_user_groups( $user_id, $args = array() ) {
	$r = bp_parse_args( $args, array(
		'is_confirmed' => true,
		'is_banned'    => false,
		'is_admin'     => false,
		'is_mod'       => false,
		'invite_sent'  => null,
		'orderby'      => 'group_id',
		'order'        => 'ASC',
	), 'get_user_groups' );

	$user_id = intval( $user_id );

	// Standard memberships
	$membership_ids = wp_cache_get( $user_id, 'bp_groups_memberships_for_user' );
	if ( false === $membership_ids ) {
		$membership_ids = BP_Groups_Member::get_membership_ids_for_user( $user_id );
		wp_cache_set( $user_id, $membership_ids, 'bp_groups_memberships_for_user' );
	}

	// Prime the membership cache.
	$uncached_membership_ids = bp_get_non_cached_ids( $membership_ids, 'bp_groups_memberships' );
	if ( ! empty( $uncached_membership_ids ) ) {
		$uncached_memberships = BP_Groups_Member::get_memberships_by_id( $uncached_membership_ids );

		foreach ( $uncached_memberships as $uncached_membership ) {
			wp_cache_set( $uncached_membership->id, $uncached_membership, 'bp_groups_memberships' );
		}
	}

	// Prime the invitations- and requests-as-memberships cache
	$invitation_ids = array();
	if ( true !== $r['is_confirmed'] || false !== $r['invite_sent'] ) {
		$invitation_ids = groups_get_invites( array(
			'user_id'     => $user_id,
			'invite_sent' => 'all',
			'type'        => 'all',
			'fields'      => 'ids'
		) );

		// Prime the invitations cache.
		$uncached_invitation_ids = bp_get_non_cached_ids( $invitation_ids, 'bp_groups_invitations_as_memberships' );
		if ( $uncached_invitation_ids ) {
			$uncached_invitations = groups_get_invites( array(
				'id'          => $uncached_invitation_ids,
				'invite_sent' => 'all',
				'type'        => 'all'
			) );
			foreach ( $uncached_invitations as $uncached_invitation ) {
				// Reshape the result as a membership db entry.
				$invitation = new StdClass;
				$invitation->id            = $uncached_invitation->id;
				$invitation->group_id      = $uncached_invitation->item_id;
				$invitation->user_id       = $uncached_invitation->user_id;
				$invitation->inviter_id    = $uncached_invitation->inviter_id;
				$invitation->is_admin      = false;
				$invitation->is_mod        = false;
				$invitation->user_title    = '';
				$invitation->date_modified = $uncached_invitation->date_modified;
				$invitation->comments      = $uncached_invitation->content;
				$invitation->is_confirmed  = false;
				$invitation->is_banned     = false;
				$invitation->invite_sent   = $uncached_invitation->invite_sent;
				wp_cache_set( $uncached_invitation->id, $invitation, 'bp_groups_invitations_as_memberships' );
			}
		}
	}

	// Assemble filter array for use in `wp_list_filter()`.
	$filters = wp_array_slice_assoc( $r, array( 'is_confirmed', 'is_banned', 'is_admin', 'is_mod', 'invite_sent' ) );
	foreach ( $filters as $filter_name => $filter_value ) {
		if ( is_null( $filter_value ) ) {
			unset( $filters[ $filter_name ] );
		}
	}

	// Populate group membership array from cache, and normalize.
	$groups    = array();
	$int_keys  = array( 'id', 'group_id', 'user_id', 'inviter_id' );
	$bool_keys = array( 'is_admin', 'is_mod', 'is_confirmed', 'is_banned', 'invite_sent' );
	foreach ( $membership_ids as $membership_id ) {
		$membership = wp_cache_get( $membership_id, 'bp_groups_memberships' );

		// Sanity check.
		if ( ! isset( $membership->group_id ) ) {
			continue;
		}

		// Integer values.
		foreach ( $int_keys as $index ) {
			$membership->{$index} = intval( $membership->{$index} );
		}

		// Boolean values.
		foreach ( $bool_keys as $index ) {
			$membership->{$index} = (bool) $membership->{$index};
		}

		foreach ( $filters as $filter_name => $filter_value ) {
			if ( ! isset( $membership->{$filter_name} ) || $filter_value != $membership->{$filter_name} ) {
				continue 2;
			}
		}

		$group_id = (int) $membership->group_id;

		$groups[ $group_id ] = $membership;
	}

	// Populate group invitations array from cache, and normalize.
	foreach ( $invitation_ids as $invitation_id ) {
		$invitation = wp_cache_get( $invitation_id, 'bp_groups_invitations_as_memberships' );

		// Sanity check.
		if ( ! isset( $invitation->group_id ) ) {
			continue;
		}

		// Integer values.
		foreach ( $int_keys as $index ) {
			$invitation->{$index} = intval( $invitation->{$index} );
		}

		// Boolean values.
		foreach ( $bool_keys as $index ) {
			$invitation->{$index} = (bool) $invitation->{$index};
		}

		foreach ( $filters as $filter_name => $filter_value ) {
			if ( ! isset( $invitation->{$filter_name} ) || $filter_value != $invitation->{$filter_name} ) {
				continue 2;
			}
		}

		$group_id = (int) $invitation->group_id;

		$groups[ $group_id ] = $invitation;
	}

	// By default, results are ordered by membership id.
	if ( 'group_id' === $r['orderby'] ) {
		ksort( $groups );
	} elseif ( in_array( $r['orderby'], array( 'id', 'date_modified' ) ) ) {
		$groups = bp_sort_by_key( $groups, $r['orderby'] );
	}

	// By default, results are ordered ASC.
	if ( 'DESC' === strtoupper( $r['order'] ) ) {
		// `true` to preserve keys.
		$groups = array_reverse( $groups, true );
	}

	return $groups;
}

/**
 * Get the count of groups of which the specified user is a member.
 *
 * @since 1.0.0
 *
 * @param int $user_id Optional. Default: ID of the displayed user.
 * @return int Group count.
 */
function groups_total_groups_for_user( $user_id = 0 ) {

	if ( empty( $user_id ) )
		$user_id = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();

	$count = wp_cache_get( 'bp_total_groups_for_user_' . $user_id, 'bp' );

	if ( false === $count ) {
		$count = BP_Groups_Member::total_group_count( $user_id );
		wp_cache_set( 'bp_total_groups_for_user_' . $user_id, $count, 'bp' );
	}

	return (int) $count;
}

/**
 * Get the BP_Groups_Group object corresponding to the current group.
 *
 * @since 1.5.0
 *
 * @return BP_Groups_Group The current group object.
 */
function groups_get_current_group() {
	$bp = buddypress();

	$current_group = isset( $bp->groups->current_group )
		? $bp->groups->current_group
		: false;

	/**
	 * Filters the BP_Groups_Group object corresponding to the current group.
	 *
	 * @since 1.5.0
	 *
	 * @param BP_Groups_Group $current_group Current BP_Groups_Group object.
	 */
	return apply_filters( 'groups_get_current_group', $current_group );
}

/** Group Avatars *************************************************************/

/**
 * Generate the avatar upload directory path for a given group.
 *
 * @since 1.1.0
 *
 * @param int $group_id Optional. ID of the group. Default: ID of the current group.
 * @return string
 */
function groups_avatar_upload_dir( $group_id = 0 ) {

	if ( empty( $group_id ) ) {
		$group_id = bp_get_current_group_id();
	}

	$directory = 'group-avatars';
	$path      = bp_core_avatar_upload_path() . '/' . $directory . '/' . $group_id;
	$newbdir   = $path;
	$newurl    = bp_core_avatar_url() . '/' . $directory . '/' . $group_id;
	$newburl   = $newurl;
	$newsubdir = '/' . $directory . '/' . $group_id;

	/**
	 * Filters the avatar upload directory path for a given group.
	 *
	 * @since 1.1.0
	 *
	 * @param array $value Array of parts related to the groups avatar upload directory.
	 */
	return apply_filters( 'groups_avatar_upload_dir', array(
		'path'    => $path,
		'url'     => $newurl,
		'subdir'  => $newsubdir,
		'basedir' => $newbdir,
		'baseurl' => $newburl,
		'error'   => false
	) );
}

/** Group Member Status Checks ************************************************/

/**
 * Get the Group roles.
 *
 * @since 5.0.0
 *
 * @return array The list of Group role objects.
 */
function bp_groups_get_group_roles() {
	return array(
		'admin' => (object) array(
			'id'           => 'admin',
			'name'         => __( 'Administrator', 'buddypress' ),
			'is_admin'     => true,
			'is_banned'    => false,
			'is_confirmed' => true,
			'is_mod'       => false,
		),
		'mod' => (object) array(
			'id'           => 'mod',
			'name'         => __( 'Moderator', 'buddypress' ),
			'is_admin'     => false,
			'is_banned'    => false,
			'is_confirmed' => true,
			'is_mod'       => true,
		),
		'member' => (object) array(
			'id'           => 'member',
			'name'         => __( 'Member', 'buddypress' ),
			'is_admin'     => false,
			'is_banned'    => false,
			'is_confirmed' => true,
			'is_mod'       => false,
		),
		'banned' => (object) array(
			'id'           => 'banned',
			'name'         => __( 'Banned', 'buddypress' ),
			'is_admin'     => false,
			'is_banned'    => true,
			'is_confirmed' => true,
			'is_mod'       => false,
		),
	);
}

/**
 * Check whether a user is an admin of a given group.
 *
 * @since 1.0.0
 *
 * @param int $user_id ID of the user.
 * @param int $group_id ID of the group.
 * @return int|bool ID of the membership if the user is admin, otherwise false.
 */
function groups_is_user_admin( $user_id, $group_id ) {
	$is_admin = false;

	$user_groups = bp_get_user_groups( $user_id, array(
		'is_admin' => true,
	) );

	if ( isset( $user_groups[ $group_id ] ) ) {
		$is_admin = $user_groups[ $group_id ]->id;
	}

	return $is_admin;
}

/**
 * Check whether a user is a mod of a given group.
 *
 * @since 1.0.0
 *
 * @param int $user_id ID of the user.
 * @param int $group_id ID of the group.
 * @return int|bool ID of the membership if the user is mod, otherwise false.
 */
function groups_is_user_mod( $user_id, $group_id ) {
	$is_mod = false;

	$user_groups = bp_get_user_groups( $user_id, array(
		'is_mod' => true,
	) );

	if ( isset( $user_groups[ $group_id ] ) ) {
		$is_mod = $user_groups[ $group_id ]->id;
	}

	return $is_mod;
}

/**
 * Check whether a user is a member of a given group.
 *
 * @since 1.0.0
 *
 * @param int $user_id ID of the user.
 * @param int $group_id ID of the group.
 * @return int|bool ID of the membership if the user is member, otherwise false.
 */
function groups_is_user_member( $user_id, $group_id ) {
	$is_member = false;

	$user_groups = bp_get_user_groups( $user_id, array(
		'is_admin' => null,
		'is_mod' => null,
	) );

	if ( isset( $user_groups[ $group_id ] ) ) {
		$is_member = $user_groups[ $group_id ]->id;
	}

	return $is_member;
}

/**
 * Check whether a user is banned from a given group.
 *
 * @since 1.0.0
 *
 * @param int $user_id  ID of the user.
 * @param int $group_id ID of the group.
 * @return int|bool ID of the membership if the user is banned, otherwise false.
 */
function groups_is_user_banned( $user_id, $group_id ) {
	$is_banned = false;

	$user_groups = bp_get_user_groups( $user_id, array(
		'is_confirmed' => null,
		'is_banned' => true,
	) );

	if ( isset( $user_groups[ $group_id ] ) ) {
		$is_banned = $user_groups[ $group_id ]->id;
	}

	return $is_banned;
}

/**
 * Check whether a user has an outstanding invitation to a group.
 *
 * @since 2.6.0
 * @since 5.0.0 Added $type parameter.
 *
 * @param int    $user_id  ID of the user.
 * @param int    $group_id ID of the group.
 * @param string $type     If 'sent', results are limited to those invitations
 *                         that have actually been sent (non-draft).
 *                         Possible values: 'sent', 'draft', or 'all' Default: 'sent'.
 * @return int|bool ID of the membership if the user is invited, otherwise false.
 */
function groups_is_user_invited( $user_id, $group_id, $type = 'sent' ) {
	return groups_check_has_invite_from_user( $user_id, $group_id, false, $type );
}

/**
 * Check whether a user has a pending membership request for a group.
 *
 * @since 2.6.0
 *
 * @param int $user_id ID of the user.
 * @param int $group_id ID of the group.
 * @return int|bool ID of the membership if the user is pending, otherwise false.
 */
function groups_is_user_pending( $user_id, $group_id ) {
	if ( empty( $user_id ) || empty( $group_id ) ) {
		return false;
	}

	$args = array(
		'user_id'     => $user_id,
		'item_id'     => $group_id,
	);
	$invites_class = new BP_Groups_Invitation_Manager();

	return $invites_class->request_exists( $args );
}

/**
 * Is the specified user the creator of the group?
 *
 * @since 1.2.6
 *
 * @param int $user_id ID of the user.
 * @param int $group_id ID of the group.
 * @return int|null
 */
function groups_is_user_creator( $user_id, $group_id ) {
	return BP_Groups_Member::check_is_creator( $user_id, $group_id );
}

/** Group Invitations *********************************************************/

/**
 * Get group objects for groups that a user is currently invited to.
 *
 * @since 1.0.0
 *
 * @param int               $user_id ID of the invited user.
 * @param int|bool          $limit   Limit to restrict to.
 * @param int|bool          $page    Optional. Page offset of results to return.
 * @param string|array|bool $exclude Array of comma-separated list of group IDs
 *                                   to exclude from results.
 * @return array {
 *     @type array $groups Array of groups returned by paginated query.
 *     @type int   $total  Count of groups matching query.
 * }
 */
function groups_get_invites_for_user( $user_id = 0, $limit = false, $page = false, $exclude = false ) {
	if ( empty( $user_id ) ) {
		$user_id = bp_loggedin_user_id();
	}

	$group_ids = groups_get_invited_to_group_ids( $user_id );

	// Remove excluded groups.
	if ( $exclude ) {
		$group_ids = array_diff( $group_ids, wp_parse_id_list( $exclude ) );
	}

	// Avoid passing an empty array.
	if ( ! $group_ids ) {
		$group_ids = array( 0 );
	}

	// Get a filtered list of groups.
	$args = array(
		'include'     => $group_ids,
		'show_hidden' => true,
		'per_page'    => $limit,
		'page'        => $page,
	);
	$groups = groups_get_groups( $args );

	return array( 'groups' => $groups['groups'], 'total' => groups_get_invite_count_for_user( $user_id ) );
}

/**
 * Get the total group invite count for a user.
 *
 * @since 2.0.0
 *
 * @param int $user_id The user ID.
 * @return int
 */
function groups_get_invite_count_for_user( $user_id = 0 ) {
	if ( empty( $user_id ) ) {
		$user_id = bp_loggedin_user_id();
	}

	return count( groups_get_invited_to_group_ids( $user_id ) );
}

/**
 * Get an array of group IDs to which a user is invited.
 *
 * @since 5.0.0
 *
 * @param int $user_id The user ID.
 *
 * @return array Array of group IDs.
 */
 function groups_get_invited_to_group_ids( $user_id = 0 ) {
	if ( empty( $user_id ) ) {
		$user_id = bp_loggedin_user_id();
	}

	$group_ids = groups_get_invites( array(
		'user_id'     => $user_id,
		'invite_sent' => 'sent',
		'fields'      => 'item_ids'
	) );

	return array_unique( $group_ids );
}

/**
 * Invite a user to a group.
 *
 * @since 1.0.0
 *
 * @param array|string $args {
 *     Array of arguments.
 *     @type int    $user_id       ID of the user being invited.
 *     @type int    $group_id      ID of the group to which the user is being invited.
 *     @type int    $inviter_id    Optional. ID of the inviting user. Default:
 *                                 ID of the logged-in user.
 *     @type string $date_modified Optional. Modified date for the invitation.
 *                                 Default: current date/time.
 *     @type string $content       Optional. Message to invitee.
 *     @type bool   $send_invite   Optional. Whether the invitation should be
 *                                 sent now. Default: false.
 * }
 * @return bool True on success, false on failure.
 */
function groups_invite_user( $args = '' ) {

	$r = bp_parse_args( $args, array(
		'user_id'       => false,
		'group_id'      => false,
		'inviter_id'    => bp_loggedin_user_id(),
		'date_modified' => bp_core_current_time(),
		'content'       => '',
		'send_invite'   => 0
	), 'groups_invite_user' );

	$inv_args = array(
		'user_id'       => $r['user_id'],
		'item_id'       => $r['group_id'],
		'inviter_id'    => $r['inviter_id'],
		'date_modified' => $r['date_modified'],
		'content'       => $r['content'],
		'send_invite'   => $r['send_invite']
	);

	// Create the unsent invitataion.
	$invites_class = new BP_Groups_Invitation_Manager();
	$created       = $invites_class->add_invitation( $inv_args );

	/**
	 * Fires after the creation of a new group invite.
	 *
	 * @since 1.0.0
	 *
	 * @param array    $r       Array of parsed arguments for the group invite.
	 * @param int|bool $created The ID of the invitation or false if it couldn't be created.
	 */
	do_action( 'groups_invite_user', $r, $created );

	return $created;
}

/**
 * Uninvite a user from a group.
 *
 * @since 1.0.0
 *
 * @param int $user_id  ID of the user.
 * @param int $group_id ID of the group.
 * @param int $inviter_id ID of the inviter.
 * @return bool True on success, false on failure.
 */
function groups_uninvite_user( $user_id, $group_id, $inviter_id = false ) {
	if ( empty( $user_id ) || empty( $group_id ) ) {
		return false;
	}

	$invites_class = new BP_Groups_Invitation_Manager();
	$success       = $invites_class->delete( array(
		'user_id'    => $user_id,
		'item_id'    => $group_id,
		'inviter_id' => $inviter_id,
	) );

	if ( $success ) {
		/**
		 * Fires after uninviting a user from a group.
		 *
		 * @since 1.0.0
		 * @since 2.7.0 Added $inviter_id parameter
		 *
		 * @param int $group_id    ID of the group being uninvited from.
		 * @param int $user_id     ID of the user being uninvited.
		 * @param int $inviter_id  ID of the inviter.
		 */
		do_action( 'groups_uninvite_user', $group_id, $user_id, $inviter_id );
	}

	return $success;
}

/**
 * Process the acceptance of a group invitation.
 *
 * Returns true if a user is already a member of the group.
 *
 * @since 1.0.0
 *
 * @param int $user_id  ID of the user.
 * @param int $group_id ID of the group.
 * @return bool True when the user is a member of the group, otherwise false.
 */
function groups_accept_invite( $user_id, $group_id ) {
	$invites_class = new BP_Groups_Invitation_Manager();
	$args = array(
		'user_id'     => $user_id,
		'item_id'     => $group_id,
		'invite_sent' => 'sent',
	);

	return $invites_class->accept_invitation( $args );
}

/**
 * Reject a group invitation.
 *
 * @since 1.0.0
 * @since 5.0.0 The $inviter_id arg was added.
 *
 * @param int $user_id    ID of the user.
 * @param int $group_id   ID of the group.
 * @param int $inviter_id ID of the inviter.
 *
 * @return bool True on success, false on failure.
 */
function groups_reject_invite( $user_id, $group_id, $inviter_id = false ) {
	if ( empty( $user_id ) || empty( $group_id ) ) {
		return false;
	}

	$invites_class = new BP_Groups_Invitation_Manager();
	$success       = $invites_class->delete( array(
		'user_id'    => $user_id,
		'item_id'    => $group_id,
		'inviter_id' => $inviter_id,
	) );

	/**
	 * Fires after a user rejects a group invitation.
	 *
	 * @since 1.0.0
	 * @since 5.0.0 The $inviter_id arg was added.
	 *
	 * @param int $user_id    ID of the user rejecting the invite.
	 * @param int $group_id   ID of the group being rejected.
	 * @param int $inviter_id ID of the inviter.
	 */
	do_action( 'groups_reject_invite', $user_id, $group_id, $inviter_id );

	return $success;
}

/**
 * Delete a group invitation.
 *
 * @since 1.0.0
 * @since 5.0.0 The $inviter_id arg was added.
 *
 * @param int $user_id  ID of the invited user.
 * @param int $group_id ID of the group.
 * @param int $inviter_id ID of the inviter.
 *
 * @return bool True on success, false on failure.
 */
function groups_delete_invite( $user_id, $group_id, $inviter_id = false ) {
	if ( empty( $user_id ) || empty( $group_id ) ) {
		return false;
	}

	$invites_class = new BP_Groups_Invitation_Manager();
	$success       = $invites_class->delete( array(
		'user_id'    => $user_id,
		'item_id'    => $group_id,
		'inviter_id' => $inviter_id,
	) );

	/**
	 * Fires after the deletion of a group invitation.
	 *
	 * @since 1.9.0
	 * @since 5.0.0 The $inviter_id arg was added.
	 *
	 * @param int $user_id  ID of the user whose invitation is being deleted.
	 * @param int $group_id ID of the group whose invitation is being deleted.
	 * @param int $inviter_id ID of the inviter.
	 */
	do_action( 'groups_delete_invite', $user_id, $group_id, $inviter_id );

	return true;
}

/**
 * Send some or all pending invites by a single user to a specific group.
 *
 * @since 1.0.0
 * @since 5.0.0 Parameters changed to associative array.
 *
 * @param array $args {
 *     An array of optional arguments.
 *     @type int    $user_id       ID of the invited user.
 *     @type string $invitee_email Email address of the invited user, if not a member of the site.
 *     @type string $group_id      ID of the group or an array of group IDs.
 *     @type string $inviter_id    ID of the user extending the invitation.
 *     @type bool   $force_resend  Whether to resend the email & notification if one has already been sent.
 * }
 */
function groups_send_invites( $args = array() ) {
	// Backward compatibility with old method of passing arguments.
	if ( ! is_array( $args ) || func_num_args() > 1 ) {
		_deprecated_argument( __METHOD__, '5.0.0', sprintf( __( 'Arguments passed to %1$s should be in an associative array. See the inline documentation at %2$s for more details.', 'buddypress' ), __METHOD__, __FILE__ ) );

		$old_args_keys = array(
			0 => 'inviter_id',
			1 => 'group_id',
		);

		$args = bp_core_parse_args_array( $old_args_keys, func_get_args() );
	}

	$r = bp_parse_args( $args, array(
		'user_id'       => false,
		'invitee_email' => '',
		'group_id'      => 0,
		'inviter_id'    => bp_loggedin_user_id(),
		'force_resend'  => false,
	), 'groups_send_invitation' );


	$args = array(
		'user_id'       => $r['user_id'],
		'invitee_email' => $r['invitee_email'],
		'item_id'       => $r['group_id'],
		'inviter_id'    => $r['inviter_id'],
	);

	/*
	 * We will generally only want to fetch unsent invitations.
	 * If force_resend is true, then we need to fetch both sent and draft invites.
	 */
	if ( $r['force_resend'] ) {
		$args['invite_sent'] = 'all';
	} else {
		$args['invite_sent'] = 'draft';
	}

	$invites = groups_get_invites( $args );

	$invited_users = array();

	$invites_class = new BP_Groups_Invitation_Manager();
	foreach ( $invites as $invite ) {
		$invited_users[] = $invite->user_id;
		$invites_class->send_invitation_by_id( $invite->id );
	}

	/**
	 * Fires after the sending of invites for a group.
	 *
	 * @since 1.0.0
	 * @since 2.5.0 Added $user_id to passed parameters.
	 *
	 * @param int   $group_id      ID of the group who's being invited to.
	 * @param array $invited_users Array of users being invited to the group.
	 * @param int   $user_id       ID of the inviting user.
	 */
	do_action( 'groups_send_invites', $r['group_id'], $invited_users, $r['inviter_id'] );
}

/**
 * Get IDs of users with outstanding invites to a given group.
 *
 * @since 1.0.0
 * @since 2.9.0 Added $sent as a parameter.
 *
 * @param  int      $user_id  ID of the inviting user.
 * @param  int      $group_id ID of the group.
 * @param  int|null $sent     Query for a specific invite sent status. If 0, this will query for users
 *                            that haven't had an invite sent to them yet. If 1, this will query for
 *                            users that have had an invite sent to them. If null, no invite status will
 *                            queried. Default: null.
 * @return array    IDs of users who have been invited to the group by the user but have not
 *                  yet accepted.
 */
function groups_get_invites_for_group( $user_id, $group_id, $sent = null ) {
	return BP_Groups_Group::get_invites( $user_id, $group_id, $sent );
}

/**
 * Get invitations to a given group filtered by arguments.
 *
 * @since 5.0.0
 *
 * @param int   $group_id ID of the group.
 * @param array $args     Invitation arguments.
 *                        See BP_Invitation::get() for list.
 *
 * @return array $invites     Matching BP_Invitation objects.
 */
function groups_get_invites( $args = array() ) {
	$invites_class = new BP_Groups_Invitation_Manager();
	return $invites_class->get_invitations( $args );
}

/**
 * Check to see whether a user has already been invited to a group.
 *
 * By default, the function checks for invitations that have been sent.
 * Entering 'all' as the $type parameter will return unsent invitations as
 * well (useful to make sure AJAX requests are not duplicated).
 *
 * @since 1.0.0
 *
 * @param int    $user_id  ID of potential group member.
 * @param int    $group_id ID of potential group.
 * @param string $type     Optional. Use 'sent' to check for sent invites,
 *                         'all' to check for all. Default: 'sent'.
 * @return int|bool ID of the first found membership if found, otherwise false.
 */
function groups_check_user_has_invite( $user_id, $group_id, $type = 'sent' ) {
	return groups_check_has_invite_from_user( $user_id, $group_id, false, $type );
}

/**
 * Check to see whether a user has already been invited to a group by a particular user.
 *
 * By default, the function checks for invitations that have been sent.
 * Entering 'all' as the $type parameter will return unsent invitations as
 * well (useful to make sure AJAX requests are not duplicated).
 *
 * @since 5.0.0
 *
 * @param int    $user_id    ID of potential group member.
 * @param int    $group_id   ID of potential group.
 * @param string $inviter_id Optional. Use 'sent' to check for sent invites,
 *                           'all' to check for all. Default: 'sent'.
 * @param string $type       Optional. Specify a user ID to limit to only invited from that user.
 *                           Default: 'false'.
 * @return int|bool ID of the first found membership if found, otherwise false.
 */
 function groups_check_has_invite_from_user( $user_id, $group_id, $inviter_id = false, $type = 'sent' ) {
	if ( empty( $user_id ) || empty( $group_id ) ) {
		return false;
	}

	$args = array(
		'user_id'     => $user_id,
		'item_id'     => $group_id,
		'invite_sent' => 'sent',
	);
	if ( $inviter_id ) {
		$args['inviter_id'] = $inviter_id;
	}
	if ( $type === 'draft' || $type === 'all' ) {
		$args['invite_sent'] = $type;
	}

	$invites_class = new BP_Groups_Invitation_Manager();

	return $invites_class->invitation_exists( $args );
}

/**
 * Delete all invitations to a given group.
 *
 * @since 1.0.0
 *
 * @param int $group_id ID of the group whose invitations are being deleted.
 * @return int|null Number of rows records deleted on success, null on failure.
 */
function groups_delete_all_group_invites( $group_id ) {
	return BP_Groups_Group::delete_all_invites( $group_id );
}

/** Group Promotion & Banning *************************************************/

/**
 * Promote a member to a new status within a group.
 *
 * @since 1.0.0
 *
 * @param int    $user_id  ID of the user.
 * @param int    $group_id ID of the group.
 * @param string $status   The new status. 'mod' or 'admin'.
 * @return bool True on success, false on failure.
 */
function groups_promote_member( $user_id, $group_id, $status ) {

	if ( ! bp_is_item_admin() )
		return false;

	$member = new BP_Groups_Member( $user_id, $group_id );

	// Don't use this action. It's deprecated as of BuddyPress 1.6.
	do_action( 'groups_premote_member', $group_id, $user_id, $status );

	/**
	 * Fires before the promotion of a user to a new status.
	 *
	 * @since 1.6.0
	 *
	 * @param int    $group_id ID of the group being promoted in.
	 * @param int    $user_id  ID of the user being promoted.
	 * @param string $status   New status being promoted to.
	 */
	do_action( 'groups_promote_member', $group_id, $user_id, $status );

	return $member->promote( $status );
}

/**
 * Demote a user to 'member' status within a group.
 *
 * @since 1.0.0
 *
 * @param int $user_id  ID of the user.
 * @param int $group_id ID of the group.
 * @return bool True on success, false on failure.
 */
function groups_demote_member( $user_id, $group_id ) {

	if ( ! bp_is_item_admin() )
		return false;

	$member = new BP_Groups_Member( $user_id, $group_id );

	/**
	 * Fires before the demotion of a user to 'member'.
	 *
	 * @since 1.0.0
	 *
	 * @param int $group_id ID of the group being demoted in.
	 * @param int $user_id  ID of the user being demoted.
	 */
	do_action( 'groups_demote_member', $group_id, $user_id );

	return $member->demote();
}

/**
 * Ban a member from a group.
 *
 * @since 1.0.0
 *
 * @param int $user_id  ID of the user.
 * @param int $group_id ID of the group.
 * @return bool True on success, false on failure.
 */
function groups_ban_member( $user_id, $group_id ) {

	if ( ! bp_is_item_admin() )
		return false;

	$member = new BP_Groups_Member( $user_id, $group_id );

	/**
	 * Fires before the banning of a member from a group.
	 *
	 * @since 1.0.0
	 *
	 * @param int $group_id ID of the group being banned from.
	 * @param int $user_id  ID of the user being banned.
	 */
	do_action( 'groups_ban_member', $group_id, $user_id );

	return $member->ban();
}

/**
 * Unban a member from a group.
 *
 * @since 1.0.0
 *
 * @param int $user_id  ID of the user.
 * @param int $group_id ID of the group.
 * @return bool True on success, false on failure.
 */
function groups_unban_member( $user_id, $group_id ) {

	if ( ! bp_is_item_admin() )
		return false;

	$member = new BP_Groups_Member( $user_id, $group_id );

	/**
	 * Fires before the unbanning of a member from a group.
	 *
	 * @since 1.0.0
	 *
	 * @param int $group_id ID of the group being unbanned from.
	 * @param int $user_id  ID of the user being unbanned.
	 */
	do_action( 'groups_unban_member', $group_id, $user_id );

	return $member->unban();
}

/** Group Removal *************************************************************/

/**
 * Remove a member from a group.
 *
 * @since 1.2.6
 *
 * @param int $user_id  ID of the user.
 * @param int $group_id ID of the group.
 * @return bool True on success, false on failure.
 */
function groups_remove_member( $user_id, $group_id ) {

	if ( ! bp_is_item_admin() ) {
		return false;
	}

	$member = new BP_Groups_Member( $user_id, $group_id );

	/**
	 * Fires before the removal of a member from a group.
	 *
	 * @since 1.2.6
	 *
	 * @param int $group_id ID of the group being removed from.
	 * @param int $user_id  ID of the user being removed.
	 */
	do_action( 'groups_remove_member', $group_id, $user_id );

	return $member->remove();
}

/** Group Membership **********************************************************/

/**
 * Create a group membership request.
 *
 * @since 1.0.0
 *
 * @param array|string $args {
 *     Array of arguments.
 *     @type int    $user_id       ID of the user being invited.
 *     @type int    $group_id      ID of the group to which the user is being invited.
 *     @type string $content       Optional. Message to invitee.
 *     @type string $date_modified Optional. Modified date for the invitation.
 *                                 Default: current date/time.
 * }
 * @return bool True on success, false on failure.
 */
function groups_send_membership_request( $args = array() ) {
	// Backward compatibility with old method of passing arguments.
	if ( ! is_array( $args ) || func_num_args() > 1 ) {
		_deprecated_argument( __METHOD__, '5.0.0', sprintf( __( 'Arguments passed to %1$s should be in an associative array. See the inline documentation at %2$s for more details.', 'buddypress' ), __METHOD__, __FILE__ ) );

		$old_args_keys = array(
			0 => 'user_id',
			1 => 'group_id',
		);

		$args = bp_core_parse_args_array( $old_args_keys, func_get_args() );
	}

	$r = bp_parse_args( $args, array(
		'user_id'       => false,
		'group_id'      => false,
		'content'       => '',
		'date_modified' => bp_core_current_time(),
	), 'groups_send_membership_request' );

	$inv_args = array(
		'user_id'       => $r['user_id'],
		'item_id'       => $r['group_id'],
		'content'       => $r['content'],
		'date_modified' => $r['date_modified'],
	);

	$invites_class = new BP_Groups_Invitation_Manager();
	$request_id = $invites_class->add_request( $inv_args );

	// If a new request was created, send the emails.
	if ( $request_id && is_int( $request_id ) ) {
		$invites_class->send_request_notification_by_id( $request_id );
		$admins = groups_get_group_admins( $r['group_id'] );

		/**
		 * Fires after the creation of a new membership request.
		 *
		 * @since 1.0.0
		 *
		 * @param int   $requesting_user_id  ID of the user requesting membership.
		 * @param array $admins              Array of group admins.
		 * @param int   $group_id            ID of the group being requested to.
		 * @param int   $request_id          ID of the request.
		 */
		do_action( 'groups_membership_requested', $r['user_id'], $admins, $r['group_id'], $request_id );

		return $request_id;
	}

	return false;
}

/**
 * Accept a pending group membership request.
 *
 * @since 1.0.0
 * @since 5.0.0 Deprecated $membership_id argument.
 *
 * @param int $membership_id Deprecated 5.0.0.
 * @param int $user_id       Required. ID of the user who requested membership.
 *                           Provide this value along with $group_id to override
 *                           $membership_id.
 * @param int $group_id      Required. ID of the group to which membership is being
 *                           requested. Provide this value along with $user_id to
 *                           override $membership_id.
 * @return bool True on success, false on failure.
 */
function groups_accept_membership_request( $membership_id, $user_id = 0, $group_id = 0 ) {

	if ( ! empty( $membership_id ) ) {
		/* translators: 1: the name of the method. 2: the name of the file. */
		_deprecated_argument( __METHOD__, '5.0.0', sprintf( __( 'Argument `membership_id` passed to %1$s is deprecated. See the inline documentation at %2$s for more details.', 'buddypress' ), __METHOD__, __FILE__ ) );
	}

	if ( ! $user_id || ! $group_id ) {
		return false;
	}

	$invites_class = new BP_Groups_Invitation_Manager();
	$args = array(
		'user_id' => $user_id,
		'item_id' => $group_id,
	);

	return $invites_class->accept_request( $args );
}

/**
 * Reject a pending group membership request.
 *
 * @since 1.0.0
 *
 * @param int $membership_id Deprecated 5.0.0.
 * @param int $user_id       Optional. ID of the user who requested membership.
 *                           Provide this value along with $group_id to override
 *                           $membership_id.
 * @param int $group_id      Optional. ID of the group to which membership is being
 *                           requested. Provide this value along with $user_id to
 *                           override $membership_id.
 * @return bool True on success, false on failure.
 */
function groups_reject_membership_request( $membership_id, $user_id = 0, $group_id = 0 ) {

	if ( ! empty( $membership_id ) ){
		_deprecated_argument( __METHOD__, '5.0.0', sprintf( __( 'Argument `membership_id` passed to %1$s  is deprecated. See the inline documentation at %2$s for more details.', 'buddypress' ), __METHOD__, __FILE__ ) );
	}

	if ( ! groups_delete_membership_request( false, $user_id, $group_id ) ) {
		return false;
	}

	/**
	 * Fires after a group membership request has been rejected.
	 *
	 * @since 1.0.0
	 *
	 * @param int  $user_id  ID of the user who rejected membership.
	 * @param int  $group_id ID of the group that was rejected membership to.
	 * @param bool $value    If membership was accepted.
	 */
	do_action( 'groups_membership_rejected', $user_id, $group_id, false );

	return true;
}

/**
 * Delete a pending group membership request.
 *
 * @since 1.2.0
 *
 * @param int $membership_id Deprecated 5.0.0.
 * @param int $user_id       Optional. ID of the user who requested membership.
 *                           Provide this value along with $group_id to override
 *                           $membership_id.
 * @param int $group_id      Optional. ID of the group to which membership is being
 *                           requested. Provide this value along with $user_id to
 *                           override $membership_id.
 * @return false|BP_Groups_Member True on success, false on failure.
 */
function groups_delete_membership_request( $membership_id, $user_id = 0, $group_id = 0 ) {
	if ( ! empty( $membership_id ) ){
		/* translators: 1: method name. 2: file name. */
		_deprecated_argument( __METHOD__, '5.0.0', sprintf( __( 'Argument `membership_id` passed to %1$s  is deprecated. See the inline documentation at %2$s for more details.', 'buddypress' ), __METHOD__, __FILE__ ) );
	}

	if ( empty( $user_id ) || empty( $group_id ) ) {
		return false;
	}

	$invites_class = new BP_Groups_Invitation_Manager();
	$success       = $invites_class->delete_requests( array(
		'user_id' => $user_id,
		'item_id' => $group_id
	) );

	return $success;
}

/**
 * Get group membership requests filtered by arguments.
 *
 * @since 5.0.0
 *
 * @param int   $group_id ID of the group.
 * @param array $args     Invitation arguments.
 *                        See BP_Invitation::get() for list.
 *
 * @return array $requests Matching BP_Invitation objects.
 */
function groups_get_requests( $args = array() ) {
	$invites_class = new BP_Groups_Invitation_Manager();
	return $invites_class->get_requests( $args );
}

/**
 * Check whether a user has an outstanding membership request for a given group.
 *
 * @since 1.0.0
 *
 * @param int $user_id  ID of the user.
 * @param int $group_id ID of the group.
 * @return int|bool ID of the request if found, otherwise false.
 */
function groups_check_for_membership_request( $user_id, $group_id ) {
	if ( empty( $user_id ) || empty( $group_id ) ) {
		return false;
	}

	$args = array(
		'user_id' => $user_id,
		'item_id' => $group_id,
	);
	$invites_class = new BP_Groups_Invitation_Manager();

	return $invites_class->request_exists( $args );
}

 /**
  * Get an array of group IDs to which a user has requested membership.
  *
  * @since 5.0.0
  *
  * @param int $user_id The user ID.
  *
  * @return array Array of group IDs.
  */
 function groups_get_membership_requested_group_ids( $user_id = 0 ) {
	if ( ! $user_id ) {
		$user_id = bp_loggedin_user_id();
	}

	$group_ids     = groups_get_requests( array(
		'user_id' => $user_id,
		'fields'  => 'item_ids'
	) );

	return $group_ids;
}

 /**
  * Get an array of group IDs to which a user has requested membership.
  *
  * @since 5.0.0
  *
  * @param int $user_id The user ID.
  *
  * @return array Array of group IDs.
  */
 function groups_get_membership_requested_user_ids( $group_id = 0 ) {
	if ( ! $group_id ) {
		$group_id = bp_get_current_group_id();
	}

	$requests = groups_get_requests( array(
		'item_id' => $group_id,
		'fields'  => 'user_ids'
	) );

	return $requests;
}

/**
 * Accept all pending membership requests to a group.
 *
 * @since 1.0.2
 *
 * @param int $group_id ID of the group.
 * @return bool True on success, false on failure.
 */
function groups_accept_all_pending_membership_requests( $group_id = 0 ) {
	if ( ! $group_id ) {
		$group_id = bp_get_current_group_id();
	}

	$user_ids = groups_get_membership_requested_user_ids( $group_id );

	if ( ! $user_ids ) {
		return false;
	}

	foreach ( (array) $user_ids as $user_id ) {
		groups_accept_membership_request( false, $user_id, $group_id );
	}

	/**
	 * Fires after the acceptance of all pending membership requests to a group.
	 *
	 * @since 1.0.2
	 *
	 * @param int $group_id ID of the group whose pending memberships were accepted.
	 */
	do_action( 'groups_accept_all_pending_membership_requests', $group_id );

	return true;
}

/** Group Meta ****************************************************************/

/**
 * Delete metadata for a group.
 *
 * @since 1.0.0
 *
 * @param int         $group_id   ID of the group.
 * @param string|bool $meta_key   The key of the row to delete.
 * @param string|bool $meta_value Optional. Metadata value. If specified, only delete
 *                                metadata entries with this value.
 * @param bool        $delete_all Optional. If true, delete matching metadata entries
 *                                for all groups. Otherwise, only delete matching
 *                                metadata entries for the specified group.
 *                                Default: false.
 * @return bool True on success, false on failure.
 */
function groups_delete_groupmeta( $group_id, $meta_key = false, $meta_value = false, $delete_all = false ) {
	global $wpdb;

	// Legacy - if no meta_key is passed, delete all for the item.
	if ( empty( $meta_key ) ) {
		$table_name = buddypress()->groups->table_name_groupmeta;
		$sql        = "SELECT meta_key FROM {$table_name} WHERE group_id = %d";
		$query      = $wpdb->prepare( $sql, $group_id );
		$keys       = $wpdb->get_col( $query );

		// With no meta_key, ignore $delete_all.
		$delete_all = false;
	} else {
		$keys = array( $meta_key );
	}

	add_filter( 'query', 'bp_filter_metaid_column_name' );

	$retval = true;
	foreach ( $keys as $key ) {
		$retval = delete_metadata( 'group', $group_id, $key, $meta_value, $delete_all );
	}

	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Get a piece of group metadata.
 *
 * @since 1.0.0
 *
 * @param int    $group_id ID of the group.
 * @param string $meta_key Metadata key.
 * @param bool   $single   Optional. If true, return only the first value of the
 *                         specified meta_key. This parameter has no effect if
 *                         meta_key is empty.
 * @return mixed Metadata value.
 */
function groups_get_groupmeta( $group_id, $meta_key = '', $single = true ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = get_metadata( 'group', $group_id, $meta_key, $single );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Update a piece of group metadata.
 *
 * @since 1.0.0
 *
 * @param int    $group_id   ID of the group.
 * @param string $meta_key   Metadata key.
 * @param mixed  $meta_value Value to store.
 * @param mixed  $prev_value Optional. If specified, only update existing
 *                           metadata entries with the specified value.
 *                           Otherwise, update all entries.
 * @return bool|int $retval Returns false on failure. On successful update of existing
 *                          metadata, returns true. On successful creation of new metadata,
 *                          returns the integer ID of the new metadata row.
 */
function groups_update_groupmeta( $group_id, $meta_key, $meta_value, $prev_value = '' ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = update_metadata( 'group', $group_id, $meta_key, $meta_value, $prev_value );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Add a piece of group metadata.
 *
 * @since 2.0.0
 *
 * @param int    $group_id   ID of the group.
 * @param string $meta_key   Metadata key.
 * @param mixed  $meta_value Metadata value.
 * @param bool   $unique     Optional. Whether to enforce a single metadata value
 *                           for the given key. If true, and the object already
 *                           has a value for the key, no change will be made.
 *                           Default: false.
 * @return int|bool The meta ID on successful update, false on failure.
 */
function groups_add_groupmeta( $group_id, $meta_key, $meta_value, $unique = false ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = add_metadata( 'group', $group_id, $meta_key, $meta_value, $unique );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/** Group Cleanup Functions ***************************************************/

/**
 * Delete all group membership information for the specified user.
 *
 * @since 1.0.0
 *
 * @param int $user_id ID of the user.
 */
function groups_remove_data_for_user( $user_id ) {
	BP_Groups_Member::delete_all_for_user( $user_id );

	/**
	 * Fires after the deletion of all data for a user.
	 *
	 * @since 1.1.0
	 *
	 * @param int $user_id ID of the user whose data is being deleted.
	 */
	do_action( 'groups_remove_data_for_user', $user_id );
}
add_action( 'wpmu_delete_user',  'groups_remove_data_for_user' );
add_action( 'bp_make_spam_user', 'groups_remove_data_for_user' );

/**
 * Deletes user group data on the 'delete_user' hook.
 *
 * @since 6.0.0
 *
 * @param int $user_id The ID of the deleted user.
 */
function bp_groups_remove_data_for_user_on_delete_user( $user_id ) {
	if ( ! bp_remove_user_data_on_delete_user_hook( 'groups', $user_id ) ) {
		return;
	}

	groups_remove_data_for_user( $user_id );
}
add_action( 'delete_user', 'bp_groups_remove_data_for_user_on_delete_user' );

/**
 * Update orphaned child groups when the parent is deleted.
 *
 * @since 2.7.0
 *
 * @param BP_Groups_Group $group Instance of the group item being deleted.
 */
function bp_groups_update_orphaned_groups_on_group_delete( $group ) {
	// Get child groups and set the parent to the deleted parent's parent.
	$grandparent_group_id = $group->parent_id;
	$child_args = array(
		'parent_id'         => $group->id,
		'show_hidden'       => true,
		'per_page'          => false,
		'update_meta_cache' => false,
	);
	$children = groups_get_groups( $child_args );
	$children = $children['groups'];

	foreach ( $children as $cgroup ) {
		$cgroup->parent_id = $grandparent_group_id;
		$cgroup->save();
	}
}
add_action( 'bp_groups_delete_group', 'bp_groups_update_orphaned_groups_on_group_delete', 10, 2 );

/** Group Types ***************************************************************/

/**
 * Fire the 'bp_groups_register_group_types' action.
 *
 * @since 2.6.0
 */
function bp_groups_register_group_types() {
	/**
	 * Fires when it's appropriate to register group types.
	 *
	 * @since 2.6.0
	 */
	do_action( 'bp_groups_register_group_types' );
}
add_action( 'bp_register_taxonomies', 'bp_groups_register_group_types' );

/**
 * Register a group type.
 *
 * @since 2.6.0
 * @since 2.7.0 Introduce $has_directory, $show_in_create_screen, $show_in_list, and
 *              $description, $create_screen_checked as $args parameters.
 *
 * @param string $group_type Unique string identifier for the group type.
 * @param array  $args {
 *     Array of arguments describing the group type.
 *
 *     @type string|bool $has_directory         Set the slug to be used for custom group directory page. eg.
 *                                              example.com/groups/type/MY_SLUG. Default: false.
 *     @type bool        $show_in_create_screen Whether this group type is allowed to be selected on the group creation
 *                                              page. Default: false.
 *     @type bool|null   $show_in_list          Whether this group type should be shown in lists rendered by
 *                                              bp_group_type_list(). Default: null. If $show_in_create_screen is true,
 *                                              this will default to true, unless this is set explicitly to false.
 *     @type string      $description           A short descriptive summary of what the group type is. Currently shown
 *                                              on a group's "Manage > Settings" page when selecting group types.
 *     @type bool        $create_screen_checked If $show_in_create_screen is true, whether we should have our group type
 *                                              checkbox checked by default. Handy if you want to imply that the group
 *                                              type should be enforced, but decision lies with the group creator.
 *                                              Default: false.
 *     @type array       $labels {
 *         Array of labels to use in various parts of the interface.
 *
 *         @type string $name          Default name. Should typically be plural.
 *         @type string $singular_name Singular name.
 *     }
 * }
 * @return object|WP_Error Group type object on success, WP_Error object on failure.
 */
function bp_groups_register_group_type( $group_type, $args = array() ) {
	$bp = buddypress();

	if ( isset( $bp->groups->types[ $group_type ] ) ) {
		return new WP_Error( 'bp_group_type_exists', __( 'Group type already exists.', 'buddypress' ), $group_type );
	}

	$r = bp_parse_args( $args, array(
		'has_directory'         => false,
		'show_in_create_screen' => false,
		'show_in_list'          => null,
		'description'           => '',
		'create_screen_checked' => false,
		'labels'                => array(),
	), 'register_group_type' );

	$group_type = sanitize_key( $group_type );

	/**
	 * Filters the list of illegal group type names.
	 *
	 * - 'any' is a special pseudo-type, representing items unassociated with any group type.
	 * - 'null' is a special pseudo-type, representing users without any type.
	 * - '_none' is used internally to denote an item that should not apply to any group types.
	 *
	 * @since 2.6.0
	 *
	 * @param array $illegal_names Array of illegal names.
	 */
	$illegal_names = apply_filters( 'bp_group_type_illegal_names', array( 'any', 'null', '_none' ) );
	if ( in_array( $group_type, $illegal_names, true ) ) {
		return new WP_Error( 'bp_group_type_illegal_name', __( 'You may not register a group type with this name.', 'buddypress' ), $group_type );
	}

	// Store the group type name as data in the object (not just as the array key).
	$r['name'] = $group_type;

	// Make sure the relevant labels have been filled in.
	$default_name = isset( $r['labels']['name'] ) ? $r['labels']['name'] : ucfirst( $r['name'] );
	$r['labels'] = array_merge( array(
		'name'          => $default_name,
		'singular_name' => $default_name,
	), $r['labels'] );

	// Directory slug.
	if ( ! empty( $r['has_directory'] ) ) {
		// A string value is intepreted as the directory slug.
		if ( is_string( $r['has_directory'] ) ) {
			$directory_slug = $r['has_directory'];

		// Otherwise fall back on group type.
		} else {
			$directory_slug = $group_type;
		}

		// Sanitize for use in URLs.
		$r['directory_slug'] = sanitize_title( $directory_slug );
		$r['has_directory']  = true;
	} else {
		$r['directory_slug'] = '';
		$r['has_directory']  = false;
	}

	// Type lists.
	if ( true === $r['show_in_create_screen'] && is_null( $r['show_in_list'] ) ) {
		$r['show_in_list'] = true;
	} else {
		$r['show_in_list'] = (bool) $r['show_in_list'];
	}

	$bp->groups->types[ $group_type ] = $type = (object) $r;

	/**
	 * Fires after a group type is registered.
	 *
	 * @since 2.6.0
	 *
	 * @param string $group_type Group type identifier.
	 * @param object $type       Group type object.
	 */
	do_action( 'bp_groups_register_group_type', $group_type, $type );

	return $type;
}

/**
 * Get a list of all registered group type objects.
 *
 * @since 2.6.0
 *
 * @see bp_groups_register_group_type() for accepted arguments.
 *
 * @param array|string $args     Optional. An array of key => value arguments to match against
 *                               the group type objects. Default empty array.
 * @param string       $output   Optional. The type of output to return. Accepts 'names'
 *                               or 'objects'. Default 'names'.
 * @param string       $operator Optional. The logical operation to perform. 'or' means only one
 *                               element from the array needs to match; 'and' means all elements
 *                               must match. Accepts 'or' or 'and'. Default 'and'.
 * @return array       $types    A list of groups type names or objects.
 */
function bp_groups_get_group_types( $args = array(), $output = 'names', $operator = 'and' ) {
	$types = buddypress()->groups->types;

	$types = wp_filter_object_list( $types, $args, $operator );

	/**
	 * Filters the array of group type objects.
	 *
	 * This filter is run before the $output filter has been applied, so that
	 * filtering functions have access to the entire group type objects.
	 *
	 * @since 2.6.0
	 *
	 * @param array  $types     group type objects, keyed by name.
	 * @param array  $args      Array of key=>value arguments for filtering.
	 * @param string $operator  'or' to match any of $args, 'and' to require all.
	 */
	$types = apply_filters( 'bp_groups_get_group_types', $types, $args, $operator );

	if ( 'names' === $output ) {
		$types = wp_list_pluck( $types, 'name' );
	}

	return $types;
}

/**
 * Retrieve a group type object by name.
 *
 * @since 2.6.0
 *
 * @param string $group_type The name of the group type.
 * @return object A group type object.
 */
function bp_groups_get_group_type_object( $group_type ) {
	$types = bp_groups_get_group_types( array(), 'objects' );

	if ( empty( $types[ $group_type ] ) ) {
		return null;
	}

	return $types[ $group_type ];
}

/**
 * Set type for a group.
 *
 * @since 2.6.0
 * @since 2.7.0 $group_type parameter also accepts an array of group types now.
 *
 * @param int          $group_id   ID of the group.
 * @param string|array $group_type Group type or array of group types to set.
 * @param bool         $append     Optional. True to append this to existing types for group,
 *                                 false to replace. Default: false.
 * @return false|array $retval See bp_set_object_terms().
 */
function bp_groups_set_group_type( $group_id, $group_type, $append = false ) {
	// Pass an empty group type to remove group's type.
	if ( ! empty( $group_type ) && is_string( $group_type ) && ! bp_groups_get_group_type_object( $group_type ) ) {
		return false;
	}

	// Cast as array.
	$group_type = (array) $group_type;

	// Validate group types.
	foreach ( $group_type as $type ) {
		// Remove any invalid group types.
		if ( is_null( bp_groups_get_group_type_object( $type ) ) ) {
			unset( $group_type[ $type ] );
		}
	}

	$retval = bp_set_object_terms( $group_id, $group_type, 'bp_group_type', $append );

	// Bust the cache if the type has been updated.
	if ( ! is_wp_error( $retval ) ) {
		wp_cache_delete( $group_id, 'bp_groups_group_type' );

		/**
		 * Fires just after a group type has been changed.
		 *
		 * @since 2.6.0
		 *
		 * @param int          $group_id   ID of the group whose group type has been updated.
		 * @param string|array $group_type Group type or array of group types.
		 * @param bool         $append     Whether the type is being appended to existing types.
		 */
		do_action( 'bp_groups_set_group_type', $group_id, $group_type, $append );
	}

	return $retval;
}

/**
 * Get type for a group.
 *
 * @since 2.6.0
 *
 * @param int  $group_id ID of the group.
 * @param bool $single   Optional. Whether to return a single type string. If multiple types are found
 *                       for the group, the oldest one will be returned. Default: true.
 * @return string|array|bool On success, returns a single group type (if `$single` is true) or an array of group
 *                           types (if `$single` is false). Returns false on failure.
 */
function bp_groups_get_group_type( $group_id, $single = true ) {
	$types = wp_cache_get( $group_id, 'bp_groups_group_type' );

	if ( false === $types ) {
		$raw_types = bp_get_object_terms( $group_id, 'bp_group_type' );

		if ( ! is_wp_error( $raw_types ) ) {
			$types = array();

			// Only include currently registered group types.
			foreach ( $raw_types as $gtype ) {
				if ( bp_groups_get_group_type_object( $gtype->name ) ) {
					$types[] = $gtype->name;
				}
			}

			wp_cache_set( $group_id, $types, 'bp_groups_group_type' );
		}
	}

	$type = false;
	if ( ! empty( $types ) ) {
		if ( $single ) {
			$type = end( $types );
		} else {
			$type = $types;
		}
	}

	/**
	 * Filters a groups's group type(s).
	 *
	 * @since 2.6.0
	 *
	 * @param string|array $type     Group type.
	 * @param int          $group_id ID of the group.
	 * @param bool         $single   Whether to return a single type string, or an array.
	 */
	return apply_filters( 'bp_groups_get_group_type', $type, $group_id, $single );
}

/**
 * Remove type for a group.
 *
 * @since 2.6.0
 *
 * @param int            $group_id   ID of the user.
 * @param string         $group_type Group type.
 * @return bool|WP_Error $deleted    True on success. False or WP_Error on failure.
 */
function bp_groups_remove_group_type( $group_id, $group_type ) {
	if ( empty( $group_type ) || ! bp_groups_get_group_type_object( $group_type ) ) {
		return false;
	}

	// No need to continue if the group doesn't have the type.
	$existing_types = bp_groups_get_group_type( $group_id, false );
	if ( ! in_array( $group_type, $existing_types, true ) ) {
		return false;
	}

	$deleted = bp_remove_object_terms( $group_id, $group_type, 'bp_group_type' );

	// Bust the case, if the type has been removed.
	if ( ! is_wp_error( $deleted ) ) {
		wp_cache_delete( $group_id, 'bp_groups_group_type' );

		/**
		 * Fires just after a group's group type has been removed.
		 *
		 * @since 2.6.0
		 *
		 * @param int    $group      ID of the group whose group type has been removed.
		 * @param string $group_type Group type.
		 */
		do_action( 'bp_groups_remove_group_type', $group_id, $group_type );
	}

	return $deleted;
}

/**
 * Check whether the given group has a certain group type.
 *
 * @since 2.6.0
 *
 * @param  int    $group_id   ID of the group.
 * @param  string $group_type Group type.
 * @return bool   Whether the group has the give group type.
 */
function bp_groups_has_group_type( $group_id, $group_type ) {
	if ( empty( $group_type ) || ! bp_groups_get_group_type_object( $group_type ) ) {
		return false;
	}

	// Get all group's group types.
	$types = bp_groups_get_group_type( $group_id, false );

	if ( ! is_array( $types ) ) {
		return false;
	}

	return in_array( $group_type, $types );
}

/**
 * Get the "current" group type, if one is provided, in group directories.
 *
 * @since 2.7.0
 *
 * @return string
 */
function bp_get_current_group_directory_type() {

	/**
	 * Filters the "current" group type, if one is provided, in group directories.
	 *
	 * @since 2.7.0
	 *
	 * @param string $value "Current" group type.
	 */
	return apply_filters( 'bp_get_current_group_directory_type', buddypress()->groups->current_directory_type );
}

/**
 * Delete a group's type when the group is deleted.
 *
 * @since 2.6.0
 *
 * @param  int   $group_id ID of the group.
 * @return array|null $value    See {@see bp_groups_set_group_type()}.
 */
function bp_remove_group_type_on_group_delete( $group_id = 0 ) {
	bp_groups_set_group_type( $group_id, '' );
}
add_action( 'groups_delete_group', 'bp_remove_group_type_on_group_delete' );

/**
 * Finds and exports group membership data associated with an email address.
 *
 * @since 4.0.0
 *
 * @param string $email_address  The user's email address.
 * @param int    $page           Batch number.
 * @return array An array of personal data.
 */
function bp_groups_memberships_personal_data_exporter( $email_address, $page ) {
	$number = 20;

	$email_address = trim( $email_address );

	$data_to_export = array();

	$user = get_user_by( 'email', $email_address );

	if ( ! $user ) {
		return array(
			'data' => array(),
			'done' => true,
		);
	}

	$memberships = BP_Groups_Member::get_user_memberships( $user->ID, array(
		'type'     => 'membership',
		'page'     => $page,
		'per_page' => $number,
	) );

	foreach ( $memberships as $membership ) {
		$group = groups_get_group( $membership->group_id );

		$item_data = array(
			array(
				'name'  => __( 'Group Name', 'buddypress' ),
				'value' => bp_get_group_name( $group ),
			),
			array(
				'name'  => __( 'Group URL', 'buddypress' ),
				'value' => bp_get_group_permalink( $group ),
			),
		);

		if ( $membership->inviter_id ) {
			$item_data[] = array(
				'name'  => __( 'Invited By', 'buddypress' ),
				'value' => bp_core_get_userlink( $membership->inviter_id ),
			);
		}

		if ( $group->creator_id === $user->ID ) {
			$group_role = __( 'Creator', 'buddypress' );
		} elseif ( $membership->is_admin ) {
			$group_role = __( 'Admin', 'buddypress' );
		} elseif ( $membership->is_mod ) {
			$group_role = __( 'Moderator', 'buddypress' );
		} else {
			$group_role = __( 'Member', 'buddypress' );
		}

		$item_data[] = array(
			'name'  => __( 'Group Role', 'buddypress' ),
			'value' => $group_role,
		);

		$item_data[] = array(
			'name'  => __( 'Date Joined', 'buddypress' ),
			'value' => $membership->date_modified,
		);

		$data_to_export[] = array(
			'group_id'    => 'bp_groups_memberships',
			'group_label' => __( 'Group Memberships', 'buddypress' ),
			'item_id'     => "bp-group-membership-{$group->id}",
			'data'        => $item_data,
		);
	}

	// Tell core if we have more items to process.
	$done = count( $memberships ) < $number;

	return array(
		'data' => $data_to_export,
		'done' => $done,
	);
}

/**
 * Finds and exports data on pending group membership requests associated with an email address.
 *
 * @since 4.0.0
 *
 * @param string $email_address  The user's email address.
 * @param int    $page           Batch number.
 * @return array An array of personal data.
 */
function bp_groups_pending_requests_personal_data_exporter( $email_address, $page ) {
	$number = 20;

	$email_address = trim( $email_address );

	$data_to_export = array();

	$user = get_user_by( 'email', $email_address );

	if ( ! $user ) {
		return array(
			'data' => array(),
			'done' => true,
		);
	}

	$requests = groups_get_requests( array(
		'user_id'  => $user->ID,
		'page'     => $page,
		'per_page' => $number,
	) );

	foreach ( $requests as $request ) {
		$group = groups_get_group( $request->item_id );

		$item_data = array(
			array(
				'name'  => __( 'Group Name', 'buddypress' ),
				'value' => bp_get_group_name( $group ),
			),
			array(
				'name'  => __( 'Group URL', 'buddypress' ),
				'value' => bp_get_group_permalink( $group ),
			),
			array(
				'name'  => __( 'Date Sent', 'buddypress' ),
				'value' => $request->date_modified,
			),
		);

		$data_to_export[] = array(
			'group_id'    => 'bp_groups_pending_requests',
			'group_label' => __( 'Pending Group Membership Requests', 'buddypress' ),
			'item_id'     => "bp-group-pending-request-{$group->id}",
			'data'        => $item_data,
		);
	}

	// Tell core if we have more items to process.
	$done = count( $requests ) < $number;

	return array(
		'data' => $data_to_export,
		'done' => $done,
	);
}

/**
 * Finds and exports data on pending group invitations sent by a user associated with an email address.
 *
 * @since 4.0.0
 *
 * @param string $email_address  The user's email address.
 * @param int    $page           Batch number.
 * @return array An array of personal data.
 */
function bp_groups_pending_sent_invitations_personal_data_exporter( $email_address, $page ) {
	$number = 20;

	$email_address = trim( $email_address );

	$data_to_export = array();

	$user = get_user_by( 'email', $email_address );

	if ( ! $user ) {
		return array(
			'data' => array(),
			'done' => true,
		);
	}

	$invitations = groups_get_invites( array(
		'inviter_id'  => $user->ID,
		'page'        => $page,
		'per_page'    => $number,
	) );

	foreach ( $invitations as $invitation ) {
		$group = groups_get_group( $invitation->item_id );

		$item_data = array(
			array(
				'name'  => __( 'Group Name', 'buddypress' ),
				'value' => bp_get_group_name( $group ),
			),
			array(
				'name'  => __( 'Group URL', 'buddypress' ),
				'value' => bp_get_group_permalink( $group ),
			),
			array(
				'name'  => __( 'Sent To', 'buddypress' ),
				'value' => bp_core_get_userlink( $invitation->user_id ),
			),
			array(
				'name'  => __( 'Date Sent', 'buddypress' ),
				'value' => $invitation->date_modified,
			),
		);

		$data_to_export[] = array(
			'group_id'    => 'bp_groups_pending_sent_invitations',
			'group_label' => __( 'Pending Group Invitations (Sent)', 'buddypress' ),
			'item_id'     => "bp-group-pending-sent-invitation-{$group->id}",
			'data'        => $item_data,
		);
	}

	// Tell core if we have more items to process.
	$done = count( $invitations ) < $number;

	return array(
		'data' => $data_to_export,
		'done' => $done,
	);
}

/**
 * Finds and exports data on pending group invitations received by a user associated with an email address.
 *
 * @since 4.0.0
 *
 * @param string $email_address  The user's email address.
 * @param int    $page           Batch number.
 * @return array An array of personal data.
 */
function bp_groups_pending_received_invitations_personal_data_exporter( $email_address, $page ) {
	$number = 20;

	$email_address = trim( $email_address );

	$data_to_export = array();

	$user = get_user_by( 'email', $email_address );

	if ( ! $user ) {
		return array(
			'data' => array(),
			'done' => true,
		);
	}

	$invitations = groups_get_invites( array(
		'user_id'  => $user->ID,
		'page'     => $page,
		'per_page' => $number,
	) );

	foreach ( $invitations as $invitation ) {
		$group = groups_get_group( $invitation->item_id );

		$item_data = array(
			array(
				'name'  => __( 'Group Name', 'buddypress' ),
				'value' => bp_get_group_name( $group ),
			),
			array(
				'name'  => __( 'Group URL', 'buddypress' ),
				'value' => bp_get_group_permalink( $group ),
			),
			array(
				'name'  => __( 'Invited By', 'buddypress' ),
				'value' => bp_core_get_userlink( $invitation->inviter_id ),
			),
			array(
				'name'  => __( 'Date Sent', 'buddypress' ),
				'value' => $invitation->date_modified,
			),
		);

		$data_to_export[] = array(
			'group_id'    => 'bp_groups_pending_received_invitations',
			'group_label' => __( 'Pending Group Invitations (Received)', 'buddypress' ),
			'item_id'     => "bp-group-pending-received-invitation-{$group->id}",
			'data'        => $item_data,
		);
	}

	// Tell core if we have more items to process.
	$done = count( $invitations ) < $number;

	return array(
		'data' => $data_to_export,
		'done' => $done,
	);
}

/**
 * Migrate invitations and requests from pre-5.0 group_members table to invitations table.
 *
 * @since 5.0.0
 */
function bp_groups_migrate_invitations() {
	global $wpdb;
	$bp = buddypress();

	$records = $wpdb->get_results( "SELECT id, group_id, user_id, inviter_id, date_modified, comments, invite_sent FROM {$bp->groups->table_name_members} WHERE is_confirmed = 0 AND is_banned = 0" );
	if ( empty( $records ) ) {
		return;
	}

	$processed = array();
	$values = array();
	foreach ( $records as $record ) {
		$values[] = $wpdb->prepare(
			"(%d, %d, %s, %s, %d, %d, %s, %s, %s, %d, %d)",
			(int) $record->user_id,
			(int) $record->inviter_id,
			'',
			'bp_groups_invitation_manager',
			(int) $record->group_id,
			0,
			( 0 === (int) $record->inviter_id ) ? 'request' : 'invite',
			$record->comments,
			$record->date_modified,
			(int) $record->invite_sent,
			0
		);
		$processed[] = (int) $record->id;
	}

	$table_name = BP_Invitation_Manager::get_table_name();
	$query = "INSERT INTO {$table_name} (user_id, inviter_id, invitee_email, class, item_id, secondary_item_id, type, content, date_modified, invite_sent, accepted) VALUES ";
	$query .= implode(', ', $values );
	$query .= ';';
	$wpdb->query( $query );

	$ids_to_delete = implode( ',', $processed );
	if ( $ids_to_delete ) {
		$wpdb->query( "DELETE FROM {$bp->groups->table_name_members} WHERE ID IN ($ids_to_delete)" );
	}
}
