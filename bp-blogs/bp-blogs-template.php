<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Output the blogs component slug
 *
 * @package BuddyPress
 * @subpackage Blogs Template
 * @since BuddyPress (r4100)
 *
 * @uses bp_get_blogs_slug()
 */
function bp_blogs_slug() {
	echo bp_get_blogs_slug();
}
	/**
	 * Return the blogs component slug
	 *
	 * @package BuddyPress
	 * @subpackage Blogs Template
	 * @since BuddyPress (r4100)
	 */
	function bp_get_blogs_slug() {
		global $bp;
		return apply_filters( 'bp_get_blogs_slug', $bp->blogs->slug );
	}

/**
 * Output the blogs component root slug
 *
 * @package BuddyPress
 * @subpackage Blogs Template
 * @since BuddyPress (r4100)
 *
 * @uses bp_get_blogs_root_slug()
 */
function bp_blogs_root_slug() {
	echo bp_get_blogs_root_slug();
}
	/**
	 * Return the blogs component root slug
	 *
	 * @package BuddyPress
	 * @subpackage Blogs Template
	 * @since BuddyPress (r4100)
	 */
	function bp_get_blogs_root_slug() {
		global $bp;
		return apply_filters( 'bp_get_blogs_root_slug', $bp->blogs->root_slug );
	}

/**
 * Output blog directory permalink
 *
 * @package BuddyPress
 * @subpackage Blogs Template
 * @since 1.5
 * @uses bp_get_blogs_directory_permalink()
 */
function bp_blogs_directory_permalink() {
	echo bp_get_blogs_directory_permalink();
}
	/**
	 * Return blog directory permalink
	 *
	 * @package BuddyPress
	 * @subpackage Blogs Template
	 * @since 1.5
	 * @uses apply_filters()
	 * @uses traisingslashit()
	 * @uses bp_get_root_domain()
	 * @uses bp_get_blogs_root_slug()
	 * @return string
	 */
	function bp_get_blogs_directory_permalink() {
		return apply_filters( 'bp_get_blogs_directory_permalink', trailingslashit( bp_get_root_domain() . '/' . bp_get_blogs_root_slug() ) );
	}

/**********************************************************************
 * Blog listing template class.
 */

class BP_Blogs_Template {
	var $current_blog = -1;
	var $blog_count;
	var $blogs;
	var $blog;

	var $in_the_loop;

	var $pag_page;
	var $pag_num;
	var $pag_links;
	var $total_blog_count;

	function bp_blogs_template( $type, $page, $per_page, $max, $user_id, $search_terms ) {
		$this->__construct( $type, $page, $per_page, $max, $user_id, $search_terms );
	}

	function __construct( $type, $page, $per_page, $max, $user_id, $search_terms ) {
		global $bp;

		$this->pag_page = isset( $_REQUEST['bpage'] ) ? intval( $_REQUEST['bpage'] ) : $page;
		$this->pag_num = isset( $_REQUEST['num'] ) ? intval( $_REQUEST['num'] ) : $per_page;

		if ( isset( $_REQUEST['letter'] ) && '' != $_REQUEST['letter'] )
			$this->blogs = BP_Blogs_Blog::get_by_letter( $_REQUEST['letter'], $this->pag_num, $this->pag_page );
		else
			$this->blogs = bp_blogs_get_blogs( array( 'type' => $type, 'per_page' => $this->pag_num, 'page' => $this->pag_page, 'user_id' => $user_id, 'search_terms' => $search_terms ) );

		if ( !$max || $max >= (int)$this->blogs['total'] )
			$this->total_blog_count = (int)$this->blogs['total'];
		else
			$this->total_blog_count = (int)$max;

		$this->blogs = $this->blogs['blogs'];

		if ( $max ) {
			if ( $max >= count($this->blogs) ) {
				$this->blog_count = count( $this->blogs );
			} else {
				$this->blog_count = (int)$max;
			}
		} else {
			$this->blog_count = count( $this->blogs );
		}

		if ( (int)$this->total_blog_count && (int)$this->pag_num ) {
			$this->pag_links = paginate_links( array(
				'base'      => add_query_arg( 'bpage', '%#%' ),
				'format'    => '',
				'total'     => ceil( (int)$this->total_blog_count / (int)$this->pag_num ),
				'current'   => (int)$this->pag_page,
				'prev_text' => _x( '&larr;', 'Blog pagination previous text', 'buddypress' ),
				'next_text' => _x( '&rarr;', 'Blog pagination next text', 'buddypress' ),
				'mid_size'  => 1
			) );
		}
	}

	function has_blogs() {
		if ( $this->blog_count )
			return true;

		return false;
	}

