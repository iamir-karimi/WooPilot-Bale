<?php

namespace WooPilot\Bale\Admin;

defined( 'ABSPATH' ) || exit;

final class AdminMenu {

	private Settings $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	public function register(): void {
		add_menu_page(
			esc_html__( 'اعلان‌های بله ووکامرس', 'woopilot-bale' ),
			esc_html__( 'اعلان‌های بله', 'woopilot-bale' ),
			'manage_woocommerce',
			'woopilot-bale',
			array( $this->settings, 'render' ),
			'dashicons-format-chat',
			56
		);

		add_submenu_page(
			'woopilot-bale',
			esc_html__( 'تنظیمات ربات', 'woopilot-bale' ),
			esc_html__( 'تنظیمات ربات', 'woopilot-bale' ),
			'manage_woocommerce',
			'woopilot-bale',
			array( $this->settings, 'render' )
		);
	}
}