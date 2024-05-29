<?php
/**
 * BuddyPress Blogs Template Tags.
 *
 * @package BuddyPress
 * @subpackage BlogsTemplate
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Output the blogs component slug.
 *
 * @since 1.5.0
 *
 */
function bp_blogs_slug() {
	echo esc_attr( bp_get_blogs_slug() );
}
	/**
	 * Return the blogs component slug.
	 *
	 * @since 1.5.0
	 *
	 * @return string The 'blogs' slug.
	 */
	function bp_get_blogs_slug() {

		/**
		 * Filters the blogs component slug.
		 *
		 * @since 1.5.0
		 *
		 * @param string $slug Slug for the blogs component.
		 */
		return apply_filters( 'bp_get_blogs_slug', buddypress()->blogs->slug );
	}

/**
 * Output the blogs component root slug.
 *
 * @since 1.5.0
 *
 */
function bp_blogs_root_slug() {
	echo esc_attr( bp_get_blogs_root_slug() );
}
	/**
	 * Return the blogs component root slug.
	 *
	 * @since 1.5.0
	 *
	 * @return string The 'blogs' root slug.
	 */
	function bp_get_blogs_root_slug() {

		/**
		 * Filters the blogs component root slug.
		 *
		 * @since 1.5.0
		 *
		 * @param string $root_slug Root slug for the blogs component.
		 */
		return apply_filters( 'bp_get_blogs_root_slug', buddypress()->blogs->root_slug );
	}

/**
 * Output Blogs directory's URL.
 *
 * @since 12.0.0
 */
function bp_blogs_directory_url() {
	echo esc_url( bp_get_blogs_directory_url() );
}

/**
 * Returns the Blogs directory's URL.
 *
 * @since 12.0.0
 *
 * @param array $path_chunks {
 *     An array of arguments. Optional.
 *
 *     @type int $create_single_item `1` to get the Blogs create link.
 * }
 * @return string The URL built for the BP Rewrites URL parser.
 */
function bp_get_blogs_directory_url( $path_chunks = array() ) {
	$supported_chunks = array_fill_keys( array( 'create_single_item' ), true );

	$path_chunks = bp_parse_args(
		array_intersect_key( $path_chunks, $supported_chunks ),
		array(
			'component_id' => 'blogs'
		)
	);

	$url = bp_rewrites_get_url( $path_chunks );

	/**
	 * Filters the Blogs directory's URL.
	 *
	 * @since 12.0.0
	 *
	 * @param string  $url      The Blogs directory's URL.
	 * @param array   $path_chunks {
	 *     An array of arguments. Optional.
	 *
	 *     @type int $create_single_item `1` to get the Blogs create link.
	 * }
	 */
	return apply_filters( 'bp_get_blogs_directory_url', $url, $path_chunks );
}

/**
 * Rewind the blogs and reset blog index.
 *
 * @global BP_Blogs_Template $blogs_template The main blog template loop class.
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
 * display a list of blogs.
 *
 * @since 1.0.0
 * @since 1.2.0 Added $type, $page, $search_terms parameters
 * @since 1.6.0 Added $page_arg parameter
 * @since 2.0.0 Added $include_blog_ids, $update_meta_cache parameters
 * @since 10.0.0 Added $date_query parameter
 *
 * @global BP_Blogs_Template $blogs_template The main blog template loop class.
 *
 * @param array|string $args {
 *     Arguments for limiting the contents of the blogs loop. Most arguments
 *     are in the same format as {@link BP_Blogs_Blog::get()}. However, because
 *     the format of the arguments accepted here differs in a number of ways,
 *     and because bp_has_blogs() determines some default arguments in a
 *     dynamic fashion, we list all accepted arguments here as well.
 *
 *     @type int      $page             Which page of results to fetch. Using page=1 without
 *                                      per_page will result in no pagination. Default: 1.
 *     @type int|bool $per_page         Number of results per page. Default: 20.
 *     @type string   $page_arg         The string used as a query parameter in
 *                                      pagination links. Default: 'bpage'.
 *     @type int|bool $max              Maximum number of results to return.
 *                                      Default: false (unlimited).
 *     @type string   $type             The order in which results should be fetched.
 *                                      'active', 'alphabetical', 'newest', or 'random'.
 *     @type array    $include_blog_ids Array of blog IDs to limit results to.
 *     @type string   $search_terms     Limit results by a search term. Default: the value of `$_REQUEST['s']` or
 *                                      `$_REQUEST['sites_search']`, if present.
 *     @type int      $user_id          The ID of the user whose blogs should be retrieved.
 *                                      When viewing a user profile page, 'user_id' defaults to the
 *                                      ID of the displayed user. Otherwise the default is false.
 *     @type array    $date_query       Filter results by site last activity date. See first parameter of
 *                                      {@link WP_Date_Query::__construct()} for syntax. Only applicable if
 *                                      $type is either 'newest' or 'active'.
 * }
 * @return bool Returns true when blogs are found, otherwise false.
 */
function bp_has_blogs( $args = '' ) {
	global $blogs_template;

	// Check for and use search terms.
	$search_terms_default = false;
	$search_query_arg = bp_core_get_component_search_query_arg( 'blogs' );
	if ( ! empty( $_REQUEST[ $search_query_arg ] ) ) {
		$search_terms_default = stripslashes( $_REQUEST[ $search_query_arg ] );
	} elseif ( ! empty( $_REQUEST['s'] ) ) {
		$search_terms_default = stripslashes( $_REQUEST['s'] );
	}

	// Parse arguments.
	$r = bp_parse_args(
		$args,
		array(
			'type'              => 'active',
			'page_arg'          => 'bpage', // See https://buddypress.trac.wordpress.org/ticket/3679.
			'page'              => 1,
			'per_page'          => 20,
			'max'               => false,
			'user_id'           => bp_displayed_user_id(), // Pass a user_id to limit to only blogs this user is a member of.
			'include_blog_ids'  => false,
			'search_terms'      => $search_terms_default,
			'date_query'        => false,
			'update_meta_cache' => true,
		),
		'has_blogs'
	);

	// Set per_page to maximum if max is enforced.
	if ( ! empty( $r['max'] ) && ( (int) $r['per_page'] > (int) $r['max'] ) ) {
		$r['per_page'] = (int) $r['max'];
	}

	// Get the blogs.
	$blogs_template = new BP_Blogs_Template( $r );

	/**
	 * Filters whether or not there are blogs to list.
	 *
	 * @since 1.1.0
	 *
	 * @param bool              $value          Whether or not there are blogs to list.
	 * @param BP_Blogs_Template $blogs_template Current blogs template object.
	 * @param array             $r              Parsed arguments used in blogs template query.
	 */
	return apply_filters( 'bp_has_blogs', $blogs_template->has_blogs(), $blogs_template, $r );
}

