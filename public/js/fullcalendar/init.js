// It all starts here!
$(function () {
    $('#overview_calendar').hide();
    $('#calendar').show();
    innGrid.getRooms(innGrid.buildCalendar);

    $("#room-edit-modal")
        .modal({show: false})
        .on('hide.bs.modal', function (e) {
            innGrid.calendar.refetchResources();
        });
    $(document).on('click', '#room_edit_save', function () {
        innGrid.saveRoom(selectedRoomID, false);
    });

    $(document).on('click', '#room_edit_save_and_proceed', function () {
        innGrid.saveRoom(selectedRoomID, true);
    });

    $(document).on('click', '#booking-calendar-legend-toggle', function () {
        var $legend = $('#booking-calendar-legend');
        var expanded = $legend.toggleClass('is-expanded').hasClass('is-expanded');
        $(this).attr('aria-expanded', expanded ? 'true' : 'false');
    });

    initBookingRoomTypeChips();
});

function initBookingRoomTypeChips() {
    var $chips = $('#booking-room-type-chips');
    if (!$chips.length) {
        return;
    }

    $(document).on('click', '.booking-room-type-chip', function () {
        var typeId = $(this).attr('data-room-type-id');
        if (typeId === undefined || typeId === null) {
            typeId = '';
        }
        $('.booking-room-type-chip').removeClass('is-active').attr('aria-selected', 'false');
        $(this).addClass('is-active').attr('aria-selected', 'true');
        $('[name=room-type]').val(typeId);
        filter_data();
    });

    $('[name=room-type]').on('change.bookingChips', function () {
        var val = $(this).val() || '';
        $('.booking-room-type-chip').removeClass('is-active').attr('aria-selected', 'false');
        if (val === '') {
            $('.booking-room-type-chip[data-room-type-id=""]').addClass('is-active').attr('aria-selected', 'true');
        } else {
            $('.booking-room-type-chip[data-room-type-id="' + val + '"]').addClass('is-active').attr('aria-selected', 'true');
        }
    });

    syncBookingRoomTypeChipsFromSelect();
}

function syncBookingRoomTypeChipsFromSelect() {
    var $chips = $('#booking-room-type-chips');
    if (!$chips.length) {
        return;
    }
    var val = $('[name=room-type]').val() || '';
    $('.booking-room-type-chip').removeClass('is-active').attr('aria-selected', 'false');
    if (val === '') {
        $('.booking-room-type-chip[data-room-type-id=""]').addClass('is-active').attr('aria-selected', 'true');
    } else {
        $('.booking-room-type-chip[data-room-type-id="' + val + '"]').addClass('is-active').attr('aria-selected', 'true');
    }
}

function filter_data() {
    innGrid.reloadCalendar();
}

//Gets rooms data from rooms.xml
//Lots of globals here. Needs to be fixed
innGrid.getRooms = function (callback) {
    var order = "room_name";
    var room_type_id = $('[name=room-type]').val();
    var group_id = "";//$('[name=room-housekeeping]').val();
    var floor_id = $('[name=room-floor]').val();
    var location_id = $('[name=room-location]').val();
    var reservation_type = $('[name=reservation-type]').val();
    var booking_source = $('[name=booking-source]').val();

    var getRoomsSuccessFn = function (data) {
        //Initialize Variables
        //For each Data
        var rooms = new Array();
        $(data).each(function (index, roomObject) {
            //rebuild objects in roomsArray but trim roomtype length
            //******************************
            rooms[index] = {};
            rooms[index].name = roomObject.room_name;
            rooms[index].status = roomObject.status;

            rooms[index].room_type = roomObject.acronym ? roomObject.acronym : (roomObject.room_type && roomObject.room_type.length > 6 ? roomObject.room_type.substr(0, 6) : roomObject.room_type);

            rooms[index].id = String(roomObject.room_id);
            rooms[index].room_type_id = roomObject.room_type_id;
            rooms[index].room_type_name = roomObject.room_type_name || roomObject.room_type || 'Other';

            rooms[index].sort_order = roomObject.sort_order ? parseInt(roomObject.sort_order) : 0;

        });

        innGrid.ajaxCache = innGrid.ajaxCache || {};

        // add unassigned room row for each room type
        var flags = [], room_types = [], l = rooms.length, i;
        for (i = 0; i < l; i++) {
            if (flags[rooms[i].room_type]) continue;
            flags[rooms[i].room_type] = true;
            room_types.push(rooms[i]);
        }

        if (!innGrid.isShowUnassignedRooms) {
            var unassigned_room = {
                name: "",
                status: "",
                room_type: "",
                id: "unassigned",
                room_type_id: ""
            };

            rooms.push(unassigned_room);

            $(room_types).each(function (i, room_type) {
                unassigned_room = {
                    name: "Unassigned",
                    status: "Clean",
                    room_type: room_type.room_type,
                    id: "unassigned-" + room_type.room_type_id,
                    room_type_id: room_type.room_type_id
                };
                rooms.push(unassigned_room);
            });
        }

        if (callback !== undefined) {
            callback(rooms);
        }
    };

    if (innGrid.roomsWithoutFilters) {
        getRoomsSuccessFn(innGrid.roomsWithoutFilters);
        innGrid.roomsWithoutFilters = null;
        return;
    }

    $.ajax({
        type: "GET",
        url: getBaseURL() + 'booking/get_all_rooms_in_JSON' + "?" + new Date().getTime(),  //To prevent IE caching
        dataType: "json",
        data: {
            order: order,
            room_type_id: room_type_id,
            group_id: group_id,
            floor_id: floor_id,
            location_id: location_id,
            reservation_type: reservation_type,
            booking_source: booking_source
        },
        success: getRoomsSuccessFn,
        error: innGrid.ajaxError
    });
}

