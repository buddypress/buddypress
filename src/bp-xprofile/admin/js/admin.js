/* exported add_option, show_options, hide, fixHelper */
/* jshint scripturl: true */
/* global XProfileAdmin */

/**
 * Add option for the forWhat type.
 *
 * @param {string} forWhat Value of the field to show options for
 */
function add_option(forWhat) {
	var holder       = document.getElementById(forWhat + '_more'),
		theId        = document.getElementById(forWhat + '_option_number').value,
		newDiv       = document.createElement( 'div' ),
		grabber      = document.createElement( 'span' ),
		newOption    = document.createElement( 'input' ),
		label        = document.createElement( 'label' ),
		isDefault    = document.createElement( 'input' ),
		txt1         = document.createTextNode( 'Default Value' ),
		toDeleteText = document.createTextNode( 'Delete' ),
		toDeleteWrap = document.createElement( 'div' ),
		toDelete     = document.createElement( 'a' );

	newDiv.setAttribute('id', forWhat + '_div' + theId);
	newDiv.setAttribute('class', 'bp-option sortable');

	grabber.setAttribute( 'class', 'bp-option-icon grabber');

	newOption.setAttribute( 'type', 'text' );
	newOption.setAttribute( 'name', forWhat + '_option[' + theId + ']' );
	newOption.setAttribute( 'id', forWhat + '_option' + theId );

	if ( forWhat === 'checkbox' || forWhat === 'multiselectbox' ) {
		isDefault.setAttribute( 'type', 'checkbox' );
		isDefault.setAttribute( 'name', 'isDefault_' + forWhat + '_option[' + theId + ']' );
	} else {
		isDefault.setAttribute( 'type', 'radio' );
		isDefault.setAttribute( 'name', 'isDefault_' + forWhat + '_option' );
	}

	isDefault.setAttribute( 'value', theId );

	toDelete.setAttribute( 'href', 'javascript:hide("' + forWhat + '_div' + theId + '")' );
	toDelete.setAttribute( 'class', 'delete' );
	toDelete.appendChild( toDeleteText );

	toDeleteWrap.setAttribute( 'class', 'delete-button' );
	toDeleteWrap.appendChild( toDelete );

	label.appendChild( document.createTextNode( ' ' ) );
	label.appendChild( isDefault );
	label.appendChild( document.createTextNode( ' ' ) );
	label.appendChild( txt1 );
	label.appendChild( document.createTextNode( ' ' ) );

	newDiv.appendChild( grabber );
	newDiv.appendChild( document.createTextNode( ' ' ) );
	newDiv.appendChild( newOption );
	newDiv.appendChild( label );
	newDiv.appendChild( toDeleteWrap );
	holder.appendChild( newDiv );

	// re-initialize the sorable ui
	enableSortableFieldOptions( forWhat );

	// set focus on newly created element
	document.getElementById(forWhat + '_option' + theId).focus();

	theId++;

	document.getElementById(forWhat + '_option_number').value = theId;
}

/**
 * Hide all "options" sections, and show the options section for the forWhat type.
 *
 * @param {string} forWhat Value of the field to show options for
 */
function show_options( forWhat ) {
	for ( var i = 0; i < XProfileAdmin.supports_options_field_types.length; i++ ) {
		document.getElementById( XProfileAdmin.supports_options_field_types[i] ).style.display = 'none';
	}

	if ( XProfileAdmin.supports_options_field_types.indexOf( forWhat ) >= 0 ) {
		document.getElementById( forWhat ).style.display = '';
	}
}

function hide( id ) {
	if ( !document.getElementById( id ) ) {
		return false;
	}

	document.getElementById( id ).style.display = 'none';
	// the field id is [fieldtype]option[iterator] and not [fieldtype]div[iterator]
	var field_id = id.replace( 'div', 'option' );
	document.getElementById( field_id ).value = '';
}

var fixHelper = function(e, ui) {
	ui.children().each(function() {
		jQuery(this).width( jQuery(this).width() );
	});
	return ui;
};

function enableSortableFieldOptions() {
	jQuery( '.bp-options-box' ).sortable( {
		cursor: 'move',
		items: 'div.sortable',
		tolerance: 'intersect',
		axis: 'y'
	});

	jQuery( '.sortable, .sortable span' ).css( 'cursor', 'move' );
}

function destroySortableFieldOptions() {
	jQuery( '.bp-options-box' ).sortable( 'destroy' );
	jQuery( '.sortable, .sortable span' ).css( 'cursor', 'default' );
}

