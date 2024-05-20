<?php
/**
 * BuddyPress Community Notice Class.
 *
 * @package buddypress\bp-members\classes\class-bp-members-notice
 * @since 14.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BuddyPress Community Notice Class.
 *
 * Use this class to create, activate, deactivate or delete notices.
 *
 * @since 14.0.0
 */
class BP_Members_Notice {

	/**
	 * The notice ID.
	 *
	 * @var int|null
	 */
	public $id = null;

	/**
	 * The subject line for the notice.
	 *
	 * @var string
	 */
	public $subject;

	/**
	 * The content of the notice.
	 *
	 * @var string
	 */
	public $message;

	/**
	 * The date the notice was created.
	 *
	 * @var string
	 */
	public $date_sent;

	/**
	 * Whether the notice is active or not.
	 *
	 * @var int
	 */
	public $is_active;

	/**
	 * Constructor.
	 *
	 * @since 14.0.0
	 *
	 * @param int|null $id Optional. The ID of the current notice.
	 */
	public function __construct( $id = null ) {
		if ( ! empty( $id ) ) {
			$this->id = (int) $id;
			$this->populate();
		}
	}

	/**
	 * Populate method.
	 *
	 * Runs during constructor.
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @since 14.0.0
	 */
	public function populate() {
		global $wpdb;

		$bp = buddypress();

		$notice = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->members->table_name_notices} WHERE id = %d", $this->id ) );

