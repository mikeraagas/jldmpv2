$(function() {
	/* Loader
	-------------------------------*/
	var c = function() {
		if (!(this instanceof c)) {
			return new c();
		}

		return this.__construct.apply(this, arguments);
	}, public = c.prototype;

	/* Public Properties
	-------------------------------*/
	public.test = 'sdfs';

	/* Private Properties
	-------------------------------*/
	/* Construct
	-------------------------------*/
	public.__construct = function() {
		this.__listen();
	};

	/* Event Listener
	-------------------------------*/
	public.__listen = function() {
		$('.memberModal').bind('click', this.getMember);
	}

	/* Public Methods
	-------------------------------*/
	public.getMember = function() {
		var member = $(this).attr('data-id'),
			href   = window.location.origin + window.location.pathname;

		var data = {
			action : 'getMember',
			id     : member
		};

		var memberContainer = $('div.modal-body .body-content.member-info');
		memberContainer.html('');

		$.get(href, data, function(response) {
			var data = JSON.parse(response);

			if (!$.isEmptyObject(data)) {
				var tpl = $('#memberDetailTmp').html(),
					tpl = tpl.replace('[IMAGE]', data.member_image.file_name),
					tpl = tpl.replace('[NAME]', data.member_fullname),
					tpl = tpl.replace('[TITLE]', data.member_type),
					tpl = tpl.replace('[EMAIL]', data.member_email),
					tpl = tpl.replace('[PHONE]', data.member_phone),
					tpl = tpl.replace('[ADDRESS]', data.member_address);

				memberContainer.append(tpl);
				return;
			}

			memberContainer.append('<p>Unable to fetch member detail.</p>');
		});
	};

	/* Private Methods
	-------------------------------*/
	/* Adaptor
	-------------------------------*/
	var ministries = c();
});