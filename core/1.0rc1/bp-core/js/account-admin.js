function check_pass_strength ( ) {

	var pass = jQuery('#pass1').val();
	var user = jQuery('#user_login').val();

	// get the result as an object, i'm tired of typing it
	var res = jQuery('#pass-strength-result');

	var strength = passwordStrength(pass, user);

	jQuery(res).removeClass('short bad good strong');

	if ( strength == pwsL10n.bad ) {
		jQuery(res).addClass('bad');
		jQuery(res).html( pwsL10n.bad );
	}
	else if ( strength == pwsL10n.good ) {
		jQuery(res).addClass('good');
		jQuery(res).html( pwsL10n.good );
	}
	else if ( strength == pwsL10n.strong ) {
		jQuery(res).addClass('strong');
		jQuery(res).html( pwsL10n.strong );
	}
	else {
		// this catches 'Too short' and the off chance anything else comes along
		jQuery(res).addClass('short');
		jQuery(res).html( pwsL10n.short );
	}
}

function update_nickname ( ) {
	
	var nickname = jQuery('#nickname').val();
	var display_nickname = jQuery('#display_nickname').val();
	
	if ( nickname == '' ) {
		jQuery('#display_nickname').remove();
	}
	jQuery('#display_nickname').val(nickname).html(nickname);
	
}

jQuery(function($) { 
	$('#pass1').keyup( check_pass_strength ) 
	$('.color-palette').click(function(){$(this).siblings('input[name=admin_color]').attr('checked', 'checked')});
} );

jQuery(document).ready( function() {
	jQuery('#pass1,#pass2').attr('autocomplete','off');
	jQuery('#nickname').blur(update_nickname);
   });

function clear(container) {
	if(!document.getElementById(container)) return false;

	var container = document.getElementById(container);

	radioButtons = container.getElementsByTagName('INPUT');

	for(var i=0; i<radioButtons.length; i++) {
		radioButtons[i].checked = false;
	}	
}