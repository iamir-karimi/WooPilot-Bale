<?php

namespace WooPilot\Bale\Api;

defined( 'ABSPATH' ) || exit;

final class BaleApi {

	private string $token;

	private string $base_url = 'https://tapi.bale.ai/bot';

	public function __construct( string $token = '' ) {
		$this->token = trim( $token );
	}

	public function test_connection(): array {
		return $this->request( 'getMe' );
	}

	public function set_webhook( string $url ): array {
		return $this->request(
			'setWebhook',
			array(
				'url' => esc_url_raw( $url ),
			)
		);
	}

	public function delete_webhook(): array {
		return $this->request( 'deleteWebhook' );
	}

	public function send_message( string $chat_id, string $message, array $reply_markup = array() ): array {
		$body = array(
			'chat_id' => $chat_id,
			'text'    => $message,
		);

		if ( ! empty( $reply_markup ) ) {
			$body['reply_markup'] = $reply_markup;
		}

		return $this->request( 'sendMessage', $body );
	}

	public function send_photo( string $chat_id, string $photo_url, string $caption = '', array $reply_markup = array() ): array {
		$body = array(
			'chat_id' => $chat_id,
			'photo'   => esc_url_raw( $photo_url ),
		);

		if ( '' !== trim( $caption ) ) {
			$body['caption'] = wp_strip_all_tags( $caption );
		}

		if ( ! empty( $reply_markup ) ) {
			$body['reply_markup'] = $reply_markup;
		}

		return $this->request( 'sendPhoto', $body );
	}


	private function request( string $method, array $body = array() ): array {
		if ( empty( $this->token ) ) {
			return array(
				'success' => false,
				'message' => __( 'توکن ربات وارد نشده است.', 'woopilot-bale' ),
				'data'    => array(),
			);
		}

		$url = esc_url_raw( $this->base_url . $this->token . '/' . $method );

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 30,
				'headers' => array(
					'Content-Type' => 'application/json; charset=utf-8',
				),
				'body' => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => $response->get_error_message(),
				'data'    => array(),
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$raw_body    = wp_remote_retrieve_body( $response );
		$decoded     = json_decode( $raw_body, true );

		if ( 'yes' === get_option( 'woopilot_bale_debug_mode', 'no' ) ) {
			error_log( 'WOOPILOT BALE API METHOD: ' . $method );
			error_log( 'WOOPILOT BALE API BODY: ' . print_r( $body, true ) );
			error_log( 'WOOPILOT BALE API STATUS: ' . $status_code );
			error_log( 'WOOPILOT BALE API RESPONSE: ' . $raw_body );
		}

		if ( ! is_array( $decoded ) ) {
			return array(
				'success' => false,
				'message' => __( 'پاسخ بله معتبر نیست.', 'woopilot-bale' ),
				'data'    => array(
					'status_code' => $status_code,
					'raw_body'    => $raw_body,
				),
			);
		}

		return array(
			'success' => ! empty( $decoded['ok'] ),
			'message' => ! empty( $decoded['ok'] )
				? __( 'درخواست موفق بود.', 'woopilot-bale' )
				: __( 'درخواست به بله ناموفق بود.', 'woopilot-bale' ),
			'data'    => $decoded,
		);
	}
}