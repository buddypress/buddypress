<?php

function add_third_level()
{
	global $thirdlevel;
	if($thirdlevel) { ?>
		<ul id="thirdlevel">
	<?php
		foreach($thirdlevel as $key => $value) {
			if('/wp-admin/' . $thirdlevel[$key][2] == $_SERVER['SCRIPT_NAME']) {
				$current = ' class="current"';
			}
			else {
				$current = '';
			}
			
			echo '<li><a' . $current . ' href="' . $thirdlevel[$key][2] . '">' . $thirdlevel[$key][0] . '</a></li>';
		}
	?>
		</ul>
		<div class="clear" style="margin-bottom: 20px"></div>
	<?php
	}
}
add_action('admin_notices', 'add_third_level');


function add_thirdlevel_css()
{
	?>
	<style type="text/css">
		ul#thirdlevel {
			margin: 0 0 15px 0;
			padding: 0 0 0 17px;
			list-style: none;
			border-top: 1px solid #c6d9e9;
		}
			ul#thirdlevel li {
				float: left;
				margin: 2px 12px 0 0;
				font-size: 12px;
			}
				ul#thirdlevel li a { 
					text-decoration: none; 
					padding: 3px 6px;
				}
				ul#thirdlevel li a.current {
					border: 1px solid #c6d9e9;
					border-top: 1px solid #fff;
					color: #D54E21;
					-moz-border-radius-bottomleft: 3px;
					-khtml-border-bottom-left-radius: 3px;
					-webkit-border-bottom-left-radius: 3px;
					border-bottom-left-radius: 3px;
					-moz-border-radius-bottomright: 3px;
					-khtml-border-bottom-right-radius: 3px;
					-webkit-border-bottom-right-radius: 3px;
					border-top-bottom-radius: 3px;
					z-index: 999;
				}
			
			#submenu li a #awaiting-mod {
				background-image: url(<?php bloginfo('home'); ?>/wp-admin/images/comment-stalk-classic.gif);
			}

			#submenu li a #awaiting-mod span {
				background-color: #d54e21;
				color: #fff;
			}
			
			#submenu li a #awaiting-mod {
				position: absolute;
				margin-left: -0.4em;
				margin-top: 0.2em;
				font-size: 0.7em;
				background-repeat: no-repeat;
				background-position: 0 bottom;
				height: 0.9em;
				width: 1em;
			}

			#submenu li a .count-0 {
				display: none;
			}
			
			#submenu li a:hover #awaiting-mod {
				background-position: -80px bottom;
			}

			#submenu li a #awaiting-mod span {
				top: -0.9em;
				right: 0;
				position: absolute;
				display: block;
				height: 1.3em;
				line-height: 1.3em;
				padding: 0.1em 0.6em;
				-moz-border-radius: 3px;
				-khtml-border-radius: 3px;
				-webkit-border-radius: 3px;
				border-radius: 3px;
			}
			
			#submenu li a #awaiting-mod span, #rightnow .reallynow {
				background-color: #d54e21;
				color: #fff;
			}

			#submenu li a:hover #awaiting-mod span {
				background-color: #264761;
			}

	</style>
	<?php
}
add_action('admin_head', 'add_thirdlevel_css');

?>