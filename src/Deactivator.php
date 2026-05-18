<?php

namespace WooPilot\Bale;

defined( 'ABSPATH' ) || exit;

final class Deactivator {

	public static function deactivate(): void {
		wp_clear_scheduled_hook( 'woopilot_bale_process_queue' );
		wp_clear_scheduled_hook( 'woopilot_bale_check_payment_reminders' );

		flush_rewrite_rules();
	}
}