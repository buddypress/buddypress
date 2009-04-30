<?php
/*
 * /groups/admin/membership-requests.php
 * The settings screen for accepting/rejecting group membership requests for a private group.
 *
 * Loaded on URL:
 * 'http://example.org/groups/[group-slug]/admin/membership-requests/
 */
?>

<?php get_header() ?>

<?php if ( bp_has_groups() ) : while ( bp_groups() ) : bp_the_group(); ?>

	<div class="content-header">
		<ul class="content-header-nav">
			<?php bp_group_admin_tabs(); ?>
		</ul>
	</div>

	<div id="main">	

		<h2><?php _e( 'Membership Requests', 'buddypress' ); ?></h2>
	
		<?php do_action( 'template_notices' ) ?>
	
		<?php if ( bp_group_has_membership_requests() ) : ?>
			
			<ul id="request-list" class="item-list">
			<?php while ( bp_group_membership_requests() ) : bp_group_the_membership_request(); ?>
				
				<li>
					<?php bp_group_request_user_avatar_thumb() ?>
					<h4><?php bp_group_request_user_link() ?> <span class="comments"><?php bp_group_request_comment() ?></span></h4>
					
					<span class="activity"><?php bp_group_request_time_since_requested() ?></span>
					
					<div class="action">
						<div class="generic-button accept">
							<a href="<?php bp_group_request_accept_link() ?>"><?php _e( 'Accept', 'buddypress' ); ?></a> 
						</div>

						<div class="generic-button reject">
							<a href="<?php bp_group_request_reject_link() ?>"><?php _e( 'Reject', 'buddypress' ); ?></a> 
						</div>
					</div>
				</li>
				
			<?php endwhile; ?>
			</ul>
			
		<?php else: ?>

			<div id="message" class="info">
				<p><?php _e( 'There are no pending membership requests.', 'buddypress' ); ?></p>
			</div>

		<?php endif;?>
		
	</div>

<?php endwhile; endif; ?>

<?php get_footer() ?>
