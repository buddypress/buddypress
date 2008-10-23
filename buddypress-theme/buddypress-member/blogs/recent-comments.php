<div class="content-header">
</div>

<div id="content">
	<h2>Recent Comments</h2>
	<?php do_action( 'template_notices' ) // (error/success feedback) ?>

	<?php if ( bp_has_comments() ) : ?>
		<ul id="comment-list">
		<?php while ( bp_comments() ) : bp_the_comment(); ?>
			<li id="comment-<?php bp_comment_id() ?>">
				<span class="small">On <?php bp_comment_date('F jS, Y') ?> <?php bp_comment_author() ?> said:</span>
				<p><?php bp_comment_content() ?></p>
				<span class="small">Commented on the post <a href="<?php bp_comment_post_permalink() ?>"><?php bp_comment_post_title() ?></a> on the blog <a href="<?php bp_comment_blog_permalink() ?>"><?php bp_comment_blog_name() ?></a>.</span>
			</li>
		<?php endwhile; ?>
		</ul>
	<?php else: ?>

		<div id="message" class="info">
			<p><?php bp_word_or_name( __( "You haven't posted any comments yet.", 'buddypress' ), __( "%s hasn't posted any comments yet.", 'buddypress' ) ) ?></p>
		</div>

	<?php endif;?>

</div>
