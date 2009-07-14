<?php bp_the_avatar() ?>

<div class="button-block">
	
	<?php if ( function_exists('bp_add_friend_button') ) : ?>
		
		<?php bp_add_friend_button() ?>
		
	<?php endif; ?>
	
	<?php if ( function_exists('bp_send_message_button') ) : ?>
		
		<?php bp_send_message_button() ?>
		
	<?php endif; ?>
	
</div>

<?php bp_custom_profile_sidebar_boxes() ?>