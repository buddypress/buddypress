<?php

class BP_Forums_Template_Forum {
	var $current_topic = -1;
	var $topic_count;
	var $topics;
	var $topic;
	
	var $in_the_loop;
	
	var $pag_page;
	var $pag_num;
	var $pag_links;
	var $total_topic_count;
	
	var $single_topic = false;
	
	var $sort_by;
	var $order;
	
	function BP_Forums_Template_Forum( $forum_id, $topics_per_page = 10 ) {
		global $bp, $current_user;
		
		$this->pag_page = isset( $_REQUEST['fpage'] ) ? intval( $_REQUEST['fpage'] ) : 1;
		$this->pag_num = isset( $_REQUEST['num'] ) ? intval( $_REQUEST['num'] ) : $topics_per_page;

		$this->topics = bp_forums_get_topics( $forum_id, $this->pag_num, $this->pag_page );
		
		if ( !$this->topics ) {
			$this->topic_count = 0;
			$this->total_topic_count = 0;
		} else {
			$this->topic_count = count( $this->topics );
			$this->total_topic_count = count( bp_forums_get_topics( $forum_id ) );			
		}

		$this->pag_links = paginate_links( array(
			'base' => add_query_arg( array( 'fpage' => '%#%', 'num' => $this->pag_num ) ),
			'format' => '',
			'total' => ceil($this->total_topic_count / $this->pag_num),
			'current' => $this->pag_page,
			'prev_text' => '&laquo;',
			'next_text' => '&raquo;',
			'mid_size' => 1
		));
	}
	
	function has_topics() {
		if ( $this->topic_count )
			return true;
		
		return false;
	}
	
	function next_topic() {
		$this->current_topic++;
		$this->topic = $this->topics[$this->current_topic];
			
		return $this->topic;
	}
	
	function rewind_topics() {
		$this->current_topic = -1;
		if ( $this->topic_count > 0 ) {
			$this->topic = $this->topics[0];
		}
	}
	
	function user_topics() { 
		if ( $this->current_topic + 1 < $this->topic_count ) {
			return true;
		} elseif ( $this->current_topic + 1 == $this->topic_count ) {
			do_action('loop_end');
			// Do some cleaning up after the loop
			$this->rewind_topics();
		}

		$this->in_the_loop = false;
		return false;
	}
	
	function the_topic() {
		global $topic;

		$this->in_the_loop = true;
		$this->topic = $this->next_topic();
		$this->topic = (object)$this->topic;
		
		if ( $this->current_topic == 0 ) // loop has just started
			do_action('loop_start');
	}
}

function bp_has_topics( $topics_per_page = 10, $forum_id = false ) {
	global $forum_template, $bp;
	global $group_obj;
	
	if ( !$forum_id )
		$forum_id = groups_get_groupmeta( $group_obj->id, 'forum_id' );

	if ( is_numeric( $forum_id ) )
		$forum_template = new BP_Forums_Template_Forum( $forum_id );
	else
		return false;

	return $forum_template->has_topics();
}

function bp_topics() {
	global $forum_template;
	return $forum_template->user_topics();
}

function bp_the_topic() {
	global $forum_template;
	return $forum_template->the_topic();
}

function bp_the_topic_id() {
	echo bp_get_the_topic_id();
}
	function bp_get_the_topic_id() {
		global $forum_template;

		return apply_filters( 'bp_get_the_topic_id', $forum_template->topic->topic_id );
	}
	
function bp_the_topic_title() {
	echo bp_get_the_topic_title();
}
	function bp_get_the_topic_title() {
		global $forum_template;

		return apply_filters( 'bp_get_the_topic_title', stripslashes( $forum_template->topic->topic_title ) );
	}

function bp_the_topic_slug() {
	echo bp_get_the_topic_slug();
}
	function bp_get_the_topic_slug() {
		global $forum_template;

		return apply_filters( 'bp_get_the_topic_slug', $forum_template->topic->topic_slug );
	}

function bp_the_topic_poster_id() {
	echo bp_get_the_topic_poster_id();
}
	function bp_get_the_topic_poster_id() {
		global $forum_template;

		return apply_filters( 'bp_get_the_topic_poster_id', $forum_template->topic->topic_poster );
	}

