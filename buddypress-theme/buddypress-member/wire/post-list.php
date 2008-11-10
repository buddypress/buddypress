<div class="info-group">
	<h4><?php bp_wire_title() ?> <a href="<?php bp_wire_see_all_link() ?>">See All &raquo;</a></h4>

	<form name="wire-post-list-form" id="wire-post-list-form" action="" method="post">
	<?php if ( bp_has_wire_posts( bp_wire_item_id(), bp_wire_can_post() ) ) : ?>
		
		<?php if ( bp_wire_needs_pagination() ) : ?>
			<div id="wire-count" class="pag-count">
				<?php bp_wire_pagination_count() ?> &nbsp;
				<img id="ajax-loader" src="<?php bp_wire_ajax_loader_src() ?>" height="7" alt="Loading" style="display: none;" />
			</div>
		
			<div id="wire-pagination" class="pagination-links">
				<?php bp_wire_pagination() ?>
			</div>
		<?php endif; ?>
		
		<ul id="wire-post-list">
		<?php while ( bp_wire_posts() ) : bp_the_wire_post(); ?>
			<li>
				<div class="wire-post-metadata">
					<?php bp_wire_post_author_avatar() ?>
					On <?php bp_wire_post_date() ?> 
					<?php bp_wire_post_author_name() ?> said:
					<?php bp_wire_delete_link() ?>
				</div>
				
				<div class="wire-post-content">
					<?php bp_wire_post_content() ?>
				</div>
			</li>
		<?php endwhile; ?>
		</ul>
	
	<?php else: ?>

		<div id="message" class="info">
			<p><?php bp_wire_no_posts_message() ?></p>
		</div>

	<?php endif;?>
	
	<input type="hidden" name="bp_wire_item_id" id="bp_wire_item_id" value="<?php bp_wire_item_id(true) ?>" />
	</form>

	<?php bp_wire_get_post_form() ?>		
</div>