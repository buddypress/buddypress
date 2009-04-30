<?php
/*
 * /directories/groups/index.php
 * Site directory for all the non-hidden groups across an install.
 * 
 * Loads: 'directories/groups/groups-loop.php'
 *
 * Loaded on URL:
 * 'http://example.org/groups/
 */
?>

<?php get_header() ?>

<div id="directory-main">
	
	<form action="<?php echo site_url() . '/' ?>" method="post" id="groups-directory-form">
		<h3><?php _e( 'Groups Directory', 'buddypress' ) ?></h3>
		
		<ul id="letter-list">
			<li><a href="#a" id="letter-a">A</a></li>
			<li><a href="#b" id="letter-b">B</a></li>
			<li><a href="#c" id="letter-c">C</a></li>
			<li><a href="#d" id="letter-d">D</a></li>
			<li><a href="#e" id="letter-e">E</a></li>
			<li><a href="#f" id="letter-f">F</a></li>
			<li><a href="#g" id="letter-g">G</a></li>
			<li><a href="#h" id="letter-h">H</a></li>
			<li><a href="#i" id="letter-i">I</a></li>
			<li><a href="#j" id="letter-j">J</a></li>
			<li><a href="#k" id="letter-k">K</a></li>
			<li><a href="#l" id="letter-l">L</a></li>
			<li><a href="#m" id="letter-m">M</a></li>
			<li><a href="#n" id="letter-n">N</a></li>
			<li><a href="#o" id="letter-o">O</a></li>
			<li><a href="#p" id="letter-p">P</a></li>
			<li><a href="#q" id="letter-q">Q</a></li>
			<li><a href="#r" id="letter-r">R</a></li>
			<li><a href="#s" id="letter-s">S</a></li>
			<li><a href="#t" id="letter-t">T</a></li>
			<li><a href="#u" id="letter-u">U</a></li>
			<li><a href="#v" id="letter-v">V</a></li>
			<li><a href="#w" id="letter-w">W</a></li>
			<li><a href="#x" id="letter-x">X</a></li>
			<li><a href="#y" id="letter-y">Y</a></li>
			<li><a href="#z" id="letter-z">Z</a></li>
		</ul>

		<div class="clear"></div>

		<div id="groups-directory-listing" class="directory-listing">
			<h3><?php _e( 'Groups Listing', 'buddypress' ) ?></h3>
			
			<div id="group-dir-list">
				<?php load_template( TEMPLATEPATH . '/directories/groups/groups-loop.php' ) ?>
			</div>

		</div>

		<?php do_action( 'bp_core_directory_groups_content' ) ?>
		<?php wp_nonce_field( 'directory_groups', '_wpnonce-group-filter' ) ?>

	</form>
	
</div>

<div id="directory-sidebar">

	<div id="groups-directory-search" class="directory-search">
		<h3><?php _e( 'Find Groups', 'buddypress' ) ?></h3>

		<?php bp_directory_groups_search_form() ?>

	</div>

	<div id="groups-directory-featured" class="directory-featured">
		<h3><?php _e( 'Featured Groups', 'buddypress' ) ?></h3>
		
		<?php if ( bp_has_site_groups( 'type=random&max=3' ) ) : ?>

			<ul id="groups-list" class="item-list">
			<?php while ( bp_site_groups() ) : bp_the_site_group(); ?>

				<li>
					<div class="item-avatar">
						<a href="<?php bp_the_site_group_link() ?>"><?php bp_the_site_group_avatar_thumb() ?></a>
					</div>

					<div class="item">
						<div class="item-title"><a href="<?php bp_the_site_group_link() ?>"><?php bp_the_site_group_name() ?></a></div>
						<div class="item-meta"><span class="activity"><?php bp_the_site_group_last_active() ?></span></div>
						
						<div class="field-data">
							<div class="field-name">
								<strong><?php _e( 'Members:', 'buddypress' ) ?></strong>
								<?php bp_the_site_group_member_count() ?>
							</div>
							
							<div class="field-name">
								<strong><?php _e( 'Description:', 'buddypress' ) ?></strong>
								<?php bp_the_site_group_description() ?>
							</div>
						</div>
						
						<?php do_action( 'bp_core_directory_groups_content' ) ?>
					</div>
					

					<div class="clear"></div>
				</li>

			<?php endwhile; ?>
			</ul>		

		<?php else: ?>

			<div id="message" class="info">
				<p><?php _e( 'No groups found.', 'buddypress' ) ?></p>
			</div>

		<?php endif; ?>
	
	</div>

</div>

<?php get_footer() ?>