<?php
/**
 * BuddyPress Activity component admin screen.
 *
 * Props to WordPress core for the Comments admin screen, and its contextual
 * help text, on which this implementation is heavily based.
 *
 * @package BuddyPress
 * @subpackage ActivityAdmin
 * @since 1.6.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Include WP's list table class.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Register the Activity component admin screen.
 *
 * @since 1.6.0
 */
function bp_activity_add_admin_menu() {

	// Add our screen.
	$hook = add_menu_page(
		_x( 'Activity', 'Admin Dashboard SWA page title', 'buddypress' ),
		_x( 'Activity', 'Admin Dashboard SWA menu', 'buddypress' ),
		'bp_moderate',
		'bp-activity',
		'bp_activity_admin',
		'div'
	);

	// Hook into early actions to load custom CSS and our init handler.
	add_action( "load-$hook", 'bp_activity_admin_load' );
}
add_action( bp_core_admin_hook(), 'bp_activity_add_admin_menu' );

/**
 * Add activity component to custom menus array.
 *
 * Several BuddyPress components have top-level menu items in the Dashboard,
 * which all appear together in the middle of the Dashboard menu. This function
 * adds the Activity page to the array of these menu items.
 *
 * @since 1.7.0
 *
 * @param array $custom_menus The list of top-level BP menu items.
 * @return array $custom_menus List of top-level BP menu items, with Activity added.
 */
function bp_activity_admin_menu_order( $custom_menus = array() ) {
	array_push( $custom_menus, 'bp-activity' );
	return $custom_menus;
}
add_filter( 'bp_admin_menu_order', 'bp_activity_admin_menu_order' );

/**
 * AJAX receiver for Activity replies via the admin screen.
 *
 * Processes requests to add new activity comments, and echoes HTML for a new
 * table row.
 *
 * @since 1.6.0
 */
function bp_activity_admin_reply() {
	// Check nonce.
	check_ajax_referer( 'bp-activity-admin-reply', '_ajax_nonce-bp-activity-admin-reply' );

	$parent_id = ! empty( $_REQUEST['parent_id'] ) ? (int) $_REQUEST['parent_id'] : 0;
	$root_id   = ! empty( $_REQUEST['root_id'] )   ? (int) $_REQUEST['root_id']   : 0;

	// $parent_id is required.
	if ( empty( $parent_id ) ) {
		die( '-1' );
	}

	// If $root_id not set (e.g. for root items), use $parent_id.
	if ( empty( $root_id ) ) {
		$root_id = $parent_id;
	}

	// Check that a reply has been entered.
	if ( empty( $_REQUEST['content'] ) ) {
		die( esc_html__( 'Error: Please type a reply.', 'buddypress' ) );
	}

	// Check parent activity exists.
	$parent_activity = new BP_Activity_Activity( $parent_id );
	if ( empty( $parent_activity->component ) ) {
		die( esc_html__( 'Error: The item you are trying to reply to cannot be found, or it has been deleted.', 'buddypress' ) );
	}

	// @todo: Check if user is allowed to create new activity items
	// if ( ! current_user_can( 'bp_new_activity' ) )
	if ( ! bp_current_user_can( 'bp_moderate' ) ) {
		die( '-1' );
	}

	// Add new activity comment.
	$new_activity_id = bp_activity_new_comment( array(
		'activity_id' => $root_id,              // ID of the root activity item.
		'content'     => $_REQUEST['content'],
		'parent_id'   => $parent_id,            // ID of a parent comment.
	) );

	// Fetch the new activity item, as we need it to create table markup to return.
	$new_activity = new BP_Activity_Activity( $new_activity_id );

	// This needs to be set for the BP_Activity_List_Table constructor to work.
	set_current_screen( 'toplevel_page_bp-activity' );

	// Set up an output buffer.
	ob_start();
	$list_table = new BP_Activity_List_Table();
	$list_table->single_row( (array) $new_activity );

	// Get table markup.
	$response =  array(
		'data'     => ob_get_contents(),
		'id'       => $new_activity_id,
		'position' => -1,
		'what'     => 'bp_activity',
	);
	ob_end_clean();

	// Send response.
	$r = new WP_Ajax_Response();
	$r->add( $response );
	$r->send();

	exit();
}
add_action( 'wp_ajax_bp-activity-admin-reply', 'bp_activity_admin_reply' );

/**
 * Hide the advanced edit meta boxes by default, so we don't clutter the screen.
 *
 * @since 1.6.0
 *
 * @param array     $hidden Array of items to hide.
 * @param WP_Screen $screen Screen identifier.
 * @return array Hidden Meta Boxes.
 */
function bp_activity_admin_edit_hidden_metaboxes( $hidden, $screen ) {
	if ( empty( $screen->id ) || 'toplevel_page_bp-activity' !== $screen->id && 'toplevel_page_bp-activity-network' !== $screen->id ) {
		return $hidden;
	}

	// Hide the primary link meta box by default.
	$hidden  = array_merge( (array) $hidden, array( 'bp_activity_itemids', 'bp_activity_link', 'bp_activity_type', 'bp_activity_userid', ) );

	/**
	 * Filters default hidden metaboxes so plugins can alter list.
	 *
	 * @since 1.6.0
	 *
	 * @param array     $hidden Default metaboxes to hide.
	 * @param WP_Screen $screen Screen identifier.
	 */
	return apply_filters( 'bp_hide_meta_boxes', array_unique( $hidden ), $screen );
}
add_filter( 'default_hidden_meta_boxes', 'bp_activity_admin_edit_hidden_metaboxes', 10, 2 );

/**
 * Set up the Activity admin page.
 *
 * Does the following:
 *   - Register contextual help and screen options for this admin page.
 *   - Enqueues scripts and styles.
 *   - Catches POST and GET requests related to Activity.
 *
 * @since 1.6.0
 *
 * @global BP_Activity_List_Table $bp_activity_list_table Activity screen list table.
 */
