<?php if ( bp_has_groups() ) : while ( bp_groups() ) : bp_the_group(); ?>

<div class="content-header">
	<ul class="content-header-nav">
		<?php bp_group_admin_tabs(); ?>
	</ul>
</div>

<div id="content">	
	
		<h2>Membership Requests</h2>
		
		<?php do_action( 'template_notices' ) // (error/success feedback) ?>
		
		<?php if ( bp_group_has_membership_requests() ) : ?>
			<ul id="request-list" class="item-list">
			<?php while ( bp_group_membership_requests() ) : bp_group_the_membership_request(); ?>
				<li>
					<?php bp_group_request_user_avatar_thumb() ?>
					<h4><?php bp_group_request_user_link() ?> <span class="comments"><?php bp_group_request_comment() ?></span></h4>
					<span class="activity"><?php bp_group_request_time_since_requested() ?></span>
					<div class="action">
						<a href="<?php bp_group_request_accept_link() ?>" id="accept">Accept</a> 
						<a href="<?php bp_group_request_reject_link() ?>" id="reject">Reject</a> 
					</div>
				</li>
			<?php endwhile; ?>
			</ul>
		<?php else: ?>

			<div id="message" class="info">
				<p>There are no pending membership requests.</p>
			</div>

		<?php endif;?>
</div>

<?php endwhile; endif; ?>