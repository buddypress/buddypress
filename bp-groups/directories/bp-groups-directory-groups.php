<?php
function groups_directory_groups_setup() {
	global $bp, $current_blog;
	
	if ( $bp->current_component == $bp->groups->slug && empty( $bp->current_action ) ) {
		add_action( 'bp_template_content', 'groups_directory_groups_content' );
		add_action( 'bp_template_sidebar', 'groups_directory_groups_sidebar' );
		
		wp_enqueue_script( 'bp-groups-directory-groups', site_url( MUPLUGINDIR . '/bp-groups/js/directory-groups.js' ), array( 'jquery', 'jquery-livequery-pack' ) );
		wp_enqueue_style( 'bp-groups-directory-groups', site_url( MUPLUGINDIR . '/bp-groups/css/directory-groups.css' ) );

		/* If you include a groups-directory.php template file in your home/blog theme, you can overide the standard output */		
		if ( file_exists( TEMPLATEPATH . '/groups-directory.php' ) )
			bp_core_load_template( 'groups-directory' );
		else if ( file_exists( TEMPLATEPATH . '/plugin-template.php' ) )
			bp_core_load_template('plugin-template');
		else
			wp_die( __( 'To enable the group directory you must drop the "plugin-template.php" file into your theme directory.', 'buddypress' ) );
	}
}
add_action( 'wp', 'groups_directory_groups_setup', 5 );

function groups_directory_groups_content() {
	global $bp;
	
	$pag_page = isset( $_GET['page'] ) ? intval( $_GET['page'] ) : 1;
	$pag_num = isset( $_GET['num'] ) ? intval( $_GET['num'] ) : 10;
	
	if ( isset( $_GET['s'] ) )
		$groups = BP_Groups_Group::search_groups( $_GET['s'], $pag_num, $pag_page );
	else
		$groups = groups_get_active( $pag_num, $pag_page );

	$groups = apply_filters( 'groups_directory_groups_content', $groups );
	 
	$pag_links = paginate_links( array(
		'base' => add_query_arg( 'page', '%#%' ),
		'format' => '',
		'total' => ceil( $groups['total'] / $pag_num ),
		'current' => $pag_page,
		'prev_text' => '&laquo;',
		'next_text' => '&raquo;',
		'mid_size' => 1
	));
	
	$from_num = intval( ( $pag_page - 1 ) * $pag_num ) + 1;
	$to_num = ( $from_num + ( $pag_num - 1  ) > $groups['total'] ) ? $groups['total'] : $from_num + ( $pag_num - 1 ); 
	
?>	
<div id="content" class="narrowcolumn">
	
	<form action="<?php echo site_url() . '/' ?>" method="post" id="groups-directory-form">
	<div class="widget">
		<h2 class="widgettitle"><?php _e( 'Group Directory', 'buddypress' ) ?></h2>
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
		<h2 class="widgettitle"><?php _e( 'Group Listing', 'buddypress' ) ?></h2>
		
		<div id="group-dir-list">
		<?php if ( $groups['groups'] ) : ?>
			<div id="group-dir-count" class="pag-count">
				<?php echo sprintf( __( 'Viewing group %d to %d (%d total active groups)', 'buddypress' ), $from_num, $to_num, $groups['total'] ); ?> &nbsp;
				<img id="ajax-loader-groups" src="<?php echo $bp->core->image_base ?>/ajax-loader.gif" height="7" alt="<?php _e( "Loading", "buddypress" ) ?>" style="display: none;" />
			</div>
			
			<div class="pagination-links" id="group-dir-pag">
				<?php echo $pag_links ?>
			</div>

			<ul id="groups-list" class="item-list">
			<?php foreach ( $groups['groups'] as $group ) : ?>
				<?php $group = new BP_Groups_Group( $group->group_id, false, false ); ?>
				<li>
					<div class="item-avatar">
						<a href="<?php echo bp_group_permalink( $group ) ?>" title="<?php echo $group->name ?>"><img src="<?php echo $group->avatar_thumb ?>" class="avatar" alt="<?php echo $group->name ?> Avatar" /></a>
					</div>

					<div class="item">
						<div class="item-title"><a href="<?php echo bp_group_permalink( $group ) ?>" title="<?php echo $group->name ?>"><?php echo $group->name ?></a></div>
						<div class="item-meta"><span class="activity"><?php echo bp_core_get_last_activity( groups_get_groupmeta( $group->id, 'last_activity' ), __('active %s ago') ) ?></span></div>
						<div class="item-meta desc"><?php echo bp_create_excerpt( $group->description ) ?></div>
						
						<?php do_action( 'groups_directory_groups_content', $group ) ?>
					</div>
					
					<div class="action">
						<?php bp_group_join_button( $group ) ?>
						<div class="meta">
							<?php $member_count = groups_get_groupmeta( $group->id, 'total_member_count' ) ?>
							<?php echo ucwords($group->status) ?> <?php _e( 'Group', 'buddypress' ) ?> / 
							<?php if ( $member_count == 1 ) : ?>
								<?php printf( __( '%d member', 'buddypress' ), $member_count ) ?>
							<?php else : ?>
								<?php printf( __( '%d members', 'buddypress' ), $member_count ) ?>
							<?php endif; ?>
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
				<p><?php _e( 'No groups found.', 'buddypress' ) ?></p>
			</div>
		<?php endif; ?>
		</div>
		
	</div>
	
	<?php do_action( 'groups_directory_groups_content' ) ?>
	
	<?php wp_nonce_field('directory_groups', '_wpnonce-group-filter' ) ?>
	</form>

</div>
<?php
}