//Creates calendar
innGrid.buildCalendar = function (rooms) {

    $('#calendar').html('');

    innGrid.roomsResources = rooms;

    var sellingDate = moment($("#sellingDate").val()).toDate();

    var width = $("body").width();

    var daysBeforeToday = Math.round(width / 400);
    var daysAfterToday = Math.round(width / 60);

    console.log('bdays',sessionStorage.getItem("beforeDays"));
    console.log('adays',sessionStorage.getItem("afterDays"));

    var today = daysBeforeToday;
    daysBeforeToday = sessionStorage.getItem("beforeDays") != null ? parseInt(sessionStorage.getItem("beforeDays")) : daysBeforeToday;
    daysAfterToday = sessionStorage.getItem("afterDays") != null ? parseInt(sessionStorage.getItem("afterDays")) : daysAfterToday;

    if(innGrid.featureSettings.calendarDays) {
        var calendarDays = innGrid.featureSettings.calendarDays;
    } else {
        var calendarDays = daysBeforeToday + daysAfterToday;
    }

    console.log('calendarDays',calendarDays);
    var calStartDate = moment(sellingDate).subtract(daysBeforeToday, 'days').toDate();

    var calResourceAreaWidth = localStorage.getItem('resourceAreaWidth');
    var currentLanguageCode = 'en';
    
    if(innGrid.featureSettings.cuurentLanguage == 'korean') {
        currentLanguageCode = 'ko';
    } else if(innGrid.featureSettings.cuurentLanguage == 'portuguese') {
        currentLanguageCode = 'pt';
    } else if(innGrid.featureSettings.cuurentLanguage == 'spanish') {
        currentLanguageCode = 'es';
    } else if(innGrid.featureSettings.cuurentLanguage == 'vietnamese') {
        currentLanguageCode = 'vi';
    } else if(innGrid.featureSettings.cuurentLanguage == 'french') {
        currentLanguageCode = 'fr';
    }

    var calendar_options = {
        locale: currentLanguageCode,
        plugins: ['resourceTimeline', 'interaction'],
        resourceColumns: [
            {
                labelText: l(innGrid.featureSettings.defaultRoomSingular, true),
                field: 'name'
            },
            {
                labelText: l("Type", true),
                field: 'room_type'
            }
        ],
        resources: innGrid.roomsResources,
        resourceOrder: 'sort_order, name',

        selectable: true,
        selectHelper: true,
        handleWindowResize: true,

        resourceAreaWidth: calResourceAreaWidth ? calResourceAreaWidth + 'px' : '15%',
        scrollTime: '00:00',
        height: 'auto',
        contentHeight: 'auto',

        header: {
            left: (innGrid.enableHourlyBooking ? 'customCreateBooking, customDayView, customMonthView, prev, customToday, next, customStartDatePicker' : 'customCreateBooking, customMonthView, prev, customToday, next, customStartDatePicker'),
            right: null,
            center: null
        },
        buttonText: {
            prev: '‹',
            next: '›'
        },

        defaultView: 'customMonthView',
        views: {
            customMonthView: {
                type: 'resourceTimeline',
                slotDuration: '24:00:00',
                duration: {days: parseInt(calendarDays)},
                buttonText: l('Monthly View', true),
                columnHeader: false,
                slotLabelFormat: [
                    {month: 'long', year: 'numeric'}, // top level of text
                    {day: 'numeric'},
                    {weekday: 'short'},
                ],
            },
            customDayView: {
                type: 'resourceTimelineDay',
                buttonText: l('Hourly View', true),
                slotLabelFormat: [
                    {month: 'long', day: 'numeric', year: 'numeric'}, // top level of text
                    {
                        hour: 'numeric',
                        minute: '2-digit',
                        omitZeroMinute: true,
                        meridiem: 'short'
                    },
                ],
            }
        },
        customButtons: {
            // Add custom datepicker
            customToday: {
                text: l('Today', true),
                click: function (e) {
                    if (innGrid.calendar.view.type === "customMonthView") {
                        if (innGrid.calendar.view.activeStart < calStartDate && calStartDate < innGrid.calendar.view.activeEnd) {
                            // date is between start and end hence gotodate won't work, so move calendar to future date and switch back to new date
                            innGrid.calendar.gotoDate(innGrid.calendar.view.activeEnd);
                            innGrid.calendar.gotoDate(calStartDate);
                        } else if (innGrid.calendar.view.activeStart == calStartDate) {
                            // do nothing as date is already set
                        } else {
                            innGrid.calendar.gotoDate(calStartDate);
                        }
                    } else {
                        innGrid.calendar.gotoDate(sellingDate);
                    }
                }
            },
            customStartDatePicker: {
                text: l('Start date', true)+': ' + moment(calStartDate).format('YYYY-MM-DD') + ' ▼',
                click: function () {
                    if (!$("#hidden-start-date-picker").length) {
                        var $btnCustomStartDate = $('.fc-customStartDatePicker-button');
                        $btnCustomStartDate.after('<div style="width: 1px;height: 1px;opacity: 0;"><input type="text" id="hidden-start-date-picker" class="hidden-start-date-picker"/></div>');
                        var left = $btnCustomStartDate.offset().left,
                            top = $btnCustomStartDate.offset().top + $btnCustomStartDate.height() + 13;

                        $("#hidden-start-date-picker").datepicker({
                            dateFormat: 'yy-mm-dd',
                            onSelect: function (date, startDatePicker) {
                                date = new Date(date);
                                if (innGrid.calendar.view.type === "customMonthView" && innGrid.calendar.view.activeStart < date && date < innGrid.calendar.view.activeEnd) {
                                    // date is between start and end hence gotodate won't work, so move calendar to future date and switch back to new date
                                    innGrid.calendar.gotoDate(innGrid.calendar.view.activeEnd);
                                    innGrid.calendar.gotoDate(date);
                                } else if (innGrid.calendar.view.activeStart == date) {
                                    // do nothing as date is already set
                                } else {
                                    innGrid.calendar.gotoDate(date);
                                }
                            },
                            beforeShow: function (event, ui) {
                                setTimeout(function () {
                                    ui.dpDiv.css({left: left, top: top});
                                }, 5);
                            }
                        });
                        $("#hidden-start-date-picker").datepicker("setDate", calStartDate);
                    }
                    $("#hidden-start-date-picker").datepicker("show");
                }
            },
            customCreateBooking: {
                text: l('Create New Booking', true),
                click: function () {
                    if (typeof $(this).openBookingModal !== 'undefined' && $.isFunction($(this).openBookingModal)) {
                        $(this).openBookingModal();
                    }
                }
            }
        },
        viewSkeletonRender: function () {
            if (innGrid.calendar.view.type === "customMonthView") {
                innGrid.calendar.gotoDate(calStartDate);
            } else {
                innGrid.calendar.gotoDate(sellingDate);
            }

            setTimeout(function () {
                var date = moment(innGrid.calendar.getDate()).format('YYYY-MM-DD');
                $('.fc-customStartDatePicker-button').text(l('Start date', true)+': ' + date + ' ▼');
                $("#hidden-start-date-picker").val(date);

                addCalendarHeaderFilters();
            }, 500);

            if ($(".fc-customMonthView-view > table, .fc-customDayView-view > table").length) {
                $(".fc-customMonthView-view > table, .fc-customDayView-view > table").stickyTableHeaders();
            }
        },
        datesRender: function (renderInfo) {
            var date = moment(innGrid.calendar.getDate()).format('YYYY-MM-DD');
            $('.fc-customStartDatePicker-button').text(l('Start date', true)+': ' + date + ' ▼');
            $("#hidden-start-date-picker").val(date);

            addCalendarHeaderFilters();
            if (innGrid.lastCalendarEvents && innGrid.lastCalendarEvents.length) {
                setTimeout(function () {
                    paintCalendarBookingEvents(innGrid.lastCalendarEvents);
                }, 50);
            }
        },
        dayRender: function (info) {
            if (innGrid.color && innGrid.color.length) {
                for (var i = 0; i < innGrid.color.length; i++) {
                    var date = moment(info.date).format('YYYY-MM-DD');
                    if (date >= innGrid.color[i]['start_date'] && date <= innGrid.color[i]['end_date']) {
                        if ($(info.el).hasClass("fc-today") && info.view.type === "customMonthView") {
                            var gradiant_color = 'linear-gradient(to left, #dbeafe,#dbeafe 50%,' + innGrid.color[i]['color_code'] + ' 50%)';
                            info.el.style.background = gradiant_color;
                            $('.fc-time-area.fc-widget-header').find('tr:not(:first-child)').find('th.fc-widget-header[data-date="' + date + '"]').css("background", gradiant_color);
                        } else {
                            info.el.style.backgroundColor = innGrid.color[i]['color_code'];
                            $('.fc-time-area.fc-widget-header').find('tr:not(:first-child)').find('th.fc-widget-header[data-date="' + date + '"]').css("background-color", innGrid.color[i]['color_code']);
                        }
                        break;
                    }
                }
            }
        },
        resourceRender: function (renderInfo) {
            var status = (renderInfo.resource.extendedProps && renderInfo.resource.extendedProps.status) || renderInfo.resource.status;
            renderInfo.el.classList.remove('room-status-dirty', 'room-status-inspected');
            if (status === "Dirty") {
                renderInfo.el.classList.add('room-status-dirty');
            } else if (status === "Inspected") {
                renderInfo.el.classList.add('room-status-inspected');
            }

            var editIcon = (renderInfo.resource.id == '' || renderInfo.resource.id == 'unassigned' || renderInfo.resource.id == 'unassigned-' + renderInfo.resource.extendedProps.room_type_id) ? "" : "<div class='edit-icon'></div>";

            if (editIcon) {
                var $el = $(renderInfo.el);
                $el.append(editIcon);
                $el.on('click', function () {
                    innGrid.populateRoomEditModal($el.parent('tr'));
                });
            }
        },

        editable: true,
        dragOpacity: .5,
        dragRevertDuration: 100,
        selectMinDistance: 3,
        eventResizableFromStart: false,
        selectOverlap: true,

        defaultDate: calStartDate,
        now: sellingDate,

        eventClick: innGrid.bookingEventClicked,
        eventResize: innGrid.bookingResized,
        eventDrop: occupacyMoved,
        eventSources: [getCalendarBookings],
        eventRender: function (eventOrInfo, element) {
            var el = element || (eventOrInfo && eventOrInfo.el);
            var fcEvent = (eventOrInfo && eventOrInfo.event) ? eventOrInfo.event : eventOrInfo;
            var eventData = fcEvent && fcEvent.extendedProps ? fcEvent.extendedProps.data : null;
            if (!el || !eventData || parseInt(eventData.state, 10) !== 7) {
                return;
            }
            el.classList.remove('border-red');
            el.classList.add('unconfirmed-booking');
            el.style.background = 'linear-gradient(180deg, #fffbeb 0%, #fef3c7 100%)';
            el.style.borderColor = 'rgba(245, 158, 11, 0.45)';
            el.style.color = '#78350f';
            var bgEl = el.querySelector('.fc-bg');
            if (bgEl) {
                bgEl.style.background = 'transparent';
                bgEl.style.opacity = '1';
            }
        },
    };

    if (innGrid.isDisplayTooltip) {
        calendar_options['eventMouseEnter'] = function (info) {
            var groupId = (info.event.extendedProps.data.id_group_booking != 'null') ? "<strong>" + l("group_id") + ":</strong> " + info.event.extendedProps.data.id_group_booking : '';
            var groupName = (info.event.extendedProps.data.group_name != 'null') ? "<strong>" + l("group_name") + ":</strong> " + info.event.extendedProps.data.group_name : '';
           var totalAmount =  parseFloat(info.event.extendedProps.data.balance) + parseFloat(info.event.extendedProps.data.payment_total);
            totalAmount = totalAmount.toFixed(2);
            // get booking sources text
            var sources = [];
            for(var i = 0; i < innGrid.bookingSources.length; i++)
            {
                sources.push({id: innGrid.bookingSources[i].id, name: innGrid.bookingSources[i].name});
            }
            
            var sourceVal = info.event.extendedProps.data.booking_source;
            var sourceText = '';
            $.each(sources, function (i, source) {
                if (source.id == sourceVal || source.name == sourceVal)
                {
                    sourceText = source.name;
                }
            });

            var notes = info.event.extendedProps.data.booking_notes.length > 120 ? info.event.extendedProps.data.booking_notes.substr(0, info.event.extendedProps.data.booking_notes.lastIndexOf(' ', 120)) + '...' : info.event.extendedProps.data.booking_notes;

            // Use stored booking dates (not rendered event range) for accurate night count
            var checkInDate = moment(info.event.extendedProps.data.check_in_date).format('YYYY-MM-DD');
            var checkOutDate = moment(info.event.extendedProps.data.check_out_date).format('YYYY-MM-DD');
            var diffDays = moment(checkOutDate).diff(moment(checkInDate), 'days');
            var warning_message = info.event.extendedProps.data.warning_message;

            // get staying customers list
            var staying_customers = info.event.extendedProps.data.staying_customers && info.event.extendedProps.data.staying_customers != 'null' ? info.event.extendedProps.data.staying_customers : '';
            sourceText = sourceText && sourceText != 'null' ? sourceText : "";

            var resDetails = '<div class="tooltip-reservation" style="display:none;position:absolute;z-index:10001;padding:10px 12px;font-size:12px;">' +
                '<p><span>' + groupId + '</span> <span>' + groupName + '</span></p>' +
                '<p style="margin-bottom:8px;text-transform: capitalize;"><strong>' + info.event.extendedProps.data.booking_customer + staying_customers + '</strong></p>' +
                '<p><strong>' + l("Room", true) + ':</strong> ' + info.event.extendedProps.data.room_name + '</p>' +
                '<p><span><strong>' + l("check_in", true) + ':</strong> ' + innGrid._getLocalFormattedDate(checkInDate) + '</span> <span><strong>' + l("check_out", true) + ':</strong> ' + innGrid._getLocalFormattedDate(checkOutDate) + '</span></p>' +
                '<p><strong>' + l("Number of nights", true) + ':</strong> ' + diffDays + '</p>' +
                '<p> <span><strong>' + l("Total Amount", true) + ':</strong> ' + parseFloat(totalAmount) + '</span></p>' +
                '<p> <span><strong>' + l("Balance Due", true) + ':</strong> ' + parseFloat(info.event.extendedProps.data.balance).toFixed(2) + '</span></p>' +
                '<p style="margin-bottom:8px;"> <span><strong>' + l("Paid Amount", true) + ':</strong> ' + parseFloat(info.event.extendedProps.data.payment_total).toFixed(2) + '</span></p>' +
                '<p><span><strong>' + l("Booking Source", true) + ':</strong> ' + sourceText + '</span></p>' +
                '<p><div style="max-width: 300px;"><strong>' + l("booking_notes", true) + ':</strong> ' + notes +
                (warning_message ? ('<p><div style="max-width: 300px;"><strong>' + l("Warning", true) + ':</strong> ' + warning_message) : '') +
                '</div></p>' +
            '</div>';

            $("body").append(resDetails);

            $('.tooltip-reservation').css('top', info.jsEvent.pageY + 10);
            $('.tooltip-reservation').css('left', info.jsEvent.pageX + 20);
            $('.tooltip-reservation').fadeIn('500');
            $('.tooltip-reservation').fadeTo('10', 1.9);

            $(info.el).mouseover(function (e) {
                $(this).css('z-index', 10000);
                $('.tooltip-reservation').fadeIn('500');
                $('.tooltip-reservation').fadeTo('10', 1.9);
            }).mousemove(function (e) {
                $('.tooltip-reservation').css('top', e.pageY + 10);
                $('.tooltip-reservation').css('left', e.pageX + 20);
            });

        };

        calendar_options['eventMouseLeave'] = function (info) {
            $(this).css('z-index', 8);
            $('.tooltip-reservation').remove();
        };
    }

    if (innGrid.hasBookingPermission == '1') {
        calendar_options.select = function (info) {
            var roomId = info.resource ? String(info.resource.id) : '';
            var room = {
                room_id: roomId,
                room_type_id: info.resource && info.resource.extendedProps ? info.resource.extendedProps.room_type_id : '',
                room_name: info.resource ? info.resource.title : ''
            };
            var stayDates = calendarSelectionToBookingDates(info.start, info.end);
            var start = stayDates.checkIn;
            var end = stayDates.checkOut;
            var pos = info.jsEvent ? { left: info.jsEvent.pageX + 12, top: info.jsEvent.pageY + 12 } : null;

            if (typeof innGrid.showCalendarRangeActions === 'function' && roomId && roomId.indexOf('unassigned') !== 0) {
                innGrid.showCalendarRangeActions(start, end, room, pos);
            } else {
                innGrid.createNewBooking(start, end, room);
            }
            this.unselect();
        };
    }

    var calendarEl = document.getElementById('calendar');

    innGrid.calendar = new FullCalendar.Calendar(calendarEl, calendar_options);

    innGrid.calendar.render();

    setTimeout(function () {
        if (innGrid.calendar) {
            innGrid.calendar.updateSize();
            if (innGrid.calendar.refetchEvents) {
                innGrid.calendar.refetchEvents();
            }
        }
        syncBookingRoomTypeChipsFromSelect();
    }, 150);

    if (innGrid.hasBookingPermission == '0') {
        $('.fc-customCreateBooking-button').prop('disabled', true);
    }
}

