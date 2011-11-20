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
add_filter( 'set-screen-option', 'bp_activity_admin_screen_options', 10, 3 );

/**
 * Registers the Activity component admin screen
 *
 * @global object $bp Global BuddyPress settings object
 * @since 1.6
 */
function bp_activity_add_admin_menu() {
	global $bp;

	if ( !bp_current_user_can( 'bp_moderate' ) )
		return;

	// Add our screen
	$hook = add_menu_page( __( 'Activity', 'buddypress' ), __( 'Activity', 'buddypress' ), 'manage_options', 'bp-activity', 'bp_activity_admin' );

	// Hook into early actions to load custom CSS and our init handler.
	add_action( "admin_print_styles-$hook", 'bp_core_add_admin_menu_styles' );
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
	if ( 'toplevel_page_bp_activity_settings_per_page' != $option )
		return $value;

	// Per page
	$new_value = (int) $new_value;
	if ( $new_value < 1 || $new_value > 999 )
		return $value;

	return $new_value;
}

/**
 * Set up the admin page before any output is sent. Register contextual help and screen options for this admin page.
 *
 * @global BP_Activity_List_Table $bp_activity_list_table Activity screen list table
 * @since 1.6
 */
function bp_activity_admin_load() {
	global $bp_activity_list_table;

	// per_page screen option
	add_screen_option( 'per_page', array( 'label' => _x( 'Activities', 'Activity items per page (screen options)', 'buddypress' )) );

	// Help panel - text
	add_contextual_help( get_current_screen(), '<p>' . __( 'You can manage activities made on your site similar to the way you manage comments and other content. This screen is customizable in the same ways as other management screens, and you can act on activities using the on-hover action links or the Bulk Actions.', 'buddypress' ) . '</p>' .
		'<p>' . __( 'There are many different types of activities. Some are generated by BuddyPress automatically, and others are entered directly by a user in the form of status update. To help manage the different activity types, use the filter dropdown box to switch between them.', 'buddypress' ) . '</p>' .

		'<p>' . __( 'In the Activity column, above each activity it says &#8220;Submitted on,&#8221; followed by the date and time the activity item was generated on your site. Clicking on the date/time link will take you to that activity on your live site. Hovering over any activity gives you options to reply, edit, spam mark, or delete that activity.', 'buddypress' ) . '</p>' .
		'<p>' . __( 'In the In Response To column, the text is the name of the user who generated the activity, and a link to the activity on your live site. The small bubble with the number in it shows how many other activities are related to this one; these are usually comments. Clicking the bubble will filter the activity screen to show only related activity items.', 'buddypress' ) . '</p>'
	);

	// Help panel - sidebar links
	get_current_screen()->set_help_sidebar(
		'<p><strong>' . __( 'For more information:', 'buddypress' ) . '</strong></p>' .
		'<p>' . __( '<a href="http://buddypress.org/support/">Support Forums</a>', 'buddypress' ) . '</p>'
	);

	// Enqueue CSS and JavaScript
	if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
		wp_enqueue_script( 'bp_activity_admin_js', BP_PLUGIN_URL . 'bp-activity/admin/js/admin.dev.js', array( 'jquery', 'wp-ajax-response' ), '20111120' );
		wp_enqueue_style( 'bp_activity_admin_css', BP_PLUGIN_URL . 'bp-activity/admin/css/admin.dev.css', array(), '20111120' );

	} else {
		wp_enqueue_script( 'bp_activity_admin_js', BP_PLUGIN_URL . 'bp-activity/admin/js/admin.js', array( 'jquery', 'wp-ajax-response' ), '20111120' );
		wp_enqueue_style( 'bp_activity_admin_css', BP_PLUGIN_URL . 'bp-activity/admin/css/admin.css', array(), '20111120' );
	}

	// Create the Activity screen list table
	$bp_activity_list_table = new BP_Activity_List_Table();

	// Handle spam/un-spam/delete of activities
	$doaction = $bp_activity_list_table->current_action();
	if ( $doaction && 'edit' != $doaction ) {

		// Build redirection URL
		$redirect_to = remove_query_arg( array( 'aid', 'deleted', 'error', 'spammed', 'unspammed', ), wp_get_referer() );
		$redirect_to = add_query_arg( 'paged', $bp_activity_list_table->get_pagenum(), $redirect_to );

		// Get activity IDs
		$activity_ids = array_map( 'absint', (array) $_REQUEST['aid'] );

		// Is this a bulk request?
		if ( 'bulk_' == substr( $doaction, 0, 5 ) && !empty( $_REQUEST['aid'] ) ) {
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

		// Store any error that occurs when updating the database item
		$error = 0;

		// "We'd like to shoot the monster, could you move, please?"
		foreach ( $activity_ids as $activity_id ) {
			// @todo: Check the permissions on each
			//if ( ! current_user_can( 'bp_edit_activity', $activity_id ) )
			//	continue;

			// Get the activity from the database
			$activity = new BP_Activity_Activity( $activity_id );
			if ( empty( $activity->component ) )
				continue;

			switch ( $doaction ) {
				case 'delete' :
					if ( 'activity_comment' == $activity->type )
						bp_activity_delete_comment( $activity->item_id, $activity->id );
					else
						bp_activity_delete( array( 'id' => $activity->id ) );

					$deleted++;
					break;

				case 'ham' :
					bp_activity_mark_as_ham( $activity );
					$result = $activity->save();

					// Check for any error during activity save
					if ( ! $result ) {
						$error = $activity->id;
						break;
					}

					$unspammed++;
					break;

				case 'spam' :
					bp_activity_mark_as_spam( $activity );
					$result = $activity->save();

					// Check for any error during activity save
					if ( ! $result ) {
						$error = $activity->id;
						break;
					}

					$spammed++;
					break;

				default:
					break;
			}

			// If an error occured, don't bother looking at the other activities. Bail out of the foreach.
			if ( $error )
				break;

			// Release memory
			unset( $activity );
		}

		// Add arguments to the redirect URL so that on page reload, we can easily display what we've just done.
		if ( $spammed )
			$redirect_to = add_query_arg( 'spammed', $spammed, $redirect_to );

		if ( $unspammed )
			$redirect_to = add_query_arg( 'unspammed', $unspammed, $redirect_to );

		if ( $deleted )
			$redirect_to = add_query_arg( 'deleted', $deleted, $redirect_to );

		// If an error occured, pass back the activity ID that failed
		if ( $error )
			$redirect_to = add_query_arg( 'error', (int) $error, $redirect_to );

		// Redirect
		wp_redirect( $redirect_to );
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
 * @global BP_Activity_List_Table $bp_activity_list_table Activity screen list table
 * @since 1.6
 */
function bp_activity_admin() {
	global $bp_activity_list_table;

	$messages = array();

	// If the user has just made a change to an activity item, build status messages
	if ( !empty( $_REQUEST['deleted'] ) || !empty( $_REQUEST['spammed'] ) || !empty( $_REQUEST['unspammed'] ) || !empty( $_REQUEST['error'] ) ) {
		$deleted   = !empty( $_REQUEST['deleted']   ) ? (int) $_REQUEST['deleted']   : 0;
		$error     = !empty( $_REQUEST['error']     ) ? (int) $_REQUEST['error']     : 0;
		$spammed   = !empty( $_REQUEST['spammed']   ) ? (int) $_REQUEST['spammed']   : 0;
		$unspammed = !empty( $_REQUEST['unspammed'] ) ? (int) $_REQUEST['unspammed'] : 0;

		if ( $deleted > 0 )
			$messages[] = sprintf( _n( '%s activity was permanently deleted.', '%s activities were permanently deleted.', $deleted, 'buddypress' ), $deleted );

		if ( $error > 0 )
			$messages[] = sprintf( __( 'An error occured when updating Activity ID #%d.', 'buddypress' ), $error );

		if ( $spammed > 0 )
			$messages[] = sprintf( _n( '%s activity marked as spam.', '%s activities marked as spam.', $spammed, 'buddypress' ), $spammed );

		if ( $unspammed > 0 )
			$messages[] = sprintf( _n( '%s activity restored from the spam.', '%s activities restored from the spam.', $unspammed, 'buddypress' ), $unspammed );

	// Handle the edit screen
	} elseif ( 'edit' == $bp_activity_list_table->current_action() && !empty( $_GET['aid'] ) ) {
		echo '@TODO: Activity Edit screen.';
		return;
	}

	// Prepare the activity items for display
	$bp_activity_list_table->prepare_items();
?>

	<div class="wrap">
		<?php screen_icon( 'buddypress' ); ?>
		<h2>
			<?php if ( !empty( $_REQUEST['aid'] ) ) : ?>
				<?php printf( __( 'Activity (ID #%d)', 'buddypress' ), (int) $_REQUEST['aid'] ); ?>
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

		<?php $bp_activity_list_table->views(); ?>

		<form id="bp-activities-form" action="" method="get">
			<?php $bp_activity_list_table->search_box( __( 'Search Activities', 'buddypress' ), 'bp-activity' ); ?>

			<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
			<?php $bp_activity_list_table->display(); ?>
		</form>

		<table style="display: none;">
			<tr id="bp-activities-container" style="display: none;">
				<td colspan="4">
					<form method="get" action="">

						<h5 id="bp-replyhead"><?php _e( 'Reply to Activity', 'buddypress' ); ?></h5>
						<?php wp_editor( '', 'bp-activities', array( 'dfw' => false, 'media_buttons' => false, 'quicktags' => array( 'buttons' => 'strong,em,link,block,del,ins,img,code,spell,close' ), 'tinymce' => false, ) ); ?>

						<p id="bp-replysubmit" class="submit">
							<a href="#" class="cancel button-secondary alignleft"><?php _e( 'Cancel', 'tmggc' ); ?></a>
							<a href="#" class="save button-primary alignright"><?php _e( 'Reply', 'tmggc' ); ?></a>

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
	 * Constructor
	 *
	 * @global $bp BuddyPress global settings
	 * @since 1.6
	 */
	public function __construct() {
		global $bp;

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

		// bp_activity_get returns an array of objects; cast these to arrays for WP_List_Table.
		$new_activities = array();
		foreach ( $activities['activities'] as $activity_item )
			$new_activities[] = (array) $activity_item;

		// @todo If we're viewing a specific activity, check/merge $activity->children into the main list (recursive).
		/*if ( $include_id ) {
		}*/

		// Set raw data to display
		$this->items       = $new_activities;
		$this->extra_items = array();

		// Store information needed for handling table pagination
		$this->set_pagination_args( array(
			'per_page'    => $per_page,
			'total_items' => $activities['total'],
			'total_pages' => ceil( $activities['total'] / $per_page )
		) );
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
		$redirect_to = remove_query_arg( array( 'activity_status', 'aid', 'deleted', 'spammed', 'unspammed', ), $_SERVER['REQUEST_URI'] );
	?>
		<ul class="subsubsub">
			<li class="all"><a href="<?php echo esc_attr( esc_url( $redirect_to ) ); ?>" class="<?php if ( 'spam' != $this->view ) echo 'current'; ?>"><?php _e( 'All', 'buddypress' ); ?></a> |</li>
			<li class="spam"><a href="<?php echo esc_attr( esc_url( add_query_arg( 'activity_status', 'spam', $redirect_to ) ) ); ?>" class="<?php if ( 'spam' == $this->view ) echo 'current'; ?>"><?php printf( __( 'Spam <span class="count">(%d)</span>', 'buddypress' ), $this->spam_count ); ?></a></li>
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

		return $actions;
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
	 * @param string $which 'top' or 'bottom'
	 * @since 1.6
	 */
	function extra_tablenav( $which ) {
		if ( 'bottom' == $which )
			return;

		$selected = !empty( $_REQUEST['activity_type'] ) ? $_REQUEST['activity_type'] : '';
		?>

		<div class="alignleft actions">
			<select name="activity_type">
				<option value="" <?php selected( !$selected ); ?>><?php _e( 'Show all activity types', 'buddypress' ); ?></option>
				<option value="activity_update"  <?php selected( 'activity_update',  $selected ); ?>><?php _e( 'Status Updates', 'buddypress' ); ?></option>
				<option value="activity_comment" <?php selected( 'activity_comment', $selected ); ?>><?php _e( 'Status Update Comments', 'buddypress' ); ?></option>

				<?php if ( bp_is_active( 'blogs' ) ) : ?>
					<option value="new_blog_post"    <?php selected( 'new_blog_post',    $selected ); ?>><?php _e( 'Posts', 'buddypress' ); ?></option>
					<option value="new_blog_comment" <?php selected( 'new_blog_comment', $selected ); ?>><?php _e( 'Comments', 'buddypress' ); ?></option>
				<?php endif; ?>

				<?php if ( bp_is_active( 'forums' ) ) : ?>
					<option value="new_forum_topic" <?php selected( 'new_forum_topic', $selected ); ?>><?php _e( 'Forum Topics', 'buddypress' ); ?></option>
					<option value="new_forum_post"  <?php selected( 'new_forum_post',  $selected ); ?>><?php _e( 'Forum Replies', 'buddypress' ); ?></option>
				<?php endif; ?>

				<?php if ( bp_is_active( 'groups' ) ) : ?>
					<option value="created_group" <?php selected( 'created_group', $selected ); ?>><?php _e( 'New Groups', 'buddypress' ); ?></option>
					<option value="joined_group"  <?php selected( 'joined_group',  $selected ); ?>><?php _e( 'Group Memberships', 'buddypress' ); ?></option>
				<?php endif; ?>

				<?php if ( bp_is_active( 'friends' ) ) : ?>
					<option value="friendship_accepted" <?php selected( 'friendship_accepted', $selected ); ?>><?php _e( 'Friendships Accepted', 'buddypress' ); ?></option>
					<option value="friendship_created"  <?php selected( 'friendship_created',  $selected ); ?>><?php _e( 'New Friendships', 'buddypress' ); ?></option>
				<?php endif; ?>

				<option value="new_member" <?php selected( 'new_member', $selected ); ?>><?php _e( 'New Members', 'buddypress' ); ?></option>
				<option value="new_avatar" <?php selected( 'new_avatar', $selected ); ?>><?php _e( 'New Member Avatar', 'buddypress' ); ?></option>

				<?php do_action( 'bp_activity_filter_options' ); ?>

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
		if ( !empty( $item['content'] ) )
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
		// Display link to user's profile
		echo bp_core_get_userlink( $item['user_id'] );

		// Get activity permalink
		$activity_link = bp_activity_get_permalink( $item['id'], (object) $item );

		// Get the root activity ID by parsing the permalink; this may be not be the same as $item['id'] for nested items (e.g. activity_comments)
		$root_activity_id = array();
		preg_match( '/\/p\/(\d+)\/*$/i', $activity_link, $root_activity_id );
		if ( empty( $root_activity_id[1] ) )
			return;

		$root_activity_id = (int) $root_activity_id[1];

		// Is $item the root activity?
		if ( (int) $item['id'] == $root_activity_id ) {
			$root_activity = (object) $item;

			// Get root activity comment count
			$comment_count = !empty( $root_activity->children ) ? bp_activity_recurse_comment_count( $root_activity ) : 0;

			// Display a link to the root activity's permalink, with its comment count in a speech bubble
			printf( '<br /><a href="%1$s" title="%2$s" class="post-com-count"><span class="comment-count">%3$d</span></a>',  network_admin_url( 'admin.php?page=bp-activity&amp;aid=' . $root_activity_id ), esc_attr( sprintf( __( '%d related activities', 'buddypress' ), $comment_count ) ), $comment_count );

		// $item is not the root activity (it is probably an activity_comment).
		} else {
			echo '<br />';

			// @todo Get comment count from a specific node ($root_activity_id) in the tree, not $root_activity_id's root.
		}

		// Link to the activity permalink
		printf( __( '<a href="%1$s">View Activity</a>', 'buddypress' ), bp_activity_get_permalink( $item['id'], (object) $item ) );
	}
}?>