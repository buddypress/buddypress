<?php

function bp_users_can_edit_settings() {
	if ( bp_is_my_profile() )
		return true;

	if ( is_super_admin() )
		return true;

	return false;
}

?>