//Get booking data
//Format is decided by Full Calendar format. See FullCalendar online documentation.
var calendarBookingFetchTimer = null;
var calendarBookingFetchPending = null;

/**
 * Convert a FullCalendar selection range to booking check-in / check-out dates.
 * Selection end is exclusive and already equals the checkout date (the day after
 * the last occupied night).
 */
function calendarSelectionToBookingDates(selectionStart, selectionEnd) {
    var checkIn = moment(selectionStart).startOf('day');
    var checkOut = moment(selectionEnd).startOf('day');
    return {
        checkIn: checkIn.format('YYYY-MM-DD HH:mm:ss'),
        checkOut: checkOut.format('YYYY-MM-DD HH:mm:ss')
    };
}

function normalizeBookingList(bookingObjects) {
    if ($.isArray(bookingObjects)) {
        return bookingObjects;
    }
    if (bookingObjects && typeof bookingObjects === 'object') {
        return $.map(bookingObjects, function (value) {
            return value;
        });
    }
    return [];
}

function getCalendarBookings(data, successCallback, failureCallback) {
    calendarBookingFetchPending = {
        data: data,
        successCallback: successCallback,
        failureCallback: failureCallback
    };

    if (calendarBookingFetchTimer) {
        clearTimeout(calendarBookingFetchTimer);
    }

    calendarBookingFetchTimer = setTimeout(function () {
        calendarBookingFetchTimer = null;
        runCalendarBookingFetch();
    }, 150);
}

