<?php

namespace WooPilot\Bale\Auth;

defined( 'ABSPATH' ) || exit;

final class BaleConnect {

	private BaleUserRepository $repository;

	public function __construct() {
		$this->repository = new BaleUserRepository();
	}

	public function create_connect_token( string $phone, string $bale_username = '', int $wp_user_id = 0 ): string {
		$phone = $this->normalize_phone( $phone );

		if ( empty( $phone ) ) {
			return '';
		}

		$token = $this->generate_token();

		$created = $this->repository->create_or_update_pending_connection(
			$token,
			$phone,
			$this->normalize_username( $bale_username ),
			$wp_user_id
		);

		return $created ? $token : '';
	}

	public function normalize_username( string $username ): string {
		return $this->repository->normalize_username( $username );
	}

	public function normalize_phone( string $phone ): string {
		return $this->repository->normalize_phone( $phone );
	}

	private function generate_token(): string {
		return wp_generate_password( 32, false, false );
	}
}