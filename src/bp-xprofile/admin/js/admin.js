/* exported add_option, show_options, hide, fixHelper */
/* jshint scripturl: true */
/* global XProfileAdmin, ajaxurl */

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
		txt1         = document.createTextNode( XProfileAdmin.text.defaultValue ),
		toDeleteText = document.createTextNode( XProfileAdmin.text.deleteLabel ),
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

	// Re-initialize the sortable ui.
	enableSortableFieldOptions( forWhat );

	// Set focus on newly created element.
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
	var do_autolink;

	for ( var i = 0; i < XProfileAdmin.do_settings_section_field_types.length; i++ ) {
		document.getElementById( XProfileAdmin.do_settings_section_field_types[i] ).style.display = 'none';
	}

	if ( XProfileAdmin.do_settings_section_field_types.indexOf( forWhat ) >= 0 ) {
		document.getElementById( forWhat ).style.display = '';
		do_autolink = 'on';
	} else {
		jQuery( '#do-autolink' ).val( '' );
		do_autolink = '';
	}

	// Only overwrite the do_autolink setting if no setting is saved in the database.
	if ( '' === XProfileAdmin.do_autolink ) {
		jQuery( '#do-autolink' ).val( do_autolink );
	}

	// Show/hides metaboxes according to selected field type supports.
	jQuery( '#field-type-visibiliy-metabox, #field-type-required-metabox, #field-type-autolink-metabox, #field-type-member-types, #field-signup-position-metabox' ).show();
	if ( -1 !== XProfileAdmin.hide_required_metabox.indexOf( forWhat ) ) {
		jQuery( '#field-type-required-metabox' ).hide();
	}

	if ( -1 !== XProfileAdmin.hide_signup_position_metabox.indexOf( forWhat ) ) {
		jQuery( '#field-signup-position-metabox' ).hide();
	}

	if ( -1 !== XProfileAdmin.hide_allow_custom_visibility_metabox.indexOf( forWhat ) ) {
		jQuery( '#field-type-visibiliy-metabox' ).hide();
	}

	if ( -1 !== XProfileAdmin.hide_do_autolink_metabox.indexOf( forWhat ) ) {
		jQuery( '#field-type-autolink-metabox' ).hide();
	}

	if ( -1 !== XProfileAdmin.hide_member_types_metabox.indexOf( forWhat ) ) {
		jQuery( '#field-type-member-types' ).hide();
	}

	jQuery( document ).trigger( 'bp-xprofile-show-options', forWhat );
}

function hide( id ) {
	if ( !document.getElementById( id ) ) {
		return false;
	}

	document.getElementById( id ).style.display = 'none';
	// The field id is [fieldtype]option[iterator] and not [fieldtype]div[iterator].
	var field_id = id.replace( 'div', 'option' );
	document.getElementById( field_id ).value = '';
}

/**
 * @summary Toggles "no member type" notice.
 *
 * @since 2.4.0
 */
