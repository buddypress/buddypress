<?php get_header(); ?>

	<div id="content">

		<?php do_action( 'bp_before_blog_search' ) ?>
		
		<div class="page" id="blog-search">
			
			<h2 class="pagetitle"><?php _e( 'Blog', 'buddypress' ) ?></h2>

			<?php if (have_posts()) : ?>
		
				<h3 class="pagetitle"><?php _e( 'Search Results', 'buddypress' ) ?></h3>

				<div class="navigation">
					<div class="alignleft"><?php next_posts_link( __( '&laquo; Previous Entries', 'buddypress' ) ) ?></div>
					<div class="alignright"><?php previous_posts_link( __( 'Next Entries &raquo;', 'buddypress' ) ) ?></div>
				</div>

				<?php while (have_posts()) : the_post(); ?>

					<?php do_action( 'bp_before_blog_post' ) ?>

					<div class="post">
						
						<h3 id="post-<?php the_ID(); ?>"><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php _e( 'Permanent Link to', 'buddypress' ) ?> <?php the_title(); ?>"><?php the_title(); ?></a></h3>
						<small><?php the_time('l, F jS, Y') ?></small>

						<p class="postmetadata"><?php _e( 'Posted in', 'buddypress' ) ?> <?php the_category(', ') ?> | <?php edit_post_link( __( 'Edit', 'buddypress' ), '', ' | '); ?>  <?php comments_popup_link( __( 'No Comments &#187;', 'buddypress' ), __( '1 Comment &#187;', 'buddypress' ), __( '% Comments &#187;', 'buddypress' ) ); ?></p>
					
						<?php do_action( 'bp_blog_post' ) ?>

					</div>

					<?php do_action( 'bp_after_blog_post' ) ?>

				<?php endwhile; ?>

				<div class="navigation">
					<div class="alignleft"><?php next_posts_link( __( '&laquo; Previous Entries', 'buddypress' ) ) ?></div>
					<div class="alignright"><?php previous_posts_link( __( 'Next Entries &raquo;', 'buddypress' ) ) ?></div>
				</div>

			<?php else : ?>

				<h2 class="center"><?php _e( 'No posts found. Try a different search?', 'buddypress' ) ?></h2>
				<?php include (TEMPLATEPATH . '/searchform.php'); ?>

			<?php endif; ?>

		</div>
		
		<?php do_action( 'bp_after_blog_search' ) ?>
		
	</div>

<?php get_sidebar(); ?>

<?php get_footer(); ?>
