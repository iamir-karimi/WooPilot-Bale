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

	public function get_shop_intro_text(): string {
		$text = get_option(
			'woopilot_bale_direct_sales_shop_intro_text',
			__( 'لطفاً یکی از بخش‌های فروشگاه را انتخاب کنید:', 'woopilot-bale' )
		);

		$text = wp_strip_all_tags( (string) $text );

		return trim( $text ) ?: __( 'لطفاً یکی از بخش‌های فروشگاه را انتخاب کنید:', 'woopilot-bale' );
	}

	public function get_shop_sections_keyboard(): array {
		$rows = array();

		foreach ( $this->get_shop_sections() as $section_key => $section ) {
			$rows[] = array(
				array(
					'text'          => $section['label'],
					'callback_data' => 'shop_section_' . $section_key,
				),
			);
		}

		return array(
			'inline_keyboard' => $rows,
		);
	}

	public function resolve_shop_section_key( string $text ): string {
		$text = trim( $text );

		if ( preg_match( '/^shop_section[:_\-](section_[123])$/i', $text, $matches ) ) {
			return sanitize_key( $matches[1] );
		}

		if ( preg_match( '/^(section_[123])$/i', $text, $matches ) ) {
			return sanitize_key( $matches[1] );
		}

		$normalized_text = $this->normalize_text( $text );

		foreach ( $this->get_shop_sections() as $section_key => $section ) {
			if ( $normalized_text === $this->normalize_text( $section['label'] ) ) {
				return $section_key;
			}
		}

		return '';
	}

	public function get_products_by_shop_section( string $section_key ): string {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return 'ووکامرس فعال نیست.';
		}

		$section_key = sanitize_key( $section_key );
		$sections    = $this->get_shop_sections();

		if ( ! isset( $sections[ $section_key ] ) ) {
			return 'بخش انتخاب‌شده معتبر نیست.';
		}

		$section = $sections[ $section_key ];
		$ids     = $this->parse_product_ids( $section['ids'] );

		if ( empty( $ids ) ) {
			return 'هنوز محصولی برای این بخش تنظیم نشده است.';
		}

		$message = $section['label'] . "\n\n";
		$shown   = 0;

		foreach ( $ids as $product_id ) {
			$product = wc_get_product( $product_id );

			if ( ! $product instanceof \WC_Product ) {
				continue;
			}

			$status = method_exists( $product, 'get_status' ) ? (string) $product->get_status() : 'publish';

			if ( ! in_array( $status, array( 'publish', 'private' ), true ) ) {
				continue;
			}

			$message .= $this->format_product_item( $product );
			$shown++;
		}

		if ( 0 === $shown ) {
			return 'محصولات انتخاب‌شده برای این بخش پیدا نشدند یا منتشر نشده‌اند. شناسه محصولات را در تنظیمات بررسی کنید.';
		}

		return trim( $message );
	}

	public function get_product_cards_by_shop_section( string $section_key ): array {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return array(
				'error' => 'ووکامرس فعال نیست.',
				'items' => array(),
			);
		}

		$section_key = sanitize_key( $section_key );
		$sections    = $this->get_shop_sections();

		if ( ! isset( $sections[ $section_key ] ) ) {
			return array(
				'error' => 'بخش انتخاب‌شده معتبر نیست.',
				'items' => array(),
			);
		}

		$section = $sections[ $section_key ];
		$ids     = $this->parse_product_ids( $section['ids'] );

		if ( empty( $ids ) ) {
			return array(
				'error' => 'هنوز محصولی برای این بخش تنظیم نشده است.',
				'items' => array(),
			);
		}

		$items = array();

		foreach ( $ids as $product_id ) {
			$product = wc_get_product( $product_id );

			if ( ! $product instanceof \WC_Product ) {
				continue;
			}

			$status = method_exists( $product, 'get_status' ) ? (string) $product->get_status() : 'publish';

			if ( ! in_array( $status, array( 'publish', 'private' ), true ) ) {
				continue;
			}

			$items[] = $this->format_product_card( $product );
		}

		if ( empty( $items ) ) {
			return array(
				'error' => 'محصولات انتخاب‌شده برای این بخش پیدا نشدند یا منتشر نشده‌اند. شناسه محصولات را در تنظیمات بررسی کنید.',
				'items' => array(),
			);
		}

		return array(
			'title' => $section['label'],
			'items' => $items,
			'error' => '',
		);
	}

	public function get_product_card_by_id( int $product_id ): array {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return array(
				'error' => 'ووکامرس فعال نیست.',
				'item'  => null,
			);
		}

		$product = wc_get_product( $product_id );

		if ( ! $product instanceof \WC_Product ) {
			return array(
				'error' => 'محصول پیدا نشد.',
				'item'  => null,
			);
		}

		return array(
			'error' => '',
			'item'  => $this->format_product_card( $product, true ),
		);
	}

	public function get_welcome_image_url(): string {
		$url = get_option( 'woopilot_bale_direct_sales_welcome_image_url', '' );
		$url = esc_url_raw( (string) $url );

		return $url ?: '';
	}


	public function get_shop_sections(): array {
		return array(
			'section_1' => array(
				'label' => $this->get_shop_section_label(
					'woopilot_bale_direct_sales_shop_section_1_label',
					__( 'پرطرفدارترین‌ها', 'woopilot-bale' )
				),
				'ids'   => get_option( 'woopilot_bale_direct_sales_shop_section_1_ids', '' ),
			),
			'section_2' => array(
				'label' => $this->get_shop_section_label(
					'woopilot_bale_direct_sales_shop_section_2_label',
					__( 'پرفروش‌ترین‌ها', 'woopilot-bale' )
				),
				'ids'   => get_option( 'woopilot_bale_direct_sales_shop_section_2_ids', '' ),
			),
			'section_3' => array(
				'label' => $this->get_shop_section_label(
					'woopilot_bale_direct_sales_shop_section_3_label',
					__( 'تخفیفات ویژه', 'woopilot-bale' )
				),
				'ids'   => get_option( 'woopilot_bale_direct_sales_shop_section_3_ids', '' ),
			),
		);
	}

	private function get_shop_section_label( string $option_name, string $default ): string {
		$label = sanitize_text_field( (string) get_option( $option_name, $default ) );

		return '' !== trim( $label ) ? $label : $default;
	}

	private function parse_product_ids( $value ): array {
		$value = (string) $value;
		$ids   = preg_split( '/[\s,،]+/u', $value );

		if ( ! is_array( $ids ) ) {
			return array();
		}

		$ids = array_map(
			static function ( $id ): int {
				return absint( $id );
			},
			$ids
		);

		return array_values( array_unique( array_filter( $ids ) ) );
	}

	private function normalize_text( string $text ): string {
		$text = wp_strip_all_tags( $text );
		$text = str_replace( array( 'ي', 'ك', '‌', 'ۀ', 'ة' ), array( 'ی', 'ک', ' ', 'ه', 'ه' ), $text );
		$text = preg_replace( '/[^\p{L}\p{N}\s]/u', ' ', $text );
		$text = preg_replace( '/\s+/u', ' ', $text );

		return mb_strtolower( trim( $text ) );
	}

	private function format_product_card( \WC_Product $product, bool $detailed = false ): array {
		$product_id = $product->get_id();
		$image_url  = $this->get_product_image_url( $product );
		$link       = get_permalink( $product_id );

		return array(
			'id'           => $product_id,
			'image_url'    => $image_url,
			'caption'      => $this->format_product_caption( $product, $detailed ),
			'fallback_text'=> $this->format_product_item( $product ),
			'reply_markup' => array(
				'inline_keyboard' => array(
					array(
						array(
							'text' => __( 'مشاهده و خرید', 'woopilot-bale' ),
							'url'  => $link,
						),
					),
				),
			),
		);
	}

	private function format_product_caption( \WC_Product $product, bool $detailed = false ): string {
		$product_id   = $product->get_id();
		$name         = $product->get_name();
		$price        = wp_strip_all_tags( $product->get_price_html() ?: 'بدون قیمت' );
		$stock        = $this->get_stock_label( $product );
		$link         = get_permalink( $product_id );
		$product_type = $product->is_type( 'variable' ) ? 'متغیر' : 'ساده';

		$message  = "🛍 {$name}\n";
		$message .= "💰 قیمت: {$price}\n";
		$message .= "📦 وضعیت موجودی: {$stock}\n";
		$message .= "🔖 نوع محصول: {$product_type}\n";

		if ( $product->is_type( 'variable' ) ) {
			$attributes = $this->get_variable_product_attributes_summary( $product );

			if ( '' !== $attributes ) {
				$message .= "🎛 گزینه‌ها: {$attributes}\n";
			}
		}

		if ( $detailed ) {
			$description = wp_trim_words( wp_strip_all_tags( $product->get_short_description() ), 35 );

			if ( ! empty( $description ) ) {
				$message .= "📝 {$description}\n";
			}
		}

		$message .= "🔎 کد محصول: {$product_id}\n";
		$message .= "🔗 مشاهده و خرید:\n{$link}";

		return $message;
	}

	private function get_product_image_url( \WC_Product $product ): string {
		$image_id = $product->get_image_id();

		if ( ! $image_id && $product->is_type( 'variation' ) ) {
			$parent_id = $product->get_parent_id();

			if ( $parent_id ) {
				$parent = wc_get_product( $parent_id );

				if ( $parent instanceof \WC_Product ) {
					$image_id = $parent->get_image_id();
				}
			}
		}

		if ( $image_id ) {
			$url = wp_get_attachment_image_url( $image_id, 'large' );

			if ( $url ) {
				return esc_url_raw( $url );
			}
		}

		return '';
	}

	private function get_variable_product_attributes_summary( \WC_Product $product ): string {
		if ( ! $product instanceof \WC_Product_Variable ) {
			return '';
		}

		$attributes = $product->get_variation_attributes();
		$parts      = array();

		foreach ( $attributes as $attribute_name => $values ) {
			if ( empty( $values ) || ! is_array( $values ) ) {
				continue;
			}

			$label = wc_attribute_label( str_replace( 'attribute_', '', $attribute_name ) );

			$clean_values = array_map(
				static function ( $value ): string {
					return wc_clean( (string) $value );
				},
				array_slice( $values, 0, 4 )
			);

			$clean_values = array_filter( $clean_values );

			if ( empty( $clean_values ) ) {
				continue;
			}

			$parts[] = $label . ': ' . implode( '، ', $clean_values );
		}

		return implode( ' | ', $parts );
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
