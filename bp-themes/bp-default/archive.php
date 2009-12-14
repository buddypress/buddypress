<?php get_header(); ?>

	<div id="content">
		<div class="padder">

		<?php do_action( 'bp_before_archive' ) ?>

		<div class="page" id="blog-archives">

			<h2 class="pagetitle"><?php _e( 'Blog', 'buddypress' ) ?></h2>

			<?php if ( have_posts() ) : ?>

				<h3><?php printf( __( 'You are browsing the archive for %1$s.', 'buddypress' ), wp_title( false, false ) ); ?></h3>

				<div class="navigation">

					<div class="alignleft"><?php next_posts_link( __( '&laquo; Previous Entries', 'buddypress' ) ) ?></div>
					<div class="alignright"><?php previous_posts_link( __( 'Next Entries &raquo;', 'buddypress' ) ) ?></div>

				</div>

				<?php while (have_posts()) : the_post(); ?>

					<?php do_action( 'bp_before_blog_post' ) ?>

					<div class="post">

						<h3 id="post-<?php the_ID(); ?>"><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php _e( 'Permanent Link to', 'buddypress' ) ?> <?php the_title(); ?>"><?php the_title(); ?></a></h3>
						<small><?php the_time('F j, Y') ?></small>

						<div class="entry">
							<?php the_content() ?>
						</div>

						<p class="postmetadata"><?php _e( 'Posted in', 'buddypress' ) ?> <?php the_category(', ') ?> | <?php edit_post_link( __( 'Edit', 'buddypress' ), '', ' | '); ?>  <?php comments_popup_link( __( 'No Comments &#187;', 'buddypress' ), __( '1 Comment &#187;', 'buddypress' ), __( '% Comments &#187;', 'buddypress' ) ); ?></p>

					</div>

					<?php do_action( 'bp_after_blog_post' ) ?>

				<?php endwhile; ?>

				<div class="navigation">

					<div class="alignleft"><?php next_posts_link( __( '&laquo; Previous Entries', 'buddypress' ) ) ?></div>
					<div class="alignright"><?php previous_posts_link( __( 'Next Entries &raquo;', 'buddypress' ) ) ?></div>

				</div>

			<?php else : ?>

				<h2 class="center"><?php _e( 'Not Found', 'buddypress' ) ?></h2>
				<?php locate_template( array( 'searchform.php' ), true ) ?>

			<?php endif; ?>

		</div>

		<?php do_action( 'bp_after_archive' ) ?>

		</div><!-- .padder -->
	</div><!-- #content -->

	<?php locate_template( array( 'sidebar.php' ), true ) ?>

<?php get_footer(); ?>
