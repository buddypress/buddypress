<?php
function bp_core_directory_members_setup() {
	global $bp, $current_blog;

	if ( $bp->current_component == MEMBERS_SLUG && empty( $bp->current_action ) ) {
		add_action( 'bp_template_content', 'bp_core_directory_members_content' );
		add_action( 'bp_template_sidebar', 'bp_core_directory_members_sidebar' );
		
		wp_enqueue_script( 'bp-core-directory-members', site_url( MUPLUGINDIR . '/bp-core/js/directory-members.js' ), array( 'jquery', 'jquery-livequery-pack' ) );
		wp_enqueue_style( 'bp-core-directory-members', site_url( MUPLUGINDIR . '/bp-core/css/directory-members.css' ) );
		
		/* If you include a members-directory.php template file in your home/blog theme, you can overide the standard output */
		if ( file_exists( TEMPLATEPATH . '/members-directory.php' ) )
			bp_core_load_template( 'members-directory' );
		else if ( file_exists( TEMPLATEPATH . '/plugin-template.php' ) )
			bp_core_load_template( 'plugin-template' );
		else
			wp_die( __( 'To enable the member directory you must drop the "plugin-template.php and plugin-sidebar.php" files into your theme directory.', 'buddypress' ) );
	}
}
add_action( 'wp', 'bp_core_directory_members_setup', 5 );

function bp_core_directory_members_content() {
	global $bp;
	
	$pag_page = isset( $_GET['page'] ) ? intval( $_GET['page'] ) : 1;
	$pag_num = isset( $_GET['num'] ) ? intval( $_GET['num'] ) : 10;
	
	if ( isset( $_GET['s'] ) )
		$users = BP_Core_User::search_users( $_GET['s'], $pag_num, $pag_page );
	else
		$users = BP_Core_User::get_active_users( $pag_num, $pag_page );

	$users = apply_filters( 'bp_core_directory_members_content', $users );

	$pag_links = paginate_links( array(
		'base' => add_query_arg( 'page', '%#%' ),
		'format' => '',
		'total' => ceil( $users['total'] / $pag_num ),
		'current' => $pag_page,
		'prev_text' => '&laquo;',
		'next_text' => '&raquo;',
		'mid_size' => 1
	));
	
	$from_num = intval( ( $pag_page - 1 ) * $pag_num ) + 1;
	$to_num = ( $from_num + 9 > $users['total'] ) ? $users['total'] : $from_num + 9; 
	
?>	
<div id="content" class="narrowcolumn">
	
	<form action="<?php echo site_url() . '/' ?>" method="post" id="members-directory-form">
	<div class="widget">
		<h2 class="widgettitle"><?php _e( 'Member Directory', 'buddypress' ) ?></h2>
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
	</div>
	
	<div class="widget">
		<h2 class="widgettitle"><?php _e( 'Member Listing', 'buddypress' ) ?></h2>
		
		<div id="member-dir-list">
		<?php if ( $users['users'] ) : ?>
			<div id="member-dir-count" class="pag-count">
				<?php echo sprintf( __( 'Viewing member %d to %d (%d total active members)', 'buddypress' ), $from_num, $to_num, $users['total'] ); ?> &nbsp;
				<img id="ajax-loader-members" src="<?php echo $bp->core->image_base ?>/ajax-loader.gif" height="7" alt="<?php _e( "Loading", "buddypress" ) ?>" style="display: none;" />
			</div>
			
			<div class="pagination-links" id="member-dir-pag">
				<?php echo $pag_links ?>
			</div>

			<ul id="members-list" class="item-list">
			<?php foreach ( $users['users'] as $user ) : ?>
				<li>
					<div class="item-avatar">
						<a href="<?php echo bp_core_get_userlink( $user->user_id, false, true ) ?>"><?php echo bp_core_get_avatar( $user->user_id, 1 ) ?></a>
					</div>

					<div class="item">
						<div class="item-title"><?php echo bp_core_get_userlink( $user->user_id ) ?></div>
						<div class="item-meta"><span class="activity"><?php echo bp_core_get_last_activity( get_usermeta( $user->user_id, 'last_activity' ), __( 'active %s ago', 'buddypress' ) ) ?></span></div>

						<?php do_action( 'bp_core_directory_members_content', $user ) ?>
					</div>
					
					<div class="action">
						<?php if ( function_exists( 'bp_add_friend_button' ) ) { ?>
							<?php bp_add_friend_button( $user->user_id ) ?>
						<?php } ?>
						<div class="meta">
							<?php if ( $user_obj->total_friends ) echo $user_obj->total_friends ?><?php if ( $user_obj->total_blogs ) echo ', ' . $user_obj->total_blogs ?><?php if ( $user_obj->total_groups ) echo ', ' . $user_obj->total_groups ?>
						</div>
					</div>
					
					<div class="clear"></div>
				</li>
			<?php endforeach; ?>
			</ul>	
			
			<?php
			if ( isset( $_GET['s'] ) ) {
				echo '<input type="hidden" id="search_terms" value="' . $_GET['s'] . '" name="search_terms" />';
			}
			?>
				
		<?php else: ?>
			<div id="message" class="info">
				<p><?php _e( 'No members found. Members must fill in at least one piece of profile data to show in member lists.', 'buddypress' ) ?></p>
			</div>
		<?php endif; ?>
		</div>		
	</div>

	<?php do_action( 'bp_core_directory_members_content' ) ?>

	<?php wp_nonce_field('directory_members', '_wpnonce-member-filter' ) ?>
	</form>
	
</div>
<?php
}

