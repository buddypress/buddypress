<?php
/**
 * Core component classes.
 *
 * @package BuddyPress
 * @subpackage Core
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BP_Core_Notification is deprecated.
 *
 * Use BP_Notifications_Notification instead.
 *
 * @deprecated since 1.9.0
 */
class BP_Core_Notification {

	/**
	 * The notification id.
	 *
	 * @var int
	 */
	public $id = 0;

	/**
	 * The ID to which the notification relates to within the component.
	 *
	 * @var int
	 */
	public $item_id = 0;

	/**
	 * The secondary ID to which the notification relates to within the component.
	 *
	 * @var int
	 */
	public $secondary_item_id = null;

	/**
	 * The user ID for who the notification is for.
	 *
	 * @var int
	 */
	public $user_id = 0;

	/**
	 * The name of the component that the notification is for.
	 *
	 * @var string
	 */
	public $component_name = '';

	/**
	 * The action within the component which the notification is related to.
	 *
	 * @var string
	 */
	public $component_action = '';

	/**
	 * The date the notification was created.
	 *
	 * @var string
	 */
	public $date_notified = '';

	/**
	 * Is the notification new or has it already been read.
	 *
	 * @var boolean
	 */
	public $is_new = false;

	/** Public Methods ********************************************************/

	/**
	 * Constructor
	 *
	 * @param int $id ID for the notification.
	 */
	public function __construct( $id = 0 ) {

		// Bail if no ID
		if ( empty( $id ) ) {
			return;
		}

		$this->id = absint( $id );
		$this->populate();
	}

	/**
	 * Update or insert notification details into the database.
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @return bool Success or failure.
	 */
	public function save() {
		global $wpdb;

		$bp = buddypress();

		// Update.
		if ( ! empty( $this->id ) ) {
			$query = "UPDATE {$bp->notifications->table_name} SET item_id = %d, secondary_item_id = %d, user_id = %d, component_name = %s, component_action = %d, date_notified = %s, is_new = %d ) WHERE id = %d";
			$sql   = $wpdb->prepare( $query, $this->item_id, $this->secondary_item_id, $this->user_id, $this->component_name, $this->component_action, $this->date_notified, $this->is_new, $this->id );

		// Save.
		} else {
			$query = "INSERT INTO {$bp->notifications->table_name} ( item_id, secondary_item_id, user_id, component_name, component_action, date_notified, is_new ) VALUES ( %d, %d, %d, %s, %s, %s, %d )";
			$sql   = $wpdb->prepare( $query, $this->item_id, $this->secondary_item_id, $this->user_id, $this->component_name, $this->component_action, $this->date_notified, $this->is_new );
		}

		$result = $wpdb->query( $sql );

		if ( empty( $result ) || is_wp_error( $result ) ) {
			return false;
		}

		$this->id = $wpdb->insert_id;

		return true;
	}

	/** Private Methods *******************************************************/

	/**
	 * Fetches the notification data from the database.
	 *
	 * @global wpdb $wpdb WordPress database object.
	 */
	public function populate() {
		global $wpdb;

		$bp = buddypress();

		$query   = "SELECT * FROM {$bp->notifications->table_name} WHERE id = %d";
		$prepare = $wpdb->prepare( $query, $this->id );
		$result  = $wpdb->get_row( $prepare );

		if ( ! empty( $result ) ) {
			$this->item_id = $result->item_id;
			$this->secondary_item_id = $result->secondary_item_id;
			$this->user_id           = $result->user_id;
			$this->component_name    = $result->component_name;
			$this->component_action  = $result->component_action;
			$this->date_notified     = $result->date_notified;
			$this->is_new            = $result->is_new;
		}
	}

	/** Static Methods ********************************************************/

	/**
	 * Check the access for a user.
	 *
	 * @param int $user_id         ID to check access for.
	 * @param int $notification_id Notification ID to check for.
	 * @return string
	 */
	public static function check_access( $user_id = 0, $notification_id = 0 ) {
		global $wpdb;

		$bp = buddypress();

		$query   = "SELECT COUNT(id) FROM {$bp->notifications->table_name} WHERE id = %d AND user_id = %d";
		$prepare = $wpdb->prepare( $query, $notification_id, $user_id );
		$result  = $wpdb->get_var( $prepare );

		return $result;
	}

