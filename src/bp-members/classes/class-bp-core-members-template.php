<?php
/**
 * BuddyPress Member Template loop class.
 *
 * @package BuddyPress
 * @subpackage Members
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The main member template loop class.
 *
 * Responsible for loading a group of members into a loop for display.
 *
 * @since 1.0.0
 */
class BP_Core_Members_Template {

	/**
	 * The loop iterator.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $current_member = -1;

	/**
	 * The number of members returned by the paged query.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $member_count;

	/**
	 * Array of members located by the query.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	public $members;

	/**
	 * The member object currently being iterated on.
	 *
	 * @since 1.0.0
	 * @var object
	 */
	public $member;

	/**
	 * A flag for whether the loop is currently being iterated.
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	public $in_the_loop;

	/**
	 * The type of member being requested. Used for ordering results.
	 *
	 * @since 2.3.0
	 * @var string
	 */
	public $type;

	/**
	 * The unique string used for pagination queries.
	 *
	 * @since 2.2.0
	 * @var string
	 */
	public $pag_arg;

	/**
	 * The page number being requested.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $pag_page;

	/**
	 * The number of items being requested per page.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $pag_num;

	/**
	 * An HTML string containing pagination links.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $pag_links;

	/**
	 * The total number of members matching the query parameters.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $total_member_count;

	/**
	 * Constructor method.
	 *
	 * @since 1.5.0
	 * @since 7.0.0 Added `$xprofile_query` parameter. Added `$user_ids` parameter.
	 * @since 10.0.0 Added `$date_query` parameter.
	 *
	 * @see BP_User_Query for an in-depth description of parameters.
	 *
	 * @param array ...$args {
	 *     Array of arguments. Supports all arguments of BP_User_Query. Additional
	 *     arguments, or those with different defaults, are described below.
	 *
	 *     @type int    $page_number Page of results. Accepted for legacy reasons. Use 'page' instead.
	 *     @type int    $max         Max number of results to return.
	 *     @type string $page_arg    Optional. The string used as a query parameter in pagination links.
	 * }
	 */
	public function __construct( ...$args ) {
		// Backward compatibility with old method of passing arguments.
		if ( ! is_array( $args[0] ) || count( $args ) > 1 ) {
			_deprecated_argument( __METHOD__, '7.0.0', sprintf( esc_html__( 'Arguments passed to %1$s should be in an associative array. See the inline documentation at %2$s for more details.', 'buddypress' ), __METHOD__, __FILE__ ) );

			$old_args_keys = array(
				0  => 'type',
				1  => 'page_number',
				2  => 'per_page',
				3  => 'max',
				4  => 'user_id',
				5  => 'search_terms',
				6  => 'include',
				7  => 'populate_extras',
				8  => 'exclude',
				9  => 'meta_key',
				10 => 'meta_value',
				11 => 'page_arg',
				12 => 'member_type',
				13 => 'member_type__in',
				14 => 'member_type__not_in'
			);

			$args = bp_core_parse_args_array( $old_args_keys, $args );
		} else {
			$args = reset( $args );
		}

		// Support both 'page_number' and 'page' for backward compatibility.
		$args['page_number'] = isset( $args['page_number'] ) ? $args['page_number'] : $args['page'];

		$defaults = array(
			'type'                => 'active',
			'page_number'         => 1,
			'per_page'            => 20,
			'max'                 => false,
			'user_id'             => false,
			'search_terms'        => '',
			'include'             => false,
			'populate_extras'     => true,
			'exclude'             => false,
			'user_ids'            => false,
			'meta_key'            => false,
			'meta_value'          => false,
			'page_arg'            => 'upage',
			'member_type'         => '',
			'member_type__in'     => '',
			'member_type__not_in' => '',
			'xprofile_query'      => false,
			'date_query'          => false,
		);

		$r = bp_parse_args(
			$args,
			$defaults
		);

		$this->pag_arg  = sanitize_key( $r['page_arg'] );
		$this->pag_page = bp_sanitize_pagination_arg( $this->pag_arg, $r['page_number'] );
		$this->pag_num  = bp_sanitize_pagination_arg( 'num',          $r['per_page']    );
		$this->type     = $r['type'];

		if ( ! empty( $_REQUEST['letter'] ) ) {
			$this->members = BP_Core_User::get_users_by_letter( $_REQUEST['letter'], $this->pag_num, $this->pag_page, $r['populate_extras'], $r['exclude'] );
		} else {
			$this->members = bp_core_get_users(
				array(
					'type'                => $this->type,
					'per_page'            => $this->pag_num,
					'page'                => $this->pag_page,
					'user_id'             => $r['user_id'],
					'include'             => $r['include'],
					'search_terms'        => $r['search_terms'],
					'populate_extras'     => $r['populate_extras'],
					'exclude'             => $r['exclude'],
					'user_ids'            => $r['user_ids'],
					'meta_key'            => $r['meta_key'],
					'meta_value'          => $r['meta_value'],
					'member_type'         => $r['member_type'],
					'member_type__in'     => $r['member_type__in'],
					'member_type__not_in' => $r['member_type__not_in'],
					'xprofile_query'      => $r['xprofile_query'],
					'date_query'          => $r['date_query'],
				)
			);
		}

		if ( ! $r['max'] || $r['max'] >= (int) $this->members['total'] ) {
			$this->total_member_count = (int) $this->members['total'];
		} else {
			$this->total_member_count = (int) $r['max'];
		}

		$this->members = $this->members['users'];

		if ( $r['max'] ) {
			if ( $r['max'] >= count( $this->members ) ) {
				$this->member_count = count( $this->members );
			} else {
				$this->member_count = (int) $r['max'];
			}
		} else {
			$this->member_count = count( $this->members );
		}

		if ( (int) $this->total_member_count && (int) $this->pag_num ) {
			$pag_args = array(
				$this->pag_arg => '%#%',
			);

			if ( defined( 'DOING_AJAX' ) && true === (bool) DOING_AJAX ) {
				$base = remove_query_arg( 's', wp_get_referer() );
			} else {
				$base = '';
			}

			/**
			 * Defaults to an empty array to make sure paginate_links()
			 * won't add the $page_arg to the links which would break
			 * pagination in case JavaScript is disabled.
			 */
			$add_args = array();

			if ( ! empty( $r['search_terms'] ) ) {
				$query_arg = bp_core_get_component_search_query_arg( 'members' );
				$add_args[ $query_arg ] = urlencode( $r['search_terms'] );
			}

			$this->pag_links = paginate_links( array(
				'base'      => add_query_arg( $pag_args, $base ),
				'format'    => '',
				'total'     => ceil( (int) $this->total_member_count / (int) $this->pag_num ),
				'current'   => (int) $this->pag_page,
				'prev_text' => _x( '&larr;', 'Member pagination previous text', 'buddypress' ),
				'next_text' => _x( '&rarr;', 'Member pagination next text', 'buddypress' ),
				'mid_size'  => 1,
				'add_args'  => $add_args,
			) );
		}
	}

