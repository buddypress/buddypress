<?php get_header() ?>

<div class="content-header">
	<?php bp_blogs_blog_tabs() ?>
</div>

<div id="content">
	<h2><?php _e("Recent Comments", "buddypress"); ?></h2>
	<?php do_action( 'template_notices' ) // (error/success feedback) ?>

	<?php if ( bp_has_comments() ) : ?>
		<ul id="comment-list" class="item-list">
		<?php while ( bp_comments() ) : bp_the_comment(); ?>
			<li id="comment-<?php bp_comment_id() ?>">
				<span class="small"><?php printf( __( 'On %1$s %2$s said:', 'buddypress' ), bp_comment_date( __( 'F jS, Y', 'buddypress' ), false ), bp_comment_author( false ) ); ?></span>
				<p><?php bp_comment_content() ?></p>
				<span class="small"><?php printf( __( 'Commented on the post <a href="%1$s">%2$s</a> on the blog <a href="%3$s">%4$s</a>.', 'buddypress' ), bp_comment_post_permalink( false ), bp_comment_post_title( false ), bp_comment_blog_permalink( false ), bp_comment_blog_name( false ) ); ?></span>
			</li>
		<?php endwhile; ?>
		</ul>
	<?php else: ?>

		<div id="message" class="info">
			<p><?php bp_word_or_name( __( "You haven't posted any comments yet.", 'buddypress' ), __( "%s hasn't posted any comments yet.", 'buddypress' ) ) ?></p>
		</div>

	<?php endif;?>

</div>

<?php get_footer() ?>