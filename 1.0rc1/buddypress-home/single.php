<?php get_header(); ?>

	<div id="content" class="narrowcolumn">

	<div class="widget" id="latest-news">
		<h2 class="widgettitle"><?php _e( 'Blog', 'buddypress' ) ?></h2>

		<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
			
			<div class="item-options">
				<div class="alignleft"><?php previous_post_link('&laquo; %link') ?> </div>
				<div class="alignright"> <?php next_post_link('%link &raquo;') ?></div>
			</div>

			<div class="post" id="post-<?php the_ID(); ?>">
				<h3><a href="<?php echo get_permalink() ?>" rel="bookmark" title="Permanent Link: <?php the_title(); ?>"><?php the_title(); ?></a></h3>

				<div class="entry">
					<?php the_content('<p class="serif">Read the rest of this entry &raquo;</p>'); ?>

					<?php wp_link_pages(array('before' => '<p><strong>Pages:</strong> ', 'after' => '</p>', 'next_or_number' => 'number')); ?>


				</div>
			</div>

		<?php comments_template(); ?>

		<?php endwhile; else: ?>

			<p><?php _e( 'Sorry, no posts matched your criteria.', 'buddypress' ) ?></p>

		<?php endif; ?>
		
	</div>
	</div>

<?php get_sidebar(); ?>

<?php get_footer(); ?>
