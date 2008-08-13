<?php get_header(); ?>

	<div id="content" class="blog">
	
		<div id="profileName">
			<h2><a href="<?php echo get_option('home'); ?>/"><?php bloginfo('name'); ?></a></h2>
			<div class="description"><?php bloginfo('description'); ?></div>
		</div>
		
		<h3 class="pageTitle">My Blog</h3>
		
		<?php query_posts('showposts=10'); ?>

		<?php if (have_posts()) : ?>

			<?php while (have_posts()) : the_post(); ?>

				<div class="post" id="post-<?php the_ID(); ?>">
					<h2><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title(); ?>"><?php the_title(); ?></a></h2>
					<small><?php the_time('F jS, Y') ?>  by <?php the_author() ?></small>

					<div class="entry">
						<?php the_content('Read the rest of this entry &raquo;'); ?>
					</div>

					<p class="postmetadata">Posted in <?php the_category(', ') ?> | <?php edit_post_link('Edit', '', ' | '); ?>  <?php comments_popup_link('No Comments &#187;', '1 Comment &#187;', '% Comments &#187;'); ?></p>
				</div>
				
			<?php endwhile; ?>

			<div class="navigation">
				<div class="alignleft"><?php next_posts_link('&laquo; Previous Entries') ?></div>
				<div class="alignright"><?php previous_posts_link('Next Entries &raquo;') ?></div>
			</div>

		<?php else : ?>

			<h2 class="center">Not Found</h2>
			<p class="center">Sorry, but you are looking for something that isn't here.</p>
			<?php include (TEMPLATEPATH . "/searchform.php"); ?>

		<?php endif; ?>
		
		
	
	</div>
	
<?php get_footer(); ?>