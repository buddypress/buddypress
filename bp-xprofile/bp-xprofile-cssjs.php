<?php

function xprofile_add_signup_css() {
	if ( $_SERVER['SCRIPT_NAME'] == '/wp-signup.php' ) {
	?>
		<style type="text/css">
		
			table#extraFields td label, 
			div.radio span, 
			div.checkbox span {
				font-weight: bold;
				display: block;
				float: left;
				width: 115px;
			}
			
			table#extraFields td input {
				font-size: 24px;
				width: 280px;
			}
			
			table#extraFields td textarea {
				width: 280px;
				height: 120px;
			}
			
			table#extraFields td select {
				width: 280px;
			}
			
			table#extraFields td div.datefield select {
				width: auto;
			}
			
			table#extraFields td div.radio label,
			table#extraFields td div.checkbox label {
				display: inline;
				font-weight: normal;
				float: none;
			}
			
			table#extraFields td div.radio input,
			table#extraFields td div.checkbox input {
				width: auto;
			}
			
			span.desc {
				margin-left: 115px;
				font-weight: normal;
			}
			
			div.error {
				font-weight: bold;
				margin: 10px 0 10px 113px;
			}
			
		</style>
		<?php		
	}
}
add_action( 'wp_head', 'xprofile_add_signup_css' );


function xprofile_add_css() {
	global $userdata, $wpdb;
	
	?>
	<style type="text/css">

	tr.header td {
		border-bottom: 2px solid #eee;
		font-weight: bold;
	}
	
	tr.core td { color: #999; }
	
	thead tr th {
		font-size: 16px;
	}
		
		#profilePicture {
			margin: 0 0 0 -280px;
			float: left;
			width: 280px;
		}

		#currentPicture {
			padding: 15px;
			width: 280px;
			margin-left: 280px;
		}
		
		#currentPicture img, #otherPictures img {
			border: 1px solid #ccc;
			padding: 4px;
			background: #fff;
		}
			#otherPictures img:hover {
				background: #f0f0f0;
			}
		
		#currentPicture a, #otherPictures a { border: none; }
		
		#otherPictures {
			float: right;
			padding: 20px;
			margin-left: 300px;
			margin-top: -5px;
		}
		
		#otherPictures ul {
			list-style: none;
			margin: 0;
			padding: 0;
		}
			#otherPictures ul li {
				float: left;
				margin: 0 10px 10px 0;
			}
		
		#profilePicture form {
			border: 1px solid #ccc;
			width: 255px;
			margin-top: 20px;
			padding: 5px;
		}
			#profilePicture form h3 {
				background: #fafafa;
				margin: 0 0 15px 0;
				padding: 10px;
			}
		
		ul.forTab {
			list-style: none;
			padding: 0;
			margin: 0 0 0 1em;
		}
			ul.forTab li {
				margin: 0 0 1em 0;
			}
		
				ul.forTab li label {
					display: block;
					
				}
		
				ul.forTab li input {
					font-size: 1.4em;
				}
		
		p.success { background: green;}
		p.err { 
			border-top: 2px solid red;
			border-bottom: 2px solid red;
			color: red;
			padding: 5px 0;
			width: 40%;
		}
		
		span.desc, span.signup-description {
			display: block;
			font-size: 11px;
			color: #555;
		}

		select.multi-select{
		    width:90%;
        	height:10em !important;
    	}

    ul.multi-checkbox {
        margin: 0 5px 0 0px;
        padding: .5em .9em;
        height: 10em;
        overflow: auto;
        list-style: none;
        border: solid 1px #ccc;
        width:90%;           
    }

    ul.multi-checkbox li{
        padding: 0;
        margin: 0;
    }

	div.options-box {
		margin-left: 20px !important;
		margin-right: 10px !important;
		border-left: 4px solid #EAF3FA;
		padding-left: 15px;
	}

	th a {
		background: #fff;
		padding: 2px 5px;
		-moz-border-radius: 3px;
		-khtml-border-radius: 3px;
		-webkit-border-radius: 3px;
		border-radius: 3px;
		top: -2px;
	}

	</style>
	<?php
	//}
}

