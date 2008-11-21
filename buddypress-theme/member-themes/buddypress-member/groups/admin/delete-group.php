<?php if ( bp_has_groups() ) : while ( bp_groups() ) : bp_the_group(); ?>

<div class="content-header">
	<ul class="content-header-nav">
		<?php bp_group_admin_tabs(); ?>
	</ul>
</div>

<div id="content">	
	
		<h2><?php _e( 'Delete Group', 'buddypress' ); ?></h2>
		
		<?php do_action( 'template_notices' ) // (error/success feedback) ?>
		
		<form action="<?php bp_group_admin_form_action('delete-group') ?>" name="group-delete-form" id="group-delete-form" class="standard-form" method="post">
			
			<div id="message" class="info">
				<p><?php _e( 'WARNING: Deleting this group will completely remove ALL content associated with it. There is no way back, please be careful with this option.', 'buddypress' ); ?></p>
			</div>
			
			<input type="checkbox" name="delete-group-understand" id="delete-group-understand" value="1" onclick="if(this.checked) { document.getElementById('delete-group-button').disabled = ''; } else { document.getElementById('delete-group-button').disabled = 'disabled'; }" /> <?php _e( 'I understand the consequences of deleting this group.', 'buddypress' ); ?>
			<p><input type="submit" disabled="disabled" value="<?php _e( 'Delete Group', 'buddypress' ) ?> &raquo;" id="delete-group-button" name="delete-group-button" /></p>
		
			<input type="hidden" name="group-id" id="group-id" value="<?php bp_group_id() ?>" />
		</form>
</div>

<?php endwhile; endif; ?>