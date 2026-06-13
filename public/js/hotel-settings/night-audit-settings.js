innGrid.nightAuditPreview = null;

innGrid.updateNightAuditPreview = function() {
	var baseURL = getBaseURL();
	var targetDate = $("#night-audit-target-date").val();

	$.post(baseURL + 'menu/get_night_audit_resulting_date', { target_date: targetDate }, function(data) {
		innGrid.nightAuditPreview = data;
		$("#night-audit-current-selling-date").text(data.current_selling_date);
		$("#night-audit-resulting-date").text(data.resulting_date);
		$("#night-audit-target-date").val(data.target_date);

		var minDate = new Date(data.current_selling_date.replace(/-/g, '/'));
		minDate.setDate(minDate.getDate() + 1);
		var maxDate = new Date(data.actual_date.replace(/-/g, '/'));

		$("#night-audit-target-date").datepicker('option', 'minDate', minDate);
		$("#night-audit-target-date").datepicker('option', 'maxDate', maxDate);

		if (data.days_to_process > 1) {
			$("#night-audit-days-summary").text(
				l('night_audit_will_process_days', 'Night audit will process {days} days to catch up.')
					.replace('{days}', data.days_to_process)
			);
		} else {
			$("#night-audit-days-summary").text(
				l('night_audit_will_process_one_day', 'Night audit will process 1 day.')
			);
		}
	}, 'json');
};

innGrid.openNightAuditPrompt = function() {
	$("#dialogNightAudit").dialog("open");
	$("#submitNightAuditButton").button({disabled: false });
	innGrid.updateNightAuditPreview();
};

innGrid.runNightAudit = function() {
	var baseURL = getBaseURL();
	var targetDate = $("#night-audit-target-date").val();

	$("#submitNightAuditButton").button({disabled: true });
	$("#dialogNightAudit").dialog("close");
	$("#dialogProcessingRequest").dialog("open");
	$.get(baseURL + "menu/run_night_audit", { target_date: targetDate },
		function(theXML) {
			alert(theXML);
			window.location.href = baseURL + "booking";
		}
	);
};


$(function() {

	$(".nightAuditButton").button();

	$("input[name='selling_date']").datepicker({
		  dateFormat: 'yy-mm-dd',
	});

	$("#night-audit-target-date").datepicker({
		dateFormat: 'yy-mm-dd',
		onSelect: function() {
			innGrid.updateNightAuditPreview();
		}
	});

	$("#night-audit-target-date").on("change", function() {
		innGrid.updateNightAuditPreview();
	});

	$("input[name='selling_date']").on("change", function() {
		alert(l("Property shouldn't run Night Audit Automatically while manipulating the selling date. Disabling Auto-Night Audit below."));
		$("input[name='night_audit_auto_run_is_enabled']").attr("checked", false);
	});

	$("#dialogNightAudit, #dialogUndoNightAudit, #dialogProcessingRequest").dialog({
			autoOpen: false,
			resizable: false,
			modal: true,
			movable: true,
			width: 450,
			height: 360
	});
	
	$('#run-night-audit-button').click(function(e) {
		e.preventDefault(); 
		innGrid.openNightAuditPrompt();
	});

	$("#cancelNightAuditButton").click(function() {
		$("#dialogNightAudit").dialog("close");
	});
	
	$("#submitNightAuditButton").click(function() {
		innGrid.runNightAudit();
	});



});
