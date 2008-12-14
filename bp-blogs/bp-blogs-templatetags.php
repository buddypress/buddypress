<?php

/** Blog listing template class **/
class BP_Blogs_Blog_Template {
	var $current_blog = -1;
	var $blog_count;
	var $blogs;
	var $blog;
	
	var $in_the_loop;
	
	var $pag_page;
	var $pag_num;
	var $pag_links;
	var $total_blog_count;
	
	function bp_blogs_blog_template( $user_id = null ) {
		global $bp;
		
		if ( !$user_id )
			$user_id = $bp['current_userid'];

		$this->pag_page = isset( $_GET['fpage'] ) ? intval( $_GET['fpage'] ) : 1;
		$this->pag_num = isset( $_GET['num'] ) ? intval( $_GET['num'] ) : 5;

		$this->blogs = bp_blogs_get_blogs_for_user( $user_id );
		$this->total_blog_count = (int)$this->blogs['count'];
		$this->blogs = $this->blogs['blogs'];
		$this->blog_count = count($this->blogs);
		
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
		
		if ( $this->current_blog == 0 ) // loop has just started
			do_action('loop_start');
	}
}

function bp_has_blogs() {
	global $blogs_template;

	$blogs_template = new BP_Blogs_Blog_Template;
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

function bp_blog_title() {
	global $blogs_template;	
	echo apply_filters( 'bp_blog_title', $blogs_template->blog['title'] );
}

function bp_blog_description() {
	global $blogs_template;
	echo apply_filters( 'bp_blog_description', $blogs_template->blog['description'] );
}

function bp_blog_permalink() {
	global $blogs_template;	
	echo apply_filters( 'bp_blog_permalink', $blogs_template->blog['siteurl'] );
}


/** User Blog posts listing template class **/

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
	
	function bp_blogs_blog_post_template( $user_id = null ) {
		global $bp;
		
		if ( !$user_id )
			$user_id = $bp['current_userid'];

		$this->pag_page = isset( $_GET['fpage'] ) ? intval( $_GET['fpage'] ) : 1;
		$this->pag_num = isset( $_GET['num'] ) ? intval( $_GET['num'] ) : 5;

		$this->posts = bp_blogs_get_posts_for_user( $user_id );
		$this->total_post_count = (int)$this->posts['count'];
		$this->posts = $this->posts['posts'];
		$this->post_count = count($this->posts);

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
		
		if ( $this->current_post == 0 ) // loop has just started
			do_action('loop_start');
	}
}

