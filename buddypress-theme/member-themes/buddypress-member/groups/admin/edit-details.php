<?php get_header() ?>

<?php if ( bp_has_groups() ) : while ( bp_groups() ) : bp_the_group(); ?>

<div class="content-header">
	<ul class="content-header-nav">
		<?php bp_group_admin_tabs(); ?>
	</ul>
</div>

<div id="content">	
	
		<h2><?php _e( 'Edit Details', 'buddypress' ); ?></h2>
		
		<?php do_action( 'template_notices' ) // (error/success feedback) ?>
		
		<form action="<?php bp_group_admin_form_action('edit-details') ?>" name="edit-details-form" id="edit-details-form" class="standard-form" method="post">
			<label for="group-name">* <?php _e( 'Group Name', 'buddypress' ) ?></label>
			<input type="text" name="group-name" id="group-name" value="<?php bp_group_name() ?>" />
		
			<label for="group-desc">* <?php _e( 'Group Description', 'buddypress' ) ?></label>
			<textarea name="group-desc" id="group-desc"><?php bp_group_description_editable() ?></textarea>
		
			<label for="group-news"><?php _e( 'Recent News', 'buddypress' ) ?></label>
			<textarea name="group-news" id="group-news"><?php bp_group_news_editable() ?></textarea>
			
			<?php bp_custom_group_fields_editable() ?>
			
			<p>
				<label for="group-notifiy-members"><?php _e( 'Notify group members of changes via email', 'buddypress' ); ?></label>
				<input type="radio" name="group-notify-members" value="1" /> <?php _e( 'Yes', 'buddypress' ); ?>&nbsp;
				<input type="radio" name="group-notify-members" value="0" checked="checked" /> <?php _e( 'No', 'buddypress' ); ?>&nbsp;
			</p>
			
			<input type="hidden" name="group-id" id="group-id" value="<?php bp_group_id() ?>" />
			
			<p><input type="submit" value="<?php _e( 'Save Changes', 'buddypress' ) ?> &raquo;" id="save" name="save" /></p>
			
			<?php wp_nonce_field( 'groups_edit_group_details' ) ?>
		</form>
</div>

<?php endwhile; endif; ?>

<?php get_footer() ?>
