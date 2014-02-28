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


/**
 * Deselects any select options or input options for the specified field element.
 *
 * @param {String} container HTML ID of the field
 * @since BuddyPress (1.0.0)
 */
function clear( container ) {
	container = document.getElementById( container );
	if ( ! container ) {
		return;
	}

	var radioButtons = container.getElementsByTagName( 'INPUT' ),
		options = container.getElementsByTagName( 'OPTION' ),
		i       = 0;

	if ( radioButtons ) {
		for ( i = 0; i < radioButtons.length; i++ ) {
			radioButtons[i].checked = '';
		}
	}

	if ( options ) {
		for ( i = 0; i < options.length; i++ ) {
			options[i].selected = false;
		}
	}
}