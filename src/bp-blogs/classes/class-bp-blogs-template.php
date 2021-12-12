<?php
/**
 * BuddyPress Blogs Template Class.
 *
 * @package BuddyPress
 * @subpackage BlogsTemplate
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The main blog template loop class.
 *
 * Responsible for loading a group of blogs into a loop for display.
 */
class BP_Blogs_Template {

	/**
	 * The loop iterator.
	 *
	 * @var int
	 */
	public $current_blog = -1;

	/**
	 * The number of blogs returned by the paged query.
	 *
	 * @var int
	 */
	public $blog_count = 0;

	/**
	 * Array of blogs located by the query..
	 *
	 * @var array
	 */
	public $blogs = array();

	/**
	 * The blog object currently being iterated on.
	 *
	 * @var object
	 */
	public $blog;

	/**
	 * A flag for whether the loop is currently being iterated.
	 *
	 * @var bool
	 */
	public $in_the_loop = false;

	/**
	 * The page number being requested.
	 *
	 * @var int
	 */
	public $pag_page = 1;

	/**
	 * The number of items being requested per page.
	 *
	 * @var int
	 */
	public $pag_num = 20;

	/**
	 * An HTML string containing pagination links.
	 *
	 * @var string
	 */
	public $pag_links = '';

	/**
	 * The total number of blogs matching the query parameters.
	 *
	 * @var int
	 */
	public $total_blog_count = 0;

	/**
	 * Constructor method.
	 *
	 * @since 1.2.0
	 * @since 10.0.0 Converted to array as main function argument. Added $date_query parameter.
	 *
	 * @see BP_Blogs_Blog::get() for a description of parameters.
	 *
	 * @param array $args {
	 *     Array of arguments. See {@link BP_Blogs_Blog::get()}.
	 * }
	 */
	public function __construct( ...$args ) {
		// Backward compatibility with old method of passing arguments.
		if ( ! is_array( $args[0] ) || count( $args ) > 1 ) {
			_deprecated_argument( __METHOD__, '10.0.0', sprintf( __( 'Arguments passed to %1$s should be in an associative array. See the inline documentation at %2$s for more details.', 'buddypress' ), __METHOD__, __FILE__ ) );

			$old_args_keys = [
				0  => 'type',
				1  => 'page',
				2  => 'per_page',
				3  => 'max',
				4  => 'user_id',
				5  => 'search_terms',
				6  => 'page_arg',
				7  => 'update_meta_cache',
				8  => 'include_blog_ids',
			];

			$args = bp_core_parse_args_array( $old_args_keys, $args );
		} else {
			$args = reset( $args );
		}

		$r = bp_parse_args(
			$args,
			array(
				'type'              => '',
				'page'              => false,
				'per_page'          => false,
				'max'               => false,
				'user_id'           => false,
				'search_terms'      => false,
				'page_arg'          => 'bpage',
				'update_meta_cache' => true,
				'include_blog_ids'  => false,
				'date_query'        => false,
			),
			'blogs_template'
		);

		$this->pag_arg  = sanitize_key( $r['page_arg'] );
		$this->pag_page = bp_sanitize_pagination_arg( $this->pag_arg, $r['page'] );
		$this->pag_num  = bp_sanitize_pagination_arg( 'num', $r['per_page'] );

		// Backwards compatibility support for blogs by first letter.
		if ( ! empty( $_REQUEST['letter'] ) ) {
			$this->blogs = BP_Blogs_Blog::get_by_letter( $_REQUEST['letter'], $this->pag_num, $this->pag_page );

		// Typical blogs query.
		} else {
			$this->blogs = bp_blogs_get_blogs( array(
				'type'              => $r['type'],
				'per_page'          => $this->pag_num,
				'page'              => $this->pag_page,
				'user_id'           => $r['user_id'],
				'search_terms'      => $r['search_terms'],
				'update_meta_cache' => $r['update_meta_cache'],
				'include_blog_ids'  => $r['include_blog_ids'],
				'date_query'        => $r['date_query']
			) );
		}

		// Set the total blog count.
		if ( empty( $r['max'] ) || ( $r['max'] >= (int) $this->blogs['total'] ) ) {
			$this->total_blog_count = (int) $this->blogs['total'];
		} else {
			$this->total_blog_count = (int) $max;
		}

		// Set the blogs array (to loop through later.
		$this->blogs = $this->blogs['blogs'];

		// Get the current blog count to compare maximum against.
		$blog_count = count( $this->blogs );

		// Set the current blog count.
		if ( empty( $r['max'] ) || ( $r['max'] >= (int) $blog_count ) ) {
			$this->blog_count = (int) $blog_count;
		} else {
			$this->blog_count = (int) $max;
		}

		// Build pagination links based on total blogs and current page number.
		if ( ! empty( $this->total_blog_count ) && ! empty( $this->pag_num ) ) {
			$this->pag_links = paginate_links( array(
				'base'      => add_query_arg( $this->pag_arg, '%#%' ),
				'format'    => '',
				'total'     => ceil( (int) $this->total_blog_count / (int) $this->pag_num ),
				'current'   => (int) $this->pag_page,
				'prev_text' => _x( '&larr;', 'Blog pagination previous text', 'buddypress' ),
				'next_text' => _x( '&rarr;', 'Blog pagination next text',     'buddypress' ),
				'mid_size'  => 1,
				'add_args'  => array(),
			) );
		}
	}

	/**
	 * Whether there are blogs available in the loop.
	 *
	 * @see bp_has_blogs()
	 *
	 * @return bool True if there are items in the loop, otherwise false.
	 */
	public function has_blogs() {
		return (bool) ! empty( $this->blog_count );
	}

	/**
	 * Set up the next blog and iterate index.
	 *
	 * @return object The next blog to iterate over.
	 */
	public function next_blog() {
		$this->current_blog++;
		$this->blog = $this->blogs[ $this->current_blog ];

		return $this->blog;
	}

	/**
	 * Rewind the blogs and reset blog index.
	 */
	public function rewind_blogs() {
		$this->current_blog = -1;
		if ( $this->blog_count > 0 ) {
			$this->blog = $this->blogs[0];
		}
	}

	/**
	 * Whether there are blogs left in the loop to iterate over.
	 *
	 * This method is used by {@link bp_blogs()} as part of the while loop
	 * that controls iteration inside the blogs loop, eg:
	 *     while ( bp_blogs() ) { ...
	 *
	 * @see bp_blogs()
	 *
	 * @return bool True if there are more blogs to show, otherwise false.
	 */
	public function blogs() {
		if ( ( $this->current_blog + 1 ) < $this->blog_count ) {
			return true;
		} elseif ( ( $this->current_blog + 1 ) === $this->blog_count ) {

			/**
			 * Fires right before the rewinding of blogs listing after all are shown.
			 *
			 * @since 1.5.0
			 */
			do_action( 'blog_loop_end' );
			// Do some cleaning up after the loop.
			$this->rewind_blogs();
		}

		$this->in_the_loop = false;
		return false;
	}

	/**
	 * Set up the current blog inside the loop.
	 *
	 * Used by {@link bp_the_blog()} to set up the current blog data while
	 * looping, so that template tags used during that iteration make
	 * reference to the current blog.
	 *
	 * @see bp_the_blog()
	 */
	public function the_blog() {

		$this->in_the_loop = true;
		$this->blog        = $this->next_blog();

		// Loop has just started.
		if ( 0 === $this->current_blog ) {

			/**
			 * Fires if on the first blog in the loop.
			 *
			 * @since 1.5.0
			 */
			do_action( 'blog_loop_start' );
		}
	}
}
