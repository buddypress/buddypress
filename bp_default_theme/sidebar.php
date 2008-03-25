	<div id="sidebar">	
		
		<div id="profilePic">
			<?php xprofile_get_picture(); ?>
		</div>
		
		<ul id="quickLinks">
			<li>Photos</li>
			<li>More Options Coming Here</li>
		</ul>

		<ul id="components">
			<?php 	/* Widgetized sidebar, if you have the plugin installed. */
					if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar() ) : ?>

			<li><h2>Blog</h2>
				<ul>
					<?php wp_get_archives('type=monthly'); ?>
				</ul>
			</li>
			
			<?php endif; ?>
		</ul>

	</div>