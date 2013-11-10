<?php

/**
 * BuddyPress Blogs Template Tags.
 *
 * @package BuddyPress
 * @subpackage BlogsTemplate
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Output the blogs component slug.
 *
 * @since BuddyPress (1.5.0)
 *
 * @uses bp_get_blogs_slug()
 */
function bp_blogs_slug() {
	echo bp_get_blogs_slug();
}
	/**
	 * Return the blogs component slug.
	 *
	 * @since BuddyPress (1.5.0)
	 *
	 * @return string The 'blogs' slug.
	 */
	function bp_get_blogs_slug() {
		return apply_filters( 'bp_get_blogs_slug', buddypress()->blogs->slug );
	}

/**
 * Output the blogs component root slug.
 *
 * @since BuddyPress (1.5.0)
 *
 * @uses bp_get_blogs_root_slug()
 */
function bp_blogs_root_slug() {
	echo bp_get_blogs_root_slug();
}
	/**
	 * Return the blogs component root slug.
	 *
	 * @since BuddyPress (1.5.0)
	 *
	 * @return string The 'blogs' root slug.
	 */
	function bp_get_blogs_root_slug() {
		return apply_filters( 'bp_get_blogs_root_slug', buddypress()->blogs->root_slug );
	}

/**
 * Output blog directory permalink.
 *
 * @since BuddyPress (1.5.0)
 *
 * @uses bp_get_blogs_directory_permalink()
 */
function bp_blogs_directory_permalink() {
	echo bp_get_blogs_directory_permalink();
}
	/**
	 * Return blog directory permalink.
	 *
	 * @since BuddyPress (1.5.0)
	 *
	 * @uses apply_filters()
	 * @uses trailingslashit()
	 * @uses bp_get_root_domain()
	 * @uses bp_get_blogs_root_slug()
	 * @return string The URL of the Blogs directory.
	 */
	function bp_get_blogs_directory_permalink() {
		return apply_filters( 'bp_get_blogs_directory_permalink', trailingslashit( bp_get_root_domain() . '/' . bp_get_blogs_root_slug() ) );
	}

/**
 * The main blog template loop class.
 *
 * Responsible for loading a group of blogs into a loop for display.
 */
class BP_Blogs_Template {

	/**
	 * The loop iterator.
	 *
	 * @access public
	 * @var int
	 */
	var $current_blog = -1;

	/**
	 * The number of blogs returned by the paged query.
	 *
	 * @access public
	 * @var int
	 */
	var $blog_count;

	/**
	 * Array of blogs located by the query..
	 *
	 * @access public
	 * @var array
	 */
	var $blogs;

	/**
	 * The blog object currently being iterated on.
	 *
	 * @access public
	 * @var object
	 */
	var $blog;

	/**
	 * A flag for whether the loop is currently being iterated.
	 *
	 * @access public
	 * @var bool
	 */
	var $in_the_loop;

	/**
	 * The page number being requested.
	 *
	 * @access public
	 * @var public
	 */
	var $pag_page;

	/**
	 * The number of items being requested per page.
	 *
	 * @access public
	 * @var public
	 */
	var $pag_num;

	/**
	 * An HTML string containing pagination links.
	 *
	 * @access public
	 * @var string
	 */
	var $pag_links;

	/**
	 * The total number of blogs matching the query parameters.
	 *
	 * @access public
	 * @var int
	 */
	var $total_blog_count;

