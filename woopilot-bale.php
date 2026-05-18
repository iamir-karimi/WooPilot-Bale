<?php
/**
 * Plugin Name: WooPilot Bale Notifications
 * Plugin URI:  https://github.com/iamir-karimi/WooPilot-Bale
 * Description: Professional WooCommerce integration with Bale Messenger for order notifications, automation, queue, and logging.
 * Version:     1.0.0
 * Author:      iamir
 * Author URI:  https://iamirs.ir
 * Text Domain: woopilot-bale
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * WC requires at least: 6.0
 * WC tested up to: 9.0
 *
 * @package WooPilot_Bale
 */

defined( 'ABSPATH' ) || exit;

define( 'WOOPILOT_BALE_VERSION', '1.0.0' );
define( 'WOOPILOT_BALE_FILE', __FILE__ );
define( 'WOOPILOT_BALE_PATH', plugin_dir_path( __FILE__ ) );
define( 'WOOPILOT_BALE_URL', plugin_dir_url( __FILE__ ) );
define( 'WOOPILOT_BALE_BASENAME', plugin_basename( __FILE__ ) );

$woopilot_bale_autoload = WOOPILOT_BALE_PATH . 'vendor/autoload.php';

if ( file_exists( $woopilot_bale_autoload ) ) {
	require_once $woopilot_bale_autoload;
} else {
	add_action(
		'admin_notices',
		static function () {
			echo '<div class="notice notice-error"><p>';
			echo esc_html__( 'WooPilot Bale Notifications requires Composer autoload. Please run composer dump-autoload.', 'woopilot-bale' );
			echo '</p></div>';
		}
	);

	return;
}

register_activation_hook(
	__FILE__,
	static function () {
		\WooPilot\Bale\Activator::activate();
	}
);

register_deactivation_hook(
	__FILE__,
	static function () {
		\WooPilot\Bale\Deactivator::deactivate();
	}
);

add_action(
	'plugins_loaded',
	static function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action(
				'admin_notices',
				static function () {
					echo '<div class="notice notice-error"><p>';
					echo esc_html__( 'WooPilot Bale Notifications requires WooCommerce to be installed and active.', 'woopilot-bale' );
					echo '</p></div>';
				}
			);

			return;
		}

		$plugin = new \WooPilot\Bale\Plugin();
		$plugin->run();
	}
);
