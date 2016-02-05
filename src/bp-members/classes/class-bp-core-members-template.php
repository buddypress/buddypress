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
 */
class BP_Core_Members_Template {

	/**
	 * The loop iterator.
	 *
	 * @var int
	 */
	public $current_member = -1;

	/**
	 * The number of members returned by the paged query.
	 *
	 * @var int
	 */
	public $member_count;

	/**
	 * Array of members located by the query.
	 *
	 * @var array
	 */
	public $members;

	/**
	 * The member object currently being iterated on.
	 *
	 * @var object
	 */
	public $member;

	/**
	 * A flag for whether the loop is currently being iterated.
	 *
	 * @var bool
	 */
	public $in_the_loop;

	/**
	 * The type of member being requested. Used for ordering results.
	 *
	 * @var string
	 */
	public $type;

	/**
	 * The unique string used for pagination queries.
	 *
	 * @var string
	 */
	public $pag_arg;

	/**
	 * The page number being requested.
	 *
	 * @var string
	 */
	public $pag_page;

	/**
	 * The number of items being requested per page.
	 *
	 * @var string
	 */
	public $pag_num;

	/**
	 * An HTML string containing pagination links.
	 *
	 * @var string
	 */
	public $pag_links;

	/**
	 * The total number of members matching the query parameters.
	 *
	 * @var int
	 */
	public $total_member_count;

	/**
	 * Constructor method.
	 *
	 * @see BP_User_Query for an in-depth description of parameters.
	 *
	 * @param string       $type                Sort order.
	 * @param int          $page_number         Page of results.
	 * @param int          $per_page            Number of results per page.
	 * @param int          $max                 Max number of results to return.
	 * @param int          $user_id             Limit to friends of a user.
	 * @param string       $search_terms        Limit to users matching search terms.
	 * @param array        $include             Limit results by these user IDs.
	 * @param bool         $populate_extras     Fetch optional extras.
	 * @param array        $exclude             Exclude these IDs from results.
	 * @param array        $meta_key            Limit to users with a meta_key.
	 * @param array        $meta_value          Limit to users with a meta_value (with meta_key).
	 * @param string       $page_arg            Optional. The string used as a query parameter in pagination links.
	 *                                          Default: 'upage'.
	 * @param array|string $member_type         Array or comma-separated string of member types to limit results to.
	 * @param array|string $member_type__in     Array or comma-separated string of member types to limit results to.
	 * @param array|string $member_type__not_in Array or comma-separated string of member types to exclude
	 *                                          from results.
	 */
	function __construct( $type, $page_number, $per_page, $max, $user_id, $search_terms, $include, $populate_extras, $exclude, $meta_key, $meta_value, $page_arg = 'upage', $member_type = '', $member_type__in = '', $member_type__not_in = '' ) {

		$this->pag_arg  = sanitize_key( $page_arg );
		$this->pag_page = bp_sanitize_pagination_arg( $this->pag_arg, $page_number );
		$this->pag_num  = bp_sanitize_pagination_arg( 'num',          $per_page    );
		$this->type     = $type;

		if ( !empty( $_REQUEST['letter'] ) )
			$this->members = BP_Core_User::get_users_by_letter( $_REQUEST['letter'], $this->pag_num, $this->pag_page, $populate_extras, $exclude );
		else
			$this->members = bp_core_get_users( array( 'type' => $this->type, 'per_page' => $this->pag_num, 'page' => $this->pag_page, 'user_id' => $user_id, 'include' => $include, 'search_terms' => $search_terms, 'populate_extras' => $populate_extras, 'exclude' => $exclude, 'meta_key' => $meta_key, 'meta_value' => $meta_value, 'member_type' => $member_type, 'member_type__in' => $member_type__in, 'member_type__not_in' => $member_type__not_in ) );

		if ( !$max || $max >= (int) $this->members['total'] )
			$this->total_member_count = (int) $this->members['total'];
		else
			$this->total_member_count = (int) $max;

		$this->members = $this->members['users'];

		if ( $max ) {
			if ( $max >= count( $this->members ) ) {
				$this->member_count = count( $this->members );
			} else {
				$this->member_count = (int) $max;
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

			if ( ! empty( $search_terms ) ) {
				$query_arg = bp_core_get_component_search_query_arg( 'members' );
				$add_args[ $query_arg ] = urlencode( $search_terms );
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
	 * @see bp_has_members()
	 *
	 * @return bool True if there are items in the loop, otherwise false.
	 */
	function has_members() {
		if ( $this->member_count )
			return true;

		return false;
	}

	/**
	 * Set up the next member and iterate index.
	 *
	 * @return object The next member to iterate over.
	 */
	function next_member() {
		$this->current_member++;
		$this->member = $this->members[$this->current_member];

		return $this->member;
	}

	/**
	 * Rewind the members and reset member index.
	 */
	function rewind_members() {
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
	 * @see bp_members()
	 *
	 * @return bool True if there are more members to show, otherwise false.
	 */
	function members() {
		if ( $this->current_member + 1 < $this->member_count ) {
			return true;
		} elseif ( $this->current_member + 1 == $this->member_count ) {

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
	 * @see bp_the_member()
	 */
	function the_member() {

		$this->in_the_loop = true;
		$this->member      = $this->next_member();

		// Loop has just started.
		if ( 0 == $this->current_member ) {

			/**
			 * Fires if the current member is the first in the loop.
			 *
			 * @since 1.5.0
			 */
			do_action( 'member_loop_start' );
		}

	}
}
