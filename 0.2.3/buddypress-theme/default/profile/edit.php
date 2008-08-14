<?php get_header(); ?>

	<div class="content-header">
		
		<ul class="content-header-nav">
			<?php bp_profile_group_tabs(); ?>
		</ul>
		
	</div>

	<div id="content">
		
		<h2>Editing '<?php bp_profile_group_name() ?>'</h2>
		
		<?php bp_edit_profile_form() ?>

	</div>
	
<?php get_footer(); ?>