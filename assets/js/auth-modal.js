(function ($) {
	'use strict';

	const box = $('.woopilot-auth-card');

	if (!box.length || typeof WooPilotBaleAuth === 'undefined') {
		return;
	}

	let phone = '';
	let token = '';
	let checkTimer = null;
	let otpSending = false;
	let otpSent = false;
	let requestRunning = false;

	function setLoading(isLoading) {
		requestRunning = isLoading;
		box.toggleClass('is-loading', isLoading);
		box.find('button').prop('disabled', isLoading);
	}

	function clearLoading() {
		requestRunning = false;
		box.removeClass('is-loading');
		box.find('button').prop('disabled', false);
	}

	function showMessage(message, type = 'info') {
		const notice = box.find('.woopilot-auth-notice');

		notice
			.removeClass('is-error is-success is-info')
			.addClass('is-' + type)
			.text(message || '')
			.fadeIn(150);
	}

	function switchStep(step) {
		box.find('.woopilot-auth-step').removeClass('is-active');
		box.find('.woopilot-auth-step[data-step="' + step + '"]').addClass('is-active');
		clearLoading();
	}

	function stopPolling() {
		if (checkTimer) {
			clearInterval(checkTimer);
			checkTimer = null;
		}
	}

	function request(action, data, callback, errorCallback) {
		if (requestRunning && action !== 'woopilot_bale_auth_check') {
			return;
		}

		setLoading(true);

		$.post(WooPilotBaleAuth.ajaxUrl, Object.assign({
			action: action,
			nonce: WooPilotBaleAuth.nonce
		}, data || {}))
			.done(function (response) {
				clearLoading();

				if (response && response.success) {
					if (typeof callback === 'function') {
						callback(response.data || {});
					}
				} else {
					const message = response && response.data && response.data.message
						? response.data.message
						: WooPilotBaleAuth.i18n.error;

					showMessage(message, 'error');

					if (typeof errorCallback === 'function') {
						errorCallback(response ? response.data : {});
					}
				}
			})
			.fail(function () {
				clearLoading();
				showMessage(WooPilotBaleAuth.i18n.error, 'error');

				if (typeof errorCallback === 'function') {
					errorCallback({});
				}
			});
	}

	box.on('submit', '.woopilot-auth-phone-form', function (e) {
		e.preventDefault();

		if (requestRunning) {
			return;
		}

		stopPolling();
		otpSending = false;
		otpSent = false;

		phone = $.trim(box.find('[name="phone"]').val());

		request('woopilot_bale_auth_start', {
			phone: phone,
			username: $.trim(box.find('[name="username"]').val())
		}, function (data) {
			token = data.token || '';

			box.find('.woopilot-auth-command').text(data.command || '');

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
			stopPolling();

			setTimeout(function () {
				window.location.href = data.redirect || window.location.href;
			}, 700);
		});
	});

	box.on('copy cut paste', function () {
		clearLoading();
	});

	$(window).on('focus pageshow', function () {
		clearLoading();

		if (phone && !otpSent && !otpSending) {
			checkConnection(false);
		}
	});

	document.addEventListener('visibilitychange', function () {
		clearLoading();

		if (!document.hidden && phone && !otpSent && !otpSending) {
			checkConnection(false);
		}
	});

	function startConnectionPolling() {
		stopPolling();

		checkTimer = setInterval(function () {
			if (!otpSent && !otpSending) {
				checkConnection(false);
			}
		}, 5000);
	}

	function checkConnection(manual) {
		if (!phone || otpSent || otpSending) {
			return;
		}

		request('woopilot_bale_auth_check', {
			phone: phone
		}, function (data) {
			if (data.connected) {
				stopPolling();
				showMessage(data.message, 'success');

				setTimeout(function () {
					sendOtp();
				}, 300);
			} else if (manual) {
				showMessage(data.message, 'info');
			}
		});
	}

	function sendOtp() {
		if (!phone || otpSending || otpSent) {
			return;
		}

		otpSending = true;
		clearLoading();

		request('woopilot_bale_auth_send_otp', {
			phone: phone
		}, function (data) {
			otpSent = true;
			otpSending = false;

			stopPolling();
			showMessage(data.message, 'success');
			switchStep('otp');
		}, function () {
			otpSending = false;
			otpSent = false;
		});
	}
})(jQuery);