<?php
/*
 * /profile/index.php
 * Displays a users profile, it's the first page you hit when visiting 'example.org/members/username/'.
 * The profile page will fetch and load a variety of content, but you can pick and chose what you want
 * to display by modifying each section.
 *
 * It's worth noting that calls to template tags such as 'xprofile_get_profile', 'bp_activity_get_list'
 * and 'bp_groups_random_groups' could be replaced by custom BuddyPress loop calls. That is where the
 * real customization power comes from. Take a look at the following codex page to see how you can
 * add in custom loop calls to return specific data that you want on profile pages:
 *
 * http://codex.buddypress.org/developer-docs/custom-buddypress-loops/
 *
 * Loads: 'profile/profile-menu.php' (The users' avatar and add friend button)
 *		  'profile/profile-loop.php' (via the xprofile_get_profile() template tag)
 *        'activity/just-me.php' (via the bp_activity_get_list() template tag)
 *		  'wire/post-list.php' (via the bp_wire_get_post_list() template tag)
 *
 * Loaded on URL:
 * 'http://example.org/members/[username]/
 * 'http://example.org/members/[username]/profile/
 */
?>
<?php get_header() ?>

<div class="content-header">
	<?php bp_last_activity() ?>
</div>

<div id="main" class="vcard">
	<?php do_action( 'template_notices' ) ?>
	
	<div class="page-menu">
		<?php load_template( TEMPLATEPATH . '/profile/profile-menu.php' ) ?>
	</div>

	<div class="main-column">
		<?php bp_get_profile_header() ?>
	
		<?php if ( function_exists('xprofile_get_profile') ) : ?>
			
			<?php xprofile_get_profile() ?>
			
		<?php endif; ?>
				
		<?php if ( function_exists('bp_activity_get_list') ) : ?>
			
			<?php bp_activity_get_list( 
					bp_current_user_id(), 
					bp_word_or_name( __( "My Activity", 'buddypress' ), __( "%s's Activity", 'buddypress' ), true, false ), 
					bp_word_or_name( __( "You haven't done anything recently.", 'buddypress' ), __( "%s has not done anything recently.", 'buddypress' ), true, false ), 
					5 /* Max number of items to show */ 
				  ) 
			?>
		
		<?php endif; ?>
	
		<?php if ( function_exists('bp_groups_random_groups') ) : ?>
			
			<?php bp_groups_random_groups() ?>
		
		<?php endif; ?>
	
		<?php if ( function_exists('bp_friends_random_friends') ) : ?>
		
			<?php bp_friends_random_friends() ?>
		
		<?php endif; ?>

		<?php bp_custom_profile_boxes() ?>

		<?php if ( function_exists('bp_wire_get_post_list') ) : ?>
			
			<?php bp_wire_get_post_list( 
					bp_current_user_id(), 
					bp_word_or_name( __( "My Wire", 'buddypress' ), __( "%s's Wire", 'buddypress' ), true, false ),
					bp_word_or_name( __( "No one has posted to your wire yet.", 'buddypress' ), __( "No one has posted to %s's wire yet.", 'buddypress' ), true, false), 
					bp_profile_wire_can_post()
				   ) 
			?>

		<?php endif; ?>
	</div>

</div>

<?php get_footer() ?>