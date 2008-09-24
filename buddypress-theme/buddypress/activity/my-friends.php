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
		<div id="profile-name">
			<h1><a href="<?php bp_user_link() ?>"><?php bp_user_fullname() ?></a></h1>
			<p class="status"><?php bp_user_status() ?></p>
		</div>

		<?php if ( function_exists('bp_activity_get_list') ) : ?>
			<?php bp_activity_get_list( bp_current_user_id(), __('My Friends Activity') ) ?>
		<?php endif; ?>
			
	</div>

</div>