/**
 * Determine if there are still blogs left in the loop.
 *
 * @global BP_Blogs_Template $blogs_template The main blog template loop class.
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
 * @global BP_Blogs_Template $blogs_template The main blog template loop class.
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
 * @since 1.0.0
 */
function bp_blogs_pagination_count() {
	echo esc_html( bp_get_blogs_pagination_count() );
}

/**
 * Get the blogs pagination count.
 *
 * @since 2.7.0
 *
 * @global BP_Blogs_Template $blogs_template The main blog template loop class.
 *
 * @return string
 */
function bp_get_blogs_pagination_count() {
	global $blogs_template;

	$start_num = intval( ( $blogs_template->pag_page - 1 ) * $blogs_template->pag_num ) + 1;
	$from_num  = bp_core_number_format( $start_num );
	$to_num    = bp_core_number_format( ( $start_num + ( $blogs_template->pag_num - 1 ) > $blogs_template->total_blog_count ) ? $blogs_template->total_blog_count : $start_num + ( $blogs_template->pag_num - 1 ) );
	$total     = bp_core_number_format( $blogs_template->total_blog_count );

	if ( 1 == $blogs_template->total_blog_count ) {
		$message = __( 'Viewing 1 site', 'buddypress' );
	} else {
		/* translators: 1: the site from number. 2: the site to number. 3: the total number of sites. */
		$message = sprintf( _n( 'Viewing %1$s - %2$s of %3$s site', 'Viewing %1$s - %2$s of %3$s sites', $blogs_template->total_blog_count, 'buddypress' ), $from_num, $to_num, $total );
	}

	/**
	 * Filters the "Viewing x-y of z blogs" pagination message.
	 *
	 * @since 2.7.0
	 *
	 * @param string $message  "Viewing x-y of z blogs" text.
	 * @param string $from_num Total amount for the low value in the range.
	 * @param string $to_num   Total amount for the high value in the range.
	 * @param string $total    Total amount of blogs found.
	 */
	return apply_filters( 'bp_get_blogs_pagination_count', $message, $from_num, $to_num, $total );
}

/**
 * Output the blogs pagination links.
 */
function bp_blogs_pagination_links() {
	// Escaping is done in WordPress's `paginate_links()` function.
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_blogs_pagination_links();
}
	/**
	 * Return the blogs pagination links.
	 *
	 * @global BP_Blogs_Template $blogs_template The main blog template loop class.
	 *
	 * @return string HTML pagination links.
	 */
	function bp_get_blogs_pagination_links() {
		global $blogs_template;

		/**
		 * Filters the blogs pagination links.
		 *
		 * @since 1.0.0
		 *
		 * @param string $pag_links HTML pagination links.
		 */
		return apply_filters( 'bp_get_blogs_pagination_links', $blogs_template->pag_links );
	}

/**
 * Output a blog's avatar.
 *
 * @see bp_get_blog_avatar() for description of arguments.
 *
 * @param array|string $args See {@link bp_get_blog_avatar()}.
 */
function bp_blog_avatar( $args = '' ) {
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_blog_avatar( $args );
}
	/**
	 * Get a blog's avatar.
	 *
	 * At the moment, unless the blog has a site icon, the blog's avatar defaults
	 * to the /bp-core/images/mystery-blog.png image or the Blog's Admin user avatar
	 * if the `admin_user_id` argument contains the Blog's Admin user ID.
	 *
	 * @since 2.4.0 Introduced `$title` argument.
	 * @since 6.0.0 Introduced the `$blog_id`, `$admin_user_id` and `html` arguments.
	 * @since 7.0.0 Introduced the Blog's default avatar {@see bp_blogs_default_avatar()}.
	 *              Removed the `'bp_get_blog_avatar_' . $blog_id` filter (it was deprecated since 1.5).
	 *
	 * @see bp_core_fetch_avatar() For a description of arguments and
	 *      return values.
	 *
	 * @param array|string $args  {
	 *     Arguments are listed here with an explanation of their defaults.
	 *     For more information about the arguments, see
	 *     {@link bp_core_fetch_avatar()}.
	 *     @type string   $alt           Default: 'Profile picture of site author [user name]'.
	 *     @type string   $class         Default: 'avatar'.
	 *     @type string   $type          Default: 'full'.
	 *     @type int|bool $width         Default: false.
	 *     @type int|bool $height        Default: false.
	 *     @type bool     $id            Currently unused.
	 *     @type bool     $no_grav       Default: false.
	 *     @type int      $blog_id       The blog ID. Default: O.
	 *     @type int      $admin_user_id The Blog Admin user ID. Default: 0.
	 *     @type bool     $html          Default: true.
	 * }
	 * @return string User avatar string.
	 */
	function bp_get_blog_avatar( $args = '' ) {
		global $blogs_template;

		// Bail if avatars are turned off
		// @todo Should we maybe still filter this?
		if ( ! buddypress()->avatar->show_avatars ) {
			return false;
		}

		// Set default value for the `alt` attribute.
		$alt_attribute = __( 'Site icon for the blog', 'buddypress' );

		if ( ! $blogs_template && isset( $args['blog_id'] ) && $args['blog_id'] ) {
			$blog_id = (int) $args['blog_id'];
		} else {
			$blog_id = bp_get_blog_id();

			/* translators: %s is the blog name */
			$alt_attribute = sprintf( __( 'Site icon for %s', 'buddypress' ), bp_get_blog_name() );
		}

		// Parse the arguments.
		$r = bp_parse_args(
			$args,
			array(
				'item_id'    => $blog_id,
				'avatar_dir' => 'blog-avatars',
				'object'     => 'blog',
				'type'       => 'full',
				'width'      => false,
				'height'     => false,
				'class'      => 'avatar',
				'id'         => false,
				'alt'        => $alt_attribute,
				'no_grav'    => false,
				'html'       => true,
			),
			'blog_avatar'
		);

		/**
		 * If the `admin_user_id` was provided, make the Blog avatar
		 * defaults to the Blog's Admin user one.
		 */
		if ( isset( $r['admin_user_id'] ) && $r['admin_user_id'] ) {
			$r['item_id']    = (int) $r['admin_user_id'];
			$r['avatar_dir'] = 'avatars';
			$r['object']     = 'user';
		} elseif ( ! $r['no_grav'] ) {
			$r['no_grav'] = true;
		}

		// Use site icon if available.
		$avatar = '';
		if ( bp_is_active( 'blogs', 'site-icon' ) ) {
			$site_icon = bp_blogs_get_blogmeta( $blog_id, "site_icon_url_{$r['type']}" );

			// Never attempted to fetch site icon before; do it now!
			if ( '' === $site_icon ) {
				// Fetch the other size first.
				if ( 'full' === $r['type'] ) {
					$size      = bp_core_avatar_thumb_width();
					$save_size = 'thumb';
				} else {
					$size      = bp_core_avatar_full_width();
					$save_size = 'full';
				}

				$site_icon = bp_blogs_get_site_icon_url( $blog_id, $size );

				// Empty site icons get saved as integer 0.
				if ( empty( $site_icon ) ) {
					$site_icon = 0;
				}

				// Sync site icon for other size to blogmeta.
				bp_blogs_update_blogmeta( $blog_id, "site_icon_url_{$save_size}", $site_icon );

				// Now, fetch the size we want.
				if ( 0 !== $site_icon ) {
					$size      = 'full' === $r['type'] ? bp_core_avatar_full_width() : bp_core_avatar_thumb_width();
					$site_icon = bp_blogs_get_site_icon_url( $blog_id, $size );
				}

				// Sync site icon to blogmeta.
				bp_blogs_update_blogmeta( $blog_id, "site_icon_url_{$r['type']}", $site_icon );
			}

			// We have a site icon.
			if ( ! is_numeric( $site_icon ) ) {
				// Just return the raw url of the Site Icon.
				if ( ! $r['html'] ) {
					return esc_url_raw( $site_icon );
				}

				if ( empty( $r['width'] ) && ! isset( $size ) ) {
					$size = 'full' === $r['type'] ? bp_core_avatar_full_width() : bp_core_avatar_thumb_width();
				} else {
					$size = (int) $r['width'];
				}

				$avatar = sprintf( '<img src="%1$s" class="%2$s" width="%3$s" height="%3$s" alt="%4$s" />',
					esc_url( $site_icon ),
					esc_attr( "{$r['class']} avatar-{$size}" ),
					esc_attr( $size ),
					esc_attr( $alt_attribute )
				);
			}
		}

		// Fallback to Default blog avatar.
		if ( '' === $avatar ) {
			$avatar = bp_core_fetch_avatar( $r );
		}

		/**
		 * Filters a blog's avatar.
		 *
		 * @since 1.5.0
		 *
		 * @param string $avatar  Formatted HTML <img> element, or raw avatar
		 *                        URL based on $html arg.
		 * @param int    $blog_id ID of the blog whose avatar is being displayed.
		 * @param array  $r       Array of arguments used when fetching avatar.
		 */
		return apply_filters( 'bp_get_blog_avatar', $avatar, $blog_id, $r );
	}

