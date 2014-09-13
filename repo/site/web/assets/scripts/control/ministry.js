$(document).ready(function(){

	// ------------------- Ministry Scripts ------------------- //

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
				validate = false;
			}
		});

		if (validate == false) { return false; }

		// check if ministry active has value
		if (!active) {
			$('span.js-error-msg').html(require_error);
			validate = false;
		}

		// username must 6 or more characters
		if (username.length < 5) {
			$('span.js-error-msg').html(username_error);
			validate = false;
		}

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
		}

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

	// remove ministry confirmation
	$('a.remove-ministry').click(function(){
		if (confirm('Are you sure?')) { return true; };
		return false;
	});

	// ------------------- Ministry Member Scripts ------------------- //

	$('a.remove-member').click(function(){
		if (confirm('Are you sure?')) { return true; };
		return false;
	});
});