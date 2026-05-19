(function ($) {
	'use strict';

	$(document).ready(function () {
		if ($.fn.wpColorPicker) {
			$('.woopilot-color-picker').wpColorPicker();
		}

		let mediaFrame = null;

		$(document).on('click', '.woopilot-upload-logo', function (e) {
			e.preventDefault();

			if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
				alert('کتابخانه رسانه وردپرس بارگذاری نشده است.');
				return;
			}

			mediaFrame = wp.media({
				title: 'انتخاب لوگوی صفحه ورود',
				button: {
					text: 'استفاده از این تصویر'
				},
				multiple: false
			});

			mediaFrame.on('select', function () {
				const attachment = mediaFrame
					.state()
					.get('selection')
					.first()
					.toJSON();

				if (!attachment || !attachment.id) {
					return;
				}

				const imageUrl = attachment.sizes && attachment.sizes.medium
					? attachment.sizes.medium.url
					: attachment.url;

				$('#woopilot_bale_auth_logo_id').val(attachment.id).trigger('change');

				$('.woopilot-auth-logo-preview').html(
					'<img src="' + imageUrl + '" alt="" style="max-width:160px;height:auto;display:block;margin-top:12px;">'
				);
			});

			mediaFrame.open();
		});

		$(document).on('click', '.woopilot-remove-logo', function (e) {
			e.preventDefault();

			$('#woopilot_bale_auth_logo_id').val('').trigger('change');

			$('.woopilot-auth-logo-preview').html(
				'<span>لوگویی انتخاب نشده است.</span>'
			);
		});

		$(document).on('input change', '[name^="woopilot_bale_auth_"]', function () {
			updatePreview();
		});

		function updatePreview() {
			const logoPreview = $('.woopilot-auth-logo-preview img').attr('src') || '';
			const title = $('[name="woopilot_bale_auth_title"]').val() || 'ورود / ثبت نام';
			const subtitle = $('[name="woopilot_bale_auth_subtitle"]').val() || 'برای ورود شماره موبایل خود را وارد کنید.';
			const primary = $('[name="woopilot_bale_auth_primary_color"]').val() || '#8bd957';
			const text = $('[name="woopilot_bale_auth_text_color"]').val() || '#111827';
			const card = $('[name="woopilot_bale_auth_card_color"]').val() || '#ffffff';
			const input = $('[name="woopilot_bale_auth_input_color"]').val() || '#f8fafc';
			const border = $('[name="woopilot_bale_auth_border_color"]').val() || '#eef0f4';
			const cardRadius = $('[name="woopilot_bale_auth_card_radius"]').val() || '24';
			const inputRadius = $('[name="woopilot_bale_auth_input_radius"]').val() || '12';
			const buttonRadius = $('[name="woopilot_bale_auth_button_radius"]').val() || '12';
			const cardWidth = $('[name="woopilot_bale_auth_card_width"]').val() || '430';
			const cardPadding = $('[name="woopilot_bale_auth_card_padding"]').val() || '36';

			const preview = $('.woopilot-auth-preview');

			if (!preview.length) {
				return;
			}

			preview.css({
				background: card,
				borderRadius: cardRadius + 'px',
				maxWidth: cardWidth + 'px',
				padding: cardPadding + 'px'
			});

			if (logoPreview) {
				preview.find('.woopilot-auth-preview-logo').html('<img src="' + logoPreview + '" alt="">');
			}

			preview.find('.woopilot-auth-preview-title')
				.text(title)
				.css('color', text);

			preview.find('.woopilot-auth-preview-subtitle')
				.text(subtitle);

			preview.find('.woopilot-auth-preview-input').css({
				background: input,
				borderColor: border,
				borderRadius: inputRadius + 'px'
			});

			preview.find('.woopilot-auth-preview-button').css({
				background: primary,
				borderRadius: buttonRadius + 'px'
			});
		}

		updatePreview();
	});
})(jQuery);