	function next_blog() {
		$this->current_blog++;
		$this->blog = $this->blogs[$this->current_blog];

		return $this->blog;
	}

	function rewind_blogs() {
		$this->current_blog = -1;
		if ( $this->blog_count > 0 ) {
			$this->blog = $this->blogs[0];
		}
	}

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

	function the_blog() {
		global $blog;

		$this->in_the_loop = true;
		$this->blog        = $this->next_blog();

		if ( 0 == $this->current_blog ) // loop has just started
			do_action('blog_loop_start');
	}
}

function bp_rewind_blogs() {
	global $blogs_template;

	$blogs_template->rewind_blogs();
}

function bp_has_blogs( $args = '' ) {
	global $bp, $blogs_template;

	/***
	 * Set the defaults based on the current page. Any of these will be overridden
	 * if arguments are directly passed into the loop. Custom plugins should always
	 * pass their parameters directly to the loop.
	 */
	$type         = 'active';
	$user_id      = 0;
	$search_terms = null;

	/* User filtering */
	if ( !empty( $bp->displayed_user->id ) )
		$user_id = $bp->displayed_user->id;

	$defaults = array(
		'type'         => $type,
		'page'         => 1,
		'per_page'     => 20,
		'max'          => false,

		'user_id'      => $user_id, // Pass a user_id to limit to only blogs this user has higher than subscriber access to
		'search_terms' => $search_terms // Pass search terms to filter on the blog title or description.
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
		if ( $per_page > $max )
			$per_page = $max;
	}

	$blogs_template = new BP_Blogs_Template( $type, $page, $per_page, $max, $user_id, $search_terms );
	return apply_filters( 'bp_has_blogs', $blogs_template->has_blogs(), $blogs_template );
}

function bp_blogs() {
	global $blogs_template;

	return $blogs_template->blogs();
}

function bp_the_blog() {
	global $blogs_template;

	return $blogs_template->the_blog();
}

function bp_blogs_pagination_count() {
	global $bp, $blogs_template;

	$start_num = intval( ( $blogs_template->pag_page - 1 ) * $blogs_template->pag_num ) + 1;
	$from_num  = bp_core_number_format( $start_num );
	$to_num    = bp_core_number_format( ( $start_num + ( $blogs_template->pag_num - 1 ) > $blogs_template->total_blog_count ) ? $blogs_template->total_blog_count : $start_num + ( $blogs_template->pag_num - 1 ) );
	$total     = bp_core_number_format( $blogs_template->total_blog_count );

	echo sprintf( __( 'Viewing site %1$s to %2$s (of %3$s sites)', 'buddypress' ), $from_num, $to_num, $total );
}

function bp_blogs_pagination_links() {
	echo bp_get_blogs_pagination_links();
}
	function bp_get_blogs_pagination_links() {
		global $blogs_template;

		return apply_filters( 'bp_get_blogs_pagination_links', $blogs_template->pag_links );
	}

