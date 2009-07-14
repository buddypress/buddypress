<?php get_header() ?>

<div class="content-header">

</div>

<div id="content">
	<?php do_action( 'template_notices' ) // (error/success feedback) ?>
	
	<div class="left-menu">
		<?php bp_the_avatar() ?>
		
		<?php if ( bp_exists('friends') ) : ?>
			<?php bp_add_friend_button() ?>
		<?php endif; ?>

	</div>

	<div class="main-column">
		<?php bp_get_profile_header() ?>

		<div class="info-group">
			<h4><?php _e( 'My Friends Activity', 'buddypress' ) ?> <a href="<?php bp_activities_member_rss_link() ?>" title="<?php _e( 'RSS Feed', 'buddypress' ) ?>"><?php _e( 'RSS Feed', 'buddypress' ) ?></a></h4>

			<ul id="activity-filter-links">
				<?php bp_activity_filter_links() ?>
			</ul>

			<?php if ( bp_has_activities( 'type=friends&per_page=25&max=500' ) ) : ?>

				<div class="pag-count" id="activity-count">
					<?php bp_activity_pagination_count() ?>
				</div>
		
				<div class="pagination-links" id="activity-pag">
					&nbsp; <?php bp_activity_pagination_links() ?>
				</div>

				<ul id="activity-list">
				<?php while ( bp_activities() ) : bp_the_activity(); ?>
					<li class="<?php bp_activity_css_class() ?>">
						<?php bp_activity_content() ?>
					</li>
				<?php endwhile; ?>
				</ul>

			<?php else: ?>

				<div id="message" class="info">
					<p><?php _e( "Your friends haven't done anything yet.", 'buddypress' )  ?></p>
				</div>

			<?php endif;?>
		</div>
		
	</div>
	

</div>

<?php get_footer() ?>

