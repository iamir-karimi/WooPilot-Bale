<?php

namespace WooPilot\Bale\Messaging;

defined( 'ABSPATH' ) || exit;

final class TemplateDefaults {

	public static function all(): array {
		return array(
			'woopilot_bale_template_admin_new_order' => self::admin_new_order(),
			'woopilot_bale_template_customer_order_confirmed' => self::customer_order_confirmed(),
			'woopilot_bale_template_payment_success' => self::payment_success(),
			'woopilot_bale_template_payment_failed' => self::payment_failed(),
			'woopilot_bale_template_order_processing' => self::order_processing(),
			'woopilot_bale_template_order_completed' => self::order_completed(),
			'woopilot_bale_template_order_cancelled' => self::order_cancelled(),
			'woopilot_bale_template_low_stock' => self::low_stock(),
		);
	}

	private static function admin_new_order(): string {
		return "🛒 سفارش جدید ثبت شد

شماره سفارش: {order_id}
نام مشتری: {customer_name}
مبلغ کل: {total_price}
وضعیت سفارش: {order_status}
روش پرداخت: {payment_method}

محصولات:
{products}

آدرس:
{address}";
	}

	private static function customer_order_confirmed(): string {
		return "سلام {customer_name} عزیز 🌱

سفارش شما با موفقیت ثبت شد.

شماره سفارش: {order_id}
مبلغ کل: {total_price}
وضعیت سفارش: {order_status}

محصولات:
{products}

از خرید شما سپاسگزاریم.";
	}

	private static function payment_success(): string {
		return "✅ پرداخت سفارش شما با موفقیت انجام شد.

شماره سفارش: {order_id}
مبلغ پرداخت‌شده: {total_price}
وضعیت سفارش: {order_status}

سفارش شما در حال پردازش است.";
	}

	private static function payment_failed(): string {
		return "⚠️ پرداخت سفارش شما ناموفق بود.

شماره سفارش: {order_id}
مبلغ سفارش: {total_price}

لطفاً برای تکمیل خرید، وضعیت پرداخت را از حساب کاربری خود بررسی کنید.";
	}

	private static function order_processing(): string {
		return "📦 سفارش شما در حال پردازش است.

شماره سفارش: {order_id}
وضعیت سفارش: {order_status}

پس از آماده‌سازی، وضعیت سفارش به شما اطلاع داده می‌شود.";
	}

	private static function order_completed(): string {
		return "✅ سفارش شما تکمیل شد.

شماره سفارش: {order_id}
وضعیت سفارش: {order_status}

از همراهی شما سپاسگزاریم.";
	}

	private static function order_cancelled(): string {
		return "❌ سفارش شما لغو شد.

شماره سفارش: {order_id}
وضعیت سفارش: {order_status}

در صورت نیاز، با پشتیبانی فروشگاه تماس بگیرید.";
	}

	private static function low_stock(): string {
		return "⚠️ هشدار موجودی کالا

محصول: {product_name}
موجودی فعلی: {stock_quantity}

موجودی این محصول کمتر از حد هشدار تنظیم‌شده است.";
	}
}