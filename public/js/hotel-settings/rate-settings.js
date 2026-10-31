
// for browsing through the calendar by adding + or - number of days
innGrid.addDays = function(date, numDays) {
	// concantenate values to date_start and date_end hidden inputs
	date = new Date(date);
	date.setDate(date.getDate() + parseInt(numDays)); 

	var year = date.getFullYear();
    var month = ("0" + (date.getMonth() + 1)).slice(-2);
    var day = ("0" + date.getDate()).slice(-2);

	return year + '-' + month + '-' + day;
}
    // Ability for show decimal places
var show_decimal = true;
$.ajax({
        type   : "GET",
        url    : getBaseURL() + 'settings/rates/get_features_AJAX/hide_decimal_places',
        success: function (data) {
            if(data != 0){
                show_decimal = false;
            }
        }
    });
    
innGrid.formatRateValue = function(value, isDecimal) {
	if (value === null || value === undefined || value === '') {
		return null;
	}
	if (isDecimal) {
		var parsed = parseFloat(value);
		return parsed ? parsed.toLocaleString('en', { style: 'decimal', maximumFractionDigits: 2, minimumFractionDigits: 2 }) : null;
	}
	var parsedInt = parseInt(value, 10);
	return parsedInt ? parsedInt.toLocaleString() : null;
};

innGrid.getDayOfWeekLabel = function(dayOfWeek) {
	switch (dayOfWeek) {
		case '0': return l("Mon", true);
		case '1': return l("Tue", true);
		case '2': return l("Wed", true);
		case '3': return l("Thu", true);
		case '4': return l("Fri", true);
		case '5': return l("Sat", true);
		case '6': return l("Sun", true);
		default: return '';
	}
};

innGrid.renderRateCellValue = function(value, suppliedValue) {
	if (value === null || value === undefined || value === '') {
		return '<span class="er-value er-value--empty">—</span>';
	}
	var html = '<span class="er-value">' + value + '</span>';
	if (user_role === 'is_admin' && suppliedValue !== null && suppliedValue !== undefined && suppliedValue !== '' && suppliedValue != 0) {
		html += '<span class="er-supplied">(' + suppliedValue + ')</span>';
	}
	return html;
};

innGrid.renderStatusBadge = function(value, openLabel, closedLabel, openClass, closedClass, oneMeansOpen) {
	var isClosed = oneMeansOpen
		? (value === '0' || value === 0)
		: (value === '1' || value === 1);
	if (isClosed) {
		return '<span class="er-badge ' + closedClass + '">' + closedLabel + '</span>';
	}
	return '<span class="er-badge ' + openClass + '">' + openLabel + '</span>';
};

