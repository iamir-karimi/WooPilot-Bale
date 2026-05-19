<?php

namespace WooPilot\Bale\Webhook;

use WooPilot\Bale\Api\BaleApi;
use WooPilot\Bale\Auth\BaleUserRepository;
use WooPilot\Bale\Auth\OtpManager;
use WooPilot\Bale\Bot\CustomerAccount;
use WooPilot\Bale\Bot\DirectSalesMenu;
use WooPilot\Bale\Bot\ProductBrowser;

defined( 'ABSPATH' ) || exit;

final class BaleWebhook {

	private BaleUserRepository $repository;

	private OtpManager $otp_manager;

	private BaleApi $api;

	private DirectSalesMenu $direct_sales_menu;

	private ProductBrowser $product_browser;

	private CustomerAccount $customer_account;

	public function __construct() {
		$this->repository        = new BaleUserRepository();
		$this->otp_manager       = new OtpManager();
		$this->api               = new BaleApi( get_option( 'woopilot_bale_bot_token', '' ) );
		$this->direct_sales_menu = new DirectSalesMenu();
		$this->product_browser   = new ProductBrowser();
		$this->customer_account  = new CustomerAccount();
	}

	public function register_routes(): void {
		register_rest_route(
			'woopilot-bale/v1',
			'/webhook',
			array(
				'methods'             => array( 'GET', 'POST' ),
				'callback'            => array( $this, 'handle' ),
				'permission_callback' => array( $this, 'permission_callback' ),
			)
		);
	}

	public function permission_callback( \WP_REST_Request $request ): bool {
		$secret = trim( (string) get_option( 'woopilot_bale_webhook_secret', '' ) );

		if ( '' === $secret ) {
			return true;
		}

		$request_secret = (string) $request->get_param( 'secret' );

		return hash_equals( $secret, $request_secret );
	}

	public function handle( \WP_REST_Request $request ): \WP_REST_Response {
		if ( 'GET' === $request->get_method() ) {
			return new \WP_REST_Response(
				array(
					'ok'      => true,
					'message' => 'WooPilot Bale webhook is active.',
				),
				200
			);
		}

		$update = $request->get_json_params();

		$this->debug_log( 'RAW UPDATE', $update );

		$message = $this->extract_message( $update );

		if ( empty( $message ) ) {
			return new \WP_REST_Response( array( 'ok' => true ), 200 );
		}

		$text = $this->extract_text( $message );

		if ( '' === $text ) {
			return new \WP_REST_Response( array( 'ok' => true ), 200 );
		}

		$chat_id       = $this->extract_chat_id( $message );
		$bale_user_id  = $this->extract_user_id( $message );
		$bale_username = $this->extract_username( $message );

		if ( '' === $chat_id ) {
			return new \WP_REST_Response(
				array(
					'ok'      => false,
					'message' => 'Chat ID not found.',
				),
				200
			);
		}

		if ( preg_match( '/^(?:\/start\s+)?connect_([A-Za-z0-9]+)$/', $text, $matches ) ) {
			return $this->handle_connect_command(
				sanitize_text_field( $matches[1] ),
				$chat_id,
				$bale_user_id,
				$bale_username
			);
		}

		if ( $this->is_start_command( $text ) ) {
			return $this->handle_start_command( $chat_id );
		}

		if ( $this->direct_sales_menu->is_enabled() ) {
			$action = $this->resolve_direct_sales_action( $text );

			if ( '' !== $action ) {
				return $this->handle_direct_sales_action( $chat_id, $action );
			}

			return $this->handle_product_search_or_command( $chat_id, $text );
		}

		$this->send_message(
			$chat_id,
			__( "سلام 👋\nفروش مستقیم هنوز در تنظیمات افزونه فعال نیست.", 'woopilot-bale' )
		);

		return new \WP_REST_Response( array( 'ok' => true ), 200 );
	}

	private function extract_message( array $update ): array {
		if ( isset( $update['message'] ) && is_array( $update['message'] ) ) {
			return $update['message'];
		}

		if ( isset( $update['edited_message'] ) && is_array( $update['edited_message'] ) ) {
			return $update['edited_message'];
		}

		if ( isset( $update['callback_query']['message'] ) && is_array( $update['callback_query']['message'] ) ) {
			$message = $update['callback_query']['message'];

			if ( isset( $update['callback_query']['data'] ) ) {
				$message['text'] = (string) $update['callback_query']['data'];
			}

			if ( isset( $update['callback_query']['from'] ) ) {
				$message['from'] = $update['callback_query']['from'];
			}

			return $message;
		}

		return array();
	}