function bp_the_topic_poster_avatar() {
	echo bp_get_the_topic_poster_avatar();
}
	function bp_get_the_topic_poster_avatar() {
		global $forum_template;

		return apply_filters( 'bp_get_the_topic_poster_avatar', bp_core_get_avatar( $forum_template->topic->topic_poster, 1 ) ); 
	}

function bp_the_topic_poster_name() {
	echo bp_get_the_topic_poster_name();
}
	function bp_get_the_topic_poster_name() {
		global $forum_template;

		return apply_filters( 'bp_get_the_topic_poster_name', bp_core_get_userlink( $forum_template->topic->topic_poster ) );
	}
	
function bp_the_topic_last_poster_name() {
	echo bp_get_the_topic_last_poster_name();
}
	function bp_get_the_topic_last_poster_name() {
		global $forum_template;

		return apply_filters( 'bp_get_the_topic_last_poster_name', bp_core_get_userlink( $forum_template->topic->topic_last_poster ) );
	}

function bp_the_topic_last_poster_avatar() {
	echo bp_get_the_topic_last_poster_avatar();
}
	function bp_get_the_topic_last_poster_avatar() {
		global $forum_template;

		return apply_filters( 'bp_get_the_topic_last_poster_avatar', bp_core_get_avatar( $forum_template->topic->topic_last_poster, 1 ) ); 
	}

function bp_the_topic_start_time() {
	echo bp_get_the_topic_start_time();
}
	function bp_get_the_topic_start_time() {
		global $forum_template;

		return apply_filters( 'bp_get_the_topic_start_time', $forum_template->topic->topic_start_time );
	}
	
function bp_the_topic_time() {
	echo bp_get_the_topic_time();
}
	function bp_get_the_topic_time() {
		global $forum_template;

		return apply_filters( 'bp_get_the_topic_time', $forum_template->topic->topic_time );
	}

function bp_the_topic_forum_id() {
	echo bp_get_the_topic_forum_id();
}
	function bp_get_the_topic_forum_id() {
		global $forum_template;

		return apply_filters( 'bp_get_the_topic_forum_id', $forum_template->topic->topic_forum_id );
	}

function bp_the_topic_status() {
	echo bp_get_the_topic_status();
}
	function bp_get_the_topic_status() {
		global $forum_template;

		return apply_filters( 'bp_get_the_topic_status', $forum_template->topic->topic_status );
	}

function bp_the_topic_is_topic_open() {
	echo bp_get_the_topic_is_topic_open();
}
	function bp_get_the_topic_is_topic_open() {
		global $forum_template;

		return apply_filters( 'bp_get_the_topic_is_topic_open', $forum_template->topic->topic_open );
	}

function bp_the_topic_last_post_id() {
	echo bp_get_the_topic_last_post_id();
}
	function bp_get_the_topic_last_post_id() {
		global $forum_template;

		return apply_filters( 'bp_get_the_topic_last_post_id', $forum_template->topic->topic_last_post_id );
	}

function bp_the_topic_is_sticky() {
	echo bp_get_the_topic_is_sticky();
}
	function bp_get_the_topic_is_sticky() {
		global $forum_template;

		return apply_filters( 'bp_get_the_topic_is_sticky', $forum_template->topic->topic_sticky );
	}

function bp_the_topic_total_post_count() {
	echo bp_get_the_topic_total_post_count();
}
	function bp_get_the_topic_total_post_count() {
		global $forum_template;

		if ( $forum_template->topic->topic_posts == 1 )
			return apply_filters( 'bp_get_the_topic_total_post_count', sprintf( __( '%d post', 'buddypress' ), $forum_template->topic->topic_posts ) );
		else
			return apply_filters( 'bp_get_the_topic_total_post_count', sprintf( __( '%d posts', 'buddypress' ), $forum_template->topic->topic_posts ) );		
	}

function bp_the_topic_tag_count() {
	echo bp_get_the_topic_tag_count();
}
	function bp_get_the_topic_tag_count() {
		global $forum_template;

		return apply_filters( 'bp_get_the_topic_tag_count', $forum_template->topic->tag_count );
	}

