<?php get_header(); ?>

	<div class="content-header">
	</div>

	<div id="content">
		<div class="pagination-links" id="groupfinder-pag">
			<?php bp_group_pagination() ?>
		</div>

		<h2>Group Finder</h2>
		
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

					<div id="finder-message">
						<div id="message" class="info">
							<p>
							   <strong>Find Groups Using the Group Finder!</strong><br />
							   Use the search box to find groups on the site. 
							   Enter anything you want, currently only group titles will
							   be searched.
							</p>
						</div>
					</div>

			<?php endif;?>
		</div>
		
	</div>

<?php get_footer() ?>