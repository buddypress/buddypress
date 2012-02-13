jQuery(document).ready( function($) {
	var toggle = $('#site-tracking-enabled');

	if ( !$(toggle).is(':checked') ) {
		$('#site-tracking-page-selector').hide();
	}

	$(toggle).click(function(){
		$('#site-tracking-page-selector').toggle('fast');
	});
},jQuery );