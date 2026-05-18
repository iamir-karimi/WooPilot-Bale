<?php

namespace WooPilot\Bale\Auth;

use WooPilot\Bale\Admin\LoginCustomizer;

defined( 'ABSPATH' ) || exit;

final class OtpLogin {

	public function register_shortcode(): void {
		add_shortcode(
			'woopilot_bale_otp_login',
			array( $this, 'render_shortcode' )
		);
	}

	public function handle_request(): void {
		return;
	}

	public function render_shortcode(): string {
		if ( is_user_logged_in() ) {
			return '<div class="woopilot-auth-shell"><div class="woopilot-auth-card"><div class="woopilot-auth-title">' . esc_html__( 'شما وارد حساب کاربری شده‌اید.', 'woopilot-bale' ) . '</div></div></div>';
		}

		$logo_url = class_exists( LoginCustomizer::class ) ? LoginCustomizer::get_logo_url() : '';
		$title    = class_exists( LoginCustomizer::class ) ? LoginCustomizer::get_title() : __( 'ورود / ثبت نام', 'woopilot-bale' );
		$subtitle = class_exists( LoginCustomizer::class ) ? LoginCustomizer::get_subtitle() : __( 'برای ورود، شماره موبایل خود را وارد کنید.', 'woopilot-bale' );
		$footer   = class_exists( LoginCustomizer::class ) ? LoginCustomizer::get_footer_text() : __( 'با ورود یا ثبت‌نام، اتصال امن حساب شما با بله انجام می‌شود.', 'woopilot-bale' );

		ob_start();
		?>
		<div class="woopilot-auth-shell">
			<div class="woopilot-auth-card">
				<div class="woopilot-auth-logo">
					<?php if ( ! empty( $logo_url ) ) : ?>
						<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
					<?php else : ?>
						<span><?php echo esc_html__( 'WooPilot Bale', 'woopilot-bale' ); ?></span>
					<?php endif; ?>
				</div>

				<div class="woopilot-auth-title"><?php echo esc_html( $title ); ?></div>
				<p class="woopilot-auth-subtitle"><?php echo esc_html( $subtitle ); ?></p>

				<div class="woopilot-auth-notice"></div>

				<div class="woopilot-auth-step is-active" data-step="phone">
					<form class="woopilot-auth-phone-form">
						<div class="woopilot-auth-field">
							<input type="text" name="phone" placeholder="<?php echo esc_attr__( 'شماره موبایل', 'woopilot-bale' ); ?>" required>
						</div>

						<div class="woopilot-auth-field">
							<input type="text" name="username" placeholder="<?php echo esc_attr__( 'آیدی بله اختیاری', 'woopilot-bale' ); ?>">
						</div>

						<button type="submit" class="woopilot-auth-button"><?php echo esc_html__( 'ادامه', 'woopilot-bale' ); ?></button>
					</form>
				</div>

				<div class="woopilot-auth-step" data-step="connect">
					<p class="woopilot-auth-help"><?php echo esc_html__( 'ربات بله را باز کنید و دستور زیر را داخل ربات ارسال کنید.', 'woopilot-bale' ); ?></p>
					<code class="woopilot-auth-command"></code>
					<a href="#" target="_blank" rel="noopener noreferrer" class="woopilot-auth-bot-link"><?php echo esc_html__( 'باز کردن ربات بله', 'woopilot-bale' ); ?></a>
					<button type="button" class="woopilot-auth-button secondary woopilot-auth-check-connection"><?php echo esc_html__( 'اتصال را بررسی کن', 'woopilot-bale' ); ?></button>
					<button type="button" class="woopilot-auth-button woopilot-auth-send-otp"><?php echo esc_html__( 'اتصال انجام شد، ارسال کد ورود', 'woopilot-bale' ); ?></button>
				</div>

				<div class="woopilot-auth-step" data-step="otp">
					<form class="woopilot-auth-otp-form">
						<div class="woopilot-auth-field">
							<input type="text" name="otp" placeholder="<?php echo esc_attr__( 'کد ورود', 'woopilot-bale' ); ?>" required>
						</div>
						<button type="submit" class="woopilot-auth-button"><?php echo esc_html__( 'ورود / ثبت نام', 'woopilot-bale' ); ?></button>
					</form>
				</div>

				<div class="woopilot-auth-help"><?php echo esc_html( $footer ); ?></div>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}
}
