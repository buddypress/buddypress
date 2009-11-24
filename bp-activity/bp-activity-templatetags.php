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

	function bp_activity_template( $type, $user_id, $per_page, $max, $include, $sort, $filter, $search_terms, $display_comments ) {
		global $bp;

		$this->pag_page = isset( $_REQUEST['acpage'] ) ? intval( $_REQUEST['acpage'] ) : 1;
		$this->pag_num = isset( $_REQUEST['num'] ) ? intval( $_REQUEST['num'] ) : $per_page;
		$this->activity_type = $type;

		if ( !empty( $include ) ) {
			/* Fetch specific activity items based on ID's */
			$this->activities = bp_activity_get_specific( array( 'activity_ids' => explode( ',', $include ), 'max' => $max, 'page' => $page, 'per_page' => $per_page, 'sort' => $sort, 'display_comments' => $display_comments ) );
		} else {
			if ( $type == 'sitewide' )
				$this->activities = bp_activity_get_sitewide( array( 'display_comments' => $display_comments, 'max' => $max, 'per_page' => $this->pag_num, 'page' => $this->pag_page, 'sort' => $sort, 'search_terms' => $search_terms, 'filter' => $filter ) );

			if ( $type == 'personal' )
				$this->activities = bp_activity_get_for_user( array( 'user_id' => $user_id, 'display_comments' => $display_comments, 'max' => $max, 'per_page' => $this->pag_num, 'page' => $this->pag_page, 'sort' => $sort, 'search_terms' => $search_terms, 'filter' => $filter ) );

			if ( $type == 'friends' && ( bp_is_home() || is_site_admin() || $bp->loggedin_user->id == $user_id ) )
				$this->activities = bp_activity_get_friends_activity( $user_id, $max, false, $this->pag_num, $this->pag_page, $filter );
		}

		if ( !$max || $max >= (int)$this->activities['total'] )
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

		if ( (int) $this->total_activity_count && (int) $this->pag_num ) {
			$this->pag_links = paginate_links( array(
				'base' => add_query_arg( 'acpage', '%#%' ),
				'format' => '',
				'total' => ceil( (int)$this->total_activity_count / (int)$this->pag_num ),
				'current' => (int)$this->pag_page,
				'prev_text' => '&laquo;',
				'next_text' => '&raquo;',
				'mid_size' => 1
			));
		}
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
			do_action('activity_loop_end');
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
			do_action('activity_loop_start');
	}
}

