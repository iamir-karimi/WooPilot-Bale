<?php

namespace WooPilot\Bale\Bot;

defined( 'ABSPATH' ) || exit;

final class CustomerAccount {

	private string $table;

	public function __construct() {
		global $wpdb;

		$this->table = $wpdb->prefix . 'woopilot_bale_users';
	}

	public function get_account_summary( string $chat_id ): string {
		$user = $this->find_bale_user_by_chat_id( $chat_id );

		if ( ! $user ) {
			return "❌ حساب شما هنوز به فروشگاه متصل نیست.\n\nبرای اتصال، ابتدا از صفحه ورود سایت شماره موبایل خود را وارد کنید و شناسه اتصال را داخل ربات ارسال کنید.";
		}

		$wp_user_id = absint( $user->wp_user_id );
		$wp_user    = $wp_user_id > 0 ? get_user_by( 'id', $wp_user_id ) : null;

		$name = $wp_user instanceof \WP_User
			? trim( $wp_user->display_name )
			: __( 'کاربر بله', 'woopilot-bale' );

		$message  = "👤 حساب من\n\n";
		$message .= "نام: {$name}\n";
		$message .= 'موبایل: ' . $user->phone . "\n";
		$message .= 'وضعیت اتصال: فعال ✅' . "\n";

		if ( $wp_user instanceof \WP_User ) {
			$message .= 'ایمیل: ' . $wp_user->user_email . "\n";
		}

		return $message;
	}

	public function get_recent_orders( string $chat_id, int $limit = 5 ): string {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return 'ووکامرس فعال نیست.';
		}

		$user = $this->find_bale_user_by_chat_id( $chat_id );

		if ( ! $user ) {
			return "❌ برای مشاهده سفارش‌ها، ابتدا حساب بله خود را به فروشگاه متصل کنید.";
		}

		$args = array(
			'limit'   => $limit,
			'orderby' => 'date',
			'order'   => 'DESC',
		);

		if ( ! empty( $user->wp_user_id ) ) {
			$args['customer_id'] = absint( $user->wp_user_id );
		} else {
			$args['billing_phone'] = $user->phone;
		}

		$orders = wc_get_orders( $args );

		if ( empty( $orders ) ) {
			return '🧾 هنوز سفارشی برای حساب شما ثبت نشده است.';
		}

		$message = "🧾 آخرین سفارش‌های شما\n\n";

		foreach ( $orders as $order ) {
			if ( ! $order instanceof \WC_Order ) {
				continue;
			}

			$message .= $this->format_order_summary( $order );
		}

		$message .= "\nبرای پیگیری دقیق‌تر، شماره سفارش را این‌طور ارسال کنید:\n";
		$message .= "order 123";

		return $message;
	}

	public function track_order( string $chat_id, int $order_id ): string {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return 'ووکامرس فعال نیست.';
		}

		$user = $this->find_bale_user_by_chat_id( $chat_id );

		if ( ! $user ) {
			return "❌ برای پیگیری سفارش، ابتدا حساب بله خود را به فروشگاه متصل کنید.";
		}

		$order = wc_get_order( $order_id );

		if ( ! $order instanceof \WC_Order ) {
			return '❌ سفارشی با این شماره پیدا نشد.';
		}

		if ( ! $this->user_can_access_order( $user, $order ) ) {
			return '❌ این سفارش متعلق به حساب متصل‌شده شما نیست.';
		}

		$message  = "📦 پیگیری سفارش #" . $order->get_order_number() . "\n\n";
		$message .= 'وضعیت: ' . wc_get_order_status_name( $order->get_status() ) . "\n";
		$message .= 'مبلغ: ' . wp_strip_all_tags( $order->get_formatted_order_total() ) . "\n";
		$message .= 'تاریخ ثبت: ' . wc_format_datetime( $order->get_date_created() ) . "\n";
		$message .= 'روش پرداخت: ' . wp_strip_all_tags( $order->get_payment_method_title() ) . "\n";

		if ( $order->needs_payment() ) {
			$message .= "\n💳 لینک پرداخت:\n" . $order->get_checkout_payment_url();
		} else {
			$message .= "\n🔗 مشاهده سفارش:\n" . $order->get_view_order_url();
		}

		return $message;
	}

	private function format_order_summary( \WC_Order $order ): string {
		$message  = 'سفارش #' . $order->get_order_number() . "\n";
		$message .= 'وضعیت: ' . wc_get_order_status_name( $order->get_status() ) . "\n";
		$message .= 'مبلغ: ' . wp_strip_all_tags( $order->get_formatted_order_total() ) . "\n";
		$message .= 'کد پیگیری: order ' . $order->get_id() . "\n\n";

		return $message;
	}

	private function find_bale_user_by_chat_id( string $chat_id ): ?object {
		global $wpdb;

		$chat_id = sanitize_text_field( $chat_id );

		if ( '' === $chat_id ) {
			return null;
		}

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table}
				WHERE bale_chat_id = %s
				AND status = %s
				ORDER BY id DESC
				LIMIT 1",
				$chat_id,
				'active'
			)
		);

		return $row ?: null;
	}

	private function user_can_access_order( object $user, \WC_Order $order ): bool {
		$wp_user_id = absint( $user->wp_user_id );

		if ( $wp_user_id > 0 && $order->get_customer_id() === $wp_user_id ) {
			return true;
		}

		$order_phone = preg_replace( '/[^0-9+]/', '', (string) $order->get_billing_phone() );
		$user_phone  = preg_replace( '/[^0-9+]/', '', (string) $user->phone );

		return $order_phone === $user_phone;
	}
}