	private function extract_text( array $message ): string {
		if ( isset( $message['text'] ) ) {
			return trim( (string) $message['text'] );
		}

		if ( isset( $message['caption'] ) ) {
			return trim( (string) $message['caption'] );
		}

		return '';
	}

	private function extract_chat_id( array $message ): string {
		if ( isset( $message['chat']['id'] ) ) {
			return sanitize_text_field( (string) $message['chat']['id'] );
		}

		if ( isset( $message['from']['id'] ) ) {
			return sanitize_text_field( (string) $message['from']['id'] );
		}

		return '';
	}

	private function extract_user_id( array $message ): string {
		if ( isset( $message['from']['id'] ) ) {
			return sanitize_text_field( (string) $message['from']['id'] );
		}

		return '';
	}

	private function extract_username( array $message ): string {
		if ( isset( $message['from']['username'] ) ) {
			return sanitize_user( (string) $message['from']['username'], true );
		}

		return '';
	}

	private function is_start_command( string $text ): bool {
		return (bool) preg_match( '/^\/start(?:@\w+)?(?:\s.*)?$/i', trim( $text ) );
	}

	private function handle_start_command( string $chat_id ): \WP_REST_Response {
		if ( $this->direct_sales_menu->is_enabled() ) {
			$this->send_message(
				$chat_id,
				$this->direct_sales_menu->get_welcome_message(),
				$this->direct_sales_menu->get_keyboard()
			);
		} else {
			$this->send_message(
				$chat_id,
				__( "سلام 👋\nفروش مستقیم در تنظیمات افزونه فعال نیست.", 'woopilot-bale' )
			);
		}

		return new \WP_REST_Response( array( 'ok' => true ), 200 );
	}

	private function handle_connect_command(
		string $token,
		string $chat_id,
		string $bale_user_id,
		string $bale_username
	): \WP_REST_Response {
		$result       = $this->repository->complete_connection_by_token( $token, $chat_id, $bale_user_id, $bale_username );
		$is_connected = is_array( $result ) && ! empty( $result['success'] );
		$row          = is_array( $result ) && ! empty( $result['row'] ) ? $result['row'] : null;

		if ( ! $is_connected ) {
			$this->send_message(
				$chat_id,
				'❌ ' . ( $result['message'] ?? __( 'کد اتصال معتبر نیست یا منقضی شده است.', 'woopilot-bale' ) )
			);

			return new \WP_REST_Response( array( 'ok' => true ), 200 );
		}

		$this->send_message(
			$chat_id,
			__( "✅ حساب بله شما با موفقیت به فروشگاه متصل شد.\nدر حال ارسال کد ورود...", 'woopilot-bale' )
		);

		if ( $row && ! empty( $row->phone ) ) {
			$otp_result = $this->otp_manager->send_otp( (string) $row->phone );

			if ( empty( $otp_result['success'] ) ) {
				$this->send_message(
					$chat_id,
					'❌ ' . ( $otp_result['message'] ?? __( 'ارسال کد ورود ناموفق بود.', 'woopilot-bale' ) )
				);
			}
		}

		if ( $this->direct_sales_menu->is_enabled() ) {
			$this->send_message(
				$chat_id,
				$this->direct_sales_menu->get_welcome_message(),
				$this->direct_sales_menu->get_keyboard()
			);
		}

		return new \WP_REST_Response( array( 'ok' => true ), 200 );
	}

	private function handle_direct_sales_action( string $chat_id, string $action ): \WP_REST_Response {
		switch ( $action ) {
			case 'search_product':
				$this->send_message(
					$chat_id,
					__( "🔍 لطفاً نام محصول موردنظر را وارد کنید.\nمثال: کفش", 'woopilot-bale' ),
					$this->direct_sales_menu->get_keyboard()
				);
				break;

			case 'shop':
				$this->send_message(
					$chat_id,
					$this->product_browser->get_latest_products(),
					$this->direct_sales_menu->get_keyboard()
				);
				break;

			case 'categories':
				$this->send_message(
					$chat_id,
					$this->product_browser->get_categories(),
					$this->direct_sales_menu->get_keyboard()
				);
				break;

			case 'sales':
				$this->send_message(
					$chat_id,
					$this->product_browser->get_sale_products(),
					$this->direct_sales_menu->get_keyboard()
				);
				break;

			case 'track_product':
				$this->send_message(
					$chat_id,
					__( "📦 برای پیگیری سفارش، شماره سفارش را به این شکل ارسال کنید:\norder 123", 'woopilot-bale' ),
					$this->direct_sales_menu->get_keyboard()
				);
				break;

			case 'my_account':
				$this->send_message(
					$chat_id,
					$this->customer_account->get_account_summary( $chat_id ),
					$this->direct_sales_menu->get_keyboard()
				);
				break;

			case 'my_orders':
				$this->send_message(
					$chat_id,
					$this->customer_account->get_recent_orders( $chat_id ),
					$this->direct_sales_menu->get_keyboard()
				);
				break;

			case 'login':
				$this->send_message(
					$chat_id,
					__( '🔐 برای ورود، شماره موبایل خود را در سایت وارد کنید و شناسه اتصال را داخل ربات ارسال کنید.', 'woopilot-bale' ),
					$this->direct_sales_menu->get_keyboard()
				);
				break;

			case 'about':
				$this->send_message(
					$chat_id,
					wp_strip_all_tags(
						get_option(
							'woopilot_bale_direct_sales_about_text',
							__( 'ℹ️ اطلاعات فروشگاه هنوز تنظیم نشده است.', 'woopilot-bale' )
						)
					),
					$this->direct_sales_menu->get_keyboard()
				);
				break;

			case 'support':
				$this->send_message(
					$chat_id,
					wp_strip_all_tags(
						get_option(
							'woopilot_bale_direct_sales_support_text',
							__( '☎️ اطلاعات پشتیبانی هنوز تنظیم نشده است.', 'woopilot-bale' )
						)
					),
					$this->direct_sales_menu->get_keyboard()
				);
				break;
		}

		return new \WP_REST_Response( array( 'ok' => true ), 200 );
	}

