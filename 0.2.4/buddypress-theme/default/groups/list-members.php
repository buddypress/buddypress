<?php get_header(); ?>

	<div class="content-header">
		
	</div>

	<div id="content">
		<div class="pagination-links" id="pag">
			<?php bp_group_pagination() ?>
		</div>
		
		<?php if ( bp_has_groups() ) : while ( bp_groups() ) : bp_the_group(); ?>
			
		<h2><a href="<?php bp_group_permalink() ?>"><?php bp_group_name() ?></a> &raquo; Members</h2>
		
		<ul id="member-list">
			<?php bp_group_list_members() // [TODO] this will be replaced with a proper template loop. ?>
		</ul>
		
		<?php endwhile; else: ?>
			<div id="message" class="error">
				<p><?php _e('Sorry, there are no members of this group.'); ?></p>
			</div>
		<?php endif;?>
		
		</div>
	</div>

<?php get_footer() ?>