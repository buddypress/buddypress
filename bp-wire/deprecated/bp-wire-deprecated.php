<?php
/***
 * Deprecated Wire Functionality
 *
 * This file contains functions that are deprecated.
 * You should not under any circumstance use these functions as they are 
 * either no longer valid, or have been replaced with something much more awesome.
 *
 * If you are using functions in this file you should slap the back of your head
 * and then use the functions or solutions that have replaced them.
 * Most functions contain a note telling you what you should be doing or using instead.
 *
 * Of course, things will still work if you use these functions but you will
 * be the laughing stock of the BuddyPress community. We will all point and laugh at
 * you. You'll also be making things harder for yourself in the long run, 
 * and you will miss out on lovely performance and functionality improvements.
 * 
 * If you've checked you are not using any deprecated functions and finished your little
 * dance, you can add the following line to your wp-config.php file to prevent any of
 * these old functions from being loaded:
 *
 * define( 'BP_IGNORE_DEPRECATED', true );
 */

function bp_wire_add_structure_css() {
	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return $template;
	
	/* Enqueue the structure CSS file to give basic positional formatting for components */
	wp_enqueue_style( 'bp-wire-structure', BP_PLUGIN_URL . '/bp-wire/deprecated/css/structure.css' );	
}
add_action( 'bp_styles', 'bp_wire_add_structure_css' );

function bp_wire_ajax_get_wire_posts() {
	global $bp;

	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return false;
	?>

	<?php if ( bp_has_wire_posts( 'item_id=' . $_POST['bp_wire_item_id'] . '&can_post=1' ) ) : ?>
		<div id="wire-count" class="pag-count">
			<?php bp_wire_pagination_count() ?> &nbsp;
			<img id="ajax-loader" src="<?php bp_wire_ajax_loader_src() ?>" height="7" alt="<?php _e( 'Loading', 'buddypress' ) ?>" style="display: none;" />
		</div>
			
		<div id="wire-pagination" class="pagination-links">
			<?php bp_wire_pagination() ?>
		</div>
		
		<ul id="wire-post-list">
		<?php $counter = 0; ?>
		<?php while ( bp_wire_posts() ) : bp_the_wire_post(); ?>
			<li<?php if ( $counter % 2 != 1 ) : ?> class="alt"<?php endif; ?>>
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
			<?php $counter++ ?>
		<?php endwhile; ?>
		</ul>
	
	<?php else: ?>

		<div id="message" class="info">
			<p><?php bp_wire_no_posts_message() ?></p>
		</div>

	<?php endif; ?>
	
	<input type="hidden" name="bp_wire_item_id" id="bp_wire_item_id" value="<?php echo attribute_escape( $_POST['bp_wire_item_id'] ) ?>" />
	<?php
}
add_action( 'wp_ajax_get_wire_posts', 'bp_wire_ajax_get_wire_posts' );

?>