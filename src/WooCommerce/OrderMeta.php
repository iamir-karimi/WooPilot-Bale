<?php

namespace WooPilot\Bale\WooCommerce;

defined( 'ABSPATH' ) || exit;

final class OrderMeta {

	private const META_KEY = '_woopilot_bale_id';

	public function display_admin_order_meta( \WC_Order $order ): void {
		$bale_id = $order->get_meta( self::META_KEY );

		echo '<div class="address">';
		echo '<p><strong>' . esc_html__( 'شناسه بله:', 'woopilot-bale' ) . '</strong> ';

		if ( ! empty( $bale_id ) ) {
			echo esc_html( $bale_id );
		} else {
			echo '<span style="color:#999;">' . esc_html__( 'ثبت نشده', 'woopilot-bale' ) . '</span>';
		}

		echo '</p>';
		echo '</div>';
	}

	public function add_admin_edit_field( \WC_Order $order ): void {
		$bale_id = $order->get_meta( self::META_KEY );

		woocommerce_wp_text_input(
			array(
				'id'          => self::META_KEY,
				'label'       => esc_html__( 'شناسه بله مشتری', 'woopilot-bale' ),
				'placeholder' => esc_attr__( 'مثلاً: 123456789', 'woopilot-bale' ),
				'value'       => $bale_id,
				'description' => esc_html__( 'این شناسه برای ارسال پیام وضعیت سفارش به مشتری در بله استفاده می‌شود.', 'woopilot-bale' ),
				'desc_tip'    => true,
			)
		);
	}

	public function save_admin_edit_field( int $order_id ): void {
		if ( ! current_user_can( 'edit_shop_order', $order_id ) ) {
			return;
		}

		if ( ! isset( $_POST[ self::META_KEY ] ) ) {
			return;
		}

		$bale_id = sanitize_text_field( wp_unslash( $_POST[ self::META_KEY ] ) );

		if ( ! empty( $bale_id ) && ! preg_match( '/^[0-9\-]{4,30}$/', $bale_id ) ) {
			return;
		}

		update_post_meta( $order_id, self::META_KEY, $bale_id );
	}

	public static function get_bale_id( \WC_Order $order ): string {
		return (string) $order->get_meta( self::META_KEY );
	}

	public static function get_meta_key(): string {
		return self::META_KEY;
	}
}