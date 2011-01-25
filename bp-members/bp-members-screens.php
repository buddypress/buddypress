<?php

function bp_members_screen_index() {
	global $bp;

	if ( !bp_is_user() && bp_is_current_component( 'members' ) ) {
		$bp->is_directory = true;

		do_action( 'bp_members_screen_index' );

		bp_core_load_template( apply_filters( 'bp_members_screen_index', 'members/index' ) );
	}
}
add_action( 'bp_screens', 'bp_members_screen_index' );


?>
