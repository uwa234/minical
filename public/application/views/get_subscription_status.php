<html>
			<head>
				<style>
				table {
				  font-family: arial, sans-serif;
				  border-collapse: collapse;
				  width: 100%;
				}

				td, th {
				  border: 1px solid #dddddd;
				  text-align: left;
				  padding: 8px;
				}

				tr:nth-child(even) {
				  background-color: #dddddd;
				}
				</style>
			</head>
			<body>
	        	<table>
	        		<tr>
	        			<th><?php echo l('Company Id', true);?></th>
	        			<th><?php echo l('Company Name', true);?></th>
	        			<th><?php echo l('Subscription Id', true);?></th>
	        			<th><?php echo l('Subscription Type', true);?></th>
	        			<th><?php echo l('Subscription State', true);?></th>
	        			<th><?php echo l('Renewal Period', true);?></th>
	        			<th><?php echo l('Renewal Cost', true);?></th>
	        		</tr>

	        		<?php foreach ($comp_list as $key => $value) { ?>
	        			<tr>
	        				<td><?php echo $value['company_id']; ?></td>
	        				<td><?php echo $value['name']; ?></td>
	        				<td><?php echo $value['subscription_id']; ?></td>
	        				
	        				<td><?php echo $value['subscription_level']; ?></td>

	        				<td><?php echo $value['subscription_state']; ?></td>

	        				<td><?php echo $value['renewal_period']; ?></td>

	        				<td><?php echo $value['renewal_cost']; ?></td>
	        			</tr>
	        		<?php } ?>
	        	</table>
	        </body>
			</html>