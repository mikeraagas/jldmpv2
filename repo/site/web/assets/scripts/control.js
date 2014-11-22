$(document).ready(function(){

	// ------------------- Event Scripts ------------------- //

	$('a.remove-event').click(function(){
		if (confirm('Are you sure?')) return true;
		return false;
	});

	// ------------------- Event Datepicker ------------------- //

	var input_startdate = $('input#start-date-display').val(),
		input_enddate   = $('input#end-date-display').val();

	var startDate = new Date(input_startdate),
		endDate   = new Date(input_enddate);
		
	$('#date-start').datetimepicker({
		pickDate : true,
		pickTime : true,
	});

	$('#date-start').on('dp.change', function(e) {
		var startDate = new Date(e.date._d),
			date      = moment(startDate).format('YYYY-MM-DD h:mm a');
        
        $('input#start-date-display').val(date);
		if (startDate.valueOf() > endDate.valueOf()){ invalidDates(); return false; }

		validDates();
		return;
	});

	$('#date-end').datetimepicker({
		pickDate : true,
		pickTime : true,
	});

	$('#date-end').on('dp.change', function(e){
        var endDate = new Date(e.date._d),
			date    = moment(endDate).format('YYYY-MM-DD h:mm a');

        $('input#end-date-display').val(date);
		if (endDate.valueOf() < startDate.valueOf()){ invalidDates(); return false; }

		validDates();
		return;
	});

	function validDates() {
		var icon = '<i class="fa fa-check"></i>&nbsp; ';

		$('div.event-date div.alert')
			.removeClass('hide alert-danger')
			.addClass('alert-success')
			.html(icon + 'Valid Dates');
	}

	function invalidDates() {
		var icon = '<i class="fa fa-times"></i>&nbsp; ';

		$('div.event-date div.alert')
			.removeClass('hide alert-success')
			.addClass('alert-danger')
			.html(icon + 'The start date must be before the end date.');
	}
});