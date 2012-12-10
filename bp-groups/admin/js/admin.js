(function($) {
	function add_member_to_list( e, ui ) {
		var remove_id = 'bp-groups-remove-new-member-' + ui.item.value;
		$('#bp-groups-new-members-list').append('<li><a href="#" class="bp-groups-remove-new-member" id="' + remove_id + '">x</a> ' + ui.item.label + '</li>');
		$('#' + remove_id).bind('click', function(e) { remove_member_from_list(e); return false; });

		$('#bp-groups-new-members-list').after('<input name="new_members[]" type="hidden" value="' + ui.item.value + '" />');
	}

	function remove_member_from_list( e ) {
		$(e.target).closest('li').remove();
	}

	var id = 'undefined' !== typeof group_members ? '&group_members=' + group_members : '';
	$(document).ready( function() {
		/* Initialize autocomplete */
		$( '.bp-suggest-user' ).autocomplete({
			source:    ajaxurl + '?action=bp_group_admin_member_autocomplete' + id,
			delay:     500,
			minLength: 2,
			position:  ( 'undefined' !== typeof isRtl && isRtl ) ? { my: 'right top', at: 'right bottom', offset: '0, -1' } : { offset: '0, -1' },
			open:      function() { $(this).addClass('open'); },
			close:     function() { $(this).removeClass('open'); $(this).val(''); },
			select:    function( event, ui ) { add_member_to_list( event, ui ); }
		});
		
		/* Replace noscript placeholder */
		$( '#bp-groups-new-members' ).attr( 'placeholder', BP_Group_Admin.add_member_placeholder );

	});
})(jQuery);
