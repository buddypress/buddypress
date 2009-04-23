<?php get_header(); ?>

	<div id="content" class="narrowcolumn">
		<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
			<div class="widget" id="latest-news">
				<h2 class="widgettitle"><?php the_title(); ?></h2>
		
				<div class="post" id="post-<?php the_ID(); ?>">
					<div class="entry">
						<?php the_content( __( '<p class="serif">Read the rest of this page &raquo;</p>', 'buddypress' ) ); ?>

						<?php wp_link_pages(array('before' => __( '<p><strong>Pages:</strong> ', 'buddypress' ), 'after' => '</p>', 'next_or_number' => 'number')); ?>

					</div>
				</div>
			</div>
		<?php endwhile; endif; ?>
	<?php edit_post_link( __( 'Edit this entry.', 'buddypress' ), '<p>', '</p>'); ?>
	</div>

<?php get_sidebar(); ?>

<?php get_footer(); ?>
