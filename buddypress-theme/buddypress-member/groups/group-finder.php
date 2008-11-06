<div class="content-header">
</div>

<div id="content">
	<h2>Group Finder</h2>
	
	<div class="left-menu">
		<?php bp_group_search_form() ?>
	</div>

	<div class="main-column">
		<?php do_action( 'template_notices' ) // (error/success feedback) ?>

		<div class="pagination-links" id="groupfinder-pag">
			<?php bp_group_pagination() ?>
		</div>
		
		<div id="finder-message">
			<div id="message" class="info">
				<p>
				   <strong>Find Groups Using the Group Finder!</strong><br />
				   Use the search box to find groups on the site. 
				   Enter anything you want, currently only group titles will
				   be searched.
				</p>
			</div>
		</div>
		
		<div id="group-loop">
		<?php load_template( TEMPLATEPATH . '/groups/group-loop.php') ?>
		</div>
		
	</div>
	
</div>