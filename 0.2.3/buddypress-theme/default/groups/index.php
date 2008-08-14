<?php get_header(); ?>

	<div class="content-header">
		You belong to <?php bp_total_group_count() ?> groups.
	</div>

	<div id="content">
		<div class="pagination-links" id="pag">
			<?php bp_group_pagination() ?>
		</div>
		
		<h2>My Groups</h2>
		
		<div class="left-menu">
			<?php bp_group_search_form() ?>
		</div>
		
		<div class="main-column">
			<?php do_action( 'template_notices' ) // (error/success feedback) ?>
		
			<?php if ( bp_has_groups() ) : ?>
				<ul id="group-list">
				<?php while ( bp_groups() ) : bp_the_group(); ?>
					<li>
						<?php bp_group_avatar_thumb() ?>
						<h4><a href="<?php bp_group_permalink() ?>"><?php bp_group_name() ?></a><span class="small"> - <?php bp_group_total_members() ?> members</span></h4>
						<p class="desc">
							<?php bp_group_description_excerpt() ?>
						</p>
						<hr />
					</li>
				<?php endwhile; ?>
				</ul>
			<?php else: ?>

				<div id="message" class="info">
					<p><?php _e('You haven\'t joined any groups yet.'); ?></p>
				</div>

			<?php endif;?>
		</div>
	</div>

<?php get_footer() ?>