	/**
	 * Whether there are members available in the loop.
	 *
	 * @since 1.0.0
	 *
	 * @see bp_has_members()
	 *
	 * @return bool True if there are items in the loop, otherwise false.
	 */
	public function has_members() {
		return ! empty( $this->member_count );
	}

	/**
	 * Set up the next member and iterate index.
	 *
	 * @since 1.0.0
	 *
	 * @return object The next member to iterate over.
	 */
	public function next_member() {
		$this->current_member++;
		$this->member = $this->members[ $this->current_member ];

		return $this->member;
	}

	/**
	 * Rewind the members and reset member index.
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
	 * Whether there are members left in the loop to iterate over.
	 *
	 * This method is used by {@link bp_members()} as part of the while loop
	 * that controls iteration inside the members loop, eg:
	 *     while ( bp_members() ) { ...
	 *
	 * @since 1.2.0
	 *
	 * @see bp_members()
	 *
	 * @return bool True if there are more members to show, otherwise false.
	 */
	public function members() {
		if ( $this->current_member + 1 < $this->member_count ) {
			return true;
		} elseif ( $this->current_member + 1 === $this->member_count ) {

			/**
			 * Fires right before the rewinding of members listing.
			 *
			 * @since 1.5.0
			 */
			do_action('member_loop_end');
			// Do some cleaning up after the loop.
			$this->rewind_members();
		}

		$this->in_the_loop = false;
		return false;
	}

	/**
	 * Set up the current member inside the loop.
	 *
	 * Used by {@link bp_the_member()} to set up the current member data
	 * while looping, so that template tags used during that iteration make
	 * reference to the current member.
	 *
	 * @since 1.0.0
	 *
	 * @see bp_the_member()
	 */
	public function the_member() {
		$this->in_the_loop = true;
		$this->member      = $this->next_member();

		// Loop has just started.
		if ( 0 === $this->current_member ) {

			/**
			 * Fires if the current member is the first in the loop.
			 *
			 * @since 1.5.0
			 */
			do_action( 'member_loop_start' );
		}
	}
}
