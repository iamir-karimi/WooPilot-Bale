<?php

namespace WooPilot\Bale\Reports;

defined( 'ABSPATH' ) || exit;

final class SalesReportService {

	public function get_report( array $args = array() ): array {
		$defaults = array(
			'period'     => 'today',
			'date_from'  => '',
			'date_to'    => '',
			'sort_by'    => 'date',
			'sort_order' => 'DESC',
		);

		$args = $this->normalize_args( wp_parse_args( $args, $defaults ) );

		$orders = wc_get_orders(
			array(
				'type'         => 'shop_order',
				'status'       => array_keys( wc_get_order_statuses() ),
				'limit'        => -1,
				'return'       => 'objects',
				'date_created' => $args['date_from'] . '...' . $args['date_to'],
			)
		);

		$total_orders       = 0;
		$completed_orders   = 0;
		$incomplete_orders  = 0;
		$total_sales        = 0;
		$completed_sales    = 0;
		$items_sold         = 0;
		$chart              = array();
		$order_rows         = array();

		foreach ( $orders as $order ) {
			if ( ! $order instanceof \WC_Order ) {
				continue;
			}

			$total_orders++;
			$status = $order->get_status();
			$total  = (float) $order->get_total();

			$total_sales += $total;

			if ( 'completed' === $status ) {
				$completed_orders++;
				$completed_sales += $total;
			} else {
				$incomplete_orders++;
			}

			$order_items_count = 0;

			foreach ( $order->get_items() as $item ) {
				if ( $item instanceof \WC_Order_Item_Product ) {
					$quantity           = absint( $item->get_quantity() );
					$items_sold        += $quantity;
					$order_items_count += $quantity;
				}
			}

			$date_key = $order->get_date_created()
				? $order->get_date_created()->date_i18n( 'Y-m-d' )
				: current_time( 'Y-m-d' );

			if ( ! isset( $chart[ $date_key ] ) ) {
				$chart[ $date_key ] = array(
					'date'              => $date_key,
					'orders_count'      => 0,
					'completed_count'   => 0,
					'incomplete_count'  => 0,
					'total_sales'       => 0,
					'completed_sales'   => 0,
					'items_sold'        => 0,
				);
			}

			$chart[ $date_key ]['orders_count']++;
			$chart[ $date_key ]['total_sales'] += $total;
			$chart[ $date_key ]['items_sold']  += $order_items_count;

			if ( 'completed' === $status ) {
				$chart[ $date_key ]['completed_count']++;
				$chart[ $date_key ]['completed_sales'] += $total;
			} else {
				$chart[ $date_key ]['incomplete_count']++;
			}

			$order_rows[] = array(
				'id'         => $order->get_id(),
				'number'     => $order->get_order_number(),
				'date'       => $date_key,
				'customer'   => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
				'total'      => $total,
				'total_html' => wp_strip_all_tags( wc_price( $total, array( 'currency' => $order->get_currency() ) ) ),
				'items_sold' => $order_items_count,
				'status'     => wc_get_order_status_name( $status ),
				'edit_url'   => $order->get_edit_order_url(),
			);
		}

		$chart = array_values( $chart );

		usort(
			$chart,
			static fn( array $a, array $b ): int => strcmp( $a['date'], $b['date'] )
		);

		return array(
			'total_orders'          => $total_orders,
			'completed_orders'      => $completed_orders,
			'incomplete_orders'     => $incomplete_orders,
			'total_sales'           => $total_sales,
			'total_sales_html'      => wp_strip_all_tags( wc_price( $total_sales ) ),
			'completed_sales'       => $completed_sales,
			'completed_sales_html'  => wp_strip_all_tags( wc_price( $completed_sales ) ),
			'items_sold'            => $items_sold,
			'date_from'             => $args['date_from'],
			'date_to'               => $args['date_to'],
			'chart'                 => $chart,
			'orders'                => $this->sort_orders( $order_rows, $args['sort_by'], $args['sort_order'] ),
		);
	}

	public function format_report_message( array $report, string $title = 'گزارش فروش' ): string {
		return sprintf(
			"%s\n\nبازه: %s تا %s\n\nتعداد کل سفارش‌ها: %d\nسفارش‌های تکمیل‌شده: %d\nسفارش‌های تکمیل‌نشده: %d\nتعداد محصولات فروخته‌شده: %d\nمبلغ کل سفارش‌ها: %s\nمبلغ سفارش‌های تکمیل‌شده: %s",
			$title,
			$report['date_from'],
			$report['date_to'],
			absint( $report['total_orders'] ),
			absint( $report['completed_orders'] ),
			absint( $report['incomplete_orders'] ),
			absint( $report['items_sold'] ),
			(string) $report['total_sales_html'],
			(string) $report['completed_sales_html']
		);
	}

