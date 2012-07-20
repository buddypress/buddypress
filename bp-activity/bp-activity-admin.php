<?php
/**
 * BuddyPress Activity component admin screen
 *
 * Props to WordPress core for the Comments admin screen, and its contextual help text,
 * on which this implementation is heavily based.
 *
 * @package BuddyPress
 * @since 1.6
 * @subpackage Activity
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

// Include WP's list table class
if ( !class_exists( 'WP_List_Table' ) ) require( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

// per_page screen option. Has to be hooked in extremely early.
if ( is_admin() && ! empty( $_REQUEST['page'] ) && 'bp-activity' == $_REQUEST['page'] )
	add_filter( 'set-screen-option', 'bp_activity_admin_screen_options', 10, 3 );

/**
 * Registers the Activity component admin screen
 *
 * @since 1.6
 */
function bp_activity_add_admin_menu() {

	if ( ! bp_current_user_can( 'bp_moderate' ) )
		return;

	// Add our screen
	$hook = add_menu_page( __( 'Activity', 'buddypress' ), __( 'Activity', 'buddypress' ), 'manage_options', 'bp-activity', 'bp_activity_admin' );

	// Hook into early actions to load custom CSS and our init handler.
	add_action( "load-$hook", 'bp_activity_admin_load' );
}
add_action( bp_core_admin_hook(), 'bp_activity_add_admin_menu' );

/**
 * AJAX receiver for Activity replies via the admin screen. Adds a new activity
 * comment, and returns HTML for a new table row.
 *
 * @since 1.6
 */
function bp_activity_admin_reply() {
	// Check nonce
	check_ajax_referer( 'bp-activity-admin-reply', '_ajax_nonce-bp-activity-admin-reply' );

	$parent_id = ! empty( $_REQUEST['parent_id'] ) ? (int) $_REQUEST['parent_id'] : 0;
	$root_id   = ! empty( $_REQUEST['root_id'] )   ? (int) $_REQUEST['root_id']   : 0;

	// $parent_id is required
	if ( empty( $parent_id ) )
		die( '-1' );

	// If $root_id not set (e.g. for root items), use $parent_id
	if ( empty( $root_id ) )
		$root_id = $parent_id;

	// Check that a reply has been entered
	if ( empty( $_REQUEST['content'] ) )
		die( __( 'ERROR: Please type a reply.', 'buddypress' ) );

	// Check parent activity exists
	$parent_activity = new BP_Activity_Activity( $parent_id );
	if ( empty( $parent_activity->component ) )
		die( __( 'ERROR: The item you are trying to reply to cannot be found, or it has been deleted.', 'buddypress' ) );

	// @todo: Check if user is allowed to create new activity items
	// if ( ! current_user_can( 'bp_new_activity' ) )
	if ( ! is_super_admin() )
		die( '-1' );

	// Add new activity comment
	$new_activity_id = bp_activity_new_comment( array(
		'activity_id' => $root_id,              // ID of the root activity item
		'content'     => $_REQUEST['content'],
		'parent_id'   => $parent_id,            // ID of a parent comment
	) );

	// Fetch the new activity item, as we need it to create table markup to return
	$new_activity = new BP_Activity_Activity( $new_activity_id );

	// This needs to be set for the BP_Activity_List_Table constructor to work
	set_current_screen( 'toplevel_page_bp-activity' );

	// Set up an output buffer
	ob_start();
	$list_table = new BP_Activity_List_Table();
	$list_table->single_row( (array) $new_activity );

	// Get table markup
	$response =  array(
		'data'     => ob_get_contents(),
		'id'       => $new_activity_id,
		'position' => -1,
		'what'     => 'bp_activity',
	);
	ob_end_clean();

	// Send response
	$r = new WP_Ajax_Response();
	$r->add( $response );
	$r->send();

	exit();
}
add_action( 'wp_ajax_bp-activity-admin-reply', 'bp_activity_admin_reply' );

/**
 * Handle save/update of screen options for the Activity component admin screen
 *
 * @param string $value Will always be false unless another plugin filters it first.
 * @param string $option Screen option name
 * @param string $new_value Screen option form value
 * @return string Option value. False to abandon update.
 * @since 1.6
 */
function bp_activity_admin_screen_options( $value, $option, $new_value ) {
	if ( 'toplevel_page_bp_activity_per_page' != $option && 'toplevel_page_bp_activity_network_per_page' != $option )
		return $value;

	// Per page
	$new_value = (int) $new_value;
	if ( $new_value < 1 || $new_value > 999 )
		return $value;

	return $new_value;
}

/**
 * Hide the advanced edit meta boxes by default, so we don't clutter the scren.
 *
 * @param WP_Screen $screen Screen identifier
 * @return array Hidden Meta Boxes
 * @since 1.0
 */
function bp_activity_admin_edit_hidden_metaboxes( $hidden, $screen ) {
	if ( empty( $screen->id ) || 'toplevel_page_bp-activity' != $screen->id && 'toplevel_page_bp-activity_network' != $screen->id )
		return $hidden;

	// Hide the primary link meta box by default
	$hidden  = array_merge( (array) $hidden, array( 'bp_activity_itemids', 'bp_activity_link', 'bp_activity_type', 'bp_activity_userid', ) );

	return apply_filters( 'bp_hide_meta_boxes', array_unique( $hidden ), $screen );
}
add_filter( 'default_hidden_meta_boxes', 'bp_activity_admin_edit_hidden_metaboxes', 10, 2 );

/**
 * Set up the admin page before any output is sent. Register contextual help and screen options for this admin page.
 *
 * @global object $bp BuddyPress global settings
 * @global BP_Activity_List_Table $bp_activity_list_table Activity screen list table
 * @since 1.6
 */