function bp_has_posts() {
	global $posts_template;

	$posts_template = new BP_Blogs_Blog_Post_Template;	
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

function bp_post_title( $echo = true ) {
	global $posts_template;
	
	if ( $echo )
		echo apply_filters( 'bp_post_title', $posts_template->post->post_title );
	else
		return apply_filters( 'bp_post_title', $posts_template->post->post_title );
}

function bp_post_permalink() {
	global $posts_template;
	echo apply_filters( 'bp_post_permalink', bp_post_get_permalink() );	
}

function bp_post_excerpt() {
	global $posts_template;
	echo apply_filters( 'bp_post_excerpt', $posts_template->post->post_excerpt );	
}

function bp_post_content() {
	global $posts_template;
	$content = $posts_template->post->post_content;
	$content = apply_filters('the_content', $content);
	$content = str_replace(']]>', ']]&gt;', $content);
	echo apply_filters( 'bp_post_content', $content );
}

function bp_post_status() {
	global $posts_template;
	echo apply_filters( 'bp_post_status', $posts_template->post->post_status );	
}

function bp_post_date( $date_format = null, $echo = true ) {
	global $posts_template;
	
	if ( !$date_format )
		$date_format = get_option('date_format');
		
	if ( $echo )
		echo apply_filters( 'bp_post_date', mysql2date( $date_format, $posts_template->post->post_date ) );
	else
		return apply_filters( 'bp_post_date', mysql2date( $date_format, $posts_template->post->post_date ) );
}

function bp_post_comment_count() {
	global $posts_template;
	echo apply_filters( 'bp_post_comment_count', $posts_template->post->comment_count );	
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

function bp_post_author( $echo = true ) {
	global $posts_template;
	
	if ( $echo )
		echo apply_filters( 'bp_post_author', bp_core_get_userlink( $posts_template->post->post_author ) );
	else
		return apply_filters( 'bp_post_author', bp_core_get_userlink( $posts_template->post->post_author ) );
}

function bp_post_id() {
	global $posts_template;
	echo apply_filters( 'bp_post_id', $posts_template->post->ID );	
}

function bp_post_category( $separator = '', $parents='', $post_id = false, $echo = true ) {
	global $posts_template;
	
	if ( $echo )
		echo apply_filters( 'bp_post_category', get_the_category_list( $separator, $parents, $posts_template->post->ID ) );
	else
		return apply_filters( 'bp_post_category', get_the_category_list( $separator, $parents, $posts_template->post->ID ) );
}

function bp_post_tags( $before = '', $sep = ', ', $after = '' ) {
	global $posts_template, $wpdb;
	
	switch_to_blog( $posts_template->post->blog_id );
	$terms = bp_post_get_term_list( $before, $sep, $after );
}

function bp_post_blog_id() {
	global $posts_template;
	echo apply_filters( 'bp_post_blog_id', $posts_template->post->blog_id );
}

function bp_post_blog_title() {
	global $posts_template;
	echo apply_filters( 'bp_post_blog_title', $posts_template->post->blog_id );	
}

function bp_post_blog_description() {
	global $posts_template;
	echo apply_filters( 'bp_post_blog_description', $posts_template->post->blog_id );	
}

function bp_post_blog_permalink() {
	global $posts_template;
	echo apply_filters( 'bp_post_blog_permalink', $posts_template->post->blog_id );	
}

function bp_post_get_permalink( $post = null, $blog_id = null ) {
	global $current_blog, $posts_template;

	if ( !$post )
		$post = $posts_template->post;	
		
	if ( !$blog_id )
		$blog_id = $posts_template->post->blog_id;
		
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

	if ( $post->post_type == 'page' )
		return get_page_link($post->ID, $leavename);
	elseif ($post->post_type == 'attachment')
		return get_attachment_link($post->ID);

	$permalink = get_blog_option( $blog_id, 'permalink_structure' );
	$site_url = get_blog_option( $blog_id, 'siteurl' ); 

	if ( '' != $permalink && !in_array($post->post_status, array('draft', 'pending')) ) {
		$unixtime = strtotime($post->post_date);

		$category = '';
		if ( strpos($permalink, '%category%') !== false ) {
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
		if ( strpos($permalink, '%author%') !== false ) {
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
		$link = get_blog_option( 1, 'siteurl') . '/tag/' . $term->slug;
		$link = apply_filters('term_link', $link);
		
		$term_links[] = '<a href="' . $link . '" rel="tag">' . $term->name . '</a>';
	}

	$term_links = apply_filters( "term_links-$taxonomy", $term_links );

	echo $before . join($sep, $term_links) . $after;
}


/** User Blog comments listing template class **/

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
	
	function bp_blogs_post_comment_template( $user_id = null ) {
		global $bp;
		
		if ( !$user_id )
			$user_id = $bp['current_userid'];

		$this->pag_page = isset( $_GET['fpage'] ) ? intval( $_GET['fpage'] ) : 1;
		$this->pag_num = isset( $_GET['num'] ) ? intval( $_GET['num'] ) : 5;

		$this->comments = bp_blogs_get_comments_for_user( $user_id );
		$this->total_comment_count = (int)$this->comments['count'];
		$this->comments = $this->comments['comments'];
		$this->comment_count = count($this->comments);

		$this->pag_links = paginate_links( array(
			'base' => add_query_arg( 'fpage', '%#%' ),
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
		
		if ( $this->current_comment == 0 ) // loop has just started
			do_action('loop_start');
	}
}

function bp_has_comments() {
	global $comments_template;

	$comments_template = new BP_Blogs_Post_Comment_Template;
	
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

function bp_comment_id() {
	global $comments_template;
	echo apply_filters( 'bp_comment_id', $comments_template->comment->comment_ID );
}

function bp_comment_post_permalink( $echo = true ) {
	global $comments_template;
	
	if ( $echo )
		echo apply_filters( 'bp_comment_post_permalink', bp_post_get_permalink( $comments_template->comment->post, $comments_template->comment->blog_id ) . '#comment-' . $comments_template->comment->comment_ID );
	else
		return apply_filters( 'bp_comment_post_permalink', bp_post_get_permalink( $comments_template->comment->post, $comments_template->comment->blog_id ) . '#comment-' . $comments_template->comment->comment_ID );
}

function bp_comment_post_title( $echo = true ) {
	global $comments_template;
	
	if ( $echo )
		echo apply_filters( 'bp_comment_post_title', $comments_template->comment->post->post_title );
	else
		return apply_filters( 'bp_comment_post_title', $comments_template->comment->post->post_title );
}

function bp_comment_author( $echo = true ) {
	global $comments_template;
	
	if ( $echo )
		echo apply_filters( 'bp_comment_author', bp_core_get_userlink( $comments_template->comment->user_id ) );
	else
		return apply_filters( 'bp_comment_author', bp_core_get_userlink( $comments_template->comment->user_id ) );
}

function bp_comment_content() {
	global $comments_template;
	$content = $comments_template->comment->comment_content;
	$content = apply_filters('the_content', $content);
	$content = str_replace(']]>', ']]&gt;', $content);
	echo apply_filters( 'bp_comment_content', $content );
}

function bp_comment_date( $date_format = null, $echo = true ) {
	global $comments_template;
	
	if ( !$date_format )
		$date_format = get_option('date_format');
		
	if ( $echo == true )
		echo apply_filters( 'bp_comment_date', mysql2date( $date_format, $comments_template->comment->post->post_date ) );
	else 
		return apply_filters( 'bp_comment_date', mysql2date( $date_format, $comments_template->comment->post->post_date ) );
}

function bp_comment_blog_permalink( $echo = true ) {
	global $comments_template;
	
	if ( $echo )
		echo apply_filters( 'bp_comment_blog_permalink', get_blog_option( $comments_template->comment->blog_id, 'siteurl' ) );
	else
		return apply_filters( 'bp_comment_blog_permalink', get_blog_option( $comments_template->comment->blog_id, 'siteurl' ) );
}

function bp_comment_blog_name( $echo = true ) {
	global $comments_template;
	
	if ( $echo )
		echo apply_filters( 'bp_comment_blog_name', get_blog_option( $comments_template->comment->blog_id, 'blogname' ) );
	else
		return apply_filters( 'bp_comment_blog_name', get_blog_option( $comments_template->comment->blog_id, 'blogname' ) );
}


/* Blog registration template tags */

function bp_blog_signup_enabled() {
	$active_signup = get_site_option( 'registration' );
	
	if ( !$active_signup )
		$active_signup = 'all';
		
	$active_signup = apply_filters( 'wpmu_active_signup', $active_signup ); // return "all", "none", "blog" or "user"
	
	if ( $active_signup == 'none' || $active_signup == 'user' )
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

		<form id="setupform" method="post" action="<?php echo $bp['loggedin_domain'] . $bp['blogs']['slug'] . '/create-a-blog' ?>">

			<input type="hidden" name="stage" value="gimmeanotherblog" />
			<?php do_action( "signup_hidden_fields" ); ?>
			<?php bp_blogs_signup_blog($blogname, $blog_title, $errors); ?>
			<p>
				<input id="submit" type="submit" name="submit" class="submit" value="<?php _e('Create Blog &raquo;', 'buddypress') ?>"/>
			</p>
		</form>
		<?php
	}
}

function bp_blogs_signup_blog( $blogname = '', $blog_title = '', $errors = '' ) {
	global $current_site;
	
	// Blog name
	if( constant( "VHOST" ) == 'no' )
		echo '<label for="blogname">' . __('Blog Name:', 'buddypress') . '</label>';
	else
		echo '<label for="blogname">' . __('Blog Domain:', 'buddypress') . '</label>';

	if ( $errmsg = $errors->get_error_message('blogname') ) { ?>
		<p class="error"><?php echo $errmsg ?></p>
	<?php }

	if( constant( "VHOST" ) == 'no' ) {
		echo '<span class="prefix_address">' . $current_site->domain . $current_site->path . '</span><input name="blogname" type="text" id="blogname" value="'.$blogname.'" maxlength="50" /><br />';
	} else {
		echo '<input name="blogname" type="text" id="blogname" value="'.$blogname.'" maxlength="50" /><span class="suffix_address">.' . $current_site->domain . $current_site->path . '</span><br />';
	}
	if ( !is_user_logged_in() ) {
		print '(<strong>' . __( 'Your address will be ' , 'buddypress');
		if( constant( "VHOST" ) == 'no' ) {
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
		<div style="clear:both;"></div>
		<label class="checkbox" for="blog_public_on">
			<input type="radio" id="blog_public_on" name="blog_public" value="1" <?php if( !isset( $_POST['blog_public'] ) || $_POST['blog_public'] == '1' ) { ?>checked="checked"<?php } ?> />
			<strong><?php _e( 'Yes' , 'buddypress'); ?></strong>
		</label>
		<label class="checkbox" for="blog_public_off">
			<input type="radio" id="blog_public_off" name="blog_public" value="0" <?php if( isset( $_POST['blog_public'] ) && $_POST['blog_public'] == '0' ) { ?>checked="checked"<?php } ?> />
			<strong><?php _e( 'No' , 'buddypress'); ?></strong>
		</label>
	</p>

	<?php
	do_action('signup_blogform', $errors);
}

function bp_blogs_validate_blog_signup() {
	global $wpdb, $current_user, $blogname, $blog_title, $errors, $domain, $path;

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
		echo apply_filters( 'bp_create_blog_link', '<a href="' . $bp['loggedin_domain'] . $bp['blogs']['slug'] . '/create-a-blog">' . __('Create a Blog', 'buddypress') . '</a>' );
	}
}

function bp_blogs_blog_tabs() {
	global $bp, $groups_template;
	
	// Don't show these tabs on a user's own profile
	if ( bp_is_home() )
		return false;
	
	$current_tab = $bp['current_action'];
?>
	<ul class="content-header-nav">
		<li<?php if ( $current_tab == 'my-blogs' || $current_tab == '' ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp['current_domain'] . $bp['blogs']['slug'] ?>/my-blogs"><?php printf( __( "%s's Blogs", 'buddypress' ), $bp['current_fullname'] )  ?></a></li>
		<li<?php if ( $current_tab == 'recent-posts' ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp['current_domain'] . $bp['blogs']['slug'] ?>/recent-posts"><?php printf( __( "%s's Recent Posts", 'buddypress' ), $bp['current_fullname'] )  ?></a></li>
		<li<?php if ( $current_tab == 'recent-comments' ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp['current_domain'] . $bp['blogs']['slug'] ?>/recent-comments"><?php printf( __( "%s's Recent Comments", 'buddypress' ), $bp['current_fullname'] )  ?></a></li>	
	</ul>
<?php
	do_action( 'bp_blogs_blog_tabs', $current_tab );
}

?>