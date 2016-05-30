<?php
/**
 * BuddyPress Notifications Template Functions.
 *
 * @package BuddyPress
 * @subpackage TonificationsTemplate
 * @since 1.9.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! buddypress()->do_autoload ) {
	require dirname( __FILE__ ) . '/classes/class-bp-notifications-template.php';
}

/**
 * Output the notifications component slug.
 *
 * @since 1.9.0
 */
function bp_notifications_slug() {
	echo bp_get_notifications_slug();
}
	/**
	 * Return the notifications component slug.
	 *
	 * @since 1.9.0
	 *
	 * @return string Slug of the Notifications component.
	 */
	function bp_get_notifications_slug() {

		/**
		 * Filters the notifications component slug.
		 *
		 * @since 1.9.0
		 *
		 * @param string $slug Notifications component slug.
		 */
		return apply_filters( 'bp_get_notifications_slug', buddypress()->notifications->slug );
	}

/**
 * Output the notifications permalink for a user.
 *
 * @since 1.9.0
 * @since 2.6.0 Added $user_id as a parameter.
 *
 * @param int $user_id The user ID.
 */
function bp_notifications_permalink( $user_id = 0 ) {
	echo bp_get_notifications_permalink( $user_id );
}
	/**
	 * Return the notifications permalink.
	 *
	 * @since 1.9.0
	 * @since 2.6.0 Added $user_id as a parameter.
	 *
	 * @param int $user_id The user ID.
	 * @return string Notifications permalink.
	 */
	function bp_get_notifications_permalink( $user_id = 0 ) {
		if ( 0 === $user_id ) {
			$user_id = bp_loggedin_user_id();
			$domain  = bp_loggedin_user_domain();
		} else {
			$domain = bp_core_get_user_domain( (int) $user_id );
		}

		$retval = trailingslashit( $domain . bp_get_notifications_slug() );

		/**
		 * Filters the notifications permalink.
		 *
		 * @since 1.9.0
		 * @since 2.6.0 Added $user_id as a parameter.
		 *
		 * @param string $retval  Permalink for the notifications.
		 * @param int    $user_id The user ID.
		 */
		return apply_filters( 'bp_get_notifications_permalink', $retval, $user_id );
	}

/**
 * Output the unread notifications permalink for a user.
 *
 * @since 1.9.0
 * @since 2.6.0 Added $user_id as a parameter.
 *
 * @param int $user_id The user ID.
 */
function bp_notifications_unread_permalink( $user_id = 0 ) {
	echo bp_get_notifications_unread_permalink( $user_id );
}
	/**
	 * Return the unread notifications permalink.
	 *
	 * @since 2.6.0 Added $user_id as a parameter.
	 *
	 * @param int $user_id The user ID.
	 * @return string Unread notifications permalink.
	 */
	function bp_get_notifications_unread_permalink( $user_id = 0 ) {
		if ( 0 === $user_id ) {
			$user_id = bp_loggedin_user_id();
			$domain  = bp_loggedin_user_domain();
		} else {
			$domain = bp_core_get_user_domain( (int) $user_id );
		}

		$retval = trailingslashit( $domain . bp_get_notifications_slug() . '/unread' );

		/**
		 * Filters the unread notifications permalink.
		 *
		 * @since 1.9.0
		 * @since 2.6.0 Added $user_id as a parameter.
		 *
		 * @param string $retval  Permalink for the unread notifications.
		 * @param int    $user_id The user ID.
		 */
		return apply_filters( 'bp_get_notifications_unread_permalink', $retval, $user_id );
	}

/**
 * Output the read notifications permalink for a user.
 *
 * @since 1.9.0
 * @since 2.6.0 Added $user_id as a parameter.
 *
 * @param int $user_id The user ID.
 */
