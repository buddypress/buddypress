<?php get_header(); ?>

	<div id="content">

		<?php do_action( 'bp_before_activation_page' ) ?>

		<div class="page" id="activate-page">
		
			<div class="register bp_core_widget_welcome">
			
				<?php do_action( 'template_notices' ) ?>

				<?php if ( bp_account_was_activated() ) : ?>
		
					<h2 class="widgettitle"><?php _e( 'Account Activated', 'buddypress' ) ?></h2>
					
					<?php do_action( 'bp_before_activate_content' ) ?>
	
					<p><?php _e( 'Your account was activated successfully! You can now log in with the username and password you provided when you signed up.', 'buddypress' ) ?></p>
			
				
				<?php else : ?>
				
					<h2 class="widgettitle"><?php _e( 'Activate your Account', 'buddypress' ) ?></h2>
					
					<?php do_action( 'bp_before_activate_content' ) ?>
	
					<p><?php _e( 'Please provide a valid activation key.', 'buddypress' ) ?></p>
					
					<form action="" method="get" class="standard-form" id="activation-form">
						
						<label for="key"><?php _e( 'Activation Key:', 'buddypress' ) ?></label>
						<input type="text" name="key" id="key" value="" />
						
						<p class="submit">
							<input type="submit" name="submit" value="<?php _e( 'Activate', 'buddypress' ) ?> &rarr;" />
						</p>
						
					</form>
					
				<?php endif; ?>

				<?php do_action( 'bp_after_activate_content' ) ?>
			
			</div>
		
		</div>

		<?php do_action( 'bp_after_activation_page' ) ?>

	</div>
	
	<?php get_sidebar() ?>

<?php get_footer(); ?>
