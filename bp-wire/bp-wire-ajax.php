<?php

function bp_wire_ajax_get_wire_posts() {
	global $bp;
	?>

	<?php if ( bp_has_wire_posts( $_POST['bp_wire_item_id'], 1 ) ) : ?>
		<div id="wire-count" class="pag-count">
			<?php bp_wire_pagination_count() ?> &nbsp;
			<img id="ajax-loader" src="<?php bp_wire_ajax_loader_src() ?>" height="7" alt="<?php _e( 'Loading', 'buddypress' ) ?>" style="display: none;" />
		</div>
			
		<div id="wire-pagination" class="pagination-links">
			<?php bp_wire_pagination() ?>
		</div>
		
		<ul id="wire-post-list">
		<?php while ( bp_wire_posts() ) : bp_the_wire_post(); ?>
			<li>
				<div class="wire-post-metadata">
					<?php bp_wire_post_author_avatar() ?>
					<?php _e( 'On', 'buddypress' ) ?> <?php bp_wire_post_date() ?> 
					<?php bp_wire_post_author_name() ?> <?php _e( 'said:', 'buddypress' ) ?>
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

	<?php endif; ?>
	
	<input type="hidden" name="bp_wire_item_id" id="bp_wire_item_id" value="<?php echo $_POST['bp_wire_item_id'] ?>" />
	<?php
}
add_action( 'wp_ajax_get_wire_posts', 'bp_wire_ajax_get_wire_posts' );
