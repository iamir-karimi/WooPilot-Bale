<?php

namespace WooPilot\Bale\Auth;

defined( 'ABSPATH' ) || exit;

final class UserResolver {

	public function get_or_create_user_by_phone( string $phone ): ?\WP_User {
		$phone = $this->normalize_phone( $phone );

		if ( empty( $phone ) ) {
			return null;
		}

		$user = $this->find_user_by_phone( $phone );

		if ( $user instanceof \WP_User ) {
			return $user;
		}

		return $this->create_customer( $phone );
	}

	private function find_user_by_phone( string $phone ): ?\WP_User {
		$users = get_users(
			array(
				'meta_key'   => 'billing_phone',
				'meta_value' => $phone,
				'number'     => 1,
				'fields'     => 'all',
			)
		);

		if ( ! empty( $users[0] ) && $users[0] instanceof \WP_User ) {
			return $users[0];
		}

		$users = get_users(
			array(
				'meta_key'   => 'woopilot_bale_phone',
				'meta_value' => $phone,
				'number'     => 1,
				'fields'     => 'all',
			)
		);

		if ( ! empty( $users[0] ) && $users[0] instanceof \WP_User ) {
			return $users[0];
		}

		return null;
	}

	private function create_customer( string $phone ): ?\WP_User {
		$username = 'user_' . $phone;
		$email    = $phone . '@woopilot.local';

		if ( username_exists( $username ) ) {
			$user = get_user_by( 'login', $username );

			return $user instanceof \WP_User ? $user : null;
		}

		$user_id = wp_insert_user(
			array(
				'user_login'   => $username,
				'user_pass'    => wp_generate_password( 32, true ),
				'user_email'   => $email,
				'display_name' => $phone,
				'role'         => 'customer',
			)
		);

		if ( is_wp_error( $user_id ) ) {
			return null;
		}

		update_user_meta( $user_id, 'billing_phone', $phone );
		update_user_meta( $user_id, 'woopilot_bale_phone', $phone );

		return get_user_by( 'id', $user_id );
	}

	private function normalize_phone( string $phone ): string {
		$phone = trim( $phone );
		$phone = preg_replace( '/[^0-9+]/', '', $phone );

		if ( str_starts_with( $phone, '+98' ) ) {
			$phone = '0' . substr( $phone, 3 );
		}

		if ( str_starts_with( $phone, '98' ) && 12 === strlen( $phone ) ) {
			$phone = '0' . substr( $phone, 2 );
		}

		if ( ! preg_match( '/^09[0-9]{9}$/', $phone ) ) {
			return '';
		}

		return $phone;
	}
}