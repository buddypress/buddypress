<?php
/**
 * BuddyPress Invitation Class
 *
 * @package BuddyPress
 * @subpackage Invitations
 *
 * @since 5.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BuddyPress Invitations.
 *
 * Use this class to create, access, edit, or delete BuddyPress Invitations.
 *
 * @since 5.0.0
 */
class BP_Invitation {

	/**
	 * The invitation ID.
	 *
	 * @since 5.0.0
	 * @access public
	 * @var int
	 */
	public $id;

	/**
	 * The ID of the invited user.
	 *
	 * @since 5.0.0
	 * @access public
	 * @var int
	 */
	public $user_id;

	/**
	 * The ID of the user who created the invitation.
	 *
	 * @since 5.0.0
	 * @access public
	 * @var int
	 */
	public $inviter_id;

	/**
	 * The email address of the invited user.
	 * Used when extending an invitation to someone who does not belong to the site.
	 *
	 * @since 5.0.0
	 * @access public
	 * @var string
	 */
	public $invitee_email;

	/**
	 * The name of the related class.
	 *
	 * @since 5.0.0
	 * @access public
	 * @var string
	 */
	public $class;

	/**
	 * The ID associated with the invitation and component.
	 * Example: the group ID if a group invitation
	 *
	 * @since 5.0.0
	 * @access public
	 * @var int
	 */
	public $item_id;

	/**
	 * The secondary ID associated with the invitation and component.
	 * Example: a taxonomy term ID if invited to a site's category-specific RSS feed
	 *
	 * @since 5.0.0
	 * @access public
	 * @var int
	 */
	public $secondary_item_id = null;

	/**
	 * Invite or request.
	 *
	 * @since 5.0.0
	 * @access public
	 * @var string
	 */
	public $type;

	/**
	 * Extra information provided by the requester or inviter.
	 *
	 * @since 5.0.0
	 * @access public
	 * @var string
	 */
	public $content;

	/**
	 * The date the invitation was last modified.
	 *
	 * @since 5.0.0
	 * @access public
	 * @var string
	 */
	public $date_modified;

	/**
	 * Has the invitation been sent, or is it a draft invite?
	 *
	 * @since 5.0.0
	 * @access public
	 * @var bool
	 */
	public $invite_sent;

	/**
	 * Has the invitation been accepted by the invitee?
	 *
	 * @since 5.0.0
	 * @access public
	 * @var bool
	 */
	public $accepted;

	/**
	 * Columns in the invitations table.
	 *
	 * @since 9.0.0
	 * @access public
	 * @var array
	 */
	public static $columns = array(
		'id',
		'user_id',
		'inviter_id',
		'invitee_email',
		'class',
		'item_id',
		'secondary_item_id',
		'type',
		'content',
		'date_modified',
		'invite_sent',
		'accepted'
	);

	/** Public Methods ****************************************************/

	/**
	 * Constructor method.
	 *
	 * @since 5.0.0
	 *
	 * @param int $id Optional. Provide an ID to access an existing
	 *        invitation item.
	 */
	public function __construct( $id = 0 ) {
		if ( ! empty( $id ) ) {
			$this->id = (int) $id;
			$this->populate();
		}
	}

	/**
	 * Update or insert invitation details into the database.
	 *
	 * @since 5.0.0
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function save() {

		// Return value
		$retval = false;

		// Default data and format
		$data = array(
			'user_id'           => $this->user_id,
			'inviter_id'        => $this->inviter_id,
			'invitee_email'     => $this->invitee_email,
			'class'             => sanitize_key( $this->class ),
			'item_id'           => $this->item_id,
			'secondary_item_id' => $this->secondary_item_id,
			'type'              => $this->type,
			'content'           => wp_kses( wp_unslash( $this->content ), array() ),
			'date_modified'     => $this->date_modified,
			'invite_sent'       => $this->invite_sent,
			'accepted'          => $this->accepted,
		);
		$data_format = array( '%d', '%d', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%d', '%d' );

		/**
		 * Fires before an invitation is saved.
		 *
		 * @since 5.0.0
		 *
		 * @param BP_Invitation object $this Characteristics of the invitation to be saved.
		 */
		do_action_ref_array( 'bp_invitation_before_save', array( &$this ) );

		// Update.
		if ( ! empty( $this->id ) ) {
			$result = self::_update( $data, array( 'ID' => $this->id ), $data_format, array( '%d' ) );
		// Insert.
		} else {
			$result = self::_insert( $data, $data_format );
		}

