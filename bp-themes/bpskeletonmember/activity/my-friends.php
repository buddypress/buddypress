<?php
/*
 * /activity/my-friends.php
 * Displays the activity stream for the currently displayed users' friends. 
 *
 * Loads: '/activity/activity-list.php' (via the bp_activity_get_list() template tag)
 * 
 * Loaded on URL:
 * 'http://example.org/members/[username]/activity/my-friends/
 */
?>

<?php get_header() ?>

<div id="main">
	<?php do_action( 'template_notices' ) ?>
	
	<div class="page-menu">
		
		<?php bp_the_avatar() ?>
		
		<?php if ( function_exists( 'bp_add_friend_button' ) ) : ?>
			<?php bp_add_friend_button() ?>
		<?php endif; ?>

	</div>

	<div class="main-column">
		
		<?php bp_get_profile_header() ?>
	
		<?php if ( function_exists('bp_activity_get_list') ) : ?>
			
			<?php
				bp_activity_get_list( 
					bp_current_user_id(), // The ID of the user to get the activity stream for.
					__( 'My Friends Activity', 'buddypress' ), // The title of the activity stream.
					__( "Your friends haven't done anything yet.", 'buddypress' ) // What do display when there is no activity.
				)
			?>
			
		<?php endif; ?>
			
	</div>

</div>

<?php get_footer() ?>

