<?php

class BP_Activity_Template {
	var $current_activity = -1;
	var $activity_count;
	var $activities;
	var $activity;
	
	var $in_the_loop;
	
	var $pag_page;
	var $pag_num;
	var $pag_links;
	var $total_activity_count;
	
	var $full_name;
	
	var $table_name;
	var $filter_content;
	var $is_home;
	
	function bp_activity_template( $user_id = false, $limit = false, $filter_content = true ) {
		global $bp;
		
		if ( !$user_id )
			$user_id = $bp['current_userid'];

		if ( $bp['current_component'] != $bp['activity']['slug'] || ( $bp['current_component'] == $bp['activity']['slug'] && $bp['current_action'] == 'just-me' || $bp['current_action'] == 'feed' ) ) {
			$this->activities = BP_Activity_Activity::get_activity_for_user( $user_id, $limit );
		} else {
			$this->activities = BP_Activity_Activity::get_activity_for_friends( $user_id, $limit );
		}

		$this->activity_count = count($this->activities);
	
		$this->full_name = $bp['current_fullname'];

		$this->is_home = bp_is_home();
		$this->filter_content = $filter_content;
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

function bp_has_activities() {
	global $bp, $activities_template, $bp_activity_user_id, $bp_activity_limit;
	
	if ( $bp['current_action'] == 'my-friends' )
		$filter_content = false;
	else
		$filter_content = true;
	
	if ( !$bp_activity_user_id )
		$bp_activity_user_id = $bp['current_userid'];
	
	if ( !$bp_activity_limit )
		$bp_activity_limit = 35;
		
	$activities_template = new BP_Activity_Template( $bp_activity_user_id, $bp_activity_limit, $filter_content );		
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
	
	if ( $activities_template->filter_content ) {
		if ( $activities_template->is_home ) {
			echo apply_filters( 'bp_activity_content', bp_activity_content_filter( $activities_template->activity['content'], $activities_template->activity['date_recorded'], $activities_template->full_name ) );						
		} else {
			echo apply_filters( 'bp_activity_content', bp_activity_content_filter( $activities_template->activity['content'], $activities_template->activity['date_recorded'], $activities_template->full_name, true, false, false ) );									
		}
	} else {
		$activities_template->activity['content'] = bp_activity_insert_time_since( $activities_template->activity['content'], $activities_template->activity['date_recorded'] );
		echo apply_filters( 'bp_activity_content', $activities_template->activity['content'] );
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

	/* Switch 'their/your' depending on whether the user is logged in or not and viewing their profile */
	if ( $filter_words ) {
		$content[0] = str_replace( __('their', 'buddypress'), __('your', 'buddypress'), $content[0] );
	}
	
	/* Remove the 'You' and replace if with the persons name */
	if ( $filter_you ) {
		$content[0] = str_replace( $full_name, __('You', 'buddypress'), $content[0] );				
	}
	
	for ( $i = 0; $i < count($content); $i++ )
		$content_new .= $content[$i];
	
	return apply_filters( 'bp_activity_content_filter', $content_new );
}

function bp_activity_insert_time_since( $content, $date ) {
	if ( !$content || !$date )
		return false;
		
	return apply_filters( 'bp_activity_insert_time_since', @sprintf( $content, @sprintf( __( '&nbsp; %s ago', 'buddypress' ), bp_core_time_since( strtotime( $date ) ) ) ) );
}

function bp_activity_css_class() {
	global $activities_template;
	echo apply_filters( 'bp_activity_css_class', $activities_template->activity['component_name'] );
}

function bp_sitewide_activity_feed_link() {
	global $bp;
	
	echo apply_filters( 'bp_sitewide_activity_feed_link', site_url() . '/' . $bp['activity']['slug'] . '/feed' );
}

function bp_activities_member_rss_link() {
	global $bp;
	
	if ( ( $bp['current_component'] == $bp['profile']['slug'] ) || $bp['current_action'] == 'just-me' )
		echo apply_filters( 'bp_activities_member_rss_link', $bp['current_domain'] . $bp['activity']['slug'] . '/feed' );
	else
		echo apply_filters( 'bp_activities_member_rss_link', $bp['current_domain'] . $bp['activity']['slug'] . '/my-friends/feed' );		
}

/* Template tags for RSS feed output */

function bp_activity_feed_item_title() {
	global $activities_template;

	$title = explode( '<span', $activities_template->activity['content'] );
	echo apply_filters( 'bp_activity_feed_item_title', trim( strip_tags( $title[0] ) ) );
}

function bp_activity_feed_item_link() {
	global $activities_template;

	echo apply_filters( 'bp_activity_feed_item_link', $activities_template->activity['primary_link'] );
}

function bp_activity_feed_item_date() {
	global $activities_template;

	echo apply_filters( 'bp_activity_feed_item_date', $activities_template->activity['date_recorded'] );
}

function bp_activity_feed_item_description() {
	global $activities_template;

	echo apply_filters( 'bp_activity_feed_item_description', sprintf( $activities_template->activity['content'], '' ) );	
}



?>