function bp_notifications_read_permalink( $user_id = 0 ) {
	echo bp_get_notifications_read_permalink( $user_id );
}
	/**
	 * Return the read notifications permalink.
	 *
	 * @since 1.9.0
	 *
	 * @return string Read notifications permalink.
	 */
	function bp_get_notifications_read_permalink( $user_id = 0 ) {
		if ( 0 === $user_id ) {
			$user_id = bp_loggedin_user_id();
			$domain  = bp_loggedin_user_domain();
		} else {
			$domain = bp_core_get_user_domain( (int) $user_id );
		}

		$retval = trailingslashit( $domain . bp_get_notifications_slug() . '/read' );

		/**
		 * Filters the read notifications permalink.
		 *
		 * @since 1.9.0
		 * @since 2.6.0 Added $user_id as a parameter.
		 *
		 * @param string $retval  Permalink for the read notifications.
		 * @param int    $user_id The user ID.
		 */
		return apply_filters( 'bp_get_notifications_unread_permalink', $retval, $user_id );
	}

/** The Loop ******************************************************************/

/**
 * Initialize the notifications loop.
 *
 * Based on the $args passed, bp_has_notifications() populates
 * buddypress()->notifications->query_loop global, enabling the use of BP
 * templates and template functions to display a list of notifications.
 *
 * @since 1.9.0
 *
 * @param array|string $args {
 *     Arguments for limiting the contents of the notifications loop. Can be
 *     passed as an associative array, or as a URL query string.
 *
 *     See {@link BP_Notifications_Notification::get()} for detailed
 *     information on the arguments.  In addition, also supports:
 *
 *     @type int    $max      Optional. Max items to display. Default: false.
 *     @type string $page_arg URL argument to use for pagination.
 *                            Default: 'npage'.
 * }
 * @return bool
 */
function bp_has_notifications( $args = '' ) {

	// Get the default is_new argument.
	if ( bp_is_current_action( 'unread' ) ) {
		$is_new = 1;
	} elseif ( bp_is_current_action( 'read' ) ) {
		$is_new = 0;

	// Not on a notifications page? default to fetch new notifications.
	} else {
		$is_new = 1;
	}

	// Get the user ID.
	if ( bp_displayed_user_id() ) {
		$user_id = bp_displayed_user_id();
	} else {
		$user_id = bp_loggedin_user_id();
	}

	// Set the component action (by default false to get all actions)
	$component_action = false;

	if ( isset( $_REQUEST['type'] ) ) {
		$component_action = sanitize_key( $_REQUEST['type'] );
	}

	// Set the search terms (by default an empty string to get all notifications)
	$search_terms = '';

	if ( isset( $_REQUEST['s'] ) ) {
		$search_terms = stripslashes( $_REQUEST['s'] );
	}

	// Parse the args.
	$r = bp_parse_args( $args, array(
		'id'                => false,
		'user_id'           => $user_id,
		'secondary_item_id' => false,
		'component_name'    => bp_notifications_get_registered_components(),
		'component_action'  => $component_action,
		'is_new'            => $is_new,
		'search_terms'      => $search_terms,
		'order_by'          => 'date_notified',
		'sort_order'        => 'DESC',
		'meta_query'        => false,
		'date_query'        => false,
		'page'              => 1,
		'per_page'          => 25,

		// These are additional arguments that are not available in
		// BP_Notifications_Notification::get().
		'max'               => false,
		'page_arg'          => 'npage',
	), 'has_notifications' );

	// Get the notifications.
	$query_loop = new BP_Notifications_Template( $r );

	// Setup the global query loop.
	buddypress()->notifications->query_loop = $query_loop;

	/**
	 * Filters whether or not the user has notifications to display.
	 *
	 * @since 1.9.0
	 * @since 2.6.0 Added the `$r` parameter.
	 *
	 * @param bool                      $value      Whether or not there are notifications to display.
	 * @param BP_Notifications_Template $query_loop BP_Notifications_Template object instance.
	 * @param array                     $r          Array of arguments passed into the BP_Notifications_Template class.
	 */
	return apply_filters( 'bp_has_notifications', $query_loop->has_notifications(), $query_loop, $r );
}

/**
 * Get the notifications returned by the template loop.
 *
 * @since 1.9.0
 *
 * @return array List of notifications.
 */
function bp_the_notifications() {
	return buddypress()->notifications->query_loop->notifications();
}

/**
 * Get the current notification object in the loop.
 *
 * @since 1.9.0
 *
 * @return object The current notification within the loop.
 */
