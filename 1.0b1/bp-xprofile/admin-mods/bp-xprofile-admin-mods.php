<?php

function xprofile_add_account_tab() {
	global $submenu;

	$submenu['users.php'][10][0] = __( 'Account Settings', 'buddypress' );
	$submenu['users.php'][10][2] = 'admin.php?page=bp-xprofile/admin-mods/bp-xprofile-account-tab.php';
}
add_action( 'admin_menu', 'xprofile_add_account_tab' );

?>