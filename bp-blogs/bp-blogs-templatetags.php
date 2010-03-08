<?php

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
			if ( $max >= count($this->blogs) )
				$this->blog_count = count($this->blogs);
			else
				$this->blog_count = (int)$max;
		} else {
			$this->blog_count = count($this->blogs);
		}

		$this->pag_links = paginate_links( array(
			'base' => add_query_arg( 'bpage', '%#%' ),
			'format' => '',
			'total' => ceil( (int) $this->total_blog_count / (int) $this->pag_num ),
			'current' => (int) $this->pag_page,
			'prev_text' => '&larr;',
			'next_text' => '&rarr;',
			'mid_size' => 1
		));
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
			do_action('loop_end');
			// Do some cleaning up after the loop
			$this->rewind_blogs();
		}

		$this->in_the_loop = false;
		return false;
	}

	function the_blog() {
		global $blog;

		$this->in_the_loop = true;
		$this->blog = $this->next_blog();

		if ( 0 == $this->current_blog ) // loop has just started
			do_action('loop_start');
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
	$type = 'active';
	$user_id = false;
	$search_terms = false;

	/* User filtering */
	if ( !empty( $bp->displayed_user->id ) )
		$user_id = $bp->displayed_user->id;

	if ( !empty( $_REQUEST['s'] ) )
		$search_terms = $_REQUEST['s'];

	$defaults = array(
		'type' => $type,
		'page' => 1,
		'per_page' => 20,
		'max' => false,

		'user_id' => $user_id, // Pass a user_id to limit to only blogs this user has higher than subscriber access to
		'search_terms' => $search_terms // Pass search terms to filter on the blog title or description.
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r );

	if ( $max ) {
		if ( $per_page > $max )
			$per_page = $max;
	}

	$blogs_template = new BP_Blogs_Template( $type, $page, $per_page, $max, $user_id, $search_terms );
	return $blogs_template->has_blogs();
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

	$from_num = bp_core_number_format( intval( ( $blogs_template->pag_page - 1 ) * $blogs_template->pag_num ) + 1 );
	$to_num = bp_core_number_format( ( $from_num + ( $blogs_template->pag_num - 1 ) > $blogs_template->total_blog_count ) ? $blogs_template->total_blog_count : $from_num + ( $blogs_template->pag_num - 1 ) );
	$total = bp_core_number_format( $blogs_template->total_blog_count );

	echo sprintf( __( 'Viewing blog %s to %s (of %s blogs)', 'buddypress' ), $from_num, $to_num, $total ); ?> &nbsp;
	<span class="ajax-loader"></span><?php
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
			'type' => 'full',
			'width' => false,
			'height' => false,
			'class' => 'avatar',
			'id' => false,
			'alt' => __( 'Blog avatar', 'buddypress' ),
			'no_grav' => true
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		/***
		 * In future BuddyPress versions you will be able to set the avatar for a blog.
		 * Right now you can use a filter with the ID of the blog to change it if you wish.
		 * By default it will return the avatar for the primary blog admin.
		 */
		return apply_filters( 'bp_get_blog_avatar_' . $blogs_template->blog->blog_id, bp_core_fetch_avatar( array( 'item_id' => $blogs_template->blog->admin_user_id, 'type' => $type, 'alt' => $alt, 'width' => $width, 'height' => $height, 'class' => $class, 'email' => $blogs_template->blog->admin_user_email ) ) );
	}

function bp_blog_permalink() {
	echo bp_get_blog_permalink();
}
	function bp_get_blog_permalink() {
		global $blogs_template;

		if ( empty( $blogs_template->blog->domain ) )
			$permalink = $bp->root_domain . $blogs_template->blog->path;
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

		return apply_filters( 'bp_blog_last_active', bp_core_get_last_activity( $blogs_template->blog->last_activity, __( 'active %s ago', 'buddypress' ) ) );
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
	if ( isset( $_REQUEST['s'] ) ) {
		echo '<input type="hidden" id="search_terms" value="' . attribute_escape( $_REQUEST['s'] ). '" name="search_terms" />';
	}

	if ( isset( $_REQUEST['letter'] ) ) {
		echo '<input type="hidden" id="selected_letter" value="' . attribute_escape( $_REQUEST['letter'] ) . '" name="selected_letter" />';
	}

	if ( isset( $_REQUEST['blogs_search'] ) ) {
		echo '<input type="hidden" id="search_terms" value="' . attribute_escape( $_REQUEST['blogs_search'] ) . '" name="search_terms" />';
	}
}

