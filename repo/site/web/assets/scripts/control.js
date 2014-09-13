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
		
	$('a#date-start').click(function(e){ e.preventDefault(); });
	$('a#date-start')
		.datepicker({
			orientation: 'auto'
		})
		.on('changeDate', function(ev){
            startDate 		= new Date(ev.date);
            startDate_dd 	= ("0" + startDate.getDate()).slice(-2);
			startDate_mm 	= ('0' + (startDate.getMonth() + 1)).slice(-2); //January is 0!
			startDate_yyyy 	= startDate.getFullYear();
            $('input#start-date-display').val(startDate_yyyy + '-' + startDate_mm + '-' + startDate_dd);
			
			if (ev.date.valueOf() > endDate.valueOf()){
	            $('div.event-date div.alert').removeClass('hide').text('The start date must be before the end date.');
	        } else {
	            $('div.event-date div.alert').addClass('hide');
	        }

	        $('a#date-start').datepicker('hide');
	});

	$('a#date-end').click(function(e){ e.preventDefault(); });
	$('a#date-end')
		.datepicker({
			orientation: 'auto'
		})
		.on('changeDate', function(ev){
            endDate 	 = new Date(ev.date);            
            endDate_dd 	 = ('0' + endDate.getDate()).slice(-2);
			endDate_mm 	 = ('0' + (endDate.getMonth() + 1)).slice(-2); //January is 0!
			endDate_yyyy = endDate.getFullYear();
            $('input#end-date-display').val(endDate_yyyy + '-' + endDate_mm + '-' + endDate_dd);
			
			if (ev.date.valueOf() < startDate.valueOf()){
	            $('div.event-date div.alert').removeClass('hide').text('The end date must be after the start date.');
	        } else {
	            $('div.event-date div.alert').addClass('hide');
	        }

	        $('a#date-end').datepicker('hide');
	});
});