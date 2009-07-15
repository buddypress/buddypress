<?php

/* Apply WordPress defined filters */
add_filter( 'bp_get_group_description', 'wptexturize' );
add_filter( 'bp_get_group_description_excerpt', 'wptexturize' );
add_filter( 'bp_get_the_site_group_description', 'wptexturize' );
add_filter( 'bp_get_the_site_group_description_excerpt', 'wptexturize' );
add_filter( 'bp_get_group_news', 'wptexturize' );
add_filter( 'bp_get_group_name', 'wptexturize' );
add_filter( 'bp_get_the_site_group_name', 'wptexturize' );

add_filter( 'bp_get_group_description', 'convert_smilies' );
add_filter( 'bp_get_group_description_excerpt', 'convert_smilies' );
add_filter( 'bp_get_group_news', 'convert_smilies' );
add_filter( 'bp_get_the_site_group_description', 'convert_smilies' );
add_filter( 'bp_get_the_site_group_description_excerpt', 'convert_smilies' );

add_filter( 'bp_get_group_description', 'convert_chars' );
add_filter( 'bp_get_group_description_excerpt', 'convert_chars' );
add_filter( 'bp_get_group_news', 'convert_chars' );
add_filter( 'bp_get_group_name', 'convert_chars' );
add_filter( 'bp_get_the_site_group_name', 'convert_chars' );
add_filter( 'bp_get_the_site_group_description', 'convert_chars' );
add_filter( 'bp_get_the_site_group_description_excerpt', 'convert_chars' );

add_filter( 'bp_get_group_description', 'wpautop' );
add_filter( 'bp_get_group_description_excerpt', 'wpautop' );
add_filter( 'bp_get_group_news', 'wpautop' );
add_filter( 'bp_get_the_site_group_description', 'wpautop' );
add_filter( 'bp_get_the_site_group_description_excerpt', 'wpautop' );

add_filter( 'bp_get_group_description', 'make_clickable' );
add_filter( 'bp_get_group_description_excerpt', 'make_clickable' );
add_filter( 'bp_get_group_news', 'make_clickable' );

add_filter( 'bp_get_group_name', 'wp_filter_kses', 1 );
add_filter( 'bp_get_group_permalink', 'wp_filter_kses', 1 );
add_filter( 'bp_get_group_description', 'wp_filter_kses', 1 );
add_filter( 'bp_get_group_description_excerpt', 'wp_filter_kses', 1 );
add_filter( 'bp_get_group_news', 'wp_filter_kses', 1 );
add_filter( 'bp_get_the_site_group_name', 'wp_filter_kses', 1 );
add_filter( 'bp_get_the_site_group_description', 'wp_filter_kses', 1 );
add_filter( 'bp_get_the_site_group_description_excerpt', 'wp_filter_kses', 1 );
add_filter( 'groups_group_name_before_save', 'wp_filter_kses', 1 );
add_filter( 'groups_group_description_before_save', 'wp_filter_kses', 1 );
add_filter( 'groups_group_news_before_save', 'wp_filter_kses', 1 );

add_filter( 'bp_get_group_description', 'stripslashes' );
add_filter( 'bp_get_group_description_excerpt', 'stripslashes' );
add_filter( 'bp_get_group_news', 'stripslashes' );
add_filter( 'bp_get_group_name', 'stripslashes' );

add_filter( 'groups_new_group_forum_desc', 'bp_create_excerpt' );

add_filter( 'groups_group_name_before_save', 'force_balance_tags' );
add_filter( 'groups_group_description_before_save', 'force_balance_tags' );
add_filter( 'groups_group_news_before_save', 'force_balance_tags' );

?>