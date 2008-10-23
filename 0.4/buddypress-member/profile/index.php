<div class="content-header">
	<?php bp_last_activity() ?>
</div>

<div id="content">
	<?php do_action( 'template_notices' ) // (error/success feedback) ?>
	
	<div class="left-menu">
		<?php bp_the_avatar() ?>
		
		<?php if ( function_exists('bp_add_friend_button') ) : ?>
			<?php bp_add_friend_button() ?>
		<?php endif; ?>
		
		<?php //bp_user_groups() ?>
	</div>

	<div class="main-column">
		<?php bp_get_profile_header() ?>
		
		<?php if ( function_exists('xprofile_get_profile') ) : ?>
			<?php xprofile_get_profile() ?>
		<?php else : ?>
			<?php bp_core_get_wp_profile() ?>
		<?php endif; ?>
		
		<?php if ( function_exists('bp_activity_get_list') ) : ?>
			<?php bp_activity_get_list( bp_current_user_id(), bp_word_or_name( __( "My Activity", 'buddypress' ), __( "%s's Activity", 'buddypress' ), true, false ), 5 ) ?>
		<?php endif; ?>
		
		<?php if ( function_exists('bp_groups_random_groups') ) : ?>
			<?php bp_groups_random_groups() ?>
		<?php endif; ?>
		
		<?php if ( function_exists('bp_friends_random_friends') ) : ?>
			<?php bp_friends_random_friends() ?>
		<?php endif; ?>

		<?php if ( function_exists('bp_wire_get_post_list') ) : ?>
			<?php bp_wire_get_post_list( bp_current_user_id(), bp_word_or_name( __( "My Wire", 'buddypress' ), __( "%s's Wire", 'buddypress' ), true, false ), bp_word_or_name( __( "No one has posted to your wire yet.", 'buddypress' ), __( "No one has posted to %s's wire yet.", 'buddypress' ), true, false ) ) ?>
		<?php endif; ?>
	</div>

</div>