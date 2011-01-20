<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


function bp_displayed_user_email() {
	echo bp_get_displayed_user_email();
}
	function bp_get_displayed_user_email() {
		global $bp;

		// If displayed user exists, return email address
		if ( isset( $bp->displayed_user->userdata->user_email ) )
			$retval = $bp->displayed_user->userdata->user_email;
		else
			$retval = '';

		return apply_filters( 'bp_get_displayed_user_email', esc_attr( $retval ) );
	}

?>
