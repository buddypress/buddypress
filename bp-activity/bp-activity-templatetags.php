<?php

class BP_Activity_Template {
	var $current_activity = -1;
	var $activity_count;
	var $total_activity_count;
	var $activities;
	var $activity;
	var $activity_type;
	
	var $in_the_loop;
	
	var $pag_page;
	var $pag_num;
	var $pag_links;

	var $full_name;

	function bp_activity_template( $type, $user_id, $per_page, $max, $timeframe ) {
		global $bp;

		$this->pag_page = isset( $_REQUEST['page'] ) ? intval( $_REQUEST['page'] ) : 1;
		$this->pag_num = isset( $_REQUEST['num'] ) ? intval( $_REQUEST['num'] ) : $per_page;
		$this->filter_content = false;
		$this->activity_type = $type;

		if ( $type == 'sitewide' )
			$this->activities = bp_activity_get_sitewide_activity( $max, $this->pag_num, $this->pag_page );
		
		if ( $type == 'personal' )
			$this->activities = bp_activity_get_user_activity( $user_id, $timeframe, $this->page_num, $this->pag_page );

		if ( $type == 'friends' && ( bp_is_home() || is_site_admin() || $bp->loggedin_user->id == $user_id ) )
			$this->activities = bp_activity_get_friends_activity( $user_id, $timeframe, $this->pag_num, $this->pag_page );
		
		if ( !$max )
			$this->total_activity_count = (int)$this->activities['total'];
		else
			$this->total_activity_count = (int)$max;
		
		$this->activities = $this->activities['activities'];
		
		if ( $max ) {
			if ( $max >= count($this->activities) )
				$this->activity_count = count($this->activities);
			else
				$this->activity_count = (int)$max;
		} else {
			$this->activity_count = count($this->activities);
		}
		
		$this->full_name = $bp->displayed_user->fullname;

		$this->pag_links = paginate_links( array(
			'base' => add_query_arg( 'page', '%#%' ),
			'format' => '',
			'total' => ceil( (int)$this->total_activity_count / (int)$this->pag_num ),
			'current' => (int)$this->pag_page,
			'prev_text' => '&laquo;',
			'next_text' => '&raquo;',
			'mid_size' => 1
		));
	}
	
	function has_activities() {
		if ( $this->activity_count )
			return true;
		
		return false;
	}
	
	function next_activity() {
		$this->current_activity++;
		$this->activity = $this->activities[$this->current_activity];
		
		return $this->activity;
	}
	
	function rewind_activities() {
		$this->current_activity = -1;
		if ( $this->activity_count > 0 ) {
			$this->activity = $this->activities[0];
		}
	}
	
	function user_activities() { 
		if ( $this->current_activity + 1 < $this->activity_count ) {
			return true;
		} elseif ( $this->current_activity + 1 == $this->activity_count ) {
			do_action('loop_end');
			// Do some cleaning up after the loop
			$this->rewind_activities();
		}

		$this->in_the_loop = false;
		return false;
	}
	
	function the_activity() {
		global $activity;

		$this->in_the_loop = true;
		$this->activity = $this->next_activity();
		
		if ( is_array( $this->activity ) )
			$this->activity = (object) $this->activity;

		if ( $this->current_activity == 0 ) // loop has just started
			do_action('loop_start');
	}
}

function bp_activity_get_list( $user_id, $title, $no_activity, $limit = false ) {
	global $bp_activity_user_id, $bp_activity_limit, $bp_activity_title, $bp_activity_no_activity;
	
	$bp_activity_user_id = $user_id;
	$bp_activity_limit = $limit;
	$bp_activity_title = $title;
	$bp_activity_no_activity = $no_activity;
	
	load_template( TEMPLATEPATH . '/activity/activity-list.php' );
}

function bp_has_activities( $args = '' ) {
	global $bp, $activities_template, $bp_activity_user_id, $bp_activity_limit;

	$defaults = array(
		'type' => 'sitewide',
		'user_id' => false,
		'per_page' => 25,
		'max' => false,
		'timeframe' => '-4 weeks'
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );
	
	// The following lines are for backwards template compatibility.
	if ( 'my-friends' == $bp->current_action && $bp->activity->slug == $bp->current_component )
		$type = 'friends';
	
	if ( $bp->displayed_user->id && $bp->activity->slug == $bp->current_component && ( !$bp->current_action || 'just-me' == $bp->current_action ) )
		$type = 'personal';
	
	if ( $bp->displayed_user->id && $bp->profile->slug == $bp->current_component )
		$type = 'personal';

	if ( $bp_activity_limit )
		$max = $bp_activity_limit;

	// END backwards compatibility ---

	if ( ( 'personal' == $type || 'friends' == $type ) && !$user_id )
		$user_id = (int)$bp->displayed_user->id;

	if ( $max ) {
		if ( $per_page > $max )
			$per_page = $max;
	}
	
	$activities_template = new BP_Activity_Template( $type, $user_id, $per_page, $max, $timeframe );		
	return $activities_template->has_activities();
}

