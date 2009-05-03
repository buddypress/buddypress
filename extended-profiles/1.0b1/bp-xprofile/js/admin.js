function add_option(forWhat) {
	var holder = document.getElementById(forWhat + "_more");
	var theId = document.getElementById(forWhat + '_option_number').value;

	var newDiv = document.createElement('p');
	newDiv.setAttribute('id', forWhat + '_div' + theId);

	var newOption = document.createElement('input');
	newOption.setAttribute('type', 'text');
	newOption.setAttribute('name', forWhat + '_option[' + theId + ']');
	newOption.setAttribute('id', forWhat + '_option' + theId);

	var label = document.createElement('label');
	label.setAttribute('for', forWhat + '_option' + theId);
	
	var txt = document.createTextNode("Option " + theId + ": ");
	label.appendChild(txt);
	
	var isDefault = document.createElement('input');
	
	if(forWhat == 'checkbox' || forWhat == 'multiselectbox') {
		isDefault.setAttribute('type', 'checkbox');
		isDefault.setAttribute('name', 'isDefault_' + forWhat + '_option[' + theId + ']');
	} else {
		isDefault.setAttribute('type', 'radio');
		isDefault.setAttribute('name', 'isDefault_' + forWhat + '_option');					
	}
	
	isDefault.setAttribute('value', theId);

	var label1 = document.createElement('label');
	var txt1 = document.createTextNode(" Default Value ");
	
	label1.appendChild(txt1);
	label1.setAttribute('for', 'isDefault_' + forWhat + '_option[]');
	toDelete = document.createElement('a');
	
	toDeleteText = document.createTextNode('[x]');
	toDelete.setAttribute('href',"javascript:hide('" + forWhat + '_div' + theId + "')");
	
	toDelete.setAttribute('class','delete');

	toDelete.appendChild(toDeleteText);

	newDiv.appendChild(label);
	newDiv.appendChild(newOption);
	newDiv.appendChild(document.createTextNode(" "));
	newDiv.appendChild(isDefault);
	newDiv.appendChild(label1);	
	newDiv.appendChild(toDelete);	
	holder.appendChild(newDiv);
	
	
	theId++
	document.getElementById(forWhat + "_option_number").value = theId;
}

function show_options(forWhat) {
	document.getElementById("radio").style.display = "none";
	document.getElementById("selectbox").style.display = "none";
	document.getElementById("multiselectbox").style.display = "none";
	document.getElementById("checkbox").style.display = "none";
	
	if(forWhat == "radio") {
		document.getElementById("radio").style.display = "";
	}
	
	if(forWhat == "selectbox") {
		document.getElementById("selectbox").style.display = "";						
	}
	
	if(forWhat == "multiselectbox") {
		document.getElementById("multiselectbox").style.display = "";						
	}
	
	if(forWhat == "checkbox") {
		document.getElementById("checkbox").style.display = "";						
	}
}

function reorderFields(table, row, field_ids) {
	jQuery.post( ajaxurl, {
		action: 'xprofile_reorder_fields',
		'cookie': encodeURIComponent(document.cookie),
		'_wpnonce': jQuery("input#_wpnonce").val(),
		'group': table.id.split('_')[1],
		'row': row,
		'field_ids': field_ids
		},
		function(response) {
			
		}, 
		1250
	);
}

function hide(id) {
	if ( !document.getElementById(id) ) return false;
	
	document.getElementById(id).style.display = "none";
	document.getElementById(id).value = '';
}

// Set up deleting options ajax
jQuery(document).ready( function() {
	var links = jQuery("a.ajax-option-delete");
	
	jQuery.each(links,
		function(link, val) {
			link.click(
				function() {
				}
			);
		}
	);
	
	jQuery("a.ajax-option-delete").click( 
		function() {
			var theId = this.id.split('-');
			theId = theId[1];
			
			jQuery.post( ajaxurl, {
				action: 'xprofile_delete_option',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#_wpnonce").val(),
				
				'option_id': theId
			},
			function(response)
			{
				alert(response);
			});
		
			
		}
	);				
});