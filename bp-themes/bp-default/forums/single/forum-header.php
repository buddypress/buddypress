<?php do_action( 'bp_before_forum_header' ); ?>

<div id="item-header-avatar">
	<a href="<?php bp_forum_permalink(); ?>" title="<?php bp_get_forum_name(); ?>">

		<?php //bp_forum_avatar(); ?>

	</a>
</div><!-- #item-header-avatar -->

<div id="item-header-content">
	<h2><a href="<?php bp_forum_permalink(); ?>" title="<?php bp_forum_name(); ?>"><?php bp_forum_name(); ?></a></h2>
	<span class="highlight"><?php //bp_forum_type(); ?></span> <span class="activity"><?php printf( __( 'active %s', 'buddypress' ), '' ); //bp_get_forum_last_active() ); ?></span>

	<?php do_action( 'bp_before_forum_header_meta' ); ?>

	<div id="item-meta">

		<?php //bp_forum_description(); ?>

		<div id="item-buttons">

			<?php do_action( 'bp_forum_header_actions' ); ?>

		</div><!-- #item-buttons -->

		<?php do_action( 'bp_forum_header_meta' ); ?>

	</div>
</div><!-- #item-header-content -->

<?php
do_action( 'bp_after_forum_header' );
do_action( 'template_notices' );
?>