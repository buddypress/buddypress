jQuery(document).ready( function() {
	jQuery("div#groups-list-options a").livequery('click',
		function() { 
			jQuery('#ajax-loader-groups').toggle();

			jQuery("div#groups-list-options a").removeClass("selected");
			jQuery(this).addClass('selected');

			jQuery.post( ajaxurl, {
				action: 'widget_groups_list',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#_wpnonce-groups").val(),
				'max_groups': jQuery("input#groups_widget_max").val(),
				'filter': jQuery(this).attr('id')
			},
			function(response)
			{	
				groups_wiget_response(response);
			});
		
			return false;
		}
	);
});

function groups_wiget_response(response) {
	response = response.substr(0, response.length-1);
	response = response.split('[[SPLIT]]');

	if ( response[0] != "-1" ) {
		jQuery("ul#groups-list").fadeOut(200, 
			function() {
				jQuery('#ajax-loader-groups').toggle();
				jQuery("ul#groups-list").html(response[1]);
				jQuery("ul#groups-list").fadeIn(200);
			}
		);

	} else {					
		jQuery("ul#groups-list").fadeOut(200, 
			function() {
				jQuery('#ajax-loader-groups').toggle();
				var message = '<p>' + response[1] + '</p>';
				jQuery("ul#groups-list").html(message);
				jQuery("ul#groups-list").fadeIn(200);
			}
		);
	}
}