function bp_the_topic_permalink() {
	echo bp_get_the_topic_permalink();
}
	function bp_get_the_topic_permalink() {
		global $forum_template, $bbpress_live, $group_obj;

		$target_uri = $bbpress_live->fetch->options['target_uri'];

		return apply_filters( 'bp_get_the_topic_permalink', bp_get_group_permalink( $group_obj ) . '/forum/topic/' . $forum_template->topic->topic_id );
	}

function bp_the_topic_time_since_created() {
	echo bp_get_the_topic_time_since_created();
}
	function bp_get_the_topic_time_since_created() {
		global $forum_template;

		return apply_filters( 'bp_get_the_topic_time_since_created', $forum_template->topic->topic_start_time_since );
	}
	
function bp_the_topic_latest_post_excerpt() {
	echo bp_get_the_topic_latest_post_excerpt();
}
	function bp_get_the_topic_latest_post_excerpt() {
		global $forum_template;

		$post = bp_forums_get_post( $forum_template->topic->topic_last_post_id );
		return apply_filters( 'bp_get_the_topic_latest_post_excerpt', $post->post_text );
	}

function bp_the_topic_time_since_last_post( $deprecated = true ) {
	global $forum_template;
	
	if ( !$deprecated )
		return bp_get_the_topic_time_since_last_post();
	else
		echo bp_get_the_topic_time_since_last_post();
}
	function bp_get_the_topic_time_since_last_post() {
		global $forum_template;

		return apply_filters( 'bp_get_the_topic_time_since_last_post', $forum_template->topic->topic_time_since );
	}

function bp_forum_pagination() {
	echo bp_get_forum_pagination();
}
	function bp_get_forum_pagination() {
		global $forum_template;

		return apply_filters( 'bp_get_forum_pagination', $forum_template->pag_links );
	}

function bp_forum_pagination_count() {
	global $bp, $forum_template;
	
	$from_num = intval( ( $forum_template->pag_page - 1 ) * $forum_template->pag_num ) + 1;
	$to_num = ( $from_num + ( $forum_template->pag_num - 1  ) > $forum_template->total_topic_count ) ? $forum_template->total_topic_count : $from_num + ( $forum_template->pag_num - 1 ); 
	
	echo apply_filters( 'bp_forum_pagination_count', sprintf( __( 'Viewing topic %d to %d (%d total topics)', 'buddypress' ), $from_num, $to_num, $forum_template->total_topic_count ) );
?>
	<img id="ajax-loader-groups" src="<?php echo $bp->core->image_base ?>/ajax-loader.gif" height="7" alt="<?php _e( "Loading", "buddypress" ) ?>" style="display: none;" />
<?php
}


class BP_Forums_Template_Topic {
	var $current_post = -1;
	var $post_count;
	var $posts;
	var $post;
	
	var $topic_id;
	var $topic;
	
	var $in_the_loop;
	
	var $pag_page;
	var $pag_num;
	var $pag_links;
	var $total_post_count;
	
	var $single_post = false;
	
	var $sort_by;
	var $order;
	
