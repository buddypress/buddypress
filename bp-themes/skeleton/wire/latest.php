<?php
/*
 * /wire/latest.php
 * Shows the latest wire posts for a user. This is specific to the profile component.
 *
 * Loaded on URL:
 * 'http://example.org/members/[username]/wire/
 * 'http://example.org/members/[username]/wire/all-posts/
 */
?>
<?php get_header() ?>

<div id="main">
	<?php do_action( 'template_notices' ) ?>
	
	<div class="page-menu">
		<?php load_template( TEMPLATEPATH . '/profile/profile-menu.php' ) ?>
	</div>

	<div class="main-column">
		<?php bp_get_profile_header() ?>
		
		<?php if ( function_exists('bp_wire_get_post_list') ) : ?>
			
			<?php bp_wire_get_post_list( bp_current_user_id(), bp_word_or_name( __( "Your Wire", 'buddypress' ), __( "%s's Wire", 'buddypress' ), true, false ), bp_word_or_name( __( "No one has posted to your wire yet.", 'buddypress' ), __( "No one has posted to %s's wire yet.", 'buddypress' ), true, false ), bp_profile_wire_can_post() ) ?>
		
		<?php endif; ?>
	</div>

</div>

<?php get_footer() ?>