<?php
/**
 * BuddyPress Members Notice Class.
 *
 * @package BuddyPress
 * @subpackage Members
 * @since 15.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BuddyPress Members Notice Class.
 *
 * Use this class to create, activate, deactivate or delete notices.
 *
 * @since 15.0.0
 */
#[AllowDynamicProperties]
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
	 * The notice target.
	 *
	 * Possible values are: 'community', 'contributors' & 'admins'.
	 *
	 * @var string
	 */
	public $target;

	/**
	 * The date the notice was created.
	 *
	 * @var string
	 */
	public $date_sent;

	/**
	 * Priority of the notice.
	 *
	 * @var int
	 */
	public $priority;

	/**
	 * Constructor.
	 *
	 * @since 15.0.0
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
	 * @since 15.0.0
	 */
	public function populate() {
		global $wpdb;

		$bp = buddypress();

		$notice = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->members->table_name_notices} WHERE id = %d", $this->id ) );

		if ( $notice ) {
			$this->subject   = $notice->subject;
			$this->message   = $notice->message;
			$this->target    = $notice->target;
			$this->date_sent = $notice->date_sent;
			$this->priority  = (int) $notice->priority;
		} else {
			$this->id = null;
		}
	}

	/**
	 * Saves a notice.
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @since 15.0.0
	 *
	 * @return integer|WP_Error The Notice ID on success. An error object otherwise.
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
		 * @deprecated 15.0.0
		 *
		 * @param string  $subject The notice subject.
		 * @param integer $id      The notice ID.
		 */
		$subject = apply_filters_deprecated( 'messages_notice_subject_before_save', array( $this->subject, $this->id ), '15.0.0', 'bp_members_notice_subject_before_save' );

		/**
		 * Fires before the current notice message gets saved.
		 *
		 * Please stop using this hook.
		 *
		 * @since 1.0.0
		 * @deprecated 15.0.0
		 *
		 * @param string  $subject The notice message.
		 * @param integer $id      The notice ID.
		 */
		$message = apply_filters_deprecated( 'messages_notice_message_before_save', array( $this->message, $this->id ), '15.0.0', 'bp_members_notice_message_before_save' );

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
			 * - 'bp_members_notice_target_before_save'
			 * - 'bp_members_notice_date_sent_before_save'
			 * - 'bp_members_notice_priority_before_save'
			 *
			 * @since 15.0.0
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
		 * @deprecated 15.0.0
		 *
		 * @param BP_Members_Notice $notice Current instance of the message notice item being saved. Passed by reference.
		 */
		do_action_deprecated( 'messages_notice_before_save', array( &$this ), '15.0.0' );

		if ( empty( $this->id ) ) {
			$result = $wpdb->insert(
				$bp->members->table_name_notices,
				array(
					'subject'   => $this->subject,
					'message'   => $this->message,
					'target'    => $this->target,
					'date_sent' => $this->date_sent,
					'priority'  => $this->priority,
				),
				array( '%s', '%s', '%s', '%s', '%d' )
			);
		} else {
			$result = $wpdb->update(
				$bp->members->table_name_notices,
				array(
					'subject'   => $this->subject,
					'message'   => $this->message,
					'target'    => $this->target,
					'date_sent' => $this->date_sent,
					'priority'  => $this->priority,
				),
				array(
					'id' => $this->id,
				),
				array( '%s', '%s', '%s', '%s', '%d' ),
				array( '%d' )
			);
		}

		if ( ! $result ) {
			return new WP_Error(
				'bp_notice_unsaved',
				__( 'An unexpected error prevented the notice to be saved.', 'buddypress' )
			);
		}

		if ( empty( $this->id ) ) {
			$this->id = $wpdb->insert_id;
		}

		bp_update_user_last_activity( bp_loggedin_user_id(), bp_core_current_time() );

		/**
		 * Please do not use this filter anymore.
		 *
		 * @since 1.0.0
		 * @deprecated 15.0.0
		 *
		 * @param BP_Members_Notice $notice Current instance of the notice being saved. Passed by reference.
		 */
		do_action_deprecated( 'messages_notice_after_save', array( &$this ), '15.0.0' );

		$saved_values = get_object_vars( $this );

		/**
		 * Fires after the current notice item has been saved.
		 *
		 * @since 15.0.0
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
	 * @since 15.0.0
	 *
	 * @return bool
	 */
	public function activate() {
		// Try to restore the previous priority.
		$previous_priority = bp_notices_get_meta( $this->id, 'previous_priority', true );

		// Use a regular one by default.
		if ( '' === $previous_priority ) {
			$previous_priority = 2;
		} else {
			$previous_priority = (int) $previous_priority;
			$test = bp_notices_delete_meta( $this->id, 'previous_priority' );
		}

		// Activate the notice.
		$this->priority = $previous_priority;
		$activated      = $this->save();

		if ( is_wp_error( $activated ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Deactivates a notice.
	 *
	 * @since 15.0.0
	 *
	 * @return bool
	 */
	public function deactivate() {
		// Used to restore the priority on re-activation.
		$previous_priority = $this->priority;
		bp_notices_update_meta( $this->id, 'previous_priority', $previous_priority );

		// Deactivating is using a priority of 127.
		$this->priority = 127;
		$deactivated    = $this->save();

		if ( is_wp_error( $deactivated ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Deletes a notice.
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @since 15.0.0
	 *
	 * @return bool
	 */
	public function delete() {
		global $wpdb;

		/**
		 * Please do not use this filter anymore.
		 *
		 * @since 1.0.0
		 * @deprecated 15.0.0
		 *
		 * @param BP_Members_Notice $notice Current instance of the message notice item being deleted.
		 */
		do_action_deprecated( 'messages_notice_before_delete', array( $this ), '15.0.0', 'bp_members_notice_before_delete' );

		/**
		 * Fires before the notice item has been deleted.
		 *
		 * @since 15.0.0
		 *
		 * @param BP_Members_Notice $notice Current instance of the notice item being deleted.
		 */
		do_action( 'bp_members_notice_before_delete', $this );

		$bp  = buddypress();
		$sql = $wpdb->prepare( "DELETE FROM {$bp->members->table_name_notices} WHERE id = %d", $this->id );

		if ( ! $wpdb->query( $sql ) ) {
			return false;

			// Remove all corresponding notice metadata.
		} else {
			bp_notices_delete_meta( $this->id );
		}

		/**
		 * Please do not use this filter anymore.
		 *
		 * @since 2.8.0
		 * @deprecated 15.0.0
		 *
		 * @param BP_Members_Notice $notice Current instance of the notice being saved. Passed by reference.
		 */
		do_action_deprecated( 'messages_notice_after_delete', array( $this ), '15.0.0', 'bp_members_notice_after_delete' );

		/**
		 * Fires after the notice item has been deleted.
		 *
		 * @since 15.0.0
		 *
		 * @param BP_Members_Notice $notice Current instance of the notice item being deleted.
		 */
		do_action( 'bp_members_notice_after_delete', $this );

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
	 * @since 15.0.0
	 *
	 * @param array $args {
	 *     An array of arguments.
	 *
	 *     @type integer      $user_id          The user ID to get notices for. Defaults to `0`.
	 *     @type integer      $pag_num          Number of notices per page. Defaults to `20`.
	 *     @type integer      $pag_page         The page number to retrieve.  Defaults to `1`.
	 *     @type boolean      $dismissed        `true` to get dismissed notices. False otherwise. Defaults to `false`.
	 *     @type array        $meta_query       An array describing the Meta Query to perform. Defaults to `[]`.
	 *     @type string       $fields           Use 'ids' to only get Notice IDs, 'all' otherwise. Defaults to 'all'.
	 *     @type string       $type             Use 'active' to only get active notices, 'all' to get all notices or
	 *                                          'inactive' to get inactive notices. Defaults to 'active'.
	 *     @type array        $exclude          An array of notice IDs to exclude from results. Defaults to `[]`.
	 *     @type array        $target__in       An array of targeted audiences to include in results. Possible targets
	 *                                          are 'community', 'contributors', 'admins'. Defaults to `[]`.
	 *     @type null|integer $priority         Use `1` to get high priority notices, `2` to get regular ones or `3` to
	 *                                          get low ones. `0` is restriced to BuddyPress usage: please don't use it.
	 *                                          Defaults to `null`.
	 *     @type boolean      $count_total_only `true` to only get the total number of notices, `false` otherwise.
	 *                                          Defaults to `false`.
	 * }
	 * @return array|integer|null List of notices, total number of notices for a count only query or null if nothing was found.
	 */
	public static function get( $args = array() ) {
		global $wpdb;

		$where_sql        = '';
		$join_sql         = '';
		$where_conditions = array();
		$result           = null;
		$r                = bp_parse_args(
			$args,
			bp_members_notices_default_query_args()
		);

		// Are we trying to get the first active notice.
		$is_first_active = 1 === $r['pag_num'] && 1 === $r['pag_page'] && 'first_active' === $r['type'];
		if ( $is_first_active ) {
			$r['type'] = 'active';
		}

		if ( ! $r['meta_query'] && $r['user_id'] && true === $r['dismissed'] ) {
			$r['meta_query'] = array(
				array(
					'key'     => 'dismissed_by',
					'value'   => (int) $r['user_id'],
					'compare' => '=',
				)
			);
		}

		// METADATA.
		$meta_query_sql = self::get_meta_query_sql( $r['meta_query'] );

		// The meta query.
		if ( ! empty( $meta_query_sql['where'] ) ) {
			$where_conditions['meta_query'] = $meta_query_sql['where'];
		}

		// The meta query.
		if ( ! empty( $meta_query_sql['join'] ) ) {
			$join_sql = $meta_query_sql['join'];
		}

		// 127 is the value used to deactivate a notice.
		if ( 'active' === $r['type'] ) {
			$where_conditions['type'] = 'n.priority != 127';
		} elseif ( 'inactive' === $r['type'] ) {
			$where_conditions['type'] = 'n.priority = 127';
		}

		if ( $r['exclude'] ) {
			$where_conditions['exclude'] = 'n.id NOT IN( ' . implode( ', ', wp_parse_id_list( $r['exclude'] ) ) . ' )';
		}

		if ( $r['target__in'] ) {
			$where_conditions['target__in'] = 'n.target IN( \'' . implode( '\', \'', wp_parse_slug_list( $r['target__in'] ) ) . '\' )';
		}

		if ( 'inactive' !== $r['type'] && ! is_null( $r['priority'] ) && is_numeric( $r['priority'] ) ) {
			$where_conditions['priority'] = $wpdb->prepare( 'priority = %d', $r['priority'] );
		}

		if ( $r['user_id'] ) {
			if ( ! bp_user_can( $r['user_id'], 'edit_posts' ) ) {
				$where_conditions['user_cap'] = 'n.target = \'community\'';
			} elseif ( ! bp_user_can( $r['user_id'], 'manage_options' ) ) {
				$where_conditions['user_cap'] = 'n.target != \'admins\'';
			}
		}

		$limit_sql = '';
		if ( (int) $r['pag_num'] >= 0 ) {
			$limit_sql = $wpdb->prepare( "LIMIT %d, %d", (int) ( ( $r['pag_page'] - 1 ) * $r['pag_num'] ), (int) $r['pag_num'] );
		}

		// Custom WHERE.
		if ( ! empty( $where_conditions ) ) {
			$where_sql = 'WHERE ' . implode( ' AND ', $where_conditions );
		}

		$bp = buddypress();

		// Get notice IDs.
		if ( 'ids' === $r['fields'] ) {
			$result = $wpdb->get_col(
				"SELECT n.id FROM {$bp->members->table_name_notices} n
				{$join_sql}
				{$where_sql}"
			);
			$result = wp_parse_id_list( $result );

			// Get the first active notice.
		} elseif ( $is_first_active ) {
			$result = $wpdb->get_row(
				"SELECT n.* FROM {$bp->members->table_name_notices} n
				{$join_sql}
				{$where_sql}
				ORDER BY priority ASC, date_sent DESC
				{$limit_sql}"
			);

			// Get the notices count.
		} elseif ( true === $r['count_total_only'] ) {
			$result = $wpdb->get_var(
				"SELECT COUNT(*) FROM {$bp->members->table_name_notices} n
				{$join_sql}
				{$where_sql}"
			);

			// Get all matching notices.
		} else {
			$user_result     = false;
			$is_user_notices = isset( $args['user_id'], $args['exclude'] ) && $args['user_id'] && 1 === $r['pag_page'] && 0 !== $r['priority'];

			// Are we getting the user's top priority notices?
			if ( $is_user_notices ) {
				$user_result = wp_cache_get( $args['user_id'], 'bp_member_top_priority_notices' );
			}

			if ( false === $user_result ) {
				$result = $wpdb->get_results(
					"SELECT n.* FROM {$bp->members->table_name_notices} n
					{$join_sql}
					{$where_sql}
					ORDER BY priority ASC, date_sent DESC
					{$limit_sql}"
				);

				if ( $is_user_notices ) {
					wp_cache_set( $args['user_id'], $result, 'bp_member_top_priority_notices' );
				}
			} else {
				$result = $user_result;
			}

			// Integer casting.
			foreach ( (array) $result as $key => $data ) {
				$result[ $key ]->id       = (int) $result[ $key ]->id;
				$result[ $key ]->priority = (int) $result[ $key ]->priority;
			}
		}

		if ( ! $r['count_total_only'] && ! $is_first_active ) {
			/**
			 * The 'messages_notice_get_notices' is deprecated as of 15.0.0.
			 *
			 * Please use 'bp_members_get_notices' instead.
			 *
			 * @deprecated 15.0.0
			 */
			$notices = apply_filters_deprecated(
				'messages_notice_get_notices',
				array( $result ),
				'15.0.0',
				'bp_members_get_notices'
			);

			/**
			 * Filters the array of notices, sorted by date and paginated.
			 *
			 * @since 15.0.0
			 *
			 * @param array|integer $notices List of notices or total number of notices for a count only query.
			 * @param array $r      The query parameters.
			 */
			return apply_filters( 'bp_members_get_notices', $notices, $r );
		}

		return $result;
	}

	/**
	 * Get the SQL for the 'meta_query' param in `BP_Members_Notice::get()`.
	 *
	 * We use WP_Meta_Query to do the heavy lifting of parsing the
	 * meta_query array and creating the necessary SQL clauses. However,
	 * since `BP_Members_Notice::get()` builds its SQL differently than
	 * `WP_Query`, we have to alter the return value (stripping the leading
	 * AND keyword from the 'where' clause).
	 *
	 * @since 15.0.0
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param  array $meta_query An array of meta_query filters. See the
	 *                           documentation for WP_Meta_Query for details.
	 * @return array $sql_array 'join' and 'where' clauses.
	 */
	public static function get_meta_query_sql( $meta_query = array() ) {
		global $wpdb;

		// Default array keys & empty values.
		$sql_array = array(
			'join'  => '',
			'where' => '',
		);

		// Bail if no meta query.
		if ( empty( $meta_query ) ) {
			return $sql_array;
		}

		$bp       = buddypress();
		$meta_sql = new WP_Meta_Query( $meta_query );

		// WP_Meta_Query expects the table name at `$wpdb->noticemeta`.
		$wpdb->noticemeta = $bp->members->table_name_notices_meta;

		$meta_sql = $meta_sql->get_sql( 'notice', 'n', 'id' );

		// Strip the leading AND - it's handled in get().
		$sql_array['where'] = preg_replace( '/^\sAND/', '', $meta_sql['where'] );
		$sql_array['join']  = $meta_sql['join'];

		return $sql_array;
	}

	/**
	 * Returns the total number of recorded notices.
	 *
	 * @since 15.0.0
	 *
	 * @param array $args See `BP_Memners_Notice->get()` for description.
	 * @return integer The total number of recorded notices.
	 */
	public static function get_total_notice_count( $args = array() ) {
		// Forces a count query.
		$args['count_total_only'] = true;
		$user_notices_count       = false;
		$is_user_notices          = isset( $args['user_id'], $args['exclude'] ) && $args['user_id'];

		// We're getting a user's unread notices count.
		if ( $is_user_notices ) {
			$user_notices_count = wp_cache_get( $args['user_id'], 'bp_member_notices_count' );
		}

		if ( false === $user_notices_count ) {
			/**
			 * The 'messages_notice_get_total_notice_count' is deprecated as of 15.0.0.
			 *
			 * Please use 'bp_members_get_total_notice_count' instead.
			 *
			 * @deprecated 15.0.0
			 */
			$notices_count = (int) apply_filters_deprecated(
				'messages_notice_get_total_notice_count',
				array( BP_Members_Notice::get( $args ) ),
				'15.0.0',
				'bp_members_get_total_notice_count'
			);

			if ( $is_user_notices ) {
				wp_cache_set( $args['user_id'], $notices_count, 'bp_member_notices_count' );
			}

			// Use cached count.
		} else {
			$notices_count = $user_notices_count;
		}

		/**
		 * Filters the total number of notices.
		 *
		 * @since 15.0.0
		 *
		 * @param integer $notices_count Total number of recorded notices.
		 */
		return apply_filters( 'bp_members_get_total_notice_count', $notices_count );
	}

	/**
	 * Returns the list of notice IDs the user dismissed.
	 *
	 * @since 15.0.0
	 *
	 * @param integer $user_id The user ID.
	 * @return array The list of Notice IDs the user has dismissed.
	 */
	public static function get_user_dismissed( $user_id ) {
		$dismissed = wp_cache_get( $user_id, 'bp_member_dismissed_notices' );

		if ( false === $dismissed ) {
			$dismissed = self::get(
				array(
					'user_id'   => $user_id,
					'dismissed' => true,
					'fields'    => 'ids',
				)
			);

			// Cache user's first active notice.
			wp_cache_set( $user_id, $dismissed, 'bp_member_dismissed_notices' );
		}

		/**
		 * Gives ability to filter the user's dismissed notices.
		 *
		 * @since 15.0.0
		 *
		 * @param array   $dismissed The list of notice IDs the user dismissed.
		 * @param integer $user_id   The corresponding user ID.
		 */
		return apply_filters( 'bp_members_get_dismissed_notices_for_user', $dismissed, $user_id );
	}

	/**
	 * Returns the active notice that should be displayed on the front end.
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @since 15.0.0
	 *
	 * @return BP_Members_Notice
	 */
	public static function get_active() {
		$notice  = false;
		$user_id = bp_loggedin_user_id();
		$notice  = wp_cache_get( $user_id, 'bp_member_first_active_notice' );

		if ( false === $notice ) {
			$notice = self::get(
				array(
					'user_id'  => $user_id,
					'pag_page' => 1,
					'pag_num'  => 1,
					'type'     => 'first_active',
					'exclude'  => self::get_user_dismissed( $user_id ),
				)
			);

			// Cache user's first active notice.
			wp_cache_set( $user_id, $notice, 'bp_member_first_active_notice' );
		}

		/**
		 * Please do not use this filter anymore.
		 *
		 * @since 2.8.0
		 * @deprecated 15.0.0
		 *
		 * @param BP_Members_Notice $notice The notice object.
		 */
		$notice = apply_filters_deprecated( 'messages_notice_get_active', array( $notice ), '15.0.0', 'bp_members_notice_get_active' );

		/**
		 * Gives ability to filter the first active notice for the current user.
		 *
		 * @since 15.0.0
		 *
		 * @param BP_Members_Notice $notice The notice object.
		 */
		return apply_filters( 'bp_members_notice_get_active', $notice );
	}
}
