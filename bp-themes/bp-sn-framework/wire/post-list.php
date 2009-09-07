<?php do_action( 'bp_before_wire_post_list_content' ) ?>

<div class="bp-widget">
	<h4><?php bp_wire_title() ?> <span><a href="<?php bp_wire_see_all_link() ?>"><?php _e( "See All", "buddypress" ) ?> &rarr;</a></span></h4>

	<?php do_action( 'bp_before_wire_post_list_form' ) ?>
	
	<?php if ( bp_has_wire_posts( 'item_id=' . bp_get_wire_item_id() . '&can_post=' . bp_wire_can_post() ) ) : ?>
		
		<?php bp_wire_get_post_form() ?>	

	<div id="wire-post-list-content">

		<?php if ( bp_wire_needs_pagination() ) : ?>
			<div class="pagination">

				<div id="wire-count" class="pag-count">
					<?php bp_wire_pagination_count() ?> &nbsp;
					<span class="ajax-loader"></span>
				</div>
		
				<div id="wire-pagination" class="pagination-links">
					<?php bp_wire_pagination() ?>
				</div>

			</div>
		<?php endif; ?>

		<?php do_action( 'bp_before_wire_post_list' ) ?>
				
		<ul id="wire-post-list" class="item-list">
		<?php while ( bp_wire_posts() ) : bp_the_wire_post(); ?>
			
			<li>
				<?php do_action( 'bp_before_wire_post_list_metadata' ) ?>
				
				<div class="wire-post-metadata">
					<?php bp_wire_post_author_avatar() ?>
					<?php printf ( __( 'On %1$s %2$s said:', "buddypress" ), bp_get_wire_post_date(), bp_get_wire_post_author_name() ) ?>
					<?php bp_wire_delete_link() ?>
					
					<?php do_action( 'bp_wire_post_list_metadata' ) ?>
				</div>
				
				<?php do_action( 'bp_after_wire_post_list_metadata' ) ?>
				<?php do_action( 'bp_before_wire_post_list_item' ) ?>
				
				<div class="wire-post-content">
					<?php bp_wire_post_content() ?>
					
					<?php do_action( 'bp_wire_post_list_item' ) ?>
				</div>
				
				<?php do_action( 'bp_after_wire_post_list_item' ) ?>
			</li>
			
		<?php endwhile; ?>
		</ul>
		
		<?php do_action( 'bp_after_wire_post_list' ) ?>
	
	</div>
	<?php else: ?>

		<div id="message" class="info">
			<p><?php bp_wire_no_posts_message() ?></p>
		</div>

	<?php endif;?>
	
	<?php do_action( 'bp_after_wire_post_list_form' ) ?>

	<?php bp_wire_get_post_form() ?>		
</div>

<?php do_action( 'bp_after_wire_post_list_content' ) ?>
