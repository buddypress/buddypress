<?php

/* Blog registration template tags */

function bp_blog_signup_enabled() {
	$active_signup = get_site_option( 'registration' );
	
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

		<form class="standard-form" id="setupform" method="post" action="<?php echo $bp->loggedin_user->domain . $bp->blogs->slug . '/create-a-blog' ?>">

			<input type="hidden" name="stage" value="gimmeanotherblog" />
			<?php do_action( "signup_hidden_fields" ); ?>
			
			<?php bp_blogs_signup_blog($blogname, $blog_title, $errors); ?>
			<p>
				<input id="submit" type="submit" name="submit" class="submit" value="<?php _e('Create Blog &raquo;', 'buddypress') ?>" />
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
		echo '<span class="prefix_address">' . $current_site->domain . $current_site->path . '</span><input name="blogname" type="text" id="blogname" value="'.$blogname.'" maxlength="50" /><br />';
	} else {
		echo '<input name="blogname" type="text" id="blogname" value="'.$blogname.'" maxlength="50" /><span class="suffix_address">.' . $current_site->domain . $current_site->path . '</span><br />';
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
	
	if ( bp_is_home() )	{
		echo apply_filters( 'bp_create_blog_link', '<a href="' . $bp->loggedin_user->domain . $bp->blogs->slug . '/create-a-blog">' . __('Create a Blog', 'buddypress') . '</a>' );
	}
}

