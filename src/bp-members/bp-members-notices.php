<?php
/**
 * BuddyPress Notices functions.
 *
 * @package buddypress\bp-members\bp-members-notices
 * @since 15.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Migrates the "closed_notices" user meta as "dismissed_by" notice meta.
 *
 * @since 15.0.0
 *
 * @access private
 */
function _bp_members_dismissed_notices_migrate() {
	global $wpdb;

	/**
	 * Filters the notices migration batch size.
	 *
	 * @since 15.0.0
	 *
	 * @param int $comment_batch_size The notices migration batch size. Default 100.
	 */
	$batch_size = (int) apply_filters( 'bp_migrate_notices_batch_size', 100 );

	$members_dismissed_notices = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT user_id, meta_value as notices FROM {$wpdb->usermeta} WHERE meta_key = 'closed_notices' LIMIT %d",
			$batch_size
		)
	);

	if ( $members_dismissed_notices ) {
		foreach ( $members_dismissed_notices as $member ) {
			$notice_ids = wp_parse_id_list( (array) maybe_unserialize( $member->notices ) );
			bp_delete_user_meta( $member->user_id, 'closed_notices' );

			foreach ( $notice_ids as $notice_id ) {
				bp_notices_add_meta( $notice_id, 'dismissed_by', $member->user_id );
			}
		}

		wp_schedule_single_event( time() + ( 2 * MINUTE_IN_SECONDS ), 'bp_usermeta_closed_notices_migrate_batch' );
	}
}
add_action( 'bp_usermeta_closed_notices_migrate_batch', '_bp_members_dismissed_notices_migrate', 10, 0 );

// Load the Members Notices Admin.
add_action( bp_core_admin_hook(), array( 'BP_Members_Notices_Admin', 'register_notices_admin' ), 9 );

/**
 * Get metadata for a given notice item.
 *
 * @since 15.0.0
 *
 * @param int    $notice_id ID of the notice item whose metadata is being requested.
 * @param string $meta_key  Optional. If present, only the metadata matching
 *                          that meta key will be returned. Otherwise, all metadata for the
 *                          notice item will be fetched.
 * @param bool   $single    Optional. If true, return only the first value of the
 *                          specified meta_key. This parameter has no effect if meta_key is not
 *                          specified. Default: false.
 * @return mixed            The meta value(s) being requested.
 */
function bp_notices_get_meta( $notice_id = 0, $meta_key = '', $single = false ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = get_metadata( 'notice', $notice_id, $meta_key, $single );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	/**
	 * Filters the metadata for a specified notice item.
	 *
	 * @since 15.0.0
	 *
	 * @param mixed  $retval    The meta values for the notice item.
	 * @param int    $notice_id ID of the noticce item.
	 * @param string $meta_key  Meta key for the value being requested.
	 * @param bool   $single    Whether to return one matched meta key row or all.
	 */
	return apply_filters( 'bp_notices_get_meta', $retval, $notice_id, $meta_key, $single );
}

/**
 * Update a piece of notice meta.
 *
 * @since 15.0.0
 *
 * @param  int    $notice_id ID of the notice item whose metadata is being
 *                                 updated.
 * @param  string $meta_key        Key of the metadata being updated.
 * @param  mixed  $meta_value      Value to be set.
 * @param  mixed  $prev_value      Optional. If specified, only update existing
 *                                 metadata entries with the specified value.
 *                                 Otherwise, update all entries.
 * @return bool|int                Returns false on failure. On successful
 *                                 update of existing metadata, returns true. On
 *                                 successful creation of new metadata,  returns
 *                                 the integer ID of the new metadata row.
 */
