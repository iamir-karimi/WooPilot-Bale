<?php

namespace WooPilot\Bale;

use WooPilot\Bale\Admin\AdminMenu;
use WooPilot\Bale\Admin\LoginCustomizer;
use WooPilot\Bale\Admin\Settings;
use WooPilot\Bale\Auth\AjaxAuth;
use WooPilot\Bale\Auth\BaleConnectPage;
use WooPilot\Bale\Auth\OtpLogin;
use WooPilot\Bale\Reports\SalesReportAjax;
use WooPilot\Bale\Reports\ScheduledSalesReport;
use WooPilot\Bale\Webhook\BaleWebhook;
use WooPilot\Bale\WooCommerce\CheckoutField;
use WooPilot\Bale\WooCommerce\OrderHooks;
use WooPilot\Bale\WooCommerce\OrderMeta;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	private Loader $loader;

	public function __construct() {
		$this->loader = new Loader();
	}

	public function run(): void {
		$this->load_textdomain();
		$this->define_hooks();

		$this->loader->run();
	}

	private function load_textdomain(): void {
		load_plugin_textdomain(
			'woopilot-bale',
			false,
			dirname( WOOPILOT_BALE_BASENAME ) . '/languages'
		);
	}

	private function define_hooks(): void {
		$settings               = new Settings();
		$admin_menu             = new AdminMenu( $settings );
		$checkout_field         = new CheckoutField();
		$order_meta             = new OrderMeta();
		$order_hooks            = new OrderHooks();
		$otp_login              = new OtpLogin();
		$ajax_auth              = new AjaxAuth();
		$bale_webhook           = new BaleWebhook();
		$bale_connect_page      = new BaleConnectPage();
		$sales_report_ajax      = new SalesReportAjax();
		$scheduled_sales_report = new ScheduledSalesReport();

		$this->loader->add_action( 'init', $ajax_auth, 'register' );

		$this->loader->add_action( 'admin_menu', $admin_menu, 'register' );
		$this->loader->add_action( 'admin_init', $settings, 'register_settings' );
		$this->loader->add_action( 'admin_post_woopilot_bale_test_connection', $settings, 'handle_test_connection' );
		$this->loader->add_action( 'admin_post_woopilot_bale_set_webhook', $settings, 'handle_set_webhook' );

		if ( 'yes' === get_option( 'woopilot_bale_enable_otp_login', 'no' ) ) {
			$this->loader->add_action( 'init', $otp_login, 'register_shortcode' );
			$this->loader->add_action( 'template_redirect', $otp_login, 'handle_request' );
		}

		if ( 'yes' === get_option( 'woopilot_bale_enable_bale_connect', 'no' ) ) {
			$this->loader->add_action( 'init', $bale_connect_page, 'add_endpoint' );
			$this->loader->add_filter( 'query_vars', $bale_connect_page, 'add_query_vars' );
			$this->loader->add_filter( 'woocommerce_account_menu_items', $bale_connect_page, 'add_menu_item' );
			$this->loader->add_action( 'woocommerce_account_bale-connect_endpoint', $bale_connect_page, 'render_endpoint' );
		}

		$this->loader->add_action( 'init', $scheduled_sales_report, 'maybe_schedule' );
		$this->loader->add_action( ScheduledSalesReport::HOOK, $scheduled_sales_report, 'send' );

		$this->loader->add_action( 'rest_api_init', $bale_webhook, 'register_routes' );
		$this->loader->add_action( 'wp_ajax_woopilot_bale_get_sales_report', $sales_report_ajax, 'handle' );

		$this->loader->add_filter( 'woocommerce_checkout_fields', $checkout_field, 'add_checkout_field' );
		$this->loader->add_action( 'woocommerce_checkout_process', $checkout_field, 'validate_checkout_field' );
		$this->loader->add_action( 'woocommerce_checkout_update_order_meta', $checkout_field, 'save_checkout_field' );
		$this->loader->add_action( 'woocommerce_checkout_create_order', $checkout_field, 'save_checkout_field_to_order', 20, 2 );

		$this->loader->add_action( 'woocommerce_admin_order_data_after_billing_address', $order_meta, 'display_admin_order_meta' );
		$this->loader->add_action( 'woocommerce_admin_order_data_after_order_details', $order_meta, 'add_admin_edit_field' );
		$this->loader->add_action( 'woocommerce_process_shop_order_meta', $order_meta, 'save_admin_edit_field' );

		$this->loader->add_action( 'woocommerce_new_order', $order_hooks, 'handle_new_order', 20, 1 );
		$this->loader->add_action( 'woocommerce_checkout_order_processed', $order_hooks, 'handle_checkout_order_processed', 20, 3 );
		$this->loader->add_action( 'woocommerce_payment_complete', $order_hooks, 'handle_payment_complete', 20, 1 );
		$this->loader->add_action( 'woocommerce_order_status_changed', $order_hooks, 'handle_order_status_changed', 20, 4 );

		$this->loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_admin_assets' );
		$this->loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_frontend_assets' );
	}

	public function enqueue_admin_assets(): void {
		wp_enqueue_style(
			'woopilot-bale-admin',
			WOOPILOT_BALE_URL . 'assets/css/admin.css',
			array(),
			WOOPILOT_BALE_VERSION
		);

		wp_enqueue_script(
			'woopilot-bale-admin',
			WOOPILOT_BALE_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			WOOPILOT_BALE_VERSION,
			true
		);

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

		if ( 'woopilot-bale' !== $page ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style( 'wp-color-picker' );

		wp_enqueue_style(
			'woopilot-bale-auth-customizer',
			WOOPILOT_BALE_URL . 'assets/css/auth-customizer.css',
			array( 'wp-color-picker' ),
			WOOPILOT_BALE_VERSION
		);

		wp_enqueue_script(
			'woopilot-bale-auth-customizer',
			WOOPILOT_BALE_URL . 'assets/js/auth-customizer.js',
			array( 'jquery', 'wp-color-picker' ),
			WOOPILOT_BALE_VERSION,
			true
		);

		wp_enqueue_style(
			'woopilot-bale-sales-report',
			WOOPILOT_BALE_URL . 'assets/css/sales-report.css',
			array(),
			WOOPILOT_BALE_VERSION
		);

		wp_enqueue_style(
			'persian-datepicker',
			WOOPILOT_BALE_URL . 'assets/date/css/persian-datepicker.min.css',
			array(),
			'1.2.0'
		);

		wp_enqueue_script(
			'chartjs',
			WOOPILOT_BALE_URL . 'assets/vendor/chart.umd.min.js',
			array(),
			'4.4.7',
			true
		);

		wp_enqueue_script(
			'persian-date',
			'https://cdn.jsdelivr.net/npm/persian-date@1.1.0/dist/persian-date.min.js',
			array( 'jquery' ),
			'1.1.0',
			true
		);

		wp_enqueue_script(
			'persian-datepicker',
			'https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js',
			array( 'jquery', 'persian-date' ),
			'1.2.0',
			true
		);

		wp_enqueue_script(
			'woopilot-bale-sales-report',
			WOOPILOT_BALE_URL . 'assets/js/sales-report.js',
			array( 'jquery', 'chartjs', 'persian-date', 'persian-datepicker' ),
			WOOPILOT_BALE_VERSION,
			true
		);

		wp_localize_script(
			'woopilot-bale-sales-report',
			'WooPilotSalesReport',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'woopilot_bale_sales_report' ),
				'i18n'     => array(
					'ajax_error'    => __( 'خطا در دریافت گزارش فروش.', 'woopilot-bale' ),
					'chart_missing' => __( 'کتابخانه نمودار بارگذاری نشده است.', 'woopilot-bale' ),
					'empty_orders'  => __( 'سفارشی در این بازه یافت نشد.', 'woopilot-bale' ),
				),
			)
		);
	}

	public function enqueue_frontend_assets(): void {
		wp_enqueue_style(
			'woopilot-bale-auth-modal',
			WOOPILOT_BALE_URL . 'assets/css/auth-modal.css',
			array(),
			WOOPILOT_BALE_VERSION
		);

		wp_add_inline_style(
			'woopilot-bale-auth-modal',
			$this->get_auth_dynamic_css()
		);

		wp_enqueue_script(
			'woopilot-bale-auth-modal',
			WOOPILOT_BALE_URL . 'assets/js/auth-modal.js',
			array( 'jquery' ),
			WOOPILOT_BALE_VERSION,
			true
		);

		wp_localize_script(
			'woopilot-bale-auth-modal',
			'WooPilotBaleAuth',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'woopilot_bale_ajax_auth' ),
				'i18n'    => array(
					'error' => __( 'خطایی رخ داد. لطفاً دوباره تلاش کنید.', 'woopilot-bale' ),
				),
			)
		);
	}

	private function get_auth_dynamic_css(): string {
		$primary       = sanitize_hex_color( get_option( 'woopilot_bale_auth_primary_color', '#8bd957' ) ) ?: '#8bd957';
		$primary_hover = sanitize_hex_color( get_option( 'woopilot_bale_auth_primary_hover_color', '#78ca45' ) ) ?: '#78ca45';
		$text          = sanitize_hex_color( get_option( 'woopilot_bale_auth_text_color', '#111827' ) ) ?: '#111827';
		$background    = sanitize_hex_color( get_option( 'woopilot_bale_auth_background_color', '#f7f9fc' ) ) ?: '#f7f9fc';
		$card          = sanitize_hex_color( get_option( 'woopilot_bale_auth_card_color', '#ffffff' ) ) ?: '#ffffff';
		$input         = sanitize_hex_color( get_option( 'woopilot_bale_auth_input_color', '#f8fafc' ) ) ?: '#f8fafc';
		$border        = sanitize_hex_color( get_option( 'woopilot_bale_auth_border_color', '#eef0f4' ) ) ?: '#eef0f4';

		$card_radius   = absint( get_option( 'woopilot_bale_auth_card_radius', 24 ) );
		$input_radius  = absint( get_option( 'woopilot_bale_auth_input_radius', 12 ) );
		$button_radius = absint( get_option( 'woopilot_bale_auth_button_radius', 12 ) );
		$card_width    = absint( get_option( 'woopilot_bale_auth_card_width', 430 ) );
		$card_padding  = absint( get_option( 'woopilot_bale_auth_card_padding', 36 ) );

		return "
			.woopilot-auth-shell {
				background: {$background};
			}
			.woopilot-auth-card {
				max-width: {$card_width}px;
				padding: {$card_padding}px;
				background: {$card};
				border-radius: {$card_radius}px;
			}
			.woopilot-auth-title {
				color: {$text};
			}
			.woopilot-auth-field input {
				background: {$input};
				border-color: {$border};
				border-radius: {$input_radius}px;
			}
			.woopilot-auth-button {
				background: {$primary};
				border-radius: {$button_radius}px;
			}
			.woopilot-auth-button:hover {
				background: {$primary_hover};
			}
		";
	}
}
