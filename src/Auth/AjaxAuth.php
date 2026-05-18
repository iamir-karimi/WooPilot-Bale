<?php

namespace WooPilot\Bale\Auth;

defined( 'ABSPATH' ) || exit;

final class AjaxAuth {

	private BaleConnect $bale_connect;
	private BaleUserRepository $repository;
	private OtpManager $otp_manager;
	private UserResolver $user_resolver;

	public function __construct() {
		$this->bale_connect  = new BaleConnect();
		$this->repository    = new BaleUserRepository();
		$this->otp_manager   = new OtpManager();
		$this->user_resolver = new UserResolver();
	}

	public function register(): void {
		add_action( 'wp_ajax_nopriv_woopilot_bale_auth_start', array( $this, 'start' ) );
		add_action( 'wp_ajax_woopilot_bale_auth_start', array( $this, 'start' ) );

		add_action( 'wp_ajax_nopriv_woopilot_bale_auth_check', array( $this, 'check_connection' ) );
		add_action( 'wp_ajax_woopilot_bale_auth_check', array( $this, 'check_connection' ) );

		add_action( 'wp_ajax_nopriv_woopilot_bale_auth_send_otp', array( $this, 'send_otp' ) );
		add_action( 'wp_ajax_woopilot_bale_auth_send_otp', array( $this, 'send_otp' ) );

		add_action( 'wp_ajax_nopriv_woopilot_bale_auth_verify', array( $this, 'verify' ) );
		add_action( 'wp_ajax_woopilot_bale_auth_verify', array( $this, 'verify' ) );
	}

	public function start(): void {
		$this->verify_nonce();

		$phone    = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$username = isset( $_POST['username'] ) ? sanitize_text_field( wp_unslash( $_POST['username'] ) ) : '';

		$phone = $this->bale_connect->normalize_phone( $phone );

		if ( empty( $phone ) ) {
			wp_send_json_error( array( 'message' => __( 'شماره موبایل معتبر نیست.', 'woopilot-bale' ) ) );
		}

		$user_id = is_user_logged_in() ? get_current_user_id() : 0;
		$token   = $this->bale_connect->create_connect_token( $phone, $username, $user_id );

		if ( empty( $token ) ) {
			wp_send_json_error( array( 'message' => __( 'ساخت شناسه اتصال ناموفق بود.', 'woopilot-bale' ) ) );
		}

		$bot_username = ltrim( trim( (string) get_option( 'woopilot_bale_bot_username', '' ) ), '@' );

		wp_send_json_success(
			array(
				'message'  => __( 'شناسه اتصال ساخته شد.', 'woopilot-bale' ),
				'phone'    => $phone,
				'token'    => $token,
				'command'  => 'connect_' . $token,
				'bot_url'  => ! empty( $bot_username ) ? 'https://ble.ir/' . rawurlencode( $bot_username ) : '',
				'nextStep' => 'connect',
			)
		);
	}

	public function check_connection(): void {
		$this->verify_nonce();

		$phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$phone = $this->repository->normalize_phone( $phone );

		if ( empty( $phone ) ) {
			wp_send_json_error( array( 'message' => __( 'شماره موبایل معتبر نیست.', 'woopilot-bale' ) ) );
		}

		$user = $this->repository->find_by_phone( $phone, true );

		if ( ! $user || empty( $user->bale_chat_id ) ) {
			wp_send_json_success(
				array(
					'connected' => false,
					'message'   => __( 'هنوز اتصال بله کامل نشده است.', 'woopilot-bale' ),
				)
			);
		}

		wp_send_json_success(
			array(
				'connected' => true,
				'message'   => __( 'اتصال بله تایید شد.', 'woopilot-bale' ),
			)
		);
	}

	public function send_otp(): void {
		$this->verify_nonce();

		$phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$result = $this->otp_manager->send_otp( $phone );

		if ( empty( $result['success'] ) ) {
			wp_send_json_error( array( 'message' => $result['message'] ?? __( 'ارسال کد ناموفق بود.', 'woopilot-bale' ) ) );
		}

		wp_send_json_success(
			array(
				'message'  => __( 'کد ورود در بله ارسال شد.', 'woopilot-bale' ),
				'nextStep' => 'otp',
			)
		);
	}

	public function verify(): void {
		$this->verify_nonce();

		$phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$otp   = isset( $_POST['otp'] ) ? sanitize_text_field( wp_unslash( $_POST['otp'] ) ) : '';

		$phone = $this->repository->normalize_phone( $phone );

		$result = $this->otp_manager->verify_otp( $phone, $otp );

		if ( empty( $result['success'] ) ) {
			wp_send_json_error( array( 'message' => $result['message'] ?? __( 'کد ورود معتبر نیست.', 'woopilot-bale' ) ) );
		}

		$user = $this->user_resolver->get_or_create_user_by_phone( $phone );

		if ( ! $user instanceof \WP_User ) {
			wp_send_json_error( array( 'message' => __( 'امکان ورود یا ساخت حساب کاربری وجود ندارد.', 'woopilot-bale' ) ) );
		}

		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID, true );

		wp_send_json_success(
			array(
				'message'  => __( 'ورود با موفقیت انجام شد.', 'woopilot-bale' ),
				'redirect' => wc_get_page_permalink( 'myaccount' ),
			)
		);
	}

	private function verify_nonce(): void {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'woopilot_bale_ajax_auth' ) ) {
			wp_send_json_error( array( 'message' => __( 'درخواست معتبر نیست.', 'woopilot-bale' ) ) );
		}
	}
}