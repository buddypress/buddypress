<?php get_header(); ?>

	<div id="content" class="narrowcolumn">
		<div class="widget" id="latest-news">
			<h2 class="widgettitle"><?php _e( 'Blog', 'buddypress' ) ?></h2>
			
			<?php if (have_posts()) : ?>

			 <?php $post = $posts[0]; // Hack. Set $post so that the_date() works. ?>
			 <?php /* If this is a category archive */ if (is_category()) { ?>
			<h3 class="pageTitle"><?php _e( 'Archive for the', 'buddypress' ) ?> &#8216;<?php single_cat_title(); ?>&#8217; <?php _e( 'Category', 'buddypress' ) ?></h2>

	 	  	 <?php /* If this is a daily archive */ } elseif (is_day()) { ?>
			<h3 class="pageTitle"><?php _e( 'Archive for', 'buddypress' ) ?> <?php the_time('F jS, Y'); ?></h2>

		 	 <?php /* If this is a monthly archive */ } elseif (is_month()) { ?>
			<h3 class="pageTitle"><?php _e( 'Archive for', 'buddypress' ) ?> <?php the_time('F, Y'); ?></h2>

			<?php /* If this is a yearly archive */ } elseif (is_year()) { ?>
			<h3 class="pageTitle"><?php _e( 'Archive for', 'buddypress' ) ?> <?php the_time('Y'); ?></h2>

		  	<?php /* If this is an author archive */ } elseif (is_author()) { ?>
			<h3 class="pageTitle"><?php _e( 'Author Archive', 'buddypress' ) ?></h2>

			<?php /* If this is a paged archive */ } elseif (isset($_GET['paged']) && !empty($_GET['paged'])) { ?>
			<h3 class="pageTitle"><?php _e( 'Blog Archives', 'buddypress' ) ?></h2>

			<?php } ?>


			<div class="navigation">
				<div class="alignleft"><?php next_posts_link( __( '&laquo; Previous Entries', 'buddypress' ) ) ?></div>
				<div class="alignright"><?php previous_posts_link( __( 'Next Entries &raquo;', 'buddypress' ) ) ?></div>
			</div>

			<?php while (have_posts()) : the_post(); ?>
			<div class="post">
					<h3 id="post-<?php the_ID(); ?>"><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php _e( 'Permanent Link to', 'buddypress' ) ?> <?php the_title(); ?>"><?php the_title(); ?></a></h3>
					<small><?php the_time('l, F jS, Y') ?></small>

					<div class="entry">
						<?php the_content() ?>
					</div>

					<p class="postmetadata"><?php _e( 'Posted in', 'buddypress' ) ?> <?php the_category(', ') ?> | <?php edit_post_link( __( 'Edit', 'buddypress' ), '', ' | '); ?>  <?php comments_popup_link('No Comments &#187;', '1 Comment &#187;', '% Comments &#187;'); ?></p>

				</div>

			<?php endwhile; ?>

			<div class="navigation">
				<div class="alignleft"><?php next_posts_link( __( '&laquo; Previous Entries', 'buddypress' ) ) ?></div>
				<div class="alignright"><?php previous_posts_link( __( 'Next Entries &raquo;', 'buddypress' ) ) ?></div>
			</div>

		<?php else : ?>

			<h2 class="center"><?php _e( 'Not Found', 'buddypress' ) ?></h2>
			<?php include (TEMPLATEPATH . '/searchform.php'); ?>

		<?php endif; ?>

	</div>
	
	</div>

<?php get_sidebar(); ?>

<?php get_footer(); ?>