function groups_directory_groups_sidebar() {
	global $bp;
?>	
	<div class="widget">
		<h2 class="widgettitle"><?php _e( 'Find Groups', 'buddypress' ) ?></h2>
		<form action="<?php echo site_url() . '/' . $bp->groups->slug  . '/search/' ?>" method="post" id="search-groups-form">
			<label><input type="text" name="groups_search" id="groups_search" value="<?php if ( isset( $_GET['s'] ) ) { echo $_GET['s']; } else { _e('Search anything...', 'buddypress' ); } ?>"  onfocus="if (this.value == '<?php _e('Search anything...', 'buddypress' ) ?>') {this.value = '';}" onblur="if (this.value == '') {this.value = '<?php _e('Search anything...', 'buddypress' ) ?>';}" /></label>
			<input type="submit" id="groups_search_submit" name="groups_search_submit" value="Search" />
		</form>
	</div>
	
	<div class="widget">
		<h2 class="widgettitle"><?php _e( 'Featured Groups', 'buddypress' ) ?></h2>
		
		<?php $groups = BP_Groups_Group::get_random( 3, 1 ) ?>
		
		<?php if ( $groups['groups'] ) { ?>
			<ul id="featured-group-list" class="item-list">
				<?php foreach( $groups['groups'] as $group ) : ?>
					<?php $group = new BP_Groups_Group( $group->group_id, false, false ); ?>
					<li>
						<div class="item-avatar">
							<a href="<?php echo bp_group_permalink( $group ) ?>" title="<?php echo $group->name ?>"><img src="<?php echo $group->avatar_thumb ?>" class="avatar" alt="<?php echo $group->name ?> Avatar" /></a>
						</div>

						<div class="item">
							<div class="item-title"><a href="<?php echo bp_group_permalink( $group ) ?>" title="<?php echo $group->name ?>"><?php echo $group->name ?></a></div>
							<div class="item-meta"><span class="activity"><?php echo bp_core_get_last_activity( groups_get_groupmeta( $group->id, 'last_activity' ), __('active %s ago') ) ?></span></div>
						
							<div class="item-title group-data">
								<p class="field-name"><?php _e( 'Members', 'buddypress' ) ?>: <span><?php echo groups_get_groupmeta( $group->id, 'total_member_count' ) ?></span></p>
								<p class="field-name"><?php _e( 'Description', 'buddypress' ) ?>:</p>
								<?php echo bp_create_excerpt( $group->description ) ?>
							</div>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php } else { ?>
			<div id="message" class="info">
				<p><?php _e( 'There are no groups to feature.', 'buddypress' ) ?></p>
			</div>
		<?php } ?>
	</div>

	<?php do_action( 'groups_directory_groups_sidebar' ) ?>
	
<?php
}
