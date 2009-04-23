<?php

/* Apply WordPress defined filters */
add_filter( 'bp_group_description', 'wptexturize' );
add_filter( 'bp_group_description_excerpt', 'wptexturize' );
add_filter( 'bp_group_news', 'wptexturize' );
add_filter( 'bp_group_name', 'wptexturize' );

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

add_filter( 'bp_group_description', 'wp_filter_kses', 1 );
add_filter( 'bp_group_description_excerpt', 'wp_filter_kses', 1 );
add_filter( 'bp_group_news', 'wp_filter_kses', 1 );
add_filter( 'bp_group_name', 'wp_filter_kses', 1 );
add_filter( 'groups_details_name_pre_save', 'wp_filter_kses', 1 );
add_filter( 'groups_details_description_pre_save', 'wp_filter_kses', 1 );
add_filter( 'groups_details_news_pre_save', 'wp_filter_kses', 1 );

add_filter( 'bp_group_description', 'stripslashes' );
add_filter( 'bp_group_description_excerpt', 'stripslashes' );
add_filter( 'bp_group_news', 'stripslashes' );
add_filter( 'bp_group_name', 'stripslashes' );

add_filter( 'groups_new_group_forum_desc', 'bp_create_excerpt' );

?>