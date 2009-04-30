<?php
/*
 * /friends/index.php
 * Displays the friend ordering tabs, the friend filter box.
 *
 * Loads: 'friends/friends-loop.php'
 *
 * Loaded on URL:
 * 'http://example.org/members/[username]/friends/
 * 'http://example.org/members/[username]/friends/my-friends/
 */
?>

<?php get_header() ?>

<div class="content-header">
	<ul class="content-header-nav">
		<?php bp_friends_header_tabs() ?>
	</ul>
</div>

<div id="main">
	<h2><?php bp_word_or_name( __( "My Friends", 'buddypress' ), __( "%s's Friends", 'buddypress' ) ) ?> &rarr; <?php bp_friends_filter_title() ?></h2>
	
	<div class="page-menu">
		<?php bp_friend_search_form() ?>
	</div>
	
	<div class="main-column">
		<?php do_action( 'template_notices' ) ?>
		
		<?php load_template( TEMPLATEPATH . '/friends/friends-loop.php') ?>
	</div>
</div>

<?php get_footer() ?>