function bp_total_blog_count() {
	echo bp_get_total_blog_count();
}
	function bp_get_total_blog_count() {
		return apply_filters( 'bp_get_total_blog_count', bp_blogs_total_blogs() );
	}
	add_filter( 'bp_get_total_blog_count', 'bp_core_number_format' );

function bp_total_blog_count_for_user( $user_id = false ) {
	echo bp_get_total_blog_count_for_user( $user_id );
}
	function bp_get_total_blog_count_for_user( $user_id = false ) {
		return apply_filters( 'bp_get_total_blog_count_for_user', bp_blogs_total_blogs_for_user( $user_id ) );
	}
	add_filter( 'bp_get_total_blog_count_for_user', 'bp_core_number_format' );


/* Blog registration template tags */

function bp_blog_signup_enabled() {
	global $bp;

	$active_signup = $bp->site_options['registration'];

	if ( !$active_signup )
		$active_signup = 'all';

	$active_signup = apply_filters( 'wpmu_active_signup', $active_signup ); // return "all", "none", "blog" or "user"

	if ( 'none' == $active_signup || 'user' == $active_signup )
		return false;

	return true;
}

function bp_show_blog_signup_form($blogname = '', $blog_title = '', $errors = '') {
	global $current_user, $current_site;
	global $bp;

	require_once( ABSPATH . WPINC . '/registration.php' );

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
		<p><?php printf(__("By filling out the form below, you can <strong>add a blog to your account</strong>. There is no limit to the number of blogs you can have, so create to your heart's content, but blog responsibly.", 'buddypress'), $current_user->display_name) ?></p>

		<p><?php _e("If you&#8217;re not going to use a great blog domain, leave it for a new user. Now have at it!", 'buddypress') ?></p>

		<form class="standard-form" id="setupform" method="post" action="">

			<input type="hidden" name="stage" value="gimmeanotherblog" />
			<?php do_action( "signup_hidden_fields" ); ?>

			<?php bp_blogs_signup_blog($blogname, $blog_title, $errors); ?>
			<p>
				<input id="submit" type="submit" name="submit" class="submit" value="<?php _e('Create Blog &rarr;', 'buddypress') ?>" />
			</p>

			<?php wp_nonce_field( 'bp_blog_signup_form' ) ?>
		</form>
		<?php
	}
}