function bp_blog_permalink() {
	echo esc_url( bp_get_blog_permalink() );
}
	function bp_get_blog_permalink() {
		global $blogs_template;

		if ( ! empty( $blogs_template->blog->domain ) ) {
			$permalink = get_site_url( $blogs_template->blog->blog_id );

		} else {
			$protocol = 'http://';
			if ( is_ssl() ) {
				$protocol = 'https://';
			}

			$permalink = $protocol . $blogs_template->blog->domain . $blogs_template->blog->path;
		}

		/**
		 * Filters the blog permalink.
		 *
		 * @since 1.0.0
		 *
		 * @param string $permalink Permalink URL for the blog.
		 */
		return apply_filters( 'bp_get_blog_permalink', $permalink );
	}

/**
 * Output the name of the current blog in the loop.
 */
function bp_blog_name() {
	echo esc_html( bp_get_blog_name() );
}
	/**
	 * Return the name of the current blog in the loop.
	 *
	 * @return string The name of the current blog in the loop.
	 */
	function bp_get_blog_name() {
		global $blogs_template;

		/**
		 * Filters the name of the current blog in the loop.
		 *
		 * @since 1.2.0
		 *
		 * @param string $name Name of the current blog in the loop.
		 */
		return apply_filters( 'bp_get_blog_name', $blogs_template->blog->name );
	}

/**
 * Output the ID of the current blog in the loop.
 *
 * @since 1.7.0
 */
function bp_blog_id() {
	echo intval( bp_get_blog_id() );
}
	/**
	 * Return the ID of the current blog in the loop.
	 *
	 * @since 1.7.0
	 *
	 * @return int ID of the current blog in the loop.
	 */
	function bp_get_blog_id() {
		global $blogs_template;

		/**
		 * Filters the ID of the current blog in the loop.
		 *
		 * @since 1.7.0
		 *
		 * @param int $blog_id ID of the current blog in the loop.
		 */
		return apply_filters( 'bp_get_blog_id', $blogs_template->blog->blog_id );
	}

/**
 * Output the description of the current blog in the loop.
 */
function bp_blog_description() {

	/**
	 * Filters the description of the current blog in the loop.
	 *
	 * @since 1.2.0
	 *
	 * @param string $value Description of the current blog in the loop.
	 */
	echo esc_html( apply_filters( 'bp_blog_description', bp_get_blog_description() ) );
}
	/**
	 * Return the description of the current blog in the loop.
	 *
	 * @return string Description of the current blog in the loop.
	 */
	function bp_get_blog_description() {
		global $blogs_template;

		/**
		 * Filters the description of the current blog in the loop.
		 *
		 * @since 1.0.0
		 *
		 * @param string $value Description of the current blog in the loop.
		 */
		return apply_filters( 'bp_get_blog_description', $blogs_template->blog->description );
	}

/**
 * Output the row class of the current blog in the loop.
 *
 * @since 1.7.0
 *
 * @param array $classes Array of custom classes.
 */
function bp_blog_class( $classes = array() ) {
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_blog_class( $classes );
}
	/**
	 * Return the row class of the current blog in the loop.
	 *
	 * @since 1.7.0
	 *
	 * @global BP_Blogs_Template $blogs_template The main blog template loop class.
	 *
	 * @param array $classes Array of custom classes.
	 * @return string Row class of the site.
	 */
	function bp_get_blog_class( $classes = array() ) {
		global $blogs_template;

		// Add even/odd classes, but only if there's more than 1 group.
		if ( $blogs_template->blog_count > 1 ) {
			$pos_in_loop = (int) $blogs_template->current_blog;
			$classes[]   = ( $pos_in_loop % 2 ) ? 'even' : 'odd';

		// If we've only one site in the loop, don't bother with odd and even.
		} else {
			$classes[] = 'bp-single-blog';
		}

		/**
		 * Filters the row class of the current blog in the loop.
		 *
		 * @since 1.7.0
		 *
		 * @param array $classes Array of classes to be applied to row.
		 */
		$classes = array_map( 'sanitize_html_class', apply_filters( 'bp_get_blog_class', $classes ) );
		$classes = array_merge( $classes, array() );
		$retval  = 'class="' . join( ' ', $classes ) . '"';

		return $retval;
	}

