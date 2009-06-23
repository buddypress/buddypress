<?php

add_filter( 'bp_get_the_status', 'wp_filter_kses', 1 );
add_filter( 'bp_get_the_status', 'wptexturize' );
add_filter( 'bp_get_the_status', 'convert_smilies' );
add_filter( 'bp_get_the_status', 'convert_chars' );
add_filter( 'bp_get_the_status', 'make_clickable' );
add_filter( 'bp_get_the_status', 'stripslashes_deep' );

?>