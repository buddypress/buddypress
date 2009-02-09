<?php get_header() ?>

<div class="content-header">

</div>

<div id="content">
	<?php do_action( 'template_notices' ) // (error/success feedback) ?>
	
	<div class="left-menu">
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
	</div>

	<div class="main-column">
		<?php bp_get_profile_header() ?>

		<?php if ( function_exists('bp_activity_get_list') ) : ?>
			<?php bp_activity_get_list( bp_current_user_id(), bp_word_or_name( __( "My Activity", 'buddypress' ), __( "%s's Activity", 'buddypress' ), true, false ), bp_word_or_name( __( "You haven't done anything yet.", 'buddypress' ), __( "%s hasn't done anything yet.", 'buddypress' ), true, false )  ) ?>
		<?php endif; ?>
		
	</div>

</div>

<?php get_footer() ?>