/**
 * Output the last active date of the current blog in the loop.
 *
 * @param array $args See {@link bp_get_blog_last_active()}.
 */
function bp_blog_last_active( $args = array() ) {
	echo esc_html( bp_get_blog_last_active( $args ) );
}
	/**
	 * Return the last active date of the current blog in the loop.
	 *
	 * @param array $args {
	 *     Array of optional arguments.
	 *     @type bool $active_format If true, formatted "Active 5 minutes ago".
	 *                               If false, formatted "5 minutes ago".
	 *                               Default: true.
	 * }
	 * @return string Last active date.
	 */
	function bp_get_blog_last_active( $args = array() ) {
		global $blogs_template;

		// Parse the activity format.
		$r = bp_parse_args(
			$args,
			array(
				'active_format' => true,
			)
		);

		// Backwards compatibility for anyone forcing a 'true' active_format.
		if ( true === $r['active_format'] ) {
			/* translators: %s: last activity timestamp (e.g. "Active 1 hour ago") */
			$r['active_format'] = _x( 'Active %s', 'last time the site was active', 'buddypress' );
		}

		// Blog has been posted to at least once.
		if ( isset( $blogs_template->blog->last_activity ) ) {

			// Backwards compatibility for pre 1.5 'ago' strings.
			$last_activity = ! empty( $r['active_format'] )
				? bp_core_get_last_activity( $blogs_template->blog->last_activity, $r['active_format'] )
				: bp_core_time_since( $blogs_template->blog->last_activity );

		// Blog has never been posted to.
		} else {
			$last_activity = __( 'Never active', 'buddypress' );
		}

		/**
		 * Filters the last active date of the current blog in the loop.
		 *
		 * @since 1.2.0
		 *
		 * @param string $last_activity Last active date.
		 * @param array  $r             Array of parsed args used to determine formatting.
		 */
		return apply_filters( 'bp_blog_last_active', $last_activity, $r );
	}

/**
 * Output the latest post from the current blog in the loop.
 *
 * @param array $args See {@link bp_get_blog_latest_post()}.
 */
function bp_blog_latest_post( $args = array() ) {
	echo wp_kses(
		bp_get_blog_latest_post( $args ),
		array(
			'a' => array(
				'href' => true,
			),
		)
	);
}
	/**
	 * Return the latest post from the current blog in the loop.
	 *
	 * @param array $args {
	 *     Array of optional arguments.
	 *     @type bool $latest_format If true, formatted "Latest post: [link to post]".
	 *                               If false, formatted "[link to post]".
	 *                               Default: true.
	 * }
	 * @return string $retval String of the form 'Latest Post: [link to post]'.
	 */
	function bp_get_blog_latest_post( $args = array() ) {
		global $blogs_template;

		$r = bp_parse_args(
			$args,
			array(
				'latest_format' => true,
			)
		);

		$retval = bp_get_blog_latest_post_title();

		if ( ! empty( $retval ) ) {
			if ( ! empty( $r['latest_format'] ) ) {

				/**
				 * Filters the title text of the latest post for the current blog in loop.
				 *
				 * @since 1.0.0
				 *
				 * @param string $retval Title of the latest post.
				 */
				$retval = sprintf(
					/* translators: %s: the title of the latest post */
					__( 'Latest Post: %s', 'buddypress' ),
					'<a href="' . $blogs_template->blog->latest_post->guid . '">' . apply_filters( 'the_title', $retval ) . '</a>'
				);
			} else {

				/** This filter is documented in bp-blogs/bp-blogs-template.php */
				$retval = '<a href="' . $blogs_template->blog->latest_post->guid . '">' . apply_filters( 'the_title', $retval ) . '</a>';
			}
		}

		/**
		 * Filters the HTML markup result for the latest blog post in loop.
		 *
		 * @since 1.2.0
		 * @since 2.6.0 Added the `$r` parameter.
		 *
		 * @param string $retval HTML markup for the latest post.
		 * @param array  $r      Array of parsed arguments.
		 */
		return apply_filters( 'bp_get_blog_latest_post', $retval, $r );
	}

/**
 * Output the title of the latest post on the current blog in the loop.
 *
 * @since 1.7.0
 *
 * @see bp_get_blog_latest_post_title()
 */
function bp_blog_latest_post_title() {
	echo esc_html( bp_get_blog_latest_post_title() );
}
	/**
	 * Return the title of the latest post on the current blog in the loop.
	 *
	 * @since 1.7.0
	 *
	 * @global BP_Blogs_Template $blogs_template The main blog template loop class.
	 *
	 * @return string Post title.
	 */
	function bp_get_blog_latest_post_title() {
		global $blogs_template;

		$retval = '';

		if ( ! empty( $blogs_template->blog->latest_post ) && ! empty( $blogs_template->blog->latest_post->post_title ) )
			$retval = $blogs_template->blog->latest_post->post_title;

		/**
		 * Filters the title text of the latest post on the current blog in the loop.
		 *
		 * @since 1.7.0
		 *
		 * @param string $retval Title text for the latest post.
		 */
		return apply_filters( 'bp_get_blog_latest_post_title', $retval );
	}

/**
 * Output the permalink of the latest post on the current blog in the loop.
 *
 * @since 1.7.0
 *
 * @see bp_get_blog_latest_post_title()
 */
function bp_blog_latest_post_permalink() {
	echo esc_url( bp_get_blog_latest_post_permalink() );
}
	/**
	 * Return the permalink of the latest post on the current blog in the loop.
	 *
	 * @since 1.7.0
	 *
	 * @global BP_Blogs_Template $blogs_template The main blog template loop class.
	 *
	 * @return string URL of the blog's latest post.
	 */
	function bp_get_blog_latest_post_permalink() {
		global $blogs_template;

		$retval = '';

		if ( ! empty( $blogs_template->blog->latest_post ) && ! empty( $blogs_template->blog->latest_post->ID ) )
			$retval = add_query_arg( 'p', $blogs_template->blog->latest_post->ID, bp_get_blog_permalink() );

		/**
		 * Filters the permalink of the latest post on the current blog in the loop.
		 *
		 * @since 1.7.0
		 *
		 * @param string $retval Permalink URL of the latest post.
		 */
		return apply_filters( 'bp_get_blog_latest_post_permalink', $retval );
	}

/**
 * Output the content of the latest post on the current blog in the loop.
 *
 * @since 1.7.0
 *
 */