function bp_activity_admin_load() {
	global $bp_activity_list_table;

	$bp       = buddypress();
	$doaction = bp_admin_list_table_current_bulk_action();
	$min      = bp_core_get_minified_asset_suffix();

	/**
	 * Fires at top of Activity admin page.
	 *
	 * @since 1.6.0
	 *
	 * @param string $doaction Current $_GET action being performed in admin screen.
	 */
	do_action( 'bp_activity_admin_load', $doaction );

	// Edit screen.
	if ( 'edit' == $doaction && ! empty( $_GET['aid'] ) ) {
		// Columns screen option.
		add_screen_option( 'layout_columns', array( 'default' => 2, 'max' => 2, ) );

		get_current_screen()->add_help_tab( array(
			'id'      => 'bp-activity-edit-overview',
			'title'   => __( 'Overview', 'buddypress' ),
			'content' =>
				'<p>' . __( 'You edit activities made on your site similar to the way you edit a comment. This is useful if you need to change which page the activity links to, or when you notice that the author has made a typographical error.', 'buddypress' ) . '</p>' .
				'<p>' . __( 'The two big editing areas for the activity title and content are fixed in place, but you can reposition all the other boxes using drag and drop, and can minimize or expand them by clicking the title bar of each box. Use the Screen Options tab to unhide more boxes (Primary Item/Secondary Item, Link, Type, Author ID) or to choose a 1- or 2-column layout for this screen.', 'buddypress' ) . '</p>' .
				'<p>' . __( 'You can also moderate the activity from this screen using the Status box, where you can also change the timestamp of the activity.', 'buddypress' ) . '</p>'
		) );

		get_current_screen()->add_help_tab( array(
			'id'      => 'bp-activity-edit-advanced',
			'title'   => __( 'Item, Link, Type', 'buddypress' ),
			'content' =>
				'<p>' . __( '<strong>Primary Item/Secondary Item</strong> - These identify the object that created the activity. For example, the fields could reference a comment left on a specific site. Some types of activity may only use one, or none, of these fields.', 'buddypress' ) . '</p>' .
				'<p>' . __( '<strong>Link</strong> - Used by some types of activity (blog posts and comments) to store a link back to the original content.', 'buddypress' ) . '</p>' .
				'<p>' . __( '<strong>Type</strong> - Each distinct kind of activity has its own type. For example, <code>created_group</code> is used when a group is created and <code>joined_group</code> is used when a user joins a group.', 'buddypress' ) . '</p>' .
				'<p>' . __( 'For information about when and how BuddyPress uses all of these settings, see the Managing Activity link in the panel to the side.', 'buddypress' ) . '</p>'
		) );

		// Help panel - sidebar links.
		get_current_screen()->set_help_sidebar(
			'<p><strong>' . __( 'For more information:', 'buddypress' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://codex.buddypress.org/administrator-guide/activity-stream-management-panels/">Managing Activity</a>', 'buddypress' ) . '</p>' .
			'<p>' . __( '<a href="https://buddypress.org/support/">Support Forums</a>', 'buddypress' ) . '</p>'
		);

		// Register metaboxes for the edit screen.
		add_meta_box( 'submitdiv',           _x( 'Status', 'activity admin edit screen', 'buddypress' ), 'bp_activity_admin_edit_metabox_status', get_current_screen()->id, 'side', 'core' );
		add_meta_box( 'bp_activity_itemids', _x( 'Primary Item/Secondary Item', 'activity admin edit screen', 'buddypress' ), 'bp_activity_admin_edit_metabox_itemids', get_current_screen()->id, 'normal', 'core' );
		add_meta_box( 'bp_activity_link',    _x( 'Link', 'activity admin edit screen', 'buddypress' ), 'bp_activity_admin_edit_metabox_link', get_current_screen()->id, 'normal', 'core' );
		add_meta_box( 'bp_activity_type',    _x( 'Type', 'activity admin edit screen', 'buddypress' ), 'bp_activity_admin_edit_metabox_type', get_current_screen()->id, 'normal', 'core' );
		add_meta_box( 'bp_activity_userid',  _x( 'Author ID', 'activity admin edit screen', 'buddypress' ), 'bp_activity_admin_edit_metabox_userid', get_current_screen()->id, 'normal', 'core' );

		/**
		 * Fires after the registration of all of the default activity meta boxes.
		 *
		 * @since 2.4.0
		 */
		do_action( 'bp_activity_admin_meta_boxes' );

		// Enqueue JavaScript files.
		wp_enqueue_script( 'postbox' );
		wp_enqueue_script( 'dashboard' );
		wp_enqueue_script( 'comment' );

	// Index screen.
	} else {
		// Create the Activity screen list table.
		$bp_activity_list_table = new BP_Activity_List_Table();

		// The per_page screen option.
		add_screen_option( 'per_page', array( 'label' => _x( 'Activity', 'Activity items per page (screen options)', 'buddypress' )) );

		// Help panel - overview text.
		get_current_screen()->add_help_tab( array(
			'id'      => 'bp-activity-overview',
			'title'   => __( 'Overview', 'buddypress' ),
			'content' =>
				'<p>' . __( 'You can manage activities made on your site similar to the way you manage comments and other content. This screen is customizable in the same ways as other management screens, and you can act on activities using the on-hover action links or the Bulk Actions.', 'buddypress' ) . '</p>' .
				'<p>' . __( 'There are many different types of activities. Some are generated automatically by BuddyPress and other plugins, and some are entered directly by a user in the form of status update. To help manage the different activity types, use the filter dropdown box to switch between them.', 'buddypress' ) . '</p>'
		) );

		// Help panel - moderation text.
		get_current_screen()->add_help_tab( array(
			'id'		=> 'bp-activity-moderating',
			'title'		=> __( 'Moderating Activity', 'buddypress' ),
			'content'	=>
				'<p>' . __( 'In the <strong>Activity</strong> column, above each activity it says &#8220;Submitted on,&#8221; followed by the date and time the activity item was generated on your site. Clicking on the date/time link will take you to that activity on your live site. Hovering over any activity gives you options to reply, edit, spam mark, or delete that activity.', 'buddypress' ) . '</p>' .
				'<p>' . __( "In the <strong>In Response To</strong> column, if the activity was in reply to another activity, it shows that activity's author's picture and name, and a link to that activity on your live site. If there is a small bubble, the number in it shows how many other activities are related to this one; these are usually comments. Clicking the bubble will filter the activity screen to show only related activity items.", 'buddypress' ) . '</p>'
		) );

		// Help panel - sidebar links.
		get_current_screen()->set_help_sidebar(
			'<p><strong>' . __( 'For more information:', 'buddypress' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://buddypress.org/support/">Support Forums</a>', 'buddypress' ) . '</p>'
		);

		// Add accessible hidden heading and text for Activity screen pagination.
		get_current_screen()->set_screen_reader_content( array(
			/* translators: accessibility text */
			'heading_pagination' => __( 'Activity list navigation', 'buddypress' ),
		) );

	}

	// Enqueue CSS and JavaScript.
	wp_enqueue_script( 'bp_activity_admin_js', $bp->plugin_url . "bp-activity/admin/js/admin{$min}.js",   array( 'jquery', 'wp-ajax-response' ), bp_get_version(), true );
	wp_localize_script( 'bp_activity_admin_js', 'bp_activity_admin_vars', array(
		'page' => get_current_screen()->id
	) );
	wp_enqueue_style( 'bp_activity_admin_css', $bp->plugin_url . "bp-activity/admin/css/admin{$min}.css", array(),                               bp_get_version()       );

	wp_style_add_data( 'bp_activity_admin_css', 'rtl', 'replace' );
	if ( $min ) {
		wp_style_add_data( 'bp_activity_admin_css', 'suffix', $min );
	}

	/**
	 * Fires after the activity js and style has been enqueued.
	 *
	 * @since 2.4.0
	 */
	do_action( 'bp_activity_admin_enqueue_scripts' );

	// Handle spam/un-spam/delete of activities.
	if ( ! empty( $doaction ) && ! in_array( $doaction, array( '-1', 'edit', 'save', 'delete', 'bulk_delete' ) ) ) {

		// Build redirection URL.
		$redirect_to = remove_query_arg( array( 'aid', 'deleted', 'error', 'spammed', 'unspammed', ), wp_get_referer() );
		$redirect_to = add_query_arg( 'paged', $bp_activity_list_table->get_pagenum(), $redirect_to );

		// Get activity IDs.
		$activity_ids = wp_parse_id_list( $_REQUEST['aid'] );

		/**
		 * Filters list of IDs being spammed/un-spammed/deleted.
		 *
		 * @since 1.6.0
		 *
		 * @param array $activity_ids Activity IDs to spam/un-spam/delete.
		 */
		$activity_ids = apply_filters( 'bp_activity_admin_action_activity_ids', $activity_ids );

		// Is this a bulk request?
		if ( 'bulk_' == substr( $doaction, 0, 5 ) && ! empty( $_REQUEST['aid'] ) ) {
			// Check this is a valid form submission.
			check_admin_referer( 'bulk-activities' );

			// Trim 'bulk_' off the action name to avoid duplicating a ton of code.
			$doaction = substr( $doaction, 5 );

			// This is a request to delete single or multiple item.
		} elseif ( 'do_delete'  === $doaction && ! empty( $_REQUEST['aid'] ) ) {
			check_admin_referer( 'bp-activities-delete' );

		// This is a request to spam, or un-spam, a single item.
		} elseif ( !empty( $_REQUEST['aid'] ) ) {

			// Check this is a valid form submission.
			check_admin_referer( 'spam-activity_' . $activity_ids[0] );
		}

		// Initialize counters for how many of each type of item we perform an action on.
		$deleted = $spammed = $unspammed = 0;

		// Store any errors that occurs when updating the database items.
		$errors = array();

		// "We'd like to shoot the monster, could you move, please?"
		foreach ( $activity_ids as $activity_id ) {
			// @todo: Check the permissions on each
			// if ( ! current_user_can( 'bp_edit_activity', $activity_id ) )
			// continue;
			// Get the activity from the database.
			$activity = new BP_Activity_Activity( $activity_id );
			if ( empty( $activity->component ) ) {
				$errors[] = $activity_id;
				continue;
			}

			switch ( $doaction ) {
				case 'do_delete' :
					if ( 'activity_comment' === $activity->type ) {
						$delete_result = bp_activity_delete_comment( $activity->item_id, $activity->id );
					} else {
						$delete_result = bp_activity_delete( array( 'id' => $activity->id ) );
					}

					if ( ! $delete_result ) {
						$errors[] = $activity->id;
					} else {
						$deleted++;
					}
					break;

				case 'ham' :
					/**
					 * Remove moderation and disallowed keyword checks in case we want to ham an activity
					 * which contains one of these listed keys.
					 */
					remove_action( 'bp_activity_before_save', 'bp_activity_check_moderation_keys', 2 );
					remove_action( 'bp_activity_before_save', 'bp_activity_check_disallowed_keys', 2 );

					bp_activity_mark_as_ham( $activity );
					$result = $activity->save();

					// Check for any error during activity save.
					if ( ! $result ) {
						$errors[] = $activity->id;
					} else {
						$unspammed++;
					}
					break;

				case 'spam' :
					bp_activity_mark_as_spam( $activity );
					$result = $activity->save();

					// Check for any error during activity save.
					if ( ! $result ) {
						$errors[] = $activity->id;
					} else {
						$spammed++;
					}
					break;

				default:
					break;
			}

			// Release memory.
			unset( $activity );
		}

		/**
		 * Fires before redirect for plugins to do something with activity.
		 *
		 * Passes an activity array counts how many were spam, not spam, deleted, and IDs that were errors.
		 *
		 * @since 1.6.0
		 *
		 * @param array  $value        Array holding spam, not spam, deleted counts, error IDs.
		 * @param string $redirect_to  URL to redirect to.
		 * @param array  $activity_ids Original array of activity IDs.
		 */
		do_action( 'bp_activity_admin_action_after', array( $spammed, $unspammed, $deleted, $errors ), $redirect_to, $activity_ids );

		// Add arguments to the redirect URL so that on page reload, we can easily display what we've just done.
		if ( $spammed ) {
			$redirect_to = add_query_arg( 'spammed', $spammed, $redirect_to );
		}

		if ( $unspammed ) {
			$redirect_to = add_query_arg( 'unspammed', $unspammed, $redirect_to );
		}

		if ( $deleted ) {
			$redirect_to = add_query_arg( 'deleted', $deleted, $redirect_to );
		}

		// If an error occurred, pass back the activity ID that failed.
		if ( ! empty( $errors ) ) {
			$redirect_to = add_query_arg( 'error', implode ( ',', array_map( 'absint', $errors ) ), $redirect_to );
		}

		/**
		 * Filters redirect URL after activity spamming/un-spamming/deletion occurs.
		 *
		 * @since 1.6.0
		 *
		 * @param string $redirect_to URL to redirect to.
		 */
		wp_safe_redirect( apply_filters( 'bp_activity_admin_action_redirect', $redirect_to ) );
		exit;


	// Save the edit.
	} elseif ( $doaction && 'save' == $doaction ) {
		// Build redirection URL.
		$redirect_to = remove_query_arg( array( 'action', 'aid', 'deleted', 'error', 'spammed', 'unspammed', ), $_SERVER['REQUEST_URI'] );

		// Get activity ID.
		$activity_id = (int) $_REQUEST['aid'];

		// Check this is a valid form submission.
		check_admin_referer( 'edit-activity_' . $activity_id );

		// Get the activity from the database.
		$activity = new BP_Activity_Activity( $activity_id );

		// If the activity doesn't exist, just redirect back to the index.
		if ( empty( $activity->component ) ) {
			wp_safe_redirect( $redirect_to );
			exit;
		}

		// Check the form for the updated properties.
		// Store any error that occurs when updating the database item.
		$error = 0;

		// Activity spam status.
		$prev_spam_status = $new_spam_status = false;
		if ( ! empty( $_POST['activity_status'] ) ) {
			$prev_spam_status = $activity->is_spam;
			$new_spam_status  = ( 'spam' == $_POST['activity_status'] ) ? true : false;
		}

		// Activity action.
		if ( isset( $_POST['bp-activities-action'] ) )
			$activity->action = $_POST['bp-activities-action'];

		// Activity content.
		if ( isset( $_POST['bp-activities-content'] ) )
			$activity->content = $_POST['bp-activities-content'];

		// Activity primary link.
		if ( ! empty( $_POST['bp-activities-link'] ) )
			$activity->primary_link = $_POST['bp-activities-link'];

		// Activity user ID.
		if ( ! empty( $_POST['bp-activities-userid'] ) )
			$activity->user_id = (int) $_POST['bp-activities-userid'];

		// Activity item primary ID.
		if ( isset( $_POST['bp-activities-primaryid'] ) )
			$activity->item_id = (int) $_POST['bp-activities-primaryid'];

		// Activity item secondary ID.
		if ( isset( $_POST['bp-activities-secondaryid'] ) )
			$activity->secondary_item_id = (int) $_POST['bp-activities-secondaryid'];

		// Activity type.
		if ( ! empty( $_POST['bp-activities-type'] ) ) {
			$actions = bp_activity_admin_get_activity_actions();

			// Check that the new type is a registered activity type.
			if ( in_array( $_POST['bp-activities-type'], $actions ) ) {
				$activity->type = $_POST['bp-activities-type'];
			}
		}

		// Activity timestamp.
		if ( ! empty( $_POST['aa'] ) && ! empty( $_POST['mm'] ) && ! empty( $_POST['jj'] ) && ! empty( $_POST['hh'] ) && ! empty( $_POST['mn'] ) && ! empty( $_POST['ss'] ) ) {
			$aa = $_POST['aa'];
			$mm = $_POST['mm'];
			$jj = $_POST['jj'];
			$hh = $_POST['hh'];
			$mn = $_POST['mn'];
			$ss = $_POST['ss'];
			$aa = ( $aa <= 0 ) ? date( 'Y' ) : $aa;
			$mm = ( $mm <= 0 ) ? date( 'n' ) : $mm;
			$jj = ( $jj > 31 ) ? 31 : $jj;
			$jj = ( $jj <= 0 ) ? date( 'j' ) : $jj;
			$hh = ( $hh > 23 ) ? $hh -24 : $hh;
			$mn = ( $mn > 59 ) ? $mn -60 : $mn;
			$ss = ( $ss > 59 ) ? $ss -60 : $ss;

			// Reconstruct the date into a timestamp.
			$gmt_date = sprintf( "%04d-%02d-%02d %02d:%02d:%02d", $aa, $mm, $jj, $hh, $mn, $ss );

			$activity->date_recorded = $gmt_date;
		}

		// Has the spam status has changed?
		if ( $new_spam_status != $prev_spam_status ) {
			if ( $new_spam_status )
				bp_activity_mark_as_spam( $activity );
			else
				bp_activity_mark_as_ham( $activity );
		}

		// Save.
		$result = $activity->save();

		// Clear the activity stream first page cache, in case this activity's timestamp was changed.
		wp_cache_delete( 'bp_activity_sitewide_front', 'bp' );

		// Check for any error during activity save.
		if ( false === $result )
			$error = $activity->id;

		/**
		 * Fires before redirect so plugins can do something first on save action.
		 *
		 * @since 1.6.0
		 *
		 * @param array $value Array holding activity object and ID that holds error.
		 */
		do_action_ref_array( 'bp_activity_admin_edit_after', array( &$activity, $error ) );

		// If an error occurred, pass back the activity ID that failed.
		if ( $error ) {
			$redirect_to = add_query_arg( 'error', $error, $redirect_to );
		} else {
			$redirect_to = add_query_arg( 'updated', $activity->id, $redirect_to );
		}

		/**
		 * Filters URL to redirect to after saving.
		 *
		 * @since 1.6.0
		 *
		 * @param string $redirect_to URL to redirect to.
		 */
		wp_safe_redirect( apply_filters( 'bp_activity_admin_edit_redirect', $redirect_to ) );
		exit;


	// If a referrer and a nonce is supplied, but no action, redirect back.
	} elseif ( ! empty( $_GET['_wp_http_referer'] ) ) {
		wp_safe_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), stripslashes( $_SERVER['REQUEST_URI'] ) ) );
		exit;
	}
}