function bp_the_notification() {
	return buddypress()->notifications->query_loop->the_notification();
}

/** Loop Output ***************************************************************/

/**
 * Output the ID of the notification currently being iterated on.
 *
 * @since 1.9.0
 */
function bp_the_notification_id() {
	echo bp_get_the_notification_id();
}
	/**
	 * Return the ID of the notification currently being iterated on.
	 *
	 * @since 1.9.0
	 *
	 * @return int ID of the current notification.
	 */
	function bp_get_the_notification_id() {

		/**
		 * Filters the ID of the notification currently being iterated on.
		 *
		 * @since 1.9.0
		 *
		 * @param int $id ID of the notification being iterated on.
		 */
		return apply_filters( 'bp_get_the_notification_id', buddypress()->notifications->query_loop->notification->id );
	}

/**
 * Output the associated item ID of the notification currently being iterated on.
 *
 * @since 1.9.0
 */
function bp_the_notification_item_id() {
	echo bp_get_the_notification_item_id();
}
	/**
	 * Return the associated item ID of the notification currently being iterated on.
	 *
	 * @since 1.9.0
	 *
	 * @return int ID of the item associated with the current notification.
	 */
	function bp_get_the_notification_item_id() {

		/**
		 * Filters the associated item ID of the notification currently being iterated on.
		 *
		 * @since 1.9.0
		 *
		 * @param int $item_id ID of the associated item.
		 */
		return apply_filters( 'bp_get_the_notification_item_id', buddypress()->notifications->query_loop->notification->item_id );
	}

/**
 * Output the secondary associated item ID of the notification currently being iterated on.
 *
 * @since 1.9.0
 */
function bp_the_notification_secondary_item_id() {
	echo bp_get_the_notification_secondary_item_id();
}
	/**
	 * Return the secondary associated item ID of the notification currently being iterated on.
	 *
	 * @since 1.9.0
	 *
	 * @return int ID of the secondary item associated with the current notification.
	 */
	function bp_get_the_notification_secondary_item_id() {

		/**
		 * Filters the secondary associated item ID of the notification currently being iterated on.
		 *
		 * @since 1.9.0
		 *
		 * @param int $secondary_item_id ID of the secondary associated item.
		 */
		return apply_filters( 'bp_get_the_notification_secondary_item_id', buddypress()->notifications->query_loop->notification->secondary_item_id );
	}

/**
 * Output the name of the component associated with the notification currently being iterated on.
 *
 * @since 1.9.0
 */
function bp_the_notification_component_name() {
	echo bp_get_the_notification_component_name();
}
	/**
	 * Return the name of the component associated with the notification currently being iterated on.
	 *
	 * @since 1.9.0
	 *
	 * @return int Name of the component associated with the current notification.
	 */
	function bp_get_the_notification_component_name() {

		/**
		 * Filters the name of the component associated with the notification currently being iterated on.
		 *
		 * @since 1.9.0
		 *
		 * @param int $component_name Name of the component associated with the current notification.
		 */
		return apply_filters( 'bp_get_the_notification_component_name', buddypress()->notifications->query_loop->notification->component_name );
	}

/**
 * Output the name of the action associated with the notification currently being iterated on.
 *
 * @since 1.9.0
 */
function bp_the_notification_component_action() {
	echo bp_get_the_notification_component_action();
}
	/**
	 * Return the name of the action associated with the notification currently being iterated on.
	 *
	 * @since 1.9.0
	 *
	 * @return int Name of the action associated with the current notification.
	 */
	function bp_get_the_notification_component_action() {

		/**
		 * Filters the name of the action associated with the notification currently being iterated on.
		 *
		 * @since 1.9.0
		 *
		 * @param int $component_action Name of the action associated with the current notification.
		 */
		return apply_filters( 'bp_get_the_notification_component_action', buddypress()->notifications->query_loop->notification->component_action );
	}

/**
 * Output the timestamp of the current notification.
 *
 * @since 1.9.0
 */
