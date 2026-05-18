<?php

namespace WooPilot\Bale\Admin;

defined( 'ABSPATH' ) || exit;

final class LoginCustomizer {

	public static function get_logo_url(): string {
		$logo_id = absint( get_option( 'woopilot_bale_auth_logo_id', 0 ) );

		if ( $logo_id ) {
			$url = wp_get_attachment_image_url( $logo_id, 'medium' );

			if ( $url ) {
				return esc_url_raw( $url );
			}
		}

		return '';
	}

	public static function get_title(): string {
		return (string) get_option(
			'woopilot_bale_auth_title',
			__( 'ورود / ثبت نام', 'woopilot-bale' )
		);
	}

	public static function get_subtitle(): string {
		return (string) get_option(
			'woopilot_bale_auth_subtitle',
			__( 'برای ورود شماره موبایل خود را وارد کنید.', 'woopilot-bale' )
		);
	}

	public static function get_footer_text(): string {
		return (string) get_option(
			'woopilot_bale_auth_footer',
			__( 'ورود امن با بله', 'woopilot-bale' )
		);
	}

	public static function get_dynamic_css(): string {
		$primary       = self::sanitize_color( get_option( 'woopilot_bale_auth_primary_color', '#8bd957' ), '#8bd957' );
		$primary_hover = self::sanitize_color( get_option( 'woopilot_bale_auth_primary_hover_color', '#78ca45' ), '#78ca45' );
		$text          = self::sanitize_color( get_option( 'woopilot_bale_auth_text_color', '#111827' ), '#111827' );
		$background    = self::sanitize_color( get_option( 'woopilot_bale_auth_background_color', '#f7f9fc' ), '#f7f9fc' );
		$card          = self::sanitize_color( get_option( 'woopilot_bale_auth_card_color', '#ffffff' ), '#ffffff' );
		$input         = self::sanitize_color( get_option( 'woopilot_bale_auth_input_color', '#f8fafc' ), '#f8fafc' );
		$border        = self::sanitize_color( get_option( 'woopilot_bale_auth_border_color', '#e5e7eb' ), '#e5e7eb' );

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

	private static function sanitize_color( $color, string $fallback ): string {
		$color = sanitize_hex_color( (string) $color );

		if ( empty( $color ) ) {
			return $fallback;
		}

		return $color;
	}
}