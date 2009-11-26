<?php do_action( 'bp_before_profile_header_content' ) ?>

<div id="profile-name">
	<h1 class="fn"><a href="<?php bp_user_link() ?>"><?php bp_user_fullname() ?></a></h1>

	<?php do_action( 'bp_profile_header_content' ) ?>
</div>

<?php do_action( 'bp_after_profile_header_content' ) ?>