		if ( $notice ) {
			$this->subject   = $notice->subject;
			$this->message   = $notice->message;
			$this->date_sent = $notice->date_sent;
			$this->is_active = (int) $notice->is_active;
		}
	}

	/**
	 * Dispatches a notice to targeted audience.
	 *
	 * @since 14.0.0
	 *
	 * @param integer $notice_id The ID of the notice to dispatch. Required.
	 * @param string  $target    The targeted audience. Optional, one of: 'community', 'admins', 'writers'.
	 *                           Defaults to 'community'.
	 * @return integer           The number of notice dispatched.
	 */
	public function dispatch( $notice_id, $target = 'community' ) {
		/**
		 * @todo Loop into members of the targeted audience to add a notice meta for each.
		 * Use single schedule events in case of large users table.
		 */
	}

	/**
	 * Saves a notice.
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @since 14.0.0
	 *
	 * @return integer|false The Notice ID on success. False otherwise.
	 */
	public function save() {
		global $wpdb;

		$bp = buddypress();

		/**
		 * Fires before the current notice subject gets saved.
		 *
		 * Please stop using this hook.
		 *
		 * @since 1.0.0
		 * @deprecated 14.0.0
		 *
		 * @param string  $subject The notice subject.
		 * @param integer $id      The notice ID.
		 */
		$subject = apply_filters_deprecated( 'messages_notice_subject_before_save', array( $this->subject, $this->id ), '14.0.0', 'bp_members_notice_subject_before_save' );

		/**
		 * Fires before the current notice message gets saved.
		 *
		 * Please stop using this hook.
		 *
		 * @since 1.0.0
		 * @deprecated 14.0.0
		 *
		 * @param string  $subject The notice message.
		 * @param integer $id      The notice ID.
		 */
		$message = apply_filters_deprecated( 'messages_notice_message_before_save', array( $this->message, $this->id ), '14.0.0', 'bp_members_notice_message_before_save' );

		foreach ( get_object_vars( $this ) as $prop => $value ) {
			if ( 'id' === $prop ) {
				continue;
			}

			if ( 'subject' === $prop ) {
				$value = $subject;
			}

			if ( 'message' === $prop ) {
				$value = $message;
			}

			/**
			 * Filter here to edit one or more properties of the notice before it is saved.
			 *
			 * NB: this is a dynamic filter. Possible values are:
			 * - 'bp_members_notice_subject_before_save'
			 * - 'bp_members_notice_message_before_save'
			 * - 'bp_members_notice_date_sent_before_save'
			 * - 'bp_members_notice_is_active_before_save'
			 *
			 * @since 14.0.0
			 *
			 * @param string       $value The property value.
			 * @param integer|null $id    The Notice ID to update or null when it's an insertion.
			 */
			$this->{$prop} = apply_filters( 'bp_members_notice_' . $prop . '_before_save', $value, $this->id );
		}

		/**
		 * Fires before the current message notice item gets saved.
		 *
		 * Please stop using this hook.
		 *
		 * @since 1.0.0
		 * @deprecated 14.0.0
		 *
		 * @param BP_Members_Notice $notice Current instance of the message notice item being saved. Passed by reference.
		 */
		do_action_deprecated( 'messages_notice_before_save', array( &$this ), '14.0.0' );

		if ( empty( $this->id ) ) {
			$result = $wpdb->insert(
				$bp->members->table_name_notices,
				array(
					'subject'   => $this->subject,
					'message'   => $this->message,
					'date_sent' => $this->date_sent,
					'is_active' => $this->is_active,
				),
				array( '%s', '%s', '%s', '%d' )
			);
		} else {
			$result = $wpdb->update(
				$bp->members->table_name_notices,
				array(
					'subject'   => $this->subject,
					'message'   => $this->message,
					'date_sent' => $this->date_sent,
					'is_active' => $this->is_active,
				),
				array(
					'id' => $this->id,
				),
				array( '%s', '%s', '%s', '%d' ),
				array( '%d' )
			);
		}

		if ( ! $result ) {
			return false;
		}

		if ( empty( $this->id ) ) {
			$this->id = $wpdb->insert_id;
		}

		// Now deactivate all notices apart from the new one.
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$bp->members->table_name_notices} SET is_active = 0 WHERE id != %d",
				$this->id
			)
		);

		bp_update_user_last_activity( bp_loggedin_user_id(), bp_core_current_time() );

		/**
		 * Please do not use this filter anymore.
		 *
		 * @since 1.0.0
		 * @deprecated 14.0.0
		 *
		 * @param BP_Members_Notice $notice Current instance of the notice being saved. Passed by reference.
		 */
		do_action_deprecated( 'messages_notice_after_save', array( &$this ), '14.0.0' );

		$saved_values = get_object_vars( $this );

		/**
		 * Fires after the current notice item has been saved.
		 *
		 * @since 14.0.0
		 *
		 * @param integer $id           The saved notice ID.
		 * @param array   $saved_values The list of the saved values keyed by object properties.
		 */
		do_action( 'bp_members_notice_after_save', $this->id, $saved_values );

		return $this->id;
	}

	/**
	 * Activates a notice.
	 *
	 * @since 14.0.0
	 *
	 * @return bool
	 */
	public function activate() {
		$this->is_active = 1;
		return (bool) $this->save();
	}

	/**
	 * Deactivates a notice.
	 *
	 * @since 14.0.0
	 *
	 * @return bool
	 */
	public function deactivate() {
		$this->is_active = 0;
		return (bool) $this->save();
	}

	/**
	 * Deletes a notice.
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @since 14.0.0
	 *
	 * @return bool
	 */
	public function delete() {
		global $wpdb;

		/**
		 * Fires before the current message item has been deleted.
		 *
		 * @since 1.0.0
		 *
		 * @param BP_Members_Notice $notice Current instance of the message notice item being deleted.
		 */
		do_action( 'messages_notice_before_delete', $this );

		$bp  = buddypress();
		$sql = $wpdb->prepare( "DELETE FROM {$bp->members->table_name_notices} WHERE id = %d", $this->id );

		if ( ! $wpdb->query( $sql ) ) {
			return false;
		}

		/**
		 * Fires after the current message item has been deleted.
		 *
		 * @since 2.8.0
		 *
		 * @param BP_Members_Notice $notice Current instance of the message notice item being deleted.
		 */
		do_action( 'messages_notice_after_delete', $this );

		return true;
	}

	/** Static Methods ********************************************************/

	/**
	 * Pulls up a list of notices.
	 *
	 * To get all notices, pass a value of -1 to pag_num.
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @since 14.0.0
	 *
	 * @param array $args {
	 *     Array of parameters.
	 *     @type int $pag_num  Number of notices per page. Defaults to 20.
	 *     @type int $pag_page The page number.  Defaults to 1.
	 * }
	 * @return array List of notices to display.
	 */
	public static function get_notices( $args = array() ) {
		global $wpdb;

		$r = bp_parse_args(
			$args,
			array(
				'pag_num'  => 20, // Number of notices per page.
				'pag_page' => 1 , // Page number.
			)
		);

		$limit_sql = '';
		if ( (int) $r['pag_num'] >= 0 ) {
			$limit_sql = $wpdb->prepare( "LIMIT %d, %d", (int) ( ( $r['pag_page'] - 1 ) * $r['pag_num'] ), (int) $r['pag_num'] );
		}

		$bp = buddypress();

		$notices = $wpdb->get_results( "SELECT * FROM {$bp->members->table_name_notices} ORDER BY date_sent DESC {$limit_sql}" );

		// Integer casting.
		foreach ( (array) $notices as $key => $data ) {
			$notices[ $key ]->id        = (int) $notices[ $key ]->id;
			$notices[ $key ]->is_active = (int) $notices[ $key ]->is_active;
		}

		/**
		 * Filters the array of notices, sorted by date and paginated.
		 *
		 * @since 2.8.0
		 *
		 * @param array $notices List of notices sorted by date and paginated.
		 * @param array $r       Array of parameters.
		 */
		return apply_filters( 'messages_notice_get_notices', $notices, $r );
	}

	/**
	 * Returns the total number of recorded notices.
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @since 14.0.0
	 *
	 * @return int
	 */
	public static function get_total_notice_count() {
		global $wpdb;

		$bp = buddypress();

		$notice_count = $wpdb->get_var( "SELECT COUNT(id) FROM {$bp->members->table_name_notices}" );

		/**
		 * Filters the total number of notices.
		 *
		 * @since 2.8.0
		 *
		 * @param int $notice_count Total number of recorded notices.
		 */
		return apply_filters( 'messages_notice_get_total_notice_count', (int) $notice_count );
	}

	/**
	 * Returns the active notice that should be displayed on the front end.
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @since 14.0.0
	 *
	 * @return BP_Members_Notice
	 */
	public static function get_active() {
		$notice = wp_cache_get( 'active_notice', 'bp_notices' );

		if ( false === $notice ) {
			global $wpdb;

			$bp = buddypress();

			$notice_id = $wpdb->get_var( "SELECT id FROM {$bp->members->table_name_notices} WHERE is_active = 1" );
			$notice    = new BP_Members_Notice( $notice_id );

			wp_cache_set( 'active_notice', $notice, 'bp_notices' );
		}

		/**
		 * Gives ability to filter the active notice that should be displayed on the front end.
		 *
		 * @since 2.8.0
		 *
		 * @param BP_Members_Notice $notice The notice object.
		 */
		return apply_filters( 'messages_notice_get_active', $notice );
	}
}
