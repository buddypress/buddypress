<?php get_header() ?>

<?php if ( bp_has_groups() ) : while ( bp_groups() ) : bp_the_group(); ?>

<div class="content-header">
	<ul class="content-header-nav">
		<?php bp_group_admin_tabs(); ?>
	</ul>
</div>

<div id="content">	
	
		<h2><?php _e( 'Group Avatar', 'buddypress' ); ?></h2>
		
		<?php do_action( 'template_notices' ) // (error/success feedback) ?>
		
		<form action="<?php bp_group_admin_form_action('group-avatar') ?>" name="group-avatar-form" id="group-avatar-form" class="standard-form" method="post" enctype="multipart/form-data">
			
			<div class="left-menu">
				<?php bp_group_current_avatar() ?>
			</div>
			
			<div class="main-column">
				<p><?php _e("Upload an image to use as an avatar for this group. The image will be shown on the main group page, and in search results.", 'buddypress') ?></p>
				
				<?php bp_group_avatar_edit_form() ?>
			</div>

			<input type="hidden" name="group-id" id="group-id" value="<?php bp_group_id() ?>" />
		
		</form>
</div>

<?php endwhile; endif; ?>

<?php get_footer() ?>
