<?php

/* Apply WordPress defined filters */
add_filter( 'bp_activity_content', 'wptexturize' );

add_filter( 'bp_activity_content', 'convert_smilies' );

add_filter( 'bp_activity_content', 'convert_chars' );

add_filter( 'bp_activity_content', 'wpautop' );

add_filter( 'bp_activity_content', 'stripslashes_deep' );

add_filter( 'bp_activity_content', 'make_clickable' );

?>