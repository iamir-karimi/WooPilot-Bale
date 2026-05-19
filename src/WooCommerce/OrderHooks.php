<?php

namespace WooPilot\Bale\WooCommerce;

use WooPilot\Bale\Messaging\NotificationManager;

defined( 'ABSPATH' ) || exit;

final class OrderHooks {

	public const PAYMENT_REMINDER_HOOK = 'woopilot_bale_send_payment_reminder';

	private NotificationManager $notification_manager;

	public function __construct() {
		$this->notification_manager = new NotificationManager();
	}

	public function handle_new_order( int $order_id ): void {
	    error_log( 'WOOPILOT: handle_new_order fired. Order ID: ' . $order_id );
		$order = wc_get_order( $order_id );

		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		if ( $this->is_already_sent( $order, '_woopilot_bale_admin_new_order_sent' ) ) {
			$this->maybe_schedule_payment_reminder( $order );
			return;
		}

		$this->notification_manager->send_admin_new_order( $order );
		$this->notification_manager->send_customer_order_confirmed( $order );

		$this->mark_as_sent( $order, '_woopilot_bale_admin_new_order_sent' );

		$this->maybe_schedule_payment_reminder( $order );
	}

	public function handle_payment_complete( int $order_id ): void {
		$order = wc_get_order( $order_id );

		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$this->clear_payment_reminder( $order );

		if ( $this->is_already_sent( $order, '_woopilot_bale_payment_success_sent' ) ) {
			return;
		}

		$this->notification_manager->send_customer_payment_success( $order );

		$this->mark_as_sent( $order, '_woopilot_bale_payment_success_sent' );
	}

	public function handle_order_status_changed(
		int $order_id,
		string $old_status,
		string $new_status,
		\WC_Order $order
	): void {
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		if ( $old_status === $new_status ) {
			return;
		}

		if ( $order->is_paid() || in_array( $new_status, array( 'processing', 'completed', 'cancelled', 'refunded' ), true ) ) {
			$this->clear_payment_reminder( $order );
		}

		if ( in_array( $new_status, array( 'pending', 'on-hold', 'failed' ), true ) && ! $order->is_paid() ) {
			$this->maybe_schedule_payment_reminder( $order );
		}

		if ( 'failed' === $new_status ) {
			$this->handle_failed_payment_status( $order );
			return;
		}

		$meta_key = '_woopilot_bale_status_' . sanitize_key( $new_status ) . '_sent';

		if ( $this->is_already_sent( $order, $meta_key ) ) {
			return;
		}

		$this->notification_manager->send_customer_status_changed( $order, $new_status );

		$this->mark_as_sent( $order, $meta_key );
	}

	public function handle_checkout_order_processed( int $order_id, array $posted_data, \WC_Order $order ): void {
	error_log( 'WOOPILOT: checkout_order_processed fired. Order ID: ' . $order_id );

	if ( ! $order instanceof \WC_Order ) {
		$order = wc_get_order( $order_id );
	}

	if ( ! $order instanceof \WC_Order ) {
		error_log( 'WOOPILOT: order not found.' );
		return;
	}

	$this->handle_new_order( $order_id );
}

	public function handle_payment_reminder( int $order_id ): void {
		$order = wc_get_order( $order_id );

		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$order->delete_meta_data( '_woopilot_bale_payment_reminder_event' );
		$order->save();

		if ( $order->is_paid() ) {
			return;
		}

		if ( ! in_array( $order->get_status(), array( 'pending', 'on-hold', 'failed' ), true ) ) {
			return;
		}

		if ( $this->is_already_sent( $order, '_woopilot_bale_payment_reminder_sent' ) ) {
			return;
		}

		$this->notification_manager->send_customer_payment_reminder( $order );

		$this->mark_as_sent( $order, '_woopilot_bale_payment_reminder_sent' );
	}

	public function handle_order_stock_reduced( \WC_Order $order ): void {
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product ) {
				continue;
			}

			$product = $item->get_product();

