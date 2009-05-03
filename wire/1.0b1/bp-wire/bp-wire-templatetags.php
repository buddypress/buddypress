<?php

class BP_Wire_Posts_Template {
	var $current_wire_post = -1;
	var $wire_post_count;
	var $wire_posts;
	var $wire_post;
	
	var $in_the_loop;
	
	var $pag_page;
	var $pag_num;
	var $pag_links;
	var $total_wire_post_count;
	
	var $can_post;
	
	var $table_name;
	
	function bp_wire_posts_template( $item_id, $can_post ) {
		global $bp;
		
		if ( $bp['current_component'] == $bp['wire']['slug'] ) {
			$this->table_name = $bp['profile']['table_name_wire'];
			
			// Seeing as we're viewing a users wire, lets remove any new wire
			// post notifications
			if ( $bp['current_action'] == 'all-posts' )
				bp_core_delete_notifications_for_user_by_type( $bp['loggedin_userid'], 'xprofile', 'new_wire_post' );
			
		} else {
			$this->table_name = $bp[$bp['current_component']]['table_name_wire'];
		}
		
		$this->pag_page = isset( $_REQUEST['wpage'] ) ? intval( $_REQUEST['wpage'] ) : 1;
		$this->pag_num = isset( $_REQUEST['num'] ) ? intval( $_REQUEST['num'] ) : 5;

		$this->wire_posts = BP_Wire_Post::get_all_for_item( $item_id, $this->table_name, $this->pag_page, $this->pag_num );
		$this->total_wire_post_count = (int)$this->wire_posts['count'];
		
		$this->wire_posts = $this->wire_posts['wire_posts'];
		$this->wire_post_count = count($this->wire_posts);
		
		if ( (int)get_site_option('non-friend-wire-posting') && ( $bp['current_component'] == $bp['profile']['slug'] || $bp['current_component'] == $bp['wire']['slug'] ) )
			$this->can_post = 1;
		else
			$this->can_post = $can_post;
		
		$this->pag_links = paginate_links( array(
			'base' => add_query_arg( 'wpage', '%#%', $bp['current_domain'] ),
			'format' => '',
			'total' => ceil($this->total_wire_post_count / $this->pag_num),
			'current' => $this->pag_page,
			'prev_text' => '&laquo;',
			'next_text' => '&raquo;',
			'mid_size' => 1
		));
		
	}
	
	function has_wire_posts() {
		if ( $this->wire_post_count )
			return true;
		
		return false;
	}
	
	function next_wire_post() {
		$this->current_wire_post++;
		$this->wire_post = $this->wire_posts[$this->current_wire_post];
		
		return $this->wire_post;
	}
	
	function rewind_wire_posts() {
		$this->current_wire_post = -1;
		if ( $this->wire_post_count > 0 ) {
			$this->wire_post = $this->wire_posts[0];
		}
	}
	
	function user_wire_posts() { 
		if ( $this->current_wire_post + 1 < $this->wire_post_count ) {
			return true;
		} elseif ( $this->current_wire_post + 1 == $this->wire_post_count ) {
			do_action('loop_end');
			// Do some cleaning up after the loop
			$this->rewind_wire_posts();
		}

		$this->in_the_loop = false;
		return false;
	}
	
	function the_wire_post() {
		global $wire_post;

		$this->in_the_loop = true;
		$this->wire_post = $this->next_wire_post();

		if ( $this->current_wire_post == 0 ) // loop has just started
			do_action('loop_start');
	}
}

function bp_has_wire_posts( $item_id = null, $can_post = true ) {
	global $wire_posts_template, $bp;
	
	if ( !$item_id )
		return false;
		
	$wire_posts_template = new BP_Wire_Posts_Template( $item_id, $can_post );		
	return $wire_posts_template->has_wire_posts();
}

function bp_wire_posts() {
	global $wire_posts_template;
	return $wire_posts_template->user_wire_posts();
}

function bp_the_wire_post() {
	global $wire_posts_template;
	return $wire_posts_template->the_wire_post();
}

