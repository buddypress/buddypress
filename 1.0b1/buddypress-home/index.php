<?php get_header(); ?>

<div id="content" class="narrowcolumn">

	<div class="widget" id="latest-news">
		<h2 class="widgettitle">News</h2>
		<?php if (have_posts()) : ?>

			<?php while (have_posts()) : the_post(); ?>
				<div class="post" id="post-<?php the_ID(); ?>">
					<h3><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h3>
					<p class="date"><?php the_time('F jS, Y') ?> <em>in <?php the_category(', ') ?> by <?php echo bp_core_get_userlink($post->post_author) ?></em></p>

					<div class="entry">
						<?php the_content('Read the rest of this entry &raquo;'); ?>
					</div>

					<p class="postmetadata"><span class="tags"><?php the_tags('Tags: ', ', ', '<br />'); ?></span> <span class="comments"><?php comments_popup_link('No Comments &#187;', '1 Comment &#187;', '% Comments &#187;'); ?></span></p>
					
					<div class="clear"></div>
				</div>

			<?php endwhile; ?>

			<div class="navigation">
				<div class="alignleft"><?php next_posts_link('&laquo; Older Entries') ?></div>
				<div class="alignright"><?php previous_posts_link('Newer Entries &raquo;') ?></div>
			</div>

		<?php else : ?>

			<h2 class="center">Not Found</h2>
			<p class="center">Sorry, but you are looking for something that isn't here.</p>
			<?php include (TEMPLATEPATH . "/searchform.php"); ?>

		<?php endif; ?>
	</div>

</div>

<?php get_sidebar(); ?>


<?php get_footer(); ?>
