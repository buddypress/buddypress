jQuery(document).ready( function() {
	jQuery("#wp-admin-bar ul.main-nav li").mouseover( function() {
		jQuery(this).addClass('sfhover');
	});
	
	jQuery("#wp-admin-bar ul.main-nav li").mouseout( function() {
		jQuery(this).removeClass('sfhover');
	});
});