function bp_wire_get_post_list( $item_id = null, $title = null, $empty_message = null, $can_post = true, $show_email_notify = false ) {
	global $bp_item_id, $bp_wire_header, $bp_wire_msg, $bp_wire_can_post, $bp_wire_show_email_notify;

	if ( !$item_id )
		return false;
	
	if ( !$message )
		$empty_message = __("There are currently no wire posts.", 'buddypress');
	
	if ( !$title )
		$title = __('Wire', 'buddypress');

	/* Pass them as globals, using the same name doesn't work. */
	$bp_item_id = $item_id;
	$bp_wire_header = $title;
	$bp_wire_msg = $empty_message;
	$bp_wire_can_post = $can_post;
	$bp_wire_show_email_notify = $show_email_notify;
	
	load_template( TEMPLATEPATH . '/wire/post-list.php' );
}

function bp_wire_title() {
	global $bp_wire_header;
	echo apply_filters( 'bp_group_reject_invite_link', $bp_wire_header );
}

function bp_wire_item_id( $echo = false ) {
	global $bp_item_id;
	
	if ( $echo )
		echo apply_filters( 'bp_wire_item_id', $bp_item_id );
	else
		return apply_filters( 'bp_wire_item_id', $bp_item_id );
}

function bp_wire_no_posts_message() {
	global $bp_wire_msg;
	echo apply_filters( 'bp_wire_no_posts_message', $bp_wire_msg );
}

function bp_wire_can_post() {
	global $bp_wire_can_post;
	return apply_filters( 'bp_wire_can_post', $bp_wire_can_post );
}

function bp_wire_show_email_notify() {
	global $bp_wire_show_email_notify;
	return apply_filters( 'bp_wire_show_email_notify', $bp_wire_show_email_notify );
}

function bp_wire_post_id( $echo = true ) {
	global $wire_posts_template;
	
	if ( $echo )
		echo apply_filters( 'bp_wire_post_id', $wire_posts_template->wire_post->id );
	else
		return apply_filters( 'bp_wire_post_id', $wire_posts_template->wire_post->id );
}

function bp_wire_post_content() {
	global $wire_posts_template;

	echo apply_filters( 'bp_wire_post_content', $wire_posts_template->wire_post->content );
}

function bp_wire_needs_pagination() {
	global $wire_posts_template;

	if ( $wire_posts_template->total_wire_post_count > $wire_posts_template->pag_num )
		return true;
	
	return false;
}

function bp_wire_pagination() {
	global $wire_posts_template;
	echo $wire_posts_template->pag_links;
	wp_nonce_field( 'get_wire_posts' );
}

function bp_wire_pagination_count() {
	global $wire_posts_template;
	
	$from_num = intval( ( $wire_posts_template->pag_page - 1 ) * $wire_posts_template->pag_num ) + 1;
	$to_num = ( $from_num + 4 > $wire_posts_template->total_wire_post_count ) ? $wire_posts_template->total_wire_post_count : $from_num + 4; 
	
	echo apply_filters( 'bp_wire_pagination_count', sprintf( __( 'Viewing post %d to %d (%d total posts)', 'buddypress' ), $from_num, $to_num, $wire_posts_template->total_wire_post_count ) );  
}

function bp_wire_ajax_loader_src() {
	global $bp;
	
	echo apply_filters( 'bp_wire_ajax_loader_src', $bp['wire']['image_base'] . '/ajax-loader.gif' );
}

function bp_wire_post_date( $date_format = null, $echo = true ) {
	global $wire_posts_template;

	if ( !$date_format )
		$date_format = get_option('date_format');
		
	if ( $echo )
		echo apply_filters( 'bp_wire_post_date', mysql2date( $date_format, $wire_posts_template->wire_post->date_posted ) );
	else
		return apply_filters( 'bp_wire_post_date', mysql2date( $date_format, $wire_posts_template->wire_post->date_posted ) );
}

