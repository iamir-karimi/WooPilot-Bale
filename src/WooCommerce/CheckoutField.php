<?php

namespace WooPilot\Bale\WooCommerce;

defined( 'ABSPATH' ) || exit;

final class CheckoutField {

	private const META_KEY = '_woopilot_bale_id';

	public function add_checkout_field( array $fields ): array {
		$fields['billing']['woopilot_bale_id'] = array(
			'type'        => 'text',
			'label'       => esc_html__( 'شناسه بله', 'woopilot-bale' ),
			'placeholder' => esc_attr__( 'مثلاً: 123456789', 'woopilot-bale' ),
			'required'    => true,
			'class'       => array( 'form-row-wide' ),
			'priority'    => 120,
		);

		return $fields;
	}

	public function validate_checkout_field(): void {
		if ( empty( $_POST['woopilot_bale_id'] ) ) {
			wc_add_notice(
				esc_html__( 'لطفاً شناسه بله خود را وارد کنید.', 'woopilot-bale' ),
				'error'
			);

			return;
		}

		$bale_id = sanitize_text_field( wp_unslash( $_POST['woopilot_bale_id'] ) );

		if ( ! preg_match( '/^[0-9\-]{4,30}$/', $bale_id ) ) {
			wc_add_notice(
				esc_html__( 'شناسه بله معتبر نیست. فقط عدد و خط تیره مجاز است.', 'woopilot-bale' ),
				'error'
			);
		}
	}

	public function save_checkout_field( int $order_id ): void {
		if ( empty( $_POST['woopilot_bale_id'] ) ) {
			return;
		}

		$bale_id = sanitize_text_field( wp_unslash( $_POST['woopilot_bale_id'] ) );

		update_post_meta( $order_id, self::META_KEY, $bale_id );

		error_log( 'WOOPILOT: Bale ID saved by order meta hook. Order ID: ' . $order_id . ' Bale ID: ' . $bale_id );
	}

	public function save_checkout_field_to_order( \WC_Order $order, array $data ): void {
		if ( empty( $_POST['woopilot_bale_id'] ) ) {
			error_log( 'WOOPILOT: Bale ID POST is empty in create_order hook.' );
			return;
		}

		$bale_id = sanitize_text_field( wp_unslash( $_POST['woopilot_bale_id'] ) );

		if ( ! preg_match( '/^[0-9\-]{4,30}$/', $bale_id ) ) {
			error_log( 'WOOPILOT: Bale ID is invalid in create_order hook: ' . $bale_id );
			return;
		}

		$order->update_meta_data( self::META_KEY, $bale_id );

		error_log( 'WOOPILOT: Bale ID saved to order object. Order ID: ' . $order->get_id() . ' Bale ID: ' . $bale_id );
	}

	public static function get_meta_key(): string {
		return self::META_KEY;
	}
}