<?php

namespace WooPilot\Bale\Auth;

defined( 'ABSPATH' ) || exit;

final class OtpRepository {

	private string $table;

	public function __construct() {
		global $wpdb;

		$this->table = $wpdb->prefix . 'woopilot_bale_otps';
	}

	public function create( string $phone, string $otp_hash, int $expires_in_minutes, string $ip_address = '' ): int {
		global $wpdb;

		$now        = current_time( 'mysql' );
		$expires_at = gmdate(
			'Y-m-d H:i:s',
			current_time( 'timestamp', true ) + ( $expires_in_minutes * MINUTE_IN_SECONDS )
		);

		$wpdb->insert(
			$this->table,
			array(
				'phone'      => $phone,
				'otp_hash'   => $otp_hash,
				'attempts'   => 0,
				'expires_at' => $expires_at,
				'used_at'    => null,
				'ip_address' => $ip_address,
				'created_at' => $now,
			),
			array(
				'%s',
				'%s',
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
			)
		);

		return absint( $wpdb->insert_id );
	}

	public function get_latest_active_by_phone( string $phone ): ?object {
		global $wpdb;

		$now = current_time( 'mysql', true );

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table}
				WHERE phone = %s
				AND used_at IS NULL
				AND expires_at >= %s
				ORDER BY id DESC
				LIMIT 1",
				$phone,
				$now
			)
		);

		return $row ?: null;
	}

	public function increment_attempts( int $id ): void {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$this->table}
				SET attempts = attempts + 1
				WHERE id = %d",
				$id
			)
		);
	}

	public function mark_used( int $id ): void {
		global $wpdb;

		$wpdb->update(
			$this->table,
			array(
				'used_at' => current_time( 'mysql' ),
			),
			array(
				'id' => $id,
			),
			array(
				'%s',
			),
			array(
				'%d',
			)
		);
	}
}