	private function normalize_args( array $args ): array {
		$period = sanitize_key( $args['period'] );
		$now    = current_time( 'timestamp' );

		if ( 'custom' === $period ) {
			$date_from = $this->normalize_input_date( (string) $args['date_from'], 'from' );
			$date_to   = $this->normalize_input_date( (string) $args['date_to'], 'to' );
		} else {
			switch ( $period ) {
				case 'yesterday':
					$date_from = date_i18n( 'Y-m-d 00:00:00', strtotime( '-1 day', $now ) );
					$date_to   = date_i18n( 'Y-m-d 23:59:59', strtotime( '-1 day', $now ) );
					break;

				case 'week':
					$date_from = date_i18n( 'Y-m-d 00:00:00', strtotime( '-7 days', $now ) );
					$date_to   = date_i18n( 'Y-m-d 23:59:59', $now );
					break;

				case 'month':
					$date_from = date_i18n( 'Y-m-01 00:00:00', $now );
					$date_to   = date_i18n( 'Y-m-d 23:59:59', $now );
					break;

				case 'today':
				default:
					$date_from = date_i18n( 'Y-m-d 00:00:00', $now );
					$date_to   = date_i18n( 'Y-m-d 23:59:59', $now );
					break;
			}
		}

		return array(
			'period'     => $period,
			'date_from'  => $date_from,
			'date_to'    => $date_to,
			'sort_by'    => sanitize_key( $args['sort_by'] ),
			'sort_order' => 'ASC' === strtoupper( (string) $args['sort_order'] ) ? 'ASC' : 'DESC',
		);
	}

	private function normalize_input_date( string $date, string $mode ): string {
		$date = trim( $date );

		if ( preg_match( '/^1[34][0-9]{2}\/[0-9]{1,2}\/[0-9]{1,2}$/', $date ) ) {
			$parts = array_map( 'absint', explode( '/', $date ) );
			$g     = $this->jalali_to_gregorian( $parts[0], $parts[1], $parts[2] );
			$date  = sprintf( '%04d-%02d-%02d', $g[0], $g[1], $g[2] );
		}

		if ( ! preg_match( '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $date ) ) {
			$date = current_time( 'Y-m-d' );
		}

		return 'from' === $mode ? $date . ' 00:00:00' : $date . ' 23:59:59';
	}

	private function jalali_to_gregorian( int $jy, int $jm, int $jd ): array {
		$jy += 1595;
		$days = -355668 + ( 365 * $jy ) + ( (int) ( $jy / 33 ) * 8 ) + (int) ( ( ( $jy % 33 ) + 3 ) / 4 ) + $jd;

		$days += ( $jm < 7 )
			? ( $jm - 1 ) * 31
			: ( ( $jm - 7 ) * 30 ) + 186;

		$gy = 400 * (int) ( $days / 146097 );
		$days %= 146097;

		if ( $days > 36524 ) {
			$gy += 100 * (int) ( --$days / 36524 );
			$days %= 36524;

			if ( $days >= 365 ) {
				$days++;
			}
		}

		$gy += 4 * (int) ( $days / 1461 );
		$days %= 1461;

		if ( $days > 365 ) {
			$gy += (int) ( ( $days - 1 ) / 365 );
			$days = ( $days - 1 ) % 365;
		}

		$gd = $days + 1;
		$sal_a = array( 0, 31, ( ( $gy % 4 === 0 && $gy % 100 !== 0 ) || ( $gy % 400 === 0 ) ) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 );

		for ( $gm = 1; $gm <= 12 && $gd > $sal_a[ $gm ]; $gm++ ) {
			$gd -= $sal_a[ $gm ];
		}

		return array( $gy, $gm, $gd );
	}

	private function sort_orders( array $orders, string $sort_by, string $sort_order ): array {
		usort(
			$orders,
			static function ( array $a, array $b ) use ( $sort_by, $sort_order ): int {
				$value_a = $a[ $sort_by ] ?? $a['date'];
				$value_b = $b[ $sort_by ] ?? $b['date'];

				$result = is_numeric( $value_a ) && is_numeric( $value_b )
					? $value_a <=> $value_b
					: strcmp( (string) $value_a, (string) $value_b );

				return 'ASC' === $sort_order ? $result : -$result;
			}
		);

		return $orders;
	}
}