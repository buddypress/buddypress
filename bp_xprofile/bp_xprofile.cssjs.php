<?php

function xprofile_add_signup_css()
{
	if($_SERVER['SCRIPT_NAME'] == '/wp-signup.php')
	{
		?>
		<style type="text/css">
			
			table#extraFields td label, div.radio span {
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
				width: auto;
			}
			
			table#extraFields td div.radio label {
				display: inline;
				font-weight: normal;
				float: none;
			}
			
			table#extraFields td div.radio input {
				width: auto;
			}
			
			span.desc {
				margin-left: 115px;
			}
			
			div.error {
				font-weight: bold;
				margin: 10px 0 10px 113px;
			}
			
		</style>
		<?php		
	}
}
add_action('wp_head', 'xprofile_add_signup_css');


function xprofile_add_css()
{
	if(strpos($_GET['page'], 'xprofile') !== false)
	{
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
		
		span.desc {
			display: block;
			font-size: 11px;
			color: #555;
		}
		
		
	</style>
	<?php
	}
}
do_action('signup_header', 'xprofile_add_css');

function xprofile_add_js()
{
	if(strpos($_GET['page'], 'xprofile') !== false)
	{
		?>
		<script type="text/javascript">
			function add_option(forWhat) {
				var holder = document.getElementById(forWhat + "_more");
				var theId = document.getElementById(forWhat + '_option_number').value;
			
				var newDiv = document.createElement('p');
				newDiv.setAttribute('id', forWhat + '_div' + theId);
			
				var newOption = document.createElement('input');
				newOption.setAttribute('type', 'text');
				newOption.setAttribute('name', forWhat + '_option[]');
				newOption.setAttribute('id', forWhat + '_option' + theId);								
			
				var label = document.createElement('label');
				label.setAttribute('for', forWhat + '_option' + theId);
				
				var txt = document.createTextNode("Option " + theId + ": ");
				label.appendChild(txt);
			
				newDiv.appendChild(label);
				 
				newDiv.appendChild(newOption);
				holder.appendChild(newDiv);
				
				theId++
				document.getElementById(forWhat + "_option_number").value = theId;
			}
			
			function show_options(forWhat) {
				document.getElementById("radio").style.display = "none";
				document.getElementById("select").style.display = "none";
				
				if(forWhat == "radio") {
					document.getElementById("radio").style.display = "";
				}
				
				if(forWhat == "selectbox") {
					document.getElementById("select").style.display = "";						
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
			
		</script>
		
		<?php
	}
}

?>