function bp_activity_admin_load() {
	global $bp, $bp_activity_list_table;

	// Decide whether to load the dev version of the CSS and JavaScript
	$dev = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? 'dev.' : '';

	// Decide whether to load the index or edit screen
	$doaction = ! empty( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';

	// Call an action for plugins to hook in early
	do_action( 'bp_activity_admin_load', $doaction );

	// Edit screen
	if ( 'edit' == $doaction && ! empty( $_GET['aid'] ) ) {
		// columns screen option
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
				'<p>' . __( '<strong>Link</strong> - Activity generated by blog posts and comments, forum topics and replies, and some plugins, uses the link field for a permalink back to the content item. Some types of activity may not use this field, even if it has been set.', 'buddypress' ) . '</p>' .
				'<p>' . __( '<strong>Type</strong> - Each distinct kind of activity has its own type. For example, <code>created_group</code> is used when a group is created and <code>joined_group</code> is used when a user joins a group.', 'buddypress' ) . '</p>' .
				'<p>' . __( 'For information about when and how BuddyPress uses all of these settings, see the Managing Activity link in the panel to the side.', 'buddypress' ) . '</p>'
		) );

		// Help panel - sidebar links
		get_current_screen()->set_help_sidebar(
			'<p><strong>' . __( 'For more information:', 'buddypress' ) . '</strong></p>' .
			'<p>' . __( '<a href="http://codex.buddypress.org/buddypress-site-administration/managing-activity/">Managing Activity</a>', 'buddypress' ) . '</p>' .
			'<p>' . __( '<a href="http://buddypress.org/support/">Support Forums</a>', 'buddypress' ) . '</p>'
		);

		// Register metaboxes for the edit screen.
		add_meta_box( 'submitdiv',           _x( 'Status', 'activity admin edit screen', 'buddypress' ), 'bp_activity_admin_edit_metabox_status', get_current_screen()->id, 'side', 'core' );
		add_meta_box( 'bp_activity_itemids', _x( 'Primary Item/Secondary Item', 'activity admin edit screen', 'buddypress' ), 'bp_activity_admin_edit_metabox_itemids', get_current_screen()->id, 'normal', 'core' );
		add_meta_box( 'bp_activity_link',    _x( 'Link', 'activity admin edit screen', 'buddypress' ), 'bp_activity_admin_edit_metabox_link', get_current_screen()->id, 'normal', 'core' );
		add_meta_box( 'bp_activity_type',    _x( 'Type', 'activity admin edit screen', 'buddypress' ), 'bp_activity_admin_edit_metabox_type', get_current_screen()->id, 'normal', 'core' );
		add_meta_box( 'bp_activity_userid',  _x( 'Author ID', 'activity admin edit screen', 'buddypress' ), 'bp_activity_admin_edit_metabox_userid', get_current_screen()->id, 'normal', 'core' );

		// Enqueue javascripts
		wp_enqueue_script( 'postbox' );
		wp_enqueue_script( 'dashboard' );
		wp_enqueue_script( 'comment' );

	// Index screen
	} else {
		// Create the Activity screen list table
		$bp_activity_list_table = new BP_Activity_List_Table();

		// per_page screen option
		add_screen_option( 'per_page', array( 'label' => _x( 'Activity', 'Activity items per page (screen options)', 'buddypress' )) );

		// Help panel - overview text
		get_current_screen()->add_help_tab( array(
			'id'      => 'bp-activity-overview',
			'title'   => __( 'Overview', 'buddypress' ),
			'content' =>
				'<p>' . __( 'You can manage activities made on your site similar to the way you manage comments and other content. This screen is customizable in the same ways as other management screens, and you can act on activities using the on-hover action links or the Bulk Actions.', 'buddypress' ) . '</p>' .
				'<p>' . __( 'There are many different types of activities. Some are generated automatically by BuddyPress and other plugins, and some are entered directly by a user in the form of status update. To help manage the different activity types, use the filter dropdown box to switch between them.', 'buddypress' ) . '</p>'
		) );

		// Help panel - moderation text
		get_current_screen()->add_help_tab( array(
			'id'		=> 'bp-activity-moderating',
			'title'		=> __( 'Moderating Activity', 'buddypress' ),
			'content'	=>
				'<p>' . __( 'In the <strong>Activity</strong> column, above each activity it says &#8220;Submitted on,&#8221; followed by the date and time the activity item was generated on your site. Clicking on the date/time link will take you to that activity on your live site. Hovering over any activity gives you options to reply, edit, spam mark, or delete that activity.', 'buddypress' ) . '</p>' .
				'<p>' . __( "In the <strong>In Response To</strong> column, if the activity was in reply to another activity, it shows that activity's author's picture and name, and a link to that activity on your live site. If there is a small bubble, the number in it shows how many other activities are related to this one; these are usually comments. Clicking the bubble will filter the activity screen to show only related activity items.", 'buddypress' ) . '</p>'
		) );

		// Help panel - sidebar links
		get_current_screen()->set_help_sidebar(
			'<p><strong>' . __( 'For more information:', 'buddypress' ) . '</strong></p>' .
			'<p>' . __( '<a href="http://buddypress.org/support/">Support Forums</a>', 'buddypress' ) . '</p>'
		);
	}

	// Enqueue CSS and JavaScript
	wp_enqueue_script( 'bp_activity_admin_js', BP_PLUGIN_URL . "bp-activity/admin/js/admin.{$dev}js",   array( 'jquery', 'wp-ajax-response' ), bp_get_version(), true );
	wp_enqueue_style( 'bp_activity_admin_css', BP_PLUGIN_URL . "bp-activity/admin/css/admin.{$dev}css", array(),                               bp_get_version()       );

	// Handle spam/un-spam/delete of activities
	if ( !empty( $doaction ) && ! in_array( $doaction, array( '-1', 'edit', 'save', ) ) ) {

		// Build redirection URL
		$redirect_to = remove_query_arg( array( 'aid', 'deleted', 'error', 'spammed', 'unspammed', ), wp_get_referer() );
		$redirect_to = add_query_arg( 'paged', $bp_activity_list_table->get_pagenum(), $redirect_to );

		// Get activity IDs
		$activity_ids = array_map( 'absint', (array) $_REQUEST['aid'] );

		// Call a filter for plugins to modify the requested activities to load
		$activity_ids = apply_filters( 'bp_activity_admin_action_activity_ids', $activity_ids );

		// Is this a bulk request?
		if ( 'bulk_' == substr( $doaction, 0, 5 ) && ! empty( $_REQUEST['aid'] ) ) {
			// Check this is a valid form submission
			check_admin_referer( 'bulk-activities' );

			// Trim 'bulk_' off the action name to avoid duplicating a ton of code
			$doaction = substr( $doaction, 5 );

		// This is a request to delete, spam, or un-spam, a single item.
		} elseif ( !empty( $_REQUEST['aid'] ) ) {

			// Check this is a valid form submission
			check_admin_referer( 'spam-activity_' . $activity_ids[0] );
		}

		// Initialise counters for how many of each type of item we perform an action on
		$deleted = $spammed = $unspammed = 0;

		// Store any errors that occurs when updating the database items
		$errors = array();

		// "We'd like to shoot the monster, could you move, please?"
		foreach ( $activity_ids as $activity_id ) {
			// @todo: Check the permissions on each
			//if ( ! current_user_can( 'bp_edit_activity', $activity_id ) )
			//	continue;

			// Get the activity from the database
			$activity = new BP_Activity_Activity( $activity_id );
			if ( empty( $activity->component ) ) {
				$errors[] = $activity_id;
				continue;
			}

			switch ( $doaction ) {
				case 'delete' :
					if ( 'activity_comment' == $activity->type )
						bp_activity_delete_comment( $activity->item_id, $activity->id );
					else
						bp_activity_delete( array( 'id' => $activity->id ) );

					$deleted++;
					break;

				case 'ham' :
					/**
					 * Remove moderation and blacklist checks in case we want to ham an activity
					 * which contains one of these listed keys.
					 */
					remove_action( 'bp_activity_before_save', 'bp_activity_check_moderation_keys', 2, 1 );
					remove_action( 'bp_activity_before_save', 'bp_activity_check_blacklist_keys',  2, 1 );

					bp_activity_mark_as_ham( $activity );
					$result = $activity->save();

					// Check for any error during activity save
					if ( ! $result )
						$errors[] = $activity->id;
					else
						$unspammed++;
					break;

				case 'spam' :
					bp_activity_mark_as_spam( $activity );
					$result = $activity->save();

					// Check for any error during activity save
					if ( ! $result )
						$errors[] = $activity->id;
					else
						$spammed++;
					break;

				default:
					break;
			}

			// Release memory
			unset( $activity );
		}

		// Call actions for plugins to do something before we redirect
		do_action( 'bp_activity_admin_action_after', array( $spammed, $unspammed, $deleted, $errors ), $redirect_to, $activity_ids );

		// Add arguments to the redirect URL so that on page reload, we can easily display what we've just done.
		if ( $spammed )
			$redirect_to = add_query_arg( 'spammed', $spammed, $redirect_to );

		if ( $unspammed )
			$redirect_to = add_query_arg( 'unspammed', $unspammed, $redirect_to );

		if ( $deleted )
			$redirect_to = add_query_arg( 'deleted', $deleted, $redirect_to );

		// If an error occurred, pass back the activity ID that failed
		if ( ! empty( $errors ) )
			$redirect_to = add_query_arg( 'error', implode ( ',', array_map( 'absint', $errors ) ), $redirect_to );

		// Redirect
		wp_redirect( apply_filters( 'bp_activity_admin_action_redirect', $redirect_to ) );
		exit;


	// Save the edit
	} elseif ( $doaction && 'save' == $doaction ) {
		// Build redirection URL
		$redirect_to = remove_query_arg( array( 'action', 'aid', 'deleted', 'error', 'spammed', 'unspammed', ), $_SERVER['REQUEST_URI'] );

		// Get activity ID
		$activity_id = (int) $_REQUEST['aid'];

		// Check this is a valid form submission
		check_admin_referer( 'edit-activity_' . $activity_id );

		// Get the activity from the database
		$activity = new BP_Activity_Activity( $activity_id );

		// If the activity doesn't exist, just redirect back to the index
		if ( empty( $activity->component ) ) {
			wp_redirect( $redirect_to );
			exit;
		}

		// Check the form for the updated properties

		// Store any error that occurs when updating the database item
		$error = 0;

		// Activity spam status
		$prev_spam_status = $new_spam_status = false;
		if ( ! empty( $_POST['activity_status'] ) ) {
			$prev_spam_status = $activity->is_spam;
			$new_spam_status  = ( 'spam' == $_POST['activity_status'] ) ? true : false;
		}

		// Activity action
		if ( isset( $_POST['bp-activities-action'] ) )
			$activity->action = $_POST['bp-activities-action'];

		// Activity content
		if ( isset( $_POST['bp-activities-content'] ) )
			$activity->content = $_POST['bp-activities-content'];

		// Activity primary link
		if ( ! empty( $_POST['bp-activities-link'] ) )
			$activity->primary_link = $_POST['bp-activities-link'];

		// Activity user ID
		if ( ! empty( $_POST['bp-activities-userid'] ) )
			$activity->user_id = (int) $_POST['bp-activities-userid'];

		// Activity item primary ID
		if ( isset( $_POST['bp-activities-primaryid'] ) )
			$activity->item_id = (int) $_POST['bp-activities-primaryid'];

		// Activity item secondary ID
		if ( isset( $_POST['bp-activities-secondaryid'] ) )
			$activity->secondary_item_id = (int) $_POST['bp-activities-secondaryid'];

		// Activity type
		if ( ! empty( $_POST['bp-activities-type'] ) ) {
			$actions  = array();

			// Walk through the registered actions, and build an array of actions/values.
			foreach ( $bp->activity->actions as $action ) {
				$action = array_values( (array) $action );

				for ( $i = 0, $i_count = count( $action ); $i < $i_count; $i++ )
					$actions[] = $action[$i]['key'];
			}

			// This was a mis-named activity type from before BP 1.6
			unset( $actions['friends_register_activity_action'] );

			// Check that the new type is a registered activity type
			if ( in_array( $_POST['bp-activities-type'], $actions ) )
				$activity->type = $_POST['bp-activities-type'];
		}

		// Activity timestamp
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

			// Reconstruct the date into a timestamp
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

		// Save
		$result = $activity->save();

		// Clear the activity stream first page cache, in case this activity's timestamp was changed
		wp_cache_delete( 'bp_activity_sitewide_front', 'bp' );

		// Check for any error during activity save
		if ( false === $result )
			$error = $activity->id;

		// Call actions for plugins to do something before we redirect
		do_action_ref_array( 'bp_activity_admin_edit_after', array( &$activity, $error ) );

		// If an error occurred, pass back the activity ID that failed
		if ( $error )
			$redirect_to = add_query_arg( 'error', (int) $error, $redirect_to );
		else
			$redirect_to = add_query_arg( 'updated', (int) $activity->id, $redirect_to );

		// Redirect
		wp_redirect( apply_filters( 'bp_activity_admin_edit_redirect', $redirect_to ) );
		exit;


	// If a referrer and a nonce is supplied, but no action, redirect back.
	} elseif ( ! empty( $_GET['_wp_http_referer'] ) ) {
		wp_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), stripslashes( $_SERVER['REQUEST_URI'] ) ) );
		exit;
	}
}

