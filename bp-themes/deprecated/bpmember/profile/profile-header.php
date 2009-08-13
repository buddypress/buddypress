<div id="profile-name">
	<h1 class="fn"><a href="<?php bp_user_link() ?>"><?php bp_user_fullname() ?></a></h1>
	
	<?php if ( function_exists( 'bp_the_status' ) ) : ?>
	<div id="user-status">
		<p><?php bp_the_status() ?></p>
	</div>
	<?php endif; ?>
</div>