/**
 * Output the Activity component admin screens.
 *
 * @since 1.6.0
 */
function bp_activity_admin() {
	// Decide whether to load the index or edit screen.
	$doaction = bp_admin_list_table_current_bulk_action();

	// Display the single activity edit screen.
	if ( 'edit' === $doaction && ! empty( $_GET['aid'] ) ) {
		bp_activity_admin_edit();

	// Display the activty delete confirmation screen.
	} elseif ( in_array( $doaction, array( 'bulk_delete', 'delete' ) ) && ! empty( $_GET['aid'] ) ) {
		bp_activity_admin_delete();

	// Otherwise, display the Activity index screen.
	} else {
		bp_activity_admin_index();
	}
}

/**
 * Display the Activity delete confirmation screen.
 *
 * @since 7.0.0
 */
function bp_activity_admin_delete() {

	if ( ! bp_current_user_can( 'bp_moderate' ) ) {
		die( '-1' );
	}

	$activity_ids = isset( $_REQUEST['aid'] ) ? $_REQUEST['aid'] : 0;

	if ( ! is_array( $activity_ids ) ) {
		$activity_ids = explode( ',', $activity_ids );
	}

	$activities = bp_activity_get( array(
		'in'               => $activity_ids,
		'show_hidden'      => true,
		'spam'             => 'all',
		'display_comments' => 0,
		'per_page'         => null
	) );

	// Create a new list of activity ids, based on those that actually exist.
	$aids = array();
	foreach ( $activities['activities'] as $activity ) {
		$aids[] = $activity->id;
	}

	$base_url = remove_query_arg( array( 'action', 'action2', 'paged', 's', '_wpnonce', 'aid' ), $_SERVER['REQUEST_URI'] ); ?>

	<div class="wrap">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Delete Activities', 'buddypress' ) ?></h1>
		<hr class="wp-header-end">

		<p><?php esc_html_e( 'You are about to delete the following activities:', 'buddypress' ) ?></p>

		<ul class="bp-activity-delete-list">
		<?php foreach ( $activities['activities'] as $activity ) : ?>
			<li>
			<?php
			$actions = bp_activity_admin_get_activity_actions();

			if ( isset( $actions[ $activity->type ] ) ) {
				$activity_type =  $actions[ $activity->type ];
			} else {
				/* translators: %s: the name of the activity type */
				$activity_type = sprintf( __( 'Unregistered action - %s', 'buddypress' ), $activity->type );
			}

			printf(
				/* translators: 1: activity type. 2: activity author. 3: activity date and time. */
				esc_html__( '"%1$s" activity submitted by %2$s on %3$s', 'buddypress' ),
				esc_html( $activity_type ),
				// phpcs:ignore WordPress.Security.EscapeOutput
				bp_core_get_userlink( $activity->user_id ),
				sprintf(
					'<a href="%1$s">%2$s</a>',
					esc_url( bp_activity_get_permalink( $activity->id, $activity ) ),
					esc_html( date_i18n( bp_get_option( 'date_format' ), strtotime( $activity->date_recorded ) ) )
				)
			);
			?>
			</li>
		<?php endforeach; ?>
		</ul>

		<p><strong><?php esc_html_e( 'This action cannot be undone.', 'buddypress' ) ?></strong></p>

		<a class="button-primary" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'do_delete', 'aid' => implode( ',', $aids ) ), $base_url ), 'bp-activities-delete' ) ); ?>"><?php esc_html_e( 'Delete Permanently', 'buddypress' ) ?></a>
		<a class="button" href="<?php echo esc_attr( $base_url ); ?>"><?php esc_html_e( 'Cancel', 'buddypress' ) ?></a>
	</div>

	<?php
}