	private function handle_product_search_or_command( string $chat_id, string $text ): \WP_REST_Response {
		if ( preg_match( '/^product\s+([0-9]+)$/i', $text, $matches ) ) {
			$this->send_message(
				$chat_id,
				$this->product_browser->get_product_by_id( absint( $matches[1] ) ),
				$this->direct_sales_menu->get_keyboard()
			);

			return new \WP_REST_Response( array( 'ok' => true ), 200 );
		}

		if ( preg_match( '/^order\s+([0-9]+)$/i', $text, $matches ) ) {
			$this->send_message(
				$chat_id,
				$this->customer_account->track_order( $chat_id, absint( $matches[1] ) ),
				$this->direct_sales_menu->get_keyboard()
			);

			return new \WP_REST_Response( array( 'ok' => true ), 200 );
		}

		$this->send_message(
			$chat_id,
			$this->product_browser->search_products( $text ),
			$this->direct_sales_menu->get_keyboard()
		);

		return new \WP_REST_Response( array( 'ok' => true ), 200 );
	}

	private function resolve_direct_sales_action( string $text ): string {
		$normalized = $this->normalize_text( $text );

		$map = array(
			'search_product' => array( 'جستجوی محصول', 'جستجو محصول', 'جستجو', 'search' ),
			'track_product'  => array( 'پیگیری محصول', 'پیگیری سفارش', 'پیگیری', 'track' ),
			'shop'           => array( 'فروشگاه', 'shop' ),
			'categories'     => array( 'دسته بندی ها', 'دسته بندی', 'دسته بندیها', 'category', 'categories' ),
			'my_account'     => array( 'حساب من', 'اکانت من', 'account' ),
			'my_orders'      => array( 'سفارشات من', 'سفارش های من', 'سفارشهای من', 'orders' ),
			'sales'          => array( 'حراجی ها', 'حراجی', 'تخفیف', 'sales' ),
			'login'          => array( 'ورود به حساب کاربری', 'ورود', 'login' ),
			'about'          => array( 'درباره ما', 'درباره', 'about' ),
			'support'        => array( 'پشتیبانی', 'support' ),
		);

		foreach ( $map as $action => $keywords ) {
			foreach ( $keywords as $keyword ) {
				if ( str_contains( $normalized, $this->normalize_text( $keyword ) ) ) {
					return $action;
				}
			}
		}

		return '';
	}

	private function normalize_text( string $text ): string {
		$text = wp_strip_all_tags( $text );
		$text = str_replace( array( 'ي', 'ك', '‌' ), array( 'ی', 'ک', ' ' ), $text );
		$text = preg_replace( '/[^\p{L}\p{N}\s]/u', ' ', $text );
		$text = preg_replace( '/\s+/u', ' ', $text );

		return mb_strtolower( trim( $text ) );
	}

	private function send_message( string $chat_id, string $message, array $reply_markup = array() ): array {
		$result = $this->api->send_message( $chat_id, $message, $reply_markup );

		$this->debug_log(
			'SEND RESULT',
			array(
				'chat_id' => $chat_id,
				'message' => $message,
				'result'  => $result,
			)
		);

		return $result;
	}

	private function debug_log( string $title, $data ): void {
		if ( 'yes' !== get_option( 'woopilot_bale_debug_mode', 'no' ) ) {
			return;
		}

		error_log( 'WOOPILOT BALE ' . $title . ': ' . print_r( $data, true ) );
	}
}