jQuery( document ).ready( function() {

	// Set focus in Field Title, if we're on the right page
	jQuery( '#bp-xprofile-add-field #title' ).focus();
	
	// Set up deleting options ajax
	jQuery( 'a.ajax-option-delete' ).on( 'click', function() {
		var theId = this.id.split( '-' );
		theId = theId[1];

		jQuery.post( ajaxurl, {
			action: 'xprofile_delete_option',
			'cookie': encodeURIComponent( document.cookie ),
			'_wpnonce': jQuery('input#_wpnonce').val(),
			'option_id': theId
		},
		function() {} );
	} );

	// Set up the sort order change actions
	jQuery( '[id^="sort_order_"]' ).change(function() {
		if ( jQuery( this ).val() !== 'custom' ) {
			destroySortableFieldOptions();
		} else {
			enableSortableFieldOptions( jQuery('#fieldtype :selected').val() );
		}
	});

	// Show object if JS is enabled
	jQuery( 'ul#field-group-tabs' ).show();

	// Allow reordering of field group tabs
	jQuery( 'ul#field-group-tabs' ).sortable( {
		cursor: 'move',
		axis: 'x',
		opacity: 1,
		items: 'li',
		tolerance: 'intersect',

		update: function() {
			jQuery.post( ajaxurl, {
				action: 'xprofile_reorder_groups',
				'cookie': encodeURIComponent( document.cookie ),
				'_wpnonce_reorder_groups': jQuery( 'input#_wpnonce_reorder_groups' ).val(),
				'group_order': jQuery( this ).sortable( 'serialize' )
			},
			function() {} );
		}
	}).disableSelection();

	// Allow reordering of fields within groups
	jQuery( 'fieldset.field-group' ).sortable({
		cursor: 'move',
		opacity: 1,
		items: 'fieldset',
		tolerance: 'ponter',

		update: function() {
			jQuery.post( ajaxurl, {
				action: 'xprofile_reorder_fields',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce_reorder_fields': jQuery( 'input#_wpnonce_reorder_fields' ).val(),
				'field_order': jQuery(this).sortable( 'serialize' ),
				'field_group_id': jQuery(this).attr( 'id' )
			},
			function() {} );
		}
	})

	// Disallow text selection
	.disableSelection()

	// Change cursor to move if JS is enabled
	.css( 'cursor', 'move' );

	// Allow reordering of field options
	enableSortableFieldOptions( jQuery('#fieldtype :selected').val() );

	// tabs init with a custom tab template and an "add" callback filling in the content
	var $tab_items,
		$tabs = jQuery( '#tabs' ).tabs();

	set_tab_items( $tabs );

	function set_tab_items( $tabs ) {
		$tab_items = jQuery( 'ul:first li', $tabs ).droppable({
			accept: '.connectedSortable fieldset',
			hoverClass: 'ui-state-hover',
			activeClass: 'ui-state-acceptable',
			touch: 'pointer',
			tolerance: 'pointer',

			// When field is dropped on tab
			drop: function( ev, ui ) {
				var $item = jQuery(this), // The tab
					$list = jQuery( $item.find( 'a' ).attr( 'href' ) ).find( '.connectedSortable' ); // The tab body

				// Remove helper class
				jQuery($item).removeClass( 'drop-candidate' );

				// Hide field, change selected tab, and show new placement
				ui.draggable.hide( 'slow', function() {

					// Select new tab as current
					$tabs.tabs( 'option', 'active', $tab_items.index( $item ) );

					// Show new placement
					jQuery(this).appendTo($list).show( 'slow' ).animate( {opacity: '1'}, 500 );

					// Refresh $list variable
					$list = jQuery( $item.find( 'a' ).attr( 'href' ) ).find( '.connectedSortable' );
					jQuery($list).find( 'p.nofields' ).hide( 'slow' );

					// Ajax update field locations and orders
					jQuery.post( ajaxurl, {
						action: 'xprofile_reorder_fields',
						'cookie': encodeURIComponent(document.cookie),
						'_wpnonce_reorder_fields': jQuery( 'input#_wpnonce_reorder_fields' ).val(),
						'field_order': jQuery( $list ).sortable( 'serialize' ),
						'field_group_id': jQuery( $list ).attr( 'id' )
					},
					function() {} );
				});
			},
			over: function() {
				jQuery(this).addClass( 'drop-candidate' );
			},
			out: function() {
				jQuery(this).removeClass( 'drop-candidate' );
			}
		});
	}
});
