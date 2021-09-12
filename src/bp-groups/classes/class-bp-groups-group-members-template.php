<?php
/**
 * BuddyPress Groups group members loop template class.
 *
 * @package BuddyPress
 * @since 1.1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Group Members Loop template class.
 *
 * @since 1.0.0
 */
class BP_Groups_Group_Members_Template {

	/**
	 * @since 1.0.0
	 * @var int
	 */
	public $current_member = -1;

	/**
	 * @since 1.0.0
	 * @var int
	 */
	public $member_count;

	/**
	 * @since 1.0.0
	 * @var array
	 */
	public $members;

	/**
	 * @since 1.0.0
	 * @var object
	 */
	public $member;

	/**
	 * @since 1.0.0
	 * @var bool
	 */
	public $in_the_loop;

	/**
	 * @since 1.0.0
	 * @var int
	 */
	public $pag_page;

	/**
	 * @since 1.0.0
	 * @var int
	 */
	public $pag_num;

	/**
	 * @since 1.0.0
	 * @var array|string|void
	 */
	public $pag_links;

	/**
	 * @since 1.0.0
	 * @var int
	 */
	public $total_group_count;

	/**
	 * Constructor.
	 *
	 * @since 1.5.0
	 *
	 * @param array $args {
	 *     An array of optional arguments.
	 *     @type int      $group_id           ID of the group whose members are being
	 *                                        queried. Default: current group ID.
	 *     @type int      $page               Page of results to be queried. Default: 1.
	 *     @type int      $per_page           Number of items to return per page of
	 *                                        results. Default: 20.
	 *     @type int      $max                Optional. Max number of items to return.
	 *     @type array    $exclude            Optional. Array of user IDs to exclude.
	 *     @type bool|int $exclude_admin_mods True (or 1) to exclude admins and mods from
	 *                                        results. Default: 1.
	 *     @type bool|int $exclude_banned     True (or 1) to exclude banned users from results.
	 *                                        Default: 1.
	 *     @type array    $group_role         Optional. Array of group roles to include.
	 *     @type string   $search_terms       Optional. Search terms to match.
	 * }
	 */
	public function __construct( $args = array() ) {
		$function_args = func_get_args();

		// Backward compatibility with old method of passing arguments.
		if ( ! is_array( $args ) || count( $function_args ) > 1 ) {
			/* translators: 1: the name of the method. 2: the name of the file. */
			_deprecated_argument( __METHOD__, '2.0.0', sprintf( __( 'Arguments passed to %1$s should be in an associative array. See the inline documentation at %2$s for more details.', 'buddypress' ), __METHOD__, __FILE__ ) );

			$old_args_keys = array(
				0 => 'group_id',
				1 => 'per_page',
				2 => 'max',
				3 => 'exclude_admins_mods',
				4 => 'exclude_banned',
				5 => 'exclude',
				6 => 'group_role',
			);

			$args = bp_core_parse_args_array( $old_args_keys, $function_args );
		}

		$r = bp_parse_args(
			$args,
			array(
				'group_id'            => bp_get_current_group_id(),
				'page'                => 1,
				'per_page'            => 20,
				'page_arg'            => 'mlpage',
				'max'                 => false,
				'exclude'             => false,
				'exclude_admins_mods' => 1,
				'exclude_banned'      => 1,
				'group_role'          => false,
				'search_terms'        => false,
				'type'                => 'last_joined',
			),
			'group_members_template'
		);

		$this->pag_arg  = sanitize_key( $r['page_arg'] );
		$this->pag_page = bp_sanitize_pagination_arg( $this->pag_arg, $r['page']     );
		$this->pag_num  = bp_sanitize_pagination_arg( 'num',          $r['per_page'] );

		/**
		 * Check the current group is the same as the supplied group ID.
		 * It can differ when using {@link bp_group_has_members()} outside the Groups screens.
		 */
		$current_group = groups_get_current_group();
		if ( empty( $current_group ) || ( $current_group && $current_group->id !== bp_get_current_group_id() ) ) {
			$current_group = groups_get_group( $r['group_id'] );
		}

		// Assemble the base URL for pagination.
		$base_url = trailingslashit( bp_get_group_permalink( $current_group ) . bp_current_action() );
		if ( bp_action_variable() ) {
			$base_url = trailingslashit( $base_url . bp_action_variable() );
		}

		$members_args = $r;

		$members_args['page']     = $this->pag_page;
		$members_args['per_page'] = $this->pag_num;

		// Get group members for this loop.
		$this->members = groups_get_group_members( $members_args );

		if ( empty( $r['max'] ) || ( $r['max'] >= (int) $this->members['count'] ) ) {
			$this->total_member_count = (int) $this->members['count'];
		} else {
			$this->total_member_count = (int) $r['max'];
		}

		// Reset members array for subsequent looping.
		$this->members = $this->members['members'];

		if ( empty( $r['max'] ) || ( $r['max'] >= count( $this->members ) ) ) {
			$this->member_count = (int) count( $this->members );
		} else {
			$this->member_count = (int) $r['max'];
		}

		$this->pag_links = paginate_links( array(
			'base'      => add_query_arg( array( $this->pag_arg => '%#%' ), $base_url ),
			'format'    => '',
			'total'     => ! empty( $this->pag_num ) ? ceil( $this->total_member_count / $this->pag_num ) : $this->total_member_count,
			'current'   => $this->pag_page,
			'prev_text' => '&larr;',
			'next_text' => '&rarr;',
			'mid_size'  => 1,
			'add_args'  => array(),
		) );
	}

	/**
	 * Whether or not there are members to display.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function has_members() {
		if ( ! empty( $this->member_count ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Increments to the next member to display.
	 *
	 * @since 1.0.0
	 *
	 * @return object
	 */
	public function next_member() {
		$this->current_member++;
		$this->member = $this->members[ $this->current_member ];

		return $this->member;
	}

	/**
	 * Rewinds to the first member to display.
	 *
	 * @since 1.0.0
	 */
	public function rewind_members() {
		$this->current_member = -1;
		if ( $this->member_count > 0 ) {
			$this->member = $this->members[0];
		}
	}

	/**
	 * Finishes up the members for display.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function members() {
		$tick = intval( $this->current_member + 1 );
		if ( $tick < $this->member_count ) {
			return true;
		} elseif ( $tick == $this->member_count ) {

			/**
			 * Fires right before the rewinding of members list.
			 *
			 * @since 1.0.0
			 * @since 2.3.0 `$this` parameter added.
			 * @since 2.7.0 Action renamed from `loop_end`.
			 *
			 * @param BP_Groups_Group_Members_Template $this Instance of the current Members template.
			 */
			do_action( 'group_members_loop_end', $this );

			// Do some cleaning up after the loop.
			$this->rewind_members();
		}

		$this->in_the_loop = false;
		return false;
	}

	/**
	 * Sets up the member to display.
	 *
	 * @since 1.0.0
	 */
	public function the_member() {
		$this->in_the_loop = true;
		$this->member      = $this->next_member();

		// Loop has just started.
		if ( 0 == $this->current_member ) {

			/**
			 * Fires if the current member item is the first in the members list.
			 *
			 * @since 1.0.0
			 * @since 2.3.0 `$this` parameter added.
			 * @since 2.7.0 Action renamed from `loop_start`.
			 *
			 * @param BP_Groups_Group_Members_Template $this Instance of the current Members template.
			 */
			do_action( 'group_members_loop_start', $this );
		}
	}
}