/**
 * Display the single activity edit screen.
 *
 * @since 1.6.0
 */
function bp_activity_admin_edit() {

	// @todo: Check if user is allowed to edit activity items
	// if ( ! current_user_can( 'bp_edit_activity' ) )
	if ( ! is_super_admin() )
		die( '-1' );

	// Get the activity from the database.
	$activity = bp_activity_get( array(
		'in'               => ! empty( $_REQUEST['aid'] ) ? (int) $_REQUEST['aid'] : 0,
		'max'              => 1,
		'show_hidden'      => true,
		'spam'             => 'all',
		'display_comments' => 0
	) );

	if ( ! empty( $activity['activities'][0] ) ) {
		$activity = $activity['activities'][0];

		// Workaround to use WP's touch_time() without duplicating that function.
		$GLOBALS['comment'] = new stdClass;
		$GLOBALS['comment']->comment_date = $activity->date_recorded;
	} else {
		$activity = '';
	}

	// Construct URL for form.
	$form_url = remove_query_arg( array( 'action', 'deleted', 'error', 'spammed', 'unspammed', ), $_SERVER['REQUEST_URI'] );
	$form_url = add_query_arg( 'action', 'save', $form_url );

	/**
	 * Fires before activity edit form is displays so plugins can modify the activity.
	 *
	 * @since 1.6.0
	 *
	 * @param array $value Array holding single activity object that was passed by reference.
	 */
	do_action_ref_array( 'bp_activity_admin_edit', array( &$activity ) ); ?>

	<div class="wrap">
		<h1 class="wp-heading-inline">
			<?php
			/* translators: %s: the activity ID */
			printf( esc_html__( 'Editing Activity (ID #%s)', 'buddypress' ), esc_html( number_format_i18n( (int) $_REQUEST['aid'] ) ) );
			?>
		</h1>

		<hr class="wp-header-end">

		<?php if ( ! empty( $activity ) ) : ?>

			<form action="<?php echo esc_url( $form_url ); ?>" id="bp-activities-edit-form" method="post">
				<div id="poststuff">

					<div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">
						<div id="post-body-content">
							<div id="postdiv">
								<div id="bp_activity_action" class="activitybox">
									<h2><?php esc_html_e( 'Action', 'buddypress' ); ?></h2>
									<div class="inside">
										<label for="bp-activities-action" class="screen-reader-text">
											<?php
												/* translators: accessibility text */
												esc_html_e( 'Edit activity action', 'buddypress' );
											?>
										</label>
										<?php wp_editor( stripslashes( $activity->action ), 'bp-activities-action', array( 'media_buttons' => false, 'textarea_rows' => 7, 'teeny' => true, 'quicktags' => array( 'buttons' => 'strong,em,link,block,del,ins,img,code,spell,close' ) ) ); ?>
									</div>
								</div>

								<div id="bp_activity_content" class="activitybox">
									<h2><?php esc_html_e( 'Content', 'buddypress' ); ?></h2>
									<div class="inside">
										<label for="bp-activities-content" class="screen-reader-text">
											<?php
												/* translators: accessibility text */
												esc_html_e( 'Edit activity content', 'buddypress' );
											?>
										</label>
										<?php wp_editor( stripslashes( $activity->content ), 'bp-activities-content', array( 'media_buttons' => false, 'teeny' => true, 'quicktags' => array( 'buttons' => 'strong,em,link,block,del,ins,img,code,spell,close' ) ) ); ?>
									</div>
								</div>
							</div>
						</div><!-- #post-body-content -->

						<div id="postbox-container-1" class="postbox-container">
							<?php do_meta_boxes( get_current_screen()->id, 'side', $activity ); ?>
						</div>

						<div id="postbox-container-2" class="postbox-container">
							<?php do_meta_boxes( get_current_screen()->id, 'normal', $activity ); ?>
							<?php do_meta_boxes( get_current_screen()->id, 'advanced', $activity ); ?>
						</div>
					</div><!-- #post-body -->

				</div><!-- #poststuff -->
				<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
				<?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>
				<?php wp_nonce_field( 'edit-activity_' . $activity->id ); ?>
			</form>

		<?php else : ?>

			<p><?php
				printf(
					'%1$s <a href="%2$s">%3$s</a>',
					esc_html__( 'No activity found with this ID.', 'buddypress' ),
					esc_url( bp_get_admin_url( 'admin.php?page=bp-activity' ) ),
					esc_html__( 'Go back and try again.', 'buddypress' )
				);
			?></p>

		<?php endif; ?>

	</div><!-- .wrap -->

<?php
}