function bp_the_notification_date_notified() {
	echo bp_get_the_notification_date_notified();
}
	/**
	 * Return the timestamp of the current notification.
	 *
	 * @since 1.9.0
	 *
	 * @return string Timestamp of the current notification.
	 */
	function bp_get_the_notification_date_notified() {

		/**
		 * Filters the timestamp of the current notification.
		 *
		 * @since 1.9.0
		 *
		 * @param string $date_notified Timestamp of the current notification.
		 */
		return apply_filters( 'bp_get_the_notification_date_notified', buddypress()->notifications->query_loop->notification->date_notified );
	}

/**
 * Output the timestamp of the current notification.
 *
 * @since 1.9.0
 */
function bp_the_notification_time_since() {
	echo bp_get_the_notification_time_since();
}
	/**
	 * Return the timestamp of the current notification.
	 *
	 * @since 1.9.0
	 *
	 * @return string Timestamp of the current notification.
	 */
	function bp_get_the_notification_time_since() {

		// Get the notified date.
		$date_notified = bp_get_the_notification_date_notified();

		// Notified date has legitimate data.
		if ( '0000-00-00 00:00:00' !== $date_notified ) {
			$retval = bp_core_time_since( $date_notified );

		// Notified date is empty, so return a fun string.
		} else {
			$retval = __( 'Date not found', 'buddypress' );
		}

		/**
		 * Filters the time since value of the current notification.
		 *
		 * @since 1.9.0
		 *
		 * @param string $retval Time since value for current notification.
		 */
		return apply_filters( 'bp_get_the_notification_time_since', $retval );
	}

/**
 * Output full-text description for a specific notification.
 *
 * @since 1.9.0
 */
function bp_the_notification_description() {
	echo bp_get_the_notification_description();
}

	/**
	 * Get full-text description for a specific notification.
	 *
	 * @since 1.9.0
	 *
	 * @return string
	 */
	function bp_get_the_notification_description() {
		$bp           = buddypress();
		$notification = $bp->notifications->query_loop->notification;

		// Callback function exists.
		if ( isset( $bp->{ $notification->component_name }->notification_callback ) && is_callable( $bp->{ $notification->component_name }->notification_callback ) ) {
			$description = call_user_func( $bp->{ $notification->component_name }->notification_callback, $notification->component_action, $notification->item_id, $notification->secondary_item_id, 1, 'string', $notification->id );

		// @deprecated format_notification_function - 1.5
		} elseif ( isset( $bp->{ $notification->component_name }->format_notification_function ) && function_exists( $bp->{ $notification->component_name }->format_notification_function ) ) {
			$description = call_user_func( $bp->{ $notification->component_name }->format_notification_function, $notification->component_action, $notification->item_id, $notification->secondary_item_id, 1 );

		// Allow non BuddyPress components to hook in.
		} else {

			/** This filter is documented in bp-notifications/bp-notifications-functions.php */
			$description = apply_filters_ref_array( 'bp_notifications_get_notifications_for_user', array( $notification->component_action, $notification->item_id, $notification->secondary_item_id, 1, 'string', $notification->component_action, $notification->component_name, $notification->id ) );
		}

		/**
		 * Filters the full-text description for a specific notification.
		 *
		 * @since 1.9.0
		 * @since 2.3.0 Added the `$notification` parameter.
		 *
		 * @param string $description  Full-text description for a specific notification.
		 * @param object $notification Notification object.
		 */
		return apply_filters( 'bp_get_the_notification_description', $description, $notification );
	}

/**
 * Output the mark read link for the current notification.
 *
 * @since 1.9.0
 * @since 2.6.0 Added $user_id as a parameter.
 *
 * @param int $user_id The user ID.
 */