	/**
	 * Fetches all the notifications in the database for a specific user.
	 *
	 * @global wpdb $wpdb WordPress database object
	 *
	 * @static
	 *
	 * @param int    $user_id User ID.
	 * @param string $status 'is_new' or 'all'.
	 * @return array Associative array
	 */
	public static function get_all_for_user( $user_id, $status = 'is_new' ) {
		global $wpdb;

		$bp = buddypress();

		$is_new = ( 'is_new' === $status )
			? ' AND is_new = 1 '
			: '';

		$query   = "SELECT * FROM {$bp->notifications->table_name} WHERE user_id = %d {$is_new}";
		$prepare = $wpdb->prepare( $query, $user_id );
		$result  = $wpdb->get_results( $prepare );

		return $result;
	}

	/**
	 * Delete all the notifications for a user based on the component name and action.
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @static
	 *
	 * @param int    $user_id          ID of the user to delet notification for.
	 * @param string $component_name   Component name.
	 * @param string $component_action Component action.
	 * @return mixed
	 */
	public static function delete_for_user_by_type( $user_id, $component_name, $component_action ) {
		global $wpdb;

		$bp = buddypress();

		$query   = "DELETE FROM {$bp->notifications->table_name} WHERE user_id = %d AND component_name = %s AND component_action = %s";
		$prepare = $wpdb->prepare( $query, $user_id, $component_name, $component_action );
		$result  = $wpdb->query( $prepare );

		return $result;
	}

	/**
	 * Delete all the notifications that have a specific item id, component name and action.
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @static
	 *
	 * @param int      $user_id           The ID of the user who the notifications are for.
	 * @param int      $item_id           The item ID of the notifications we wish to delete.
	 * @param string   $component_name    The name of the component that the notifications we wish to delete.
	 * @param string   $component_action  The action of the component that the notifications we wish to delete.
	 * @param int      $secondary_item_id (optional) The secondary item id of the notifications that we wish to
	 *                                    use to delete.
	 * @return mixed
	 */
	public static function delete_for_user_by_item_id( $user_id, $item_id, $component_name, $component_action, $secondary_item_id = 0 ) {
		global $wpdb;

		$bp = buddypress();

		$secondary_item_sql = ! empty( $secondary_item_id )
			? $wpdb->prepare( " AND secondary_item_id = %d", $secondary_item_id )
			: '';

		$query   = "DELETE FROM {$bp->notifications->table_name} WHERE user_id = %d AND item_id = %d AND component_name = %s AND component_action = %s{$secondary_item_sql}";
		$prepare = $wpdb->prepare( $query, $user_id, $item_id, $component_name, $component_action );
		$result  = $wpdb->query( $prepare );

		return $result;
	}

	/**
	 * Deletes all the notifications sent by a specific user, by component and action.
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @static
	 *
	 * @param int    $user_id          The ID of the user whose sent notifications we wish to delete.
	 * @param string $component_name   The name of the component the notification was sent from.
	 * @param string $component_action The action of the component the notification was sent from.
	 * @return mixed
	 */
	public static function delete_from_user_by_type( $user_id, $component_name, $component_action ) {
		global $wpdb;

		$bp = buddypress();

		$query   = "DELETE FROM {$bp->notifications->table_name} WHERE item_id = %d AND component_name = %s AND component_action = %s";
		$prepare = $wpdb->prepare( $query, $user_id, $component_name, $component_action );
		$result  = $wpdb->query( $prepare );

		return $result;
	}

	/**
	 * Deletes all the notifications for all users by item id, and optional secondary item id,
	 * and component name and action.
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @static
	 *
	 * @param int    $item_id           The item id that they notifications are to be for.
	 * @param string $component_name    The component that the notifications are to be from.
	 * @param string $component_action  The action that the notifications are to be from.
	 * @param int    $secondary_item_id Optional secondary item id that the notifications are to have.
	 * @return mixed
	 */
	public static function delete_all_by_type( $item_id, $component_name, $component_action = '', $secondary_item_id = 0 ) {
		global $wpdb;

		$component_action_sql = ! empty( $component_action )
			? $wpdb->prepare( "AND component_action = %s", $component_action )
			: '';

		$secondary_item_sql = ! empty( $secondary_item_id )
			? $wpdb->prepare( "AND secondary_item_id = %d", $secondary_item_id )
			: '';

		$bp = buddypress();

		$query   = "DELETE FROM {$bp->notifications->table_name} WHERE item_id = %d AND component_name = %s {$component_action_sql} {$secondary_item_sql}";
		$prepare = $wpdb->prepare( $query, $item_id, $component_name );
		$result  = $wpdb->query( $prepare );

		return $result;
	}
}
