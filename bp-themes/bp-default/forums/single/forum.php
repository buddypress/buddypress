<?php get_header(); ?>

	<div id="content">
		<div class="padder">

			<?php do_action( 'bp_before_group_home_content' ) ?>

				<div id="item-header" role="complementary">

					<?php locate_template( array( 'forums/single/forum-header.php' ), true ); ?>

				</div><!-- #item-header -->

				<div id="item-nav">
					<div class="item-list-tabs no-ajax" id="subnav" role="navigation">
						<ul>

							<li>
								<a href="#post-new" class="show-hide-new"><?php _e( 'New Topic', 'buddypress' ) ?></a>
							</li>

							<?php if ( !bp_forum_directory_is_disabled() ) : ?>

								<li>
									<a href="<?php bp_forum_directory_permalink() ?>"><?php _e( 'Forum Directory', 'buddypress') ?></a>
								</li>

							<?php endif; ?>

							<?php do_action( 'bp_forums_directory_group_sub_types' ); ?>

							<li id="forums-order-select" class="last filter">

								<?php _e( 'Order By:', 'buddypress' ); ?>

								<select>
									<option value="active"><?php _e( 'Last Active', 'buddypress' ); ?></option>
									<option value="popular"><?php _e( 'Most Posts', 'buddypress' ); ?></option>
									<option value="unreplied"><?php _e( 'Unreplied', 'buddypress' ); ?></option>

									<?php do_action( 'bp_forums_directory_order_options' ); ?>

								</select>
							</li>
						</ul>
					</div>
				</div><!-- #item-nav -->

				<div id="item-body">

					<div id="forums-dir-list" class="forums dir-list" role="main">

						<?php locate_template( array( 'forums/forums-loop.php' ), true ); ?>

					</div>

					<?php do_action( 'bp_directory_forums_content' ); ?>

				</div>
			</div><!-- #new-topic-post -->
		</div><!-- .padder -->

	<?php do_action( 'bp_after_directory_forums_content' ); ?>

<?php get_sidebar(); ?>
<?php get_footer(); ?>