function bp_blog_latest_post_content() {
	echo wp_kses_post( bp_get_blog_latest_post_content() );
}
	/**
	 * Return the content of the latest post on the current blog in the loop.
	 *
	 * @since 1.7.0
	 *
	 * @global BP_Blogs_Template $blogs_template The main blog template loop class.
	 *
	 * @return string Content of the blog's latest post.
	 */
	function bp_get_blog_latest_post_content() {
		global $blogs_template;

		$retval = '';

		if ( ! empty( $blogs_template->blog->latest_post ) && ! empty( $blogs_template->blog->latest_post->post_content ) ) {
			$retval = $blogs_template->blog->latest_post->post_content;
		}

		/**
		 * Filters the content of the latest post on the current blog in the loop.
		 *
		 * @since 1.7.0
		 *
		 * @param string $retval Content of the latest post on the current blog in the loop.
		 */
		return apply_filters( 'bp_get_blog_latest_post_content', $retval );
	}

/**
 * Output the featured image of the latest post on the current blog in the loop.
 *
 * @since 1.7.0
 *
 * @see bp_get_blog_latest_post_content() For description of parameters.
 *
 * @param string $size See {@link bp_get_blog_latest_post_featured_image()}.
 */
function bp_blog_latest_post_featured_image( $size = 'thumbnail' ) {
	echo esc_url( bp_get_blog_latest_post_featured_image( $size ) );
}
	/**
	 * Return the featured image of the latest post on the current blog in the loop.
	 *
	 * @since 1.7.0
	 *
	 * @global BP_Blogs_Template $blogs_template The main blog template loop class.
	 *
	 * @param string $size Image version to return. 'thumbnail', 'medium',
	 *                     'large', or 'post-thumbnail'. Default: 'thumbnail'.
	 * @return string URL of the image.
	 */
	function bp_get_blog_latest_post_featured_image( $size = 'thumbnail' ) {
		global $blogs_template;

		$retval = '';

		if ( ! empty( $blogs_template->blog->latest_post ) && ! empty( $blogs_template->blog->latest_post->images[$size] ) ) {
			$retval = $blogs_template->blog->latest_post->images[$size];
		}

		/**
		 * Filters the featured image of the latest post on the current blog in the loop.
		 *
		 * @since 1.7.0
		 *
		 * @param string $retval The featured image of the latest post on the current blog in the loop.
		 */
		return apply_filters( 'bp_get_blog_latest_post_featured_image', $retval );
	}

/**
 * Does the latest blog post have a featured image?
 *
 * @since 1.7.0
 *
 * @param string $thumbnail Image version to return. 'thumbnail', 'medium', 'large',
 *                          or 'post-thumbnail'. Default: 'thumbnail'.
 * @return bool True if the latest blog post from the current blog has a
 *              featured image of the given size.
 */
function bp_blog_latest_post_has_featured_image( $thumbnail = 'thumbnail' ) {
	$image  = bp_get_blog_latest_post_featured_image( $thumbnail );

	/**
	 * Filters whether or not the latest blog post has a featured image.
	 *
	 * @since 1.7.0
	 *
	 * @param bool   $value     Whether or not the latest blog post has a featured image.
	 * @param string $thumbnail Image version to return.
	 * @param string $image     Returned value from bp_get_blog_latest_post_featured_image.
	 */
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
	if ( isset( $_REQUEST['s'] ) ) {
		echo '<input type="hidden" id="search_terms" value="' . esc_attr( $_REQUEST['s'] ). '" name="search_terms" />';
	}

	if ( isset( $_REQUEST['letter'] ) ) {
		echo '<input type="hidden" id="selected_letter" value="' . esc_attr( $_REQUEST['letter'] ) . '" name="selected_letter" />';
	}

	if ( isset( $_REQUEST['blogs_search'] ) ) {
		echo '<input type="hidden" id="search_terms" value="' . esc_attr( $_REQUEST['blogs_search'] ) . '" name="search_terms" />';
	}
}

/**
 * Output the total number of blogs on the site.
 */
function bp_total_blog_count() {
	echo intval( bp_get_total_blog_count() );
}
	/**
	 * Return the total number of blogs on the site.
	 *
	 * @return int Total number of blogs.
	 */
	function bp_get_total_blog_count() {

		/**
		 * Filters the total number of blogs on the site.
		 *
		 * @since 1.2.0
		 *
		 * @param int $value Total number of blogs on the site.
		 */
		return apply_filters( 'bp_get_total_blog_count', bp_blogs_total_blogs() );
	}
	add_filter( 'bp_get_total_blog_count', 'bp_core_number_format' );

/**
 * Output the total number of blogs for a given user.
 *
 * @param int $user_id ID of the user.
 */
function bp_total_blog_count_for_user( $user_id = 0 ) {
	echo intval( bp_get_total_blog_count_for_user( $user_id ) );
}
	/**
	 * Return the total number of blogs for a given user.
	 *
	 * @param int $user_id ID of the user.
	 * @return int Total number of blogs for the user.
	 */
	function bp_get_total_blog_count_for_user( $user_id = 0 ) {

		/**
		 * Filters the total number of blogs for a given user.
		 *
		 * @since 1.2.0
		 * @since 2.6.0 Added the `$user_id` parameter.
		 *
		 * @param int $value   Total number of blogs for a given user.
		 * @param int $user_id ID of the queried user.
		 */
		return apply_filters( 'bp_get_total_blog_count_for_user', bp_blogs_total_blogs_for_user( $user_id ), $user_id );
	}
	add_filter( 'bp_get_total_blog_count_for_user', 'bp_core_number_format' );


/** Blog Registration ********************************************************/

/**
 * Output the wrapper markup for the blog signup form.
 *
 * @since 1.0.0
 *
 * @param string          $blogname   Optional. The default blog name (path or domain).
 * @param string          $blog_title Optional. The default blog title.
 * @param string|WP_Error $errors     Optional. The WP_Error object returned by a previous
 *                                    submission attempt.
 */
