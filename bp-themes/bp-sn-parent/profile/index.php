<?php get_header() ?>

<div class="content-header">
	<?php bp_last_activity() ?>
</div>

<div id="content" class="vcard">

	<?php do_action( 'template_notices' ) // (error/success feedback) ?>

	<?php do_action( 'bp_before_profile_content' ) ?>

	<div class="left-menu">
		<!-- Profile Menu (Avatar, Add Friend, Send Message buttons etc) -->
		<?php locate_template( array( 'profile/profile-menu.php' ), true ) ?>
	</div>

	<div class="main-column">
		<div class="inner-tube">

			<?php /* Profile Header (Name & Status) */ ?>
			<?php locate_template( array( 'profile/profile-header.php' ), true ) ?>

			<?php /* Profile Data Loop */ ?>
			<?php locate_template( array( 'profile/profile-loop.php' ), true ) ?>

			<?php do_action( 'bp_before_profile_activity_loop' ) ?>

		</div>

	<?php do_action( 'bp_after_profile_content' ) ?>

	</div>

</div>

<?php get_footer() ?>