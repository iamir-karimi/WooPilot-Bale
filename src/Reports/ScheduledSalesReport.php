<?php

namespace WooPilot\Bale\Reports;

use WooPilot\Bale\Api\BaleApi;

defined( 'ABSPATH' ) || exit;

final class ScheduledSalesReport {

	public const HOOK = 'woopilot_bale_send_scheduled_sales_report';

	public function maybe_schedule(): void {
		if ( 'yes' !== get_option( 'woopilot_bale_enable_sales_report_notification', 'no' ) ) {
			wp_clear_scheduled_hook( self::HOOK );
			return;
		}

		if ( wp_next_scheduled( self::HOOK ) ) {
			return;
		}

		wp_schedule_event(
			$this->get_next_timestamp(),
			'daily',
			self::HOOK
		);
	}

	public function send(): void {
		if ( 'yes' !== get_option( 'woopilot_bale_enable_sales_report_notification', 'no' ) ) {
			return;
		}

		if ( ! function_exists( 'wc_get_orders' ) ) {
			return;
		}

		$period = sanitize_key(
			(string) get_option( 'woopilot_bale_sales_report_period', 'today' )
		);

		$service = new SalesReportService();

		$report = $service->get_report(
			array(
				'period'     => $period,
				'sort_by'    => 'date',
				'sort_order' => 'DESC',
			)
		);

		$message = $service->format_report_message(
			$report,
			$this->get_report_title( $period )
		);

		$api        = new BaleApi( get_option( 'woopilot_bale_bot_token', '' ) );
		$recipients = $this->get_admin_recipients();

		foreach ( $recipients as $recipient ) {
			$api->send_message( $recipient, $message );
		}
	}

	private function get_report_title( string $period ): string {
		switch ( $period ) {
			case 'week':
				return '📊 گزارش فروش هفتگی ووکامرس';

			case 'month':
				return '📊 گزارش فروش ماهانه ووکامرس';

			case 'yesterday':
				return '📊 گزارش فروش دیروز ووکامرس';

			case 'today':
			default:
				return '📊 گزارش فروش روزانه ووکامرس';
		}
	}

	private function get_next_timestamp(): int {
		$time = (string) get_option( 'woopilot_bale_sales_report_send_time', '23:00' );

		if ( ! preg_match( '/^[0-2][0-9]:[0-5][0-9]$/', $time ) ) {
			$time = '23:00';
		}

		$timezone = wp_timezone();
		$now      = new \DateTimeImmutable( 'now', $timezone );

		$target = \DateTimeImmutable::createFromFormat(
			'Y-m-d H:i',
			$now->format( 'Y-m-d' ) . ' ' . $time,
			$timezone
		);

		if ( ! $target ) {
			return time() + HOUR_IN_SECONDS;
		}

		if ( $target <= $now ) {
			$target = $target->modify( '+1 day' );
		}

		return $target->getTimestamp();
	}

	private function get_admin_recipients(): array {
		$admin_ids = get_option( 'woopilot_bale_admin_ids', '' );
		$group_id  = get_option( 'woopilot_bale_group_id', '' );

		$recipients = array();

		if ( ! empty( $admin_ids ) ) {
			$recipients = array_merge(
				$recipients,
				array_map( 'trim', explode( ',', (string) $admin_ids ) )
			);
		}

		if ( ! empty( $group_id ) ) {
			$recipients[] = trim( (string) $group_id );
		}

		return array_values( array_unique( array_filter( $recipients ) ) );
	}
}