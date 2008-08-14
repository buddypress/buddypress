<?php get_header(); ?>
<?php query_posts('offset=0'); ?>


	<div class="content-header">
		Last Updated: [date]
	</div>

	<div id="content">
		
		<div id="blog-info">
			<h1><a href="<?php echo bp_core_get_current_domain() ?>blog"><?php bloginfo('name'); ?></a></h1>
			<p class="desc"><?php bloginfo('description'); ?></p>
		</div>
		
		<?php if (have_posts()) : ?>

			<?php while (have_posts()) : the_post(); ?>

				<div class="post" id="post-<?php the_ID(); ?>">
					<h2><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
					<p class="date"><?php the_time('F jS, Y') ?> <em>in <?php the_category(', ') ?> by <?php the_author() ?></em><?php edit_post_link('Edit', ' [ ', ' ]'); ?></p>
					<?php the_content('Read the rest of this entry &raquo;'); ?>
					<p class="postmetadata"><?php the_tags('<span class="tags">', ', ', '</span>'); ?>  <span class="comments"><?php comments_popup_link('No Comments', '1 Comment', '% Comments'); ?></span></p>
					<hr />
				</div>

			<?php endwhile; ?>

			<div class="navigation">
				<div><?php next_posts_link('&laquo; Older Entries') ?></div>
				<div><?php previous_posts_link('Newer Entries &raquo;') ?></div>
			</div>

		<?php else : ?>

			<h2>Not Found</h2>
			<p>Sorry, but you are looking for something that isn't here.</p>
			<?php include (TEMPLATEPATH . "/searchform.php"); ?>

		<?php endif; ?>
	
	</div>

<?php get_footer(); ?>