<?php get_header() ?>

	<div class="content-header">
		<?php bp_blogs_blog_tabs() ?>
	</div>

	<div id="content">

		<?php do_action( 'template_notices' ) // (error/success feedback) ?>

		<h2><?php bp_word_or_name( __( "My Blogs", 'buddypress' ), __( "%s's Blogs", 'buddypress' ) ) ?></h2>

		<?php do_action( 'bp_before_my_blogs_content' ) ?>

		<?php if ( bp_has_blogs( 'user_id=' . bp_displayed_user_id() ) ) : ?>

			<ul id="blog-list" class="item-list">
			<?php while ( bp_blogs() ) : bp_the_blog(); ?>

					<li>
						<h4><a href="<?php bp_blog_permalink() ?>"><?php bp_blog_name() ?></a></h4>
						<p><?php bp_blog_description() ?></p>

						<?php do_action( 'bp_my_blogs_item' ) ?>
					</li>

				<?php endwhile; ?>
			</ul>

			<?php do_action( 'bp_my_blogs_content' ) ?>

		<?php else: ?>

			<div id="message" class="info">
				<p><?php bp_word_or_name( __( "You haven't created any blogs yet.", 'buddypress' ), __( "%s hasn't created any public blogs yet.", 'buddypress' ) ) ?> <?php bp_create_blog_link() ?> </p>
			</div>

		<?php endif;?>

		<?php do_action( 'bp_after_my_blogs_content' ) ?>

	</div>

<?php get_footer() ?>