function runCalendarBookingFetch() {
    var pending = calendarBookingFetchPending;
    if (!pending) {
        return;
    }
    calendarBookingFetchPending = null;

    var data = pending.data;
    var successCallback = pending.successCallback;
    var failureCallback = pending.failureCallback;
    var start = data.start;
    var end = data.end;
    var room_type_id = $('[name=room-type]').val();
    var group_id = $('[name=room-housekeeping]').val();
    var floor_id = $('[name=room-floor]').val();
    var location_id = $('[name=room-location]').val();
    var reservation_type = $('[name=reservation-type]').val();
    var booking_source = $('[name=booking-source]').val();
    var sellingdate = $("#sellingDate").val();
    sellingdate = new Date(sellingdate.replace(/-/g, '/'));

    var beforeTime = (new Date(sellingdate) - new Date(start));
    var beforeDays = Math.ceil(beforeTime / (1000 * 60 * 60 * 24));

    var afterTime = (new Date(end) - new Date(sellingdate));
    var afterDays = Math.ceil(afterTime / (1000 * 60 * 60 * 24));

    sessionStorage.setItem("beforeDays", beforeDays);
    sessionStorage.setItem("afterDays", afterDays);
    sessionStorage.setItem("currentCompanyId", $('#currentCompanyId').val());

    var param = 'start=' + encodeURIComponent(start.toUTCString()) + '&end=' + encodeURIComponent(moment(end).add(1, 'day').toDate().toUTCString()) + '&room_type_id=' + room_type_id + '&group_id=' + group_id + '&floor_id=' + floor_id + '&location_id=' + location_id + '&reservation_type=' + reservation_type + '&booking_source=' + booking_source;

    get_bookings_AJAX_request = $.ajax({
        type: "POST",
        url: getBaseURL() + 'booking/get_bookings_in_JSON',
        data: param,
        dataType: "json",
        success: function (bookingObjects) {
            applyCalendarBookingResponse(bookingObjects, successCallback, failureCallback);
            if (calendarBookingFetchPending) {
                runCalendarBookingFetch();
            }
        },
        error: function (xhr, textStatus) {
            if (textStatus === 'abort') {
                if (calendarBookingFetchPending) {
                    runCalendarBookingFetch();
                }
                return;
            }
            applyCalendarBookingResponse([], successCallback, failureCallback);
            if (calendarBookingFetchPending) {
                runCalendarBookingFetch();
            }
        }
    });
}

