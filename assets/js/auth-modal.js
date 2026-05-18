(function ($) {
	'use strict';

	const box = $('.woopilot-auth-card');

	if (!box.length || typeof WooPilotBaleAuth === 'undefined') {
		return;
	}

	let phone = '';
	let token = '';
	let checkTimer = null;

	function setLoading(isLoading) {
		box.toggleClass('is-loading', isLoading);
		box.find('button').prop('disabled', isLoading);
	}

	function showMessage(message, type = 'info') {
		const notice = box.find('.woopilot-auth-notice');
		notice.removeClass('is-error is-success is-info');
		notice.addClass('is-' + type);
		notice.text(message).fadeIn(150);
	}

	function switchStep(step) {
		box.find('.woopilot-auth-step').removeClass('is-active');
		box.find('.woopilot-auth-step[data-step="' + step + '"]').addClass('is-active');
	}

	function request(action, data, callback) {
		setLoading(true);

		$.post(WooPilotBaleAuth.ajaxUrl, Object.assign({
			action: action,
			nonce: WooPilotBaleAuth.nonce
		}, data))
			.done(function (response) {
				if (response && response.success) {
					callback(response.data || {});
				} else {
					showMessage(response.data && response.data.message ? response.data.message : WooPilotBaleAuth.i18n.error, 'error');
				}
			})
			.fail(function () {
				showMessage(WooPilotBaleAuth.i18n.error, 'error');
			})
			.always(function () {
				setLoading(false);
			});
	}

	box.on('submit', '.woopilot-auth-phone-form', function (e) {
		e.preventDefault();

		phone = $.trim(box.find('[name="phone"]').val());

		request('woopilot_bale_auth_start', {
			phone: phone,
			username: $.trim(box.find('[name="username"]').val())
		}, function (data) {
			token = data.token;

			box.find('.woopilot-auth-command').text(data.command);

			if (data.bot_url) {
				box.find('.woopilot-auth-bot-link').attr('href', data.bot_url).show();
			} else {
				box.find('.woopilot-auth-bot-link').hide();
			}

			showMessage(data.message, 'success');
			switchStep('connect');
			startConnectionPolling();
		});
	});

	box.on('click', '.woopilot-auth-check-connection', function () {
		checkConnection(true);
	});

	box.on('click', '.woopilot-auth-send-otp', function () {
		sendOtp();
	});

	box.on('submit', '.woopilot-auth-otp-form', function (e) {
		e.preventDefault();

		request('woopilot_bale_auth_verify', {
			phone: phone,
			otp: $.trim(box.find('[name="otp"]').val())
		}, function (data) {
			showMessage(data.message, 'success');

			setTimeout(function () {
				window.location.href = data.redirect || window.location.href;
			}, 700);
		});
	});

	function startConnectionPolling() {
		if (checkTimer) {
			clearInterval(checkTimer);
		}

		checkTimer = setInterval(function () {
			checkConnection(false);
		}, 4000);
	}

	function checkConnection(manual) {
		request('woopilot_bale_auth_check', {
			phone: phone
		}, function (data) {
			if (data.connected) {
				clearInterval(checkTimer);
				showMessage(data.message, 'success');
				sendOtp();
			} else if (manual) {
				showMessage(data.message, 'info');
			}
		});
	}

	function sendOtp() {
		request('woopilot_bale_auth_send_otp', {
			phone: phone
		}, function (data) {
			showMessage(data.message, 'success');
			switchStep('otp');
		});
	}

})(jQuery);