/**
 * Status metabox for the Activity admin edit screen.
 *
 * @since 1.6.0
 *
 * @param object $item Activity item.
 */
function bp_activity_admin_edit_metabox_status( $item ) {
	$base_url = add_query_arg( array(
		'page' => 'bp-activity',
		'aid'  => $item->id
	), bp_get_admin_url( 'admin.php' ) );
?>

	<div class="submitbox" id="submitcomment">

		<div id="minor-publishing">
			<div id="minor-publishing-actions">
				<div id="preview-action">
					<a class="button preview" href="<?php echo esc_url( bp_activity_get_permalink( $item->id, $item ) ); ?>" target="_blank"><?php esc_html_e( 'View Activity', 'buddypress' ); ?></a>
				</div>

				<div class="clear"></div>
			</div><!-- #minor-publishing-actions -->

			<div id="misc-publishing-actions">
				<div class="misc-pub-section" id="comment-status-radio">
					<label class="approved" for="activity-status-approved"><input type="radio" name="activity_status" id="activity-status-approved" value="ham" <?php checked( $item->is_spam, 0 ); ?>><?php esc_html_e( 'Approved', 'buddypress' ); ?></label><br />
					<label class="spam" for="activity-status-spam"><input type="radio" name="activity_status" id="activity-status-spam" value="spam" <?php checked( $item->is_spam, 1 ); ?>><?php esc_html_e( 'Spam', 'buddypress' ); ?></label>
				</div>

				<div class="misc-pub-section curtime misc-pub-section-last">
					<?php
					// Translators: Publish box date format, see http://php.net/date.
					$datef = __( 'M j, Y @ G:i', 'buddypress' );
					$date  = date_i18n( $datef, strtotime( $item->date_recorded ) );
					?>
					<span id="timestamp">
						<?php
						/* translators: %s: the date the activity was submitted on */
						printf( esc_html__( 'Submitted on: %s', 'buddypress' ), '<strong>' . esc_html( $date ) . '</strong>' );
						?>
					</span>&nbsp;<a href="#edit_timestamp" class="edit-timestamp hide-if-no-js" tabindex='4'><?php esc_html_e( 'Edit', 'buddypress' ); ?></a>

					<div id='timestampdiv' class='hide-if-js'>
						<?php touch_time( 1, 0, 5 ); ?>
					</div><!-- #timestampdiv -->
				</div>
			</div> <!-- #misc-publishing-actions -->

			<div class="clear"></div>
		</div><!-- #minor-publishing -->

		<div id="major-publishing-actions">
			<div id="delete-action">
				<a class="submitdelete deletion" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'action', 'delete', $base_url ), 'bp-activities-delete' ) ); ?>"><?php esc_html_e( 'Delete Permanently', 'buddypress' ) ?></a>
			</div>

			<div id="publishing-action">
				<?php submit_button( __( 'Update', 'buddypress' ), 'primary', 'save', false ); ?>
			</div>
			<div class="clear"></div>
		</div><!-- #major-publishing-actions -->

	</div><!-- #submitcomment -->