	/**
	 * Constructor method.
	 *
	 * @see BP_Blogs_Blog::get() for a description of parameters.
	 *
	 * @param string $type See {@link BP_Blogs_Blog::get()}.
	 * @param string $page See {@link BP_Blogs_Blog::get()}.
	 * @param string $per_page See {@link BP_Blogs_Blog::get()}.
	 * @param string $max See {@link BP_Blogs_Blog::get()}.
	 * @param string $user_id See {@link BP_Blogs_Blog::get()}.
	 * @param string $search_terms See {@link BP_Blogs_Blog::get()}.
	 * @param string $page_arg The string used as a query parameter in
	 *        pagination links. Default: 'bpage'.
	 */
	function __construct( $type, $page, $per_page, $max, $user_id, $search_terms, $page_arg = 'bpage' ) {

		$this->pag_page = isset( $_REQUEST[$page_arg] ) ? intval( $_REQUEST[$page_arg] ) : $page;
		$this->pag_num = isset( $_REQUEST['num'] ) ? intval( $_REQUEST['num'] ) : $per_page;

		if ( isset( $_REQUEST['letter'] ) && '' != $_REQUEST['letter'] )
			$this->blogs = BP_Blogs_Blog::get_by_letter( $_REQUEST['letter'], $this->pag_num, $this->pag_page );
		else
			$this->blogs = bp_blogs_get_blogs( array( 'type' => $type, 'per_page' => $this->pag_num, 'page' => $this->pag_page, 'user_id' => $user_id, 'search_terms' => $search_terms ) );

		if ( !$max || $max >= (int) $this->blogs['total'] )
			$this->total_blog_count = (int) $this->blogs['total'];
		else
			$this->total_blog_count = (int) $max;

		$this->blogs = $this->blogs['blogs'];

		if ( $max ) {
			if ( $max >= count($this->blogs) ) {
				$this->blog_count = count( $this->blogs );
			} else {
				$this->blog_count = (int) $max;
			}
		} else {
			$this->blog_count = count( $this->blogs );
		}

		if ( (int) $this->total_blog_count && (int) $this->pag_num ) {
			$this->pag_links = paginate_links( array(
				'base'      => add_query_arg( $page_arg, '%#%' ),
				'format'    => '',
				'total'     => ceil( (int) $this->total_blog_count / (int) $this->pag_num ),
				'current'   => (int) $this->pag_page,
				'prev_text' => _x( '&larr;', 'Blog pagination previous text', 'buddypress' ),
				'next_text' => _x( '&rarr;', 'Blog pagination next text', 'buddypress' ),
				'mid_size'  => 1
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
	function has_blogs() {
		if ( $this->blog_count )
			return true;

		return false;
	}

	/**
	 * Set up the next blog and iterate index.
	 *
	 * @return object The next blog to iterate over.
	 */
	function next_blog() {
		$this->current_blog++;
		$this->blog = $this->blogs[$this->current_blog];

		return $this->blog;
	}

	/**
	 * Rewind the blogs and reset blog index.
	 */
	function rewind_blogs() {
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
	function blogs() {
		if ( $this->current_blog + 1 < $this->blog_count ) {
			return true;
		} elseif ( $this->current_blog + 1 == $this->blog_count ) {
			do_action('blog_loop_end');
			// Do some cleaning up after the loop
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
	function the_blog() {

		$this->in_the_loop = true;
		$this->blog        = $this->next_blog();

		if ( 0 == $this->current_blog ) // loop has just started
			do_action('blog_loop_start');
	}
}

/**
 * Rewind the blogs and reset blog index.
 */
function bp_rewind_blogs() {
	global $blogs_template;

	$blogs_template->rewind_blogs();
}

/**
 * Initialize the blogs loop.
 *
 * Based on the $args passed, bp_has_blogs() populates the $blogs_template
 * global, enabling the use of BuddyPress templates and template functions to
 * display a list of activity items.
 *
 * @global object $blogs_template {@link BP_Blogs_Template}
 *
 * @param array $args {
 *     Arguments for limiting the contents of the blogs loop. Most arguments
 *     are in the same format as {@link BP_Blogs_Blog::get()}. However, because
 *     the format of the arguments accepted here differs in a number of ways,
 *     and because bp_has_blogs() determines some default arguments in a
 *     dynamic fashion, we list all accepted arguments here as well.
 *
 *     Arguments can be passed as an associative array, or as a URL query
 *     string (eg, 'user_id=4&per_page=3').
 *
 *     @type int $page Which page of results to fetch. Using page=1 without
 *           per_page will result in no pagination. Default: 1.
 *     @type int|bool $per_page Number of results per page. Default: 20.
 *     @type string $page_arg The string used as a query parameter in
 *           pagination links. Default: 'bpage'.
 *     @type int|bool $max Maximum number of results to return.
 *           Default: false (unlimited).
 *     @type string $type The order in which results should be fetched.
	     'active', 'alphabetical', 'newest', or 'random'.
 *     @type string $sort 'ASC' or 'DESC'. Default: 'DESC'.
 *     @type string $search_terms Limit results by a search term. Default: null.
 *     @type int $user_id The ID of the user whose blogs should be retrieved.
 *           When viewing a user profile page, 'user_id' defaults to the ID of
 *           the displayed user. Otherwise the default is false.
 * }
 * @return bool Returns true when blogs are found, otherwise false.
 */
function bp_has_blogs( $args = '' ) {
	global $blogs_template;

	/***
	 * Set the defaults based on the current page. Any of these will be overridden
	 * if arguments are directly passed into the loop. Custom plugins should always
	 * pass their parameters directly to the loop.
	 */
	$type         = 'active';
	$user_id      = 0;
	$search_terms = null;

	// User filtering
	if ( bp_displayed_user_id() )
		$user_id = bp_displayed_user_id();

	$defaults = array(
		'type'         => $type,
		'page'         => 1,
		'per_page'     => 20,
		'max'          => false,

		'page_arg'     => 'bpage',        // See https://buddypress.trac.wordpress.org/ticket/3679

		'user_id'      => $user_id,       // Pass a user_id to limit to only blogs this user has higher than subscriber access to
		'search_terms' => $search_terms   // Pass search terms to filter on the blog title or description.
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r );

	if ( is_null( $search_terms ) ) {
		if ( isset( $_REQUEST['s'] ) && !empty( $_REQUEST['s'] ) )
			$search_terms = $_REQUEST['s'];
		else
			$search_terms = false;
	}

	if ( $max ) {
		if ( $per_page > $max ) {
			$per_page = $max;
		}
	}

	$blogs_template = new BP_Blogs_Template( $type, $page, $per_page, $max, $user_id, $search_terms, $page_arg );
	return apply_filters( 'bp_has_blogs', $blogs_template->has_blogs(), $blogs_template );
}

/**
 * Determine if there are still blogs left in the loop.
 *
 * @global object $blogs_template {@link BP_Blogs_Template}
 *
 * @return bool Returns true when blogs are found.
 */
function bp_blogs() {
	global $blogs_template;

	return $blogs_template->blogs();
}

/**
 * Get the current blog object in the loop.
 *
 * @global object $blogs_template {@link BP_Blogs_Template}
 *
 * @return object The current blog within the loop.
 */
function bp_the_blog() {
	global $blogs_template;

	return $blogs_template->the_blog();
}

/**
 * Output the blogs pagination count.
 *
 * @global object $blogs_template {@link BP_Blogs_Template}
 */
function bp_blogs_pagination_count() {
	global $blogs_template;

	$start_num = intval( ( $blogs_template->pag_page - 1 ) * $blogs_template->pag_num ) + 1;
	$from_num  = bp_core_number_format( $start_num );
	$to_num    = bp_core_number_format( ( $start_num + ( $blogs_template->pag_num - 1 ) > $blogs_template->total_blog_count ) ? $blogs_template->total_blog_count : $start_num + ( $blogs_template->pag_num - 1 ) );
	$total     = bp_core_number_format( $blogs_template->total_blog_count );

	echo sprintf( _n( 'Viewing site %1$s to %2$s (of %3$s site)', 'Viewing site %1$s to %2$s (of %3$s sites)', $total, 'buddypress' ), $from_num, $to_num, $total );
}

/**
 * Output the blogs pagination links.
 */
function bp_blogs_pagination_links() {
	echo bp_get_blogs_pagination_links();
}
	/**
	 * Return the blogs pagination links.
	 *
	 * @global object $blogs_template {@link BP_Blogs_Template}
	 *
	 * @return string HTML pagination links.
	 */
	function bp_get_blogs_pagination_links() {
		global $blogs_template;

		return apply_filters( 'bp_get_blogs_pagination_links', $blogs_template->pag_links );
	}

/**
 * Output a blog's avatar.
 *
 * @see bp_get_blog_avatar() for description of arguments.
 *
 * @param array $args See {@link bp_get_blog_avatar()}.
 */
function bp_blog_avatar( $args = '' ) {
	echo bp_get_blog_avatar( $args );
}
	/**
	 * Get a blog's avatar.
	 *
	 * At the moment, blog avatars are simply the user avatars of the blog
	 * admin. Filter 'bp_get_blog_avatar_' . $blog_id to customize.
	 *
	 * @see bp_core_fetch_avatar() For a description of arguments and
	 *      return values.
	 *
	 * @param array $args  {
	 *     Arguments are listed here with an explanation of their defaults.
	 *     For more information about the arguments, see
	 *     {@link bp_core_fetch_avatar()}.
	 *     @type string $alt Default: 'Profile picture of site author
	 *           [user name]'.
	 *     @type string $class Default: 'avatar'.
	 *     @type string $type Default: 'full'.
	 *     @type int|bool $width Default: false.
	 *     @type int|bool $height Default: false.
	 *     @type bool $id Currently unused.
	 *     @type bool $no_grav Default: false.
	 * }
	 * @return string User avatar string.
	 */
	function bp_get_blog_avatar( $args = '' ) {
		global $blogs_template;

		$defaults = array(
			'type'    => 'full',
			'width'   => false,
			'height'  => false,
			'class'   => 'avatar',
			'id'      => false,
			'alt'     => sprintf( __( 'Profile picture of site author %s', 'buddypress' ), bp_core_get_user_displayname( $blogs_template->blog->admin_user_id ) ),
			'no_grav' => true
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		/***
		 * In future BuddyPress versions you will be able to set the avatar for a blog.
		 * Right now you can use a filter with the ID of the blog to change it if you wish.
		 * By default it will return the avatar for the primary blog admin.
		 *
		 * This filter is deprecated as of BuddyPress 1.5 and may be removed in a future version.
		 * Use the 'bp_get_blog_avatar' filter instead.
		 */
		$avatar = apply_filters( 'bp_get_blog_avatar_' . $blogs_template->blog->blog_id, bp_core_fetch_avatar( array( 'item_id' => $blogs_template->blog->admin_user_id, 'type' => $type, 'alt' => $alt, 'width' => $width, 'height' => $height, 'class' => $class, 'email' => $blogs_template->blog->admin_user_email ) ) );

		return apply_filters( 'bp_get_blog_avatar', $avatar, $blogs_template->blog->blog_id, array( 'item_id' => $blogs_template->blog->admin_user_id, 'type' => $type, 'alt' => $alt, 'width' => $width, 'height' => $height, 'class' => $class, 'email' => $blogs_template->blog->admin_user_email ) );
	}

function bp_blog_permalink() {
	echo bp_get_blog_permalink();
}
	function bp_get_blog_permalink() {
		global $blogs_template;

		if ( empty( $blogs_template->blog->domain ) )
			$permalink = bp_get_root_domain() . $blogs_template->blog->path;
		else {
			$protocol = 'http://';
			if ( is_ssl() )
				$protocol = 'https://';

			$permalink = $protocol . $blogs_template->blog->domain . $blogs_template->blog->path;
		}

		return apply_filters( 'bp_get_blog_permalink', $permalink );
	}

/**
 * Output the name of the current blog in the loop.
 */
function bp_blog_name() {
	echo bp_get_blog_name();
}
	/**
	 * Return the name of the current blog in the loop.
	 *
	 * @return string The name of the current blog in the loop.
	 */
	function bp_get_blog_name() {
		global $blogs_template;

		return apply_filters( 'bp_get_blog_name', $blogs_template->blog->name );
	}

/**
 * Output the ID of the current blog in the loop.
 *
 * @since BuddyPress (1.7.0)
 */
function bp_blog_id() {
	echo bp_get_blog_id();
}
	/**
	 * Return the ID of the current blog in the loop.
	 *
	 * @since BuddyPress (1.7.0)
	 *
	 * @return int ID of the current blog in the loop.
	 */
	function bp_get_blog_id() {
		global $blogs_template;

		return apply_filters( 'bp_get_blog_id', $blogs_template->blog->blog_id );
	}

/**
 * Output the description of the current blog in the loop.
 */
function bp_blog_description() {
	echo apply_filters( 'bp_blog_description', bp_get_blog_description() );
}
	/**
	 * Return the description of the current blog in the loop.
	 *
	 * @return string Description of the current blog in the loop.
	 */
	function bp_get_blog_description() {
		global $blogs_template;

		return apply_filters( 'bp_get_blog_description', $blogs_template->blog->description );
	}

/**
 * Output the row class of the current blog in the loop.
 *
 * @since BuddyPress (1.7.0)
 */
function bp_blog_class() {
	echo bp_get_blog_class();
}
	/**
	 * Return the row class of the current blog in the loop.
	 *
	 * @since BuddyPress (1.7.0)
	 *
	 * @global BP_Blogs_Template $blogs_template
	 *
	 * @return string Row class of the site.
	 */
	function bp_get_blog_class() {
		global $blogs_template;

		$classes     = array();
		$pos_in_loop = (int) $blogs_template->current_blog;

		// If we've only one site in the loop, don't bother with odd and even.
		if ( $blogs_template->blog_count > 1 )
			$classes[] = ( $pos_in_loop % 2 ) ? 'even' : 'odd';
		else
			$classes[] = 'bp-single-blog';

		$classes = apply_filters( 'bp_get_blog_class', $classes );
		$classes = array_merge( $classes, array() );

		$retval = 'class="' . join( ' ', $classes ) . '"';
		return $retval;
	}

/**
 * Output the last active date of the current blog in the loop.
 */
function bp_blog_last_active() {
	echo bp_get_blog_last_active();
}
	/**
	 * Return the last active date of the current blog in the loop.
	 *
	 * @return string Last active date.
	 */
	function bp_get_blog_last_active() {
		global $blogs_template;

		return apply_filters( 'bp_blog_last_active', bp_core_get_last_activity( $blogs_template->blog->last_activity, __( 'active %s', 'buddypress' ) ) );
	}

/**
 * Output the latest post from the current blog in the loop.
 */
function bp_blog_latest_post() {
	echo bp_get_blog_latest_post();
}
	/**
	 * Return the latest post from the current blog in the loop.
	 *
	 * @return string $retval String of the form 'Latest Post: [link to post]'.
	 */
	function bp_get_blog_latest_post() {
		global $blogs_template;

		$retval = bp_get_blog_latest_post_title();

		if ( ! empty( $retval ) )
			$retval = sprintf( __( 'Latest Post: %s', 'buddypress' ), '<a href="' . $blogs_template->blog->latest_post->guid . '">' . apply_filters( 'the_title', $retval ) . '</a>' );

		return apply_filters( 'bp_get_blog_latest_post', $retval );
	}

/**
 * Output the title of the latest post on the current blog in the loop.
 *
 * @since BuddyPress (1.7.0)
 *
 * @see bp_get_blog_latest_post_title()
 */
function bp_blog_latest_post_title() {
	echo bp_get_blog_latest_post_title();
}
	/**
	 * Return the title of the latest post on the current blog in the loop.
	 *
	 * @since BuddyPress (1.7.0)
	 *
	 * @global BP_Blogs_Template
	 *
	 * @return string Post title.
	 */
	function bp_get_blog_latest_post_title() {
		global $blogs_template;

		$retval = '';

		if ( ! empty( $blogs_template->blog->latest_post ) && ! empty( $blogs_template->blog->latest_post->post_title ) )
			$retval = $blogs_template->blog->latest_post->post_title;

		return apply_filters( 'bp_get_blog_latest_post_title', $retval );
	}

/**
 * Output the permalink of the latest post on the current blog in the loop.
 *
 * @since BuddyPress (1.7.0)
 *
 * @see bp_get_blog_latest_post_title()
 */
function bp_blog_latest_post_permalink() {
	echo bp_get_blog_latest_post_permalink();
}
	/**
	 * Return the permalink of the latest post on the current blog in the loop.
	 *
	 * @since BuddyPress (1.7.0)
	 *
	 * @global BP_Blogs_Template
	 *
	 * @return string URL of the blog's latest post.
	 */
	function bp_get_blog_latest_post_permalink() {
		global $blogs_template;

		$retval = '';

		if ( ! empty( $blogs_template->blog->latest_post ) && ! empty( $blogs_template->blog->latest_post->ID ) )
			$retval = add_query_arg( 'p', $blogs_template->blog->latest_post->ID, bp_get_blog_permalink() );

		return apply_filters( 'bp_get_blog_latest_post_permalink', $retval );
	}

/**
 * Output the content of the latest post on the current blog in the loop.
 *
 * @since BuddyPress (1.7.0)
 *
 * @uses bp_get_blog_latest_post_content()
 */
function bp_blog_latest_post_content() {
	echo bp_get_blog_latest_post_content();
}
	/**
	 * Return the content of the latest post on the current blog in the loop.
	 *
	 * @since BuddyPress (1.7.0)
	 *
	 * @global BP_Blogs_Template
	 *
	 * @return string Content of the blog's latest post.
	 */
	function bp_get_blog_latest_post_content() {
		global $blogs_template;

		$retval = '';

		if ( ! empty( $blogs_template->blog->latest_post ) && ! empty( $blogs_template->blog->latest_post->post_content ) )
			$retval = $blogs_template->blog->latest_post->post_content;

		return apply_filters( 'bp_get_blog_latest_post_content', $retval );
	}

/**
 * Output the featured image of the latest post on the current blog in the loop.
 *
 * @since BuddyPress (1.7.0)
 *
 * @see bp_get_blog_latest_post_content() For description of parameters.
 *
 * @param string $size See {@link bp_get_blog_latest_post_featured_image()}.
 */
function bp_blog_latest_post_featured_image( $size = 'thumbnail' ) {
	echo bp_get_blog_latest_post_featured_image( $size );
}
	/**
	 * Return the featured image of the latest post on the current blog in the loop.
	 *
	 * @since BuddyPress (1.7.0)
	 *
	 * @global BP_Blogs_Template
	 *
	 * @param string $size Image version to return. 'thumbnail', 'medium',
	 *        'large', or 'post-thumbnail'. Default: 'thumbnail'.
	 * @return string URL of the image.
	 */
	function bp_get_blog_latest_post_featured_image( $size = 'thumbnail' ) {
		global $blogs_template;

		$retval = '';

		if ( ! empty( $blogs_template->blog->latest_post ) && ! empty( $blogs_template->blog->latest_post->images[$size] ) )
			$retval = $blogs_template->blog->latest_post->images[$size];

		return apply_filters( 'bp_get_blog_latest_post_featured_image', $retval );
	}

/**
 * Does the latest blog post have a featured image?
 *
 * @since BuddyPress (1.7.0)
 *
 * @param string $size Image version to return. 'thumbnail', 'medium', 'large',
 *        or 'post-thumbnail'. Default: 'thumbnail'.
 * @return bool True if the latest blog post from the current blog has a
 *         featured image of the given size.
 */
function bp_blog_latest_post_has_featured_image( $thumbnail = 'thumbnail' ) {
	$image  = bp_get_blog_latest_post_featured_image( $thumbnail );

	return apply_filters( 'bp_blog_latest_post_has_featured_image', ! empty( $image ), $thumbnail, $image );
}

/**
 * Output hidden fields to help with form submissions in Sites directory.
 *
 * This function detects whether 's', 'letter', or 'blogs_search' requests are
 * currently being made (as in a URL parameter), and creates corresponding
 * hidden fields.
 */
function bp_blog_hidden_fields() {
	if ( isset( $_REQUEST['s'] ) )
		echo '<input type="hidden" id="search_terms" value="' . esc_attr( $_REQUEST['s'] ). '" name="search_terms" />';

	if ( isset( $_REQUEST['letter'] ) )
		echo '<input type="hidden" id="selected_letter" value="' . esc_attr( $_REQUEST['letter'] ) . '" name="selected_letter" />';

	if ( isset( $_REQUEST['blogs_search'] ) )
		echo '<input type="hidden" id="search_terms" value="' . esc_attr( $_REQUEST['blogs_search'] ) . '" name="search_terms" />';
}

/**
 * Output the total number of blogs on the site.
 */
function bp_total_blog_count() {
	echo bp_get_total_blog_count();
}
	/**
	 * Return the total number of blogs on the site.
	 *
	 * @return int Total number of blogs.
	 */
	function bp_get_total_blog_count() {
		return apply_filters( 'bp_get_total_blog_count', bp_blogs_total_blogs() );
	}
	add_filter( 'bp_get_total_blog_count', 'bp_core_number_format' );

/**
 * Output the total number of blogs for a given user.
 *
 * @param int $user_id ID of the user.
 */
function bp_total_blog_count_for_user( $user_id = 0 ) {
	echo bp_get_total_blog_count_for_user( $user_id );
}
	/**
	 * Return the total number of blogs for a given user.
	 *
	 * @param int $user_id ID of the user.
	 * @return int Total number of blogs for the user.
	 */
	function bp_get_total_blog_count_for_user( $user_id = 0 ) {
		return apply_filters( 'bp_get_total_blog_count_for_user', bp_blogs_total_blogs_for_user( $user_id ) );
	}
	add_filter( 'bp_get_total_blog_count_for_user', 'bp_core_number_format' );


/** Blog Registration ********************************************************/

/**
 * Checks whether blog creation is enabled.
 *
 * Returns true when blog creation is enabled for logged-in users only, or
 * when it's enabled for new registrations.
 *
 * @return bool True if blog registration is enabled.
 */
function bp_blog_signup_enabled() {
	global $bp;

	$active_signup = isset( $bp->site_options['registration'] ) ? $bp->site_options['registration'] : 'all';

	$active_signup = apply_filters( 'wpmu_active_signup', $active_signup ); // return "all", "none", "blog" or "user"

	if ( 'none' == $active_signup || 'user' == $active_signup )
		return false;

	return true;
}

/**
 * Output the wrapper markup for the blog signup form.
 *
 * @param string $blogname Optional. The default blog name (path or domain).
 * @param string $blog_title Optional. The default blog title.
 * @param string|WP_Error Optional. The WP_Error object returned by a previous
 *        submission attempt.
 */
function bp_show_blog_signup_form($blogname = '', $blog_title = '', $errors = '') {
	global $current_user;

	if ( isset($_POST['submit']) ) {
		bp_blogs_validate_blog_signup();
	} else {
		if ( ! is_wp_error($errors) ) {
			$errors = new WP_Error();
		}

		// allow definition of default variables
		$filtered_results = apply_filters('signup_another_blog_init', array('blogname' => $blogname, 'blog_title' => $blog_title, 'errors' => $errors ));
		$blogname = $filtered_results['blogname'];
		$blog_title = $filtered_results['blog_title'];
		$errors = $filtered_results['errors'];

		if ( $errors->get_error_code() ) {
			echo "<p>" . __('There was a problem, please correct the form below and try again.', 'buddypress') . "</p>";
		}
		?>
		<p><?php printf(__("By filling out the form below, you can <strong>add a site to your account</strong>. There is no limit to the number of sites that you can have, so create to your heart's content, but blog responsibly!", 'buddypress'), $current_user->display_name) ?></p>

		<p><?php _e("If you&#8217;re not going to use a great domain, leave it for a new user. Now have at it!", 'buddypress') ?></p>

		<form class="standard-form" id="setupform" method="post" action="">

			<input type="hidden" name="stage" value="gimmeanotherblog" />
			<?php do_action( 'signup_hidden_fields' ); ?>

			<?php bp_blogs_signup_blog($blogname, $blog_title, $errors); ?>
			<p>
				<input id="submit" type="submit" name="submit" class="submit" value="<?php _e('Create Site', 'buddypress') ?>" />
			</p>

			<?php wp_nonce_field( 'bp_blog_signup_form' ) ?>
		</form>
		<?php
	}
}

/**
 * Output the input fields for the blog creation form.
 *
 * @param string $blogname Optional. The default blog name (path or domain).
 * @param string $blog_title Optional. The default blog title.
 * @param string|WP_Error Optional. The WP_Error object returned by a previous
 *        submission attempt.
 */
function bp_blogs_signup_blog( $blogname = '', $blog_title = '', $errors = '' ) {
	global $current_site;

	// Blog name
	if( !is_subdomain_install() )
		echo '<label for="blogname">' . __('Site Name:', 'buddypress') . '</label>';
	else
		echo '<label for="blogname">' . __('Site Domain:', 'buddypress') . '</label>';

	if ( $errmsg = $errors->get_error_message('blogname') ) { ?>

		<p class="error"><?php echo $errmsg ?></p>

	<?php }

	if ( !is_subdomain_install() )
		echo '<span class="prefix_address">' . $current_site->domain . $current_site->path . '</span> <input name="blogname" type="text" id="blogname" value="'.$blogname.'" maxlength="63" /><br />';
	else
		echo '<input name="blogname" type="text" id="blogname" value="'.$blogname.'" maxlength="63" /> <span class="suffix_address">.' . bp_blogs_get_subdomain_base() . '</span><br />';

	if ( !is_user_logged_in() ) {
		print '(<strong>' . __( 'Your address will be ' , 'buddypress');

		if ( !is_subdomain_install() ) {
			print $current_site->domain . $current_site->path . __( 'blogname' , 'buddypress');
		} else {
			print __( 'domain.' , 'buddypress') . $current_site->domain . $current_site->path;
		}

		echo '.</strong> ' . __( 'Must be at least 4 characters, letters and numbers only. It cannot be changed so choose carefully!)' , 'buddypress') . '</p>';
	}

	// Blog Title
	?>

	<label for="blog_title"><?php _e('Site Title:', 'buddypress') ?></label>

	<?php if ( $errmsg = $errors->get_error_message('blog_title') ) { ?>

		<p class="error"><?php echo $errmsg ?></p>

	<?php }
	echo '<input name="blog_title" type="text" id="blog_title" value="'.esc_html($blog_title, 1).'" /></p>';
	?>

	<p>
		<label for="blog_public_on"><?php _e('Privacy:', 'buddypress') ?></label>
		<?php _e( 'I would like my site to appear in search engines, and in public listings around this network.', 'buddypress' ); ?>

		<label class="checkbox" for="blog_public_on">
			<input type="radio" id="blog_public_on" name="blog_public" value="1" <?php if( !isset( $_POST['blog_public'] ) || '1' == $_POST['blog_public'] ) { ?>checked="checked"<?php } ?> />
			<strong><?php _e( 'Yes' , 'buddypress'); ?></strong>
		</label>
		<label class="checkbox" for="blog_public_off">
			<input type="radio" id="blog_public_off" name="blog_public" value="0" <?php if( isset( $_POST['blog_public'] ) && '0' == $_POST['blog_public'] ) { ?>checked="checked"<?php } ?> />
			<strong><?php _e( 'No' , 'buddypress'); ?></strong>
		</label>
	</p>

	<?php
	do_action('signup_blogform', $errors);
}

/**
 * Output the base URL for subdomain installations of WordPress Multisite.
 *
 * @since BuddyPress (1.6.0)
 */
function bp_blogs_subdomain_base() {
	echo bp_blogs_get_subdomain_base();
}
	/**
	 * Return the base URL for subdomain installations of WordPress Multisite.
	 *
	 * @since BuddyPress (1.6.0)
	 *
	 * @return string The base URL - eg, 'example.com' for site_url() example.com or www.example.com.
	 */
	function bp_blogs_get_subdomain_base() {
		global $current_site;

		return apply_filters( 'bp_blogs_subdomain_base', preg_replace( '|^www\.|', '', $current_site->domain ) . $current_site->path );
	}

/**
 * Process a blog registration submission.
 *
 * Passes submitted values to {@link wpmu_create_blog()}.
 *
 * @return bool True on success, false on failure.
 */
function bp_blogs_validate_blog_signup() {
	global $wpdb, $current_user, $blogname, $blog_title, $errors, $domain, $path, $current_site;

	if ( !check_admin_referer( 'bp_blog_signup_form' ) )
		return false;

	$current_user = wp_get_current_user();

	if( !is_user_logged_in() )
		die();

	$result = bp_blogs_validate_blog_form();
	extract($result);

	if ( $errors->get_error_code() ) {
		unset($_POST['submit']);
		bp_show_blog_signup_form( $blogname, $blog_title, $errors );
		return false;
	}

	$public = (int) $_POST['blog_public'];

	$meta = apply_filters( 'signup_create_blog_meta', array( 'lang_id' => 1, 'public' => $public ) ); // depreciated
	$meta = apply_filters( 'add_signup_meta', $meta );

	// If this is a subdomain install, set up the site inside the root domain.
	if ( is_subdomain_install() )
		$domain = $blogname . '.' . preg_replace( '|^www\.|', '', $current_site->domain );

	wpmu_create_blog( $domain, $path, $blog_title, $current_user->ID, $meta, $wpdb->siteid );
	bp_blogs_confirm_blog_signup($domain, $path, $blog_title, $current_user->user_login, $current_user->user_email, $meta);
	return true;
}

/**
 * Validate a blog creation submission.
 *
 * Essentially, a wrapper for {@link wpmu_validate_blog_signup()}.
 *
 * @return array Contains the new site data and error messages.
 */
function bp_blogs_validate_blog_form() {
	$user = '';
	if ( is_user_logged_in() )
		$user = wp_get_current_user();

	return wpmu_validate_blog_signup($_POST['blogname'], $_POST['blog_title'], $user);
}

/**
 * Display a message after successful blog registration.
 *
 * @param string $domain The new blog's domain.
 * @param string $path The new blog's path.
 * @param string $blog_title The new blog's title.
 * @param string $user_name The user name of the user who created the blog. Unused.
 * @param string $user_email The email of the user who created the blog. Unused.
 * @param string|array $meta Meta values associated with the new blog. Unused.
 */
function bp_blogs_confirm_blog_signup( $domain, $path, $blog_title, $user_name, $user_email = '', $meta = '' ) {
	$protocol = is_ssl() ? 'https://' : 'http://';
	$blog_url = $protocol . $domain . $path; ?>

	<p><?php _e( 'Congratulations! You have successfully registered a new site.', 'buddypress' ) ?></p>
	<p>
		<?php printf(__( '<a href="%1$s">%2$s</a> is your new site.  <a href="%3$s">Login</a> as "%4$s" using your existing password.', 'buddypress' ), $blog_url, $blog_url, $blog_url . "wp-login.php", $user_name ); ?>
	</p>

<?php
	do_action('signup_finished');
}

/**
 * Output a "Create a Site" link for users viewing their own profiles.
 */
function bp_create_blog_link() {
	if ( bp_is_my_profile() )
		echo apply_filters( 'bp_create_blog_link', '<a href="' . bp_get_root_domain() . '/' . bp_get_blogs_root_slug() . '/create/">' . __( 'Create a Site', 'buddypress' ) . '</a>' );
}

/**
 * Output navigation tabs for a user Blogs page.
 *
 * Currently unused by BuddyPress.
 */
function bp_blogs_blog_tabs() {

	// Don't show these tabs on a user's own profile
	if ( bp_is_my_profile() )
		return false;

	?>

	<ul class="content-header-nav">
		<li<?php if ( bp_is_current_action( 'my-blogs'        ) || !bp_current_action() ) : ?> class="current"<?php endif; ?>><a href="<?php echo trailingslashit( bp_displayed_user_domain() . bp_get_blogs_slug() . '/my-blogs'        ); ?>"><?php printf( __( "%s's Sites", 'buddypress' ),           bp_get_displayed_user_fullname() ); ?></a></li>
		<li<?php if ( bp_is_current_action( 'recent-posts'    )                         ) : ?> class="current"<?php endif; ?>><a href="<?php echo trailingslashit( bp_displayed_user_domain() . bp_get_blogs_slug() . '/recent-posts'    ); ?>"><?php printf( __( "%s's Recent Posts", 'buddypress' ),    bp_get_displayed_user_fullname() ); ?></a></li>
		<li<?php if ( bp_is_current_action( 'recent-comments' )                         ) : ?> class="current"<?php endif; ?>><a href="<?php echo trailingslashit( bp_displayed_user_domain() . bp_get_blogs_slug() . '/recent-comments' ); ?>"><?php printf( __( "%s's Recent Comments", 'buddypress' ), bp_get_displayed_user_fullname() ); ?></a></li>
	</ul>

<?php
	do_action( 'bp_blogs_blog_tabs' );
}

/**
 * Output the blog directory search form.
 */
function bp_directory_blogs_search_form() {
	$default_search_value = bp_get_search_default_text();
	$search_value         = !empty( $_REQUEST['s'] ) ? stripslashes( $_REQUEST['s'] ) : $default_search_value;

	$search_form_html = '<form action="" method="get" id="search-blogs-form">
		<label><input type="text" name="s" id="blogs_search" placeholder="'. esc_attr( $search_value ) .'" /></label>
		<input type="submit" id="blogs_search_submit" name="blogs_search_submit" value="' . __( 'Search', 'buddypress' ) . '" />
	</form>';

	echo apply_filters( 'bp_directory_blogs_search_form', $search_form_html );
}

/**
 * Output button for visiting a blog in a loop.
 *
 * @see bp_get_blogs_visit_blog_button() for description of arguments.
 *
 * @param array $args See {@link bp_get_blogs_visit_blog_button()}.
 */
function bp_blogs_visit_blog_button( $args = '' ) {
	echo bp_get_blogs_visit_blog_button( $args );
}
	/**
	 * Return button for visiting a blog in a loop.
	 *
	 * @see BP_Button for a complete description of arguments and return
	 *      value.
	 *
	 * @param array $args {
	 *     Arguments are listed below, with their default values. For a
	 *     complete description of arguments, see {@link BP_Button}.
	 *     @type string $id Default: 'visit_blog'.
	 *     @type string $component Default: 'blogs'.
	 *     @type bool $must_be_logged_in Default: false.
	 *     @type bool $block_self Default: false.
	 *     @type string $wrapper_class Default: 'blog-button visit'.
	 *     @type string $link_href Permalink of the current blog in the loop.
	 *     @type string $link_class Default: 'blog-button visit'.
	 *     @type string $link_text Default: 'Visit Site'.
	 *     @type string $link_title Default: 'Visit Site'.
	 * }
	 * @return string The HTML for the Visit button.
	 */
	function bp_get_blogs_visit_blog_button( $args = '' ) {
		$defaults = array(
			'id'                => 'visit_blog',
			'component'         => 'blogs',
			'must_be_logged_in' => false,
			'block_self'        => false,
			'wrapper_class'     => 'blog-button visit',
			'link_href'         => bp_get_blog_permalink(),
			'link_class'        => 'blog-button visit',
			'link_text'         => __( 'Visit Site', 'buddypress' ),
			'link_title'        => __( 'Visit Site', 'buddypress' ),
		);

		$button = wp_parse_args( $args, $defaults );

		// Filter and return the HTML button
		return bp_get_button( apply_filters( 'bp_get_blogs_visit_blog_button', $button ) );
	}