function bp_has_activities( $args = '' ) {
	global $bp, $activities_template;

	/* Note: any params used for filtering can be a single value, or multiple values comma separated. */

	$defaults = array(
		'type' => 'sitewide',
		'display_comments' => false, // false for none, stream/threaded - show comments in the stream or threaded under items
		'include' => false, // pass an activity_id or string of ID's comma separated
		'sort' => 'DESC', // sort DESC or ASC
		'per_page' => 25, // number of items per page
		'max' => false, // max number to return

		/* Filtering */
		'user_id' => false, // user_id to filter on
		'object' => false, // object to filter on e.g. groups, profile, status, friends
		'action' => false, // action to filter on e.g. new_wire_post, new_forum_post, profile_updated
		'primary_id' => false, // object ID to filter on e.g. a group_id or forum_id or blog_id etc.
		'secondary_id' => false, // secondary object ID to filter on e.g. a post_id

		/* Searching */
		'search_terms' => false // specify terms to search on
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	if ( ( 'personal' == $type || 'friends' == $type ) && !$user_id )
		$user_id = (int)$bp->displayed_user->id;

	if ( $max ) {
		if ( $per_page > $max )
			$per_page = $max;
	}

	if ( isset( $_GET['afilter'] ) )
		$filter = array( 'object' => $_GET['afilter'] );
	else
		$filter = array( 'object' => $object, 'action' => $action, 'primary_id' => $primary_id, 'secondary_id' => $secondary_id );

	$activities_template = new BP_Activity_Template( $type, $user_id, $per_page, $max, $include, $sort, $filter, $search_terms, $display_comments );

	return apply_filters( 'bp_has_activities', $activities_template->has_activities(), &$activities_template );
}

function bp_activities() {
	global $activities_template;
	return $activities_template->user_activities();
}

function bp_the_activity() {
	global $activities_template;
	return $activities_template->the_activity();
}

function bp_activity_pagination_count() {
	global $bp, $activities_template;

	$from_num = intval( ( $activities_template->pag_page - 1 ) * $activities_template->pag_num ) + 1;
	$to_num = ( $from_num + ( $activities_template->pag_num - 1 ) > $activities_template->total_activity_count ) ? $activities_template->total_activity_count : $from_num + ( $activities_template->pag_num - 1) ;

	echo sprintf( __( 'Viewing item %d to %d (of %d items)', 'buddypress' ), $from_num, $to_num, $activities_template->total_activity_count ); ?> &nbsp;
	<span class="ajax-loader"></span><?php
}

function bp_activity_pagination_links() {
	echo bp_get_activity_pagination_links();
}
	function bp_get_activity_pagination_links() {
		global $activities_template;

		return apply_filters( 'bp_get_activity_pagination_links', $activities_template->pag_links );
	}

function bp_activities_title() {
	global $bp_activity_title;

	echo bp_get_activities_title();
}
	function bp_get_activities_title() {
		global $bp_activity_title;

		return apply_filters( 'bp_get_activities_title', $bp_activity_title );
	}

function bp_activities_no_activity() {
	global $bp_activity_no_activity;

	echo bp_get_activities_no_activity();
}
	function bp_get_activities_no_activity() {
		global $bp_activity_no_activity;

		return apply_filters( 'bp_get_activities_no_activity', $bp_activity_no_activity );
	}

function bp_activity_id() {
	echo bp_get_activity_id();
}
	function bp_get_activity_id() {
		global $activities_template;
		return apply_filters( 'bp_get_activity_id', $activities_template->activity->id );
	}

function bp_activity_item_id() {
	echo bp_get_activity_item_id();
}
	function bp_get_activity_item_id() {
		global $activities_template;
		return apply_filters( 'bp_get_activity_item_id', $activities_template->activity->item_id );
	}

function bp_activity_secondary_item_id() {
	echo bp_get_activity_secondary_item_id();
}
	function bp_get_activity_secondary_item_id() {
		global $activities_template;
		return apply_filters( 'bp_get_activity_secondary_item_id', $activities_template->activity->secondary_item_id );
	}

function bp_activity_date_recorded() {
	echo bp_get_activity_date_recorded();
}
	function bp_get_activity_date_recorded() {
		global $activities_template;
		return apply_filters( 'bp_get_activity_date_recorded', $activities_template->activity->date_recorded );
	}

function bp_activity_object_name() {
	echo bp_get_activity_object_name();
}
	function bp_get_activity_object_name() {
		global $activities_template;
		return apply_filters( 'bp_get_activity_object_name', $activities_template->activity->component_name );
	}

function bp_activity_action_name() {
	echo bp_get_activity_action_name();
}
	function bp_get_activity_action_name() {
		global $activities_template;
		return apply_filters( 'bp_get_activity_action_name', $activities_template->activity->component_action );
	}

function bp_activity_user_id() {
	echo bp_get_activity_user_id();
}
	function bp_get_activity_user_id() {
		global $activities_template;
		return apply_filters( 'bp_get_activity_user_id', $activities_template->activity->user_id );
	}

function bp_activity_avatar( $args = '' ) {
	echo bp_get_activity_avatar( $args );
}
	function bp_get_activity_avatar( $args = '' ) {
		global $bp, $activities_template;

		$defaults = array(
			'type' => 'thumb',
			'width' => 20,
			'height' => 20,
			'class' => 'avatar',
			'alt' => __( 'Avatar', 'buddypress' )
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		$item_id = false;
		if ( (int)$activities_template->activity->user_id )
			$item_id = $activities_template->activity->user_id;
		else if ( $activities_template->activity->item_id )
			$item_id = $activities_template->activity->item_id;

		$object = 'user';
		if ( $bp->groups->id == $activities_template->activity->component_name && !(int) $activities_template->activity->user_id )
			$object = 'group';
		if ( $bp->blogs->id == $activities_template->activity->component_name && !(int) $activities_template->activity->user_id )
			$object = 'blog';

		$object = apply_filters( 'bp_get_activity_avatar_object_' . $activities_template->activity->component_name, $object );

		return apply_filters( 'bp_get_activity_avatar', bp_core_fetch_avatar( array( 'item_id' => $item_id, 'object' => $object, 'type' => $type, 'alt' => $alt, 'class' => $class, 'width' => $width, 'height' => $height ) ) );
	}

function bp_activity_content() {
	echo bp_get_activity_content();
}
	function bp_get_activity_content() {
		global $activities_template, $allowed_tags, $bp;

		if ( bp_is_home() && $activities_template->activity_type == 'personal' )
			$content = bp_activity_content_filter( $activities_template->activity->content, $activities_template->activity->date_recorded, $activities_template->full_name );
		else
			$content = bp_activity_content_filter( $activities_template->activity->content, $activities_template->activity->date_recorded, $activities_template->full_name, true, false, false );

		return apply_filters( 'bp_get_activity_content', $content, $activities_template->activity->component_name, $activities_template->activity->component_action );
	}

function bp_activity_content_filter( $content, $date_recorded, $full_name, $insert_time = true, $filter_words = true, $filter_you = true ) {
	global $activities_template, $bp;

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
			$content[0] = preg_replace( "/{$full_name}[<]/", 'You<', $content[0], 1 );
		}
	}

	/* Add the delete link if the user has permission on this item */
	if ( ( $activities_template->activity->user_id == $bp->loggedin_user->id ) || $bp->is_item_admin || is_site_admin() )
		$content[1] = '</span> <span class="activity-delete-link">' . bp_get_activity_delete_link() . '</span>' . $content[1];

	$content_new = '';

	for ( $i = 0; $i < count($content); $i++ )
		$content_new .= $content[$i];

	return apply_filters( 'bp_activity_content_filter', $content_new );
}

