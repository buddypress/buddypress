<?php

function bp_core_add_homebase_notice() {
	if ( !bp_core_user_has_home() )	{
		/* The logged in user hasn't assigned a "user home" blog yet. We need to
		   prompt them to do that, so they can use the BuddyPress features. */
		?>
		<div id="update-nag">
			<p><a href="admin.php?page=bp-core/homebase-creation/bp-core-homebase-tab.php"><?php _e('Create a Home Base!') ?></a><br />
			<?php _e('Create your home base and start using all the new social networking features') ?></p>
		</div>
		<?php
	}
}
add_action('admin_notices', 'bp_core_add_homebase_notice');

function bp_core_add_createhomebase_tab() {
	if ( !bp_core_user_has_home() )	{
		add_menu_page( __('Create Home Base'), __('Create Home Base'), 1, 'bp-core/homebase-creation/bp-core-homebase-tab.php');
	}
}
add_action( 'admin_head', 'bp_core_add_createhomebase_tab' );

function bp_core_notify_admin_of_homebase() {
	global $wpdb, $bp;
	
	if ( ( is_site_admin() && $bp['current_userid'] != $bp['loggedin_userid'] ) && ( $wpdb->blogid == get_usermeta( $bp['current_userid'], 'home_base' ) ) ) { ?>
		<div id="update-nag">
			<p><strong><?php _e('Administrator Notice:') ?></strong> <?php _e('This is a user home base, not a blog.') ?></p>
		</div>	
	<?php	
	}		
}
add_action('admin_notices', 'bp_core_notify_admin_of_homebase');

function bp_core_remove_home_base( $blog_id ) {
	global $wpdb, $bp;

	/* Only home_base meta settings if we are removing a home base */
	if ( $user_id = bp_core_get_homebase_userid( $blog_id ) ) {
		delete_usermeta( (int)$user_id, 'home_base' );
	}
}
add_action( 'delete_blog', 'bp_core_remove_home_base', 10 );

function bp_core_homebase_signup_form($blogname = '', $blog_title = '', $errors = '') {
	global $current_user, $current_site;
	
	if ( !is_wp_error($errors) ) {
		$errors = new WP_Error();
	}

	// allow definition of default variables
	$filtered_results = apply_filters('signup_another_blog_init', array('blogname' => $blogname, 'blog_title' => $blog_title, 'errors' => $errors ));
	$blogname = $filtered_results['blogname'];
	$blog_title = $filtered_results['blog_title'];
	$errors = $filtered_results['errors'];

	if ( $errors->get_error_code() ) {
		echo "<p>" . __('There was a problem, please correct the form below and try again.') . "</p>";
	}
	?>
	
	<div class="wrap">
		<p><h2><?php _e('Create Your Home Base') ?></h2></p>
		<div>
			<h3><?php _e('What\'s a home base and how do I make one?') ?></h3>
			<p><?php _e('Your home base will be where you and other members go to view your profile, groups, friends and more.') ?></p>
			<p><?php _e('Creating a home base is easy, all you have to do is fill in the form below. Once your home base is created, you can start using all the new features. Any existing blogs will be linked with your home base.') ?></p>

			<form id="setupform" method="post" action="admin.php?page=bp-core/homebase-creation/bp-core-homebase-tab.php">
				<input type="hidden" name="stage" value="gimmeanotherblog" />
				<?php do_action( "signup_hidden_fields" ); ?>
				<?php bp_core_show_homebase_form($blogname, $blog_title, $errors); ?>
				<?php do_action( 'signup_extra_fields', $errors ); ?>
				<p>
					<input id="submit" type="submit" name="submit" value="<?php _e('Create Home Base &raquo;') ?>" /></p>
			</form>
		</div>
	</div>
	<?php
}

function bp_core_show_homebase_form($blogname = '', $blog_title = '', $errors = '') {
	global $current_site;
?>
	<table class="form-table">
	<tbody>
		<tr>
			<th scope="row"><?php _e('Username') ?></th>
			<td>
			<?php
			
				if ( $errmsg = $errors->get_error_message('blogname') ) { ?>
					<p class="error"><?php echo $errmsg ?></p>
				<?php }

				if( constant( "VHOST" ) == 'no' ) {
					echo ' <span class="prefix_address">' . $current_site->domain . $current_site->path . '</span><input name="blogname" type="text" id="blogname" value="'.$blogname.'" maxlength="50" />';
				} else {
					echo ' <input name="blogname" type="text" id="blogname" value="'.$blogname.'" maxlength="50" /><span class="suffix_address"> .' . $current_site->domain . $current_site->path . '</span>';
				}
			?>
			</td>			
		</tr>
	</tbody>
	</table>

<?php
	
	// Blog Title
	?>
	<?php if ( !function_exists('xprofile_install') ) { ?>
		<label for="blog_title"><?php _e('Full Name:') ?></label>	
		<?php if ( $errmsg = $errors->get_error_message('blog_title') ) { ?>
			<p class="error"><?php echo $errmsg ?></p>
		<?php }
		echo '<input name="blog_title" type="text" id="blog_title" value="'.wp_specialchars($blog_title, 1).'" /></p>';
		?>
		<input type="hidden" name="blog_public" value="0" />
	<?php } else { ?>
		<input type="hidden" name="blog_title" value="" />
	<?php } ?> 
	
	<?php
	do_action('signup_blogform', $errors);
}

function bp_core_validate_homebase_form_primary() {
	global $domain, $current_site;
	
	$domain = $current_site->domain;
	$user = '';
	if ( is_user_logged_in() )
		$user = wp_get_current_user();

	return wpmu_validate_blog_signup($_POST['blogname'], $_POST['blog_title'], $user);
}

