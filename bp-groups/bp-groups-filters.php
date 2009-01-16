<?php

/* Apply WordPress defined filters */
add_filter( 'bp_group_description', 'wptexturize' );
add_filter( 'bp_group_description_excerpt', 'wptexturize' );
add_filter( 'bp_group_news', 'wptexturize' );
add_filter( 'bp_group_name', 'wptexturize' );

add_filter( 'bp_the_topic_post_content', 'make_clickable' );
add_filter( 'bp_group_description', 'convert_smilies' );
add_filter( 'bp_group_description_excerpt', 'convert_smilies' );
add_filter( 'bp_group_news', 'convert_smilies' );

add_filter( 'bp_group_description', 'convert_chars' );
add_filter( 'bp_group_description_excerpt', 'convert_chars' );
add_filter( 'bp_group_news', 'convert_chars' );
add_filter( 'bp_group_name', 'convert_chars' );

add_filter( 'bp_group_description', 'wpautop' );
add_filter( 'bp_group_news', 'wpautop' );

add_filter( 'bp_group_description', 'make_clickable' );
add_filter( 'bp_group_description_excerpt', 'make_clickable' );
add_filter( 'bp_group_news', 'make_clickable' );

?>