<?php get_header(); ?>
<div class="content-header">
	Blog Archives
</div>

<div id="content">
	
	<div id="blog-info">
		<h1><a href="<?php echo bp_core_get_current_domain() ?>blog"><?php bloginfo('name'); ?></a></h1>
		<p class="desc"><?php bloginfo('description'); ?></p>
	</div>
	
<?php is_tag(); ?>
	<?php if (have_posts()) : ?>

		<?php $post = $posts[0]; // Hack. Set $post so that the_date() works. ?>
 		<?php /* If this is a category archive */ if (is_category()) { ?>
	<h4 class="archive">Archive for the &#8216;<?php single_cat_title(); ?>&#8217; Category</h4>
		<?php /* If this is a tag archive */ } elseif( is_tag() ) { ?>
	<h4 class="archive">Posts Tagged &#8216;<?php single_tag_title(); ?>&#8217;</h4>
		<?php /* If this is a daily archive */ } elseif (is_day()) { ?>
	<h4 class="archive">Archive for <?php the_time('F jS, Y'); ?></h4>
 		<?php /* If this is a monthly archive */ } elseif (is_month()) { ?>
	<h4 class="archive">Archive for <?php the_time('F, Y'); ?></h4>
 		<?php /* If this is a yearly archive */ } elseif (is_year()) { ?>
	<h4 class="archive">Archive for <?php the_time('Y'); ?></h4>
		<?php /* If this is an author archive */ } elseif (is_author()) { ?>
	<h4 class="archive">Author Archive</h4>
		<?php /* If this is a paged archive */ } elseif (isset($_GET['paged']) && !empty($_GET['paged'])) { ?>
	<h4 class="archive">Blog Archives</h4>
		<?php } ?>


	<div class="navigation">
		<div><?php next_posts_link('&laquo; Older Entries') ?></div>
		<div><?php previous_posts_link('Newer Entries &raquo;') ?></div>
	</div>

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
	<?php include (TEMPLATEPATH . '/searchform.php'); ?>

<?php endif; ?>

</div>


<?php get_footer(); ?>
