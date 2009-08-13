<div class="bp-widget">
	<h4><?php bp_wire_title() ?> <span><a href="<?php bp_wire_see_all_link() ?>"><?php _e( "See All", "buddypress" ) ?> &rarr;</a></span></h4>

	<form name="wire-post-list-form" id="wire-post-list-form" action="" method="post">
	<?php if ( bp_has_wire_posts( 'item_id=' . bp_get_wire_item_id() . '&can_post=' . bp_wire_can_post() ) ) : ?>
		
		<?php if ( bp_wire_needs_pagination() ) : ?>
			<div id="wire-count" class="pag-count">
				<?php bp_wire_pagination_count() ?> &nbsp;
				<span class="ajax-loader"></span>
			</div>
		
			<div id="wire-pagination" class="pagination-links">
				<?php bp_wire_pagination() ?>
			</div>
		<?php endif; ?>
		
		<ul id="wire-post-list" class="item-list">
		<?php while ( bp_wire_posts() ) : bp_the_wire_post(); ?>
			<li>
				<div class="wire-post-metadata">
					<?php bp_wire_post_author_avatar() ?>
					<?php printf ( __( 'On %1$s %2$s said:', "buddypress" ), bp_wire_post_date( null, false ), bp_wire_post_author_name( false ) ) ?>
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