function bp_activity_comments( $args = '' ) {
	echo bp_activity_get_comments( $args );
}
	function bp_activity_get_comments( $args = '' ) {
		global $activities_template, $bp;

		if ( !$activities_template->activity->children )
			return false;

		$comments_html = bp_activity_recurse_comments( $activities_template->activity );

		return apply_filters( 'bp_activity_get_comments', $comments_html );
	}
		/* The HTML in this function is temporary, it will be move to template tags once comments are working. */
		function bp_activity_recurse_comments( $comment ) {
			global $activities_template, $bp;

			if ( !$comment->children )
				return false;

			$content .= '<ul>';
			foreach ( $comment->children as $comment ) {
				$content .= '<li id="acomment-' . $comment->id . '">';

				$content .= '<div class="acomment-avatar">' . bp_core_fetch_avatar( array( 'item_id' => $comment->user_id, 'width' => 25, 'height' => 25 ) ) . '</div>';

				$content .= '<div class="acomment-meta">' . bp_core_get_userlink( $comment->user_id ) . ' &middot; ' . sprintf( __( '%s ago', 'buddypress' ), bp_core_time_since( strtotime( $comment->date_recorded ) ) );

				/* Reply link */
				if ( is_user_logged_in() )
					$content .= ' &middot; <a href="#acomment-' . $comment->id . '" class="acomment-reply" id="acomment-reply-' . $activities_template->activity->id . '">' . __( 'Reply', 'buddypress' ) . '</a>';

				/* Delete link */
				if ( is_site_admin() || $bp->loggedin_user->id == $comment->user_id )
					$content .= ' &middot; <a href="' . wp_nonce_url( $bp->activity->id . '/delete/?cid=' . $comment->id, 'delete_activity_comment' ) . '" class="delete acomment-delete">' . __( 'Delete', 'buddypress' ) . '</a>';

				$content .= '</div>';
				$content .= '<div class="acomment-content">' . apply_filters( 'bp_get_activity_content', $comment->content ) . '</div>';

				$content .= bp_activity_recurse_comments( $comment );
				$content .= '</li>';
			}
			$content .= '</ul>';

			return $content;
		}

function bp_activity_comment_count() {
	echo bp_activity_get_comment_count();
}
	function bp_activity_get_comment_count( $args = '' ) {
		global $activities_template, $bp;

		if ( !$activities_template->activity->children )
			return 0;

		$count = bp_activity_recurse_comment_count( $activities_template->activity );

		return apply_filters( 'bp_activity_get_comment_count', (int)$count );
	}
		function bp_activity_recurse_comment_count( $comment, $count = 0 ) {
			global $activities_template, $bp;

			if ( !$comment->children )
				return $count;

			foreach ( $comment->children as $comment ) {
				$count++;
				$count = bp_activity_recurse_comment_count( $comment, $count );
			}

			return $count;
		}

function bp_activity_insert_time_since( $content, $date ) {
	if ( !$content || !$date )
		return false;

	// Make sure we don't have any URL encoding in links when trying to insert the time.
	$content = urldecode($content);

	return apply_filters( 'bp_activity_insert_time_since', @sprintf( $content, @sprintf( __( '&nbsp; %s ago', 'buddypress' ), bp_core_time_since( strtotime( $date ) ) ) ) );
}

function bp_activity_css_class() {
	echo bp_get_activity_css_class();
}
	function bp_get_activity_css_class() {
		global $activities_template;

		return apply_filters( 'bp_get_activity_css_class', $activities_template->activity->component_name );
	}

function bp_activity_delete_link() {
	echo bp_get_activity_delete_link();
}
	function bp_get_activity_delete_link() {
		global $activities_template, $bp;

		return apply_filters( 'bp_get_activity_delete_link', '<a href="' . wp_nonce_url( $bp->root_domain . '/' . $bp->activity->slug . '/delete/' . $activities_template->activity->id, 'bp_activity_delete_link' ) . '" class="item-button delete-activity confirm">' . __( 'Delete', 'buddypress' ) . '</a>' );
	}