innGrid.loadRateDetail = function(ratePlanID, startDate, roomTypeID) {
	$('#er-calendar-body').addClass('er-loading');

	$.post(getBaseURL() + 'settings/rates/get_rates_JSON', {
		rate_plan_id: ratePlanID,
		start_date: startDate,
		room_type_id: roomTypeID
	}, function (data) {
		var rates = data.rates || [];
		var tableHTML = '<thead><tr><th class="er-th-metric">' + l('Metric', true) + '</th>';

		$.each(rates, function(index, rate) {
			var dayOfWeek = innGrid.getDayOfWeekLabel(rate.day_of_week);
			var isWeekend = rate.day_of_week === '5' || rate.day_of_week === '6';
			var dateParts = rate.date ? rate.date.split('-') : [];
			var shortDate = dateParts.length === 3 ? dateParts[1] + '/' + dateParts[2] : rate.date;

			tableHTML += '<th class="' + (isWeekend ? 'er-th-weekend' : '') + '">' +
				'<span class="er-date-num">' + shortDate + '</span>' +
				'<span class="er-date-dow">' + dayOfWeek + '</span>' +
				'</th>';
		});

		tableHTML += '</tr></thead><tbody>';

		var metrics = [
			{ key: 'adult_1_rate', suppliedKey: 'supplied_adult_1_rate', label: l('Rate', true) + ' (1 ' + l('Adult', true) + ')', section: true },
			{ key: 'adult_2_rate', suppliedKey: 'supplied_adult_2_rate', label: l('Rate', true) + ' (2 ' + l('Adults', true) + ')' },
			{ key: 'adult_3_rate', suppliedKey: 'supplied_adult_3_rate', label: l('Rate', true) + ' (3 ' + l('Adults', true) + ')' },
			{ key: 'adult_4_rate', suppliedKey: 'supplied_adult_4_rate', label: l('Rate', true) + ' (4 ' + l('Adults', true) + ')' },
			{ key: 'additional_adult_rate', label: l('Rate for Additional Adult', true) },
			{ key: 'additional_child_rate', label: l('Rate for Additional Child', true) },
			{ key: 'minimum_length_of_stay', label: l('Minimum Length of Stay', true), section: true },
			{ key: 'maximum_length_of_stay', label: l('Maximum Length of Stay', true) },
			{ key: 'closed_to_arrival', label: l('Closed to Arrival', true), type: 'arrival', section: true },
			{ key: 'closed_to_departure', label: l('Closed to Departure', true), type: 'departure' },
			{ key: 'can_be_sold_online', label: l('Can be sold online', true), type: 'online', section: true }
		];

		$.each(metrics, function(metricIndex, metric) {
			var rowClass = metric.section ? 'er-row-section' : '';
			tableHTML += '<tr' + (rowClass ? ' class="' + rowClass + '"' : '') + '><td class="er-td-metric">' + metric.label + '</td>';

			$.each(rates, function(index, rate) {
				var cellHTML = '';

				if (metric.type === 'arrival' || metric.type === 'departure') {
					cellHTML = innGrid.renderStatusBadge(rate[metric.key], l('Open', true), l('Closed', true), 'er-badge--open', 'er-badge--closed');
				} else if (metric.type === 'online') {
					cellHTML = innGrid.renderStatusBadge(rate[metric.key], l('Yes', true), l('No', true), 'er-badge--yes', 'er-badge--no', true);
				} else if (metric.key.indexOf('rate') > -1) {
					var formatted = innGrid.formatRateValue(rate[metric.key], show_decimal);
					var suppliedFormatted = metric.suppliedKey ? innGrid.formatRateValue(rate[metric.suppliedKey], show_decimal) : null;
					cellHTML = innGrid.renderRateCellValue(formatted, suppliedFormatted);
				} else {
					var losValue = rate[metric.key];
					cellHTML = (losValue !== null && losValue !== undefined && losValue !== '')
						? '<span class="er-value">' + losValue + '</span>'
						: '<span class="er-value er-value--empty">—</span>';
				}

				tableHTML += '<td>' + cellHTML + '</td>';
			});

			tableHTML += '</tr>';
		});

		tableHTML += '</tbody>';

		$("#rate-detail-table").html(tableHTML);

		if (rates.length > 0) {
			var firstDate = rates[0].date;
			var lastDate = rates[rates.length - 1].date;
			$('#er-date-range-label').text(firstDate + ' — ' + lastDate);
		}
        $(".modifiable_2").each(function() {
            //alert(123);
            $(this).attr("checked", false);
            $(this).prop("checked", false);
        });
			// append error message div with page header class
            $('#rates-error-msg').remove();
			$('#rate-detail-table').before('<div style="display:none;" id="rates-error-msg" class="alert alert-warning alert-dismissable"><a href="#" class="close" data-dismiss="alert" aria-label="close">X</a></div>');
            // append errors with error div 
            if(Object.keys(data.errors).length > 0)
            {
                for(var key in data.errors)
                {
                    if(data.errors[key]){
                        $('#rates-error-msg').append('<p>'+data.errors[key]+'.</p>');
                    }
                }
                $('#rates-error-msg').show();
            }
            else
            {
                $('#rates-error-msg').hide();
            }

		$('#er-calendar-body').removeClass('er-loading');
		}, 'json');
}



