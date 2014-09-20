$(document).ready(function() {
	
	/* ----------- FIXED HEADER ON SCROLL ----------- */

	$(window).scroll(function() {
		if ($(window).scrollTop() >= 45) {
			$('.bottom-header').addClass('fixed-header');
		} else {
			$('.bottom-header').removeClass('fixed-header');
		}
	});
});