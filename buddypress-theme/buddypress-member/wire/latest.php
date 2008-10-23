<div class="content-header">

</div>

<div id="content">
	<?php do_action( 'template_notices' ) // (error/success feedback) ?>
	
	<div class="left-menu">
		<?php bp_the_avatar() ?>
		
		<?php if ( bp_exists('friends') ) : ?>
			<?php bp_add_friend_button() ?>
		<?php endif; ?>
		
		<?php //bp_user_groups() ?>
	</div>

	<div class="main-column">
		<?php bp_get_profile_header() ?>
		
		<?php if ( function_exists('bp_wire_get_post_list') ) : ?>
			<?php bp_wire_get_post_list( bp_current_user_id(), bp_my_or_name( true, false ) . ' Wire', 'No one has posted to ' . bp_your_or_name( false, false ) . ' wire yet.' ) ?>
		<?php endif; ?>
		
	</div>

</div>