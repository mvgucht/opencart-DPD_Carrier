<?php echo $header; ?>
<div id="content">
  <div class="breadcrumb">
    <?php foreach ($breadcrumbs as $breadcrumb) { ?>
    <?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
    <?php } ?>
  </div>
  <?php if ($error_warning) { ?>
  <div class="warning"><?php echo $error_warning; ?></div>
  <?php } ?>
  <div class="box">
    <div class="heading">
      <h1><img src="view/image/shipping.png" alt="" /> <?php echo $heading_title; ?></h1>
      <div class="buttons"><a onclick="$('#form').submit();" class="button"><?php echo $button_save; ?></a><a href="<?php echo $cancel; ?>" class="button"><?php echo $button_cancel; ?></a></div>
    </div>
    <div class="content">
			<form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">
				<h2>Delis Credentials</h2>
        <table class="form">
          <tr>
            <td><span class="required">*</span> <?php echo $delis_id; ?></td>
            <td><input type="text" name="dpd_carrier_delis_id" cols="40" value="<?php echo $dpd_carrier_delis_id; ?>">
              <?php if ($delis_id_error) { ?>
              <span class="error"><?php echo $delis_id_error; ?></span>
              <?php } ?></td>
          </tr>
					<tr>
            <td><span class="required">*</span> <?php echo $delis_password; ?></td>
            <td><input type="password" name="dpd_carrier_delis_password" cols="40" value="<?php echo $dpd_carrier_delis_password; ?>">
              <?php if ($delis_password_error) { ?>
              <span class="error"><?php echo $delis_password_error; ?></span>
              <?php } ?></td>
          </tr>
					<tr>
						<td><span class="required">*</span> <?php echo $delis_server; ?></td>
						<td>
							<input type="radio" name="dpd_carrier_delis_server" value="1" <?php echo $dpd_carrier_delis_server ? "checked" : ""; ?>><?php echo $delis_server_live; ?><br>
							<input type="radio" name="dpd_carrier_delis_server" value="0" <?php echo $dpd_carrier_delis_server ? "" : "checked"; ?>><?php echo $delis_server_stage; ?>
							<?php if ($delis_server_error) { ?>
              <span class="error"><?php echo $delis_server_error; ?></span>
              <?php } ?></td>
						</td>
					</tr>
        </table>
				<h2>Layout Options</h2>
				<table class="form">
					<tr>
						<td><span class="required">*</span> <?php echo $locator_location; ?></td>
						<td>
							<input type="radio" name="dpd_carrier_locator_location" value="1" <?php echo $dpd_carrier_locator_location ? "checked" : ""; ?>><?php echo $locator_location_before; ?><br>
							<input type="radio" name="dpd_carrier_locator_location" value="0" <?php echo $dpd_carrier_locator_location ? "" : "checked"; ?>><?php echo $locator_location_after; ?>
							<?php if ($delis_server_error) { ?>
              <span class="error"><?php echo $delis_server_error; ?></span>
              <?php } ?></td>
						</td>
					</tr>
        </table>
				<h2>Web Service response time logging</h2>
				<table class="form">
					<tr>
						<td><span class="required">*</span> <?php echo $time_logging; ?></td>
						<td>
							<input type="radio" name="dpd_carrier_time_logging" value="1" <?php echo $dpd_carrier_time_logging ? "checked" : ""; ?>><?php echo $time_logging_on; ?><br>
							<input type="radio" name="dpd_carrier_time_logging" value="0" <?php echo $dpd_carrier_time_logging ? "" : "checked"; ?>><?php echo $time_logging_off; ?>
							<?php if ($delis_server_error) { ?>
              <span class="error"><?php echo $delis_server_error; ?></span>
              <?php } ?></td>
						</td>
					</tr>
        </table>
				<h2>Service Configuration</h2>
				<table id="rates" class="list">
          <thead>
            <tr>
              <td class="left"><?php echo $service_title_service; ?></td>
              <td class="left"><?php echo $service_title_status; ?></td>
              <td class="left"><?php echo $service_title_zone; ?></td>
              <td class="left"><?php echo $service_title_from; ?></td>
              <td class="left"><?php echo $service_title_cost; ?></td>
              <td></td>
            </tr>
          </thead>
					<?php $service_counter = 0; ?>
					<?php foreach ($services as $service_key => $service) { ?>
						<tbody id="dpd_carrier_service_<?php echo $service_key; ?>" data-service_id="<?php echo $service_key; ?>">
							<?php foreach($service['rows'] as $row_key => $row) { ?>
								<tr id="dpd_carrier_service_<?php echo $service_key . "_" . $row_key; ?>" data-row_id="<?php echo $row_key; ?>">
									<?php if($row_key == 0) { ?>
										<td class="left" rowspan="<?php echo count($service['rows']) ?>">
											<input type="hidden" name="dpd_carrier_service[<?php echo $service_key; ?>][name]" value="<?php echo $service['name']; ?>">
											<?php echo $service['name']; ?>
										</td>
										<td rowspan="<?php echo count($service['rows']) ?>">
											<input type="radio" name="dpd_carrier_service[<?php echo $service_key; ?>][status]" value="1" <?php echo $service['status'] ? "checked" : ""; ?>><?php echo $text_enabled; ?>
											<input type="radio" name="dpd_carrier_service[<?php echo $service_key; ?>][status]" value="0" <?php echo $service['status'] ? "" : "checked"; ?>><?php echo $text_disabled; ?>
										</td>
									<?php } ?>
									<td class="left">
										<select name="dpd_carrier_service[<?php echo $service_key; ?>][rows][<?php echo $row_key ?>][geo_zone_id]">
											<option value="0">All Zones</option>
											<?php foreach ($geo_zones as $geo_zone) { ?>
											<option value="<?php echo $geo_zone['geo_zone_id']; ?>" <?php echo $geo_zone['geo_zone_id'] == $row['geo_zone_id'] ? "selected" : ''; ?>><?php echo $geo_zone['name']; ?></option>
											<?php } ?>
										</select>
									</td>
									<td class="left"><input name="dpd_carrier_service[<?php echo $service_key; ?>][rows][<?php echo $row_key ?>][from]" value="<?php echo $row['from'] ?>" /></td>
									<td class="left"><input name="dpd_carrier_service[<?php echo $service_key; ?>][rows][<?php echo $row_key ?>][cost]" value="<?php echo $row['cost'] ?>" /></td>
									<td class="center" width="40px";>
										<a class="clone" onclick="cloneRow(event);"><img src="view/image/add.png" alt="" /></a>
										<a class="delete" <?php if($row_key == 0) { echo "style=\"display: none;\""; } ?>onclick="deleteRow(event);"><img src="view/image/delete.png" alt="" /></a>
									</td>
								</tr>
							<?php } ?>
						</tbody>
					<?php } ?>
        </table>
      </form>
    </div>
  </div>