// Makes jquery datepicker to have limited range. (Greying out invalid selections)
innGrid.customRange = function(input) { 
	// disable the datepicker input field, so only calendar is allowed. 
	//This also prevents keyboard pop up in ipad
	//$(this).attr("disabled", true);

   // Split timestamp into [ Y, M, D, h, m, s ]
	var sd = $("#date_start").val().split(/[-]/);
	var cd = $("#date_start").val().split(/[-]/);
	
	// Apply each element to the Date function
	var current_selling_date = new Date(sd[0], sd[1]-1, sd[2]);
	var check_in_date = new Date(cd[0], cd[1]-1, cd[2]);
	var dateMin = current_selling_date;
	var dateMax = null;	
	
	if (input.id == "date_start" && $("#date_end").datepicker("getDate") != null)
	{
		dateMax = $("#date_end").datepicker("getDate");
		if (dateMin < current_selling_date)
		{
			dateMin = current_selling_date;
		}                       
	}
	else if (input.id == "date_end")
	{
		if ($("#date_start").datepicker("getDate") != null)
		{
			var tempDate = new Date($("#date_start").datepicker("getDate"));			
			dateMin = new Date(tempDate.getTime());
		}
	}

	return {
		minDate: dateMin, 
		maxDate: dateMax
	}; 

}

innGrid.customRangeForSuppliedRate = function(input) { 
	// disable the datepicker input field, so only calendar is allowed. 
	//This also prevents keyboard pop up in ipad
	//$(this).attr("disabled", true);
        
   // Split timestamp into [ Y, M, D, h, m, s ]
	var sd = $("#supplied_rate_date_start").val().split(/[-]/);
	var cd = $("#supplied_rate_date_start").val().split(/[-]/);
	
	// Apply each element to the Date function
	var current_selling_date = new Date(sd[0], sd[1]-1, sd[2]);
	var check_in_date = new Date(cd[0], cd[1]-1, cd[2]);
	var dateMin = current_selling_date;
	var dateMax = null;	
	
	if (input.id == "supplied_rate_date_start" && $("#supplied_rate_date_end").datepicker("getDate") != null)
	{
		dateMax = $("#supplied_rate_date_end").datepicker("getDate");
		if (dateMin < current_selling_date)
		{
			dateMin = current_selling_date;
		}                       
	}
	else if (input.id == "supplied_rate_date_end")
	{
		if ($("#supplied_rate_date_start").datepicker("getDate") != null)
		{
			var tempDate = new Date($("#supplied_rate_date_start").datepicker("getDate"));			
			dateMin = new Date(tempDate.getTime());
		}
	}

	return {
		minDate: dateMin, 
		maxDate: dateMax
	}; 

}

