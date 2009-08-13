<?php

add_filter( 'the_status_content', 'bp_status_filter_tags' );
add_filter( 'the_status_content', 'force_balance_tags' );
add_filter( 'the_status_content', 'wptexturize' );
add_filter( 'the_status_content', 'convert_smilies' );
add_filter( 'the_status_content', 'convert_chars' );
add_filter( 'the_status_content', 'make_clickable' );
add_filter( 'the_status_content', 'stripslashes_deep' );

add_filter( 'bp_get_the_status', 'wpautop' );

function bp_status_filter_tags( $status ) {
	return apply_filters( 'bp_status_filter_tags', strip_tags( $status, '<a>' ) );
}

?>