/**
 * Outputs the Activity component admin screens
 *
 * @since 1.6
 */
function bp_activity_admin() {
	// Decide whether to load the index or edit screen
	$doaction = ! empty( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';

	// Display the single activity edit screen
	if ( 'edit' == $doaction && ! empty( $_GET['aid'] ) )
		bp_activity_admin_edit();

	// Otherwise, display the Activity index screen
	else
		bp_activity_admin_index();
}

/**
 * Display the single activity edit screen
 *
 * @global int $screen_layout_columns Number of columns shown on this admin page
 * @since 1.6
 */
function bp_activity_admin_edit() {
	global $screen_layout_columns;

	// @todo: Check if user is allowed to edit activity items
	// if ( ! current_user_can( 'bp_edit_activity' ) )
	if ( ! is_super_admin() )
		die( '-1' );

	// Get the activity from the database
	$activity = bp_activity_get( array(
		'in'               => ! empty( $_REQUEST['aid'] ) ? (int) $_REQUEST['aid'] : 0,
		'max'              => 1,
		'show_hidden'      => true,
		'spam'             => 'all',
		'display_comments' => 0
	) );

	if ( ! empty( $activity['activities'][0] ) ) {
		$activity = $activity['activities'][0];

		// Workaround to use WP's touch_time() without duplicating that function
		$GLOBALS['comment'] = new stdClass;
		$GLOBALS['comment']->comment_date = $activity->date_recorded;
	} else {
		$activity = '';
	}

	// Construct URL for form
	$form_url = remove_query_arg( array( 'action', 'deleted', 'error', 'spammed', 'unspammed', ), $_SERVER['REQUEST_URI'] );
	$form_url = add_query_arg( 'action', 'save', $form_url );

	// Call an action for plugins to modify the activity before we display the edit form
	do_action_ref_array( 'bp_activity_admin_edit', array( &$activity ) );
?>

	<div class="wrap">
		<?php screen_icon( 'buddypress-activity' ); ?>
		<h2><?php printf( __( 'Editing Activity (ID #%s)', 'buddypress' ), number_format_i18n( (int) $_REQUEST['aid'] ) ); ?></h2>

		<?php if ( ! empty( $activity ) ) : ?>

			<form action="<?php echo esc_attr( $form_url ); ?>" id="bp-activities-edit-form" method="post">
				<div id="poststuff">

					<div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>"> 
						<div id="post-body-content">
							<div id="postdiv" class="postarea">
								<div id="bp_activity_action" class="postbox">
									<h3><?php _e( 'Action', 'buddypress' ); ?></h3>
									<div class="inside">
										<?php wp_editor( stripslashes( $activity->action ), 'bp-activities-action', array( 'media_buttons' => false, 'textarea_rows' => 7, 'teeny' => true, 'quicktags' => array( 'buttons' => 'strong,em,link,block,del,ins,img,code,spell,close' ) ) ); ?>
									</div>
								</div>

								<div id="bp_activity_content" class="postbox">
									<h3><?php _e( 'Content', 'buddypress' ); ?></h3>
									<div class="inside">
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
			<p><?php printf( __( 'No activity found with this ID. <a href="%s">Go back and try again</a>.', 'buddypress' ), network_admin_url( 'admin.php?page=bp-activity' ) ); ?></p>
		<?php endif; ?>

	</div><!-- .wrap -->

<?php
}

/**
 * Status metabox for the Activity admin edit screen
 *
 * @param object $item Activity item
 * @since 1.6
 */
function bp_activity_admin_edit_metabox_status( $item ) {
?>

	<div class="submitbox" id="submitcomment">

		<div id="minor-publishing">
			<div id="minor-publishing-actions">
				<div id="preview-action">
					<a class="button preview" href="<?php echo esc_attr( bp_activity_get_permalink( $item->id, $item ) ); ?>" target="_blank"><?php _e( 'View Activity', 'buddypress' ); ?></a>
				</div>

				<div class="clear"></div>
			</div><!-- #minor-publishing-actions -->

			<div id="misc-publishing-actions">
				<div class="misc-pub-section" id="comment-status-radio">
					<label class="approved"><input type="radio" name="activity_status" value="ham" <?php checked( $item->is_spam, 0 ); ?>><?php _e( 'Approved', 'buddypress' ); ?></label><br />
					<label class="spam"><input type="radio" name="activity_status" value="spam" <?php checked( $item->is_spam, 1 ); ?>><?php _e( 'Spam', 'buddypress' ); ?></label>
				</div>

				<div class="misc-pub-section curtime misc-pub-section-last">
					<?php
					// translators: Publish box date format, see http://php.net/date
					$datef = __( 'M j, Y @ G:i', 'buddypress' );
					$date  = date_i18n( $datef, strtotime( $item->date_recorded ) );
					?>
					<span id="timestamp"><?php printf( __( 'Submitted on: <strong>%1$s</strong>', 'buddypress' ), $date ); ?></span>&nbsp;<a href="#edit_timestamp" class="edit-timestamp hide-if-no-js" tabindex='4'><?php _e( 'Edit', 'buddypress' ); ?></a>

					<div id='timestampdiv' class='hide-if-js'>
						<?php touch_time( 1, 0, 5 ); ?>
					</div><!-- #timestampdiv -->
				</div>
			</div> <!-- #misc-publishing-actions -->

			<div class="clear"></div>
		</div><!-- #minor-publishing -->

		<div id="major-publishing-actions">
			<div id="publishing-action">
				<?php submit_button( __( 'Update', 'buddypress' ), 'primary', 'save', false, array( 'tabindex' => '4' ) ); ?>
			</div>
			<div class="clear"></div>
		</div><!-- #major-publishing-actions -->

	</div><!-- #submitcomment -->

<?php
}

/**
 * Primary link metabox for the Activity admin edit screen
 *
 * @param object $item Activity item
 * @since 1.6
 */
function bp_activity_admin_edit_metabox_link( $item ) {
?>

	<label class="screen-reader-text" for="bp-activities-link"><?php _e( 'Link', 'buddypress' ); ?></label>
	<input type="url" name="bp-activities-link" value="<?php echo esc_url( $item->primary_link ); ?>" />
	<p><?php _e( 'Activity generated by blog posts and comments, forum topics and replies, and some plugins, uses the link field for a permalink back to the content item.', 'buddypress' ); ?></p>

<?php
}

/**
 * User ID metabox for the Activity admin edit screen
 *
 * @param object $item Activity item
 * @since 1.6
 */
function bp_activity_admin_edit_metabox_userid( $item ) {
?>

	<label class="screen-reader-text" for="bp-activities-userid"><?php _e( 'Author ID', 'buddypress' ); ?></label>
	<input type="number" name="bp-activities-userid" value="<?php echo esc_attr( $item->user_id ); ?>" min="1" />

<?php
}

/**
 * Activity type metabox for the Activity admin edit screen
 *
 * @global object $bp BuddyPress global settings
 * @param object $item Activity item
 * @since 1.6
 */
function bp_activity_admin_edit_metabox_type( $item ) {
	global $bp;

	$actions  = array();
	$selected = $item->type;

	// Walk through the registered actions, and build an array of actions/values.
	foreach ( $bp->activity->actions as $action ) {
		$action = array_values( (array) $action );

		for ( $i = 0, $i_count = count( $action ); $i < $i_count; $i++ )
			$actions[ $action[$i]['key'] ] = $action[$i]['value'];
	}

	// This was a mis-named activity type from before BP 1.6
	unset( $actions['friends_register_activity_action'] );

	// Sort array by the human-readable value
	natsort( $actions );
?>

	<select name="bp-activities-type">
		<?php foreach ( $actions as $k => $v ) : ?>
			<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $k,  $selected ); ?>><?php echo esc_html( $v ); ?></option>
		<?php endforeach; ?>
	</select>

<?php
}

