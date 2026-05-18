<?php

namespace WooPilot\Bale;

use WooPilot\Bale\Messaging\TemplateDefaults;

defined( 'ABSPATH' ) || exit;

final class Activator {

	public static function activate(): void {
		self::create_tables();
		self::add_default_options();

		flush_rewrite_rules();
	}

	private static function create_tables(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate  = $wpdb->get_charset_collate();
		$bale_users_table = $wpdb->prefix . 'woopilot_bale_users';
		$otps_table       = $wpdb->prefix . 'woopilot_bale_otps';
		$queue_table      = $wpdb->prefix . 'woopilot_bale_queue';
		$logs_table       = $wpdb->prefix . 'woopilot_bale_logs';

		$sql_bale_users = "CREATE TABLE {$bale_users_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			wp_user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			phone VARCHAR(30) NOT NULL,
			bale_chat_id VARCHAR(191) NULL,
			bale_user_id VARCHAR(191) NULL,
			bale_username VARCHAR(191) NULL,
			connect_token VARCHAR(191) NULL,
			token_expires_at DATETIME NULL,
			connected_at DATETIME NULL,
			last_otp_sent_at DATETIME NULL,
			status VARCHAR(30) NOT NULL DEFAULT 'pending',
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY phone (phone),
			UNIQUE KEY connect_token (connect_token),
			KEY wp_user_id (wp_user_id),
			KEY bale_chat_id (bale_chat_id),
			KEY bale_user_id (bale_user_id),
			KEY bale_username (bale_username),
			KEY status (status),
			KEY token_expires_at (token_expires_at)
		) {$charset_collate};";

		$sql_otps = "CREATE TABLE {$otps_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			phone VARCHAR(30) NOT NULL,
			otp_hash VARCHAR(255) NOT NULL,
			attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
			expires_at DATETIME NOT NULL,
			used_at DATETIME NULL,
			ip_address VARCHAR(100) NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY phone (phone),
			KEY expires_at (expires_at),
			KEY used_at (used_at)
		) {$charset_collate};";

		$sql_queue = "CREATE TABLE {$queue_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			recipient_type VARCHAR(50) NOT NULL,
			recipient_id VARCHAR(191) NOT NULL,
			message_type VARCHAR(100) NOT NULL,
			message_body LONGTEXT NOT NULL,
			order_id BIGINT UNSIGNED NULL,
			status VARCHAR(30) NOT NULL DEFAULT 'pending',
			attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
			scheduled_at DATETIME NULL,
			sent_at DATETIME NULL,
			last_error TEXT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY status (status),
			KEY order_id (order_id),
			KEY scheduled_at (scheduled_at)
		) {$charset_collate};";

		$sql_logs = "CREATE TABLE {$logs_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			level VARCHAR(30) NOT NULL,
			context VARCHAR(100) NULL,
			message TEXT NOT NULL,
			request_data LONGTEXT NULL,
			response_data LONGTEXT NULL,
			order_id BIGINT UNSIGNED NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY level (level),
			KEY order_id (order_id),
			KEY created_at (created_at)
		) {$charset_collate};";

		dbDelta( $sql_bale_users );
		dbDelta( $sql_otps );
		dbDelta( $sql_queue );
		dbDelta( $sql_logs );
	}

	private static function add_default_options(): void {
		$defaults = array(
			'woopilot_bale_enable_otp_login'              => 'yes',
			'woopilot_bale_enable_bale_connect'           => 'yes',
			'woopilot_bale_otp_length'                    => 5,
			'woopilot_bale_otp_expiration_minutes'        => 5,
			'woopilot_bale_otp_max_attempts'              => 5,
			'woopilot_bale_connect_token_expiration_hours'=> 24,
			'woopilot_bale_bot_token'                     => '',
			'woopilot_bale_admin_ids'                     => '',
			'woopilot_bale_group_id'                      => '',
			'woopilot_bale_enable_admin_notifications'    => 'yes',
			'woopilot_bale_enable_customer_notifications' => 'yes',
			'woopilot_bale_low_stock_threshold'           => 5,
			'woopilot_bale_payment_reminder_minutes'      => 30,
			'woopilot_bale_debug_mode'                    => 'no',
			'woopilot_bale_retry_limit'                   => 3,
			'woopilot_bale_bot_username'                  =>   '',
		);

		$defaults = array_merge( $defaults, TemplateDefaults::all() );

		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( $key ) ) {
				add_option( $key, $value );
			}
		}
	}
}