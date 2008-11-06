<div class="content-header">
	
</div>

<div id="content">	
	<?php if ( bp_has_groups() ) : while ( bp_groups() ) : bp_the_group(); ?>
	
	<div class="left-menu">
		<?php bp_group_avatar() ?>

		<?php bp_group_join_button() ?>

		<div class="info-group">
			<h4>Admins</h4>
			<?php bp_group_list_admins() ?>
		</div>
	</div>

	<div class="main-column">

		<div id="group-name">
			<h1><a href="<?php bp_group_permalink() ?>"><?php bp_group_name() ?></a></h1>
			<p class="status"><?php bp_group_type() ?></p>
		</div>

		<div class="info-group">
			<h4>Request Membership</h4>
			
			<?php do_action( 'template_notices' ) // (error/success feedback) ?>

			<?php if ( !bp_group_has_requested_membership() ) : ?>
				<p>You are requesting to become a member of the group '<?php bp_group_name() ?>'.</p>

				<form action="<?php bp_group_form_action('request-membership') ?>" method="post" name="request-membership-form" id="request-membership-form" class="standard-form">
					<label for="group-request-membership-comments">Comments (optional)</label>
					<textarea name="group-request-membership-comments" id="group-request-membership-comments"></textarea>

					<p><input type="submit" name="group-request-send" id="group-request-send" value="<?php _e( 'Send Request', 'buddypress' ) ?> &raquo;" />
				</form>
			<?php endif; ?>
			
		</div>
	
	</div>
	
	<?php endwhile; endif; ?>
</div>