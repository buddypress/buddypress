<?php get_header() ?>

<div class="content-header">

</div>

<div id="content">
	<?php do_action( 'template_notices' ) // (error/success feedback) ?>
	
	<div class="left-menu">
		<?php bp_the_avatar() ?>
		
		<?php if ( bp_exists('friends') ) : ?>
			<?php bp_add_friend_button() ?>
		<?php endif; ?>

	</div>

	<div class="main-column">
	<?php bp_get_profile_header() ?>
	
		<?php if ( function_exists('bp_activity_get_list') ) : ?>
			<?php bp_activity_get_list( bp_current_user_id(), __( 'My Friends Activity', 'buddypress' ), __( "Your friends haven't done anything yet.", 'buddypress' ) ) ?>
		<?php endif; ?>
			
	</div>

</div>

<?php get_footer() ?>