function toggle_no_member_type_notice() {
	var $member_type_checkboxes = jQuery( 'input.member-type-selector' );

	// No checkboxes? Nothing to do.
	if ( ! $member_type_checkboxes.length ) {
		return;
	}

	var has_checked = false;
	$member_type_checkboxes.each( function() {
		if ( jQuery( this ).is( ':checked' ) ) {
			has_checked = true;
			return false;
		}
	} );

	if ( has_checked ) {
		jQuery( 'p.member-type-none-notice' ).addClass( 'hide' );
	} else {
		jQuery( 'p.member-type-none-notice' ).removeClass( 'hide' );
	}
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

function titleHint( id ) {
	id = id || 'title';

	var title = jQuery('#' + id), titleprompt = jQuery('#' + id + '-prompt-text');

	if ( '' === title.val() ) {
		titleprompt.removeClass('screen-reader-text');
	} else {
		titleprompt.addClass('screen-reader-text');
	}

	titleprompt.on( 'click', function(){
		jQuery(this).addClass('screen-reader-text');
		title.trigger( 'focus' );
	});

	title.on( 'blur', function(){
		if ( '' === this.value ) {
			titleprompt.removeClass('screen-reader-text');
		}
	}).on( 'focus', function(){
		titleprompt.addClass('screen-reader-text');
	}).on( 'keydown', function(e){
		titleprompt.addClass('screen-reader-text');
		jQuery(this).off( e );
	});
}

jQuery( function() {
	var isMovingToSignups = false;

	// Set focus in Field Title, if we're on the right page.
	jQuery( '#bp-xprofile-add-field #title' ).trigger( 'focus' );

	// Set up the notice that shows when no member types are selected for a field.
	toggle_no_member_type_notice();
	jQuery( 'input.member-type-selector' ).on( 'change', function() {
		toggle_no_member_type_notice();
	} );

	// Set up deleting options ajax.
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

	// Set up the sort order change actions.
	jQuery( '[id^="sort_order_"]' ).on( 'change', function() {
		if ( jQuery( this ).val() !== 'custom' ) {
			destroySortableFieldOptions();
		} else {
			enableSortableFieldOptions( jQuery('#fieldtype :selected').val() );
		}
	} );

	// Show object if JS is enabled.
	jQuery( 'ul#field-group-tabs' ).show();

	// Allow reordering of field group tabs.
	jQuery( 'ul#field-group-tabs' ).sortable( {
		cursor: 'move',
		axis: 'x',
		opacity: 1,
		items: 'li:not(.not-sortable)',
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

	// Allow reordering of fields within groups.
	jQuery( 'fieldset.field-group' ).sortable({
		cursor: 'move',
		opacity: 0.7,
		items: 'fieldset',
		tolerance: 'pointer',

		update: function() {
			if ( isMovingToSignups ) {
				return false;
			}

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

	// Disallow text selection.
	.disableSelection();

	// Allow reordering of field options.
	enableSortableFieldOptions( jQuery('#fieldtype :selected').val() );

	// Handle title placeholder text the WordPress way.
	titleHint( 'title' );

	// On Date fields, selecting a date_format radio button should change the Custom value.
	var $date_format = jQuery( 'input[name="date_format"]' );
	var $date_format_custom_value = jQuery( '#date-format-custom-value' );
	var $date_format_sample = jQuery( '#date-format-custom-sample' );
	$date_format.on( 'click', function( e ) {
		switch ( e.target.value ) {
			case 'elapsed' :
				$date_format_custom_value.val( '' );
				$date_format_sample.html( '' );
			break;

			case 'custom' :
			break;

			default :
				$date_format_custom_value.val( e.target.value );
				$date_format_sample.html( jQuery( e.target ).siblings( '.date-format-label' ).html() );
			break;
		}
	} );

	// Clicking into the custom date format field should select the Custom radio button.
	var $date_format_custom = jQuery( '#date-format-custom' );
	$date_format_custom_value.on( 'focus', function() {
		$date_format_custom.prop( 'checked', 'checked' );
	} );

	// Validate custom date field.
	var $date_format_spinner = jQuery( '#date-format-custom-spinner' );
	$date_format_custom_value.on( 'change', function( e ) {
		$date_format_spinner.addClass( 'is-active' );
		jQuery.post( ajaxurl, {
			action: 'date_format',
			date: e.target.value
		},
		function( response ) {
			$date_format_spinner.removeClass( 'is-active' );
			$date_format_sample.html( response );
		} );
	} );

	// tabs init with a custom tab template and an "add" callback filling in the content.
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

			// When field is dropped on tab.
			drop: function( ev, ui ) {
				var $item = jQuery(this), // The tab
					$list = jQuery( $item.find( 'a' ).attr( 'href' ) ).find( '.connectedSortable' ), // The tab body
					dropInGroup = function( fieldId ) {
						var fieldOrder, postData = {
							action: 'xprofile_reorder_fields',
							'cookie': encodeURIComponent(document.cookie),
							'_wpnonce_reorder_fields': jQuery( 'input#_wpnonce_reorder_fields' ).val()
						};

						// Select new tab as current.
						$tabs.tabs( 'option', 'active', $tab_items.index( $item ) );

						// Refresh $list variable.
						$list = jQuery( $item.find( 'a' ).attr( 'href' ) ).find( '.connectedSortable' );
						jQuery($list).find( 'p.nofields' ).hide( 'slow' );

						jQuery.extend( postData, {
							'field_group_id': jQuery( $list ).attr( 'id' ),
							'group_tab': jQuery( $item ).prop( 'id' )
						} );

						// Set serialized data
						fieldOrder = jQuery( $list ).sortable( 'serialize' );

						if ( fieldId ) {
							var serializedField = fieldId.replace( 'draggable_field_', 'draggable_signup_field[]=' );
							if ( fieldOrder ) {
								fieldOrder += '&' + serializedField;
							} else {
								fieldOrder = serializedField;
							}

							jQuery.extend( postData, {
								'new_signup_field_id': serializedField
							} );
						} else {
							// Show new placement.
							jQuery( this ).appendTo( $list ).show( 'slow' ).animate( { opacity: '1' }, 500 );

							// Refresh $list variable.
							$list = jQuery( $item.find( 'a' ).attr( 'href' ) ).find( '.connectedSortable' );

							// Reset serialized data.
							fieldOrder = jQuery( $list ).sortable( 'serialize' );

							jQuery.extend( postData, {
								'field_group_id': jQuery( $list ).attr( 'id' )
							} );
						}

						jQuery.extend( postData, {
							'field_order': fieldOrder
						} );

						// Ajax update field locations and orders.
						jQuery.post( ajaxurl, postData, function( response ) {
							if ( response.data && response.data.signup_field ) {
								jQuery( $list ).append( response.data.signup_field );

								if ( response.data.field_id ) {
									jQuery( '#draggable_field_' + response.data.field_id + ' legend' ).append(
										jQuery( '<span></span>' ).addClass( 'bp-signup-field-label' ).html( XProfileAdmin.signup_info )
									);
								}
							}
						}, 'json' ).always( function() {
							isMovingToSignups = false;
						} );
					};

				// Remove helper class.
				jQuery($item).removeClass( 'drop-candidate' );

				if ( 'signup-group' === jQuery( $item ).prop( 'id' ) ) {
					// Simply add the field to signup ones.
					dropInGroup( ui.draggable.prop( 'id' ) );

				} else if ( ! ui.draggable.prop( 'id' ).match( /draggable_signup_field_([0-9]+)/ ) ) {
					// Hide field, change selected tab, and show new placement.
					ui.draggable.hide( 'slow', dropInGroup );
				}
			},
			over: function() {
				isMovingToSignups = true;
				jQuery(this).addClass( 'drop-candidate' );
			},
			out: function() {
				jQuery(this).removeClass( 'drop-candidate' );
				isMovingToSignups = false;
			}
		});
	}

	jQuery( '#signup-fields' ).on( 'click', '.removal', function( e ) {
		e.preventDefault();

		var fieldId = jQuery( e.target ).attr( 'href' ).replace( '#remove_field-', '' ),
		    container = jQuery( e.target ).closest( '#draggable_signup_field_' + fieldId );

		if ( ! fieldId ) {
			return false;
		}

		// Ajax update field locations and orders.
		jQuery.post( ajaxurl, {
			action: 'xprofile_remove_signup_field',
			'cookie': encodeURIComponent(document.cookie),
			'_wpnonce_reorder_fields': jQuery( 'input#_wpnonce_reorder_fields' ).val(),
			'signup_field_id': fieldId
		}, function( response ) {
			if ( response.success ) {
				jQuery( container ).remove();
				jQuery( '#draggable_field_' + fieldId + ' .bp-signup-field-label' ).remove();
			}
		}, 'json' );
	} );
});
