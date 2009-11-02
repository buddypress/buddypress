<?php get_header() ?>

	<?php do_action( 'bp_before_directory_members_content' ) ?>

	<div id="content">

		<div class="page" id="members-directory-page">

			<form action="<?php echo site_url() . '/' ?>" method="post" id="members-directory-form">
				<h3><?php _e( 'Members Directory', 'buddypress' ) ?></h3>

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

				<div id="members-directory-listing" class="directory-widget">
					<h3><?php _e( 'Member Listing', 'buddypress' ) ?></h3>

					<div id="member-dir-list">
						<?php locate_template( array( 'directories/members/members-loop.php' ), true ) ?>
					</div>

				</div>

				<?php do_action( 'bp_directory_members_content' ) ?>

				<?php wp_nonce_field( 'directory_members', '_wpnonce-member-filter' ) ?>

			</form>

		</div>

	</div>

	<?php do_action( 'bp_after_directory_members_content' ) ?>
	<?php do_action( 'bp_before_directory_members_sidebar' ) ?>

	<div id="sidebar" class="directory-sidebar">

		<?php do_action( 'bp_before_directory_members_search' ) ?>

		<div id="members-directory-search" class="directory-widget">
			<h3><?php _e( 'Find Members', 'buddypress' ) ?></h3>

			<?php bp_directory_members_search_form() ?>

			<?php do_action( 'bp_directory_members_search' ) ?>
		</div>

		<?php do_action( 'bp_after_directory_members_search' ) ?>
		<?php do_action( 'bp_before_directory_members_featured' ) ?>

		<div id="members-directory-featured" class="directory-widget">
			<h3><?php _e( 'Random Members', 'buddypress' ) ?></h3>

			<?php if ( bp_has_site_members( 'type=random&max=3' ) ) : ?>

				<ul id="featured-members-list" class="item-list">
				<?php while ( bp_site_members() ) : bp_the_site_member(); ?>

					<li>
						<div class="item-avatar">
							<a href="<?php bp_the_site_member_link() ?>"><?php bp_the_site_member_avatar() ?></a>
						</div>

						<div class="item">
							<div class="item-title"><a href="<?php bp_the_site_member_link() ?>"><?php bp_the_site_member_name() ?></a></div>
							<div class="item-meta"><span class="activity"><?php bp_the_site_member_last_active() ?></span></div>

							<div class="field-data">
								<div class="field-name"><?php bp_the_site_member_total_friend_count() ?></div>
								<div class="field-name xprofile-data"><?php bp_the_site_member_random_profile_data() ?></div>
							</div>

							<?php do_action( 'bp_directory_members_featured_item' ) ?>
						</div>
					</li>

				<?php endwhile; ?>
				</ul>

				<?php do_action( 'bp_directory_members_featured' ) ?>

			<?php else: ?>

				<div id="message" class="info">
					<p><?php _e( 'There are not enough members to feature.', 'buddypress' ) ?></p>
				</div>

			<?php endif; ?>

		</div>

		<?php do_action( 'bp_after_directory_members_featured' ) ?>

	</div>

	<?php do_action( 'bp_after_directory_members_sidebar' ) ?>

<?php get_footer() ?>