function bp_show_blog_signup_form( $blogname = '', $blog_title = '', $errors = '' ) {
	$blog_id = bp_blogs_validate_blog_signup();

	// Display the signup form.
	if ( false === $blog_id || is_wp_error( $blog_id ) ) {
		if ( is_wp_error( $blog_id ) ) {
			$errors = $blog_id;
		} else {
			$errors = new WP_Error();
		}

		/**
		 * Filters the default values for Blog name, title, and any current errors.
		 *
		 * @since 1.0.0
		 *
		 * @param array $value {
		 *      string   $blogname   Default blog name provided.
		 *      string   $blog_title Default blog title provided.
		 *      WP_Error $errors     WP_Error object.
		 * }
		 */
		$filtered_results = apply_filters('signup_another_blog_init', array('blogname' => $blogname, 'blog_title' => $blog_title, 'errors' => $errors ));
		$blogname         = $filtered_results['blogname'];
		$blog_title       = $filtered_results['blog_title'];
		$errors           = $filtered_results['errors'];

		if ( $errors->get_error_code() ) {
			if ( in_array( $errors->get_error_code(), array( 'blogname', 'blog_title' ), true ) ) {
				printf(
					'<p class="error">%s</p>',
					esc_html__( 'There was a problem; please correct the form below and try again.', 'buddypress' )
				);
			} else {
				printf(
					'<p class="error">%s</p>',
					esc_html( $errors->get_error_message() )
				);
			}
		}

		printf(
			'<p>%1$s <strong>%2$s</strong>. %3$s</p>',
			esc_html__( 'By filling out the form below, you can', 'buddypress' ),
			esc_html__( 'add a site to your account', 'buddypress' ),
			esc_html__( 'There is no limit to the number of sites that you can have, so create to your heart’s content, but blog responsibly!', 'buddypress' )
		);
		?>

		<p>
			<?php esc_html_e( 'If you’re not going to use a great domain, leave it for a new user. Now have at it!', 'buddypress' ); ?>
		</p>

		<form class="standard-form" id="setupform" method="post" action="">

			<input type="hidden" name="stage" value="gimmeanotherblog" />
			<?php

			/**
			 * Fires after the default hidden fields in blog signup form markup.
			 *
			 * @since 1.0.0
			 */
			do_action( 'signup_hidden_fields' ); ?>

			<?php bp_blogs_signup_blog( $blogname, $blog_title, $errors ); ?>
			<p>
				<input id="submit" type="submit" name="submit" class="submit" value="<?php esc_attr_e( 'Create Site', 'buddypress' ); ?>" />
			</p>

			<?php wp_nonce_field( 'bp_blog_signup_form' ) ?>
		</form>
		<?php

		// Display the confirmation form.
	} elseif ( is_numeric( $blog_id ) ) {
		// Validate the site.
		$site = get_site( $blog_id );

		if ( isset( $site->id ) && $site->id ) {
			$current_user = wp_get_current_user();

			bp_blogs_confirm_blog_signup(
				$site->domain,
				$site->path,
				$site->blogname,
				$current_user->user_login,
				$current_user->user_email,
				'',
				$site->id
			);
		}
	}
}

/**
 * Output the input fields for the blog creation form.
 *
 * @since 1.0.0
 *
 * @param string          $blogname   Optional. The default blog name (path or domain).
 * @param string          $blog_title Optional. The default blog title.
 * @param string|WP_Error $errors     Optional. The WP_Error object returned by a previous
 *                                    submission attempt.
 */
function bp_blogs_signup_blog( $blogname = '', $blog_title = '', $errors = '' ) {
	$current_site = get_current_site();

	if ( ! $blogname && ! $blog_title ) {
		$submitted_vars = bp_blogs_get_signup_form_submitted_vars();

		if ( array_filter( $submitted_vars ) ) {
			$blogname   = $submitted_vars['blogname'];
			$blog_title = $submitted_vars['blog_title'];
		}
	}
	?>

	<p>
		<?php
		// Blog name.
		if ( ! is_subdomain_install() ) {
			printf( '<label for="blogname">%s</label>', esc_html__( 'Site Name:', 'buddypress' ) );
		} else {
			printf( '<label for="blogname">%s</label>', esc_html__( 'Site Domain:', 'buddypress' ) );
		}

		if ( ! is_subdomain_install() ) {
			printf(
				'<span class="prefix_address">%1$s</span> <input name="blogname" type="text" id="blogname" value="%2$s" maxlength="63" style="width: auto!important" /><br />',
				esc_html( $current_site->domain . $current_site->path ),
				esc_attr( $blogname )
			);
		} else {
			printf(
				'<input name="blogname" type="text" id="blogname" value="%1$s" maxlength="63" style="width: auto!important" %2$s/> <span class="suffix_address">.%3$s</span><br />',
				esc_attr( $blogname ),
				// phpcs:ignore WordPress.Security.EscapeOutput
				bp_get_form_field_attributes( 'blogname' ),
				esc_attr( bp_signup_get_subdomain_base() )
			);
		}
		if ( is_wp_error( $errors ) && $errors->get_error_message( 'blogname' ) ) {
			printf( '<div class="error">%s</div>', esc_html( $errors->get_error_message( 'blogname' ) ) );
		}
		?>
	</p>

	<?php
	if ( ! is_user_logged_in() ) {
		$url = sprintf(
			/* translators: %s is the site domain and path. */
			__( 'domain.%s' , 'buddypress' ),
			$current_site->domain . $current_site->path
		);

		if ( ! is_subdomain_install() ) {
			$url = sprintf(
				/* translators: %s is the site domain and path. */
				__( '%sblogname' , 'buddypress'),
				$current_site->domain . $current_site->path
			);
		}

		printf(
			'<p>(<strong>%1$s.</strong> %2$s)</p>',
			sprintf(
				/* translators: %s is the site url. */
				esc_html__( 'Your address will be %s' , 'buddypress' ), esc_url( $url )
			),
			esc_html__( 'Must be at least 4 characters, letters and numbers only. It cannot be changed so choose carefully!' , 'buddypress' )
		);
	}

	// Blog Title.
	?>
	<p>
		<label for="blog_title"><?php esc_html_e('Site Title:', 'buddypress') ?></label>
		<input name="blog_title" type="text" id="blog_title" value="<?php echo esc_html( $blog_title ); ?>" />

		<?php
		if ( is_wp_error( $errors ) && $errors->get_error_message( 'blog_title' ) ) {
			printf( '<div class="error">%s</div>', esc_html( $errors->get_error_message( 'blog_title' ) ) );
		}
		?>
	</p>

	<fieldset class="create-site">

		<legend class="label"><?php esc_html_e( 'Privacy: I would like my site to appear in search engines, and in public listings around this network', 'buddypress' ) ?></legend>

		<p>
			<label class="checkbox" for="blog_public_on">
				<input type="radio" id="blog_public_on" name="blog_public" value="1" <?php checked( ! isset( $_POST['blog_public'] ) || 1 === (int) $_POST['blog_public'] ); ?> />
				<strong><?php esc_html_e( 'Yes' , 'buddypress'); ?></strong>
			</label>
		</p>

		<p>
			<label class="checkbox" for="blog_public_off">
				<input type="radio" id="blog_public_off" name="blog_public" value="0" <?php checked( isset( $_POST['blog_public'] ) && 0 === (int) $_POST['blog_public'] ); ?> />
				<strong><?php esc_html_e( 'No' , 'buddypress'); ?></strong>
			</label>
		</p>

	</fieldset>

	<?php

	/**
	 * Fires at the end of all of the default input fields for blog creation form.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Error $errors WP_Error object if any present.
	 */
	do_action( 'signup_blogform', $errors );
}

