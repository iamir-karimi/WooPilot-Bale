<?php

namespace WooPilot\Bale\WooCommerce;

use WooPilot\Bale\Messaging\NotificationManager;

defined( 'ABSPATH' ) || exit;

final class OrderHooks {

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
			return;
		}

		$this->notification_manager->send_admin_new_order( $order );
		$this->notification_manager->send_customer_order_confirmed( $order );

		$this->mark_as_sent( $order, '_woopilot_bale_admin_new_order_sent' );
	}

	public function handle_payment_complete( int $order_id ): void {
		$order = wc_get_order( $order_id );

		if ( ! $order instanceof \WC_Order ) {
			return;
		}

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

	private function handle_failed_payment_status( \WC_Order $order ): void {
		if ( $this->is_already_sent( $order, '_woopilot_bale_payment_failed_sent' ) ) {
			return;
		}

		$this->notification_manager->send_customer_payment_failed( $order );

		$this->mark_as_sent( $order, '_woopilot_bale_payment_failed_sent' );
	}

	private function is_already_sent( \WC_Order $order, string $meta_key ): bool {
		return 'yes' === $order->get_meta( $meta_key );
	}

	private function mark_as_sent( \WC_Order $order, string $meta_key ): void {
		$order->update_meta_data( $meta_key, 'yes' );
		$order->save();
	}
}