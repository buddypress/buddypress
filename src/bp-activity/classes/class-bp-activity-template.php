<?php
/**
 * BuddyPress Activity Template.
 *
 * @package BuddyPress
 * @subpackage ActivityTemplate
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The main activity template loop class.
 *
 * This is responsible for loading a group of activity items and displaying them.
 *
 * @since 1.0.0
 */
class BP_Activity_Template {

	/**
	 * The loop iterator.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $current_activity = -1;

	/**
	 * The activity count.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $activity_count;

	/**
	 * The total activity count.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $total_activity_count;

	/**
	 * Array of activities located by the query.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	public $activities;

	/**
	 * The activity object currently being iterated on.
	 *
	 * @since 1.0.0
	 * @var object
	 */
	public $activity;

	/**
	 * A flag for whether the loop is currently being iterated.
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	public $in_the_loop;

	/**
	 * URL parameter key for activity pagination. Default: 'acpage'.
	 *
	 * @since 2.1.0
	 * @var string
	 */
	public $pag_arg;

	/**
	 * The page number being requested.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $pag_page;

	/**
	 * The number of items being requested per page.
	 *
	 * @since 1.0.0
	 * @var int
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
	 * The displayed user's full name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $full_name;

	/**
	 * Constructor method.
	 *
	 * The arguments passed to this class constructor are of the same
	 * format as {@link BP_Activity_Activity::get()}.
	 *
	 * @since 1.5.0
	 *
	 * @see BP_Activity_Activity::get() for a description of the argument
	 *      structure, as well as default values.
	 *
	 * @param array $args {
	 *     Array of arguments. Supports all arguments from
	 *     BP_Activity_Activity::get(), as well as 'page_arg' and
	 *     'include'. Default values for 'per_page' and 'display_comments'
	 *     differ from the originating function, and are described below.
	 *     @type string      $page_arg         The string used as a query parameter in
	 *                                         pagination links. Default: 'acpage'.
	 *     @type array|bool  $include          Pass an array of activity IDs to
	 *                                         retrieve only those items, or false to noop the 'include'
	 *                                         parameter. 'include' differs from 'in' in that 'in' forms
	 *                                         an IN clause that works in conjunction with other filters
	 *                                         passed to the function, while 'include' is interpreted as
	 *                                         an exact list of items to retrieve, which skips all other
	 *                                         filter-related parameters. Default: false.
	 *     @type int|bool    $per_page         Default: 20.
	 *     @type string|bool $display_comments Default: 'threaded'.
	 * }
	 */
	public function __construct( $args ) {
		$bp = buddypress();

		// Backward compatibility with old method of passing arguments.
		if ( !is_array( $args ) || func_num_args() > 1 ) {
			_deprecated_argument( __METHOD__, '1.6', sprintf( __( 'Arguments passed to %1$s should be in an associative array. See the inline documentation at %2$s for more details.', 'buddypress' ), __METHOD__, __FILE__ ) );

			$old_args_keys = array(
				0 => 'page',
				1 => 'per_page',
				2 => 'max',
				3 => 'include',
				4 => 'sort',
				5 => 'filter',
				6 => 'search_terms',
				7 => 'display_comments',
				8 => 'show_hidden',
				9 => 'exclude',
				10 => 'in',
				11 => 'spam',
				12 => 'page_arg'
			);

			$func_args = func_get_args();
			$args = bp_core_parse_args_array( $old_args_keys, $func_args );
		}

		$defaults = array(
			'page'              => 1,
			'per_page'          => 20,
			'page_arg'          => 'acpage',
			'max'               => false,
			'fields'            => 'all',
			'count_total'       => false,
			'sort'              => false,
			'include'           => false,
			'exclude'           => false,
			'in'                => false,
			'filter'            => false,
			'scope'             => false,
			'search_terms'      => false,
			'meta_query'        => false,
			'date_query'        => false,
			'filter_query'      => false,
			'display_comments'  => 'threaded',
			'show_hidden'       => false,
			'spam'              => 'ham_only',
			'update_meta_cache' => true,
		);
		$r = wp_parse_args( $args, $defaults );
		extract( $r );

		$this->pag_arg  = sanitize_key( $r['page_arg'] );
		$this->pag_page = bp_sanitize_pagination_arg( $this->pag_arg, $r['page']     );
		$this->pag_num  = bp_sanitize_pagination_arg( 'num',          $r['per_page'] );

		// Check if blog/forum replies are disabled.
		$this->disable_blogforum_replies = (bool) bp_core_get_root_option( 'bp-disable-blogforum-comments' );

		// Get an array of the logged in user's favorite activities.
		$this->my_favs = maybe_unserialize( bp_get_user_meta( bp_loggedin_user_id(), 'bp_favorite_activities', true ) );

		// Fetch specific activity items based on ID's.
		if ( !empty( $include ) ) {
			$this->activities = bp_activity_get_specific( array(
				'activity_ids'      => explode( ',', $include ),
				'max'               => $max,
				'count_total'       => $count_total,
				'page'              => $this->pag_page,
				'per_page'          => $this->pag_num,
				'sort'              => $sort,
				'display_comments'  => $display_comments,
				'show_hidden'       => $show_hidden,
				'spam'              => $spam,
				'update_meta_cache' => $update_meta_cache,
			) );

		// Fetch all activity items.
		} else {
			$this->activities = bp_activity_get( array(
				'display_comments'  => $display_comments,
				'max'               => $max,
				'count_total'       => $count_total,
				'per_page'          => $this->pag_num,
				'page'              => $this->pag_page,
				'sort'              => $sort,
				'search_terms'      => $search_terms,
				'meta_query'        => $meta_query,
				'date_query'        => $date_query,
				'filter_query'      => $filter_query,
				'filter'            => $filter,
				'scope'             => $scope,
				'show_hidden'       => $show_hidden,
				'exclude'           => $exclude,
				'in'                => $in,
				'spam'              => $spam,
				'update_meta_cache' => $update_meta_cache,
			) );
		}

		// The total_activity_count property will be set only if a
		// 'count_total' query has taken place.
		if ( ! is_null( $this->activities['total'] ) ) {
			if ( ! $max || $max >= (int) $this->activities['total'] ) {
				$this->total_activity_count = (int) $this->activities['total'];
			} else {
				$this->total_activity_count = (int) $max;
			}
		}

		$this->has_more_items = $this->activities['has_more_items'];

		$this->activities = $this->activities['activities'];

		if ( $max ) {
			if ( $max >= count($this->activities) ) {
				$this->activity_count = count( $this->activities );
			} else {
				$this->activity_count = (int) $max;
			}
		} else {
			$this->activity_count = count( $this->activities );
		}

		$this->full_name = bp_get_displayed_user_fullname();

		// Fetch parent content for activity comments so we do not have to query in the loop.
		foreach ( (array) $this->activities as $activity ) {
			if ( 'activity_comment' != $activity->type ) {
				continue;
			}

			$parent_ids[] = $activity->item_id;
		}

		if ( !empty( $parent_ids ) ) {
			$activity_parents = bp_activity_get_specific( array( 'activity_ids' => $parent_ids ) );
		}

		if ( !empty( $activity_parents['activities'] ) ) {
			foreach( $activity_parents['activities'] as $parent ) {
				$this->activity_parents[$parent->id] = $parent;
			}

			unset( $activity_parents );
		}

		if ( (int) $this->total_activity_count && (int) $this->pag_num ) {
			$this->pag_links = paginate_links( array(
				'base'      => add_query_arg( $this->pag_arg, '%#%' ),
				'format'    => '',
				'total'     => ceil( (int) $this->total_activity_count / (int) $this->pag_num ),
				'current'   => (int) $this->pag_page,
				'prev_text' => _x( '&larr;', 'Activity pagination previous text', 'buddypress' ),
				'next_text' => _x( '&rarr;', 'Activity pagination next text', 'buddypress' ),
				'mid_size'  => 1,
				'add_args'  => array(),
			) );
		}
	}