/**
 * Process a blog registration submission.
 *
 * Passes submitted values to {@link wpmu_create_blog()}.
 *
 * @since 1.0.0
 *
 * @return bool|int|WP_Error False if not a form submission, the Blog ID on success, a WP_Error object on failure.
 */
function bp_blogs_validate_blog_signup() {
	if ( ! isset( $_POST['submit'] ) ) {
		return false;
	}

	$current_site = get_current_site();
	$current_user = wp_get_current_user();
	$blog_name    = '';
	$blog_title   = '';
	$public       = 1;

	if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['_wpnonce'] ), 'bp_blog_signup_form' ) || ! $current_user->ID ) {
		return new WP_Error( 'bp_blogs_doing_it_wrong', __( 'Sorry, we cannot create the site. Please try again later.', 'buddypress' ) );
	}

	$submitted_vars = bp_blogs_get_signup_form_submitted_vars();

	if ( array_filter( $submitted_vars ) ) {
		$blog_name  = $submitted_vars['blogname'];
		$blog_title = $submitted_vars['blog_title'];
		$public     = (int) $submitted_vars['blog_public'];
	}

	$blog = bp_blogs_validate_blog_form( $blog_name, $blog_title );

	if ( is_wp_error( $blog['errors'] ) && $blog['errors']->get_error_code() ) {
		return $blog['errors'];
	}

	/**
	 * Filters the default values for Blog meta.
	 *
	 * @since 1.0.0
	 *
	 * @param array $meta {
	 *      string $value  Default blog language ID.
	 *      string $public Default public status.
	 * }
	 */
	$meta = apply_filters( 'add_signup_meta', array( 'lang_id' => 1, 'public' => $public ) );

	return wpmu_create_blog(
		$blog['domain'],
		$blog['path'],
		$blog['blog_title'],
		$current_user->ID, $meta,
		$current_site->id
	);
}

/**
 * Display a message after successful blog registration.
 *
 * @since 1.0.0
 * @since 2.6.0 Introduced `$blog_id` parameter.
 *
 * @param string       $domain     The new blog's domain.
 * @param string       $path       The new blog's path.
 * @param string       $blog_title The new blog's title.
 * @param string       $user_name  The user name of the user who created the blog. Unused.
 * @param string       $user_email The email of the user who created the blog. Unused.
 * @param string|array $meta       Meta values associated with the new blog. Unused.
 * @param int|null     $blog_id    ID of the newly created blog.
 */
function bp_blogs_confirm_blog_signup( $domain, $path, $blog_title, $user_name, $user_email = '', $meta = '', $blog_id = null ) {
	switch_to_blog( $blog_id );
	$blog_url  = set_url_scheme( home_url() );
	$login_url = set_url_scheme( wp_login_url() );
	restore_current_blog();

	$args = array(
		'blog_url'  => $blog_url,
		'login_url' => $login_url,
		'user_name' => $user_name,
	);

	bp_get_template_part( 'blogs/confirm', null, $args );

	/**
	 * Fires after the default successful blog registration message markup.
	 *
	 * @since 1.0.0
	 */
	do_action( 'signup_finished' );
}

/**
 * Output a "Create a Site" link for users viewing their own profiles.
 *
 * This function is not used by BuddyPress as of 1.2, but is kept here for older
 * themes that may still be using it.
 */
function bp_create_blog_link() {

	// Don't show this link when not on your own profile.
	if ( ! bp_is_my_profile() ) {
		return;
	}

	$url = bp_get_blogs_directory_url(
		array(
			'create_single_item' => 1,
		)
	);

	// phpcs:ignore WordPress.Security.EscapeOutput
	echo apply_filters(
		/**
		 * Filters "Create a Site" links for users viewing their own profiles.
		 *
		 * @since 1.0.0
		 *
		 * @param string $url HTML link for creating a site.
		 */
		'bp_create_blog_link',
		'<a href="' . esc_url( $url ) . '">' . esc_html__( 'Create a Site', 'buddypress' ) . '</a>'
	);
}

/**
 * Output the blog directory search form.
 *
 * @since 1.9.0
 */
function bp_directory_blogs_search_form() {

	$query_arg = bp_core_get_component_search_query_arg( 'blogs' );

	if ( ! empty( $_REQUEST[ $query_arg ] ) ) {
		$search_value = stripslashes( $_REQUEST[ $query_arg ] );
	} else {
		$search_value = bp_get_search_default_text( 'blogs' );
	}

	$search_form_html = '<form action="" method="get" id="search-blogs-form">
		<label for="blogs_search"><input type="text" name="' . esc_attr( $query_arg ) . '" id="blogs_search" placeholder="'. esc_attr( $search_value ) .'" /></label>
		<input type="submit" id="blogs_search_submit" name="blogs_search_submit" value="' . esc_attr__( 'Search', 'buddypress' ) . '" />
	</form>';

	// phpcs:ignore WordPress.Security.EscapeOutput
	echo apply_filters(
		/**
		 * Filters the output for the blog directory search form.
		 *
		 * @since 1.9.0
		 *
		 * @param string $search_form_html HTML markup for blog directory search form.
		 */
		'bp_directory_blogs_search_form',
		$search_form_html
	);
}

/**
 * Output the Create a Site button.
 *
 * @since 2.0.0
 */
function bp_blog_create_button() {
	// Escaping is done in `BP_Core_HTML_Element()`.
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_blog_create_button();
}
	/**
	 * Get the Create a Site button.
	 *
	 * @since 2.0.0
	 *
	 * @return false|string
	 */
	function bp_get_blog_create_button() {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		if ( ! bp_blog_signup_enabled() ) {
			return false;
		}

		$url = bp_get_blogs_directory_url(
			array(
				'create_single_item' => 1,
			)
		);

		$button_args = array(
			'id'         => 'create_blog',
			'component'  => 'blogs',
			'link_text'  => __( 'Create a Site', 'buddypress' ),
			'link_class' => 'blog-create no-ajax',
			'link_href'  => $url,
			'wrapper'    => false,
			'block_self' => false,
		);

		/**
		 * Filters the Create a Site button.
		 *
		 * @since 2.0.0
		 *
		 * @param array $button_args Array of arguments to be used for the Create a Site button.
		 */
		return bp_get_button( apply_filters( 'bp_get_blog_create_button', $button_args ) );
	}

/**
 * Output the Create a Site nav item.
 *
 * @since 2.2.0
 */
