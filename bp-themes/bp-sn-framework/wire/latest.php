<?php get_header() ?>

	<div class="content-header">

	</div>

	<div id="content">
		<?php do_action( 'template_notices' ) // (error/success feedback) ?>

		<?php do_action( 'bp_before_profile_wire_latest_content' ) ?>
	
		<div class="left-menu">
			<!-- Profile Menu (Avatar, Add Friend, Send Message buttons etc) -->
			<?php load_template( TEMPLATEPATH . '/profile/profile-menu.php' ) ?>
		</div>

		<div class="main-column">
			<!-- Profile Header (Name & Status) -->
			<?php load_template( TEMPLATEPATH . '/profile/profile-header.php' ) ?>
					
			<?php if ( function_exists('bp_wire_get_post_list') ) : ?>
				<?php bp_wire_get_post_list( bp_current_user_id(), bp_word_or_name( __( "Your Wire", 'buddypress' ), __( "%s's Wire", 'buddypress' ), true, false ), bp_word_or_name( __( "No one has posted to your wire yet.", 'buddypress' ), __( "No one has posted to %s's wire yet.", 'buddypress' ), true, false ), bp_profile_wire_can_post() ) ?>
			<?php endif; ?>
		</div>

		<?php do_action( 'bp_after_profile_wire_latest_content' ) ?>

	</div>

<?php get_footer() ?>