function bp_blogs_signup_blog( $blogname = '', $blog_title = '', $errors = '' ) {
	global $current_site;

	// Blog name
	if( 'no' == constant( "VHOST" ) )
		echo '<label for="blogname">' . __('Blog Name:', 'buddypress') . '</label>';
	else
		echo '<label for="blogname">' . __('Blog Domain:', 'buddypress') . '</label>';

	if ( $errmsg = $errors->get_error_message('blogname') ) { ?>
		<p class="error"><?php echo $errmsg ?></p>
	<?php }

	if( 'no' == constant( "VHOST" ) ) {
		echo '<span class="prefix_address">' . $current_site->domain . $current_site->path . '</span> <input name="blogname" type="text" id="blogname" value="'.$blogname.'" maxlength="50" /><br />';
	} else {
		echo '<input name="blogname" type="text" id="blogname" value="'.$blogname.'" maxlength="50" /> <span class="suffix_address">.' . $current_site->domain . $current_site->path . '</span><br />';
	}
	if ( !is_user_logged_in() ) {
		print '(<strong>' . __( 'Your address will be ' , 'buddypress');
		if( 'no' == constant( "VHOST" ) ) {
			print $current_site->domain . $current_site->path . __( 'blogname' , 'buddypress');
		} else {
			print __( 'domain.' , 'buddypress') . $current_site->domain . $current_site->path;
		}
		echo '.</strong> ' . __( 'Must be at least 4 characters, letters and numbers only. It cannot be changed so choose carefully!)' , 'buddypress') . '</p>';
	}

	// Blog Title
	?>
	<label for="blog_title"><?php _e('Blog Title:', 'buddypress') ?></label>
	<?php if ( $errmsg = $errors->get_error_message('blog_title') ) { ?>
		<p class="error"><?php echo $errmsg ?></p>
	<?php }
	echo '<input name="blog_title" type="text" id="blog_title" value="'.wp_specialchars($blog_title, 1).'" /></p>';
	?>

	<p>
		<label for="blog_public_on"><?php _e('Privacy:', 'buddypress') ?></label>
		<?php _e('I would like my blog to appear in search engines like Google and Technorati, and in public listings around this site.', 'buddypress'); ?>


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
	global $wpdb, $current_user, $blogname, $blog_title, $errors, $domain, $path;

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

	/* If this is a VHOST install, remove the username from the domain as we are setting this blog
	   up inside a user domain, not the root domain. */

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
	?>
	<p><?php _e('Congratulations! You have successfully registered a new blog.', 'buddypress') ?></p>
	<p>
		<?php printf(__('<a href="http://%1$s">http://%2$s</a> is your new blog.  <a href="%3$s">Login</a> as "%4$s" using your existing password.', 'buddypress'), $domain.$path, $domain.$path, "http://" . $domain.$path . "wp-login.php", $user_name) ?>
	</p>
	<?php
	do_action('signup_finished');
}

function bp_create_blog_link() {
	global $bp;

	if ( bp_is_my_profile() )	{
		echo apply_filters( 'bp_create_blog_link', '<a href="' . $bp->root_domain . '/' . $bp->blogs->slug . '/create">' . __('Create a Blog', 'buddypress') . '</a>' );
	}
}

function bp_blogs_blog_tabs() {
	global $bp, $groups_template;

	// Don't show these tabs on a user's own profile
	if ( bp_is_my_profile() )
		return false;

	$current_tab = $bp->current_action
?>
	<ul class="content-header-nav">
		<li<?php if ( 'my-blogs' == $current_tab || empty( $current_tab ) ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp->displayed_user->domain . $bp->blogs->slug ?>/my-blogs"><?php printf( __( "%s's Blogs", 'buddypress' ), $bp->displayed_user->fullname )  ?></a></li>
		<li<?php if ( 'recent-posts' == $current_tab ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp->displayed_user->domain . $bp->blogs->slug ?>/recent-posts"><?php printf( __( "%s's Recent Posts", 'buddypress' ), $bp->displayed_user->fullname )  ?></a></li>
		<li<?php if ( 'recent-comments' == $current_tab ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp->displayed_user->domain . $bp->blogs->slug ?>/recent-comments"><?php printf( __( "%s's Recent Comments", 'buddypress' ), $bp->displayed_user->fullname )  ?></a></li>
	</ul>
<?php
	do_action( 'bp_blogs_blog_tabs', $current_tab );
}

function bp_directory_blogs_search_form() {
	global $bp; ?>
	<form action="" method="get" id="search-blogs-form">
		<label><input type="text" name="s" id="blogs_search" value="<?php if ( isset( $_GET['s'] ) ) { echo $_GET['s']; } else { _e( 'Search anything...', 'buddypress' ); } ?>"  onfocus="if (this.value == '<?php _e( 'Search anything...', 'buddypress' ) ?>') {this.value = '';}" onblur="if (this.value == '') {this.value = '<?php _e( 'Search anything...', 'buddypress' ) ?>';}" /></label>
		<input type="submit" id="blogs_search_submit" name="blogs_search_submit" value="<?php _e( 'Search', 'buddypress' ) ?>" />
	</form>
<?php
}

?>