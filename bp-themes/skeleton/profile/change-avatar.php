<?php
/*
 * /profile/change-avatar.php
 * Displays the upload file form so a user can upload a new avatar image.
 * The form has an ID of 'avatar-upload'.
 *
 * Loaded on URL:
 * 'http://example.org/members/[username]/profile/change-avatar/
 */
?>
<?php get_header() ?>

<div id="main">
	
	<h2><?php _e( 'Change Avatar', 'buddypress' ) ?></h2>
	
	<?php bp_avatar_upload_form() ?>

</div>

<?php get_footer() ?>