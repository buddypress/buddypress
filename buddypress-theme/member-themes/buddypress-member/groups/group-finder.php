<div class="content-header">
</div>

<div id="content">
	<h2>Group Finder</h2>
	
	<div class="left-menu">
		<?php bp_group_search_form() ?>
	</div>

	<div class="main-column">
		<?php do_action( 'template_notices' ) // (error/success feedback) ?>

		<div id="finder-message">
			<div id="message" class="info">
				<p>
				   <strong><?php _e( 'Find Groups Using the Group Finder!', 'buddypress' ); ?></strong><br />
				   <?php _e( 'Use the search box to find groups on the site. Enter anything you want, currently only group titles will be searched.', 'buddypress' ); ?>
				</p>
			</div>
		</div>
		
		<?php load_template( TEMPLATEPATH . '/groups/group-loop.php') ?>
		
	</div>
	
</div>