		// Set the invitation ID if successful.
		if ( ! empty( $result ) && ! is_wp_error( $result ) ) {
			global $wpdb;

			$this->id = $wpdb->insert_id;
			$retval   = $wpdb->insert_id;
		}

		wp_cache_delete( $this->id, 'bp_invitations' );

		/**
		 * Fires after an invitation is saved.
		 *
		 * @since 5.0.0
		 *
		 * @param BP_Invitation object $this Characteristics of the invitation just saved.
		 */
		do_action_ref_array( 'bp_invitation_after_save', array( &$this ) );

		// Return the result.
		return $retval;
	}

	/**
	 * Fetch data for an existing invitation from the database.
	 *
	 * @since 5.0.0
	 *
	 * @global wpdb $wpdb WordPress database object.
	 */
	public function populate() {
		global $wpdb;
		$invites_table_name = BP_Invitation_Manager::get_table_name();

		// Check cache for invitation data.
		$invitation = wp_cache_get( $this->id, 'bp_invitations' );

		// Cache missed, so query the DB.
		if ( false === $invitation ) {
			$invitation = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$invites_table_name} WHERE id = %d", $this->id ) );
			wp_cache_set( $this->id, $invitation,'bp_invitations' );
		}

		// No invitation found so set the ID and bail.
		if ( empty( $invitation ) || is_wp_error( $invitation ) ) {
			$this->id = 0;
			return;
		}

		$this->user_id           = (int) $invitation->user_id;
		$this->inviter_id        = (int) $invitation->inviter_id;
		$this->invitee_email     = $invitation->invitee_email;
		$this->class             = sanitize_key( $invitation->class );
		$this->item_id           = (int) $invitation->item_id;
		$this->secondary_item_id = (int) $invitation->secondary_item_id;
		$this->type              = $invitation->type;
		$this->content           = $invitation->content;
		$this->date_modified     = $invitation->date_modified;
		$this->invite_sent       = (int) $invitation->invite_sent;
		$this->accepted          = (int) $invitation->accepted;

	}

	/** Protected Static Methods ******************************************/

	/**
	 * Create an invitation entry.
	 *
	 * @since 5.0.0
	 *
	 * @param array $data {
	 *     Array of invitation data, passed to {@link wpdb::insert()}.
	 *	   @type int    $user_id           ID of the invited user.
	 *	   @type int    $inviter_id        ID of the user who created the invitation.
	 *	   @type string $invitee_email     Email address of the invited user.
	 * 	   @type string $class             Name of the related class.
	 * 	   @type int    $item_id           ID associated with the invitation and component.
	 * 	   @type int    $secondary_item_id Secondary ID associated with the invitation and
	 *                                     component.
	 * 	   @type string $content           Extra information provided by the requester
	 *			                           or inviter.
	 * 	   @type string $date_modified     Date the invitation was last modified.
	 * 	   @type int    $invite_sent       Has the invitation been sent, or is it a draft
	 *                                     invite?
	 * }
	 * @param array $data_format See {@link wpdb::insert()}.
	 * @return int|false The number of rows inserted, or false on error.
	 */
	protected static function _insert( $data = array(), $data_format = array() ) {
		global $wpdb;
		return $wpdb->insert( BP_Invitation_Manager::get_table_name(), $data, $data_format );
	}

	/**
	 * Update invitations.
	 *
	 * @since 5.0.0
	 *
	 * @see wpdb::update() for further description of paramater formats.
	 *
	 * @param array $data         Array of invitation data to update, passed to
	 *                            {@link wpdb::update()}. Accepts any property of a
	 *                            BP_Invitation object.
	 * @param array $where        The WHERE params as passed to wpdb::update().
	 *                            Typically consists of array( 'ID' => $id ) to specify the ID
	 *                            of the item being updated. See {@link wpdb::update()}.
	 * @param array $data_format  See {@link wpdb::insert()}.
	 * @param array $where_format See {@link wpdb::insert()}.
	 * @return int|false The number of rows updated, or false on error.
	 */
	protected static function _update( $data = array(), $where = array(), $data_format = array(), $where_format = array() ) {
		global $wpdb;
		return $wpdb->update( BP_Invitation_Manager::get_table_name(), $data, $where, $data_format, $where_format );
	}

	/**
	 * Delete invitations.
	 *
	 * @since 5.0.0
	 *
	 * @see wpdb::update() for further description of paramater formats.
	 *
	 * @param array $where        Array of WHERE clauses to filter by, passed to
	 *                            {@link wpdb::delete()}. Accepts any property of a
	 *                            BP_Invitation object.
	 * @param array $where_format See {@link wpdb::insert()}.
	 * @return int|false The number of rows updated, or false on error.
	 */
	protected static function _delete( $where = array(), $where_format = array() ) {
		global $wpdb;
		return $wpdb->delete( BP_Invitation_Manager::get_table_name(), $where, $where_format );
	}

	/**
	 * Assemble the WHERE clause of a get() SQL statement.
	 *
	 * Used by BP_Invitation::get() to create its WHERE
	 * clause.
	 *
	 * @since 5.0.0
	 *
	 * @param array $args See {@link BP_Invitation::get()} for more details.
	 * @return string WHERE clause.
	 */
	protected static function get_where_sql( $args = array() ) {
		global $wpdb;

		$where_conditions = array();
		$where            = '';

		// id.
		if ( false !== $args['id'] ) {
			$id_in = implode( ',', wp_parse_id_list( $args['id'] ) );
			$where_conditions['id'] = "id IN ({$id_in})";
		}

		// user_id.
		if ( ! empty( $args['user_id'] ) ) {
			$user_id_in = implode( ',', wp_parse_id_list( $args['user_id'] ) );
			$where_conditions['user_id'] = "user_id IN ({$user_id_in})";
		}

		// inviter_id. 0 can be meaningful, in the case of requests.
		if ( ! empty( $args['inviter_id'] ) || 0 === $args['inviter_id'] ) {
			$inviter_id_in = implode( ',', wp_parse_id_list( $args['inviter_id'] ) );
			$where_conditions['inviter_id'] = "inviter_id IN ({$inviter_id_in})";
		}

		// invitee_email.
		if ( ! empty( $args['invitee_email'] ) ) {
			if ( ! is_array( $args['invitee_email'] ) ) {
				$invitee_emails = explode( ',', $args['invitee_email'] );
			} else {
				$invitee_emails = $args['invitee_email'];
			}

			$email_clean = array();
			foreach ( $invitee_emails as $email ) {
				$email_clean[] = $wpdb->prepare( '%s', $email );
			}

			$invitee_email_in = implode( ',', $email_clean );
			$where_conditions['invitee_email'] = "invitee_email IN ({$invitee_email_in})";
		}

		// class.
		if ( ! empty( $args['class'] ) ) {
			if ( ! is_array( $args['class'] ) ) {
				$class_names = explode( ',', $args['class'] );
			} else {
				$class_names = $args['class'];
			}

			$cn_clean = array();
			foreach ( $class_names as $cn ) {
				$cn_clean[] = $wpdb->prepare( '%s', sanitize_key( $cn ) );
			}

			$cn_in = implode( ',', $cn_clean );
			$where_conditions['class'] = "class IN ({$cn_in})";
		}

		// item_id.
		if ( ! empty( $args['item_id'] ) ) {
			$item_id_in = implode( ',', wp_parse_id_list( $args['item_id'] ) );
			$where_conditions['item_id'] = "item_id IN ({$item_id_in})";
		}

		// secondary_item_id.
		if ( ! empty( $args['secondary_item_id'] ) ) {
			$secondary_item_id_in = implode( ',', wp_parse_id_list( $args['secondary_item_id'] ) );
			$where_conditions['secondary_item_id'] = "secondary_item_id IN ({$secondary_item_id_in})";
		}

		// Type.
		if ( ! empty( $args['type'] ) && 'all' !== $args['type'] ) {
			if ( 'invite' == $args['type'] || 'request' == $args['type'] ) {
				$type_clean = $wpdb->prepare( '%s', $args['type'] );
				$where_conditions['type'] = "type = {$type_clean}";
			}
		}

		/**
		 * invite_sent
		 * Only create a where statement if something less than "all" has been
		 * specifically requested.
		 */
		if ( ! empty( $args['invite_sent'] ) && 'all' !== $args['invite_sent'] ) {
			if ( $args['invite_sent'] == 'draft' ) {
				$where_conditions['invite_sent'] = "invite_sent = 0";
			} else if ( $args['invite_sent'] == 'sent' ) {
				$where_conditions['invite_sent'] = "invite_sent = 1";
			}
		}

		// Accepted.
		if ( ! empty( $args['accepted'] ) && 'all' !== $args['accepted'] ) {
			if ( $args['accepted'] == 'pending' ) {
				$where_conditions['accepted'] = "accepted = 0";
			} else if ( $args['accepted'] == 'accepted' ) {
				$where_conditions['accepted'] = "accepted = 1";
			}
		}

		// search_terms.
		if ( ! empty( $args['search_terms'] ) ) {
			$search_terms_like = '%' . bp_esc_like( $args['search_terms'] ) . '%';
			$where_conditions['search_terms'] = $wpdb->prepare( '( invitee_email LIKE %s OR content LIKE %s )', $search_terms_like, $search_terms_like );
		}

		// Custom WHERE.
		if ( ! empty( $where_conditions ) ) {
			$where = 'WHERE ' . implode( ' AND ', $where_conditions );
		}

		return $where;
	}

	/**
	 * Assemble the ORDER BY clause of a get() SQL statement.
	 *
	 * Used by BP_Invitation::get() to create its ORDER BY
	 * clause.
	 *
	 * @since 5.0.0
	 *
	 * @param array $args See {@link BP_Invitation::get()} for more details.
	 * @return string ORDER BY clause.
	 */
	protected static function get_order_by_sql( $args = array() ) {

		// Setup local variable.
		$conditions = array();
		$retval     = '';

		// Order by.
		if ( ! empty( $args['order_by'] ) ) {
			$order_by_clean = array();
			foreach ( (array) $args['order_by'] as $key => $value ) {
				if ( in_array( $value, self::$columns, true ) ) {
					$order_by_clean[] = $value;
				}
			}
			if ( ! empty( $order_by_clean ) ) {
				$order_by               = implode( ', ', $order_by_clean );
				$conditions['order_by'] = "{$order_by}";
			}
		}

		// Sort order direction.
		if ( ! empty( $args['sort_order'] ) ) {
			$sort_order               = bp_esc_sql_order( $args['sort_order'] );
			$conditions['sort_order'] = "{$sort_order}";
		}

		// Custom ORDER BY.
		if ( ! empty( $conditions ) ) {
			$retval = 'ORDER BY ' . implode( ' ', $conditions );
		}

		return $retval;
	}

	/**
	 * Assemble the LIMIT clause of a get() SQL statement.
	 *
	 * Used by BP_Invitation::get() to create its LIMIT clause.
	 *
	 * @since 5.0.0
	 *
	 * @param array $args See {@link BP_Invitation::get()} for more details.
	 * @return string LIMIT clause.
	 */
	protected static function get_paged_sql( $args = array() ) {
		global $wpdb;

		// Setup local variable.
		$retval = '';

		// Custom LIMIT.
		if ( ! empty( $args['page'] ) && ! empty( $args['per_page'] ) ) {
			$page     = absint( $args['page']     );
			$per_page = absint( $args['per_page'] );
			$offset   = $per_page * ( $page - 1 );
			$retval   = $wpdb->prepare( "LIMIT %d, %d", $offset, $per_page );
		}

		return $retval;
	}

	/**
	 * Assemble query clauses, based on arguments, to pass to $wpdb methods.
	 *
	 * The insert(), update(), and delete() methods of {@link wpdb} expect
	 * arguments of the following forms:
	 *
	 * - associative arrays whose key/value pairs are column => value, to
	 *   be used in WHERE, SET, or VALUES clauses
	 * - arrays of "formats", which tell $wpdb->prepare() which type of
	 *   value to expect when sanitizing (eg, array( '%s', '%d' ))
	 *
	 * This utility method can be used to assemble both kinds of params,
	 * out of a single set of associative array arguments, such as:
	 *
	 *     $args = array(
	 *         'user_id' => 4,
	 *         'class'   => 'BP_Groups_Invitation_Manager',
	 *     );
	 *
	 * This will be converted to:
	 *
	 *     array(
	 *         'data' => array(
	 *             'user_id' => 4,
	 *             'class'   => 'BP_Groups_Invitation_Manager',
	 *         ),
	 *         'format' => array(
	 *             '%d',
	 *             '%s',
	 *         ),
	 *     )
	 *
	 * which can easily be passed as arguments to the $wpdb methods.
	 *
	 * @since 5.0.0
	 *
	 * @param array $args Associative array of filter arguments.
	 *                    See {@BP_Invitation::get()} for a breakdown.
	 * @return array Associative array of 'data' and 'format' args.
	 */
	protected static function get_query_clauses( $args = array() ) {
		$where_clauses = array(
			'data'   => array(),
			'format' => array(),
		);

		// id.
		if ( ! empty( $args['id'] ) ) {
			$where_clauses['data']['id'] = absint( $args['id'] );
			$where_clauses['format'][] = '%d';
		}

		// user_id.
		if ( ! empty( $args['user_id'] ) ) {
			$where_clauses['data']['user_id'] = absint( $args['user_id'] );
			$where_clauses['format'][] = '%d';
		}

		// inviter_id.
		if ( ! empty( $args['inviter_id'] ) ) {
			$where_clauses['data']['inviter_id'] = absint( $args['inviter_id'] );
			$where_clauses['format'][] = '%d';
		}

		// invitee_email.
		if ( ! empty( $args['invitee_email'] ) ) {
			$where_clauses['data']['invitee_email'] = $args['invitee_email'];
			$where_clauses['format'][] = '%s';
		}

		// class.
		if ( ! empty( $args['class'] ) ) {
			$where_clauses['data']['class'] = $args['class'];
			$where_clauses['format'][] = '%s';
		}

		// item_id.
		if ( ! empty( $args['item_id'] ) ) {
			$where_clauses['data']['item_id'] = absint( $args['item_id'] );
			$where_clauses['format'][] = '%d';
		}

		// secondary_item_id.
		if ( ! empty( $args['secondary_item_id'] ) ) {
			$where_clauses['data']['secondary_item_id'] = absint( $args['secondary_item_id'] );
			$where_clauses['format'][] = '%d';
		}

		// type.
		if ( ! empty( $args['type'] ) && 'all' !== $args['type'] ) {
			if ( 'invite' == $args['type'] || 'request' == $args['type'] ) {
				$where_clauses['data']['type'] = $args['type'];
				$where_clauses['format'][] = '%s';
			}
		}

		/**
		 * invite_sent
		 * Only create a where statement if something less than "all" has been
		 * specifically requested.
		 */
		if ( isset( $args['invite_sent'] ) && 'all' !== $args['invite_sent'] ) {
			if ( $args['invite_sent'] == 'draft' ) {
				$where_clauses['data']['invite_sent'] = 0;
				$where_clauses['format'][] = '%d';
			} else if ( $args['invite_sent'] == 'sent' ) {
				$where_clauses['data']['invite_sent'] = 1;
				$where_clauses['format'][] = '%d';
			}
		}

		// accepted.
		if ( ! empty( $args['accepted'] ) && 'all' !== $args['accepted'] ) {
			if ( $args['accepted'] == 'pending' ) {
				$where_clauses['data']['accepted'] = 0;
				$where_clauses['format'][] = '%d';
			} else if ( $args['accepted'] == 'accepted' ) {
				$where_clauses['data']['accepted'] = 1;
				$where_clauses['format'][] = '%d';
			}
		}

		// date_modified
		if ( ! empty( $args['date_modified'] ) ) {
			$where_clauses['data']['date_modified'] = $args['date_modified'];
			$where_clauses['format'][] = '%s';
		}

		return $where_clauses;
	}

	/** Public Static Methods *********************************************/

	/**
	 * Get invitations, based on provided filter parameters.
	 *
	 * @since 5.0.0
	 *
	 * @param array $args {
	 *     Associative array of arguments. All arguments but $page and
	 *     $per_page can be treated as filter values for get_where_sql()
	 *     and get_query_clauses(). All items are optional.
	 *     @type int|array    $id                ID of invitation being fetched.
	 *                                           Can be an array of IDs.
	 *     @type int|array    $user_id           ID of user being queried. Can be an
	 *                                           Can be an array of IDs.
	 *     @type int|array    $inviter_id        ID of user who created the
	 *                                           invitation. Can be an array of IDs.
	 *     @type string|array $invitee_email     Email address of invited users
	 *			                                 being queried. Can be an array of
	 *                                           addresses.
	 *     @type string|array $class             Name of the class to filter by.
	 *                                           Can be an array of class names.
	 *     @type int|array    $item_id           ID of associated item.
	 *                                           Can be an array of multiple item IDs.
	 *     @type int|array    $secondary_item_id ID of secondary associated item.
	 *                                           Can be an array of multiple IDs.
	 *     @type string|array $type              Type of item. An "invite" is sent
	 *                                           from one user to another.
	 *                                           A "request" is submitted by a
	 *                                           user and no inviter is required.
	 *                                           'all' returns all. Default: 'all'.
	 *     @type string       $invite_sent       Limit to draft, sent or all
	 *                                           'draft' limits to unsent invites,
	 *                                           'sent' returns only sent invites,
	 *                                           'all' returns all. Default: 'all'.
	 *     @type bool         $accepted          Limit to accepted or
	 *                                           not-yet-accepted invitations.
	 *                                           'accepted' returns accepted invites,
	 *                                           'pending' returns pending invites,
	 *                                           'all' returns all. Default: 'pending'
	 *     @type string       $search_terms      Term to match against class field.
	 *     @type string       $order_by          Database column to order by.
	 *     @type string       $sort_order        Either 'ASC' or 'DESC'.
	 *     @type string       $order_by          Field to order results by.
	 *     @type string       $sort_order        ASC or DESC.
	 *     @type int          $page              Number of the current page of results.
	 *                                           Default: false (no pagination,
	 *                                           all items).
	 *     @type int          $per_page          Number of items to show per page.
	 *                                           Default: false (no pagination,
	 *                                           all items).
	 *     @type string       $fields            Which fields to return. Specify 'item_ids' to fetch a list of Item_IDs.
	 *                                           Specify 'ids' to fetch a list of Invitation IDs.
	 *                                           Default: 'all' (return BP_Invitation objects).
	 * }
	 *
	 * @return array BP_Invitation objects | IDs of found invite.
	 */
	public static function get( $args = array() ) {
		global $wpdb;
		$invites_table_name = BP_Invitation_Manager::get_table_name();

		// Parse the arguments.
		$r = bp_parse_args(
			$args,
			array(
				'id'                => false,
				'user_id'           => false,
				'inviter_id'        => false,
				'invitee_email'     => false,
				'class'             => false,
				'item_id'           => false,
				'secondary_item_id' => false,
				'type'              => 'all',
				'invite_sent'       => 'all',
				'accepted'          => 'pending',
				'search_terms'      => '',
				'order_by'          => false,
				'sort_order'        => false,
				'page'              => false,
				'per_page'          => false,
				'fields'            => 'all',
			),
			'bp_invitations_invitation_get'
		);

		$sql = array(
			'select'     => "SELECT",
			'fields'     => '',
			'from'       => "FROM {$invites_table_name} i",
			'where'      => '',
			'orderby'    => '',
			'pagination' => '',
		);

		if ( 'item_ids' === $r['fields'] ) {
			$sql['fields'] = "DISTINCT i.item_id";
		} else if ( 'user_ids' === $r['fields'] ) {
			$sql['fields'] = "DISTINCT i.user_id";
		} else if ( 'inviter_ids' === $r['fields'] ) {
			$sql['fields'] = "DISTINCT i.inviter_id";
		} else {
			$sql['fields'] = 'DISTINCT i.id';
		}

		// WHERE.
		$sql['where'] = self::get_where_sql( array(
			'id'                => $r['id'],
			'user_id'           => $r['user_id'],
			'inviter_id'		=> $r['inviter_id'],
			'invitee_email'     => $r['invitee_email'],
			'class'             => $r['class'],
			'item_id'           => $r['item_id'],
			'secondary_item_id' => $r['secondary_item_id'],
			'type'              => $r['type'],
			'invite_sent'       => $r['invite_sent'],
			'accepted'          => $r['accepted'],
			'search_terms'      => $r['search_terms'],
		) );

		// ORDER BY.
		$sql['orderby'] = self::get_order_by_sql( array(
			'order_by'   => $r['order_by'],
			'sort_order' => $r['sort_order']
		) );

		// LIMIT %d, %d.
		$sql['pagination'] = self::get_paged_sql( array(
			'page'     => $r['page'],
			'per_page' => $r['per_page'],
		) );

		$paged_invites_sql = "{$sql['select']} {$sql['fields']} {$sql['from']} {$sql['where']} {$sql['orderby']} {$sql['pagination']}";

		/**
		 * Filters the pagination SQL statement.
		 *
		 * @since 5.0.0
		 *
		 * @param string $value Concatenated SQL statement.
		 * @param array  $sql   Array of SQL parts before concatenation.
		 * @param array  $r     Array of parsed arguments for the get method.
		 */
		$paged_invites_sql = apply_filters( 'bp_invitations_get_paged_invitations_sql', $paged_invites_sql, $sql, $r );

		$cached = bp_core_get_incremented_cache( $paged_invites_sql, 'bp_invitations' );
		if ( false === $cached ) {
			$paged_invite_ids = $wpdb->get_col( $paged_invites_sql );
			bp_core_set_incremented_cache( $paged_invites_sql, 'bp_invitations', $paged_invite_ids );
		} else {
			$paged_invite_ids = $cached;
		}

		// Special return format cases.
		if ( in_array( $r['fields'], array( 'ids', 'item_ids', 'user_ids', 'inviter_ids' ), true ) ) {
			// We only want the field that was found.
			return array_map( 'intval', $paged_invite_ids );
		}

		$uncached_ids = bp_get_non_cached_ids( $paged_invite_ids, 'bp_invitations' );
		if ( $uncached_ids ) {
			$ids_sql = implode( ',', array_map( 'intval', $uncached_ids ) );
			$data_objects = $wpdb->get_results( "SELECT i.* FROM {$invites_table_name} i WHERE i.id IN ({$ids_sql})" );
			foreach ( $data_objects as $data_object ) {
				wp_cache_set( $data_object->id, $data_object, 'bp_invitations' );
			}
		}

		$paged_invites = array();
		foreach ( $paged_invite_ids as $paged_invite_id ) {
			$paged_invites[] = new BP_Invitation( $paged_invite_id );
		}

		return $paged_invites;
	}

	/**
	 * Get a count of total invitations matching a set of arguments.
	 *
	 * @since 5.0.0
	 *
	 * @see BP_Invitation::get() for a description of
	 *      arguments.
	 *
	 * @param array $args See {@link BP_Invitation::get()}.
	 * @return int Count of located items.
	 */
	public static function get_total_count( $args ) {
		global $wpdb;
		$invites_table_name = BP_Invitation_Manager::get_table_name();

		$r = bp_parse_args(
			$args,
			array(
				'id'                => false,
				'user_id'           => false,
				'inviter_id'        => false,
				'invitee_email'     => false,
				'class'             => false,
				'item_id'           => false,
				'secondary_item_id' => false,
				'type'              => 'all',
				'invite_sent'       => 'all',
				'accepted'          => 'pending',
				'search_terms'      => '',
				'order_by'          => false,
				'sort_order'        => false,
				'page'              => false,
				'per_page'          => false,
				'fields'            => 'all',
			),
			'bp_invitations_invitation_get_total_count'
		);

		// Build the query
		$select_sql = "SELECT COUNT(*)";
		$from_sql   = "FROM {$invites_table_name}";
		$where_sql  = self::get_where_sql( $r );
		$sql        = "{$select_sql} {$from_sql} {$where_sql}";

		// Return the queried results
		return $wpdb->get_var( $sql );
	}

	/**
	 * Update invitations.
	 *
	 * @since 5.0.0
	 *
	 * @see BP_Invitation::get() for a description of
	 *      accepted update/where arguments.
	 *
	 * @param array $update_args Associative array of fields to update,
	 *                           and the values to update them to. Of the format
	 *                           array( 'user_id' => 4, 'class' => 'BP_Groups_Invitation_Manager', ).
	 * @param array $where_args  Associative array of columns/values, to
	 *                           determine which rows should be updated. Of the format
	 *                           array( 'item_id' => 7, 'class' => 'BP_Groups_Invitation_Manager', ).
	 * @return int|bool Number of rows updated on success, false on failure.
	 */
	public static function update( $update_args = array(), $where_args = array() ) {
		$update = self::get_query_clauses( $update_args );
		$where  = self::get_query_clauses( $where_args  );

		/**
		 * Fires before an invitation is updated.
		 *
		 * @since 5.0.0
		 *
		 * @param array $where_args  Associative array of columns/values describing
		 *                           invitations about to be deleted.
		 * @param array $update_args Array of new values.
		 */
		do_action( 'bp_invitation_before_update', $where_args, $update_args );

		$retval = self::_update( $update['data'], $where['data'], $update['format'], $where['format'] );

		// Clear matching items from the cache.
		$cache_args = $where_args;
		$cache_args['fields'] = 'ids';
		$maybe_cached_ids = self::get( $cache_args );
		foreach ( $maybe_cached_ids as $invite_id ) {
			wp_cache_delete( $invite_id, 'bp_invitations' );
		}

		/**
		 * Fires after an invitation is updated.
		 *
		 * @since 5.0.0
		 *
		 * @param array $where_args  Associative array of columns/values describing
		 *                           invitations about to be deleted.
		 * @param array $update_args Array of new values.
		 */
		do_action( 'bp_invitation_after_update', $where_args, $update_args );

		return $retval;
	}

	/**
	 * Delete invitations.
	 *
	 * @since 5.0.0
	 *
	 * @see BP_Invitation::get() for a description of
	 *      accepted where arguments.
	 *
	 * @param array $args Associative array of columns/values, to determine
	 *                    which rows should be deleted.  Of the format
	 *                    array( 'item_id' => 7, 'class' => 'BP_Groups_Invitation_Manager', ).
	 * @return int|bool Number of rows deleted on success, false on failure.
	 */
	public static function delete( $args = array() ) {
		$where = self::get_query_clauses( $args );

		/**
		 * Fires before an invitation is deleted.
		 *
		 * @since 5.0.0
		 *
		 * @param array $args Characteristics of the invitations to be deleted.
		 */
		do_action( 'bp_invitation_before_delete', $args );

		// Clear matching items from the cache.
		$cache_args = $args;
		$cache_args['fields'] = 'ids';
		$maybe_cached_ids = self::get( $cache_args );
		foreach ( $maybe_cached_ids as $invite_id ) {
			wp_cache_delete( $invite_id, 'bp_invitations' );
		}

		$retval = self::_delete( $where['data'], $where['format'] );

		/**
		 * Fires after an invitation is deleted.
		 *
		 * @since 5.0.0
		 *
		 * @param array $args Characteristics of the invitations just deleted.
		 */
		do_action( 'bp_invitation_after_delete', $args );

		return $retval;
	}

	/** Convenience methods ***********************************************/

	/**
	 * Delete a single invitation by ID.
	 *
	 * @since 5.0.0
	 *
	 * @see BP_Invitation::delete() for explanation of
	 *      return value.
	 *
	 * @param int $id ID of the invitation item to be deleted.
	 * @return bool True on success, false on failure.
	 */
	public static function delete_by_id( $id ) {
		return self::delete( array(
			'id' => $id,
		) );
	}

	/** Sent status ***********************************************************/

	/**
	 * Mark specific invitations as sent by invitation ID.
	 *
	 * @since 5.0.0
	 *
	 * @param int   $id   The ID of the invitation to mark as sent.
	 * @param array $args {
	 *     Optional. Invitation characteristics used
	 *     to override certain sending behaviors.
	 *
	 *     @type string $date_modified Modified time in 'Y-m-d h:i:s' format, GMT.
	 *                                 Defaults to current time if not specified.
	 * }
	 * @return int|bool The number of rows updated, or false on error.
	 */
	public static function mark_sent( $id = 0, $args = array() ) {

		if ( ! $id ) {
			return false;
		}

		// Values to be updated.
		$update_args = array(
			'invite_sent'   => 'sent',
			'date_modified' => bp_core_current_time(),
		);
		// Respect a specified `date-modified`.
		if ( ! empty( $args['date_modified'] ) ) {
			$update_args['date_modified'] = $args['date_modified'];
		}

		// WHERE clauses.
		$where_args = array(
			'id' => $id,
		);

		return self::update( $update_args, $where_args );
	}

	/**
	 * Mark invitations as sent that are found by user_id, inviter_id, item id, and optional
	 * secondary item id, and class name.
	 *
	 * @since 5.0.0
	 *
	 * @param array $args See BP_Invitation::update().
	 * @return int|bool The number of rows updated, or false on error.
	 */
	public static function mark_sent_by_data( $args ) {

		// Values to be updated.
		$update_args = array(
			'invite_sent'   => 'sent',
			'date_modified' => bp_core_current_time(),
		);
		// Respect a specified `date-modified`.
		if ( ! empty( $args['date_modified'] ) ) {
			$update_args['date_modified'] = $args['date_modified'];
		}

		return self::update( $update_args, $args );
	}

	/** Accepted status ***********************************************************/

	/**
	 * Mark specific invitations as accepted by invitation ID.
	 *
	 * @since 5.0.0
	 *
	 * @param int   $id   The ID of the invitation to mark as sent.
	 * @param array $args {
	 *     Optional. Invitation characteristics used
	 *     to override certain sending behaviors.
	 *
	 *     @type string $date_modified Modified time in 'Y-m-d h:i:s' format, GMT.
	 *                                 Defaults to current time if not specified.
	 * }
	 * @return int|bool The number of rows updated, or false on error.
	 */
	public static function mark_accepted( $id = 0, $args = array() ) {

		if ( ! $id ) {
			return false;
		}

		// Values to be updated.
		$update_args = array(
			'accepted'      => 'accepted',
			'date_modified' => bp_core_current_time(),
		);
		// Respect a specified `date-modified`.
		if ( ! empty( $args['date_modified'] ) ) {
			$update_args['date_modified'] = $args['date_modified'];
		}

		// WHERE clauses.
		$where_args = array(
			'id' => $id,
		);

		return self::update( $update_args, $where_args );
	}

	/**
	 * Mark invitations as accepted that are found by user_id, inviter_id,
	 * item id, and optional secondary item id, and class name.
	 *
	 * @since 5.0.0
	 *
	 * @param array $args See BP_Invitation::update().
	 * @return int|bool The number of rows updated, or false on error.
	 */
	public static function mark_accepted_by_data( $args ) {

		// Values to be updated.
		$update_args = array(
			'accepted'      => 'accepted',
			'date_modified' => bp_core_current_time(),
		);
		// Respect a specified `date-modified`.
		if ( ! empty( $args['date_modified'] ) ) {
			$update_args['date_modified'] = $args['date_modified'];
		}

		return self::update( $update_args, $args );
	}

}