function bp_the_notification_mark_read_link( $user_id = 0 ) {
	echo bp_get_the_notification_mark_read_link( $user_id );
}
	/**
	 * Return the mark read link for the current notification.
	 *
	 * @since 1.9.0
	 * @since 2.6.0 Added $user_id as a parameter.
	 *
	 * @param int $user_id The user ID.
	 * @return string
	 */
	function bp_get_the_notification_mark_read_link( $user_id = 0 ) {
		// Set default user ID to use.
		$user_id = 0 === $user_id ? bp_displayed_user_id() : $user_id;

		$retval = sprintf( '<a href="%1$s" class="mark-read primary">%2$s</a>', esc_url( bp_get_the_notification_mark_read_url( $user_id ) ), __( 'Read', 'buddypress' ) );

		/**
		 * Filters the mark read link for the current notification.
		 *
		 * @since 1.9.0
		 * @since 2.6.0 Added $user_id as a parameter.
		 *
		 * @param string $retval  HTML for the mark read link for the current notification.
		 * @param int    $user_id The user ID.
		 */
		return apply_filters( 'bp_get_the_notification_mark_read_link', $retval, $user_id );
	}

/**
 * Output the URL used for marking a single notification as read.
 *
 * Since this function directly outputs a URL, it is escaped.
 *
 * @since 2.1.0
 * @since 2.6.0 Added $user_id as a parameter.
 *
 * @param int $user_id The user ID.
 */
function bp_the_notification_mark_read_url( $user_id = 0 ) {
	echo esc_url( bp_get_the_notification_mark_read_url( $user_id ) );
}
	/**
	 * Return the URL used for marking a single notification as read.
	 *
	 * @since 2.1.0
	 * @since 2.6.0 Added $user_id as a parameter.
	 *
	 * @param int $user_id The user ID.
	 * @return string
	 */
	function bp_get_the_notification_mark_read_url( $user_id = 0 ) {

		// Get the notification ID.
		$id   = bp_get_the_notification_id();

		// Get the args to add to the URL.
		$args = array(
			'action'          => 'read',
			'notification_id' => $id
		);

		// Set default user ID to use.
		$user_id = 0 === $user_id ? bp_displayed_user_id() : $user_id;

		// Add the args to the URL.
		$url = add_query_arg( $args, bp_get_notifications_unread_permalink( $user_id ) );

		// Add the nonce.
		$url = wp_nonce_url( $url, 'bp_notification_mark_read_' . $id );

		/**
		 * Filters the URL used for marking a single notification as read.
		 *
		 * @since 2.1.0
		 * @since 2.6.0 Added $user_id as a parameter.
		 *
		 * @param string $url     URL to use for marking the single notification as read.
		 * @param int    $user_id The user ID.
		 */
		return apply_filters( 'bp_get_the_notification_mark_read_url', $url, $user_id );
	}

/**
 * Output the mark unread link for the current notification.
 *
 * @since 1.9.0
 * @since 2.6.0 Added $user_id as a parameter.
 *
 * @param int $user_id The user ID.
 */
function bp_the_notification_mark_unread_link( $user_id = 0 ) {
	echo bp_get_the_notification_mark_unread_link();
}
	/**
	 * Return the mark unread link for the current notification.
	 *
	 * @since 1.9.0
	 * @since 2.6.0 Added $user_id as a parameter.
	 *
	 * @param int $user_id The user ID.
	 * @return string
	 */
	function bp_get_the_notification_mark_unread_link( $user_id = 0 ) {
		// Set default user ID to use.
		$user_id = 0 === $user_id ? bp_displayed_user_id() : $user_id;

		$retval = sprintf( '<a href="%1$s" class="mark-unread primary">%2$s</a>', esc_url( bp_get_the_notification_mark_unread_url( $user_id ) ), __( 'Unread', 'buddypress' ) );

		/**
		 * Filters the link used for marking a single notification as unread.
		 *
		 * @since 1.9.0
		 * @since 2.6.0 Added $user_id as a parameter.
		 *
		 * @param string $retval  HTML for the mark unread link for the current notification.
		 * @param int    $user_id The user ID.
		 */
		return apply_filters( 'bp_get_the_notification_mark_unread_link', $retval, $user_id );
	}

/**
 * Output the URL used for marking a single notification as unread.
 *
 * Since this function directly outputs a URL, it is escaped.
 *
 * @since 2.1.0
 * @since 2.6.0 Added $user_id as a parameter.
 *
 * @param int $user_id The user ID.
 */
