(function($){

	'use strict';

	$(document).ready(function(){

		$('.woopilot-color-picker').wpColorPicker();

		let mediaFrame;

		$('.woopilot-upload-logo').on('click', function(e){

			e.preventDefault();

			if(mediaFrame){
				mediaFrame.open();
				return;
			}

			mediaFrame = wp.media({
				title: 'انتخاب لوگو',
				button: {
					text: 'استفاده از تصویر'
				},
				multiple: false
			});

			mediaFrame.on('select', function(){

				const attachment = mediaFrame
					.state()
					.get('selection')
					.first()
					.toJSON();

				$('#woopilot_bale_auth_logo_id')
					.val(attachment.id);

				$('.woopilot-auth-logo-preview')
					html('<img src="'+attachment.url+'" />');
			});

			mediaFrame.open();
		});

	});

})(jQuery);