function bp_notices_update_meta( $notice_id, $meta_key, $meta_value, $prev_value = '' ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = update_metadata( 'notice', $notice_id, $meta_key, $meta_value, $prev_value );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Add a piece of notice metadata.
 *
 * @since 15.0.0
 *
 * @param int    $notice_id ID of the notice item.
 * @param string $meta_key        Metadata key.
 * @param mixed  $meta_value      Metadata value.
 * @param bool   $unique          Optional. Whether to enforce a single metadata value
 *                                for the given key. If true, and the object already has a value for
 *                                the key, no change will be made. Default: false.
 * @return int|bool               The meta ID on successful update, false on failure.
 */
function bp_notices_add_meta( $notice_id, $meta_key, $meta_value, $unique = false ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = add_metadata( 'notice', $notice_id, $meta_key, $meta_value, $unique );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Delete a piece of notice metadata.
 *
 * @since 15.0.0
 *
 * @param int    $notice_id ID of the notice item.
 * @param string $meta_key        Metadata key.
 * @param mixed  $meta_value      Metadata value.
 * @param bool   $delete_all      If true, delete matching metadata entries for all objects, ignoring the specified object_id.
 *                                Otherwise, only delete matching metadata entries for the specified object_id. Default: false.
 * @return bool                   True on successful update, false on failure or partial success.
 */
function bp_notices_delete_meta( $notice_id, $meta_key = '', $meta_value = '', $delete_all = false ) {
	if ( empty( $meta_key ) ) {
		global $wpdb;

		$table_name = buddypress()->members->table_name_notices_meta;
		$sql        = "SELECT meta_key FROM {$table_name} WHERE notice_id = %d";
		$query      = $wpdb->prepare( $sql, $notice_id );
		$keys       = $wpdb->get_col( $query );

		// With no meta_key, ignore $delete_all.
		$delete_all = false;
	} else {
		$keys = array( $meta_key );
	}

	add_filter( 'query', 'bp_filter_metaid_column_name' );

	$results = array();
	foreach ( $keys as $key ) {
		$results[] = delete_metadata( 'notice', $notice_id, $key, $meta_value, $delete_all );
	}
	$result = array_filter( $results );

	remove_filter( 'query', 'bp_filter_metaid_column_name' );


	return count( $keys ) === count( $result );
}

/**
 * Allowed HTML tags for Notices content.
 *
 * @since 15.0.0
 *
 * @return array The allowed HTML tags for Notices content.
 */
function bp_notice_get_allowed_tags() {
	$allowedtags      = bp_get_allowedtags();
	$allowedtags['p'] = array();

	return $allowedtags;
}

/**
 * Custom kses filtering for Notices content.
 *
 * @since 15.0.0
 *
 * @param string $content The notice content.
 * @return string         The filtered notice content.
 */
function bp_members_notice_filter_kses( $content ) {

	/**
	 * Filters the allowed HTML tags for BuddyPress Notice content.
	 *
	 * @since 15.0.0
	 *
	 * @param array $allowedtags Array of allowed HTML tags and attributes.
	 */
	$allowedtags = apply_filters( 'bp_members_notice_allowed_tags', bp_notice_get_allowed_tags() );
	return wp_kses( $content, $allowedtags );
}

/**
 * Creates a new notice or updates an existing one.
 *
 * @since 15.0.0
 *
 * @param array $args {
 *     Array of parameters.
 *     @type integer $id       The ID of the notice to update. Optional. Defaults to `0`.
 *     @type string  $title    The subject of the notice. Required. Defaults to ''.
 *     @type string  $content  The content to be noticed. Required. Defaults to ''.
 *     @type string  $target   The targeted audience. Optional. Defaults to "community". Possible values:
 *                             - 'community': all members will be noticed.
 *                             - 'contributors': users having a publishing role/cap will be noticed.
 *                             - 'admins': administrators will be noticed.
 *     @type integer $priority The notice priority. Optional. Defaults to `2`. Possible values:
 *                             - `0` is restriced to BuddyPress to inform Site Admins of
 *                               major plugin changes: please do not use it.
 *                             - `1` is the highest priority.
 *                             - `2` is the regular priority.
 *                             - `3` is the lowest priority.
 *                             - `127` is used to deactivate a notice.
 *     @type string  $date     A date string of the format 'Y-m-d h:i:s'. Optional. Defaults to ''.
 *     @type string  $url      The URL of the notice action button. Optional. Defaults to ''.
 *     @type string  $text     The text of the notice action button. Optional. Defaults to ''.
 *     @type array   $meta     An array of key-value pairs. Optional. Defaults to ''.
 * }
 * @return integer|WP_Error The notice ID on success, a WP Error on failure.
 */
function bp_members_save_notice( $args = array() ) {

	$attrs = array();
	$r     = bp_parse_args(
		$args,
		array(
			'id'       => 0,
			'title'    => '',
			'content'  => '',
			'target'   => 'community',
			'priority' => 2,
			'date'     => '',
			'url'     => '',
			'text'     => '',
			'meta'     => array(),
		)
	);

	if ( ! $r['title'] || ! $r['content'] ) {
		return new WP_Error( 'bp_notices_missing_data', __( 'The notice subject and content are required fields.', 'buddypress' ) );
	}

	// Sanitize data.
	$title   = sanitize_text_field( $r['title'] );
	$content = bp_members_notice_filter_kses( $r['content'] );
	$target  = 'community';
	$id      = (int) $r['id'];
	$date    = bp_core_current_time();

	if ( $r['date'] && preg_match( '/^\d{4}-\d{2}-\d{2}[ ]\d{2}:\d{2}:\d{2}$/', $r['date'] ) ) {
		$date = sanitize_text_field( $r['date'] );
	}

	if ( in_array( $r['target'], array( 'community', 'admins', 'contributors' ), true ) ) {
		$target = $r['target'];
	}

	if ( $r['url'] ) {
		$attrs['url'] = sanitize_url( $r['url'] );
	}

	if ( $r['text'] ) {
		$attrs['text'] = sanitize_text_field( $r['text'] );
	}

	if ( $r['meta'] && is_array( $r['meta'] ) && ! wp_is_numeric_array( $r['meta'] ) ) {
		$sanitized_meta = array();

		foreach ( $r['meta'] as $key_meta => $meta_value ) {
			if ( ! is_string( $meta_value ) && ! is_numeric( $meta_value ) ) {
				continue;
			}

			$sanitized_key                    = sanitize_key( $key_meta );
			$sanitized_meta[ $sanitized_key ] = sanitize_text_field( $meta_value );
		}

		if ( $sanitized_meta ) {
			$attrs['meta'] = $sanitized_meta;
		}
	}

	// Use the block grammar to save content.
	$message = serialize_block(
		array(
			'blockName'    => 'bp/member-notice',
			'innerContent' => array( $content ),
			'attrs'        => $attrs,
		)
	);

	$previous_notice = null;
	$notice          = new BP_Members_Notice( $id );

	if ( ! empty( $notice->id ) ) {
		$previous_notice = clone $notice;
	}

	// Set the new notice or existing notice new properties.
	$notice->subject   = $title;
	$notice->message   = $message;
	$notice->target    = $target;
	$notice->date_sent = $date;
	$notice->priority  = (int) $r['priority'];

	// Create or update it.
	$notice_id = $notice->save();

	/**
	 * Fires after a notice has been successfully sent.
	 *
	 * Please stop using this hook.
	 *
	 * @since 1.0.0
	 * @deprecated 15.0.0
	 *
	 * @param string            $title   Title of the notice.
	 * @param string            $content Content of the notice.
	 * @param BP_Members_Notice $notice  Notice object sent.
	 */
	do_action_deprecated( 'messages_send_notice', array( $title, $content, $notice ), '15.0.0', 'bp_members_notice_saved' );

	if ( ! is_wp_error( $notice_id ) ) {
		$saved_values = get_object_vars( $notice );

		/**
		 * Fires after a notice has been successfully added to the sending queue.
		 *
		 * @since 15.0.0
		 *
		 * @param integer                $notice_id       The notice ID.
		 * @param array                  $saved_values    The list of the saved values keyed by object properties.
		 * @param BP_Members_Notice|null $previous_notice The previous version of the notice when updating. Null otherwise.
		 */
		do_action( 'bp_members_notice_saved', $notice_id, $saved_values, $previous_notice );
	}

	return $notice_id;
}

/**
 * Dismiss a sitewide notice for a user.
 *
 * @since 15.0.0
 *
 * @param int     $user_id     ID of the user to dismiss the notice for. Defaults to the logged-in user.
 * @param int     $notice_id   ID of the notice to be dismissed.
 * @param boolean $return_bool Whether to force a boolean return or not. Defaults to `false`.
 * @return WP_Error|bool False or a WP Error object on failure, true if notice is dismissed
 *                       (or was already dismissed).
 */
function bp_members_dismiss_notice( $user_id = 0, $notice_id = 0, $return_bool = false ) {
	$dismissed = false;

	if ( ! $user_id ) {
		$user_id = bp_loggedin_user_id();
	}

	// Bail if no user is set.
	if ( ! $user_id ) {
		$dismissed = new WP_Error(
			'notice_dismiss_missing_user',
			__( 'No user was provided for the notice to dismiss.', 'buddypress' )
		);

		return $return_bool ? false : $dismissed;
	}

	$notice = bp_members_get_notice( $notice_id );

	// Bail if no notice is set.
	if ( is_null( $notice ) ) {
		$dismissed = new WP_Error(
			'notice_dismiss_missing_notice',
			__( 'The notice to dismiss does not exist.', 'buddypress' )
		);

		return $return_bool ? false : $dismissed;
	}

	$dismissed_notices = (array) bp_members_get_dismissed_notices_for_user( $user_id );
	if ( in_array( $notice->id, $dismissed_notices, true ) ) {
		return true;
	}

	$dismissed = (bool) bp_notices_add_meta( $notice->id, 'dismissed_by', $user_id );
	if ( ! $dismissed ) {
		$dismissed = new WP_Error(
			'notice_dismiss_failed',
			__( 'The notice could not be dismissed.', 'buddypress' )
		);

		return $return_bool ? false : $dismissed;
	}

	return $dismissed;
}

/**
 * Handle editing of sitewide notices.
 *
 * @since 2.4.0 This function was split from messages_screen_notices(). See #6505.
 *
 * @return bool
 */
function bp_members_edit_notice() {

	/**
	 *
	 *
	 *
	 *
	 * @todo check to see is we still need this code.
	 *
	 */
	return false;

	// Get the notice ID (1|2|3).
	$notice_id = bp_action_variable( 1 );

	// Bail if notice ID is not numeric.
	if ( empty( $notice_id ) || ! is_numeric( $notice_id ) ) {
		return false;
	}

	// Bail if the current user doesn't have administrator privileges.
	if ( ! bp_current_user_can( 'bp_moderate' ) ) {
		return false;
	}

	// Get the action (deactivate|activate|delete).
	$action = sanitize_key( bp_action_variable( 0 ) );

	// Check the nonce.
	check_admin_referer( "messages_{$action}_notice" );

	// Get the notice from database.
	$notice   = new BP_Members_Notice( $notice_id );
	$success  = false;
	$feedback = '';

	// Take action.
	switch ( $action ) {

		// Deactivate.
		case 'deactivate' :
			$success  = $notice->deactivate();
			$feedback = true === $success
				? __( 'Notice deactivated successfully.',              'buddypress' )
				: __( 'There was a problem deactivating that notice.', 'buddypress' );
			break;

		// Activate.
		case 'activate' :
			$success  = $notice->activate();
			$feedback = true === $success
				? __( 'Notice activated successfully.',              'buddypress' )
				: __( 'There was a problem activating that notice.', 'buddypress' );
			break;

		// Delete.
		case 'delete' :
			$success  = $notice->delete();
			$feedback = true === $success
				? __( 'Notice deleted successfully.',              'buddypress' )
				: __( 'There was a problem deleting that notice.', 'buddypress' );
			break;
	}

	// Feedback.
	if ( ! empty( $feedback ) ) {

		// Determine message type.
		$type = ( true === $success )
			? 'success'
			: 'error';

		// Add feedback message.
		bp_core_add_message( $feedback, $type );
	}

	// Redirect.
	$redirect_to = bp_loggedin_user_url( bp_members_get_path_chunks( array( bp_get_messages_slug(), 'notices' ) ) );

	bp_core_redirect( $redirect_to );
}

/**
 * Returns Members Notices default query arguments.
 *
 * PS: avoids code duplication into `bp_members_get_notices()` & `BP_Members_Notice::get()`.
 *
 * @since 15.0.0
 *
 * @return array The Members Notices default query arguments.
 */
function bp_members_notices_default_query_args() {
	return array(
		'user_id'          => 0,
		'pag_num'          => 20,
		'pag_page'         => 1,
		'dismissed'        => false,
		'meta_query'       => array(),
		'fields'           => 'all',
		'type'             => 'active',
		'exclude'          => array(),
		'target__in'       => array(),
		'priority'         => null,
		'count_total_only' => false,
	);
}

/**
 * Get the total number of notices according to requested arguments.
 *
 * @since 15.0.0
 *
 * @param array $args See `BP_Memners_Notice->get()` for description.
 * @return integer The total number of notices for the query arguments.
 */
function bp_members_get_notices_count( $args = array() ) {
	return BP_Members_Notice::get_total_notice_count( $args );
}

/**
 * Get Member notices according to requested arguments.
 *
 * @since 15.0.0
 *
 * @param array $args See `BP_Memners_Notice->get()` for description.
 * @return array The list of notices matching the query arguments.
 */
function bp_members_get_notices( $args = array() ) {
	/**
	 * 2 dynamic filters you can use to edit args are included as the 3rd parameter.
	 *
	 * - Use 'bp_before_members_get_notices_parse_args' to passively filter the args before the parse.
	 * - Use 'bp_after_members_get_notices_parse_args' to aggressively filter the args after the parse.
	 *
	 * @since 15.0.0
	 *
	 * @param array $args See `BP_Memners_Notice->get()` for description.
	 */
	$r = bp_parse_args(
		$args,
		bp_members_notices_default_query_args(),
		'members_get_notices'
	);

	return BP_Members_Notice::get( $r );
}

/**
 * Gets a notice object for the requested ID.
 *
 * @since 15.0.0
 *
 * @param integer $notice_id The Notice ID.
 * @return BP_Members_Notice|null The Notice object. Null if not found.
 */
function bp_members_get_notice( $notice_id = 0 ) {
	if ( ! $notice_id ) {
		return null;
	}

	$notice = new BP_Members_Notice( $notice_id );
	if ( ! $notice->id ) {
		return null;
	}

	/**
	 * Filter here to edit the returned notice.
	 *
	 * @since 15.0.0
	 *
	 * @param BP_Members_Notice|null $notice    The Notice object. Null if not found.
	 * @param integer                $notice_id The Notice ID.
	 */
	return apply_filters( 'bp_members_get_notice', $notice, $notice_id );
}

/**
 * Get the list of Notice IDs the user has dismissed.
 *
 * @since 15.0.0
 *
 * @param integer $user_id The user ID to get the notices for.
 * @return array The list of Notice IDs the user has dismissed.
 */
function bp_members_get_dismissed_notices_for_user( $user_id ) {
	return BP_Members_Notice::get(
		array(
			'user_id'   => $user_id,
			'dismissed' => true,
			'fields'    => 'ids',
		)
	);
}

/**
 * Get the user's higher priority notice according to the requested page.
 *
 * @since 15.0.0
 *
 * @param integer $user_id The user ID to get the notice for.
 * @param integer $page    The page number to get.
 * @return array The higher priority notice & the notices total count.
 */
function bp_members_get_notices_for_user( $user_id, $page = 1 ) {
	$notice            = null;
	$dismissed_notices = bp_members_get_dismissed_notices_for_user( $user_id );

	// Get notices orderered by priority.
	$notices = BP_Members_Notice::get(
		array(
			'user_id'  => $user_id,
			'pag_num'  => 1,
			'pag_page' => $page,
			'exclude'  => $dismissed_notices,
		)
	);

	// Get Total number of notices.
	$notices_count = bp_members_get_notices_count(
		array(
			'user_id'  => $user_id,
			'exclude'  => $dismissed_notices,
		)
	);

	$notice_item = reset( $notices );
	if ( ! empty( $notice_item->id ) ) {
		$notice = new BP_Members_Notice();
		$props  = array_keys( get_object_vars( $notice ) );

		foreach ( $notice_item as $prop => $value ) {
			if ( ! in_array( $prop, $props, true ) ) {
				continue;
			}

			$notice->{$prop} = $value;
		}
	}

	return array( 'item' => $notice, 'count' => (int) $notices_count );
}

/**
 * Output the ID of a notice.
 *
 * @since 15.0.0
 *
 * @param BP_Members_Notice|null $notice The notice object.
 */
function bp_notice_id( $notice = null ) {
	echo esc_attr( bp_get_notice_id( $notice ) );
}

/**
 * Get the ID of a notice.
 *
 * @since 15.0.0
 *
 * @param BP_Members_Notice|null $notice The notice object.
 * @return integer The notice ID.
 */
function bp_get_notice_id( $notice = null ) {
	$notice_id = 0;

	if ( ! empty( $notice->id ) ) {
		$notice_id = $notice->id;
	}

	return apply_filters( 'bp_get_notice_id', $notice_id );
}

/**
 * Output the title of a notice.
 *
 * @since 15.0.0
 *
 * @param BP_Members_Notice|null $notice The notice object.
 */
function bp_notice_title( $notice = null ) {
	// Escaping is made in `bp-members/bp-members-filters.php`.
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_notice_title( $notice );
}

/**
 * Get the title of a notice.
 *
 * @since 15.0.0
 *
 * @param BP_Members_Notice|null $notice The notice object.
 * @return string The notice title.
 */
function bp_get_notice_title( $notice = null ) {
	$notice_title = '';

	if ( ! empty( $notice->subject ) ) {
		/**
		 * Stop using this filter, use `bp_get_notice_title` instead.
		 *
		 * @since 1.0.0
		 * @deprecated 15.0.0
		 *
		 * @param string $subject Subject of the current notice in the loop.
		 */
		$notice_title = apply_filters_deprecated( 'bp_get_message_notice_subject', array( $notice->subject ), '15.0.0', 'bp_get_notice_title' );
	}

	/**
	 * Filter the notice title.
	 *
	 * @since 15.0.0
	 *
	 * @param string                 $notice_title The notice title.
	 * @param BP_Members_Notice|null $notice       The notice object if it exists. Null otherwise.
	 */
	return apply_filters( 'bp_get_notice_title', $notice_title, $notice );
}

/**
 * Output the content of a notice.
 *
 * @since 15.0.0
 *
 * @param BP_Members_Notice|null $notice The notice object.
 */
function bp_notice_content( $notice = null ) {
	// Escaping is made in `bp-messages/bp-messages-filters.php`.
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_notice_content( $notice );
}

/**
 * Get the parsed block content for the Notice.
 *
 * @since 15.0.0
 *
 * @param BP_Members_Notice|null $notice The notice object.
 * @return array The parsed block content for the Notice.
 */
function bp_get_parsed_notice_block( $notice = null ) {
	$parsed_content = array();

	if ( ! isset( $notice->message ) ) {
		return $parsed_content;
	}

	$notice_data = parse_blocks( $notice->message );
	if ( 'bp/member-notice' === $notice_data[0]['blockName'] ) {
		$parsed_content = reset( $notice_data );
	} else {
		$parsed_content['innerHTML'] = $notice->message;
		$parsed_content['attrs']     = array();
	}

	return $parsed_content;
}

/**
 * Get the content of a notice.
 *
 * @since 15.0.0
 *
 * @param BP_Members_Notice|null $notice The notice object.
 * @return string The notice content.
 */
function bp_get_notice_content( $notice = null ) {
	$parsed_content = bp_get_parsed_notice_block( $notice );

	if ( ! empty( $parsed_content['innerHTML'] ) ) {
		$notice_content = apply_filters_deprecated( 'bp_get_message_notice_text', array( $parsed_content['innerHTML'] ), '15.0.0', 'bp_get_notice_content' );
	}

	/**
	 * Filters the notice content.
	 *
	 * @since 15.0.0
	 *
	 * @param string                 $notice_content The content of the notice.
	 * @param BP_Members_Notice|null $notice         The notice object if it exists. Null otherwise.
	 */
	return apply_filters( 'bp_get_notice_content', $notice_content, $notice );
}

/**
 * Output the selected target for the notice.
 *
 * @since 15.0.0
 *
 * @param BP_Members_Notice|null $notice The notice object.
 */
function bp_notice_target( $notice = null ) {
	$target_key = bp_get_notice_target( $notice );

	$targets = array(
		'community'    => __( 'All community members', 'buddypress' ),
		'admins'       => __( 'All administrators', 'buddypress' ),
		'contributors' => __( 'All contributors', 'buddypress' ),
	);

	echo esc_html( $targets[ $target_key ] );
}

/**
 * Get the notice target.
 *
 * @since 15.0.0
 *
 * @param BP_Members_Notice|null $notice The notice object.
 * @return string The notice target.
 */
function bp_get_notice_target( $notice = null ) {
	$target = 'community';

	// Community is the default target.
	if ( ! empty( $notice->target ) && in_array( $notice->target, array( 'community', 'admins', 'contributors' ), true ) ) {
		$target = $notice->target;
	}

	/**
	 * Filters the notice target.
	 *
	 * @since 15.0.0
	 *
	 * @param string                 $target The notice target.
	 * @param BP_Members_Notice|null $notice The notice object if it exists. Null otherwise.
	 */
	return apply_filters( 'bp_get_notice_target', $target, $notice );
}

/**
 * Get the notice target icon.
 *
 * @since 15.0.0
 *
 * @param BP_Members_Notice|null $notice The notice object.
 * @return string The notice target icon.
 */
function bp_get_notice_target_icon( $notice = null ) {
	$target_icon = 'dashicons-buddicons-community';
	$target      = bp_get_notice_target( $notice );

	if ( 'admins' === $target ) {
		$target_icon = 'dashicons-dashboard';
	} elseif ( 'contributors' === $target ) {
		$target_icon = 'dashicons-edit';
	}

	/**
	 * Filters the notice type.
	 *
	 * @since 15.0.0
	 *
	 * @param string                 $target_icon The notice target icon.
	 * @param BP_Members_Notice|null $notice      The notice object if it exists. Null otherwise.
	 */
	return apply_filters( 'bp_get_notice_target_icon', $target_icon, $notice );
}

/**
 * Output the notice priority.
 *
 * @since 15.0.0
 *
 * @param BP_Members_Notice|null $notice The notice object.
 */
function bp_notice_priority( $notice = null ) {
	$priority_key = bp_get_notice_priority( $notice );

	$priorities = array(
		__( 'Very High', 'buddypress' ),
		__( 'High', 'buddypress' ),
		__( 'Regular', 'buddypress' ),
		__( 'Low', 'buddypress' ),
	);

	// It's a deactivated notice.
	if ( 127 === $priority_key ) {
		$priority = '&#8212;';

		// Try to get previous priority.
		$previous_priority = bp_notices_get_meta( $notice->id, 'previous_priority', true );
		if ( '' !== $previous_priority && isset( $priorities[ $previous_priority ] ) ) {
			$priority = $priorities[ $previous_priority ];
		}
	} else {
		$priority = $priorities[ $priority_key ];
	}

	echo esc_html( $priority );
}

/**
 * Get the notice priority.
 *
 * @since 15.0.0
 *
 * @param BP_Members_Notice|null $notice The notice object.
 * @return integer The notice priority.
 */
function bp_get_notice_priority( $notice = null ) {
	$priority = 2;
	if ( isset( $notice->priority ) ) {
		$priority = (int) $notice->priority;
	}

	/**
	 * Filters the notice type.
	 *
	 * @since 15.0.0
	 *
	 * @param integer                $priority The notice priority.
	 * @param BP_Members_Notice|null $notice   The notice object if it exists. Null otherwise.
	 */
	return apply_filters( 'bp_get_notice_priority', $priority, $notice );
}

/**
 * Output the Notice Action URL.
 *
 * @since 15.0.0
 *
 * @param BP_Members_Notice|null $notice The notice object.
 */
function bp_notice_action_url( $notice = null ) {
	echo esc_url( bp_get_notice_action_url( $notice ) );
}

/**
 * Get the Notice Action URL.
 *
 * @since 15.0.0
 *
 * @param BP_Members_Notice|null $notice The notice object.
 * @return string The Notice Action URL.
 */
function bp_get_notice_action_url( $notice = null ) {
	$url           = '';
	$parsed_notice = bp_get_parsed_notice_block( $notice );

	if ( isset( $parsed_notice['attrs']['url'] ) ) {
		$url = $parsed_notice['attrs']['url'];
	}

	return $url;
}

/**
 * Output the Notice Action Text.
 *
 * @since 15.0.0
 *
 * @param BP_Members_Notice|null $notice The notice object.
 */
function bp_notice_action_text( $notice = null ) {
	echo esc_html( bp_get_notice_action_text( $notice ) );
}

/**
 * Get the Notice Action Text.
 *
 * @since 15.0.0
 *
 * @param BP_Members_Notice|null $notice The notice object.
 * @return string The Notice Action Text.
 */
function bp_get_notice_action_text( $notice = null ) {
	$text          = '';
	$parsed_notice = bp_get_parsed_notice_block( $notice );

	if ( isset( $parsed_notice['attrs']['text'] ) ) {
		$text = $parsed_notice['attrs']['text'];
	}

	return $text;
}

/**
 * Output the URL for dismissing a notice for the current user.
 *
 * @since 15.0.0
 *
 * @param BP_Members_Notice|null $notice The notice object.
 */
function bp_notice_dismiss_url( $notice = null ) {
	echo esc_url( bp_get_notice_dismiss_url( $notice ) );
}

/**
 * Get the URL for dismissing the current notice for the current user.
 *
 * @since 15.0.0
 *
 * @param BP_Members_Notice|null $notice The notice object.
 * @return string URL for dismissing the current notice for the current user.
 */
function bp_get_notice_dismiss_url( $notice = null ) {
	$notice_id = 0;
	$url       = '';

	if ( isset( $notice->id ) ) {
		$notice_id = (int) $notice->id;

		$path_chunks = array( 'notices', 'community', 'dismiss', $notice_id );
		if ( bp_is_active( 'notifications' ) ) {
			unset( $path_chunks[1] );
			array_unshift( $path_chunks, bp_get_notifications_slug() );
		}

		$user_url = '';
		if ( bp_is_admin() ) {
			$user_url = bp_loggedin_user_url( bp_members_get_path_chunks( $path_chunks ) );
		} else {
			$user_url = bp_displayed_user_url( bp_members_get_path_chunks( $path_chunks ) );
		}

		$url = wp_nonce_url(
			$user_url,
			'members_dismiss_notice'
		);
	}

	/**
	 * Filters the URL for dismissing the current notice for the current user.
	 *
	 * @since 9.0.0
	 * @deprecated 15.0.0
	 *
	 * @param string $url URL for dismissing the current notice.
	 */
	$url = apply_filters_deprecated( 'bp_get_message_notice_dismiss_link', array( $url ), '15.0.0', 'bp_get_notice_dismiss_url' );

	/**
	 * Filters the URL for dismissing the current notice for the current user.
	 *
	 * @since 15.0.0
	 *
	 * @param string $url      Nonced URL for dismissing the current notice.
	 * @param string $user_url User URL for dismissing the current notice.
	 */
	return apply_filters( 'bp_get_notice_dismiss_url', $url, $user_url );
}

/**
 * Used to render the active notice after the WP Admin Bar.
 *
 * @since 15.0.0
 */
function bp_render_notices_center() {
	$notice        = null;
	$user_id       = bp_loggedin_user_id();
	$result        = bp_members_get_notices_for_user( $user_id );
	$notices_count = 0;

	// @todo: make it aware of each notice of the loop.
	$current_num = 1;

	if ( ! isset( $result['item'] ) || is_null( $result['item'] ) ) {
		return;
	} else {
		$notice = $result['item'];
		$notices_count  = 1;

		if ( isset( $result['count'] ) ) {
			$notices_count = $result['count'];
		}
	}

	$notifications       = array();
	$notifications_count = 0;

	if ( bp_is_active( 'notifications' ) ) {
		$notifications       = bp_notifications_get_notifications_for_user( $user_id, 'object' );
		$notifications_count = 0;

		if ( false !== $notifications && is_countable( $notifications ) ) {
			$notifications_count = count( $notifications );
		}
	}
	?>
	<aside popover="auto" id="bp-notices-container" role="complementary" tabindex="-1">
		<?php if ( $notices_count ) : ?>
			<section class="bp-notices-section">
				<h2 class="community-notices-title"><?php esc_html_e( 'Community notices', 'buddypress' ); ?></h2>
				<article id="notice-<?php echo esc_attr( $notice->id ); ?>">
					<header class="bp-notice-header">
						<h3><?php bp_notice_title( $notice ); ?></h2>
					</header>
					<div class="bp-notice-body">
						<div class="bp-notice-type dashicons <?php echo esc_attr( bp_get_notice_target_icon( $notice ) ); ?>" ></div>
						<div class="bp-notice-content">
							<?php bp_notice_content( $notice ); ?>
						</div>
					</div>
					<footer class="bp-notice-footer">
						<div class="bp-notice-pagination">
							<span class="bp-notice-current-page">
								<?php
								printf(
									/* translators: 1: the current number notice. 2: the total number of notices. */
									_n( 'Viewing %1$s/%2$s notice', 'Viewing %1$s/%2$s notices', $notices_count, 'buddypress' ),
									$current_num,
									$notices_count
								);
								?>
							</span>
						</div>
					</footer>
				</article>
			</section>
		<?php endif; ?>
		<?php if ( $notifications_count ) : ?>

			<?php if ( $notices_count ) : ?>
				<hr>
			<?php endif; ?>

			<section class="bp-notications-section">
				<h2 class="my-notifications-title"><?php esc_html_e( 'My personal notifications', 'buddypress' ); ?></h2>

				<?php foreach( $notifications as $notification ) : ?>
					<article id="notification-<?php echo esc_attr( $notification->id ); ?>">
						<div class="bp-notification-body">
							<div class="notification">
								<?php echo esc_html( $notification->content ); ?> &#8212; <a href="<?php echo esc_url( $notification->href ); ?>"><?php esc_html_e( 'View', 'buddypress' ); ?></a>
							</div>
						</div>
					</article>
				<?php endforeach; ?>
			</section>

		<?php endif; ?>
	</aside>
	<?php
}

/**
 * Callback function to render the BP Sitewide Notices Block.
 *
 * @since 15.0.0
 *
 * @param array $attributes The block attributes.
 * @return string HTML output.
 */
function bp_members_render_notices_block( $attributes = array() ) {
	$block_args = bp_parse_args(
		$attributes,
		array(
			'title' => '',
		),
		'widget_object_sitewide_notices'
	);

	if ( ! is_user_logged_in() ) {
		return;
	}

	$feedback_tpl  = '<div class="components-placeholder">' . "\n";
	$feedback_tpl .= '<div class="components-placeholder__label">%1$s</div>' . "\n";
	$feedback_tpl .= '<div class="components-placeholder__fieldset">%2$s</div>' . "\n";
	$feedback_tpl .= '</div>';

	// Don't display the block if there are no Notices to show.
	$notice = BP_Members_Notice::get_active();
	if ( empty( $notice->id ) ) {
		// Previewing the Block inside the editor.
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return sprintf(
				$feedback_tpl,
				esc_html__( 'Preview unavailable', 'buddypress' ),
				esc_html__( 'No active sitewide notices.', 'buddypress' )
			);
		}

		return;
	}

	// Only enqueue common/specific scripts and data once per page load.
	if ( ! wp_script_is( 'bp-sitewide-notices-script', 'enqueued' ) ) {
		wp_enqueue_script( 'bp-sitewide-notices-script' );
	}

	$closed_notices = (array) bp_get_user_meta( bp_loggedin_user_id(), 'closed_notices', true );

	if ( in_array( $notice->id, $closed_notices, true ) ) {
		// Previewing the Block inside the editor.
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return sprintf(
				$feedback_tpl,
				esc_html__( 'Preview unavailable', 'buddypress' ),
				esc_html__( 'You dismissed the sitewide notice.', 'buddypress' )
			);
		}

		return;
	}

	// There is an active, non-dismissed notice to show.
	$title = $block_args['title'];

	$classnames         = 'widget_bp_core_sitewide_messages buddypress widget';
	$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $classnames ) );

	$widget_content = '<div class="bp-sitewide-notice-block">';

	if ( $title ) {
		$widget_content .= sprintf(
			'<h2 class="widget-title">%s</h2>',
			esc_html( $title )
		);
	}

	$widget_content .= sprintf(
		'<div class="bp-sitewide-notice-message info bp-notice" rel="n-%1$d">
			<strong>%2$s</strong>
			<a href="%3$s" class="bp-tooltip button dismiss-notice" data-bp-tooltip="%4$s" data-bp-sitewide-notice-id="%5$d"><span class="bp-screen-reader-text">%6$s</span> <span aria-hidden="true">&#x2716;</span></a>
			%7$s
		</div>',
		esc_attr( $notice->id ),
		bp_get_notice_title( $notice ),
		esc_url( bp_get_notice_dismiss_url() ),
		esc_attr__( 'Dismiss this notice', 'buddypress' ),
		esc_attr( $notice->id ),
		esc_html__( 'Dismiss this notice', 'buddypress' ),
		bp_get_notice_content( $notice )
	);

	$widget_content .= '</div>';

	// Enqueue BP Tooltips.
	wp_enqueue_style( 'bp-tooltips' );

	if ( ! did_action( 'dynamic_sidebar_before' ) ) {
		return sprintf(
			'<div %1$s>%2$s</div>',
			$wrapper_attributes,
			$widget_content
		);
	}

	return $widget_content;
}

