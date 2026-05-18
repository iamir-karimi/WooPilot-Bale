<?php

namespace WooPilot\Bale\Auth;

use WooPilot\Bale\Api\BaleApi;

defined( 'ABSPATH' ) || exit;

final class OtpManager {

	private OtpRepository $otp_repository;

	private BaleUserRepository $bale_user_repository;

	private BaleApi $api;

	public function __construct() {
		$this->otp_repository       = new OtpRepository();
		$this->bale_user_repository = new BaleUserRepository();
		$this->api                  = new BaleApi( get_option( 'woopilot_bale_bot_token', '' ) );
	}

	public function send_otp( string $phone ): array {
		$phone = $this->normalize_phone( $phone );

		if ( empty( $phone ) ) {
			return array(
				'success' => false,
				'message' => __( 'شماره موبایل معتبر نیست.', 'woopilot-bale' ),
			);
		}

		$bale_user = $this->bale_user_repository->find_by_phone( $phone, true );

		if ( ! $bale_user || empty( $bale_user->bale_chat_id ) || 'active' !== $bale_user->status ) {
			return array(
				'success' => false,
				'message' => __( 'این شماره هنوز به ربات بله متصل نشده است. ابتدا شناسه اتصال را در ربات ارسال کنید.', 'woopilot-bale' ),
			);
		}

		$otp        = $this->generate_otp();
		$otp_hash   = wp_hash_password( $otp );
		$expires_in = absint( get_option( 'woopilot_bale_otp_expiration_minutes', 5 ) );

		if ( $expires_in < 1 ) {
			$expires_in = 5;
		}

		$this->otp_repository->create(
			$phone,
			$otp_hash,
			$expires_in,
			$this->get_ip_address()
		);

		$message = sprintf(
			__( "کد ورود شما: %1\$s\nاعتبار کد: %2\$d دقیقه\n\nاگر شما درخواست ورود نداده‌اید، این پیام را نادیده بگیرید.", 'woopilot-bale' ),
			$otp,
			$expires_in
		);

		$result = $this->api->send_message(
			(string) $bale_user->bale_chat_id,
			$message
		);

		if ( empty( $result['success'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'ارسال کد ورود در بله ناموفق بود.', 'woopilot-bale' ),
				'data'    => $result,
			);
		}

		if ( method_exists( $this->bale_user_repository, 'touch_otp_sent' ) ) {
			$this->bale_user_repository->touch_otp_sent( $phone );
		}

		return array(
			'success' => true,
			'message' => __( 'کد ورود در بله ارسال شد.', 'woopilot-bale' ),
		);
	}

	public function verify_otp( string $phone, string $otp ): array {
		$phone = $this->normalize_phone( $phone );
		$otp   = preg_replace( '/[^0-9]/', '', $otp );

		if ( empty( $phone ) || empty( $otp ) ) {
			return array(
				'success' => false,
				'message' => __( 'شماره موبایل یا کد ورود معتبر نیست.', 'woopilot-bale' ),
			);
		}

		$record = $this->otp_repository->get_latest_active_by_phone( $phone );

		if ( ! $record ) {
			return array(
				'success' => false,
				'message' => __( 'کد ورود منقضی شده یا وجود ندارد.', 'woopilot-bale' ),
			);
		}

		$max_attempts = absint( get_option( 'woopilot_bale_otp_max_attempts', 5 ) );

		if ( $max_attempts < 1 ) {
			$max_attempts = 5;
		}

		if ( absint( $record->attempts ) >= $max_attempts ) {
			return array(
				'success' => false,
				'message' => __( 'تعداد تلاش‌های ناموفق بیش از حد مجاز است.', 'woopilot-bale' ),
			);
		}

		$this->otp_repository->increment_attempts( absint( $record->id ) );

		if ( ! wp_check_password( $otp, (string) $record->otp_hash ) ) {
			return array(
				'success' => false,
				'message' => __( 'کد ورود صحیح نیست.', 'woopilot-bale' ),
			);
		}

		$this->otp_repository->mark_used( absint( $record->id ) );

		return array(
			'success' => true,
			'message' => __( 'کد ورود تایید شد.', 'woopilot-bale' ),
		);
	}

	public function normalize_phone( string $phone ): string {
		return $this->bale_user_repository->normalize_phone( $phone );
	}

	private function generate_otp(): string {
		$length = absint( get_option( 'woopilot_bale_otp_length', 5 ) );

		if ( $length < 4 || $length > 8 ) {
			$length = 5;
		}

		$min = (int) pow( 10, $length - 1 );
		$max = (int) pow( 10, $length ) - 1;

		return (string) wp_rand( $min, $max );
	}

	private function get_ip_address(): string {
		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return '';
	}
}