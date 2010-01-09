<?php get_header() ?>

	<div class="content-header">
		<ul class="content-header-nav">
			<?php bp_friends_header_tabs() ?>
		</ul>
	</div>

	<div id="content">
		<h2><?php bp_word_or_name( __( "My Friends", 'buddypress' ), __( "%s's Friends", 'buddypress' ) ) ?> &rarr; <?php bp_friends_filter_title() ?></h2>

		<?php do_action( 'bp_before_my_friends_content' ) ?>

		<div class="left-menu">
			<?php do_action( 'bp_before_my_friends_search' ) ?>

			<?php bp_friend_search_form() ?>

			<?php do_action( 'bp_after_my_friends_search' ) ?>
		</div>

		<div class="main-column">
			<?php do_action( 'template_notices' ) // (error/success feedback) ?>

			<?php locate_template( array( 'friends/friends-loop.php' ), true ) ?>
		</div>

		<?php do_action( 'bp_after_my_friends_content' ) ?>
	</div>

<?php get_footer() ?>