function bp_activity_filter_links( $args = false ) {
	echo bp_get_activity_filter_links( $args );
}
	function bp_get_activity_filter_links( $args = false ) {
		global $activities_template, $bp;

		$defaults = array(
			'style' => 'list'
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		/* Fetch the names of components that have activity recorded in the DB */
		$component_names = BP_Activity_Activity::get_recorded_component_names();

		if ( !$component_names )
			return false;

		foreach ( (array) $component_names as $component_name ) {
			if ( isset( $_GET['afilter'] ) && $component_name == $_GET['afilter'] )
				$selected = ' class="selected"';
			else
				unset($selected);

			$component_name = attribute_escape( $component_name );

			switch ( $style ) {
				case 'list':
					$tag = 'li';
					$before = '<li id="afilter-' . $component_name . '"' . $selected . '>';
					$after = '</li>';
				break;
				case 'paragraph':
					$tag = 'p';
					$before = '<p id="afilter-' . $component_name . '"' . $selected . '>';
					$after = '</p>';
				break;
				case 'span':
					$tag = 'span';
					$before = '<span id="afilter-' . $component_name . '"' . $selected . '>';
					$after = '</span>';
				break;
			}

			$link = add_query_arg( 'afilter', $component_name );
			$link = remove_query_arg( 'acpage' , $link );

			$link = apply_filters( 'bp_get_activity_filter_link_href', $link, $component_name );

			/* Make sure all core internal component names are translatable */
			$translatable_component_names = array( __( 'profile', 'buddypress'), __( 'friends', 'buddypress' ), __( 'groups', 'buddypress' ), __( 'status', 'buddypress' ), __( 'blogs', 'buddypress' ) );

			$component_links[] = $before . '<a href="' . attribute_escape( $link ) . '">' . ucwords( __( $component_name, 'buddypress' ) ) . '</a>' . $after;
		}

		$link = remove_query_arg( 'afilter' , $link );

		if ( isset( $_GET['afilter'] ) )
			$component_links[] = '<' . $tag . ' id="afilter-clear"><a href="' . attribute_escape( $link ) . '"">' . __( 'Clear Filter', 'buddypress' ) . '</a></' . $tag . '>';

 		return apply_filters( 'bp_get_activity_filter_links', implode( "\n", $component_links ) );
	}

function bp_sitewide_activity_feed_link() {
	echo bp_get_sitewide_activity_feed_link();
}
	function bp_get_sitewide_activity_feed_link() {
		global $bp;

		return apply_filters( 'bp_get_sitewide_activity_feed_link', site_url( $bp->activity->slug . '/feed' ) );
	}

function bp_activities_member_rss_link() {
	echo bp_get_activities_member_rss_link();
}
	function bp_get_activities_member_rss_link() {
		global $bp;

		if ( ( $bp->current_component == $bp->profile->slug ) || 'just-me' == $bp->current_action )
			return apply_filters( 'bp_get_activities_member_rss_link', $bp->displayed_user->domain . $bp->activity->slug . '/feed' );
		else
			return apply_filters( 'bp_get_activities_member_rss_link', $bp->displayed_user->domain . $bp->activity->slug . '/my-friends/feed' );
	}

/* Template tags for RSS feed output */

function bp_activity_feed_item_guid() {
	echo bp_get_activity_feed_item_guid();
}
	function bp_get_activity_feed_item_guid() {
		global $activities_template;

		return apply_filters( 'bp_get_activity_feed_item_guid', md5( $activities_template->activity->date_recorded . '-' . $activities_template->activity->content ) );
	}

function bp_activity_feed_item_title() {
	echo bp_get_activity_feed_item_title();
}
	function bp_get_activity_feed_item_title() {
		global $activities_template;

		$title = explode( '<span', $activities_template->activity->content );
		return apply_filters( 'bp_get_activity_feed_item_title', trim( strip_tags( html_entity_decode( $title[0] ) ) ) );
	}

function bp_activity_feed_item_link() {
	echo bp_get_activity_feed_item_link();
}
	function bp_get_activity_feed_item_link() {
		global $activities_template;

		return apply_filters( 'bp_get_activity_feed_item_link', $activities_template->activity->primary_link );
	}

function bp_activity_feed_item_date() {
	echo bp_get_activity_feed_item_date();
}
	function bp_get_activity_feed_item_date() {
		global $activities_template;

		return apply_filters( 'bp_get_activity_feed_item_date', $activities_template->activity->date_recorded );
	}

function bp_activity_feed_item_description() {
	echo bp_get_activity_feed_item_description();
}
	function bp_get_activity_feed_item_description() {
		global $activities_template;

		return apply_filters( 'bp_get_activity_feed_item_description', html_entity_decode( str_replace( '%s', '', $activities_template->activity->content ), ENT_COMPAT, 'UTF-8' ) );
	}

?>