function applyCalendarBookingResponse(bookingObjects, successCallback, failureCallback) {
    bookingObjects = normalizeBookingList(bookingObjects);
    var bookingsArray = [];

    if (bookingObjects.length) {
        $(bookingObjects).each(function (index, value) {
            bookingsArray.push({
                booking_id: value.booking_id,
                room_id: (value.room_id == 0 || value.room_id == null)
                    ? l('unassigned', true) + '-' + (value.brh_room_type_id || value.room_type_id)
                    : String(value.room_id),
                room_type_id: value.room_type_id,
                room_name: value.room_name,
                warning_message: value.warning_message,
                border_color: value.border_color,
                color: value.color,
                customer_type_id: value.customer_type_id,
                group_booking: value.is_group_booking != null ? 'group-booking' : undefined,
                customer_name: buildBookingCustomerLabel(value),
                staying_customers: value.staying_customers,
                booking_customer: value.customer_name,
                check_in_date: value.check_in_date,
                check_out_date: value.check_out_date,
                state: value.state,
                booking_source: value.source,
                id_group_booking: value.is_group_booking,
                group_name: value.group_name,
                payment_total: value.payment_total,
                balance: value.balance,
                booking_notes: value.booking_notes
            });
        });
    }

    var events = convertToEvents(bookingsArray);
    bookings = events;
    innGrid.lastCalendarEvents = events;

    if (innGrid.isOverviewCalendar) {
        return;
    }

    if (typeof successCallback === 'function') {
        successCallback(events);
    }

    paintCalendarBookingEvents(events);
}

function buildBookingCustomerLabel(value) {
    var numberOfStayingGuests = parseInt(value.guest_count, 10) || 0;

    if (value.state == 3) {
        return l('maintenance', true) || 'Maintenance';
    }
    if (numberOfStayingGuests > 1) {
        return value.customer_name + ", " + value.guest_name + " " + l('and') + " " + (numberOfStayingGuests - 1) + " " + l('other(s)');
    }
    if (numberOfStayingGuests === 1) {
        return value.customer_name + " " + l('with') + " " + value.guest_name;
    }
    return value.customer_name;
}

function paintCalendarBookingEvents(events) {
    if (!innGrid.calendar || innGrid.isOverviewCalendar) {
        return;
    }

    var calendar = innGrid.calendar;
    var existing = calendar.getEvents();
    for (var i = 0; i < existing.length; i++) {
        existing[i].remove();
    }

    if (!events || !events.length) {
        return;
    }

    for (var j = 0; j < events.length; j++) {
        if (events[j]) {
            calendar.addEvent(events[j]);
        }
    }
}

innGrid.ajaxError = function (XMLHttpRequest, textStatus, errorThrown) {
    /*
    if(XMLHttpRequest.status==200)
        $('#errorDiv').html('200 AJAX error occured: '+textStatus);
    else
        $('#errorDiv').html('AJAX error occured: '+XMLHttpRequest.status+' '+XMLHttpRequest.statusText);
    */
}

