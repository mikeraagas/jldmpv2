$(document).ready(function(){
	function validate(type) {
		var title 		   = $('input#ministry_title').val(),
			description    = $('textarea#ministry_description').val(),
			active 		   = $('input.ministry-active:checked').val(),
			name 		   = $('input#admin_name').val(),
			email 		   = $('input#admin_email').val(),
			username 	   = $('input#admin_username').val(),
			password 	   = $('input#admin_password').val(),
			confirm 	   = $('input#admin_confirm').val(),
			require_error  = 'All Fields are required!',
			confirm_error  = 'Password did not match!',
			username_error = 'Username must be 6 or more characters',
			password_error = 'Password must be 8 or more characters',
			validate 	   = true,
			fields 		   = [];

		$('span.js-error-msg').html('');

		$('.form-control').each(function(){
			var $this = $(this);

			if ($this.val() == '' || $this.val().length == 0) {
				$('span.js-error-msg').html(require_error);
				console.log($this.val());
				validate = false;
			};
		})

		if (validate == false) { return false; };

		// check if ministry active has value
		if (!active) {
			$('span.js-error-msg').html(require_error);
			validate = false;
		};

		// username must 6 or more characters
		if (username.length < 5) {
			$('span.js-error-msg').html(username_error);
			validate = false;
		};

		if (type == 'add') {
			// if password did not match
			if (password != confirm) {
				$('span.js-error-msg').html(confirm_error);
				validate = false;
			};

			// password must be 8 or more characters
			if (password.length < 8) {
				$('span.js-error-msg').html(password_error);
				validate = false;
			};
		};

		return validate;
	}

	$('form.add-ministry-form').submit(function(e){
		if (validate('add')) {
			return true;
		} else {
			return false;
		};
	});

	$('form.edit-ministry-form').submit(function(e){
		if (validate('edit')) {
			return true;
		} else {
			return false;
		};
	});

	// event datepicker 
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