function bp_blog_avatar( $args = '' ) {
	echo bp_get_blog_avatar( $args );
}
	function bp_get_blog_avatar( $args = '' ) {
		global $blogs_template, $bp;

		$defaults = array(
			'type'    => 'full',
			'width'   => false,
			'height'  => false,
			'class'   => 'avatar',
			'id'      => false,
			'alt'     => __( 'Site authored by %s', 'buddypress' ),
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

function bp_blog_name() {
	echo bp_get_blog_name();
}
	function bp_get_blog_name() {
		global $blogs_template;

		return apply_filters( 'bp_get_blog_name', $blogs_template->blog->name );
	}

function bp_blog_description() {
	echo apply_filters( 'bp_blog_description', bp_get_blog_description() );
}
	function bp_get_blog_description() {
		global $blogs_template;

		return apply_filters( 'bp_get_blog_description', $blogs_template->blog->description );
	}

function bp_blog_last_active() {
	echo bp_get_blog_last_active();
}
	function bp_get_blog_last_active() {
		global $blogs_template;

		return apply_filters( 'bp_blog_last_active', bp_core_get_last_activity( $blogs_template->blog->last_activity, __( 'active %s', 'buddypress' ) ) );
	}

function bp_blog_latest_post() {
	echo bp_get_blog_latest_post();
}
	function bp_get_blog_latest_post() {
		global $blogs_template;

		if ( null == $blogs_template->blog->latest_post )
			return false;

		return apply_filters( 'bp_get_blog_latest_post', sprintf( __( 'Latest Post: %s', 'buddypress' ), '<a href="' . $blogs_template->blog->latest_post->guid . '">' . apply_filters( 'the_title', $blogs_template->blog->latest_post->post_title ) . '</a>' ) );
	}

function bp_blog_hidden_fields() {
	if ( isset( $_REQUEST['s'] ) )
		echo '<input type="hidden" id="search_terms" value="' . esc_attr( $_REQUEST['s'] ). '" name="search_terms" />';

	if ( isset( $_REQUEST['letter'] ) )
		echo '<input type="hidden" id="selected_letter" value="' . esc_attr( $_REQUEST['letter'] ) . '" name="selected_letter" />';

	if ( isset( $_REQUEST['blogs_search'] ) )
		echo '<input type="hidden" id="search_terms" value="' . esc_attr( $_REQUEST['blogs_search'] ) . '" name="search_terms" />';
}

function bp_total_blog_count() {
	echo bp_get_total_blog_count();
}
	function bp_get_total_blog_count() {
		return apply_filters( 'bp_get_total_blog_count', bp_blogs_total_blogs() );
	}
	add_filter( 'bp_get_total_blog_count', 'bp_core_number_format' );

function bp_total_blog_count_for_user( $user_id = 0 ) {
	echo bp_get_total_blog_count_for_user( $user_id );
}
	function bp_get_total_blog_count_for_user( $user_id = 0 ) {
		return apply_filters( 'bp_get_total_blog_count_for_user', bp_blogs_total_blogs_for_user( $user_id ) );
	}
	add_filter( 'bp_get_total_blog_count_for_user', 'bp_core_number_format' );


/* Blog registration template tags */

function bp_blog_signup_enabled() {
	global $bp;

	$active_signup = isset( $bp->site_options['registration'] ) ? $bp->site_options['registration'] : 'all';

	$active_signup = apply_filters( 'wpmu_active_signup', $active_signup ); // return "all", "none", "blog" or "user"

	if ( 'none' == $active_signup || 'user' == $active_signup )
		return false;

	return true;
}

function bp_show_blog_signup_form($blogname = '', $blog_title = '', $errors = '') {
	global $current_user, $current_site;
	global $bp;

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
		echo '<span class="prefix_address">' . $current_site->domain . $current_site->path . '</span> <input name="blogname" type="text" id="blogname" value="'.$blogname.'" maxlength="50" /><br />';
	else
		echo '<input name="blogname" type="text" id="blogname" value="'.$blogname.'" maxlength="50" /> <span class="suffix_address">.' . preg_replace( '|^www\.|', '', $current_site->domain ) . $current_site->path . '</span><br />';

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

	wpmu_create_blog( $domain, $path, $blog_title, $current_user->id, $meta, $wpdb->siteid );
	bp_blogs_confirm_blog_signup($domain, $path, $blog_title, $current_user->user_login, $current_user->user_email, $meta);
	return true;
}

function bp_blogs_validate_blog_form() {
	$user = '';
	if ( is_user_logged_in() )
		$user = wp_get_current_user();

	return wpmu_validate_blog_signup($_POST['blogname'], $_POST['blog_title'], $user);
}

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

function bp_create_blog_link() {
	global $bp;

	if ( bp_is_my_profile() )
		echo apply_filters( 'bp_create_blog_link', '<a href="' . bp_get_root_domain() . '/' . bp_get_blogs_root_slug() . '/create/">' . __( 'Create a Site', 'buddypress' ) . '</a>' );
}

function bp_blogs_blog_tabs() {
	global $bp, $groups_template;

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
	do_action( 'bp_blogs_blog_tabs', $current_tab );
}

function bp_directory_blogs_search_form() {
	global $bp;

	$default_search_value = bp_get_search_default_text();
	$search_value = !empty( $_REQUEST['s'] ) ? stripslashes( $_REQUEST['s'] ) : $default_search_value; ?>

	<form action="" method="get" id="search-blogs-form">
		<label><input type="text" name="s" id="blogs_search" value="<?php echo esc_attr( $search_value ) ?>"  onfocus="if (this.value == '<?php echo $default_search_value ?>') {this.value = '';}" onblur="if (this.value == '') {this.value = '<?php echo $default_search_value ?>';}" /></label>
		<input type="submit" id="blogs_search_submit" name="blogs_search_submit" value="<?php _e( 'Search', 'buddypress' ) ?>" />
	</form>

<?php
}

/**
 * bp_blogs_visit_blog_button()
 *
 * Output button for visiting a blog in a loop
 *
 * @param array $args Custom button properties
 */
function bp_blogs_visit_blog_button( $args = '' ) {
	echo bp_get_blogs_visit_blog_button( $args );
}
	/**
	 * bp_get_blogs_visit_blog_button()
	 *
	 * Return button for visiting a blog in a loop
	 *
	 * @param array $args Custom button properties
	 * @return string
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

?>