	function BP_Forums_Template_Topic( $topic_id, $posts_per_page = 10 ) {
		global $bp, $current_user, $forum_template;
		
		$this->pag_page = isset( $_REQUEST['page'] ) ? intval( $_REQUEST['page'] ) : 1;
		$this->pag_num = isset( $_REQUEST['num'] ) ? intval( $_REQUEST['num'] ) : $posts_per_page;
	
		$this->topic_id = $topic_id;
		$forum_template->topic = (object) bp_forums_get_topic_details( $this->topic_id );
		
		$this->posts = bp_forums_get_posts( $this->topic_id, $this->pag_num, $this->pag_page );
		
		if ( !$this->posts ) {
			$this->post_count = 0;
			$this->total_post_count = 0;
		} else {
			$this->post_count = count( $this->posts );
			$this->total_post_count = (int) $forum_template->topic->topic_posts;			
		}

		$this->pag_links = paginate_links( array(
			'base' => add_query_arg( array( 'page' => '%#%', 'num' => $this->pag_num ) ),
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
		$this->post = $this->next_post();
		$this->post = (object)$this->post;
		
		if ( $this->current_post == 0 ) // loop has just started
			do_action('loop_start');
	}
}

function bp_has_topic_posts( $posts_per_page = 10, $topic_id = false ) {
	global $topic_template, $bp;
	
	if ( !$topic_id )
		$topic_id = $bp->action_variables[1];

	if ( is_numeric( $topic_id ) )
		$topic_template = new BP_Forums_Template_Topic( $topic_id, $posts_per_page );
	else
		return false;

	return $topic_template->has_posts();
}

function bp_topic_posts() {
	global $topic_template;
	return $topic_template->user_posts();
}

function bp_the_topic_post() {
	global $topic_template;
	return $topic_template->the_post();
}

function bp_the_topic_post_id() {
	echo bp_get_the_topic_post_id();
}
	function bp_get_the_topic_post_id() {
		global $topic_template;
		
		return apply_filters( 'bp_get_the_topic_post_id', $topic_template->post->post_id );
	}

function bp_the_topic_post_content() {
	echo bp_get_the_topic_post_content();
}
	function bp_get_the_topic_post_content() {
		global $topic_template;

		return apply_filters( 'bp_get_the_topic_post_content', stripslashes( $topic_template->post->post_text ) );
	}

function bp_the_topic_post_poster_avatar() {
	echo bp_get_the_topic_post_poster_avatar();
}
	function bp_get_the_topic_post_poster_avatar() {
		global $topic_template;

		return apply_filters( 'bp_get_the_topic_post_poster_avatar', bp_core_get_avatar( $topic_template->post->poster_id, 1, 20, 20 ) ); 
	}

function bp_the_topic_post_poster_name( $deprecated = true ) {
	if ( !$deprecated )
		return bp_get_the_topic_post_poster_name();
	else
		echo bp_get_the_topic_post_poster_name();
}
	function bp_get_the_topic_post_poster_name() {
		global $topic_template;

		return apply_filters( 'bp_get_the_topic_post_poster_name', bp_core_get_userlink( $topic_template->post->poster_id ) );		
	}

function bp_the_topic_post_time_since( $deprecated = true ) {
	if ( !$deprecated )
		return bp_get_the_topic_post_time_since();
	else
		echo bp_get_the_topic_post_time_since();
}
	function bp_get_the_topic_post_time_since() {
		global $topic_template;

		return apply_filters( 'bp_get_the_topic_post_time_since', $topic_template->post->post_time_since );
	}

function bp_the_topic_pagination() {
	echo bp_get_the_topic_pagination();
}
	function bp_get_the_topic_pagination() {
		global $topic_template;

		return apply_filters( 'bp_get_the_topic_pagination', $topic_template->pag_links );
	}

function bp_the_topic_pagination_count() {
	global $bp, $topic_template;
	
	$from_num = intval( ( $topic_template->pag_page - 1 ) * $topic_template->pag_num ) + 1;
	$to_num = ( $from_num + ( $topic_template->pag_num - 1  ) > $topic_template->total_post_count ) ? $topic_template->total_post_count : $from_num + ( $topic_template->pag_num - 1 ); 
	
	echo apply_filters( 'bp_the_topic_pagination_count', sprintf( __( 'Viewing post %d to %d (%d total posts)', 'buddypress' ), $from_num, $to_num, $topic_template->total_post_count ) );
?>
	<img id="ajax-loader-groups" src="<?php echo $bp->core->image_base ?>/ajax-loader.gif" height="7" alt="<?php _e( "Loading", "buddypress" ) ?>" style="display: none;" />
<?php
}

function bp_forum_permalink() {
	echo bp_get_forum_permalink();
}
	function bp_get_forum_permalink() {
		global $group_obj;

		return apply_filters( 'bp_get_forum_permalink', bp_get_group_permalink( $group_obj ) . '/forum' );
	}

function bp_forum_action() {
	echo bp_get_forum_action();
}
	function bp_get_forum_action() {
		global $topic_template;

		return apply_filters( 'bp_get_forum_action', bp_get_group_permalink( $group_obj ) . '/forum' );	
	}

function bp_forum_topic_action() {
	echo bp_get_forum_topic_action();
}
	function bp_get_forum_topic_action() {
		global $topic_template;

		return apply_filters( 'bp_get_forum_topic_action', bp_get_group_permalink( $group_obj ) . '/forum/topic/' . $topic_template->topic_id );	
	}

?>