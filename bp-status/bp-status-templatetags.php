<?php

function bp_the_status( $args = false ) {
	/***
	 * To support updating your status without JS, we can display the update form when the GET var "status" is set
	 * to "new".
	 */
	if ( 'new' == $_GET['status'] && is_user_logged_in() ) {
		load_template( TEMPLATEPATH . '/status/post-form.php' );
	} else {
		if ( 'clear' == $_GET['status'] && is_user_logged_in() )
			bp_status_clear_status();
		
		echo bp_get_the_status( $args );
	}
}
	function bp_get_the_status( $args = false ) {
		global $bp;
	
		$defaults = array(
			'user_id' => $bp->displayed_user->id,
			'clear_button_text' => __( 'Clear', 'buddypress' ),
			'new_button_text' => __( 'Update Your Status', 'buddypress' ),
			'no_anchor' => false
		);
	
		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );
		
		if ( !$user_id )
			$user_id = $bp->displayed_user->id;
		
		$status = get_usermeta( $user_id, 'bp_status' );
		
		if ( empty($status) )
			return bp_get_update_status_button( 'text=' . $new_button_text );
		
		$time_since = sprintf( __( '%s ago', 'buddypress' ), bp_core_time_since( $status['recorded_time'] ) );
		$content = apply_filters( 'the_status_content', $status['content'] );
		
		if ( !(int)$no_anchor && $user_id == $bp->loggedin_user->id )
			$content = '<a href="' . bp_core_get_user_domain( $user_id ) . '?status=new" id="status-new-status">' . $content . '</a>';

		$content .= ' <span class="time-since">' . $time_since . '</span>';
		$content .= ' ' . bp_get_clear_status_button( 'text=' . $clear_button_text );
		
		return apply_filters( 'bp_get_the_status', $content );
	}
	
function bp_update_status_button( $args = false ) {
	echo bp_get_update_status_button( $args );
}
	function bp_get_update_status_button( $args = false ) {
		global $bp;

		$defaults = array(
			'user_id' => false,
			'text' => __( 'Update Your Status', 'buddypress' )
		);
	
		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );
		
		if ( !$user_id )
			$user_id = $bp->displayed_user->id;
		
		if ( $user_id != $bp->loggedin_user->id )
			return false;
		
		return apply_filters( 'bp_get_update_status_button', '<div class="generic-button"><a href="' . bp_core_get_user_domain( $user_id ) . '?status=new" id="status-new-status">' . $text . '</a></div>' );
	}

function bp_clear_status_button( $args = false ) {
	echo bp_get_clear_status_button( $args );
}
	function bp_get_clear_status_button( $args = false ) {
		global $bp;

		$defaults = array(
			'user_id' => false,
			'text' => __( 'Clear', 'buddypress' )
		);
	
		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );
		
		if ( !$user_id )
			$user_id = $bp->displayed_user->id;
		
		if ( $user_id != $bp->loggedin_user->id )
			return false;
		
		return apply_filters( 'bp_get_clear_status_button', '<a href="' . bp_core_get_user_domain( $user_id ) . '?status=clear" id="status-clear-status">' . $text . '</a>' );
	}

function bp_status_form_action( $user_id = false ) {
	global $bp;
	
	if ( !$user_id )
		$user_id = $bp->loggedin_user->id;
		
	echo apply_filters( 'bp_status_form_action', bp_core_get_user_domain( $user_id ) . BP_STATUS_SLUG . '/add' );
}

function bp_the_status_css_class() {
	echo bp_get_the_status_css_class();
}
	function bp_get_the_status_css_class() {
		return ( bp_loggedin_user_id() == bp_displayed_user_id() ) ? 'status-editable' : 'status-display';
	}

?>