/**
 * Output the Admin Notice version.
 *
 * @since 15.0.0
 *
 * @param BP_Members_Notice|null $notice The notice object.
 */
function bp_admin_notice_version( $notice = null ) {
	echo esc_html( bp_get_admin_notice_version( $notice ) );
}

/**
 * Get the Admin Notice version.
 *
 * @since 15.0.0
 *
 * @param BP_Members_Notice|null $notice The notice object.
 * @return string The Admin Notice version.
 */
function bp_get_admin_notice_version( $notice = null ) {
	$version = '';
	$parsed_notice = bp_get_parsed_notice_block( $notice );

	if ( isset( $parsed_notice['attrs']['meta']['version'] ) ) {
		$version = $parsed_notice['attrs']['meta']['version'];
	}

	return number_format( $version, 1 );
}

/**
 * Output list of notices for the displayed user.
 *
 * @since 15.0.0
 */
function bp_output_notices() {
	$user_id = bp_displayed_user_id();
	$notices = bp_members_get_notices(
		array(
			'user_id' => $user_id,
			'exclude' => bp_members_get_dismissed_notices_for_user( $user_id )
		)
	);

	if ( empty( $notices ) ) {
		?>
		<p class="bp-notices-no-results"><?php esc_html_e( 'There are no notices to display.', 'buddypress' ); ?></p>
		<?php
	} else {
		// Loop through Notices.
		foreach ( $notices as $notice ) {
			bp_get_template_part( 'members/single/notices/entry', null, array( 'context' => $notice ) );
		}
	}
}
