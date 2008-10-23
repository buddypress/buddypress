<?
function bp_core_directory_members_setup() {
	global $bp, $current_blog;
	
	if ( $bp['current_component'] == 'members' && $bp['current_action'] == '' ) {
		add_action( 'bp_template_content', 'bp_core_directory_members_content' );
		add_action( 'bp_template_sidebar', 'bp_core_directory_members_sidebar' );
		
		wp_enqueue_script( 'bp-core-directory-members', site_url() . '/wp-content/mu-plugins/bp-core/js/directory-members.js', array( 'jquery', 'jquery-livequery-pack' ) );
		wp_enqueue_style( 'bp-core-directory-members', site_url() . '/wp-content/mu-plugins/bp-core/css/directory-members.css' );
		
		if ( file_exists( TEMPLATEPATH . '/plugin-template.php' ) )
			bp_catch_uri('plugin-template');
		else
			wp_die( __( 'To enable the member directory you must drop the "plugin-template.php and plugin-sidebar.php" files into your theme directory.', 'buddypress' ) );
	}
}
add_action( 'wp', 'bp_core_directory_members_setup', 5 );

function bp_core_directory_members_content() {
	global $bp;
	
	$users = BP_Core_User::get_active_users(10);
?>	
	<form action="<?php echo site_url() . '/' ?>" method="post" id="members-directory-form">
	<div class="widget">
		<h2 class="widgettitle"><?php _e('Member Directory') ?></h2>
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
		<h2 class="widgettitle"><?php _e('Member Listing') ?></h2>
		<?php if ( $users ) : ?>
			<div class="item-options" id="members-list-options">
				<img id="ajax-loader-members" src="<?php echo $bp['core']['image_base'] ?>/ajax-loader.gif" height="7" alt="Loading" style="display: none;" /> &nbsp;
			</div>
			<ul id="members-list" class="item-list">
			<?php foreach ( $users as $user ) : ?>
				<?php $user_obj = new BP_Core_User( $user->user_id, true ); ?>
				<li>
					<div class="item-avatar">
						<?php echo $user_obj->avatar_thumb ?>
					</div>

					<div class="item">
						<div class="item-title"><?php echo $user_obj->user_link ?></div>
						<div class="item-meta"><span class="activity"><?php echo $user_obj->last_active ?></span></div>
					</div>
					
					<div class="action">
						<?php bp_add_friend_button( $user_obj->id ) ?>
						<?php if ( $user_obj->total_friends ) echo $user_obj->total_friends ?>
						<?php if ( $user_obj->total_blogs ) echo $user_obj->total_blogs ?>
						<?php if ( $user_obj->total_groups ) echo $user_obj->total_groups ?>
					</div>
					
					<div class="clear"></div>
				</li>
			<?php endforeach; ?>
			</ul>	
		<?php endif; ?>
	</div>
	<?php wp_nonce_field('directory_members') ?>
	</form>
	
	
<?php
}

function bp_core_directory_members_sidebar() {
	global $bp;
?>	
	<div class="widget">
		<h2 class="widgettitle"><?php _e('Search Members') ?></h2>
		<form action="<?php echo site_url() . '/members/search/' ?>" method="post" id="search-members-form">
			<label><input type="text" name="members_search" id="members_search" value="" /></label>
			<input type="submit" id="members_search_submit" name="members_search_submit" value="Search" />
		</form>
	</div>
	
	<div class="widget">
		<h2 class="widgettitle"><?php _e('Members') ?></h2>
	</div>
<?php
}
