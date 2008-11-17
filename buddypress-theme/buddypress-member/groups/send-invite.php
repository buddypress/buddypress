<div class="content-header">
	
</div>

<div id="content">
	<div class="pagination-links" id="pag">
		<?php bp_group_pagination() ?>
	</div>
	
	<?php if ( bp_has_groups() ) : while ( bp_groups() ) : bp_the_group(); ?>
		
		<h2><a href="<?php bp_group_permalink() ?>"><?php bp_group_name() ?></a> &raquo; Send Invites</h2>
	
		<?php if ( bp_has_friends_to_invite() ) : ?>
			<form action="<?php bp_group_send_invite_form_action() ?>" method="post" id="send-invite-form">
				<?php bp_group_send_invite_form() ?>
			</form>
		<?php else : ?>
			<div id="message" class="info">
				<p>You either need to build up your friends list, or your friends have already been invited or are current members.</p>
			</div>
		<?php endif; ?>
	<?php endwhile; endif; ?>
</div>