<?php
}

/**
 * Primary link metabox for the Activity admin edit screen.
 *
 * @since 1.6.0
 *
 * @param object $item Activity item.
 */
function bp_activity_admin_edit_metabox_link( $item ) {
?>

	<label class="screen-reader-text" for="bp-activities-link">
		<?php
			/* translators: accessibility text */
			esc_html_e( 'Link', 'buddypress' );
		?>
	</label>
	<input type="url" name="bp-activities-link" id="bp-activities-link" value="<?php echo esc_url( $item->primary_link ); ?>" aria-describedby="bp-activities-link-description" />
	<p id="bp-activities-link-description"><?php esc_html_e( 'Activity generated by posts and comments uses the link field for a permalink back to the content item.', 'buddypress' ); ?></p>

<?php
}

/**
 * User ID metabox for the Activity admin edit screen.
 *
 * @since 1.6.0
 *
 * @param object $item Activity item.
 */
function bp_activity_admin_edit_metabox_userid( $item ) {
?>

	<label class="screen-reader-text" for="bp-activities-userid">
		<?php
			/* translators: accessibility text */
			esc_html_e( 'Author ID', 'buddypress' );
		?>
	</label>
	<input type="number" name="bp-activities-userid" id="bp-activities-userid" value="<?php echo esc_attr( $item->user_id ); ?>" min="1" />

<?php
}

