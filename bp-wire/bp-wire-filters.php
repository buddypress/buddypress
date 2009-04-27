<?php

/* Apply WordPress defined filters */
add_filter( 'bp_get_wire_post_content', 'wp_filter_kses', 1 );
add_filter( 'bp_wire_post_content_before_save', 'wp_filter_kses', 1 );

add_filter( 'bp_get_wire_post_content', 'wptexturize' );

add_filter( 'bp_get_wire_post_content', 'convert_smilies', 2 );

add_filter( 'bp_get_wire_post_content', 'convert_chars' );

add_filter( 'bp_get_wire_post_content', 'wpautop' );

add_filter( 'bp_get_wire_post_content', 'stripslashes_deep' );

add_filter( 'bp_get_wire_post_content', 'make_clickable' );

?>