	/**
	 * Whether there are activity items available in the loop.
	 *
	 * @since 1.0.0
	 *
	 * @see bp_has_activities()
	 *
	 * @return bool True if there are items in the loop, otherwise false.
	 */
	function has_activities() {
		if ( $this->activity_count ) {
			return true;
		}

		return false;
	}

	/**
	 * Set up the next activity item and iterate index.
	 *
	 * @since 1.0.0
	 *
	 * @return object The next activity item to iterate over.
	 */
	public function next_activity() {
		$this->current_activity++;
		$this->activity = $this->activities[ $this->current_activity ];

		return $this->activity;
	}

	/**
	 * Rewind the posts and reset post index.
	 *
	 * @since 1.0.0
	 */
	public function rewind_activities() {
		$this->current_activity = -1;
		if ( $this->activity_count > 0 ) {
			$this->activity = $this->activities[0];
		}
	}

	/**
	 * Whether there are activity items left in the loop to iterate over.
	 *
	 * This method is used by {@link bp_activities()} as part of the while loop
	 * that controls iteration inside the activities loop, eg:
	 *     while ( bp_activities() ) { ...
	 *
	 * @since 1.0.0
	 *
	 * @see bp_activities()
	 *
	 * @return bool True if there are more activity items to show,
	 *              otherwise false.
	 */
	public function user_activities() {
		if ( ( $this->current_activity + 1 ) < $this->activity_count ) {
			return true;
		} elseif ( ( $this->current_activity + 1 ) == $this->activity_count ) {

			/**
			 * Fires right before the rewinding of activity posts.
			 *
			 * @since 1.1.0
			 */
			do_action( 'activity_loop_end' );

			// Do some cleaning up after the loop.
			$this->rewind_activities();
		}

		$this->in_the_loop = false;

		return false;
	}

	/**
	 * Set up the current activity item inside the loop.
	 *
	 * Used by {@link bp_the_activity()} to set up the current activity item
	 * data while looping, so that template tags used during that iteration
	 * make reference to the current activity item.
	 *
	 * @since 1.0.0
	 *
	 * @see bp_the_activity()
	 */
	public function the_activity() {

		$this->in_the_loop = true;
		$this->activity    = $this->next_activity();

		if ( is_array( $this->activity ) ) {
			$this->activity = (object) $this->activity;
		}

		// Loop has just started.
		if ( $this->current_activity == 0 ) {

			/**
			 * Fires if the current activity item is the first in the activity loop.
			 *
			 * @since 1.1.0
			 */
			do_action('activity_loop_start');
		}
	}
}