innGrid.createRate = function() {

	var end_date = $("#date_end").val()
	var confirmbox = false;

	if (end_date != '') {
		confirmbox = true;
	} else {
		confirmbox = confirm(l("Are you sure you don't want to set the end date? The default value of the end date will be set to FOREVER (10 years from now) and will update Rates for all future dates."));
	}

	if (confirmbox) {
	var rateArray = {
		date_start: $("#date_start").val(),
		date_end: $("#date_end").val(),
		rate_plan_id: $("input[name ='rate_plan_id']").val(),
        tab_identification: $("input[name ='tab_identification']").val()
		/*sunday: $("input[name ='sunday']").prop('checked'),
		monday: $("input[name ='monday']").prop('checked'),
		tuesday: $("input[name ='tuesday']").prop('checked'),
		wednesday: $("input[name ='wednesday']").prop('checked'),
		thursday: $("input[name ='thursday']").prop('checked'),
		friday: $("input[name ='friday']").prop('checked'),
		saturday: $("input[name ='saturday']").prop('checked')*/
	};

	if(rateArray.date_end == ''){
		var myDate = new Date(rateArray.date_start);
		myDate.setFullYear(myDate.getFullYear() + 10);
		myDate.setDate(myDate.getDate() - 1);

		rateArray.date_end = moment(myDate).format('YYYY-MM-DD');
	}

	$(".modifiable").each(function() {
            
		var fieldname = $(this).prop("name");
		var inputType = $(this).attr('type');

		if (inputType === 'radio') {
			if (rateArray[fieldname] !== undefined) {
				return;
			}
			var checkedRadio = $('input[name="' + fieldname + '"]:checked');
			if (checkedRadio.length) {
				rateArray[fieldname] = checkedRadio.val();
			}
		} else {
			rateArray[fieldname] = $(this).val() ? $(this).val() : 'null';
		}

	});
    $(".modifiable_2").each(function() {

        var fieldname = $(this).prop("name");

        //var is_modified = $(this).parents(".input-group").find(".modified");
        //var isChecked = $('.modifiable_2').prop('checked');
        var setValue = $('input[name='+fieldname+']:checked').val();
        if(setValue){
            rateArray[fieldname] = setValue;
        }
        else
        {
            rateArray[fieldname] = 0;
        }

       // rateArray[fieldname] = $(this).val() ? $(this).val() : 'null';

    });
    console.log(rateArray);
	$.post(getBaseURL() + 'settings/rates/create_rate_AJAX/',
		rateArray,
		function(data){
			console.log(data);
			if (data.status === 'error')
			{
				alert(data.message.replace(/<p>/g,'').replace(/<\/p>/g,''));
			}
			else
			{

				// update channel_manager

				var rateCreatedEvent = new CustomEvent('rate_created', { "detail" : {"rate_data" : rateArray} });
                document.dispatchEvent(rateCreatedEvent);

				// innGrid.updateRates(
    //                             rateArray.date_start, 
    //                             rateArray.date_end,
    //                             rateArray.rate_plan_id
    //                         );
				$('#create_rate_modal').modal("hide");

				// clear all fields after saving
				$(".rate-data input").each(function() {
                                    
					if ($(this).prop("name") !== "date_start" && $(this).prop("name") !== "supplied_rate_date_start" && $(this).prop("name") !== "additional-adult-rate")

					{
						if(!$(this).hasClass("modifiable_2") && $(this).attr('type')!='radio'){ 
							$(this).val("");
                                                        $(this).prop('readonly', false);
                                            }
					}
				});

				// Update end date place holder
                $('#date_end').prop('placeholder', l('Forever'));
                // $('#date_end').prop('placeholder', l('End Date'));

                $(".default_radio").each(function() {
                    $(this).prop("checked", true);
                });
                $("div[data-toggle=\"buttons\"] .btn-primary").removeClass('active');
                 $("div[data-toggle=\"buttons\"] .btn-primary:first-child").addClass('active');

                 $(".copy_to_all").each(function() {
                        $(this).prop("checked", false);
                });
                $(".modified").each(function() {
                        $(this).prop("checked", false);
                });

                $(".modifiable_2").each(function() {
                	//alert(123);
                    $(this).prop("checked", false);
                });

				$(".weekdays input").each(function() {
					$(this).prop("checked", true);
				});

				alert(l("successfully modified rates!"));
				
			}
		}, 'json'
	);
	}
}

// Create supplied rate
innGrid.createSuppliedRate = function() {

	var rateArray = {
		date_start: $("#supplied_rate_date_start").val(),
		date_end: $("#supplied_rate_date_end").val(),
		rate_plan_id: $("input[name ='rate_plan_id']").val(),
                room_type_id: $("input[name ='room_type_id']").val(),
		/*sunday: $("input[name ='sunday']").prop('checked'),
		monday: $("input[name ='monday']").prop('checked'),
		tuesday: $("input[name ='tuesday']").prop('checked'),
		wednesday: $("input[name ='wednesday']").prop('checked'),
		thursday: $("input[name ='thursday']").prop('checked'),
		friday: $("input[name ='friday']").prop('checked'),
		saturday: $("input[name ='saturday']").prop('checked')*/
	};

	$(".modifiable").each(function() {
            
		var fieldname = $(this).prop("name");
		var inputType = $(this).attr('type');

		if (inputType === 'radio') {
			if (rateArray[fieldname] !== undefined) {
				return;
			}
			var checkedRadio = $('input[name="' + fieldname + '"]:checked');
			if (checkedRadio.length) {
				rateArray[fieldname] = checkedRadio.val();
			}
		} else {
			rateArray[fieldname] = $(this).val() ? $(this).val() : 'null';
		}

	});
    console.log(rateArray);
	$.post(getBaseURL() + 'settings/rates/create_supplied_rate_AJAX/',
		rateArray,
		function(data){
			console.log(data);
			if (data.status === 'error')
			{
				alert(data.message.replace(/<p>/g,'').replace(/<\/p>/g,''));
			}
			else
			{


				// update channel_manager
				innGrid.updateRates(
                                rateArray.date_start, 
                                rateArray.date_end,
                                rateArray.rate_plan_id
                            );
				$('#create_supplied_rate_modal').modal("hide");

				// clear all fields after saving
				$(".rate-data input").each(function() {
					if ($(this).prop("name") !== "supplied_rate_date_start" && $(this).prop("name") !== "date_start")
					{
						$(this).val(""); 
                                                $(this).prop('readonly', false);
					}
				});
                                $(".copy_to_all").each(function() {
                                    $(this).prop("checked", false);
                            });
				$(".modified").each(function() {
					$(this).prop("checked", false);
				});

				$(".weekdays input").each(function() {
					$(this).prop("checked", true);
				});

				alert(l("successfully modified supplied rates!"));
				
			}
		}, 'json'
	);
}