function xprofile_add_js() {
	if ( strpos( $_GET['page'], 'xprofile' ) !== false ) {
	?>
		<script type="text/javascript">
			var ajaxurl = '<?php echo get_option('siteurl') . "/wp-admin/admin-ajax.php"; ?>';
		
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
			
			function clear(container) {
				if(!document.getElementById(container)) return false;
				
				var container = document.getElementById(container);
				
				radioButtons = container.getElementsByTagName('INPUT');

				for(var i=0; i<radioButtons.length; i++) {
					radioButtons[i].checked = false;
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
						alert(ajaxUrl);
						alert("test");
						return false;
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
		</script>
		
<?php
	}
}

function xprofile_add_cropper_js() {
	if ( $_REQUEST['page'] == 'bp-xprofile.php' || $_SERVER['SCRIPT_NAME'] == '/wp-activate.php' ) {
?>
	<style type="text/css">
		#avatar_v2 { display: none; }
		.crop-img { float: left; margin: 0 20px 15px 0; }
		.submit { clear: left; }
	</style>
	
	<script type="text/javascript">
	function cropAndContinue() {
		jQuery('#avatar_v1').slideUp();
		jQuery('#avatar_v2').slideDown('normal', function(){
			v2Cropper();
		});
	}
	
	function v1Cropper() {
		v1Crop = new Cropper.ImgWithPreview( 
			'crop-v1-img',
			{ 
				ratioDim: { x: <?php echo round(XPROFILE_AVATAR_V1_W / XPROFILE_AVATAR_V1_H, 5); ?>, y: 1 },
				minWidth:   <?php echo XPROFILE_AVATAR_V1_W; ?>,
				minHeight:  <?php echo XPROFILE_AVATAR_V1_H; ?>,
				prevWidth:  <?php echo XPROFILE_AVATAR_V1_W; ?>,
				prevHeight: <?php echo XPROFILE_AVATAR_V1_H; ?>,
				onEndCrop: onEndCropv1,
				previewWrap: 'crop-preview-v1'
			}
		);
	}
	
	function onEndCropv1(coords, dimensions) {
		jQuery('#v1_x1').val(coords.x1);
		jQuery('#v1_y1').val(coords.y1);
		jQuery('#v1_x2').val(coords.x2);
		jQuery('#v1_y2').val(coords.y2);
		jQuery('#v1_w').val(dimensions.width);
		jQuery('#v1_h').val(dimensions.height);
	}

	<?php if (XPROFILE_AVATAR_V2_W !== false && XPROFILE_AVATAR_V2_H !== false) { ?>
	function v2Cropper() {
		v1Crop = new Cropper.ImgWithPreview( 
			'crop-v2-img',
			{ 
				ratioDim: { x: <?php echo round(XPROFILE_AVATAR_V2_W / XPROFILE_AVATAR_V2_H, 5); ?>, y: 1 },
				minWidth:   <?php echo XPROFILE_AVATAR_V2_W; ?>,
				minHeight:  <?php echo XPROFILE_AVATAR_V2_H; ?>,
				prevWidth:  <?php echo XPROFILE_AVATAR_V2_W; ?>,
				prevHeight: <?php echo XPROFILE_AVATAR_V2_H; ?>,
				onEndCrop: onEndCropv2,
				previewWrap: 'crop-preview-v2'
			}
		);
	}
	<?php } ?>
	
	function onEndCropv2(coords, dimensions) {
		jQuery('#v2_x1').val(coords.x1);
		jQuery('#v2_y1').val(coords.y1);
		jQuery('#v2_x2').val(coords.x2);
		jQuery('#v2_y2').val(coords.y2);
		jQuery('#v2_w').val(dimensions.width);
		jQuery('#v2_h').val(dimensions.height);
	}
	</script>
<?php
	}
}

?>
