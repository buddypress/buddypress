<?php

function bp_users_screen_index() {
	global $bp;

	if ( !bp_is_user() && bp_is_current_component( 'members' ) ) {
		$bp->is_directory = true;

		do_action( 'bp_users_screen_index' );

		bp_core_load_template( apply_filters( 'bp_users_screen_index', 'members/index' ) );
	}
}
add_action( 'wp', 'bp_users_screen_index', 2 );


?>
