<?php

namespace WooPilot\Bale\Reports;

defined( 'ABSPATH' ) || exit;

final class SalesReportAjax {

	private SalesReportService $service;

	public function __construct() {
		$this->service = new SalesReportService();
	}

	public function handle(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'شما اجازه دسترسی به گزارش فروش را ندارید.', 'woopilot-bale' ),
				),
				403
			);
		}

		check_ajax_referer( 'woopilot_bale_sales_report', 'nonce' );

		if ( ! function_exists( 'wc_get_orders' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'ووکامرس فعال نیست یا در دسترس نیست.', 'woopilot-bale' ),
				),
				400
			);
		}

		$args = array(
			'period'     => isset( $_POST['period'] ) ? sanitize_key( wp_unslash( $_POST['period'] ) ) : 'today',
			'date_from'  => isset( $_POST['date_from'] ) ? sanitize_text_field( wp_unslash( $_POST['date_from'] ) ) : '',
			'date_to'    => isset( $_POST['date_to'] ) ? sanitize_text_field( wp_unslash( $_POST['date_to'] ) ) : '',
			'sort_by'    => isset( $_POST['sort_by'] ) ? sanitize_key( wp_unslash( $_POST['sort_by'] ) ) : 'date',
			'sort_order' => isset( $_POST['sort_order'] ) ? sanitize_key( wp_unslash( $_POST['sort_order'] ) ) : 'DESC',
		);

		try {
			wp_send_json_success( $this->service->get_report( $args ) );
		} catch ( \Throwable $e ) {
			wp_send_json_error(
				array(
					'message' => defined( 'WP_DEBUG' ) && WP_DEBUG
						? $e->getMessage()
						: __( 'خطا در تولید گزارش فروش.', 'woopilot-bale' ),
				),
				500
			);
		}
	}
}