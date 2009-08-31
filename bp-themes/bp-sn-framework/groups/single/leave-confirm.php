<?php get_header() ?>

	<div class="content-header">
	
	</div>

	<div id="content">	

		<?php if ( bp_has_groups() ) : while ( bp_groups() ) : bp_the_group(); ?>
	
			<?php do_action( 'bp_before_group_leave_confirm_content' ) ?>
			
			<div class="left-menu">
				<?php locate_template( array( '/groups/single/menu.php' ), true ) ?>
			</div>

			<div class="main-column">
				<div class="inner-tube">

					<?php do_action( 'bp_before_group_name' ) ?>

					<div id="group-name">
						<h1><a href="<?php bp_group_permalink() ?>"><?php bp_group_name() ?></a></h1>
						<p class="status"><?php bp_group_type() ?></p>
					</div>

					<?php do_action( 'bp_after_group_name' ) ?>

					<div class="bp-widget">
						<h4><?php _e( 'Confirm Leave Group', 'buddypress' ); ?></h4>
						<h3><?php _e( 'Are you sure you want to leave this group?', 'buddypress' ); ?></h3>
	
						<p>
							<a href="<?php bp_group_leave_confirm_link() ?>"><?php _e( "Yes, I'd like to leave this group.", 'buddypress' ) ?></a> | 
							<a href="<?php bp_group_leave_reject_link() ?>"><?php _e( "No, I'll stay!", 'buddypress' ) ?></a>
						</p>
						
						<?php do_action( 'bp_group_leave_confirm_content' ) ?>
					</div>
		
				</div>
			</div>

			<?php do_action( 'bp_after_group_leave_confirm_content' ) ?>
	
		<?php endwhile; endif; ?>
		
	</div>

<?php get_footer() ?>