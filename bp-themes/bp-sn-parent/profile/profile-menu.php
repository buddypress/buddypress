<?php do_action( 'bp_before_profile_menu' ) ?>

<?php bp_displayed_user_avatar( 'type=full' ) ?>

<div class="button-block">
	
	<?php if ( function_exists('bp_add_friend_button') ) : ?>
		
		<?php bp_add_friend_button() ?>
		
	<?php endif; ?>
	
	<?php if ( function_exists('bp_send_message_button') ) : ?>
		
		<?php bp_send_message_button() ?>
		
	<?php endif; ?>
	
	<?php do_action( 'bp_before_profile_menu_buttons' ) ?>

</div>

<?php do_action( 'bp_after_profile_menu' ); /* Deprecated -> */ bp_custom_profile_sidebar_boxes(); ?>
