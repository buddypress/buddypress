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
	
	var $table_name;
	
	function bp_wire_posts_template( $item_id ) {
		global $bp;
		
		if ( $bp['current_component'] == $bp['wire']['slug'] ) {
			$this->table_name = $bp['profile']['table_name_wire'];
		} else {
			$this->table_name = $bp[$bp['current_component']]['table_name_wire'];
		}
		
		$this->pag_page = isset( $_GET['fpage'] ) ? intval( $_GET['fpage'] ) : 1;
		$this->pag_num = isset( $_GET['num'] ) ? intval( $_GET['num'] ) : 5;
		
		$this->wire_posts = BP_Wire_Post::get_all_for_item( $item_id, $this->table_name, $this->pag_page, $this->pag_num );
		$this->total_wire_post_count = (int)$this->wire_posts['count'];
		
		$this->wire_posts = $this->wire_posts['wire_posts'];
		$this->wire_post_count = count($this->wire_posts);
		
		$this->pag_links = paginate_links( array(
			'base' => add_query_arg( 'fpage', '%#%' ),
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

function bp_has_wire_posts( $item_id = null ) {
	global $wire_posts_template, $bp;
	
	if ( !$item_id )
		return false;
		
	$wire_posts_template = new BP_Wire_Posts_Template( $item_id );		
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

function bp_wire_get_post_list( $bp_wire_item_id = null, $bp_wire_title = null, $bp_wire_empty_message = null ) {
	global $bp_item_id, $bp_wire_header, $bp_wire_msg;

	if ( !$bp_wire_item_id )
		return false;
	
	if ( !$bp_wire_empty_message )
		$bp_wire_empty_message = __("There are currently no wire posts.");
	
	if ( !$bp_wire_title )
		$bp_wire_title = __('Wire');
	
	/* Pass them as globals, using the same name doesn't work. */
	$bp_item_id = $bp_wire_item_id;
	$bp_wire_header = $bp_wire_title;
	$bp_wire_msg = $bp_wire_empty_message;
	
	load_template( TEMPLATEPATH . '/wire/post-list.php' );
}

function bp_wire_title() {
	global $bp_wire_header;
	echo $bp_wire_header;
}

function bp_wire_item_id() {
	global $bp_item_id;
	return $bp_item_id;
}

function bp_wire_no_posts_message() {
	global $bp_wire_msg;
	echo $bp_wire_msg;
}

function bp_wire_post_id( $echo = true ) {
	global $wire_posts_template;
	
	if ( $echo )
		echo $wire_posts_template->wire_post->id;
	else
		return $wire_posts_template->wire_post->id;
}

function bp_wire_post_content() {
	global $wire_posts_template;
	
	$content = $wire_posts_template->wire_post->content;
	$content = apply_filters('the_content', $content);
	$content = str_replace(']]>', ']]&gt;', $content);
	$content = stripslashes( $content );
	
	echo $content;
}

function bp_wire_post_date( $date_format = null ) {
	global $wire_posts_template;

	if ( !$date_format )
		$date_format = get_option('date_format');
		
	echo mysql2date( $date_format, $wire_posts_template->wire_post->date_posted );	
}

function bp_wire_post_author_name() {
	global $wire_posts_template;
	
	echo bp_core_get_userlink( $wire_posts_template->wire_post->user_id );
}

function bp_wire_post_author_avatar() {
	global $wire_posts_template;
	
	echo bp_core_get_avatar( $wire_posts_template->wire_post->user_id, 1 );
}

function bp_wire_get_post_form() {
	if ( is_user_logged_in() )
		load_template( TEMPLATEPATH . '/wire/post-form.php' );		
}

function bp_wire_get_action() {
	global $bp;
	
	if ( $bp['current_component'] == 'wire' || $bp['current_component'] == 'profile' ) {
		echo $bp['current_domain'] . $bp['wire']['slug'] . '/post/';
	} else {
		echo $bp['current_domain'] . $bp[$bp['current_component']]['slug'] . '/' . $bp['current_action'] . '/wire/post/';
	}
}

function bp_wire_poster_avatar() {
	global $bp;
	
	echo bp_core_get_avatar( $bp['loggedin_userid'], 1 );
}

function bp_wire_poster_name() {
	global $bp;
	
	echo '<a href="' . $bp['loggedin_domain'] . $bp['profile']['slug'] . '">' . __('You') . '</a>';
}

function bp_wire_poster_date( $date_format = null ) {
	if ( !$date_format )
		$date_format = get_option('date_format');

	echo mysql2date( $date_format, date("Y-m-d H:i:s") );	
}

function bp_wire_delete_link() {
	global $wire_posts_template, $bp, $is_item_admin;
	
	if ( $wire_posts_template->wire_post->user_id == $bp['loggedin_userid'] || $is_item_admin ) {
		if ( $bp['current_component'] == 'wire' || $bp['current_component'] == 'profile' ) {
			echo '<a href="' . $bp['current_domain'] . $bp['wire']['slug'] . '/delete/' . $wire_posts_template->wire_post->id . '">[' . __('Delete') . ']</a>';
		} else {
			echo '<a href="' . $bp['current_domain'] . $bp[$bp['current_component']]['slug'] . '/' . $bp['current_action'] . '/wire/delete/' . $wire_posts_template->wire_post->id . '">[' . __('Delete') . ']</a>';
		}
	}
}

function bp_wire_see_all_link() {
	global $bp;
	
	if ( $bp['current_component'] == 'wire' || $bp['current_component'] == 'profile') {
		echo $bp['current_domain'] . $bp['wire']['slug'];
	} else if ( $bp['current_component'] == 'groups' ) {
		echo $bp['current_domain'] . $bp['groups']['slug'] . '/' . $bp['current_action'] . '/wire';
	} else {
		echo $bp['current_domain'] . $bp[$bp['current_component']]['slug'] . '/wire';
	}
	
}

?>