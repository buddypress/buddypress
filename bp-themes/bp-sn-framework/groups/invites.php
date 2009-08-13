<?php get_header() ?>

	<div class="content-header">

	</div>

	<div id="content">
		<h2><?php _e( 'Group Invites', 'buddypress' ) ?></h2>

		<?php do_action( 'template_notices' ) // (error/success feedback) ?>

		<?php do_action( 'bp_before_group_invites_content' ) ?>

		<?php if ( bp_has_groups() ) : ?>
			
			<ul id="group-list" class="invites item-list">

				<?php while ( bp_groups() ) : bp_the_group(); ?>
					
					<li>
						<?php bp_group_avatar_thumb() ?>
						<h4><a href="<?php bp_group_permalink() ?>"><?php bp_group_name() ?></a><span class="small"> - <?php printf( __( '%s members', 'buddypress' ), bp_group_total_members( false ) ) ?></span></h4>
						<p class="desc">
							<?php bp_group_description_excerpt() ?>
						</p>
						
						<?php do_action( 'bp_group_invites_item' ) ?>
						
						<div class="action">
					
							<div class="generic-button accept">
								<a href="<?php bp_group_accept_invite_link() ?>"><?php _e( 'Accept', 'buddypress' ) ?></a> 
							</div>
					
							 &nbsp; 

							<div class="generic-button reject">
								<a href="<?php bp_group_reject_invite_link() ?>"><?php _e( 'Reject', 'buddypress' ) ?></a> 
							</div>

							<?php do_action( 'bp_group_invites_item_action' ) ?>
					
						</div>
						<hr />
					</li>

				<?php endwhile; ?>
			</ul>
			
		<?php else: ?>

			<div id="message" class="info">
				<p><?php _e( 'You have no outstanding group invites.', 'buddypress' ) ?></p>
			</div>

		<?php endif;?>

		<?php do_action( 'bp_after_group_invites_content' ) ?>

	</div>

<?php get_footer() ?>