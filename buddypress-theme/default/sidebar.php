	<div id="sidebar">
		<ul>
			<?php if ( bp_is_home() ) : ?>
			<li>
				<div class="widget">
					<a id="adminarea" href="<?php echo bp_core_get_loggedin_domain() ?>wp-admin/post-new.php">Blog Admin Area</a>
				</div>
			</li>
			<?php else: ?>
			<li>
				<div class="widget">
					<a id="backhome" href="<?php echo bp_core_get_current_domain() ?>"><?php bp_user_fullname() ?></a>
				</div>
			</li>
			<?php endif; ?>
			
			<?php 	/* Widgetized sidebar, if you have the plugin installed. */
					if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar() ) : ?>
			<li>
				<div class="widget">
					<?php include (TEMPLATEPATH . '/searchform.php'); ?>
				</div>
			</li>

			<!-- Author information is disabled per default. Uncomment and fill in your details if you want to use it.
			<li><h2>Author</h2>
			<p>A little something about you, the author. Nothing lengthy, just an overview.</p>
			</li>
			-->
			
			<li>
				<div class="widget">
					<?php wp_list_pages('title_li=<h2>Pages</h2>' ); ?>
				</div>
			</li>
			
			<li>
				<div class="widget">
					<h2>Archives</h2>
					<ul>
					<?php wp_get_archives('type=monthly'); ?>
					</ul>
				</div>
			</li>

			<li>
				<div class="widget">
					<?php wp_list_categories('show_count=1&title_li=<h2>Categories</h2>'); ?>
				</div>
			</li>
			
			<?php /* If this is the frontpage */ if ( is_home() || is_page() ) { ?>
			<li>
				<div class="widget">
					<?php wp_list_bookmarks(); ?>
				</div>
			</li>
			<?php } ?>

			<?php endif; ?>
		</ul>
	</div>