			if ( $product instanceof \WC_Product ) {
				$this->maybe_send_low_stock_alert( $product );
			}
		}
	}

	public function handle_low_stock( $product ): void {
		if ( is_numeric( $product ) ) {
			$product = wc_get_product( absint( $product ) );
		}

		if ( $product instanceof \WC_Product ) {
			$this->send_low_stock_alert( $product, true );
		}
	}

	public function handle_no_stock( $product ): void {
		if ( is_numeric( $product ) ) {
			$product = wc_get_product( absint( $product ) );
		}

		if ( $product instanceof \WC_Product ) {
			$this->send_low_stock_alert( $product, true );
		}
	}

	public function handle_product_set_stock( \WC_Product $product ): void {
		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$this->maybe_send_low_stock_alert( $product );
	}

	private function handle_failed_payment_status( \WC_Order $order ): void {
		if ( $this->is_already_sent( $order, '_woopilot_bale_payment_failed_sent' ) ) {
			return;
		}

		$this->notification_manager->send_customer_payment_failed( $order );

		$this->mark_as_sent( $order, '_woopilot_bale_payment_failed_sent' );
	}

	private function maybe_schedule_payment_reminder( \WC_Order $order ): void {
		$minutes = absint( get_option( 'woopilot_bale_payment_reminder_minutes', 0 ) );

		if ( $minutes < 1 ) {
			return;
		}

		if ( $order->is_paid() ) {
			$this->clear_payment_reminder( $order );
			return;
		}

		if ( ! in_array( $order->get_status(), array( 'pending', 'on-hold', 'failed' ), true ) ) {
			return;
		}

		if ( $this->is_already_sent( $order, '_woopilot_bale_payment_reminder_sent' ) ) {
			return;
		}

		$existing_timestamp = absint( $order->get_meta( '_woopilot_bale_payment_reminder_event' ) );

		if ( $existing_timestamp > time() && wp_next_scheduled( self::PAYMENT_REMINDER_HOOK, array( $order->get_id() ) ) ) {
			return;
		}

		$this->clear_payment_reminder( $order );

		$timestamp = time() + ( $minutes * MINUTE_IN_SECONDS );

		wp_schedule_single_event(
			$timestamp,
			self::PAYMENT_REMINDER_HOOK,
			array( $order->get_id() )
		);

		$order->update_meta_data( '_woopilot_bale_payment_reminder_event', $timestamp );
		$order->save();

		error_log( 'WOOPILOT: payment reminder scheduled for order ' . $order->get_id() . ' at ' . gmdate( 'Y-m-d H:i:s', $timestamp ) );
	}

	private function clear_payment_reminder( \WC_Order $order ): void {
		$timestamp = wp_next_scheduled( self::PAYMENT_REMINDER_HOOK, array( $order->get_id() ) );

		while ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::PAYMENT_REMINDER_HOOK, array( $order->get_id() ) );
			$timestamp = wp_next_scheduled( self::PAYMENT_REMINDER_HOOK, array( $order->get_id() ) );
		}

		if ( $order->get_meta( '_woopilot_bale_payment_reminder_event' ) ) {
			$order->delete_meta_data( '_woopilot_bale_payment_reminder_event' );
			$order->save();
		}
	}

	private function maybe_send_low_stock_alert( \WC_Product $product ): void {
		$threshold = absint( get_option( 'woopilot_bale_low_stock_threshold', 0 ) );

		if ( $threshold < 1 ) {
			return;
		}

		if ( ! $product->managing_stock() ) {
			return;
		}

		$quantity = $product->get_stock_quantity();

		if ( null === $quantity ) {
			return;
		}

		if ( (int) $quantity <= $threshold ) {
			$this->send_low_stock_alert( $product );
		}
	}

	private function send_low_stock_alert( \WC_Product $product, bool $force = false ): void {
		if ( 'yes' !== get_option( 'woopilot_bale_enable_admin_notifications', 'yes' ) ) {
			return;
		}

		$quantity = $product->get_stock_quantity();
		$quantity_key = null === $quantity ? 'unknown' : (string) (int) $quantity;
		$transient_key = 'woopilot_bale_low_stock_' . $product->get_id() . '_' . md5( $quantity_key );

		if ( ! $force && get_transient( $transient_key ) ) {
			return;
		}

		$this->notification_manager->send_admin_low_stock( $product );

		set_transient( $transient_key, 'yes', 6 * HOUR_IN_SECONDS );
	}

	private function is_already_sent( \WC_Order $order, string $meta_key ): bool {
		return 'yes' === $order->get_meta( $meta_key );
	}

	private function mark_as_sent( \WC_Order $order, string $meta_key ): void {
		$order->update_meta_data( $meta_key, 'yes' );
		$order->save();
	}
}