//Converts booking entry (taken from XML) to event used by calendar
function convertToEvents(data) {
    var bookings = [];

    for (var i = 0; i < data.length; i++) {
        if (!data[i]) {
            continue;
        }

        var flag = '';
        if (data[i].customer_type_id == '-1') {
            flag = 'blacklist';
        } else if (data[i].customer_type_id == '-2') {
            flag = 'vip';
        }

        var startMoment = moment(data[i].check_in_date);
        var endMoment = moment(data[i].check_out_date);
        if (!startMoment.isValid() || !endMoment.isValid()) {
            continue;
        }

        // FullCalendar end is exclusive; check_out_date is already the day after the
        // last occupied night. Same-day stays need a 1-day span so the bar fills a slot.
        if (!endMoment.isAfter(startMoment)) {
            endMoment = startMoment.clone().add(1, 'day');
        }

        var resourceId = String(data[i].room_id);

        var event = {
            id: String(data[i].booking_id),
            title: "" + (data[i].customer_name || ''),
            resourceId: resourceId,
            resourceIds: [resourceId],
            start: startMoment.format("YYYY-MM-DD HH:mm:ss"),
            end: endMoment.format("YYYY-MM-DD HH:mm:ss")
        };

        if (parseInt(data[i].state, 10) === 3) {
            event.title = l('maintenance', true) || 'Maintenance';
            event.backgroundColor = '#000000';
            event.borderColor = '#000000';
            event.textColor = '#ffffff';
            event.classNames = [flag, data[i].group_booking, 'maintenance-block'].filter(Boolean);
        } else {
            var bookingState = parseInt(data[i].state, 10);
            event.backgroundColor = (data[i].color && data[i].color != 'transparent') ? "#" + data[i].color : getdefaultStateBGColor(bookingState);
            event.borderColor = event.backgroundColor;
            event.textColor = getdefaultStateTextColor(bookingState);
            event.classNames = [flag, data[i].group_booking].filter(Boolean);
            if (bookingState !== 7) {
                event.classNames.push("border-" + data[i].border_color);
            }
            if (bookingState === 7) {
                event.classNames.push('unconfirmed-booking');
                event.backgroundColor = '#fef3c7';
                event.borderColor = '#fcd34d';
                event.textColor = '#78350f';
            }
        }

        event.extendedProps = {
            data: {
                booking_id: data[i].booking_id,
                color: "" + data[i].color,
                check_in_date: "" + data[i].check_in_date,
                check_out_date: "" + data[i].check_out_date,
                booking_source: "" + data[i].booking_source,
                id_group_booking: "" + data[i].id_group_booking,
                group_name: "" + data[i].group_name,
                payment_total: "" + data[i].payment_total,
                balance: "" + data[i].balance,
                room_name: "" + data[i].room_name,
                booking_customer: "" + data[i].booking_customer,
                staying_customers: "" + data[i].staying_customers,
                customer_type_id: "" + data[i].customer_type_id,
                room_id: resourceId,
                state: parseInt(data[i].state, 10),
                warning_message: data[i].warning_message,
                booking_notes: data[i].booking_notes
            }
        };
        event.data = event.extendedProps.data;

        bookings.push(event);
    }

    return bookings;
}

//Occures when an booking is moved. Gets an event that represents the booking as parameter.
function occupacyMoved(info) {
    var dateFrom1 = moment(info.oldEvent.start).format('YYYY-MM-DD HH:mm:ss');
    var dateTo1 = moment(info.oldEvent.end || info.oldEvent.start).format('YYYY-MM-DD HH:mm:ss');
    var dateFrom2 = moment(info.event.start).format('YYYY-MM-DD HH:mm:ss');
    var dateTo2 = moment(info.event.end || info.event.start).format('YYYY-MM-DD HH:mm:ss');

    var room1 = info.oldResource ? info.oldResource.extendedProps.name : '';
    var room2 = info.newResource ? info.newResource.extendedProps.name : '';
    var type1 = info.oldResource ? info.oldResource.extendedProps.room_type : '';
    var type2 = info.newResource ? info.newResource.extendedProps.room_type : '';
    var room_id = info.newResource ? info.newResource.id : info.event.extendedProps.data.room_id;
    var state = info.event.extendedProps.data.state;

    // Generate alert string for moving booking blocks
    confirmation_string = "";
    if (type1 != type2) {
        confirmation_string = confirmation_string + "- "+l('Roomtype will be changed from')+" " + type1 + " "+l('to')+" " + type2 + "\n";
    }
    if (room1 != room2) {
        confirmation_string = confirmation_string + "- "+l('Room will be changed from')+" " + room1 + " "+l('to')+" " + room2 + "\n";
    }
    if (dateFrom1 != dateFrom2) {
        confirmation_string = confirmation_string + "- "+l('Check-in date will change from')+" " + dateFrom1 + " "+l('to')+" " + dateFrom2 + "\n";
    }
    confirmation_string = confirmation_string + l("Are you sure?");

    if (!confirm(confirmation_string)) {
        info.revert();
    } else {
        $.ajax({
            url: getBaseURL() + 'calendar/move_booking_room_history',
            type: "POST",
            data: {
                booking_id: info.event.id,
                room_id: room_id,
                state: state,
                start1: encodeURIComponent(dateFrom1),
                end1: encodeURIComponent(dateTo1),
                start2: encodeURIComponent(dateFrom2),
                end2: encodeURIComponent(dateTo2)
            },
            success: function(data){
                if (data.trim()  != "success") {
                    alert(data);
                    info.revert();
                } else {
                    
                    // reload booking_list_wrap and calendar
                    innGrid.reloadBookings();
                    if(dateFrom1 < dateFrom2){
                        innGrid.updateAvailabilities(
                            dateFrom1,
                            dateTo2
                        );
                    }else{
                        innGrid.updateAvailabilities(
                            dateFrom2,
                            dateTo1
                        );
                    }  

                }
            }
        });
    }
}

