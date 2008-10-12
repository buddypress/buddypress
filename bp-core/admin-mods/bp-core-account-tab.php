<?php

$is_profile_page = true;

$title = $is_profile_page? __('Profile', 'buddypress') : __('Edit User', 'buddypress');
if ( current_user_can('edit_users') && !$is_profile_page )
	$submenu_file = 'users.php';
else
	$submenu_file = 'profile.php';
$parent_file = 'users.php';

wp_reset_vars(array('action', 'redirect', 'profile', 'user_id', 'wp_http_referer'));

$wp_http_referer = remove_query_arg(array('update', 'delete_count'), stripslashes($wp_http_referer));

$user_id = (int) $user_id;

if ( !$user_id )
	if ( $is_profile_page ) {
		$current_user = wp_get_current_user();
		$user_id = $current_user->ID;
	} else {
		wp_die(__('Invalid user ID.', 'buddypress'));
	}

// Only allow site admins to edit every user. 
if ( !is_site_admin() && ($user_id != $current_user->ID) ) 
	wp_die('You do not have permission to edit this user.'); 
	
switch ($action) {
case 'switchposts':

check_admin_referer();

/* TODO: Switch all posts from one user to another user */

break;

case 'update':

check_admin_referer('update-user_' . $user_id);

if ( !current_user_can('edit_user', $user_id) )
	wp_die(__('You do not have permission to edit this user.', 'buddypress'));

if ( $is_profile_page ) {
	do_action('personal_options_update');
}

$cap = $wpdb->get_var( "SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = '{$user_id}' AND meta_key = '{$wpdb->base_prefix}{$wpdb->blogid}_capabilities' AND meta_value = 'a:0:{}'" );
$errors = edit_user($user_id);
if( $cap == null ) // stops users being added to current blog when they are edited
	$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE user_id = '{$user_id}' AND meta_key = '{$wpdb->base_prefix}{$wpdb->blogid}_capabilities' AND meta_value = 'a:0:{}'" );

if( !is_wp_error( $errors ) ) {
	$redirect = ($is_profile_page? "admin.php?page=bp-core/admin-mods/bp-core-account-tab.php&" : "admin.php?page=bp-core/admin-mods/bp-core-settings-tab.php&user_id=$user_id&"). "updated=true";
	$redirect = add_query_arg('wp_http_referer', urlencode($wp_http_referer), $redirect);
	wp_redirect($redirect);
	exit;
}

default:
$profileuser = get_user_to_edit($user_id);

if ( !current_user_can('edit_user', $user_id) )
		wp_die(__('You do not have permission to edit this user.', 'buddypress'));

?>

<?php if ( isset($_GET['updated']) ) : ?>
<div id="message" class="updated fade">
	<p><strong><?php _e('User updated.', 'buddypress') ?></strong></p>
	<?php if ( $wp_http_referer && !$is_profile_page ) : ?>
	<p><a href="users.php"><?php _e('&laquo; Back to Authors and Users', 'buddypress'); ?></a></p>
	<?php endif; ?>
</div>
<?php endif; ?>
<?php if ( is_wp_error( $errors ) ) { ?>
<div class="error">
	<ul>
	<?php
	foreach( $errors->get_error_messages() as $message )
		echo "<li>$message</li>";
	?>
	</ul>
</div>
<?php } else { ?>

<div class="wrap" id="profile-page">
<h2><?php $is_profile_page? _e('Account Settings', 'buddypress') : _e('Edit User Account Settings', 'buddypress'); ?></h2>

<form name="profile" id="your-profile" action="admin.php?page=bp-core/admin-mods/bp-core-account-tab.php" method="post">
<?php wp_nonce_field('update-user_' . $user_id) ?>
<?php if ( $wp_http_referer ) : ?>
	<input type="hidden" name="wp_http_referer" value="<?php echo clean_url($wp_http_referer); ?>" />
<?php endif; ?>
<p>
<input type="hidden" name="from" value="profile" />
<input type="hidden" name="checkuser_id" value="<?php echo $user_ID ?>" />
<input type="hidden" name="user_login" id="user_login" value="<?php echo $profileuser->user_login; ?>" disabled="disabled" />
</p>

<h3><?php _e('Personal Options', 'buddypress'); ?></h3>

<table class="form-table">
<?php if ( rich_edit_exists() ) : // don't bother showing the option if the editor has been removed ?>
	<tr>
		<th scope="row"><?php _e('Visual Editor', 'buddypress')?></th>
		<td><label for="rich_editing"><input name="rich_editing" type="checkbox" id="rich_editing" value="true" <?php checked('true', $profileuser->rich_editing); ?> /> <?php _e('Use the visual editor when writing', 'buddypress'); ?></label></td>
	</tr>
<?php endif; ?>
<tr>
<th scope="row"><?php _e('Admin Color Scheme', 'buddypress')?></th>
<td><fieldset><legend class="hidden"><?php _e('Admin Color Scheme', 'buddypress')?></legend>
<?php
$current_color = get_user_option('admin_color', $user_id);
if ( empty($current_color) )
	$current_color = 'fresh';
foreach ( $_wp_admin_css_colors as $color => $color_info ): ?>
<div class="color-option"><input name="admin_color" id="admin_color_<?php echo $color; ?>" type="radio" value="<?php echo $color ?>" class="tog" <?php checked($color, $current_color); ?> />
	<table class="color-palette">
	<tr>
	<?php
	foreach ( $color_info->colors as $html_color ): ?>
	<td style="background-color: <?php echo $html_color ?>" title="<?php echo $color ?>">&nbsp;</td>
	<?php endforeach; ?>
	</tr>
	</table>
	
	<label for="admin_color_<?php echo $color; ?>"><?php echo $color_info->name ?></label>
</div>
<?php endforeach; ?>
</fieldset></td>
</tr>
</table>

<h3><?php $is_profile_page? _e('Your Account Details', 'buddypress') : _e('User Account Details', 'buddypress'); ?></h3>

<table class="form-table">
<tr>
	<th><label for="email">* <?php _e('Account Email', 'buddypress') ?></label></th>
	<td><input type="text" name="email" id="email" value="<?php echo $profileuser->user_email ?>" /> (<?php _e('Required', 'buddypress'); ?>)</td>
</tr>
<?php
$show_password_fields = apply_filters('show_password_fields', true);
if ( $show_password_fields ) :
?>
<tr>
	<th><label for="pass1"><?php _e('New Password', 'buddypress'); ?></label></th>
	<td><input type="password" name="pass1" id="pass1" size="16" value="" /> <?php _e("If you would like to change the password type a new one. Otherwise leave this blank.", 'buddypress'); ?><br />
		<input type="password" name="pass2" id="pass2" size="16" value="" /> <?php _e("Type your new password again.", 'buddypress'); ?><br />
		<?php if ( $is_profile_page ): ?>
		<p><strong><?php _e('Password Strength', 'buddypress'); ?></strong></p>
		<div id="pass-strength-result"><?php _e('Too short', 'buddypress'); ?></div> <?php _e('Hint: Use upper and lower case characters, numbers and symbols like !"?$%^&amp;( in your password.', 'buddypress'); ?>
		<?php endif; ?>
	</td>
</tr>
<?php endif; ?>
</table>

<?php
	if ( $is_profile_page ) {
		do_action('show_user_profile');
	} else {
		do_action('edit_user_profile');
	}
?>

<?php if (count($profileuser->caps) > count($profileuser->roles)): ?>
<br class="clear" />
	<table width="99%" style="border: none;" cellspacing="2" cellpadding="3" class="editform">
		<tr>
			<th scope="row"><?php _e('Additional Capabilities', 'buddypress') ?></th>
			<td><?php
			$output = '';
			foreach($profileuser->caps as $cap => $value) {
				if(!$wp_roles->is_role($cap)) {
					if($output != '') $output .= ', ';
					$output .= $value ? $cap : "Denied: {$cap}";
				}
			}
			echo $output;
			?></td>
		</tr>
	</table>
<?php endif; ?>

<p class="submit">
	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="user_id" id="user_id" value="<?php echo $user_id; ?>" />
	<input type="submit" value="<?php $is_profile_page? _e('Update Profile', 'buddypress') : _e('Update User', 'buddypress') ?>" name="submit" />
 </p>
</form>
</div>
<?php
}
break;
}

?>
