<?php

add_filter( 'the_status_content', 'wp_filter_kses', 1 );
add_filter( 'bp_status_content_before_save', 'wp_filter_kses', 1 );

add_filter( 'the_status_content', 'strip_tags' );
add_filter( 'bp_status_content_before_save', 'strip_tags' );

add_filter( 'the_status_content', 'force_balance_tags' );
add_filter( 'bp_status_content_before_save', 'force_balance_tags' );

add_filter( 'the_status_content', 'wptexturize' );
add_filter( 'the_status_content', 'convert_smilies' );
add_filter( 'the_status_content', 'convert_chars' );
add_filter( 'the_status_content', 'make_clickable' );
add_filter( 'the_status_content', 'stripslashes_deep' );

add_filter( 'bp_get_the_status', 'wpautop' );

?>