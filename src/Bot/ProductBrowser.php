<?php

namespace WooPilot\Bale\Bot;

defined( 'ABSPATH' ) || exit;

final class ProductBrowser {

	public function search_products( string $keyword, int $limit = 5 ): string {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return 'ووکامرس فعال نیست.';
		}

		$keyword = sanitize_text_field( $keyword );

		if ( mb_strlen( $keyword ) < 2 ) {
			return 'لطفاً حداقل ۲ کاراکتر برای جستجو وارد کنید.';
		}

		$products = wc_get_products(
			array(
				'status' => 'publish',
				'limit'  => $limit,
				's'      => $keyword,
			)
		);

		if ( empty( $products ) ) {
			return 'محصولی با این عبارت پیدا نشد.';
		}

		$message = "🔍 نتیجه جستجو برای: {$keyword}\n\n";

		foreach ( $products as $product ) {
			$message .= $this->format_product_item( $product );
		}

		return $message;
	}

	public function get_latest_products( int $limit = 8 ): string {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return 'ووکامرس فعال نیست.';
		}

		$products = wc_get_products(
			array(
				'status'  => 'publish',
				'limit'   => $limit,
				'orderby' => 'date',
				'order'   => 'DESC',
			)
		);

		if ( empty( $products ) ) {
			return 'فعلاً محصولی در فروشگاه وجود ندارد.';
		}

		$message = "🛒 محصولات فروشگاه\n\n";

		foreach ( $products as $product ) {
			$message .= $this->format_product_item( $product );
		}

		return $message;
	}

	public function get_sale_products( int $limit = 8 ): string {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return 'ووکامرس فعال نیست.';
		}

		$products = wc_get_products(
			array(
				'status'  => 'publish',
				'limit'   => $limit,
				'on_sale' => true,
			)
		);

		if ( empty( $products ) ) {
			return 'فعلاً محصول حراجی وجود ندارد.';
		}

		$message = "🔥 محصولات حراجی\n\n";

		foreach ( $products as $product ) {
			$message .= $this->format_product_item( $product );
		}

		return $message;
	}

	public function get_categories( int $limit = 20 ): string {
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
				'number'     => $limit,
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return 'دسته‌بندی فعالی پیدا نشد.';
		}

		$message = "📂 دسته‌بندی‌های فروشگاه\n\n";

		foreach ( $terms as $term ) {
			$message .= '• ' . $term->name . ' (' . number_format_i18n( (int) $term->count ) . " محصول)\n";
		}

		$message .= "\nبرای جستجوی محصول، نام محصول یا دسته‌بندی را ارسال کنید.";

		return $message;
	}

	public function get_product_by_id( int $product_id ): string {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return 'ووکامرس فعال نیست.';
		}

		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			return 'محصول پیدا نشد.';
		}

		return $this->format_product_detail( $product );
	}

	private function format_product_item( \WC_Product $product ): string {
		$product_id = $product->get_id();
		$name       = $product->get_name();
		$price      = $product->get_price_html();
		$link       = get_permalink( $product_id );
		$stock      = $this->get_stock_label( $product );

		$message  = "🛍 {$name}\n";
		$message .= '💰 ' . wp_strip_all_tags( $price ?: 'بدون قیمت' ) . "\n";
		$message .= "📦 {$stock}\n";
		$message .= "🔎 کد محصول: {$product_id}\n";
		$message .= "🔗 {$link}\n\n";

		return $message;
	}

	private function format_product_detail( \WC_Product $product ): string {
		$product_id  = $product->get_id();
		$name        = $product->get_name();
		$price       = wp_strip_all_tags( $product->get_price_html() ?: 'بدون قیمت' );
		$stock       = $this->get_stock_label( $product );
		$link        = get_permalink( $product_id );
		$description = wp_trim_words( wp_strip_all_tags( $product->get_short_description() ), 35 );

		$message  = "🛍 {$name}\n\n";
		$message .= "💰 قیمت: {$price}\n";
		$message .= "📦 وضعیت موجودی: {$stock}\n";

		if ( ! empty( $description ) ) {
			$message .= "📝 {$description}\n";
		}

		$message .= "\n🔎 کد محصول: {$product_id}\n";
		$message .= "🔗 مشاهده و خرید:\n{$link}";

		return $message;
	}

	private function get_stock_label( \WC_Product $product ): string {
		if ( ! $product->managing_stock() ) {
			return $product->is_in_stock() ? 'موجود' : 'ناموجود';
		}

		$quantity = $product->get_stock_quantity();

		if ( null === $quantity ) {
			return $product->is_in_stock() ? 'موجود' : 'ناموجود';
		}

		return $quantity > 0
			? 'موجودی: ' . number_format_i18n( $quantity )
			: 'ناموجود';
	}
}