function bp_the_notification_mark_unread_url( $user_id = 0 ) {
	echo esc_url( bp_get_the_notification_mark_unread_url( $user_id ) );
}
	/**
	 * Return the URL used for marking a single notification as unread.
	 *
	 * @since 2.1.0
	 * @since 2.6.0 Added $user_id as a parameter.
	 *
	 * @param int $user_id The user ID.
	 * @return string
	 */
	function bp_get_the_notification_mark_unread_url( $user_id = 0 ) {

		// Get the notification ID.
		$id   = bp_get_the_notification_id();

		// Get the args to add to the URL.
		$args = array(
			'action'          => 'unread',
			'notification_id' => $id
		);

		// Set default user ID to use.
		$user_id = 0 === $user_id ? bp_displayed_user_id() : $user_id;

		// Add the args to the URL.
		$url = add_query_arg( $args, bp_get_notifications_read_permalink( $user_id ) );

		// Add the nonce.
		$url = wp_nonce_url( $url, 'bp_notification_mark_unread_' . $id );

		/**
		 * Filters the URL used for marking a single notification as unread.
		 *
		 * @since 2.1.0
		 * @since 2.6.0 Added $user_id as a parameter.
		 *
		 * @param string $url     URL to use for marking the single notification as unread.
		 * @param int    $user_id The user ID.
		 */
		return apply_filters( 'bp_get_the_notification_mark_unread_url', $url, $user_id );
	}

/**
 * Output the mark link for the current notification.
 *
 * @since 1.9.0
 * @since 2.6.0 Added $user_id as a parameter.
 *
 * @param int $user_id The user ID.
 */
function bp_the_notification_mark_link( $user_id = 0 ) {
	echo bp_get_the_notification_mark_link( $user_id );
}
	/**
	 * Return the mark link for the current notification.
	 *
	 * @since 1.9.0
	 * @since 2.6.0 Added $user_id as a parameter.
	 *
	 * @param int $user_id The user ID.
	 * @return string
	 */
	function bp_get_the_notification_mark_link( $user_id = 0 ) {
		// Set default user ID to use.
		$user_id = 0 === $user_id ? bp_displayed_user_id() : $user_id;

		if ( bp_is_current_action( 'read' ) ) {
			$retval = bp_get_the_notification_mark_unread_link( $user_id );
		} else {
			$retval = bp_get_the_notification_mark_read_link( $user_id );
		}

		/**
		 * Filters the mark link for the current notification.
		 *
		 * @since 1.9.0
		 * @since 2.6.0 Added $user_id as a parameter.
		 *
		 * @param string $retval  The mark link for the current notification.
		 * @param int    $user_id The user ID.
		 */
		return apply_filters( 'bp_get_the_notification_mark_link', $retval, $user_id );
	}

/**
 * Output the delete link for the current notification.
 *
 * @since 1.9.0
 * @since 2.6.0 Added $user_id as a parameter.
 *
 * @param int $user_id The user ID.
 */
function bp_the_notification_delete_link( $user_id = 0 ) {
	echo bp_get_the_notification_delete_link( $user_id );
}
	/**
	 * Return the delete link for the current notification.
	 *
	 * @since 1.9.0
	 * @since 2.6.0 Added $user_id as a parameter.
	 *
	 * @param int $user_id The user ID.
	 * @return string
	 */
	function bp_get_the_notification_delete_link( $user_id = 0 ) {
		// Set default user ID to use.
		$user_id = 0 === $user_id ? bp_displayed_user_id() : $user_id;

		$retval = sprintf( '<a href="%1$s" class="delete secondary confirm">%2$s</a>', esc_url( bp_get_the_notification_delete_url( $user_id ) ), __( 'Delete', 'buddypress' ) );

		/**
		 * Filters the delete link for the current notification.
		 *
		 * @since 1.9.0
		 * @since 2.6.0 Added $user_id as a parameter.
		 *
		 * @param string $retval  HTML for the delete link for the current notification.
		 * @param int    $user_id The user ID.
		 */
		return apply_filters( 'bp_get_the_notification_delete_link', $retval, $user_id );
	}

/**
 * Output the URL used for deleting a single notification.
 *
 * Since this function directly outputs a URL, it is escaped.
 *
 * @since 2.1.0
 * @since 2.6.0 Added $user_id as a parameter.
 *
 * @param int $user_id The user ID.
 */