function bp_blogs_blog_tabs() {
	global $bp, $groups_template;
	
	// Don't show these tabs on a user's own profile
	if ( bp_is_home() )
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

/**********************************************************************
 * User Blog listing template class
 */

class BP_Blogs_User_Blogs_Template {
	var $current_blog = -1;
	var $blog_count;
	var $blogs;
	var $blog;
	
	var $in_the_loop;
	
	var $pag_page;
	var $pag_num;
	var $pag_links;
	var $total_blog_count;
	
	function bp_blogs_user_blogs_template( $user_id, $per_page, $max ) {
		global $bp;
		
		if ( !$user_id )
			$user_id = $bp->displayed_user->id;

		$this->pag_page = isset( $_GET['fpage'] ) ? intval( $_GET['fpage'] ) : 1;
		$this->pag_num = isset( $_GET['num'] ) ? intval( $_GET['num'] ) : $per_page;

		if ( !$this->blogs = wp_cache_get( 'bp_blogs_for_user_' . $user_id, 'bp' ) ) {
			$this->blogs = bp_blogs_get_blogs_for_user( $user_id );
			wp_cache_set( 'bp_blogs_for_user_' . $user_id, $this->blogs, 'bp' );
		}
		
		if ( !$max || $max >= (int)$this->blogs['count'] )
			$this->total_blog_count = (int)$this->blogs['count'];
		else
			$this->total_blog_count = (int)$max;
		
		$this->blogs = array_slice( (array)$this->blogs['blogs'], intval( ( $this->pag_page - 1 ) * $this->pag_num), intval( $this->pag_num ) );
		
		if ( $max ) {
			if ( $max >= count($this->blogs) )
				$this->blog_count = count($this->blogs);
			else
				$this->blog_count = (int)$max;
		} else {
			$this->blog_count = count($this->blogs);
		}
		
		$this->pag_links = paginate_links( array(
			'base' => add_query_arg( 'fpage', '%#%' ),
			'format' => '',
			'total' => ceil($this->total_blog_count / $this->pag_num),
			'current' => $this->pag_page,
			'prev_text' => '&laquo;',
			'next_text' => '&raquo;',
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
	
	function user_blogs() { 
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
		$blog = $this->next_blog();
		
		if ( 0 == $this->current_blog ) // loop has just started
			do_action('loop_start');
	}
}

function bp_has_blogs( $args = '' ) {
	global $blogs_template;

	$defaults = array(
		'user_id' => false,
		'per_page' => 10,
		'max' => false
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	$blogs_template = new BP_Blogs_User_Blogs_Template( $user_id, $per_page, $max );
	return $blogs_template->has_blogs();
}

function bp_blogs() {
	global $blogs_template;
	return $blogs_template->user_blogs();
}

function bp_the_blog() {
	global $blogs_template;
	return $blogs_template->the_blog();
}

function bp_blogs_pagination_count() {
	global $bp, $blogs_template;
	
	$from_num = intval( ( $blogs_template->pag_page - 1 ) * $blogs_template->pag_num ) + 1;
	$to_num = ( $from_num + ( $blogs_template->pag_num - 1 ) > $blogs_template->total_blog_count ) ? $blogs_template->total_blog_count : $from_num + ( $blogs_template->pag_num - 1 ) ;

	echo sprintf( __( 'Viewing blog %d to %d (of %d blogs)', 'buddypress' ), $from_num, $to_num, $blogs_template->total_blog_count ); ?> &nbsp;
	<span class="ajax-loader"></span><?php
}

function bp_blogs_pagination_links() {
	echo bp_get_blogs_pagination_links();
}
	function bp_get_blogs_pagination_links() {
		global $blogs_template;

		return apply_filters( 'bp_get_blogs_pagination_links', $blogs_template->pag_links );
	}

function bp_blog_title() {
	echo bp_get_blog_title();
}
	function bp_get_blog_title() {
		global $blogs_template;
			
		return apply_filters( 'bp_get_blog_title', $blogs_template->blog['title'] );
	}

function bp_blog_description() {
	echo bp_get_blog_description();
}
	function bp_get_blog_description() {
		global $blogs_template;
		
		return apply_filters( 'bp_get_blog_description', $blogs_template->blog['description'] );
	}

function bp_blog_permalink() {
	echo bp_get_blog_permalink();
}
	function bp_get_blog_permalink() {
		global $blogs_template;	
		
		return apply_filters( 'bp_get_blog_permalink', $blogs_template->blog['siteurl'] );
	}


/**********************************************************************
 * User Blog Posts listing template class
 */

class BP_Blogs_Blog_Post_Template {
	var $current_post = -1;
	var $post_count;
	var $posts;
	var $post;
	
	var $in_the_loop;
	
	var $pag_page;
	var $pag_num;
	var $pag_links;
	var $total_post_count;
	
	function bp_blogs_blog_post_template( $user_id, $per_page, $max ) {
		global $bp;
		
		if ( !$user_id )
			$user_id = $bp->displayed_user->id;

		$this->pag_page = isset( $_GET['fpage'] ) ? intval( $_GET['fpage'] ) : 1;
		$this->pag_num = isset( $_GET['num'] ) ? intval( $_GET['num'] ) : $per_page;
		
		if ( !$this->posts = wp_cache_get( 'bp_user_posts_' . $user_id, 'bp' ) ) {
			$this->posts = bp_blogs_get_posts_for_user( $user_id );
			wp_cache_set( 'bp_user_posts_' . $user_id, $this->posts, 'bp' );
		}
		
		if ( !$max || $max >= (int)$this->posts['count'] )
			$this->total_post_count = (int)$this->posts['count'];
		else
			$this->total_post_count = (int)$max;
		
		$this->posts = array_slice( (array)$this->posts['posts'], intval( ( $this->pag_page - 1 ) * $this->pag_num), intval( $this->pag_num ) );

		if ( $max ) {
			if ( $max >= count($this->posts) )
				$this->post_count = count($this->posts);
			else
				$this->post_count = (int)$max;
		} else {
			$this->post_count = count($this->posts);
		}
		
		$this->pag_links = paginate_links( array(
			'base' => add_query_arg( 'fpage', '%#%' ),
			'format' => '',
			'total' => ceil($this->total_post_count / $this->pag_num),
			'current' => $this->pag_page,
			'prev_text' => '&laquo;',
			'next_text' => '&raquo;',
			'mid_size' => 1
		));	
	}
	
	function has_posts() {
		if ( $this->post_count )
			return true;
		
		return false;
	}
	
	function next_post() {
		$this->current_post++;
		$this->post = $this->posts[$this->current_post];
		
		return $this->post;
	}
	
	function rewind_posts() {
		$this->current_post = -1;
		if ( $this->post_count > 0 ) {
			$this->post = $this->posts[0];
		}
	}
	
	function user_posts() { 
		if ( $this->current_post + 1 < $this->post_count ) {
			return true;
		} elseif ( $this->current_post + 1 == $this->post_count ) {
			do_action('loop_end');
			// Do some cleaning up after the loop
			$this->rewind_posts();
		}

		$this->in_the_loop = false;
		return false;
	}
	
	function the_post() {
		global $post;

		$this->in_the_loop = true;
		$post = $this->next_post();
		
		if ( 0 == $this->current_post ) // loop has just started
			do_action('loop_start');
	}
}

function bp_has_posts( $args = '' ) {
	global $posts_template;

	$defaults = array(
		'user_id' => false,
		'per_page' => 10,
		'max' => false
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	$posts_template = new BP_Blogs_Blog_Post_Template( $user_id, $per_page, $max );	
	return $posts_template->has_posts();
}

function bp_posts() {
	global $posts_template;
	return $posts_template->user_posts();
}

function bp_the_post() {
	global $posts_template;
	return $posts_template->the_post();
}

function bp_post_pagination_count() {
	global $bp, $posts_template;
	
	$from_num = intval( ( $posts_template->pag_page - 1 ) * $posts_template->pag_num ) + 1;
	$to_num = ( $from_num + ( $posts_template->pag_num - 1 ) > $posts_template->total_post_count ) ? $posts_template->total_post_count : $from_num + ( $posts_template->pag_num - 1 ) ;

	echo sprintf( __( 'Viewing post %d to %d (of %d posts)', 'buddypress' ), $from_num, $to_num, $posts_template->total_post_count ); ?> &nbsp;
	<span class="ajax-loader"></span><?php
}

function bp_post_pagination_links() {
	echo bp_get_post_pagination_links();
}
	function bp_get_post_pagination_links() {
		global $posts_template;

		return apply_filters( 'bp_get_post_pagination_links', $posts_template->pag_links );
	}

function bp_post_id() {
	echo bp_get_post_id();
}
	function bp_get_post_id() {
		global $posts_template;
		echo apply_filters( 'bp_get_post_id', $posts_template->post->ID );	
	}
	
function bp_post_title( $deprecated = true ) {
	if ( !$deprecated )
		bp_get_post_title();
	else
		echo bp_get_post_title();
}
	function bp_get_post_title() {
		global $posts_template;
		
		return apply_filters( 'bp_get_post_title', $posts_template->post->post_title );
	}

function bp_post_permalink() {
	global $posts_template;
	
	echo bp_post_get_permalink();	
}

function bp_post_excerpt() {
	echo bp_get_post_excerpt();	
}
	function bp_get_post_excerpt() {
		global $posts_template;
		echo apply_filters( 'bp_get_post_excerpt', $posts_template->post->post_excerpt );	
	}

function bp_post_content() {
	echo bp_get_post_content();
}
	function bp_get_post_content() {
		global $posts_template;
		$content = $posts_template->post->post_content;
		$content = apply_filters('the_content', $content);
		$content = str_replace(']]>', ']]&gt;', $content);
		return apply_filters( 'bp_get_post_content', $content );
	}

function bp_post_status() {
	echo bp_get_post_status();
}
	function bp_get_post_status() {
		global $posts_template;
		return apply_filters( 'bp_get_post_status', $posts_template->post->post_status );	
	}
	
function bp_post_date( $date_format = null, $deprecated = true ) {
	if ( !$date_format )
		$date_format = get_option('date_format');
		
	if ( !$deprecated )
		return bp_get_post_date( $date_format );
	else
		echo bp_get_post_date();
}
	function bp_get_post_date( $date_format = null ) {
		global $posts_template;

		if ( !$date_format )
			$date_format = get_option('date_format');

		echo apply_filters( 'bp_get_post_date', mysql2date( $date_format, $posts_template->post->post_date ) );
	}

function bp_post_comment_count() {
	echo bp_get_post_comment_count();
}
	function bp_get_post_comment_count() {
		global $posts_template;
		return apply_filters( 'bp_get_post_comment_count', $posts_template->post->comment_count );	
	}

function bp_post_comments( $zero = 'No Comments', $one = '1 Comment', $more = '% Comments', $css_class = '', $none = 'Comments Off' ) {
	global $posts_template, $wpdb;

	$number = get_comments_number( $posts_template->post->ID );

	if ( 0 == $number && 'closed' == $posts_template->postcomment_status && 'closed' == $posts_template->postping_status ) {
		echo '<span' . ((!empty($css_class)) ? ' class="' . $css_class . '"' : '') . '>' . $none . '</span>';
		return;
	}

	if ( !empty($posts_template->postpost_password) ) { // if there's a password
		if ( !isset($_COOKIE['wp-postpass_' . COOKIEHASH]) || $_COOKIE['wp-postpass_' . COOKIEHASH] != $posts_template->postpost_password ) {  // and it doesn't match the cookie
			echo __('Enter your password to view comments', 'buddypress');
			return;
		}
	}

	echo '<a href="';
	
	if ( 0 == $number )
		echo bp_post_get_permalink() . '#respond';
	else
		echo bp_post_get_permalink() . '#comments';
	echo '"';
	
	if ( !empty( $css_class ) ) {
		echo ' class="'.$css_class.'" ';
	}
	$title = attribute_escape( $posts_template->post->post_title );

	echo apply_filters( 'comments_popup_link_attributes', '' );

	echo ' title="' . sprintf( __('Comment on %s', 'buddypress'), $title ) . '">';
	comments_number( $zero, $one, $more, $number );
	echo '</a>';
}

function bp_post_author( $deprecated = true ) {
	if ( !$deprecated )
		return bp_get_post_author();
	else
		echo bp_get_post_author();
}
	function bp_get_post_author() {
		global $posts_template;
		
		return apply_filters( 'bp_get_post_author', bp_core_get_userlink( $posts_template->post->post_author ) );
	}

function bp_post_category( $separator = '', $parents = '', $post_id = false, $deprecated = true ) {
	global $posts_template;

	if ( !$deprecated )
		return bp_get_post_category( $separator, $parents, $post_id );
	else
		echo bp_get_post_category();
}
	function bp_get_post_category( $separator = '', $parents = '', $post_id = false ) {
		global $posts_template;

		if ( !$post_id )
			$post_id = $posts_template->post->ID;

		return apply_filters( 'bp_get_post_category', get_the_category_list( $separator, $parents, $post_id ) );	
	}

function bp_post_tags( $before = '', $sep = ', ', $after = '' ) {
	global $posts_template, $wpdb;
	
	switch_to_blog( $posts_template->post->blog_id );
	$terms = bp_post_get_term_list( $before, $sep, $after );
	restore_current_blog();
}

function bp_post_blog_id() {
	echo bp_get_post_blog_id();
}
	function bp_get_post_blog_id() {
		global $posts_template;

		return apply_filters( 'bp_get_post_blog_id', $posts_template->post->blog_id );
	}

function bp_post_blog_name() {
	echo bp_get_post_blog_name();
}
	function bp_get_post_blog_name() {
		global $posts_template;
		return apply_filters( 'bp_get_post_blog_name', get_blog_option( $posts_template->post->blog_id, 'blogname' ) );	
	}

function bp_post_blog_permalink() {
	echo bp_get_post_blog_permalink();	
}
	function bp_get_post_blog_permalink() {
		global $posts_template;
		return apply_filters( 'bp_get_post_blog_permalink', get_blog_option( $posts_template->post->blog_id, 'siteurl' ) );	
	}
	
function bp_post_get_permalink( $post = null, $blog_id = null ) {
	global $current_blog, $posts_template;
	
	if ( !$post )
		$post = $posts_template->post;	
		
	if ( !$blog_id )
		$blog_id = $posts_template->post->blog_id;
		
	if ( !$post || !$blog_id )
		return false;
		
	$rewritecode = array(
		'%year%',
		'%monthnum%',
		'%day%',
		'%hour%',
		'%minute%',
		'%second%',
		$leavename? '' : '%postname%',
		'%post_id%',
		'%category%',
		'%author%',
		$leavename? '' : '%pagename%',
	);

	if ( 'page' == $post->post_type )
		return get_page_link($post->ID, $leavename);
	else if ( 'attachment' == $post->post_type )
		return get_attachment_link($post->ID);

	$permalink = get_blog_option( $blog_id, 'permalink_structure' );
	$site_url = get_blog_option( $blog_id, 'siteurl' ); 

	if ( '' != $permalink && !in_array($post->post_status, array('draft', 'pending')) ) {
		$unixtime = strtotime($post->post_date);

		$category = '';
		if ( false !== strpos($permalink, '%category%') ) {
			$cats = get_the_category($post->ID);
			if ( $cats )
				usort($cats, '_usort_terms_by_ID'); // order by ID
			$category = $cats[0]->slug;
			if ( $parent=$cats[0]->parent )
				$category = get_category_parents($parent, FALSE, '/', TRUE) . $category;

			// show default category in permalinks, without
			// having to assign it explicitly
			if ( empty($category) ) {
				$default_category = get_category( get_option( 'default_category' ) );
				$category = is_wp_error( $default_category ) ? '' : $default_category->slug; 
			}
		}

		$author = '';
		if ( false !== strpos($permalink, '%author%') ) {
			$authordata = get_userdata($post->post_author);
			$author = $authordata->user_nicename;
		}

		$date = explode(" ",date('Y m d H i s', $unixtime));
		$rewritereplace =
		array(
			$date[0],
			$date[1],
			$date[2],
			$date[3],
			$date[4],
			$date[5],
			$post->post_name,
			$post->ID,
			$category,
			$author,
			$post->post_name,
		);
		$permalink = $site_url . str_replace($rewritecode, $rewritereplace, $permalink);
		$permalink = user_trailingslashit($permalink, 'single');
		return apply_filters('post_link', $permalink, $post);
	} else { // if they're not using the fancy permalink option
		$permalink = $site_url . '/?p=' . $post->ID;
		return apply_filters('post_link', $permalink, $post);
	}
}

function bp_post_get_term_list( $before = '', $sep = '', $after = '' ) {
	global $posts_template;
	
	$terms = get_the_terms( $posts_template->post->ID, 'post_tag' );

	if ( is_wp_error($terms) )
		return $terms;

	if ( empty( $terms ) )
		return false;

	foreach ( $terms as $term ) {
		$link = get_blog_option( BP_ROOT_BLOG, 'siteurl') . '/tag/' . $term->slug;
		$link = apply_filters('term_link', $link);
		
		$term_links[] = '<a href="' . $link . '" rel="tag">' . $term->name . '</a>';
	}

	$term_links = apply_filters( "term_links-$taxonomy", $term_links );

	echo $before . join($sep, $term_links) . $after;
}


/**********************************************************************
 * User Blog Comments listing template class
 */

class BP_Blogs_Post_Comment_Template {
	var $current_comment = -1;
	var $comment_count;
	var $comments;
	var $comment;
	
	var $in_the_loop;
	
	var $pag_page;
	var $pag_num;
	var $pag_links;
	var $total_comment_count;
	
	function bp_blogs_post_comment_template( $user_id, $per_page, $max ) {
		global $bp;
		
		if ( !$user_id )
			$user_id = $bp->displayed_user->id;

		$this->pag_page = isset( $_GET['compage'] ) ? intval( $_GET['compage'] ) : 1;
		$this->pag_num = isset( $_GET['num'] ) ? intval( $_GET['num'] ) : $per_page;
		
		if ( !$this->comments = wp_cache_get( 'bp_user_comments_' . $user_id, 'bp' ) ) {
			$this->comments = bp_blogs_get_comments_for_user( $user_id );
			wp_cache_set( 'bp_user_comments_' . $user_id, $this->comments, 'bp' );
		}
		
		if ( !$max || $max >= (int)$this->comments['count'] )
			$this->total_comment_count = (int)$this->comments['count'];
		else
			$this->total_comment_count = (int)$max;
		
		$this->comments = array_slice( (array)$this->comments['comments'], intval( ( $this->pag_page - 1 ) * $this->pag_num), intval( $this->pag_num ) );

		if ( $max ) {
			if ( $max >= count($this->comments) )
				$this->comment_count = count($this->comments);
			else
				$this->comment_count = (int)$max;
		} else {
			$this->comment_count = count($this->comments);
		}
		
		$this->pag_links = paginate_links( array(
			'base' => add_query_arg( 'compage', '%#%' ),
			'format' => '',
			'total' => ceil($this->total_comment_count / $this->pag_num),
			'current' => $this->pag_page,
			'prev_text' => '&laquo;',
			'next_text' => '&raquo;',
			'mid_size' => 1
		));
				
	}
	
	function has_comments() {
		if ( $this->comment_count )
			return true;
		
		return false;
	}
	
	function next_comment() {
		$this->current_comment++;
		$this->comment = $this->comments[$this->current_comment];
		
		return $this->comment;
	}
	
	function rewind_comments() {
		$this->current_comment = -1;
		if ( $this->comment_count > 0 ) {
			$this->comment = $this->comments[0];
		}
	}
	
	function user_comments() { 
		if ( $this->current_comment + 1 < $this->comment_count ) {
			return true;
		} elseif ( $this->current_comment + 1 == $this->comment_count ) {
			do_action('loop_end');
			// Do some cleaning up after the loop
			$this->rewind_comments();
		}

		$this->in_the_loop = false;
		return false;
	}
	
	function the_comment() {
		global $comment;

		$this->in_the_loop = true;
		$comment = $this->next_comment();
		
		if ( 0 == $this->current_comment ) // loop has just started
			do_action('loop_start');
	}
}

function bp_has_comments( $args = '' ) {
	global $comments_template;

	$defaults = array(
		'user_id' => false,
		'per_page' => 10,
		'max' => false
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );
	
	$comments_template = new BP_Blogs_Post_Comment_Template( $user_id, $per_page, $max );
	
	return $comments_template->has_comments();
}

function bp_comments() {
	global $comments_template;
	return $comments_template->user_comments();
}

function bp_the_comment() {
	global $comments_template;
	return $comments_template->the_comment();
}

function bp_comments_pagination() {
	echo bp_get_comments_pagination();
}
	function bp_get_comments_pagination() {
		global $comments_template;
		
		return apply_filters( 'bp_get_comments_pagination', $comments_template->pag_links );
	}

function bp_comment_id() {
	echo bp_get_comment_id();
}
	function bp_get_comment_id() {
		global $comments_template;
		echo apply_filters( 'bp_get_comment_id', $comments_template->comment->comment_ID );
	}

function bp_comment_post_permalink( $depricated = true ) {
	if ( !$depricated )
		return bp_get_comment_post_permalink();
	else
		echo bp_get_comment_post_permalink();
}
	function bp_get_comment_post_permalink() {
		global $comments_template;
		
		return apply_filters( 'bp_get_comment_post_permalink', bp_post_get_permalink( $comments_template->comment->post, $comments_template->comment->blog_id ) . '#comment-' . $comments_template->comment->comment_ID );
	}

function bp_comment_post_title( $deprecated = true ) {
	if ( !$deprecated )
		return bp_get_comment_post_title();
	else
		echo bp_get_comment_post_title();
}
	function bp_get_comment_post_title( $deprecated = true ) {
		global $comments_template;
		
		return apply_filters( 'bp_get_comment_post_title', $comments_template->comment->post->post_title );
	}

function bp_comment_author( $deprecated = true ) {
	global $comments_template;
	
	if ( !$deprecated )
		return bp_get_comment_author();
	else
		echo bp_get_comment_author();
}
	function bp_get_comment_author() {
		global $comments_template;

		return apply_filters( 'bp_get_comment_author', bp_core_get_userlink( $comments_template->comment->user_id ) );
	}

function bp_comment_content() {
	echo bp_get_comment_content();
}
	function bp_get_comment_content() {
		global $comments_template;
		$content = $comments_template->comment->comment_content;
		$content = apply_filters('the_content', $content);
		$content = str_replace(']]>', ']]&gt;', $content);
		echo apply_filters( 'bp_get_comment_content', $content );
	}

function bp_comment_date( $date_format = null, $deprecated = true ) {
	if ( !$date_format )
		$date_format = get_option('date_format');
		
	if ( !$deprecated )
		return bp_get_comment_date( $date_format );
	else 
		echo bp_get_comment_date( $date_format );
}
	function bp_get_comment_date( $date_format = null ) {
		global $comments_template;

		if ( !$date_format )
			$date_format = get_option('date_format');

		return apply_filters( 'bp_get_comment_date', mysql2date( $date_format, $comments_template->comment->comment_date ) );
	}

function bp_comment_blog_permalink( $deprecated = true ) {
	if ( !$deprecated )
		return bp_get_comment_blog_permalink();	
	else
		echo bp_get_comment_blog_permalink();
}
	function bp_get_comment_blog_permalink() {
		global $comments_template;

		return apply_filters( 'bp_get_comment_blog_permalink', get_blog_option( $comments_template->comment->blog_id, 'siteurl' ) );
	}

function bp_comment_blog_name( $deprecated = true ) {
	global $comments_template;
	
	if ( !$deprecated )
		return bp_get_comment_blog_permalink();	
	else
		echo bp_get_comment_blog_permalink();	
}
	function bp_get_comment_blog_name( $deprecated = true ) {
		global $comments_template;

		return apply_filters( 'bp_get_comment_blog_name', get_blog_option( $comments_template->comment->blog_id, 'blogname' ) );
	}

/**********************************************************************
 * Site Wide Blog listing template class
 */

class BP_Blogs_Site_Blogs_Template {
	var $current_blog = -1;
	var $blog_count;
	var $blogs;
	var $blog;
	
	var $in_the_loop;
	
	var $pag_page;
	var $pag_num;
	var $pag_links;
	var $total_blog_count;
	
	function bp_blogs_site_blogs_template( $type, $per_page, $max ) {
		global $bp;

		$this->pag_page = isset( $_REQUEST['bpage'] ) ? intval( $_REQUEST['bpage'] ) : 1;
		$this->pag_num = isset( $_REQUEST['num'] ) ? intval( $_REQUEST['num'] ) : $per_page;
				
		if ( isset( $_REQUEST['s'] ) && '' != $_REQUEST['s'] && $type != 'random' ) {
			$this->blogs = BP_Blogs_Blog::search_blogs( $_REQUEST['s'], $this->pag_num, $this->pag_page );
		} else if ( isset( $_REQUEST['letter'] ) && '' != $_REQUEST['letter'] ) {
			$this->blogs = BP_Blogs_Blog::get_by_letter( $_REQUEST['letter'], $this->pag_num, $this->pag_page );
		} else {
			switch ( $type ) {
				case 'random':
					$this->blogs = BP_Blogs_Blog::get_random( $this->pag_num, $this->pag_page );
					break;
				
				case 'newest':
					$this->blogs = BP_Blogs_Blog::get_newest( $this->pag_num, $this->pag_page );
					break;	
				
				case 'active': default:
					$this->blogs = BP_Blogs_Blog::get_active( $this->pag_num, $this->pag_page );
					break;					
			}
		}
		
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
			'prev_text' => '&laquo;',
			'next_text' => '&raquo;',
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

function bp_rewind_site_blogs() {
	global $site_blogs_template;
	
	$site_blogs_template->rewind_blogs();	
}

function bp_has_site_blogs( $args = '' ) {
	global $site_blogs_template;

	$defaults = array(
		'type' => 'active',
		'per_page' => 10,
		'max' => false
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );
	
	// type: active ( default ) | random | newest | popular
	
	if ( $max ) {
		if ( $per_page > $max )
			$per_page = $max;
	}
		
	$site_blogs_template = new BP_Blogs_Site_Blogs_Template( $type, $per_page, $max );

	return $site_blogs_template->has_blogs();
}

function bp_site_blogs() {
	global $site_blogs_template;
	
	return $site_blogs_template->blogs();
}

function bp_the_site_blog() {
	global $site_blogs_template;
	
	return $site_blogs_template->the_blog();
}

function bp_site_blogs_pagination_count() {
	global $bp, $site_blogs_template;
	
	$from_num = intval( ( $site_blogs_template->pag_page - 1 ) * $site_blogs_template->pag_num ) + 1;
	$to_num = ( $from_num + ( $site_blogs_template->pag_num - 1 ) > $site_blogs_template->total_blog_count ) ? $site_blogs_template->total_blog_count : $from_num + ( $site_blogs_template->pag_num - 1 ) ;

	echo sprintf( __( 'Viewing blog %d to %d (of %d blogs)', 'buddypress' ), $from_num, $to_num, $site_blogs_template->total_blog_count ); ?> &nbsp;
	<span class="ajax-loader"></span><?php
}

function bp_site_blogs_pagination_links() {
	echo bp_get_site_blogs_pagination_links();
}
	function bp_get_site_blogs_pagination_links() {
		global $site_blogs_template;

		return apply_filters( 'bp_get_site_blogs_pagination_links', $site_blogs_template->pag_links );
	}
	
function bp_the_site_blog_avatar() {
	echo bp_get_the_site_blog_avatar();
}
	function bp_get_the_site_blog_avatar() {
		global $site_blogs_template, $bp;
		
		/***
		 * In future BuddyPress versions you will be able to set the avatar for a blog.
		 * Right now you can use a filter with the ID of the blog to change it if you wish.
		 */
		return apply_filters( 'bp_get_blogs_blog_avatar_' . $site_blogs_template->blog->blog_id, '<img src="' . apply_filters( 'bp_gravatar_url', 'http://www.gravatar.com/avatar/' ) . md5( $site_blogs_template->blog->blog_id . '.blogs@' . $bp->root_domain ) . '?d=identicon&amp;s=150" class="avatar blog-avatar" alt="' . __( 'Blog Avatar', 'buddypress' ) . '" />', $site_blogs_template->blog->blog_id );
	}

function bp_the_site_blog_avatar_thumb() {
	echo bp_get_the_site_blog_avatar_thumb();
}
	function bp_get_the_site_blog_avatar_thumb() {
		global $site_blogs_template, $bp;

		return apply_filters( 'bp_get_blogs_blog_avatar_thumb_' . $site_blogs_template->blog->blog_id, '<img src="' . apply_filters( 'bp_gravatar_url', 'http://www.gravatar.com/avatar/' ) . md5( $site_blogs_template->blog->blog_id . '.blogs@' . $bp->root_domain ) . '?d=identicon&amp;s=50" class="avatar blog-avatar thumb" alt="' . __( 'Blog Avatar', 'buddypress' ) . '" />', $site_blogs_template->blog->blog_id );
	}

function bp_the_site_blog_avatar_mini() {
	echo bp_get_the_site_blog_avatar_mini();
}
	function bp_get_the_site_blog_avatar_mini() {
		global $site_blogs_template, $bp;

		return apply_filters( 'bp_get_blogs_blog_avatar_mini_' . $site_blogs_template->blog->blog_id, '<img src="' . apply_filters( 'bp_gravatar_url', 'http://www.gravatar.com/avatar/' ) . md5( $site_blogs_template->blog->blog_id . '.blogs@' . $bp->root_domain ) . '?d=identicon&amp;s=25" class="avatar blog-avatar mini" alt="' . __( 'Blog Avatar', 'buddypress' ) . '" />', $site_blogs_template->blog->blog_id );
	}

function bp_the_site_blog_link() {
	echo bp_get_the_site_blog_link();
}
	function bp_get_the_site_blog_link() {
		global $site_blogs_template;

		return apply_filters( 'bp_get_the_site_blog_link', get_blog_option( $site_blogs_template->blog->blog_id, 'siteurl' ) );
	}

function bp_the_site_blog_name() {
	echo bp_get_the_site_blog_name();
}
	function bp_get_the_site_blog_name() {
		global $site_blogs_template;

		return apply_filters( 'bp_get_the_site_blog_name', get_blog_option( $site_blogs_template->blog->blog_id, 'blogname' ) );
	}

function bp_the_site_blog_description() {
	echo apply_filters( 'bp_the_site_blog_description', bp_get_the_site_blog_description() );
}
	function bp_get_the_site_blog_description() {
		global $site_blogs_template;

		return apply_filters( 'bp_get_the_site_blog_description', get_blog_option( $site_blogs_template->blog->blog_id, 'blogdescription' ) );
	}

function bp_the_site_blog_last_active() {
	echo bp_get_the_site_blog_last_active();
}
	function bp_get_the_site_blog_last_active() {
		global $site_blogs_template;

		return apply_filters( 'bp_the_site_blog_last_active', bp_core_get_last_activity( bp_blogs_get_blogmeta( $site_blogs_template->blog->blog_id, 'last_activity' ), __( 'active %s ago', 'buddypress' ) ) );
	}

function bp_the_site_blog_latest_post() {
	echo bp_get_the_site_blog_latest_post();
}
	function bp_get_the_site_blog_latest_post() {
		global $site_blogs_template;

		if ( $post = bp_blogs_get_latest_posts( $site_blogs_template->blog->blog_id, 1 ) ) {
			return apply_filters( 'bp_get_the_site_blog_latest_post', sprintf( __( 'Latest Post: %s', 'buddypress' ), '<a href="' . bp_post_get_permalink( $post[0], $site_blogs_template->blog->blog_id ) . '">' . apply_filters( 'the_title', $post[0]->post_title ) . '</a>' ) );
		}
	}

function bp_the_site_blog_hidden_fields() {
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

function bp_directory_blogs_search_form() {
	global $bp; ?>
	<form action="" method="get" id="search-blogs-form">
		<label><input type="text" name="s" id="blogs_search" value="<?php if ( isset( $_GET['s'] ) ) { echo $_GET['s']; } else { _e( 'Search anything...', 'buddypress' ); } ?>"  onfocus="if (this.value == '<?php _e( 'Search anything...', 'buddypress' ) ?>') {this.value = '';}" onblur="if (this.value == '') {this.value = '<?php _e( 'Search anything...', 'buddypress' ) ?>';}" /></label>
		<input type="submit" id="blogs_search_submit" name="blogs_search_submit" value="<?php _e( 'Search', 'buddypress' ) ?>" />
	</form>
<?php
}


?>