function bp_wire_post_author_name( $echo = true ) {
	global $wire_posts_template;
	
	if ( $echo )
		echo apply_filters( 'bp_wire_post_author_name', bp_core_get_userlink( $wire_posts_template->wire_post->user_id ) );
	else
		return apply_filters( 'bp_wire_post_author_name', bp_core_get_userlink( $wire_posts_template->wire_post->user_id ) );
}

function bp_wire_post_author_avatar() {
	global $wire_posts_template;
	
	echo apply_filters( 'bp_wire_post_author_avatar', bp_core_get_avatar( $wire_posts_template->wire_post->user_id, 1 ) );
}

function bp_wire_get_post_form() {
	global $wire_posts_template;
	
	if ( is_user_logged_in() && $wire_posts_template->can_post )
		load_template( TEMPLATEPATH . '/wire/post-form.php' );		
}

function bp_wire_get_action() {
	global $bp;
	
	if ( $bp['current_item'] == '')
		$uri = $bp['current_action'];
	else
		$uri = $bp['current_item'];
	
	if ( $bp['current_component'] == 'wire' || $bp['current_component'] == 'profile' ) {
		echo apply_filters( 'bp_wire_get_action', $bp['current_domain'] . $bp['wire']['slug'] . '/post/' );
	} else {
		echo apply_filters( 'bp_wire_get_action', site_url() . '/' . $bp[$bp['current_component']]['slug'] . '/' . $uri . '/wire/post/' );
	}
}

function bp_wire_poster_avatar() {
	global $bp;
	
	echo apply_filters( 'bp_wire_poster_avatar', bp_core_get_avatar( $bp['loggedin_userid'], 1 ) );
}

function bp_wire_poster_name( $echo = true ) {
	global $bp;
	
	if ( $echo )
		echo apply_filters( 'bp_wire_poster_name', '<a href="' . $bp['loggedin_domain'] . $bp['profile']['slug'] . '">' . __('You', 'buddypress') . '</a>' );
	else
		return apply_filters( 'bp_wire_poster_name', '<a href="' . $bp['loggedin_domain'] . $bp['profile']['slug'] . '">' . __('You', 'buddypress') . '</a>' );
}

function bp_wire_poster_date( $date_format = null, $echo = true ) {
	if ( !$date_format )
		$date_format = get_option('date_format');

	if ( $echo )
		echo apply_filters( 'bp_wire_poster_date', mysql2date( $date_format, date("Y-m-d H:i:s") ) );
	else
		return apply_filters( 'bp_wire_poster_date', mysql2date( $date_format, date("Y-m-d H:i:s") ) );	
}

function bp_wire_delete_link() {
	global $wire_posts_template, $bp;

	if ( $bp['current_item'] == '')
		$uri = $bp['current_action'];
	else
		$uri = $bp['current_item'];
		
	if ( ( $wire_posts_template->wire_post->user_id == $bp['loggedin_userid'] ) || $bp['is_item_admin'] ) {
		if ( $bp['current_component'] == 'wire' || $bp['current_component'] == 'profile' ) {
			echo apply_filters( 'bp_wire_delete_link', '<a href="' . $bp['current_domain'] . $bp['wire']['slug'] . '/delete/' . $wire_posts_template->wire_post->id . '">[' . __('Delete', 'buddypress') . ']</a>' );
		} else {
			echo apply_filters( 'bp_wire_delete_link', '<a href="' . site_url() . '/' . $bp[$bp['current_component']]['slug'] . '/' . $uri . '/wire/delete/' . $wire_posts_template->wire_post->id . '">[' . __('Delete', 'buddypress') . ']</a>' );
		}
	}
}

function bp_wire_see_all_link() {
	global $bp;
	
	if ( $bp['current_item'] == '')
		$uri = $bp['current_action'];
	else
		$uri = $bp['current_item'];
	
	if ( $bp['current_component'] == 'wire' || $bp['current_component'] == 'profile') {
		echo apply_filters( 'bp_wire_see_all_link', $bp['current_domain'] . $bp['wire']['slug'] );
	} else {
		echo apply_filters( 'bp_wire_see_all_link', $bp['root_domain'] . '/' . $bp['groups']['slug'] . '/' . $uri . '/wire' );
	}
}


?>