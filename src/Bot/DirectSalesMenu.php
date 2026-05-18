<?php

namespace WooPilot\Bale\Bot;

defined( 'ABSPATH' ) || exit;

final class DirectSalesMenu {

	public function is_enabled(): bool {
		$value = get_option( 'woopilot_bale_direct_sales_enabled', 'no' );

		return in_array( $value, array( 'yes', '1', 1, true ), true );
	}

	public function get_buttons(): array {
		$default_buttons = $this->get_default_buttons();

		$enabled_buttons = get_option(
			'woopilot_bale_direct_sales_buttons',
			array_keys( $default_buttons )
		);

		if ( ! is_array( $enabled_buttons ) ) {
			$enabled_buttons = array_keys( $default_buttons );
		}

		$buttons = array();

		foreach ( $enabled_buttons as $key ) {
			$key = sanitize_key( $key );

			if ( isset( $default_buttons[ $key ] ) ) {
				$custom_label = get_option(
					'woopilot_bale_direct_sales_button_' . $key,
					''
				);

				$buttons[ $key ] = ! empty( $custom_label )
					? sanitize_text_field( $custom_label )
					: $default_buttons[ $key ];
			}
		}

		return $buttons;
	}

	public function get_keyboard(): array {
		$rows = array();

		foreach ( array_chunk( $this->get_buttons(), 2, true ) as $row ) {

			$keyboard_row = array();

			foreach ( $row as $label ) {

				$keyboard_row[] = array(
					'text' => $label,
				);
			}

			$rows[] = $keyboard_row;
		}

		return array(
			'keyboard' => $rows,
			'resize_keyboard' => true,
			'persistent_keyboard' => true,
			'one_time_keyboard' => false,
		);
	}

	public function get_welcome_image_url(): string {
		$url = get_option( 'woopilot_bale_direct_sales_welcome_image_url', '' );
		$url = esc_url_raw( (string) $url );

		return $url ?: '';
	}


	public function get_welcome_message(): string {
		$message = get_option(
			'woopilot_bale_direct_sales_welcome_message',
			"سلام 👋\nبه فروشگاه ما خوش آمدید.\nاز منوی زیر استفاده کنید:"
		);

		return wp_strip_all_tags( (string) $message );
	}

	private function get_default_buttons(): array {
		return array(
			'search_product' => '🔍 جستجوی محصول',
			'track_product'  => '📦 پیگیری محصول',
			'shop'           => '🛒 فروشگاه',
			'categories'     => '📂 دسته‌بندی‌ها',
			'my_account'     => '👤 حساب من',
			'my_orders'      => '🧾 سفارشات من',
			'sales'          => '🔥 حراجی‌ها',
			'login'          => '🔐 ورود به حساب کاربری',
			'about'          => 'ℹ️ درباره ما',
			'support'        => '☎️ پشتیبانی',
		);
	}
}