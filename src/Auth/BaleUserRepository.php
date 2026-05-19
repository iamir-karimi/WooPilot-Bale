<?php

namespace WooPilot\Bale\Auth;

defined( 'ABSPATH' ) || exit;

final class BaleUserRepository {

	private string $table;

	public function __construct() {
		global $wpdb;

		$this->table = $wpdb->prefix . 'woopilot_bale_users';

		$this->maybe_upgrade_table();
	}

	public function create_or_update_pending_connection(
		string $token,
		string $phone,
		string $bale_username = '',
		int $wp_user_id = 0
	): bool {
		global $wpdb;

		$token         = $this->sanitize_token( $token );
		$phone         = $this->normalize_phone( $phone );
		$bale_username = $this->normalize_username( $bale_username );
		$wp_user_id    = absint( $wp_user_id );

		if ( empty( $token ) || empty( $phone ) ) {
			return false;
		}

		$now = current_time( 'mysql' );

		$existing = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id FROM {$this->table}
				WHERE phone = %s
				ORDER BY id DESC
				LIMIT 1",
				$phone
			)
		);

		$data = array(
			'wp_user_id'    => $wp_user_id,
			'phone'         => $phone,
			'bale_chat_id'  => '',
			'bale_user_id'  => '',
			'bale_username' => $bale_username,
			'connect_token' => $token,
			'status'        => 'pending',
			'updated_at'    => $now,
		);

		$formats = array(
			'%d',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
		);

		if ( $existing ) {
			$result = $wpdb->update(
				$this->table,
				$data,
				array(
					'id' => absint( $existing->id ),
				),
				$formats,
				array(
					'%d',
				)
			);

			return false !== $result;
		}

		$data['created_at'] = $now;
		$formats[]          = '%s';

		$result = $wpdb->insert(
			$this->table,
			$data,
			$formats
		);

		return false !== $result;
	}

	public function create_pending_connection(
		string $token,
		string $phone = '',
		string $bale_username = '',
		int $wp_user_id = 0
	): void {
		$this->create_or_update_pending_connection(
			$token,
			$phone,
			$bale_username,
			$wp_user_id
		);
	}

	public function complete_connection_by_token(
		string $token,
		string $bale_chat_id,
		string $bale_user_id = '',
		string $bale_username = ''
	): array {
		global $wpdb;

		$token         = $this->sanitize_token( $token );
		$bale_chat_id  = sanitize_text_field( $bale_chat_id );
		$bale_user_id  = sanitize_text_field( $bale_user_id );
		$bale_username = $this->normalize_username( $bale_username );

		if ( empty( $token ) || empty( $bale_chat_id ) ) {
			return array(
				'success' => false,
				'message' => __( 'اطلاعات اتصال کامل نیست.', 'woopilot-bale' ),
				'row'     => null,
			);
		}

		$row = $this->find_pending_by_token( $token );

		if ( ! $row ) {
			return array(
				'success' => false,
				'message' => __( 'شناسه اتصال معتبر نیست یا قبلاً استفاده شده است.', 'woopilot-bale' ),
				'row'     => null,
			);
		}

		if ( empty( $row->phone ) ) {
			return array(
				'success' => false,
				'message' => __( 'شماره موبایل برای این شناسه اتصال ذخیره نشده است.', 'woopilot-bale' ),
				'row'     => $row,
			);
		}

		$updated = $wpdb->update(
			$this->table,
			array(
				'bale_chat_id'  => $bale_chat_id,
				'bale_user_id'  => $bale_user_id,
				'bale_username' => ! empty( $bale_username ) ? $bale_username : (string) $row->bale_username,
				'connect_token' => '',
				'status'        => 'active',
				'updated_at'    => current_time( 'mysql' ),
			),
			array(
				'id' => absint( $row->id ),
			),
			array(
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
			),
			array(
				'%d',
			)
		);

		if ( false === $updated ) {
			$error_message = __( 'ذخیره اتصال در دیتابیس ناموفق بود.', 'woopilot-bale' );

			if ( ! empty( $wpdb->last_error ) ) {
				$error_message .= ' ' . $wpdb->last_error;
			}

			return array(
				'success' => false,
				'message' => $error_message,
				'row'     => $row,
			);
		}

		$row->bale_chat_id  = $bale_chat_id;
		$row->bale_user_id  = $bale_user_id;
		$row->bale_username = ! empty( $bale_username ) ? $bale_username : (string) $row->bale_username;
		$row->status        = 'active';

		return array(
			'success' => true,
			'message' => __( 'حساب بله با موفقیت متصل شد.', 'woopilot-bale' ),
			'row'     => $row,
		);
	}

	public function find_pending_by_token( string $token ): ?object {
		global $wpdb;

		$token = $this->sanitize_token( $token );

		if ( empty( $token ) ) {
			return null;
		}

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table}
				WHERE connect_token = %s
				AND status = %s
				ORDER BY id DESC
				LIMIT 1",
				$token,
				'pending'
			)
		);

		return $row ?: null;
	}

	public function find_by_phone( string $phone, bool $active_only = true ): ?object {
		global $wpdb;

		$phone = $this->normalize_phone( $phone );

		if ( empty( $phone ) ) {
			return null;
		}

		if ( $active_only ) {
			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$this->table}
					WHERE phone = %s
					AND status = %s
					ORDER BY id DESC
					LIMIT 1",
					$phone,
					'active'
				)
			);
		} else {
			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$this->table}
					WHERE phone = %s
					ORDER BY id DESC
					LIMIT 1",
					$phone
				)
			);
		}

		return $row ?: null;
	}

	public function find_by_chat_id( string $chat_id, bool $active_only = true ): ?object {
		global $wpdb;

		$chat_id = sanitize_text_field( $chat_id );

		if ( empty( $chat_id ) ) {
			return null;
		}

		if ( $active_only ) {
			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$this->table}
					WHERE bale_chat_id = %s
					AND status = %s
					ORDER BY id DESC
					LIMIT 1",
					$chat_id,
					'active'
				)
			);
		} else {
			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$this->table}
					WHERE bale_chat_id = %s
					ORDER BY id DESC
					LIMIT 1",
					$chat_id
				)
			);
		}

		return $row ?: null;
	}

	public function find_by_username( string $username, bool $active_only = true ): ?object {
		global $wpdb;

		$username = $this->normalize_username( $username );

		if ( empty( $username ) ) {
			return null;
		}

		if ( $active_only ) {
			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$this->table}
					WHERE bale_username = %s
					AND status = %s
					ORDER BY id DESC
					LIMIT 1",
					$username,
					'active'
				)
			);
		} else {
			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$this->table}
					WHERE bale_username = %s
					ORDER BY id DESC
					LIMIT 1",
					$username
				)
			);
		}

		return $row ?: null;
	}

	public function upsert(
		string $phone,
		string $bale_chat_id,
		int $wp_user_id = 0,
		string $bale_username = '',
		string $bale_user_id = ''
	): bool {
		global $wpdb;

		$phone         = $this->normalize_phone( $phone );
		$bale_chat_id  = sanitize_text_field( $bale_chat_id );
		$wp_user_id    = absint( $wp_user_id );
		$bale_username = $this->normalize_username( $bale_username );
		$bale_user_id  = sanitize_text_field( $bale_user_id );

		if ( empty( $phone ) || empty( $bale_chat_id ) ) {
			return false;
		}

		$existing = $this->find_by_phone( $phone, false );
		$now      = current_time( 'mysql' );

		$data = array(
			'wp_user_id'    => $wp_user_id,
			'phone'         => $phone,
			'bale_chat_id'  => $bale_chat_id,
			'bale_user_id'  => $bale_user_id,
			'bale_username' => $bale_username,
			'connect_token' => '',
			'status'        => 'active',
			'updated_at'    => $now,
		);

		$formats = array(
			'%d',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
		);

		if ( $existing ) {
			$result = $wpdb->update(
				$this->table,
				$data,
				array(
					'id' => absint( $existing->id ),
				),
				$formats,
				array(
					'%d',
				)
			);

			return false !== $result;
		}

		$data['created_at'] = $now;
		$formats[]          = '%s';

		$result = $wpdb->insert(
			$this->table,
			$data,
			$formats
		);

		return false !== $result;
	}

	public function touch_otp_sent( string $phone ): bool {
		global $wpdb;

		$phone = $this->normalize_phone( $phone );

		if ( empty( $phone ) ) {
			return false;
		}

		$this->maybe_upgrade_table();

		$result = $wpdb->update(
			$this->table,
			array(
				'last_otp_sent_at' => current_time( 'mysql' ),
				'updated_at'       => current_time( 'mysql' ),
			),
			array(
				'phone' => $phone,
			),
			array(
				'%s',
				'%s',
			),
			array(
				'%s',
			)
		);

		return false !== $result;
	}

	private function maybe_upgrade_table(): void {
		global $wpdb;

		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $this->table ) );

		if ( $exists !== $this->table ) {
			return;
		}

		$columns = $wpdb->get_col( "SHOW COLUMNS FROM {$this->table}", 0 );

		if ( ! is_array( $columns ) ) {
			return;
		}

		$schema = array(
			'bale_user_id'     => "ALTER TABLE {$this->table} ADD bale_user_id varchar(191) NOT NULL DEFAULT ''",
			'bale_username'    => "ALTER TABLE {$this->table} ADD bale_username varchar(191) NOT NULL DEFAULT ''",
			'connect_token'    => "ALTER TABLE {$this->table} ADD connect_token varchar(191) NOT NULL DEFAULT ''",
			'status'           => "ALTER TABLE {$this->table} ADD status varchar(30) NOT NULL DEFAULT 'pending'",
			'created_at'       => "ALTER TABLE {$this->table} ADD created_at datetime NULL",
			'updated_at'       => "ALTER TABLE {$this->table} ADD updated_at datetime NULL",
			'last_otp_sent_at' => "ALTER TABLE {$this->table} ADD last_otp_sent_at datetime NULL",
		);

		foreach ( $schema as $column => $sql ) {
			if ( ! in_array( $column, $columns, true ) ) {
				$wpdb->query( $sql );
			}
		}
	}


	public function normalize_phone( string $phone ): string {
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

	public function normalize_username( string $username ): string {
		$username = trim( $username );
		$username = ltrim( $username, '@' );

		return sanitize_user( $username, true );
	}

	private function sanitize_token( string $token ): string {
		return preg_replace( '/[^A-Za-z0-9]/', '', trim( $token ) );
	}
}