function bp_the_notification_delete_url( $user_id = 0 ) {
	echo esc_url( bp_get_the_notification_delete_url( $user_id ) );
}
	/**
	 * Return the URL used for deleting a single notification.
	 *
	 * @since 2.1.0
	 * @since 2.6.0 Added $user_id as a parameter.
	 *
	 * @param int $user_id The user ID.
	 * @return string
	 */
	function bp_get_the_notification_delete_url( $user_id = 0 ) {
		// Set default user ID to use.
		$user_id = 0 === $user_id ? bp_displayed_user_id() : $user_id;

		// URL to add nonce to.
		if ( bp_is_current_action( 'unread' ) ) {
			$link = bp_get_notifications_unread_permalink( $user_id );
		} elseif ( bp_is_current_action( 'read' ) ) {
			$link = bp_get_notifications_read_permalink( $user_id );
		}

		// Get the ID.
		$id = bp_get_the_notification_id();

		// Get the args to add to the URL.
		$args = array(
			'action'          => 'delete',
			'notification_id' => $id
		);

		// Add the args.
		$url = add_query_arg( $args, $link );

		// Add the nonce.
		$url = wp_nonce_url( $url, 'bp_notification_delete_' . $id );

		/**
		 * Filters the URL used for deleting a single notification.
		 *
		 * @since 2.1.0
		 * @since 2.6.0 Added $user_id as a parameter.
		 *
		 * @param string $url     URL used for deleting a single notification.
		 * @param int    $user_id The user ID.
		 */
		return apply_filters( 'bp_get_the_notification_delete_url', $url, $user_id );
	}

/**
 * Output the action links for the current notification.
 *
 * @since 1.9.0
 * @since 2.6.0 Added $user_id as a parameter to $args.
 *
 * @param array|string $args Array of arguments.
 */
function bp_the_notification_action_links( $args = '' ) {
	echo bp_get_the_notification_action_links( $args );
}
	/**
	 * Return the action links for the current notification.
	 *
	 * @since 1.9.0
	 * @since 2.6.0 Added $user_id as a parameter to $args.
	 *
	 * @param array|string $args {
	 *     @type string $before  HTML before the links.
	 *     @type string $after   HTML after the links.
	 *     @type string $sep     HTML between the links.
	 *     @type array  $links   Array of links to implode by 'sep'.
	 *     @type int    $user_id User ID to fetch action links for. Defaults to displayed user ID.
	 * }
	 * @return string HTML links for actions to take on single notifications.
	 */
	function bp_get_the_notification_action_links( $args = '' ) {
		// Set default user ID to use.
		$user_id = isset( $args['user_id'] ) ? $args['user_id'] : bp_displayed_user_id();

		// Parse.
		$r = wp_parse_args( $args, array(
			'before' => '',
			'after'  => '',
			'sep'    => ' | ',
			'links'  => array(
				bp_get_the_notification_mark_link( $user_id ),
				bp_get_the_notification_delete_link( $user_id )
			)
		) );

		// Build the links.
		$retval = $r['before'] . implode( $r['links'], $r['sep'] ) . $r['after'];

		/**
		 * Filters the action links for the current notification.
		 *
		 * @since 1.9.0
		 * @since 2.6.0 Added the `$r` parameter.
		 *
		 * @param string $retval HTML links for actions to take on single notifications.
		 * @param array  $r      Array of parsed arguments.
		 */
		return apply_filters( 'bp_get_the_notification_action_links', $retval, $r );
	}

/**
 * Output the pagination count for the current notification loop.
 *
 * @since 1.9.0
 */
