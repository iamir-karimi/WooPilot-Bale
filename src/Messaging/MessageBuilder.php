<?php

namespace WooPilot\Bale\Messaging;

defined( 'ABSPATH' ) || exit;

final class MessageBuilder {

	public function build_order_message( string $template, \WC_Order $order ): string {
		$variables = $this->get_order_variables( $order );

		return $this->replace_variables( $template, $variables );
	}

	public function build_product_message( string $template, \WC_Product $product ): string {
		$stock_quantity = $product->get_stock_quantity();

		$variables = array(
			'{product_id}'      => (string) $product->get_id(),
			'{product_name}'    => $product->get_name(),
			'{stock_quantity}'  => null === $stock_quantity ? __( 'نامشخص', 'woopilot-bale' ) : (string) $stock_quantity,
			'{product_sku}'     => $product->get_sku(),
			'{product_price}'   => wp_strip_all_tags( $product->get_price_html() ),
			'{product_status}'  => $product->is_in_stock() ? __( 'موجود', 'woopilot-bale' ) : __( 'ناموجود', 'woopilot-bale' ),
			'{low_stock_limit}' => (string) absint( get_option( 'woopilot_bale_low_stock_threshold', 0 ) ),
		);

		return $this->replace_variables( $template, $variables );
	}

	public function get_order_variables( \WC_Order $order ): array {
		return array(
			'{order_id}'       => (string) $order->get_id(),
			'{order_number}'   => $order->get_order_number(),
			'{customer_name}'  => $this->get_customer_name( $order ),
			'{total_price}'    => wp_strip_all_tags( $order->get_formatted_order_total() ),
			'{order_status}'   => wc_get_order_status_name( $order->get_status() ),
			'{payment_method}' => $order->get_payment_method_title(),
			'{products}'       => $this->get_products_text( $order ),
			'{address}'        => $this->get_address_text( $order ),
			'{billing_phone}'  => (string) $order->get_billing_phone(),
			'{billing_email}'  => (string) $order->get_billing_email(),
			'{site_name}'      => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
		);
	}

	public function replace_variables( string $template, array $variables ): string {
		$message = strtr( $template, $variables );

		/**
		 * Filter final Bale message before sending.
		 *
		 * @param string $message
		 * @param string $template
		 * @param array  $variables
		 */
		return apply_filters( 'woopilot_bale_built_message', $message, $template, $variables );
	}

	private function get_customer_name( \WC_Order $order ): string {
		$name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );

		if ( empty( $name ) ) {
			$name = __( 'مشتری گرامی', 'woopilot-bale' );
		}

		return $name;
	}

	private function get_products_text( \WC_Order $order ): string {
		$lines = array();

		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product ) {
				continue;
			}

			$product_name = $item->get_name();
			$quantity     = $item->get_quantity();
			$total        = wc_price( $item->get_total(), array( 'currency' => $order->get_currency() ) );

			$lines[] = sprintf(
				'%1$s × %2$d - %3$s',
				wp_strip_all_tags( $product_name ),
				absint( $quantity ),
				wp_strip_all_tags( $total )
			);
		}

		return ! empty( $lines ) ? implode( "\n", $lines ) : __( 'محصولی ثبت نشده است.', 'woopilot-bale' );
	}

	private function get_address_text( \WC_Order $order ): string {
		$address = $order->get_formatted_billing_address();

		if ( empty( $address ) ) {
			return __( 'آدرس ثبت نشده است.', 'woopilot-bale' );
		}

		$address = str_replace( array( '<br/>', '<br />', '<br>' ), "\n", $address );

		return wp_strip_all_tags( $address );
	}
}