function getdefaultStateBGColor(state) {
    var defaultColors = {
        "0": "#2563eb", // reservation
        "1": "#059669", // in-house
        "2": "#d97706", // checked-out
        "3": "#334155", // maintenance / out of order
        "4": "#94a3b8", // cancelled
        "5": "#dc2626", // no show
        "6": "#f1f5f9", // deleted
        "7": "#fef3c7" // unconfirmed reservation
    };
    return defaultColors[state] ? defaultColors[state] : '#2563eb';
}

function getdefaultStateTextColor(state) {
    var defaultColors = {
        "3": "#ffffff",
        "4": "#1f2937",
        "5": "#ffffff",
        "6": "#6b7280",
        "7": "#78350f"
    };
    return defaultColors[state] ? defaultColors[state] : '#ffffff';
}


innGrid.bookingEventClicked = function (info) {
    if (typeof $.fn.openBookingModal !== 'undefined' && $.isFunction($.fn.openBookingModal)) {
        if (innGrid.hasBookingPermission == '1' || (typeof innGrid.hasBookingPermission === 'undefined')) {
            $.fn.openBookingModal({
                id: info.event.id,
                selected_room_id: info.event.extendedProps.data.room_id,
            });
        }
    }
}

//Occurs when an booking is resized. Gets an event that represents the booking as parameter.
innGrid.bookingResized = function (info) {
    var dateFrom1 = moment(info.prevEvent.start).format('YYYY-MM-DD HH:mm:ss');
    var dateTo1 = moment(info.prevEvent.end || info.prevEvent.start).format('YYYY-MM-DD HH:mm:ss');

    var dateFrom2 = moment(info.event.start).format('YYYY-MM-DD HH:mm:ss');
    var dateTo2 = moment(info.event.end || info.event.start).format('YYYY-MM-DD HH:mm:ss');

    var room_id = info.event.extendedProps.data.room_id;
    var state = info.event.extendedProps.data.state;

    if (!confirm(l('Change check-out date from')+" " + dateTo1 + " "+l('to')+" " + dateTo2 + "?")) {
        info.revert();
    } else {
        $.ajax({
            url: getBaseURL() + 'calendar/resize_booking_room_history',
            type: "POST",
            data: {
                booking_id: info.event.id,
                room_id: room_id,
                state: state,
                start1: encodeURIComponent(dateFrom1),
                end1: encodeURIComponent(dateTo1),
                start2: encodeURIComponent(dateFrom2),
                end2: encodeURIComponent(dateTo2)
            },

            success: function(data){
                if (data.trim() != "success") {
                    alert(data);
                    info.revert();
                } else {
                   
                    // reload booking_list_wrap and calendar
                    innGrid.reloadBookings();
                    if(dateTo1 > dateTo2) {
                        innGrid.updateAvailabilities(
                            dateFrom2,
                            dateTo1
                        );
                    }else{
                         innGrid.updateAvailabilities(
                            dateFrom2,
                            dateTo2
                        );
                    }
                }
            }
        });
    }
}

;(function ($, window, undefined) {
    'use strict';

    var name = 'stickyTableHeaders';
    var defaults = {
        fixedOffset: 0
    };

    function Plugin(el, options) {
        // To avoid scope issues, use 'base' instead of 'this'
        // to reference this class from internal events and functions.
        var base = this;

        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;

        // Listen for destroyed, call teardown
        base.$el.bind('destroyed',
            $.proxy(base.teardown, base));

        // Cache DOM refs for performance reasons
        base.$window = $(window);
        base.$clonedHeader = null;
        base.$originalHeader = null;
        base.$fixedHeadersTable = null;

        // Keep track of state
        base.isSticky = false;
        base.leftOffset = null;
        base.topOffset = null;

        base.init = function () {
            base.options = $.extend({}, defaults, options);

            base.$el.each(function () {
                var $this = $(this);

                // remove padding on <table> to fix issue #7
                $this.css('padding', 0);

                base.$originalHeader = $('thead', this);

                base.$fixedHeadersTable = $("<table>");
                base.$fixedHeadersTable.addClass('fixedHeadersTable');
                base.$fixedHeadersTable.css({
                    position: 'fixed',
                    padding: 0,
                    zIndex: 10,
                    backgroundColor: 'white',
                });

                $this.before(base.$fixedHeadersTable);

                base.$printStyle = $('<style type="text/css" media="print">' + '.fixedHeadersTable { display:none !important; }' + '</style>');
                $('head').append(base.$printStyle);
            });

            base.toggleHeaders();
            base.bind();
        };

        base.destroy = function () {
            base.$el.unbind('destroyed', base.teardown);
            base.teardown();
        };

        base.teardown = function () {
            $.removeData(base.el, 'plugin_' + name);

            base.unbind();

            base.$fixedHeadersTable.remove();
            base.$printStyle.remove();

            base.el = null;
            base.$el = null;
        };

        base.bind = function () {
            base.$window.on('scroll.' + name, base.toggleHeaders);
            base.$window.on('resize.' + name, base.toggleHeaders);
            base.$window.on('resize.' + name, base.updateWidth);
        };

        base.unbind = function () {
            // unbind window events by specifying handle so we don't remove too much
            base.$window.off('.' + name, base.toggleHeaders);
            base.$window.off('.' + name, base.updateWidth);
            base.$el.off('.' + name);
            base.$el.find('*').off('.' + name);
        };

        base.toggleHeaders = function () {
            base.$el.each(function () {
                var $this = $(this);

                var newTopOffset = isNaN(base.options.fixedOffset) ?
                    base.options.fixedOffset.height() : base.options.fixedOffset;

                var offset = $this.offset();
                var scrollTop = base.$window.scrollTop() + newTopOffset;
                var scrollLeft = base.$window.scrollLeft();

                if ((scrollTop > offset.top) && (scrollTop < offset.top + $this.height() - base.$originalHeader.height())) {
                    var newLeft = offset.left - scrollLeft;
                    if (base.isSticky && (newLeft === base.leftOffset) && (newTopOffset === base.topOffset)) {
                        return;
                    }

                    base.leftOffset = newLeft;
                    base.topOffset = newTopOffset;

                    base.$fixedHeadersTable.css({
                        left: newLeft,
                        top: newTopOffset,
                        width: $this.width()
                    });

                    base.$fixedHeadersTable.html(base.$originalHeader.clone());

                    base.$fixedHeadersTable.find("th").css({
                        borderBottomWidth: "1px"
                    });

                    base.$fixedHeadersTable.show();

                    base.isSticky = true;
                } else if (base.isSticky) {
                    base.$fixedHeadersTable.hide();

                    base.isSticky = false;
                }
            });
        };

        base.updateOptions = function (options) {
            base.options = $.extend({}, defaults, options);
            base.updateWidth();
            base.toggleHeaders();
        };

        // Run initializer
        base.init();
    }

    // A plugin wrapper around the constructor,
    // preventing against multiple instantiations
    $.fn[name] = function (options) {
        return this.each(function () {
            var instance = $.data(this, 'plugin_' + name);
            if (instance) {
                if (typeof options === "string") {
                    instance[options].apply(instance);
                } else {
                    instance.updateOptions(options);
                }
            } else if (options !== 'destroy') {
                $.data(this, 'plugin_' + name, new Plugin(this, options));
            }
        });
    };

})(jQuery, window);

