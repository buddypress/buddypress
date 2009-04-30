<?php
/*
 * /groups/admin/create.php
 * The create group screen, currently the HTML is not editable. All edit screens are wrapped
 * in a form with the ID: 'create-group-form'.
 *
 * Loaded on URL:
 * 'http://example.org/members/[username]/groups/create/
 */
?>

<?php get_header() ?>

<div class="content-header">
	<ul class="content-header-nav">
		<?php bp_group_creation_tabs(); ?>
	</ul>
</div>

<div id="main">
		
	<h2><?php _e( 'Create a Group', 'buddypress' ) ?> <?php bp_group_creation_stage_title() ?></h2>
	
	<?php do_action( 'template_notices' ) ?>
	
	<?php bp_group_create_form() ?>
	
</div>

<?php get_footer() ?>