$(function() {
    var rateArr = ["adult_1_rate","adult_2_rate","adult_3_rate","adult_4_rate","additional_adult_rate","additional_child_rate","minimum_length_of_stay","maximum_length_of_stay","minimum_length_of_stay_arrival","maximum_length_of_stay_arrival","supplied_adult_1_rate","supplied_adult_2_rate","supplied_adult_3_rate","supplied_adult_4_rate"];
    $.each(rateArr, function(index, item){
        $("input[name="+item+"]").blur(function(){
            if($(this).val()==''){
            $("input[name="+item+"]").prop('readonly', true);
            }else{
                $("input[name="+item+"]").prop('readonly', false);
            }

        });
        $("input[name="+item+"]").click(function(){
            $("input[name="+item+"]").prop('readonly', false);
        });
        $("#"+item+"_mon").blur(function(){
            if($(this).val()==''){
                $("#"+item+"_mon").prop('readonly', true);
            }else{
                $("#"+item+"_mon").prop('readonly', false);
            }
            

        });
        $("#"+item+"_mon").click(function(){
            $("#"+item+"_mon").prop('readonly', false);
        });
        $("#"+item+"_tue").blur(function(){
            if($(this).val()==''){
                $("#"+item+"_tue").prop('readonly', true);
            }else{
                $("#"+item+"_tue").prop('readonly', false);
            }
            

        });
        $("#"+item+"_tue").click(function(){
            $("#"+item+"_tue").prop('readonly', false);
        });
        $("#"+item+"_wed").blur(function(){
            if($(this).val()==''){
                $("#"+item+"_wed").prop('readonly', true);
            }else{
                $("#"+item+"_wed").prop('readonly', false);
            }
            

        });
        $("#"+item+"_wed").click(function(){
            $("#"+item+"_wed").prop('readonly', false);
        });
        $("#"+item+"_thu").blur(function(){
            if($(this).val()==''){
                $("#"+item+"_thu").prop('readonly', true);
            }else{
                $("#"+item+"_thu").prop('readonly', false);
            }
            

        });
        $("#"+item+"_thu").click(function(){
            $("#"+item+"_thu").prop('readonly', false);
        });
        $("#"+item+"_fri").blur(function(){
            if($(this).val()==''){
                $("#"+item+"_fri").prop('readonly', true);
            }else{
                $("#"+item+"_fri").prop('readonly', false);
            }
            

        });
        $("#"+item+"_fri").click(function(){
            $("#"+item+"_fri").prop('readonly', false);
        });
        $("#"+item+"_sat").blur(function(){
            if($(this).val()==''){
                $("#"+item+"_sat").prop('readonly', true);
            }else{
                $("#"+item+"_sat").prop('readonly', false);
            }
            

        });
        $("#"+item+"_sat").click(function(){
            $("#"+item+"_sat").prop('readonly', false);
        });
        $("#"+item+"_sun").blur(function(){
            if($(this).val()==''){
                $("#"+item+"_sun").prop('readonly', true);
            }else{
                $("#"+item+"_sun").prop('readonly', false);
            }
            

        });
        $("#"+item+"_sun").click(function(){
            $("#"+item+"_sun").prop('readonly', false);
        });
    });
    $(".modifiable_2").each(function() {
        $(this).attr("checked", false);
        $(this).prop("checked", false);
    });
   /* $(".modifiable_2").click(function(){
        alert('yes');
        $(this).attr("disabled", "readonly");
    });
    */
    $(".nav-tabs a").click(function(){
        $(this).tab('show');
    });
    $("#general_rates_tab").click(function(){

        $("#tab_identification").val(1);
    });
    $("#day_specific_rates_tab").click(function(){

        $("#tab_identification").val(2);
    });
	$("#date_start").datepicker({
			dateFormat: 'yy-mm-dd',				
			beforeShow: innGrid.customRange
		});

	$("#date_end").datepicker({
		dateFormat: 'yy-mm-dd',				
		beforeShow: innGrid.customRange
	});
        
        $("#supplied_rate_date_start").datepicker({
			dateFormat: 'yy-mm-dd',				
			beforeShow: innGrid.customRangeForSuppliedRate
		});

	$("#supplied_rate_date_end").datepicker({
		dateFormat: 'yy-mm-dd',				
		beforeShow: innGrid.customRangeForSuppliedRate
	});

	$(document).on('click', '.modifiable', function() {
        $(this).parents(".input-group").find(".modified").prop("checked", true);
	});

	$(document).on('click','#modify-rates', function(){
		$('#create_rate_modal').modal('show');
	});

	$(document).on('click','#modify-supplied-rates', function(){
		$('#create_supplied_rate_modal').modal('show');
	});
        
        
	$("#modify_rates_button").on('click', function() {		
		innGrid.createRate();
	});

	// when modal closes, update the calendar
	$('#create_rate_modal').on('hidden.bs.modal', function () {
	    innGrid.loadRateDetail(ratePlanID, currentDate, roomTypeID);
	});
        
        // Rate Supplied JS
        $("#modify_supplied_rates_button").on('click', function() {		
		innGrid.createSuppliedRate();
	});

	// when modal closes, update the calendar
	$('#create_supplied_rate_modal').on('hidden.bs.modal', function () {
	    innGrid.loadRateDetail(ratePlanID, currentDate, roomTypeID);
	});
        
	/*
		Calendar Control
	*/

	var ratePlanID = $("input[name ='rate_plan_id']").val();
        var roomTypeID = $("input[name ='room_type_id']").val();
	var currentDate = $("input[name ='today']").val();

	// load rate plan detail (the calendar)
	innGrid.loadRateDetail(ratePlanID, currentDate, roomTypeID);

	$('#show-previous-month').on('click', function() {
		currentDate = innGrid.addDays(currentDate, -28);
		innGrid.loadRateDetail(ratePlanID, currentDate, roomTypeID);
	});

	$('#show-next-month').on('click', function() {
		currentDate = innGrid.addDays(currentDate, 28);
		innGrid.loadRateDetail(ratePlanID, currentDate, roomTypeID);
	});

	$(document).on('click','[id^="all_"]',function(){
		var id = $(this).attr('id');
		var id_string = id.replace("all_", "");
                
		var mon_value = $("#"+id_string+"_mon").val();
		if($(this).is(':checked')){
			$("[id^="+id_string+"]").each(function(){
				var input_id = $(this).attr('id');
                if (id_string == "minimum_length_of_stay" || id_string == "maximum_length_of_stay") {
                    if(input_id.indexOf("minimum_length_of_stay_arrival") > -1 || input_id.indexOf("maximum_length_of_stay_arrival") > -1){
                        return;
                    }
                }
				$(this).val(mon_value);
                                $("#"+input_id).prop('readonly', false);
			});
		}
		else{
			$("[id^="+id_string+"]").each(function(){
				$(this).val('');
			});
            $("#"+id_string+"_mon").val(mon_value);
		}
	});

});