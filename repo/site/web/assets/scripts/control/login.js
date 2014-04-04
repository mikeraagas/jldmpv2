$(document).ready(function(){
	$('form.login-form').submit(function(){
		var $this = $(this),
			username = $this.find('input.login-username').val(),
			password = $this.find('input.login-password').val();

		if (username == '' || password == '') {
			$this.find('div.ui.message').html('All fields are required');
			$this.find('div.ui.message').show();
			return false;
		};

		return true;
	});
});