function bp_blog_create_nav_item() {
	// Escaping is done in `BP_Core_HTML_Element()`.
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_blog_create_nav_item();
}

	/**
	 * Get the Create a Site nav item.
	 *
	 * @since 2.2.0
	 *
	 * @return string
	 */
	function bp_get_blog_create_nav_item() {
		// Get the create a site button.
		$create_blog_button = bp_get_blog_create_button();

		// Make sure the button is available.
		if ( empty( $create_blog_button ) ) {
			return;
		}

		$output = '<li id="blog-create-nav">' . $create_blog_button . '</li>';

		/**
		 * Filters the Create A Site nav item output.
		 *
		 * @since 2.2.0
		 *
		 * @param string $output Nav item output.
		 */
		return apply_filters( 'bp_get_blog_create_nav_item', $output );
	}

/**
 * Checks if a specific theme is still filtering the Blogs directory title
 * if so, transform the title button into a Blogs directory nav item.
 *
 * @since 2.2.0
 */
function bp_blog_backcompat_create_nav_item() {
	// Bail if Blogs nav item is already used by bp-legacy.
	if ( has_action( 'bp_blogs_directory_blog_types', 'bp_legacy_theme_blog_create_nav' ) ) {
		return;
	}

	// Bail if the theme is not filtering the Blogs directory title.
	if ( ! has_filter( 'bp_blogs_directory_header' ) ) {
		return;
	}

	bp_blog_create_nav_item();
}
add_action( 'bp_blogs_directory_blog_types', 'bp_blog_backcompat_create_nav_item', 1000 );

/**
 * Output button for visiting a blog in a loop.
 *
 * @see bp_get_blogs_visit_blog_button_args() for description of arguments.
 *
 * @param array|string $args See {@link bp_get_blogs_visit_blog_button_args()}.
 */
function bp_blogs_visit_blog_button( $args = '' ) {
	// Escaping is done in `BP_Core_HTML_Element()`.
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_blogs_visit_blog_button( $args );
}

	/**
	 * Return the arguments of the button for visiting a blog in a loop.
	 *
	 * @see BP_Button for a complete description of arguments and return
	 *      value.
	 *
	 * @since 11.0.0
	 *
	 * @param array|string $args {
	 *     Arguments are listed below, with their default values. For a
	 *     complete description of arguments, see {@link BP_Button}.
	 *     @type string $id                Default: 'visit_blog'.
	 *     @type string $component         Default: 'blogs'.
	 *     @type bool   $must_be_logged_in Default: false.
	 *     @type bool   $block_self        Default: false.
	 *     @type string $wrapper_class     Default: 'blog-button visit'.
	 *     @type string $link_href         Permalink of the current blog in the loop.
	 *     @type string $link_class        Default: 'blog-button visit'.
	 *     @type string $link_text         Default: 'Visit Site'.
	 *     @type string $link_title        Default: 'Visit Site'.
	 * }
	 * @return array Thhe arguments of the button for visiting a blog in a loop.
	 */
	function bp_get_blogs_visit_blog_button_args( $args = '' ) {
		$button_args = bp_parse_args(
			$args,
			array(
				'id'                => 'visit_blog',
				'component'         => 'blogs',
				'must_be_logged_in' => false,
				'block_self'        => false,
				'wrapper_class'     => 'blog-button visit',
				'link_href'         => bp_get_blog_permalink(),
				'link_class'        => 'blog-button visit',
				'link_text'         => __( 'Visit Site', 'buddypress' ),
				'link_title'        => __( 'Visit Site', 'buddypress' ),
			)
		);

		/**
		 * Filters the button for visiting a blog in a loop.
		 *
		 * @since 1.2.10
		 *
		 * @param array $button_args Array of arguments to be used for the button to visit a blog.
		 */
		return (array) apply_filters( 'bp_get_blogs_visit_blog_button', $button_args );
	}

	/**
	 * Return button for visiting a blog in a loop.
	 *
	 * @see BP_Button for a complete description of arguments and return
	 *      value.
	 *
	 * @see bp_get_blogs_visit_blog_button_args() for description of arguments.
	 *
	 * @param array|string $args See {@link bp_get_blogs_visit_blog_button_args()}.
	 * @return string The HTML for the Visit button.
	 */
	function bp_get_blogs_visit_blog_button( $args = '' ) {
		$button_args = bp_get_blogs_visit_blog_button_args( $args );

		if ( ! array_filter( $button_args ) ) {
			return '';
		}

		return bp_get_button( $button_args );
	}

/** Stats **********************************************************************/

/**
 * Display the number of blogs in user's profile.
 *
 * @since 2.0.0
 *
 * @param array|string $args Before|after|user_id.
 */
function bp_blogs_profile_stats( $args = '' ) {
	echo wp_kses(
		bp_blogs_get_profile_stats( $args ),
		array(
			'li'     => array( 'class' => true ),
			'div'    => array( 'class' => true ),
			'strong' => true,
			'a'      => array( 'href' => true ),
		)
	);
}
add_action( 'bp_members_admin_user_stats', 'bp_blogs_profile_stats', 9, 1 );

/**
 * Return the number of blogs in user's profile.
 *
 * @since 2.0.0
 *
 * @param array|string $args Before|after|user_id.
 * @return string HTML for stats output.
 */
function bp_blogs_get_profile_stats( $args = '' ) {

	// Parse the args.
	$r = bp_parse_args(
		$args,
		array(
			'before'  => '<li class="bp-blogs-profile-stats">',
			'after'   => '</li>',
			'user_id' => bp_displayed_user_id(),
			'blogs'   => 0,
			'output'  => '',
		),
		'blogs_get_profile_stats'
	);

	// Allow completely overloaded output.
	if ( is_multisite() && empty( $r['output'] ) ) {

		// Only proceed if a user ID was passed.
		if ( ! empty( $r['user_id'] ) ) {

			// Get the user's blogs.
			if ( empty( $r['blogs'] ) ) {
				$r['blogs'] = absint( bp_blogs_total_blogs_for_user( $r['user_id'] ) );
			}

			// If blogs exist, show some formatted output.
			$r['output'] = $r['before'];

			/* translators: %s: the number of blogs */
			$r['output'] .= sprintf( _n( '%s site', '%s sites', $r['blogs'], 'buddypress' ), '<strong>' . $r['blogs'] . '</strong>' );
			$r['output'] .= $r['after'];
		}
	}

	/**
	 * Filters the number of blogs in user's profile.
	 *
	 * @since 2.0.0
	 *
	 * @param string $value Output determined for the profile stats.
	 * @param array  $r     Array of arguments used for default output if none provided.
	 */
	return apply_filters( 'bp_blogs_get_profile_stats', $r['output'], $r );
}
