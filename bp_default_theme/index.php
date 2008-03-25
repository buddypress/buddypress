<?php get_header(); ?>

	<div id="content">

		<div id="profileName">
			<h2><a href="<?php echo get_option('home'); ?>/"><?php bloginfo('name'); ?></a></h2>
			<div class="description"><?php bloginfo('description'); ?></div>
		</div>
		
		<?php $profileGroups = xprofile_get_data($authordata->ID); ?>

		<?php for($i=0; $i<count($profileGroups); $i++) { ?>
			
			<div class="profileGroup">
				
				<h3><?php echo $profileGroups[$i]->name; ?></h3>
				
				<table>
				<?php for($j=0; $j<count($profileGroups[$i]->fields); $j++) { ?>
					
					<?php if($profileGroups[$i]->fields[$j]->data->value != "") { ?>
					<tr>
						<td class="label">
							<?php echo $profileGroups[$i]->fields[$j]->name; ?>
						</td>
						<td class="data">
							<?php if($profileGroups[$i]->fields[$j]->type == 'datebox') { ?>
								<?php echo date('jS F Y', $profileGroups[$i]->fields[$j]->data->value); ?>
							<?php } else { ?> 
								<?php echo nl2br($profileGroups[$i]->fields[$j]->data->value); ?>
							<?php } ?>
						</td>
					</tr>
					<?php } // End If ?>
					
				<?php } // End For ?>
				</table>
				
			</div>
		
		<?php } ?>
	
	</div>
	
<?php get_footer(); ?>