jQuery(document).ready( function() {
	jQuery("div.friendship-button a").livequery('click',
		function(e) {
			var fid = jQuery(this).attr('id');
			fid = fid.split('-');
			fid = fid[1];
			
			var thelink = jQuery(this);

			jQuery.post( ajaxurl, {
				action: 'addremove_friend',
				'cookie': encodeURIComponent(document.cookie),
				'fid': fid
			},
			function(response)
			{
				response = response.substr(0, response.length-1);
			
				var action = thelink.attr('rel');
				var parentdiv = thelink.parent();
				
				if ( action == 'add' ) {
					jQuery(parentdiv).fadeOut(200, 
						function() {
							parentdiv.removeClass('add_friend');
							parentdiv.addClass('pending');
							parentdiv.fadeIn(200).html(response);
						}
					);

				} else if ( action == 'remove' ) {
					jQuery(parentdiv).fadeOut(200, 
						function() {
							parentdiv.removeClass('remove_friend');
							parentdiv.addClass('add');
							parentdiv.fadeIn(200).html(response);
						}
					);				
				}
			});
			return false;
		}
	);
});

function clear(container) {
	if(!document.getElementById(container)) return false;
	
	var container = document.getElementById(container);
	
	radioButtons = container.getElementsByTagName('INPUT');

	for(var i=0; i<radioButtons.length; i++) {
		radioButtons[i].checked = false;
	}	
}

/* For admin-bar */
sfHover = function() {
	var sfEls = document.getElementById("nav").getElementsByTagName("LI");
	for (var i=0; i<sfEls.length; i++) {
		sfEls[i].onmouseover=function() {
			this.className+=" sfhover";
		}
		sfEls[i].onmouseout=function() {
			this.className=this.className.replace(new RegExp(" sfhover\\b"), "");
		}
	}
}
if (window.attachEvent) window.attachEvent("onload", sfHover);
