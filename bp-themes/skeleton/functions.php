<?php
/*
 * /functions.php
 * Use this file to add custom PHP functions that your theme will use. You might want to
 * override default BuddyPress settings or functionality by using actions and filters.
 * You might also want to define custom admin menus, or provide new functionality specifically
 * for your theme in this file.
 */
?>
<?php

/**
 * By default, BuddyPress components provide some basic structural CSS to help you out with a base to work from.
 * You may find you don't want these styles enabled with your theme and would rather work from scratch. If this
 * is the case, you can uncomment the following lines, and component structure CSS will be disabled.
 */

// remove_action( 'bp_styles', 'bp_activity_add_structure_css' );
// remove_action( 'bp_styles', 'bp_blogs_add_structure_css' );
// remove_action( 'bp_styles', 'bp_core_add_structure_css' );
// remove_action( 'bp_styles', 'friends_add_structure_css' );
// remove_action( 'bp_styles', 'groups_add_structure_css' );
// remove_action( 'bp_styles', 'messages_add_structure_css' );
// remove_action( 'bp_styles', 'bp_wire_add_structure_css' );
// remove_action( 'bp_styles', 'xprofile_add_structure_css' );


/* Hook for custom theme functions via plugins */
do_action( 'bp_member_theme_functions' );

?>