<?php

/* Apply WordPress defined filters */
add_filter( 'bp_wire_post_content', 'wptexturize' );

add_filter( 'bp_wire_post_content', 'convert_smilies' );

add_filter( 'bp_wire_post_content', 'convert_chars' );

add_filter( 'bp_wire_post_content', 'wpautop' );

add_filter( 'bp_wire_post_content', 'stripslashes_deep' );

add_filter( 'bp_wire_post_content', 'make_clickable' );

?>