function bp_notifications_pagination_count() {
	echo bp_get_notifications_pagination_count();
}
	/**
	 * Return the pagination count for the current notification loop.
	 *
	 * @since 1.9.0
	 *
	 * @return string HTML for the pagination count.
	 */
	function bp_get_notifications_pagination_count() {
		$query_loop = buddypress()->notifications->query_loop;
		$start_num  = intval( ( $query_loop->pag_page - 1 ) * $query_loop->pag_num ) + 1;
		$from_num   = bp_core_number_format( $start_num );
		$to_num     = bp_core_number_format( ( $start_num + ( $query_loop->pag_num - 1 ) > $query_loop->total_notification_count ) ? $query_loop->total_notification_count : $start_num + ( $query_loop->pag_num - 1 ) );
		$total      = bp_core_number_format( $query_loop->total_notification_count );

		if ( 1 == $query_loop->total_notification_count ) {
			$pag = __( 'Viewing 1 notification', 'buddypress' );
		} else {
			$pag = sprintf( _n( 'Viewing %1$s - %2$s of %3$s notification', 'Viewing %1$s - %2$s of %3$s notifications', $query_loop->total_notification_count, 'buddypress' ), $from_num, $to_num, $total );
		}

		/**
		 * Filters the pagination count for the current notification loop.
		 *
		 * @since 1.9.0
		 *
		 * @param string $pag HTML for the pagination count.
		 */
		return apply_filters( 'bp_notifications_pagination_count', $pag );
	}

/**
 * Output the pagination links for the current notification loop.
 *
 * @since 1.9.0
 */
function bp_notifications_pagination_links() {
	echo bp_get_notifications_pagination_links();
}
	/**
	 * Return the pagination links for the current notification loop.
	 *
	 * @since 1.9.0
	 *
	 * @return string HTML for the pagination links.
	 */
	function bp_get_notifications_pagination_links() {

		/**
		 * Filters the pagination links for the current notification loop.
		 *
		 * @since 1.9.0
		 *
		 * @param string $pag_links HTML for the pagination links.
		 */
		return apply_filters( 'bp_get_notifications_pagination_links', buddypress()->notifications->query_loop->pag_links );
	}

/** Form Helpers **************************************************************/

/**
 * Output the form for changing the sort order of notifications.
 *
 * @since 1.9.0
 */
function bp_notifications_sort_order_form() {

	// Setup local variables.
	$orders   = array( 'DESC', 'ASC' );
	$selected = 'DESC';

	// Check for a custom sort_order.
	if ( !empty( $_REQUEST['sort_order'] ) ) {
		if ( in_array( $_REQUEST['sort_order'], $orders ) ) {
			$selected = $_REQUEST['sort_order'];
		}
	} ?>

	<form action="" method="get" id="notifications-sort-order">
		<label for="notifications-sort-order-list"><?php esc_html_e( 'Order By:', 'buddypress' ); ?></label>

		<select id="notifications-sort-order-list" name="sort_order" onchange="this.form.submit();">
			<option value="DESC" <?php selected( $selected, 'DESC' ); ?>><?php _e( 'Newest First', 'buddypress' ); ?></option>
			<option value="ASC"  <?php selected( $selected, 'ASC'  ); ?>><?php _e( 'Oldest First', 'buddypress' ); ?></option>
		</select>

		<noscript>
			<input id="submit" type="submit" name="form-submit" class="submit" value="<?php esc_attr_e( 'Go', 'buddypress' ); ?>" />
		</noscript>
	</form>

<?php
}

/**
 * Output the dropdown for bulk management of notifications.
 *
 * @since 2.2.0
 */
function bp_notifications_bulk_management_dropdown() {
	?>
	<label class="bp-screen-reader-text" for="notification-select"><?php
		/* translators: accessibility text */
		_e( 'Select Bulk Action', 'buddypress' );
	?></label>
	<select name="notification_bulk_action" id="notification-select">
		<option value="" selected="selected"><?php _e( 'Bulk Actions', 'buddypress' ); ?></option>

		<?php if ( bp_is_current_action( 'unread' ) ) : ?>
			<option value="read"><?php _e( 'Mark read', 'buddypress' ); ?></option>
		<?php elseif ( bp_is_current_action( 'read' ) ) : ?>
			<option value="unread"><?php _e( 'Mark unread', 'buddypress' ); ?></option>
		<?php endif; ?>
		<option value="delete"><?php _e( 'Delete', 'buddypress' ); ?></option>
	</select>
	<input type="submit" id="notification-bulk-manage" class="button action" value="<?php esc_attr_e( 'Apply', 'buddypress' ); ?>">
	<?php
}