/**
 * Primary item ID/Secondary item ID metabox for the Activity admin edit screen
 *
 * @param object $item Activity item
 * @since 1.6
 */
function bp_activity_admin_edit_metabox_itemids( $item ) {
?>

	<label for="bp-activities-primaryid"><?php _e( 'Primary Item ID', 'buddypress' ); ?></label>
	<input type="number" name="bp-activities-primaryid" id="bp-activities-primaryid" value="<?php echo esc_attr( $item->item_id ); ?>" min="0" />
	<br />

	<label for="bp-activities-secondaryid"><?php _e( 'Secondary Item ID', 'buddypress' ); ?></label>
	<input type="number" name="bp-activities-secondaryid" id="bp-activities-secondaryid" value="<?php echo esc_attr( $item->secondary_item_id ); ?>" min="0" />

	<p><?php _e( 'These identify the object that created this activity. For example, the fields could reference a pair of site and comment IDs.', 'buddypress' ); ?></p>

<?php
}

/**
 * Display the Activity admin index screen, which contains a list of all the activities.
 *
 * @global BP_Activity_List_Table $bp_activity_list_table Activity screen list table
 * @global string $plugin_page
 * @since 1.6
 */
function bp_activity_admin_index() {
	global $bp_activity_list_table, $plugin_page;

	$messages = array();

	// If the user has just made a change to an activity item, build status messages
	if ( ! empty( $_REQUEST['deleted'] ) || ! empty( $_REQUEST['spammed'] ) || ! empty( $_REQUEST['unspammed'] ) || ! empty( $_REQUEST['error'] ) || ! empty( $_REQUEST['updated'] ) ) {
		$deleted   = ! empty( $_REQUEST['deleted']   ) ? (int) $_REQUEST['deleted']   : 0;
		$errors    = ! empty( $_REQUEST['error']     ) ? $_REQUEST['error']           : '';
		$spammed   = ! empty( $_REQUEST['spammed']   ) ? (int) $_REQUEST['spammed']   : 0;
		$unspammed = ! empty( $_REQUEST['unspammed'] ) ? (int) $_REQUEST['unspammed'] : 0;
		$updated   = ! empty( $_REQUEST['updated']   ) ? (int) $_REQUEST['updated']   : 0;

		$errors = array_map( 'absint', explode( ',', $errors ) );

		// Make sure we don't get any empty values in $errors
		for ( $i = 0, $errors_count = count( $errors ); $i < $errors_count; $i++ ) {
			if ( 0 === $errors[$i] )
				unset( $errors[$i] );
		}

		// Reindex array
		$errors = array_values( $errors );

		if ( $deleted > 0 )
			$messages[] = sprintf( _n( '%s activity has been permanently deleted.', '%s activity items have been permanently deleted.', $deleted, 'buddypress' ), number_format_i18n( $deleted ) );

		if ( ! empty( $errors ) ) {
			if ( 1 == count( $errors ) ) {
				$messages[] = sprintf( __( 'An error occurred when trying to update activity ID #%s.', 'buddypress' ), number_format_i18n( $errors[0] ) );

			} else {
				$error_msg  = __( 'Errors occurred when trying to update these activity items:', 'buddypress' );
				$error_msg .= '<ul class="activity-errors">';

				// Display each error as a list item
				foreach ( $errors as $error ) {
					// Translators: This is a bulleted list of item IDs
					$error_msg .= '<li>' . sprintf( __( '#%s', 'buddypress' ), number_format_i18n( $error ) ) . '</li>';
				}

				$error_msg  .= '</ul>';
				$messages[] = $error_msg;
			}
		}

		if ( $spammed > 0 )
			$messages[] = sprintf( _n( '%s activity has been successfully spammed.', '%s activity items have been successfully spammed.', $spammed, 'buddypress' ), number_format_i18n( $spammed ) );

		if ( $unspammed > 0 )
			$messages[] = sprintf( _n( '%s activity has been successfully unspammed.', '%s activity items have been successfully unspammed.', $unspammed, 'buddypress' ), number_format_i18n( $unspammed ) );

		if ( $updated > 0 )
			$messages[] = __( 'The activity has been updated succesfully.', 'buddypress' );
	}

	// Prepare the activity items for display
	$bp_activity_list_table->prepare_items();

	// Call an action for plugins to modify the activity before we display the edit form
	do_action( 'bp_activity_admin_index', $messages );
?>

	<div class="wrap">
		<?php screen_icon( 'buddypress-activity' ); ?>
		<h2>
			<?php if ( !empty( $_REQUEST['aid'] ) ) : ?>
				<?php printf( __( 'Activity related to ID #%s', 'buddypress' ), number_format_i18n( (int) $_REQUEST['aid'] ) ); ?>
			<?php else : ?>
				<?php _e( 'Activity', 'buddypress' ); ?>
			<?php endif; ?>

			<?php if ( !empty( $_REQUEST['s'] ) ) : ?>
				<span class="subtitle"><?php printf( __( 'Search results for &#8220;%s&#8221;', 'buddypress' ), wp_html_excerpt( esc_html( stripslashes( $_REQUEST['s'] ) ), 50 ) ); ?></span>
			<?php endif; ?>
		</h2>

		<?php // If the user has just made a change to an activity item, display the status messages ?>
		<?php if ( !empty( $messages ) ) : ?>
			<div id="moderated" class="<?php echo ( ! empty( $_REQUEST['error'] ) ) ? 'error' : 'updated'; ?>"><p><?php echo implode( "<br/>\n", $messages ); ?></p></div>
		<?php endif; ?>

		<?php // Display each activity on its own row ?>
		<?php $bp_activity_list_table->views(); ?>

		<form id="bp-activities-form" action="" method="get">
			<?php $bp_activity_list_table->search_box( __( 'Search all Activity', 'buddypress' ), 'bp-activity' ); ?>
			<input type="hidden" name="page" value="<?php echo esc_attr( $plugin_page ); ?>" />
			<?php $bp_activity_list_table->display(); ?>
		</form>

		<?php // This markup is used for the reply form ?>
		<table style="display: none;">
			<tr id="bp-activities-container" style="display: none;">
				<td colspan="4">
					<form method="get" action="">

						<h5 id="bp-replyhead"><?php _e( 'Reply to Activity', 'buddypress' ); ?></h5>
						<?php wp_editor( '', 'bp-activities', array( 'dfw' => false, 'media_buttons' => false, 'quicktags' => array( 'buttons' => 'strong,em,link,block,del,ins,img,code,spell,close' ), 'tinymce' => false, ) ); ?>

						<p id="bp-replysubmit" class="submit">
							<a href="#" class="cancel button-secondary alignleft"><?php _e( 'Cancel', 'buddypress' ); ?></a>
							<a href="#" class="save button-primary alignright"><?php _e( 'Reply', 'buddypress' ); ?></a>

							<img class="waiting" style="display:none;" src="<?php echo esc_url( network_admin_url( 'images/wpspin_light.gif' ) ); ?>" alt="" />
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

/**
 * List table class for the Activity component admin page.
 *
 * @since 1.6
 */
class BP_Activity_List_Table extends WP_List_Table {
	/**
	 * What type of view is being displayed? e.g. "All", "Pending", "Approved", "Spam"...
	 *
	 * @since 1.6
	*/
	public $view = 'all';

	/**
	 * How many activity items have been marked as spam.
	 *
	 * @since 1.6
	 */
	public $spam_count = 0;

	/**
	 * Store activity-to-user-ID mappings for use in the In Response To column.
	 *
	 * @since 1.6
	 */
	protected $activity_user_id = array();

	/**
	 * Constructor
	 *
	 * @since 1.6
	 */
	public function __construct() {

		// Define singular and plural labels, as well as whether we support AJAX.
		parent::__construct( array(
			'ajax'     => false,
			'plural'   => 'activities',
			'singular' => 'activity',
		) );
	}

	/**
	 * Handle filtering of data, sorting, pagination, and any other data-manipulation required prior to rendering.
	 *
	 * @since 1.6
	 */
	function prepare_items() {
		$screen = get_current_screen();

		// Option defaults
		$filter           = array();
		$include_id       = false;
		$search_terms     = false;
		$sort             = 'DESC';
		$spam             = 'ham_only';

		// Set current page
		$page = $this->get_pagenum();

		// Set per page from the screen options
		$per_page = $this->get_items_per_page( str_replace( '-', '_', "{$screen->id}_per_page" ) );

		// Check if we're on the "Spam" view
		if ( !empty( $_REQUEST['activity_status'] ) && 'spam' == $_REQUEST['activity_status'] ) {
			$spam       = 'spam_only';
			$this->view = 'spam';
		}

		// Sort order
		if ( !empty( $_REQUEST['order'] ) && 'desc' != $_REQUEST['order'] )
			$sort = 'ASC';

		// Order by
		/*if ( !empty( $_REQUEST['orderby'] ) ) {
		}*/

		// Filter
		if ( !empty( $_REQUEST['activity_type'] ) )
			$filter = array( 'action' => $_REQUEST['activity_type'] );

		// Are we doing a search?
		if ( !empty( $_REQUEST['s'] ) )
			$search_terms = $_REQUEST['s'];

		// Check if user has clicked on a specific activity (if so, fetch only that, and any related, activity).
		if ( !empty( $_REQUEST['aid'] ) )
			$include_id = (int) $_REQUEST['aid'];

		// Get the spam total (ignoring any search query or filter)
		$spams = bp_activity_get( array(
			'display_comments' => 'stream',
			'show_hidden'      => true,
			'spam'             => 'spam_only',
		) );
		$this->spam_count = $spams['total'];
		unset( $spams );

		// Get the activities from the database
		$activities = bp_activity_get( array(
			'display_comments' => 'stream',
			'filter'           => $filter,
			'in'               => $include_id,
			'page'             => $page,
			'per_page'         => $per_page,
			'search_terms'     => $search_terms,
			'show_hidden'      => true,
			//'sort'             => $sort,
			'spam'             => $spam,
		) );

		// If we're viewing a specific activity, flatten all activites into a single array.
		if ( $include_id ) {
			$activities['activities'] = BP_Activity_List_Table::flatten_activity_array( $activities['activities'] );
			$activities['total']      = count( $activities['activities'] );

			// Sort the array by the activity object's date_recorded value
			usort( $activities['activities'], create_function( '$a, $b', 'return $a->date_recorded > $b->date_recorded;' ) );
		}

		// bp_activity_get returns an array of objects; cast these to arrays for WP_List_Table.
		$new_activities = array();
		foreach ( $activities['activities'] as $activity_item ) {
			$new_activities[] = (array) $activity_item;

			// Build an array of activity-to-user ID mappings for better efficency in the In Response To column
			$this->activity_user_id[$activity_item->id] = $activity_item->user_id;
		}

		// Set raw data to display
		$this->items       = $new_activities;

		// Store information needed for handling table pagination
		$this->set_pagination_args( array(
			'per_page'    => $per_page,
			'total_items' => $activities['total'],
			'total_pages' => ceil( $activities['total'] / $per_page )
		) );

		// Don't truncate activity items; bp_activity_truncate_entry() needs to be used inside a BP_Activity_Template loop.
		remove_filter( 'bp_get_activity_content_body', 'bp_activity_truncate_entry', 5 );
	}

	/**
	 * Get an array of all the columns on the page
	 *
	 * @return array
	 * @since 1.6
	 */
	function get_column_info() {
		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns(),
		);

		return $this->_column_headers;
	}

	/**
	 * Displays a message on screen when no items are found (e.g. no search matches)
	 *
	 * @since 1.6
	 */
	function no_items() {
		_e( 'No activities found.', 'buddypress' );
	}

	/**
	 * Outputs the Activity data table
	 *
	 * @since 1.6
	*/
	function display() {
		extract( $this->_args );

		$this->display_tablenav( 'top' );
	?>

		<table class="<?php echo implode( ' ', $this->get_table_classes() ); ?>" cellspacing="0">
			<thead>
				<tr>
					<?php $this->print_column_headers(); ?>
				</tr>
			</thead>

			<tfoot>
				<tr>
					<?php $this->print_column_headers( false ); ?>
				</tr>
			</tfoot>

			<tbody id="the-comment-list">
				<?php $this->display_rows_or_placeholder(); ?>
			</tbody>
		</table>
		<?php

		$this->display_tablenav( 'bottom' );
	}

	/**
	 * Generates content for a single row of the table
	 *
	 * @param object $item The current item
	 * @since 1.6
	 */
	function single_row( $item ) {
		static $row_class = '';
		$row_class = ( $row_class == '' ? ' class="alternate"' : '' );

		echo '<tr' . $row_class . ' id="activity-' . esc_attr( $item['id'] ) . '" data-parent_id="' . esc_attr( $item['id'] ) . '" data-root_id="' . esc_attr( $item['item_id'] ) . '">';
		echo $this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * Get the list of views available on this table (e.g. "all", "spam").
	 *
	 * @since 1.6
	 */
	function get_views() {
		$redirect_to = remove_query_arg( array( 'activity_status', 'aid', 'deleted', 'error', 'spammed', 'unspammed', 'updated', ), $_SERVER['REQUEST_URI'] );
	?>
		<ul class="subsubsub">
			<li class="all"><a href="<?php echo esc_attr( esc_url( $redirect_to ) ); ?>" class="<?php if ( 'spam' != $this->view ) echo 'current'; ?>"><?php _e( 'All', 'buddypress' ); ?></a> |</li>
			<li class="spam"><a href="<?php echo esc_attr( esc_url( add_query_arg( 'activity_status', 'spam', $redirect_to ) ) ); ?>" class="<?php if ( 'spam' == $this->view ) echo 'current'; ?>"><?php printf( __( 'Spam <span class="count">(%s)</span>', 'buddypress' ), number_format_i18n( $this->spam_count ) ); ?></a></li>

			<?php do_action( 'bp_activity_list_table_get_views', $redirect_to, $this->view ); ?>
		</ul>
	<?php
	}

		/**
	 * Get bulk actions
	 *
	 * @return array Key/value pairs for the bulk actions dropdown
	 * @since 1.6
	 */
	function get_bulk_actions() {
		$actions = array();
		$actions['bulk_spam']   = __( 'Mark as Spam', 'buddypress' );
		$actions['bulk_ham']    = __( 'Not Spam', 'buddypress' );
		$actions['bulk_delete'] = __( 'Delete Permanently', 'buddypress' );

		return apply_filters( 'bp_activity_list_table_get_bulk_actions', $actions );
	}

	/**
	 * Get the table column titles.
	 *
	 * @see WP_List_Table::single_row_columns()
	 * @return array
	 * @since 1.6
	 */
	function get_columns() {
		return array(
			'cb'       => '<input name type="checkbox" />',
			'author'   => __( 'Author', 'buddypress' ),
			'comment'  => __( 'Activity', 'buddypress' ),
			'response' => __( 'In Response To', 'buddypress' ),
		);
	}

	/**
	 * Get the column names for sortable columns
	 *
	 * @return array
	 * @since 1.6
	 * @todo For this to work, BP_Activity_Activity::get() needs updating to supporting ordering by specific fields
	 */
	function get_sortable_columns() {
		return array();

		/*return array(
			'author' => array( 'activity_author', false ),  // Intentionally not using "=>"
		);*/
	}

	/**
	 * Markup for the "filter" part of the form (i.e. which activity type to display)
	 *
	 * @global object $bp BuddyPress global settings
	 * @param string $which 'top' or 'bottom'
	 * @since 1.6
	 */
	function extra_tablenav( $which ) {
		global $bp;

		if ( 'bottom' == $which )
			return;

		$actions  = array();
		$selected = !empty( $_REQUEST['activity_type'] ) ? $_REQUEST['activity_type'] : '';

		// Walk through the registered actions, and build an array of actions/values.
		foreach ( $bp->activity->actions as $action ) {
			$action = array_values( (array) $action );

			for ( $i = 0, $i_count = count( $action ); $i < $i_count; $i++ )
				$actions[ $action[$i]['key'] ] = $action[$i]['value'];
		}

		// This was a mis-named activity type from before BP 1.6
		unset( $actions['friends_register_activity_action'] );

		// This type has not been used since BP 1.0.3. It will be re-instated in a future version.
		unset( $actions['updated_profile'] );

		// Sort array by the human-readable value
		natsort( $actions );
	?>

		<div class="alignleft actions">
			<select name="activity_type">
				<option value="" <?php selected( !$selected ); ?>><?php _e( 'Show all activity types', 'buddypress' ); ?></option>

				<?php foreach ( $actions as $k => $v ) : ?>
					<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $k,  $selected ); ?>><?php echo esc_html( $v ); ?></option>
				<?php endforeach; ?>
			</select>

			<?php submit_button( __( 'Filter', 'buddypress' ), 'secondary', false, false, array( 'id' => 'post-query-submit' ) ); ?>
		</div>

	<?php
	}

	/**
	 * Checkbox column
	 *
	 * @param array $item A singular item (one full row)
	 * @see WP_List_Table::single_row_columns()
	 * @since 1.6
	 */
	function column_cb( $item ) {
		printf( '<input type="checkbox" name="aid[]" value="%d" />', (int) $item['id'] );
	}

	/**
	 * Author column
	 *
	 * @param array $item A singular item (one full row)
	 * @see WP_List_Table::single_row_columns()
	 * @since 1.6
	 */
	function column_author( $item ) {
		echo '<strong>' . get_avatar( $item['user_id'], '32' ) . ' ' . bp_core_get_userlink( $item['user_id'] ) . '</strong>';
	}

	/**
	 * Content column, and "quick admin" rollover actions.
	 *
	 * Called "comment" in the CSS so we can re-use some WP core CSS.
	 *
	 * @param array $item A singular item (one full row)
	 * @see WP_List_Table::single_row_columns()
	 * @since 1.6
	 */
	function column_comment( $item ) {
		// Determine what type of item (row) we're dealing with
		if ( $item['is_spam'] )
			$item_status = 'spam';
		else
			$item_status = 'all';

		// Preorder items: Reply | Edit | Spam | Delete Permanently
		$actions = array(
			'reply'  => '',
			'edit'   => '',
			'spam'   => '', 'unspam' => '',
			'delete' => '',
		);

		// Build actions URLs
		$base_url   = network_admin_url( 'admin.php?page=bp-activity&amp;aid=' . $item['id'] );
		$spam_nonce = esc_html( '_wpnonce=' . wp_create_nonce( 'spam-activity_' . $item['id'] ) );

		$delete_url = $base_url . "&amp;action=delete&amp;$spam_nonce";
		$edit_url   = $base_url . '&amp;action=edit';
		$ham_url    = $base_url . "&amp;action=ham&amp;$spam_nonce";
		$spam_url   = $base_url . "&amp;action=spam&amp;$spam_nonce";

		// Rollover actions

		// Reply - javascript only; implemented by AJAX.
		if ( 'spam' != $item_status ) {
			$actions['reply'] = sprintf( '<a href="#" class="reply hide-if-no-js">%s</a>', __( 'Reply', 'buddypress' ) );

			// Edit
			$actions['edit'] = sprintf( '<a href="%s">%s</a>', $edit_url, __( 'Edit', 'buddypress' ) );
		}

		// Spam/unspam
		if ( 'spam' == $item_status )
			$actions['unspam'] = sprintf( '<a href="%s">%s</a>', $ham_url, __( 'Not Spam', 'buddypress' ) );
		else
			$actions['spam'] = sprintf( '<a href="%s">%s</a>', $spam_url, __( 'Spam', 'buddypress' ) );

		// Delete
		$actions['delete'] = sprintf( '<a href="%s" onclick="%s">%s</a>', $delete_url, "javascript:return confirm('" . esc_js( __( 'Are you sure?', 'buddypress' ) ) . "'); ", __( 'Delete Permanently', 'buddypress' ) );

		// Start timestamp
		echo '<div class="submitted-on">';

		// Other plugins can filter which actions are shown
		$actions = apply_filters( 'bp_activity_admin_comment_row_actions', array_filter( $actions ), $item );

		/* translators: 2: activity admin ui date/time */
		printf( __( 'Submitted on <a href="%1$s">%2$s at %3$s</a>', 'buddypress' ), bp_get_root_domain() . '/' . bp_get_activity_root_slug() . '/p/' . $item['id'] . '/', date_i18n( get_option( 'date_format' ), strtotime( $item['date_recorded'] ) ), date_i18n( get_option( 'time_format' ), strtotime( $item['date_recorded'] ) ) );

		// End timestamp
		echo '</div>';

		// Get activity content - if not set, use the action
		if ( ! empty( $item['content'] ) )
			$content = apply_filters_ref_array( 'bp_get_activity_content_body', array( $item['content'] ) );
		else
			$content = apply_filters_ref_array( 'bp_get_activity_action', array( $item['action'] ) );

		echo $content . ' ' . $this->row_actions( $actions );
	}

	/**
	 * "In response to" column
	 *
	 * @param array $item A singular item (one full row)
	 * @see WP_List_Table::single_row_columns()
	 * @since 1.6
	 */
	function column_response( $item ) {
		// Is $item is a root activity?
		if ( empty( $item['item_id'] ) || ! in_array( $item['type'], apply_filters( 'bp_activity_admin_root_activity_types', array( 'activity_comment' ), $item ) ) ) {
			$comment_count     = !empty( $item['children'] ) ? bp_activity_recurse_comment_count( (object) $item ) : 0;
			$root_activity_url = network_admin_url( 'admin.php?page=bp-activity&amp;aid=' . $item['id'] );

			// If the activity has comments, display a link to the activity's permalink, with its comment count in a speech bubble
			if ( $comment_count ) {
				$title_attr = sprintf( _n( '%s related activity', '%s related activities', $comment_count, 'buddypress' ), number_format_i18n( $comment_count ) );
				printf( '<a href="%1$s" title="%2$s" class="post-com-count"><span class="comment-count">%3$s</span></a>', esc_attr( $root_activity_url ), esc_attr( $title_attr ), number_format_i18n( $comment_count ) );
			}

		// For non-root activities, display a link to the replied-to activity's author's profile
		} else {
			echo '<strong>' . get_avatar( $this->get_activity_user_id( $item['item_id'] ), '32' ) . ' ' . bp_core_get_userlink( $this->get_activity_user_id( $item['item_id'] ) ) . '</strong><br />';
		}

		// Activity permalink
		if ( ! $item['is_spam'] )
			printf( __( '<a href="%1$s">View Activity</a>', 'buddypress' ), bp_activity_get_permalink( $item['id'], (object) $item ) );
	}

	/**
	 * A wrapper function for the BP_Activity_List_Table to get the specified activity's user ID.
	 *
	 * @param int $activity_id Activity ID to retrieve User ID for
	 * @since 1.6
	 */
	protected function get_activity_user_id( $activity_id ) {
		// If there is an existing activity/user ID mapping, just return the user ID.
		if ( ! empty( $this->activity_user_id[$activity_id] ) ) {
			return $this->activity_user_id[$activity_id];

		/**
		 * We don't have a mapping. This means the $activity_id is not on the current
		 * page of results, so fetch its details from the database.
		 */
		} else {
			$activity = bp_activity_get_specific( array( 'activity_ids' => $activity_id, 'show_hidden' => true, 'spam' => 'all', ) );

			/**
			 * If, somehow, the referenced activity has been deleted, leaving its associated
			 * activites as orphans, use the logged in user's ID to avoid errors.
			 */
			if ( empty( $activity['activities'] ) )
				return bp_loggedin_user_id();

			// Store the new activity/user ID mapping for any later re-use
			$this->activity_user_id[ $activity['activities'][0]->id ] = $activity['activities'][0]->user_id;

			// Return the user ID
			return $activity['activities'][0]->user_id;
		}
	}

	/**
	 * Helper function to flatten all activites returned from bp_activity_get() into a single array.
	 *
	 * @param array $tree Source array
	 * @return array Flattened array
	 * @since 1.6
	 */
	public static function flatten_activity_array( $tree ){
		foreach ( (array) $tree as $node ) {
			if ( isset( $node->children ) ) {

				foreach ( BP_Activity_List_Table::flatten_activity_array( $node->children ) as $child ) {
					$tree[] = $child;
				}

				unset( $node->children );
			}
		}

		return $tree;
	}
}
?>