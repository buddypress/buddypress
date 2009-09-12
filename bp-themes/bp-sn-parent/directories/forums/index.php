<?php get_header() ?>

	<?php do_action( 'bp_before_directory_forums_content' ) ?>		

	<div id="content">
	
		<div class="page" id="forums-directory-page">
	
			<form action="<?php echo site_url() . '/' ?>" method="post" id="forums-directory-form">

				<div id="forums-directory-listing" class="directory-listing">
					<h3><?php _e( 'Latest Forum Topics', 'buddypress' ) ?></h3>
					
					<div id="forum-dir-list">
						
						<?php do_action( 'bp_before_directory_forums_topic_filters' ) ?>
						
						<div id="global-forum-topic-filters">
							<ul>
								<li<?php if ( '' == bp_current_action() && !isset( $_GET['s'] ) ) : ?> class="selected"<?php endif; ?> id="forums-newest"><a href="<?php bp_newest_forum_topics_link() ?>" title="<?php _e( 'Newest Topics', 'buddypress' ) ?>"><?php _e( 'Newest Topics', 'buddypress') ?></a></li>
								<li<?php if ( 'popular' == bp_current_action() ) : ?> class="selected"<?php endif; ?> id="forums-popular"><a href="<?php bp_popular_forum_topics_link() ?>" title="<?php _e( 'Most Popular Topics', 'buddypress' ) ?>"><?php _e( 'Most Popular Topics', 'buddypress') ?></a></li>
								<li<?php if ( 'unreplied' == bp_current_action() ) : ?> class="selected"<?php endif; ?> id="forums-unreplied"><a href="<?php bp_unreplied_forum_topics_link() ?>" title="<?php _e( 'Unreplied Topics', 'buddypress' ) ?>"><?php _e( 'Unreplied Topics', 'buddypress') ?></a></li>
		
								<?php if ( is_user_logged_in() ) : ?>
									<li<?php if ( 'personal' == bp_current_action() ) : ?> class="selected"<?php endif; ?> id="forums-personal"><a href="<?php bp_my_forum_topics_link() ?>" title="<?php _e( 'Topics I have started or replied to', 'buddypress' ) ?>"><?php _e( 'My Topics', 'buddypress') ?></a></li>
								<?php endif; ?>
								
								<?php if ( 'tag' == bp_current_action() ) : ?>
									<li class="selected" id="forums-tag"><a href="<?php bp_newest_forum_topics_link() ?>" title="<?php _e( 'Tag', 'buddypress' ) ?>"><?php printf( __( 'Tagged: %s', 'buddypress' ), bp_get_forums_tag_name() ) ?></a></li>
								<?php endif; ?>
								
								<?php if ( isset( $_GET['s'] ) ) : ?>
									<li class="selected" id="forums-search"><a href="<?php bp_newest_forum_topics_link() ?>" title="<?php _e( 'Search', 'buddypress' ) ?>"><?php printf( __( 'Matching: %s', 'buddypress' ), attribute_escape( $_GET['s'] ) ) ?></a></li>
								<?php endif; ?>
								
								<?php do_action( 'bp_directory_forums_topic_filters' ) ?>
							</ul>								
						</div>
						
						<?php do_action( 'bp_after_directory_forums_topic_filters' ) ?>

						<?php locate_template( array( 'directories/forums/forums-loop.php' ), true ) ?>
					</div>

				</div>
			
				<?php do_action( 'bp_directory_forums_content' ) ?>

			</form>
	
		</div>
	
	</div>

	<?php do_action( 'bp_after_directory_forums_content' ) ?>		
	<?php do_action( 'bp_before_directory_forums_sidebar' ) ?>		

	<div id="sidebar" class="directory-sidebar">

		<?php do_action( 'bp_before_directory_forums_search' ) ?>	

		<div id="forums-directory-search" class="directory-widget">
			
			<h3><?php _e( 'Forum Topic Search', 'buddypress' ) ?></h3>

			<?php bp_directory_forums_search_form() ?>

			<?php do_action( 'bp_directory_forums_search' ) ?>
				
		</div>

		<?php do_action( 'bp_after_directory_forums_search' ) ?>
		<?php do_action( 'bp_before_directory_forums_tags' ) ?>	

		<div id="forums-directory-tags" class="directory-widget">
			
			<h3><?php _e( 'Forum Topic Tags', 'buddypress' ) ?></h3>

			<?php bp_forums_tag_heat_map(); ?>

			<?php do_action( 'bp_directory_forums_search' ) ?>
				
		</div>

		<?php do_action( 'bp_after_directory_forums_search' ) ?>	

	</div>
	
	<?php do_action( 'bp_after_directory_forums_sidebar' ) ?> 

<?php get_footer() ?>