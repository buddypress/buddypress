<?php
/*
 * /profile/profile-header.php
 * This file is loaded by most template files using the 'bp_get_profile_header()' template tag.
 * At the top of most pages, the users' full name is displayed using this file.
 * In the future you could include the users' current status update in this file, so it will 
 * show on these pages.
 *
 * Loaded by: Most template files.
 */
?>
<div id="profile-name">
	<h1 class="fn"><a href="<?php bp_user_link() ?>"><?php bp_user_fullname() ?></a></h1>
</div>