</div>

<script>
	function updateNames(row, serviceID, currRowID) {
		var namePrefix = "dpd_carrier_service[" + serviceID + "][rows][" + currRowID + "]";
		var newRowID = $(row).attr('data-row_id');
		
		$(row).find("[name^='" + namePrefix + "']").each(function(i, obj){
			var namePostfix = obj.name.substring(namePrefix.length);
			var newName = "dpd_carrier_service[" + serviceID + "][rows][" + newRowID + "]" + namePostfix;
			obj.name = newName;
		});
	}
	
	function cloneRow(e) {
		// Get the row that was clicked
		var trClicked = $(e.target).parents("tr");
				
		// Clone the clicked row
		trNew = trClicked.clone();
		// If the new row is a clone from the first we remove the first two cells.
		if(trNew[0].cells.length > 4) {
			$(trNew[0].cells[0]).remove();
			$(trNew[0].cells[0]).remove();
		}
		// Enable the delete button
		trNew.find(".delete").attr('style', '');
		
		// Get the table body where the clicked row resides in.
		var tableBody = trClicked.parents("tbody");
		// Get the service ID
		var serviceID = tableBody.attr("data-service_id");
		// Get the current row id.
		var oldRowID = trClicked.data('row_id');
		// Calculate new row id (clicked row + 1)
		var newRowID = oldRowID + 1;
		
		// Set new id to cloned row
		trNew.attr('data-row_id', newRowID);
		updateNames(trNew, serviceID, oldRowID);
		
		// Update the ids of the existing rows (if they come after the added row)
		tableBody.find("tr").each(function(i, row){
			// Get current row ID
			var currRowID = parseInt($(row).attr('data-row_id'));
			// If the row comes after the new row
			if(currRowID >= newRowID){
				// Augment the id by 1
				$(row).attr('data-row_id', currRowID + 1 );
				
				updateNames(row, serviceID, currRowID);
			}
		});
		
		// Get the first row of the service so we can expand rowspan.
		trFirst = tableBody.find("tr:first");
		$(trFirst[0].cells[0]).attr('rowSpan', trFirst[0].cells[0].rowSpan + 1);
		$(trFirst[0].cells[1]).attr('rowSpan', trFirst[0].cells[1].rowSpan + 1);
		
		// Append the new row.
		trClicked.after(trNew);	
	}
	
	function deleteRow(e) {
		// Get the row that was clicked
		var trClicked = $(e.target).parents("tr");
		var oldRowID = trClicked.data('row_id');
		
		// Get the table body where the clicked row resides in.
		var tableBody = trClicked.parents("tbody");
		// Get the service ID
		var serviceID = tableBody.attr("data-service_id");
		
		trClicked.remove();
		
		// Update the ids of the existing rows (if they come after the added row)
		tableBody.find("tr").each(function(i, row){
			// Get current row ID
			var currRowID = parseInt($(row).attr('data-row_id'));
			// If the row comes after the new row
			if(currRowID >= oldRowID){
				// Decrease the id by 1
				$(row).attr('data-row_id', currRowID - 1 );
				
				updateNames(row, serviceID, currRowID);
			}
		});
	}
</script>

<?php echo $footer; ?>