/**
 * Get flattened array of all registered activity actions.
 *
 * Format is [activity_type] => Pretty name for activity type.
 *
 * @since 2.0.0
 *
 * @return array $actions
 */
function bp_activity_admin_get_activity_actions() {
	$actions  = array();

	// Walk through the registered actions, and build an array of actions/values.
	foreach ( bp_activity_get_actions() as $action ) {
		$action = array_values( (array) $action );

		for ( $i = 0, $i_count = count( $action ); $i < $i_count; $i++ ) {
			/**
			 * Don't take in account:
			 * - a mis-named Friends activity type from before BP 1.6,
			 * - The Group's component 'activity_update' one as the Activity component is using it.
			 */
			if ( 'friends_register_activity_action' === $action[$i]['key'] || 'bp_groups_format_activity_action_group_activity_update' === $action[$i]['format_callback'] ) {
				continue;
			}

			$actions[ $action[$i]['key'] ] = $action[$i]['value'];
		}
	}

	// Sort array by the human-readable value.
	natsort( $actions );

	return $actions;
}

/**
 * Activity type metabox for the Activity admin edit screen.
 *
 * @since 1.6.0
 *
 * @param object $item Activity item.
 */
function bp_activity_admin_edit_metabox_type( $item ) {

	$actions  = array();
	$selected = $item->type;

	// Walk through the registered actions, and build an array of actions/values.
	foreach ( bp_activity_get_actions() as $action ) {
		$action = array_values( (array) $action );

		for ( $i = 0, $i_count = count( $action ); $i < $i_count; $i++ ) {
			/**
			 * Don't take in account:
			 * - a mis-named Friends activity type from before BP 1.6,
			 * - The Group's component 'activity_update' one as the Activity component is using it.
			 */
			if ( 'friends_register_activity_action' === $action[$i]['key'] || 'bp_groups_format_activity_action_group_activity_update' === $action[$i]['format_callback'] ) {
				continue;
			}

			$actions[ $action[$i]['key'] ] = $action[$i]['value'];
		}
	}

	// Sort array by the human-readable value.
	natsort( $actions );

	/*
	 * If the activity type is not registered properly (eg, a plugin has
	 * not called bp_activity_set_action()), add the raw type to the end
	 * of the list.
	 */
	if ( ! isset( $actions[ $selected ] ) ) {
		_doing_it_wrong(
			__FUNCTION__,
			sprintf(
				/* translators: %s: the name of the activity type */
				esc_html__( 'This activity item has a type (%s) that is not registered using bp_activity_set_action(), so no label is available.', 'buddypress' ),
				esc_html( $selected )
			),
			'2.0.0'
		);

		$actions[ $selected ] = $selected;
	}

	?>

	<label for="bp-activities-type" class="screen-reader-text">
		<?php
			/* translators: accessibility text */
			esc_html_e( 'Select activity type', 'buddypress' );
		?>
	</label>
	<select name="bp-activities-type" id="bp-activities-type">
		<?php foreach ( $actions as $k => $v ) : ?>
			<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $k,  $selected ); ?>><?php echo esc_html( $v ); ?></option>
		<?php endforeach; ?>
	</select>

<?php
}

/**
 * Primary item ID/Secondary item ID metabox for the Activity admin edit screen.
 *
 * @since 1.6.0
 *
 * @param object $item Activity item.
 */
function bp_activity_admin_edit_metabox_itemids( $item ) {
?>

	<label for="bp-activities-primaryid"><?php esc_html_e( 'Primary Item ID', 'buddypress' ); ?></label>
	<input type="number" name="bp-activities-primaryid" id="bp-activities-primaryid" value="<?php echo esc_attr( $item->item_id ); ?>" min="0" />
	<br />

	<label for="bp-activities-secondaryid"><?php esc_html_e( 'Secondary Item ID', 'buddypress' ); ?></label>
	<input type="number" name="bp-activities-secondaryid" id="bp-activities-secondaryid" value="<?php echo esc_attr( $item->secondary_item_id ); ?>" min="0" />

	<p><?php esc_html_e( 'These identify the object that created this activity. For example, the fields could reference a pair of site and comment IDs.', 'buddypress' ); ?></p>

<?php
}

/**
 * Display the Activity admin index screen, which contains a list of all the activities.
 *
 * @since 1.6.0
 *
 * @global BP_Activity_List_Table $bp_activity_list_table Activity screen list table.
 * @global string                 $plugin_page            The current plugin page.
 */