//Occurs when an booking is clicked. Gets an event that represents the booking as parameter.
innGrid.populateRoomEditModal = function ($el) {
    // a global cache to be used later in save room func
    selectedRoomDiv = $el;
    selectedRoomID = $el.data('resource-id');
    if (selectedRoomID === "unassigned") {
        alert(l("there's no more rooms"));
        return;
    }

    $.getJSON(getBaseURL() + 'room/get_room_AJAX/' + selectedRoomID, function (room) {

        $("#room-edit-modal").show();

        $("[name='room_edit_room_type_id'] option").each(function () {
            var option = $(this);
            if (room.room_type_id == option.val()) {
                option.prop("selected", true);
            }
        });

        $("[name='room_edit_room_status'] option").each(function () {
            var option = $(this);
            if (room.status == option.val()) {
                option.prop("selected", true);
            }
        });

        $("[name='room_edit_room_name']").val(room.room_name);
        $("#room-edit-modal").modal('show');
    });
};

innGrid.saveRoom = function (roomID, proceedToNextRoom) {
    $.ajax({
        type: "POST",
        url: getBaseURL() + 'room/update_room_AJAX',
        data: {
            room_id: selectedRoomID,
            room_name: $("[name='room_edit_room_name']").val(),
            room_type_id: $("[name='room_edit_room_type_id']").val(),
            status: $("[name='room_edit_room_status']").val()
        },
        dataType: "json",
        success: function (data) {
            for (key in innGrid.roomsResources) {
                if (innGrid.roomsResources[key]['id'] == selectedRoomID) {
                    innGrid.roomsResources[key]['status'] = $("[name='room_edit_room_status']").val();
                }
            }

            if (proceedToNextRoom) {
                var nextRoom = selectedRoomDiv.next('tr');
                innGrid.populateRoomEditModal(nextRoom);
            } else {
                $("#room-edit-modal").modal('hide');
            }
        }
    });
};

function addCalendarViewSwitcher() {
    var $toolbar = $('.fc-toolbar.fc-header-toolbar');
    if (!$toolbar.length || $toolbar.find('.booking-cal-view-switch').length) {
        return;
    }

    var isOverview = innGrid.isOverviewCalendar;
    var $switch = $('<div/>', { class: 'booking-cal-view-switch', role: 'group', 'aria-label': l('Booking calendar view', true) })
        .append(
            $('<a/>', {
                href: getBaseURL() + 'booking',
                class: 'booking-cal-view-switch__btn' + (isOverview ? '' : ' is-active'),
                title: l('Booking calendar view', true),
                html: '<i class="fa fa-calendar" aria-hidden="true"></i>'
            })
        )
        .append(
            $('<a/>', {
                href: getBaseURL() + 'booking/overview',
                class: 'booking-cal-view-switch__btn' + (isOverview ? ' is-active' : ''),
                title: l('Room Type availability view', true),
                html: '<i class="fa fa-th" aria-hidden="true"></i>'
            })
        );

    $toolbar.find('.fc-left').prepend($switch);
}

function addCalendarHeaderFilters() {
    var $toolbar = $('.fc-toolbar.fc-header-toolbar');
    if (!$toolbar.length) {
        return;
    }

    addCalendarViewSwitcher();

    if ($toolbar.find('#booking_search').length) {
        return;
    }

    $toolbar
        .find('.fc-right')
        .append(
            $('<div/>', { class: 'booking-calendar-toolbar-actions form-inline m-041' })
                .append(
                    $("<form/>",
                        {
                            id: 'booking_search',
                            method: 'GET',
                            class: "input-group booking-calendar-search",
                            action: getBaseURL() + "booking/show_bookings/"
                        }
                    )
                        .append("<input class='form-control' placeholder='" + l('search_bookings') + "' name='search_query' type='text' value=''>")
                        .append(
                            $("<span/>", {
                                class: "input-group-btn"
                            })
                                .append(
                                    $('<button/>', {class: 'btn btn-light', type: 'submit', 'aria-label': l('search_bookings', true)})
                                        .append(
                                            $("<i/>", {
                                                class: "fa fa-search"
                                            })
                                        )
                                )
                        )
                )
                .append(
                    $("<button/>", {
                        type: 'button',
                        href: '#',
                        class: 'btn btn-light filter-booking m-040',
                        text: l('More Filters', true)
                    }).on('click', function () {
                        var $panel = $('#filter-booking');
                        $panel.slideToggle(200, function () {
                            $panel.attr('aria-hidden', $panel.is(':visible') ? 'false' : 'true');
                        });
                    })
                )
        );

    $('.btn_calendar_view[rel="popover"]').popover({trigger: 'hover', container: 'body', html: true});
}

innGrid.cacheResourceAreaWidth = function (n) {
    localStorage.setItem('resourceAreaWidth', n);
}