<?php get_header() ?>

<div class="content-header">
	<ul class="content-header-nav">
		<?php bp_friends_header_tabs() ?>
	</ul>
</div>

<div id="content">
	<h2><?php bp_word_or_name( __( "My Friends", 'buddypress' ), __( "%s's Friends", 'buddypress' ) ) ?> &raquo; <?php bp_friends_filter_title() ?></h2>
	
	<div class="left-menu">
		<?php bp_friend_search_form('Search Friends') ?>
	</div>
	
	<div class="main-column">
		<?php do_action( 'template_notices' ) // (error/success feedback) ?>
		
		<?php load_template( TEMPLATEPATH . '/friends/friends-loop.php') ?>
	</div>
</div>

<?php get_footer() ?>