function bp_activity_admin_index() {
	global $bp_activity_list_table, $plugin_page;

	$messages = array();

	// If the user has just made a change to an activity item, build status messages.
	if ( ! empty( $_REQUEST['deleted'] ) || ! empty( $_REQUEST['spammed'] ) || ! empty( $_REQUEST['unspammed'] ) || ! empty( $_REQUEST['error'] ) || ! empty( $_REQUEST['updated'] ) ) {
		$deleted   = ! empty( $_REQUEST['deleted']   ) ? (int) $_REQUEST['deleted']   : 0;
		$errors    = ! empty( $_REQUEST['error']     ) ? $_REQUEST['error']           : '';
		$spammed   = ! empty( $_REQUEST['spammed']   ) ? (int) $_REQUEST['spammed']   : 0;
		$unspammed = ! empty( $_REQUEST['unspammed'] ) ? (int) $_REQUEST['unspammed'] : 0;
		$updated   = ! empty( $_REQUEST['updated']   ) ? (int) $_REQUEST['updated']   : 0;

		$errors = array_map( 'absint', explode( ',', $errors ) );

		// Make sure we don't get any empty values in $errors.
		for ( $i = 0, $errors_count = count( $errors ); $i < $errors_count; $i++ ) {
			if ( 0 === $errors[$i] ) {
				unset( $errors[$i] );
			}
		}

		// Reindex array.
		$errors = array_values( $errors );

		if ( $deleted > 0 ) {
			/* translators: %s: the number of permanently deleted activities */
			$messages[] = sprintf( _n( '%s activity item has been permanently deleted.', '%s activity items have been permanently deleted.', $deleted, 'buddypress' ), number_format_i18n( $deleted ) );
		}

		if ( ! empty( $errors ) ) {
			if ( 1 == count( $errors ) ) {
				/* translators: %s: the ID of the activity which errored during an update */
				$messages[] = sprintf( __( 'An error occurred when trying to update activity ID #%s.', 'buddypress' ), number_format_i18n( $errors[0] ) );

			} else {
				$error_msg  = __( 'Errors occurred when trying to update these activity items:', 'buddypress' );
				$error_msg .= '<ul class="activity-errors">';

				// Display each error as a list item.
				foreach ( $errors as $error ) {
					/* Translators: %s: the activity ID */
					$error_msg .= '<li>' . sprintf( __( '#%s', 'buddypress' ), number_format_i18n( $error ) ) . '</li>';
				}

				$error_msg  .= '</ul>';
				$messages[] = $error_msg;
			}
		}

		if ( $spammed > 0 ) {
			/* translators: %s: the number of activities successfully marked as spam */
			$messages[] = sprintf( _n( '%s activity item has been successfully spammed.', '%s activity items have been successfully spammed.', $spammed, 'buddypress' ), number_format_i18n( $spammed ) );
		}

		if ( $unspammed > 0 ) {
			/* translators: %s: the number of activities successfully marked as ham */
			$messages[] = sprintf( _n( '%s activity item has been successfully unspammed.', '%s activity items have been successfully unspammed.', $unspammed, 'buddypress' ), number_format_i18n( $unspammed ) );
		}

		if ( $updated > 0 ) {
			$messages[] = __( 'The activity item has been updated successfully.', 'buddypress' );
		}
	}

	// Prepare the activity items for display.
	$bp_activity_list_table->prepare_items();

	/**
	 * Fires before edit form is displayed so plugins can modify the activity messages.
	 *
	 * @since 1.6.0
	 *
	 * @param array $messages Array of messages to display at top of page.
	 */
	do_action( 'bp_activity_admin_index', $messages ); ?>

	<div class="wrap">
		<h1 class="wp-heading-inline">
			<?php if ( !empty( $_REQUEST['aid'] ) ) : ?>
				<?php
				/* translators: %s: the activity ID */
				printf( esc_html__( 'Activity related to ID #%s', 'buddypress' ), esc_html( number_format_i18n( (int) $_REQUEST['aid'] ) ) );
				?>
			<?php else : ?>
				<?php echo esc_html_x( 'Activity', 'Admin SWA page', 'buddypress' ); ?>
			<?php endif; ?>

			<?php if ( !empty( $_REQUEST['s'] ) ) : ?>
				<span class="subtitle">
					<?php
					/* translators: %s: the activity search terms */
					printf( esc_html__( 'Search results for &#8220;%s&#8221;', 'buddypress' ), esc_html( wp_html_excerpt( stripslashes( $_REQUEST['s'] ), 50 ) ) );
					?>
				</span>
			<?php endif; ?>
		</h1>

		<hr class="wp-header-end">

		<?php // If the user has just made a change to an activity item, display the status messages. ?>
		<?php if ( !empty( $messages ) ) : ?>
			<div id="moderated" class="<?php echo ( ! empty( $_REQUEST['error'] ) ) ? 'error' : 'updated'; ?> notice is-dismissible"><p><?php echo implode( "<br/>\n", array_map( 'esc_html', $messages ) ); ?></p></div>
		<?php endif; ?>

		<?php // Display each activity on its own row. ?>
		<?php $bp_activity_list_table->views(); ?>

		<form id="bp-activities-form" action="" method="get">
			<?php $bp_activity_list_table->search_box( esc_html__( 'Search all Activity', 'buddypress' ), 'bp-activity' ); ?>
			<input type="hidden" name="page" value="<?php echo esc_attr( $plugin_page ); ?>" />
			<?php $bp_activity_list_table->display(); ?>
		</form>

		<?php // This markup is used for the reply form. ?>
		<table style="display: none;">
			<tr id="bp-activities-container" style="display: none;">
				<td colspan="4">
					<form method="get" action="">

						<h3 id="bp-replyhead"><?php esc_html_e( 'Reply to Activity', 'buddypress' ); ?></h3>
						<label for="bp-activities" class="screen-reader-text">
							<?php
								/* translators: accessibility text */
								esc_html_e( 'Reply', 'buddypress' );
							?>
						</label>
						<?php wp_editor( '', 'bp-activities', array( 'dfw' => false, 'media_buttons' => false, 'quicktags' => array( 'buttons' => 'strong,em,link,block,del,ins,img,code,spell,close' ), 'tinymce' => false, ) ); ?>

						<p id="bp-replysubmit" class="submit">
							<a href="#" class="cancel button-secondary alignleft"><?php esc_html_e( 'Cancel', 'buddypress' ); ?></a>
							<a href="#" class="save button-primary alignright"><?php esc_html_e( 'Reply', 'buddypress' ); ?></a>

							<img class="waiting" style="display:none;" src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" alt="" />
							<span class="error" style="display:none;"></span>
							<br class="clear" />
						</p>

						<?php wp_nonce_field( 'bp-activity-admin-reply', '_ajax_nonce-bp-activity-admin-reply', false ); ?>

					</form>
				</td>
			</tr>
		</table>
	</div>

<?php
}
