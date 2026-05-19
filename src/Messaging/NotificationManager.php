<?php

namespace WooPilot\Bale\Messaging;

use WooPilot\Bale\Api\BaleApi;
use WooPilot\Bale\WooCommerce\OrderMeta;

defined( 'ABSPATH' ) || exit;

final class NotificationManager {

	private BaleApi $api;

	private MessageBuilder $message_builder;

	public function __construct() {
		$this->api             = new BaleApi( get_option( 'woopilot_bale_bot_token', '' ) );
		$this->message_builder = new MessageBuilder();
	}

	public function send_admin_new_order( \WC_Order $order ): void {
		error_log( 'WOOPILOT: send_admin_new_order fired for order ' . $order->get_id() );

		if ( 'yes' !== get_option( 'woopilot_bale_enable_admin_notifications', 'yes' ) ) {
			error_log( 'WOOPILOT: admin notifications disabled.' );
			return;
		}

		$template = $this->get_template( 'woopilot_bale_template_admin_new_order' );

		if ( empty( $template ) ) {
			error_log( 'WOOPILOT: admin new order template is empty.' );
			return;
		}

		$message = $this->message_builder->build_order_message( $template, $order );

		$this->send_to_admins( $message );
	}

	public function send_customer_order_confirmed( \WC_Order $order ): void {
		if ( 'yes' !== get_option( 'woopilot_bale_enable_customer_notifications', 'yes' ) ) {
			error_log( 'WOOPILOT: customer notifications disabled.' );
			return;
		}

		$bale_id = OrderMeta::get_bale_id( $order );

		error_log( 'WOOPILOT CUSTOMER BALE ID: ' . $bale_id );

		if ( empty( $bale_id ) ) {
			error_log( 'WOOPILOT: customer Bale ID is empty.' );
			return;
		}

		$template = $this->get_template( 'woopilot_bale_template_customer_order_confirmed' );

		if ( empty( $template ) ) {
			error_log( 'WOOPILOT: customer order confirmed template is empty.' );
			return;
		}

		$message = $this->message_builder->build_order_message( $template, $order );
		$result  = $this->api->send_message( $bale_id, $message );

		error_log( 'WOOPILOT CUSTOMER SEND RESULT: ' . print_r( $result, true ) );
	}

	public function send_customer_payment_success( \WC_Order $order ): void {
		$this->send_customer_template( $order, 'woopilot_bale_template_payment_success' );
	}

	public function send_customer_payment_failed( \WC_Order $order ): void {
		$this->send_customer_template( $order, 'woopilot_bale_template_payment_failed' );
	}

	public function send_customer_status_changed( \WC_Order $order, string $new_status ): void {
		$template_key = '';

		switch ( $new_status ) {
			case 'processing':
				$template_key = 'woopilot_bale_template_order_processing';
				break;

			case 'completed':
				$template_key = 'woopilot_bale_template_order_completed';
				break;

			case 'cancelled':
				$template_key = 'woopilot_bale_template_order_cancelled';
				break;
		}

		if ( empty( $template_key ) ) {
			return;
		}

		$this->send_customer_template( $order, $template_key );
	}

	public function send_customer_payment_reminder( \WC_Order $order ): void {
		$this->send_customer_template( $order, 'woopilot_bale_template_payment_reminder' );
	}

	public function send_admin_low_stock( \WC_Product $product ): void {
		if ( 'yes' !== get_option( 'woopilot_bale_enable_admin_notifications', 'yes' ) ) {
			error_log( 'WOOPILOT: admin notifications disabled. Low stock not sent.' );
			return;
		}

		$template = $this->get_template( 'woopilot_bale_template_low_stock' );

		if ( empty( $template ) ) {
			error_log( 'WOOPILOT: low stock template is empty.' );
			return;
		}

		$message = $this->message_builder->build_product_message( $template, $product );

		$this->send_to_admins( $message );
	}

	private function send_customer_template( \WC_Order $order, string $template_key ): void {
		if ( 'yes' !== get_option( 'woopilot_bale_enable_customer_notifications', 'yes' ) ) {
			error_log( 'WOOPILOT: customer notifications disabled.' );
			return;
		}

		$bale_id = OrderMeta::get_bale_id( $order );

		error_log( 'WOOPILOT CUSTOMER BALE ID: ' . $bale_id );

		if ( empty( $bale_id ) ) {
			error_log( 'WOOPILOT: customer Bale ID is empty.' );
			return;
		}

		$template = $this->get_template( $template_key );

		if ( empty( $template ) ) {
			error_log( 'WOOPILOT: template is empty: ' . $template_key );
			return;
		}

		$message = $this->message_builder->build_order_message( $template, $order );
		$result  = $this->api->send_message( $bale_id, $message );

		error_log( 'WOOPILOT CUSTOMER TEMPLATE SEND RESULT: ' . print_r( $result, true ) );
	}

	private function send_to_admins( string $message ): void {
		$recipients = $this->get_admin_recipients();

		error_log( 'WOOPILOT ADMIN RECIPIENTS: ' . print_r( $recipients, true ) );

		if ( empty( $recipients ) ) {
			error_log( 'WOOPILOT: no admin recipients found.' );
			return;
		}

		foreach ( $recipients as $recipient ) {
			$result = $this->api->send_message( $recipient, $message );

			error_log( 'WOOPILOT ADMIN SEND RESULT: ' . print_r( $result, true ) );
		}
	}

	private function get_admin_recipients(): array {
		$admin_ids = get_option( 'woopilot_bale_admin_ids', '' );
		$group_id  = get_option( 'woopilot_bale_group_id', '' );

		$recipients = array();

		if ( ! empty( $admin_ids ) ) {
			$recipients = array_merge(
				$recipients,
				array_map( 'trim', explode( ',', $admin_ids ) )
			);
		}

		if ( ! empty( $group_id ) ) {
			$recipients[] = trim( $group_id );
		}

		$recipients = array_filter(
			$recipients,
			static function ( string $recipient ): bool {
				return '' !== $recipient;
			}
		);

		return array_values( array_unique( $recipients ) );
	}

	private function get_template( string $template_key ): string {
		$defaults = TemplateDefaults::all();
		$template = get_option( $template_key, '' );

		if ( empty( $template ) && isset( $defaults[ $template_key ] ) ) {
			$template = $defaults[ $template_key ];
		}

		return (string) $template;
	}
}