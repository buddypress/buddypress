(function( $ ) {

	/** Profile Visibility Settings *********************************/
	$('.visibility-toggle-link').on( 'click', function( event ) {

		event.preventDefault();

		var toggle_div = $(this).parent();

		$(toggle_div).fadeOut( 600, function(){
			$(toggle_div).siblings('.field-visibility-settings').slideDown(400);
		});

	} );

	$('.field-visibility-settings-close').on( 'click', function( event ) {

		event.preventDefault();

		var settings_div = $(this).parent();
		var vis_setting_text = settings_div.find('input:checked').parent().text();

		settings_div.slideUp( 400, function() {
			settings_div.siblings('.field-visibility-settings-toggle').fadeIn(800);
			settings_div.siblings('.field-visibility-settings-toggle').children('.current-visibility-level').html(vis_setting_text);
		} );

		return false;
	} );

})(jQuery);


function clear(container) {
	if( !document.getElementById(container) ) return;

	var container = document.getElementById(container);

	if ( radioButtons = container.getElementsByTagName('INPUT') ) {
		for(var i=0; i<radioButtons.length; i++) {
			radioButtons[i].checked = '';
		}
	}

	if ( options = container.getElementsByTagName('OPTION') ) {
		for(var i=0; i<options.length; i++) {
			options[i].selected = false;
		}
	}

	return;
}