function bp_core_directory_members_sidebar() {
	global $bp;
?>	
	<div class="widget">
		<h2 class="widgettitle"><?php _e( 'Find Members', 'buddypress' ) ?></h2>
		<form action="<?php echo site_url() . '/' . MEMBERS_SLUG  . '/search/' ?>" method="post" id="search-members-form">
			<label><input type="text" name="members_search" id="members_search" value="<?php if ( isset( $_GET['s'] ) ) { echo $_GET['s']; } else { _e('Search anything...', 'buddypress' ); } ?>"  onfocus="if (this.value == '<?php _e('Search anything...', 'buddypress' ) ?>') {this.value = '';}" onblur="if (this.value == '') {this.value = '<?php _e('Search anything...', 'buddypress' ) ?>';}" /></label>
			<input type="submit" id="members_search_submit" name="members_search_submit" value="Search" />
		</form>
	</div>
	
	<div class="widget">
		<h2 class="widgettitle"><?php _e( 'Featured Members', 'buddypress' ) ?></h2>
		
		<?php $users = BP_Core_User::get_random_users( 3, 1 ) ?>
		
		<?php if ( $users['users'] ) { ?>
			<ul id="featured-member-list" class="item-list">
				<?php foreach( $users['users'] as $user ) : ?>
				<li>
					<div class="item-avatar">
						<a href="<?php echo bp_core_get_userlink( $user->user_id, false, true ) ?>"><?php echo bp_core_get_avatar( $user->user_id, 1 ) ?></a>
					</div>

					<div class="item">
						<div class="item-title"><?php echo bp_core_get_userlink( $user->user_id ) ?></div>
						<div class="item-meta"><span class="activity"><?php echo bp_core_get_last_activity( get_usermeta( $user->user_id, 'last_activity' ), __( 'active %s ago', 'buddypress' ) ) ?></span></div>
					
						<?php if ( function_exists( 'xprofile_get_random_profile_data' ) ) { ?>
							<?php $random_data = xprofile_get_random_profile_data( $user->user_id, true ); ?>
							<div class="item-title profile-data">
								<p class="field-name"><?php echo $random_data[0]->name ?></p>
								<?php echo $random_data[0]->value ?>
							</div>
						<?php } ?>
					</div>
				</li>
				<?php endforeach; ?>
			</ul>
		<?php } else { ?>
			<div id="message" class="info">
				<p><?php _e( 'There are no members to feature.', 'buddypress' ) ?></p>
			</div>
		<?php } ?>
	</div>
	
	<?php do_action( 'bp_core_directory_members_sidebar' ) ?>
<?php
}
