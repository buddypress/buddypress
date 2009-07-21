<?php

add_filter( 'bp_get_the_status', 'bp_status_filter_tags' );
add_filter( 'bp_get_the_status', 'force_balance_tags' );
add_filter( 'bp_get_the_status', 'wptexturize' );
add_filter( 'bp_get_the_status', 'convert_smilies' );
add_filter( 'bp_get_the_status', 'convert_chars' );
add_filter( 'bp_get_the_status', 'make_clickable' );
add_filter( 'bp_get_the_status', 'stripslashes_deep' );

function bp_status_filter_tags( $status ) {
	return apply_filters( 'bp_status_filter_tags', strip_tags( $status, '<a>' ) );
}

?>