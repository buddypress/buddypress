<?php
/*
 * /activity/just-me.php
 * Displays the activity stream for the currently displayed user. 
 *
 * Loads: '/activity/activity-list.php' (via the bp_activity_get_list() template tag)
 * 
 * Loaded on URL:
 * 'http://example.org/members/[username]/activity/
 * 'http://example.org/members/[username]/activity/just-me/
 */
?>

<?php get_header() ?>

<div id="main">
	<?php do_action( 'template_notices' ) // (error/success feedback) ?>
	
	<div class="page-menu">
		<?php bp_the_avatar() ?>
		
		<div class="button-block">
			<?php if ( function_exists( 'bp_add_friend_button' ) ) : ?>
				<?php bp_add_friend_button() ?>
			<?php endif; ?>
			
			<?php if ( function_exists( 'bp_send_message_button' ) ) : ?>
				<?php bp_send_message_button() ?>
			<?php endif; ?>
		</div>

		<?php bp_custom_profile_sidebar_boxes() ?>
	</div>

	<div class="main-column">
		<?php bp_get_profile_header() ?>

		<?php if ( function_exists( 'bp_activity_get_list' ) ) : ?>
			
			<?php 
				bp_activity_get_list( 
					bp_current_user_id(), // The ID of the user to get activity for.
					bp_word_or_name( __( "My Activity", 'buddypress' ), __( "%s's Activity", 'buddypress' ), true, false ), // The title of the activity list.
					bp_word_or_name( __( "You haven't done anything yet.", 'buddypress' ), __( "%s hasn't done anything yet.", 'buddypress' ), true, false ) // What to show when there is no activity.
				);
			?>
		
		<?php endif; ?>
		
	</div>

</div>

<?php get_footer() ?>