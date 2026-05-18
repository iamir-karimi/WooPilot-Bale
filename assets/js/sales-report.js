(function ($) {
	'use strict';

	let salesChart = null;

	function i18n(key, fallback) {
		if (
			window.WooPilotSalesReport &&
			WooPilotSalesReport.i18n &&
			WooPilotSalesReport.i18n[key]
		) {
			return WooPilotSalesReport.i18n[key];
		}

		return fallback;
	}

	function escapeHtml(value) {
		return String(value === null || value === undefined ? '' : value)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	function formatNumber(value) {
		value = Number(value) || 0;

		try {
			return value.toLocaleString('fa-IR');
		} catch (e) {
			return String(value);
		}
	}

	function initDatepicker(retryCount) {
		retryCount = retryCount || 0;

		const $fields = $('.woopilot-persian-datepicker');

		if (!$fields.length) {
			return;
		}

		if (typeof $.fn.persianDatepicker === 'undefined') {
			if (retryCount < 10) {
				setTimeout(function () {
					initDatepicker(retryCount + 1);
				}, 300);
			}

			if (window.console) {
				console.warn('WooPilot Bale: persianDatepicker is not loaded.');
			}

			return;
		}

		$fields.each(function () {
			const $field = $(this);

			if ($field.data('woopilot-datepicker-ready')) {
				return;
			}

			$field.persianDatepicker({
				format: 'YYYY/MM/DD',
				initialValue: false,
				autoClose: true,
				calendarType: 'persian',
				observer: true,
				calendar: {
					persian: {
						locale: 'fa',
						showHint: true
					}
				},
				toolbox: {
					calendarSwitch: {
						enabled: false
					},
					todayButton: {
						enabled: true,
						text: {
							fa: 'امروز'
						}
					},
					submitButton: {
						enabled: true,
						text: {
							fa: 'تایید'
						}
					}
				}
			});

			$field.data('woopilot-datepicker-ready', true);
		});
	}

	function getReportPayload() {
		return {
			action: 'woopilot_bale_get_sales_report',
			nonce: WooPilotSalesReport.nonce,
			period: $('#woopilot_report_period').val() || 'today',
			date_from: $('#woopilot_report_date_from').val() || '',
			date_to: $('#woopilot_report_date_to').val() || '',
			sort_by: $('#woopilot_report_sort_by').val() || 'date',
			sort_order: $('#woopilot_report_sort_order').val() || 'DESC'
		};
	}

	function normalizeChartPoints(points) {
		if (!Array.isArray(points)) {
			return [];
		}

		return points.map(function (item) {
			return {
				date: item.date || '',
				total_sales: Number(item.total_sales) || 0,
				completed_sales: Number(item.completed_sales) || 0,
				orders_count: parseInt(item.orders_count, 10) || 0,
				completed_count: parseInt(item.completed_count, 10) || 0,
				incomplete_count: parseInt(item.incomplete_count, 10) || 0,
				items_sold: parseInt(item.items_sold, 10) || 0
			};
		});
	}

	function drawChart(points) {
		const canvas = document.getElementById('woopilot-sales-chart');

		if (!canvas) {
			return;
		}

		if (typeof Chart === 'undefined') {
			$('.woopilot-sales-chart-wrap').append(
				'<p class="woopilot-chart-error">' +
				escapeHtml(i18n('chart_missing', 'کتابخانه نمودار بارگذاری نشده است.')) +
				'</p>'
			);
			return;
		}

		const normalizedPoints = normalizeChartPoints(points);

		const labels = normalizedPoints.map(function (item) {
			return item.date;
		});

		const totalSales = normalizedPoints.map(function (item) {
			return item.total_sales;
		});

		const totalOrders = normalizedPoints.map(function (item) {
			return item.orders_count;
		});

		const completedOrders = normalizedPoints.map(function (item) {
			return item.completed_count;
		});

		const incompleteOrders = normalizedPoints.map(function (item) {
			return item.incomplete_count;
		});

		if (salesChart) {
			salesChart.destroy();
			salesChart = null;
		}

		salesChart = new Chart(canvas.getContext('2d'), {
			type: 'bar',
			data: {
				labels: labels,
				datasets: [
					{
						type: 'bar',
						label: 'مبلغ فروش',
						data: totalSales,
						yAxisID: 'salesAxis',
						borderWidth: 1
					},
					{
						type: 'line',
						label: 'کل سفارش‌ها',
						data: totalOrders,
						yAxisID: 'ordersAxis',
						tension: 0.35,
						borderWidth: 2,
						pointRadius: 3
					},
					{
						type: 'line',
						label: 'سفارش‌های تکمیل‌شده',
						data: completedOrders,
						yAxisID: 'ordersAxis',
						tension: 0.35,
						borderWidth: 2,
						pointRadius: 3
					},
					{
						type: 'line',
						label: 'سفارش‌های تکمیل‌نشده',
						data: incompleteOrders,
						yAxisID: 'ordersAxis',
						tension: 0.35,
						borderWidth: 2,
						pointRadius: 3
					}
				]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				interaction: {
					mode: 'index',
					intersect: false
				},
				plugins: {
					legend: {
						position: 'bottom',
						rtl: true,
						labels: {
							usePointStyle: true,
							font: {
								family: 'Tahoma, Arial, sans-serif'
							}
						}
					},
					tooltip: {
						rtl: true,
						bodyFont: {
							family: 'Tahoma, Arial, sans-serif'
						},
						titleFont: {
							family: 'Tahoma, Arial, sans-serif'
						},
						callbacks: {
							label: function (context) {
								const label = context.dataset.label || '';
								const value = context.raw || 0;

								return label + ': ' + formatNumber(value);
							}
						}
					}
				},
				scales: {
					x: {
						ticks: {
							font: {
								family: 'Tahoma, Arial, sans-serif'
							}
						}
					},
					salesAxis: {
						type: 'linear',
						position: 'right',
						beginAtZero: true,
						ticks: {
							callback: function (value) {
								return formatNumber(value);
							},
							font: {
								family: 'Tahoma, Arial, sans-serif'
							}
						},
						title: {
							display: true,
							text: 'مبلغ فروش',
							font: {
								family: 'Tahoma, Arial, sans-serif'
							}
						}
					},
					ordersAxis: {
						type: 'linear',
						position: 'left',
						beginAtZero: true,
						grid: {
							drawOnChartArea: false
						},
						ticks: {
							precision: 0,
							font: {
								family: 'Tahoma, Arial, sans-serif'
							}
						},
						title: {
							display: true,
							text: 'تعداد سفارش',
							font: {
								family: 'Tahoma, Arial, sans-serif'
							}
						}
					}
				}
			}
		});
	}

	function renderSummary(data) {
		$('#woopilot-report-total-orders').text(formatNumber(data.total_orders));
		$('#woopilot-report-completed-orders').text(formatNumber(data.completed_orders));
		$('#woopilot-report-incomplete-orders').text(formatNumber(data.incomplete_orders));
		$('#woopilot-report-total-sales').text(data.total_sales_html || '0');
		$('#woopilot-report-completed-sales').text(data.completed_sales_html || '0');
		$('#woopilot-report-items-sold').text(formatNumber(data.items_sold));
		$('#woopilot-report-date-range').text((data.date_from || '-') + ' تا ' + (data.date_to || '-'));
	}

	function renderOrdersTable(orders) {
		const $body = $('#woopilot-sales-report-table-body');

		$body.empty();

		if (!Array.isArray(orders) || !orders.length) {
			$body.append(
				'<tr><td colspan="6">' +
				escapeHtml(i18n('empty_orders', 'سفارشی در این بازه یافت نشد.')) +
				'</td></tr>'
			);
			return;
		}

		orders.forEach(function (order) {
			const editUrl = order.edit_url ? escapeHtml(order.edit_url) : '#';
			const number = escapeHtml(order.number || order.id || '-');

			$body.append(
				'<tr>' +
					'<td><a href="' + editUrl + '">#' + number + '</a></td>' +
					'<td>' + escapeHtml(order.date || '-') + '</td>' +
					'<td>' + escapeHtml(order.customer || '-') + '</td>' +
					'<td>' + escapeHtml(formatNumber(order.items_sold || 0)) + '</td>' +
					'<td>' + escapeHtml(order.total_html || '0') + '</td>' +
					'<td>' + escapeHtml(order.status || '-') + '</td>' +
				'</tr>'
			);
		});
	}

	function showError(message) {
		const text = message || i18n('ajax_error', 'خطا در دریافت گزارش فروش.');

		$('#woopilot-sales-report-table-body').html(
			'<tr><td colspan="6">' + escapeHtml(text) + '</td></tr>'
		);
	}

	function loadReport() {
		if (!$('.woopilot-sales-report').length || typeof WooPilotSalesReport === 'undefined') {
			return;
		}

		$('#woopilot-sales-report-loading').show();

		$.ajax({
			url: WooPilotSalesReport.ajax_url,
			type: 'POST',
			dataType: 'json',
			data: getReportPayload()
		})
			.done(function (response) {
				if (!response || !response.success || !response.data) {
					showError(response && response.data && response.data.message ? response.data.message : null);
					return;
				}

				renderSummary(response.data);
				drawChart(response.data.chart || []);
				renderOrdersTable(response.data.orders || []);
			})
			.fail(function (xhr) {
				let message = null;

				if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
					message = xhr.responseJSON.data.message;
				}

				showError(message);
			})
			.always(function () {
				$('#woopilot-sales-report-loading').hide();
			});
	}

	$(document).on('click', '#woopilot-load-sales-report', function (e) {
		e.preventDefault();
		loadReport();
	});

	$(document).on('change', '#woopilot_report_period', function () {
		const isCustom = $(this).val() === 'custom';

		$('.woopilot-custom-date-fields').toggle(isCustom);

		if (isCustom) {
			initDatepicker();
		}
	});

	$(function () {
		if (!$('.woopilot-sales-report').length) {
			return;
		}

		initDatepicker(0);
		$('#woopilot_report_period').trigger('change');
		loadReport();
	});
})(jQuery);