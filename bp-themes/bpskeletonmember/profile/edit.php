<?php
/*
 * /profile/edit.php
 * Displays the input fields for each profile field so that the user can
 * edit their profile.
 * 
 * Because the fields are built up dynamically by the site admin, it's not
 * possible for the HTML to be editable. All fields are wrapped in a class of
 * 'signup-field', with a label class of 'signup-label'. All fields and labels
 * are wrapped in a form with ID 'profile-edit-form'.
 *
 * Loaded on URL:
 * 'http://example.org/members/[username]/profile/edit/
 * 'http://example.org/members/[username]/profile/edit/group/[profile-group-id]
 */
?>

<?php get_header() ?>

<div class="content-header">
	
	<ul class="content-header-nav">
		<?php bp_profile_group_tabs(); ?>
	</ul>
	
</div>

<div id="main">
	
	<h2><?php printf( __( "Editing '%s'", "buddypress" ), bp_get_profile_group_name() ); ?></h2>
	
	<?php bp_edit_profile_form() ?>

</div>

<?php get_footer() ?>