function bp_activities() {
	global $activities_template;
	return $activities_template->user_activities();
}

function bp_the_activity() {
	global $activities_template;
	return $activities_template->the_activity();
}

function bp_activities_title() {
	global $bp_activity_title;
	
	echo apply_filters( 'bp_activities_title', $bp_activity_title );
}

function bp_activities_no_activity() {
	global $bp_activity_no_activity;
	echo apply_filters( 'bp_activities_no_activity', $bp_activity_no_activity );
}

function bp_activity_content() {
	global $activities_template;

	if ( bp_is_home() && $activities_template->activity_type == 'personal' ) {
		echo apply_filters( 'bp_activity_content', bp_activity_content_filter( $activities_template->activity->content, $activities_template->activity->date_recorded, $activities_template->full_name ) );						
	} else {
		$activities_template->activity->content = bp_activity_insert_time_since( $activities_template->activity->content, $activities_template->activity->date_recorded );
		echo apply_filters( 'bp_activity_content', $activities_template->activity->content );
	}
}

function bp_activity_content_filter( $content, $date_recorded, $full_name, $insert_time = true, $filter_words = true, $filter_you = true ) {
	if ( !$content )
		return false;
		
	/* Split the content so we don't evaluate and replace text on content we don't want to */
	$content = explode( '%s', $content );

	/* Re-add the exploded %s */
	$content[0] .= '%s';

	/* Insert the time since */
	if ( $insert_time )
		$content[0] = bp_activity_insert_time_since( $content[0], $date_recorded );

	// The "You" and "Your" conversion is only done in english, if a translation file is present
	// then do not translate as it causes problems in other languages.
	if ( '' == get_locale() ) {
		/* Switch 'their/your' depending on whether the user is logged in or not and viewing their profile */
		if ( $filter_words ) {
			$content[0] = preg_replace( '/their\s/', 'your ', $content[0] );
		}

		/* Remove the 'You' and replace if with the persons name */
		if ( $filter_you && $full_name != '' ) {
			$content[0] = preg_replace( "/{$full_name}[<]/", 'You<', $content[0] );				
		}
	}
	
	$content_new = '';
	
	for ( $i = 0; $i < count($content); $i++ )
		$content_new .= $content[$i];
	
	return apply_filters( 'bp_activity_content_filter', $content_new );
}

function bp_activity_insert_time_since( $content, $date ) {
	if ( !$content || !$date )
		return false;

	// Make sure we don't have any URL encoding in links when trying to insert the time.
	$content = urldecode($content);
	
	return apply_filters( 'bp_activity_insert_time_since', @sprintf( $content, @sprintf( __( '&nbsp; %s ago', 'buddypress' ), bp_core_time_since( strtotime( $date ) ) ) ) );
}

function bp_activity_css_class() {
	global $activities_template;
	echo apply_filters( 'bp_activity_css_class', $activities_template->activity->component_name );
}

function bp_sitewide_activity_feed_link() {
	global $bp;
	
	echo apply_filters( 'bp_sitewide_activity_feed_link', site_url() . '/' . $bp->activity->slug . '/feed' );
}

function bp_activities_member_rss_link() {
	global $bp;
	
	if ( ( $bp->current_component == $bp->profile->slug ) || 'just-me' == $bp->current_action )
		echo apply_filters( 'bp_activities_member_rss_link', $bp->displayed_user->domain . $bp->activity->slug . '/feed' );
	else
		echo apply_filters( 'bp_activities_member_rss_link', $bp->displayed_user->domain . $bp->activity->slug . '/my-friends/feed' );		
}

/* Template tags for RSS feed output */

function bp_activity_feed_item_title() {
	global $activities_template;

	$title = explode( '<span', $activities_template->activity->content );
	echo apply_filters( 'bp_activity_feed_item_title', trim( strip_tags( html_entity_decode( $title[0] ) ) ) );
}

function bp_activity_feed_item_link() {
	global $activities_template;

	echo apply_filters( 'bp_activity_feed_item_link', $activities_template->activity->primary_link );
}

function bp_activity_feed_item_date() {
	global $activities_template;

	echo apply_filters( 'bp_activity_feed_item_date', $activities_template->activity->date_recorded );
}

function bp_activity_feed_item_description() {
	global $activities_template;

	echo apply_filters( 'bp_activity_feed_item_description', sprintf( html_entity_decode( $activities_template->activity->content, ENT_COMPAT, 'UTF-8' ) ) );	
}



?>