function bp_core_validate_homebase_form_secondary() {
	global $wpdb, $current_user, $blogname, $blog_id, $blog_title, $errors, $domain, $path, $bp_xprofile_callback, $canvas, $original, $meta, $has_errors;

	$current_user = wp_get_current_user();
	if( !is_user_logged_in() )
		die();

	$result = bp_core_validate_homebase_form_primary();
	extract($result);

	if ( $errors->get_error_code() || $has_errors ) {
		bp_core_homebase_signup_form($blogname, $blog_title, $errors);
		return false;
	}

	$public = (int) $_POST['blog_public'];
	
	$meta = array( 'lang_id' => 1, 'public' => $public );
	
	if ( function_exists('xprofile_install') ) {
		for ( $i = 0; $i < count($bp_xprofile_callback); $i++ ) {
			$meta['field_' . $bp_xprofile_callback[$i]['field_id']] .= $bp_xprofile_callback[$i]['value'];
		}

		$meta['xprofile_field_ids'] = $_POST['xprofile_ids'];
		$meta['avatar_image_resized'] = $canvas;
		$meta['avatar_image_original'] = $original;
	}
	
	$blog_id = wpmu_create_blog( $domain, $path, $blog_title, $current_user->id, $meta, $wpdb->siteid );

	/* Make this blog the "home base" for the new user */
	update_usermeta( $current_user->id, 'home_base', $blog_id );

	/* Set the BuddyPress theme as the theme for this blog */
	$wpdb->set_blog_id( $blog_id );	
	switch_theme( 'buddypress', 'buddypress' );

	// If xprofile is installed, extract the metadata into the correct tables
	if ( function_exists('xprofile_install') ) {
		bp_core_extract_homebase_metadata();
	} else {		
		bp_core_confirm_homebase_signup( $blog_id );
	}

	return true;
}

function bp_core_confirm_homebase_signup( $blog_id = null ) {
	global $current_user, $wpdb;
		
	do_action( 'bp_homebase_signup_completed', $blog_id );

	if ( function_exists('xprofile_install') ) {
		if ( isset( $_GET['cropped'] ) ) {
			// Confirm that the nonce is valid
			if ( isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'slick_avatars') ) {
				$user_id = $current_user->id;

				if ( $user_id && isset($_POST['orig']) && isset($_POST['canvas']) ) {
					bp_core_check_crop( $_POST['orig'], $_POST['canvas'] );
					$result = bp_core_avatar_cropstore( $_POST['orig'], $_POST['canvas'], $_POST['v1_x1'], $_POST['v1_y1'], $_POST['v1_w'], $_POST['v1_h'], $_POST['v2_x1'], $_POST['v2_y1'], $_POST['v2_w'], $_POST['v2_h'] );
					$crop_url = get_blog_option($blog_id, 'siteurl') . '/';
					bp_core_avatar_save($result, $user_id, false, $_GET['crop_url'] );
				}
			}
		}
	}
	
	$url = get_blog_option( $blog_id, 'siteurl' ) . '/';	
	do_action('signup_finished');

	wp_redirect( $url . '/wp-admin' );
}

function bp_core_extract_homebase_metadata() {
	global $meta, $bp, $blog_id, $wpdb;

	$field_ids = explode( ',', $meta['xprofile_field_ids'] );
	$user_id = $bp['loggedin_userid'];
	
	// Loop through each bit of profile data and save it to profile.
	for ( $i = 0; $i < count($field_ids); $i++ ) {
		$field_value = $meta['field_' . $field_ids[$i]];

		$field 				 = new BP_XProfile_ProfileData();
		$field->user_id      = $user_id;
		$field->value        = $field_value;
		$field->field_id     = $field_ids[$i];
		$field->last_updated = time();	

		$field->save();
	}

	/* Make this blog the "home base" for the new user */
	update_usermeta( $bp['loggedin_userid'], 'home_base', $blog_id );
	
	/* Set the BuddyPress theme as the theme for this blog */
	$wpdb->set_blog_id( $blog_id );
	switch_theme( 'buddypress', 'buddypress' );

	// move and set the avatar if one has been provided.
	$resized = $meta['avatar_image_resized'];
	$original = $meta['avatar_image_original'];	

	if ( $resized && $original ) {
		$upload_dir = bp_upload_dir(NULL, $blog_id);
		
		if ( $upload_dir ) {
			$resized_strip_path = explode( '/', $resized );
			$original_strip_path = explode( '/', $original );

			$resized_filename = $resized_strip_path[count($resized_strip_path) - 1];
			$original_filename = $original_strip_path[count($original_strip_path) - 1];

			$resized_new = $upload_dir['path'] . '/' . $resized_filename;
			$original_new = $upload_dir['path'] . '/' . $original_filename;

			@copy( $resized, $resized_new );
			@copy( $original, $original_new );

			@unlink($resized);
			@unlink($original);

			$resized = $resized_new;
			$original = $original_new;
		}
	
		$crop_url = get_blog_option($blog_id, 'siteurl') . '/';
		
		// Render the cropper UI
		$action = 'admin.php?page=bp-core/homebase-creation/bp-core-homebase-tab.php&amp;cropped=true&amp;crop_url=' . $crop_url;
		bp_core_render_avatar_cropper($original, $resized, $action, $user_id, false, $crop_url );
	} else {
		bp_core_confirm_homebase_signup( $blog_id );
	}
}


?>