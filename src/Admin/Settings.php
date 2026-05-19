<?php

namespace WooPilot\Bale\Admin;

use WooPilot\Bale\Api\BaleApi;
use WooPilot\Bale\Messaging\TemplateDefaults;

defined( 'ABSPATH' ) || exit;

final class Settings {

	private const OPTION_GROUP = 'woopilot_bale_settings';

	public function register_settings(): void {
		$options = array(
			'woopilot_bale_bot_token'                       => array( $this, 'sanitize_token' ),
			'woopilot_bale_bot_username'                    => array( $this, 'sanitize_bot_username' ),
			'woopilot_bale_webhook_secret'                  => array( $this, 'sanitize_token' ),
			'woopilot_bale_admin_ids'                       => array( $this, 'sanitize_admin_ids' ),
			'woopilot_bale_group_id'                        => array( $this, 'sanitize_text_preserve' ),

			'woopilot_bale_enable_admin_notifications'      => array( $this, 'sanitize_yes_no' ),
			'woopilot_bale_enable_customer_notifications'   => array( $this, 'sanitize_yes_no' ),

			'woopilot_bale_enable_sales_report_notification'=> array( $this, 'sanitize_yes_no' ),
			'woopilot_bale_sales_report_period'             => array( $this, 'sanitize_report_period' ),
			'woopilot_bale_sales_report_send_time'          => array( $this, 'sanitize_time' ),

			'woopilot_bale_enable_otp_login'                => array( $this, 'sanitize_yes_no' ),
			'woopilot_bale_enable_bale_connect'             => array( $this, 'sanitize_yes_no' ),

			'woopilot_bale_direct_sales_enabled'            => array( $this, 'sanitize_yes_no' ),
			'woopilot_bale_direct_sales_welcome_message'    => array( $this, 'sanitize_template' ),
			'woopilot_bale_direct_sales_welcome_image_url'  => array( $this, 'sanitize_url' ),
			'woopilot_bale_direct_sales_about_text'         => array( $this, 'sanitize_template' ),
			'woopilot_bale_direct_sales_support_text'       => array( $this, 'sanitize_template' ),
			'woopilot_bale_direct_sales_buttons'            => array( $this, 'sanitize_direct_sales_buttons' ),
			'woopilot_bale_direct_sales_shop_intro_text'    => array( $this, 'sanitize_template' ),
			'woopilot_bale_direct_sales_shop_section_1_label'=> array( $this, 'sanitize_text_preserve' ),
			'woopilot_bale_direct_sales_shop_section_1_ids'  => array( $this, 'sanitize_product_ids' ),
			'woopilot_bale_direct_sales_shop_section_2_label'=> array( $this, 'sanitize_text_preserve' ),
			'woopilot_bale_direct_sales_shop_section_2_ids'  => array( $this, 'sanitize_product_ids' ),
			'woopilot_bale_direct_sales_shop_section_3_label'=> array( $this, 'sanitize_text_preserve' ),
			'woopilot_bale_direct_sales_shop_section_3_ids'  => array( $this, 'sanitize_product_ids' ),

			'woopilot_bale_auth_logo_id'                    => array( $this, 'sanitize_number' ),
			'woopilot_bale_auth_title'                      => array( $this, 'sanitize_text_preserve' ),
			'woopilot_bale_auth_subtitle'                   => array( $this, 'sanitize_text_preserve' ),
			'woopilot_bale_auth_footer'                     => array( $this, 'sanitize_text_preserve' ),
			'woopilot_bale_auth_primary_color'              => array( $this, 'sanitize_color' ),
			'woopilot_bale_auth_primary_hover_color'        => array( $this, 'sanitize_color' ),
			'woopilot_bale_auth_text_color'                 => array( $this, 'sanitize_color' ),
			'woopilot_bale_auth_background_color'           => array( $this, 'sanitize_color' ),
			'woopilot_bale_auth_card_color'                 => array( $this, 'sanitize_color' ),
			'woopilot_bale_auth_input_color'                => array( $this, 'sanitize_color' ),
			'woopilot_bale_auth_border_color'               => array( $this, 'sanitize_color' ),
			'woopilot_bale_auth_card_radius'                => array( $this, 'sanitize_number' ),
			'woopilot_bale_auth_input_radius'               => array( $this, 'sanitize_number' ),
			'woopilot_bale_auth_button_radius'              => array( $this, 'sanitize_number' ),
			'woopilot_bale_auth_card_width'                 => array( $this, 'sanitize_number' ),
			'woopilot_bale_auth_card_padding'               => array( $this, 'sanitize_number' ),

			'woopilot_bale_debug_mode'                      => array( $this, 'sanitize_yes_no' ),
			'woopilot_bale_low_stock_threshold'             => array( $this, 'sanitize_number' ),
			'woopilot_bale_payment_reminder_minutes'        => array( $this, 'sanitize_number' ),
			'woopilot_bale_retry_limit'                     => array( $this, 'sanitize_number' ),

			'woopilot_bale_template_admin_new_order'          => array( $this, 'sanitize_template' ),
			'woopilot_bale_template_customer_order_confirmed' => array( $this, 'sanitize_template' ),
			'woopilot_bale_template_payment_success'          => array( $this, 'sanitize_template' ),
			'woopilot_bale_template_payment_failed'           => array( $this, 'sanitize_template' ),
			'woopilot_bale_template_payment_reminder'         => array( $this, 'sanitize_template' ),
			'woopilot_bale_template_order_processing'         => array( $this, 'sanitize_template' ),
			'woopilot_bale_template_order_completed'          => array( $this, 'sanitize_template' ),
			'woopilot_bale_template_order_cancelled'          => array( $this, 'sanitize_template' ),
			'woopilot_bale_template_low_stock'                => array( $this, 'sanitize_template' ),
		);

		foreach ( $this->get_direct_sales_default_buttons() as $key => $label ) {
			$options[ 'woopilot_bale_direct_sales_button_' . $key ] = array( $this, 'sanitize_text_preserve' );
		}

		foreach ( $options as $option_name => $sanitize_callback ) {
			register_setting(
				self::OPTION_GROUP,
				$option_name,
				array(
					'type'              => is_array( $sanitize_callback ) && 'sanitize_direct_sales_buttons' === $sanitize_callback[1] ? 'array' : 'string',
					'sanitize_callback' => $sanitize_callback,
				)
			);
		}

		add_action( 'update_option_woopilot_bale_enable_sales_report_notification', array( $this, 'reschedule_sales_report_cron' ), 10, 3 );
		add_action( 'update_option_woopilot_bale_sales_report_period', array( $this, 'reschedule_sales_report_cron' ), 10, 3 );
		add_action( 'update_option_woopilot_bale_sales_report_send_time', array( $this, 'reschedule_sales_report_cron' ), 10, 3 );
	}

	public function handle_test_connection(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'شما اجازه دسترسی به این بخش را ندارید.', 'woopilot-bale' ) );
		}

		check_admin_referer( 'woopilot_bale_test_connection' );

		$api    = new BaleApi( get_option( 'woopilot_bale_bot_token', '' ) );
		$result = $api->test_connection();

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'                       => 'woopilot-bale',
					'tab'                        => 'bot',
					'woopilot_bale_test_status'  => $result['success'] ? 'success' : 'error',
					'woopilot_bale_test_message' => rawurlencode( $result['message'] ),
				),
				admin_url( 'admin.php' )
			)
		);

		exit;
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'شما اجازه دسترسی به این بخش را ندارید.', 'woopilot-bale' ) );
		}

		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'bot';
		$tabs       = $this->get_tabs();

		if ( ! isset( $tabs[ $active_tab ] ) ) {
			$active_tab = 'bot';
		}
		?>
		<div class="wrap woopilot-bale-wrap">
			<div class="woopilot-bale-panel">
				<div class="woopilot-bale-header">
					<div>
						<h1><?php echo esc_html__( 'اعلان‌های بله ووکامرس', 'woopilot-bale' ); ?></h1>
						<p><?php echo esc_html__( 'مدیریت ارسال پیام‌های سفارش، پرداخت، موجودی، گزارش فروش، ورود با بله و فروش مستقیم', 'woopilot-bale' ); ?></p>
					</div>

					<span class="woopilot-bale-version">
						<?php echo esc_html( 'v' . WOOPILOT_BALE_VERSION ); ?>
					</span>
				</div>

				<?php $this->render_notice(); ?>

				<div class="woopilot-bale-body">
					<aside class="woopilot-bale-sidebar">
						<?php foreach ( $tabs as $tab_key => $tab ) : ?>
							<a
								href="<?php echo esc_url( admin_url( 'admin.php?page=woopilot-bale&tab=' . $tab_key ) ); ?>"
								class="woopilot-bale-tab <?php echo $active_tab === $tab_key ? 'is-active' : ''; ?>"
							>
								<span class="dashicons <?php echo esc_attr( $tab['icon'] ); ?>"></span>
								<span><?php echo esc_html( $tab['label'] ); ?></span>
							</a>
						<?php endforeach; ?>
					</aside>

					<main class="woopilot-bale-content">
						<form method="post" action="options.php">
							<?php settings_fields( self::OPTION_GROUP ); ?>
							<input type="hidden" name="woopilot_bale_active_tab" value="<?php echo esc_attr( $active_tab ); ?>">

							<?php
							switch ( $active_tab ) {
								case 'bot':
									$this->render_bot_tab();
									break;

								case 'notifications':
									$this->render_notifications_tab();
									break;

								case 'reports':
									$this->render_reports_tab();
									break;

								case 'direct_sales':
									$this->render_direct_sales_tab();
									break;

								case 'login_customizer':
									$this->render_login_customizer_tab();
									break;

								case 'templates':
									$this->render_templates_tab();
									break;

								case 'automation':
									$this->render_automation_tab();
									break;

								case 'inventory':
									$this->render_inventory_tab();
									break;

								case 'logs':
									$this->render_logs_tab();
									break;

								case 'advanced':
									$this->render_advanced_tab();
									break;
							}
							?>

							<?php if ( 'logs' !== $active_tab && 'reports' !== $active_tab ) : ?>
								<div class="woopilot-bale-footer">
									<?php submit_button( esc_html__( 'ذخیره تنظیمات', 'woopilot-bale' ), 'primary', 'submit', false ); ?>
								</div>
							<?php endif; ?>

							<?php if ( 'reports' === $active_tab ) : ?>
								<div class="woopilot-bale-footer">
									<?php submit_button( esc_html__( 'ذخیره تنظیمات اعلان گزارش', 'woopilot-bale' ), 'primary', 'submit', false ); ?>
								</div>
							<?php endif; ?>
						</form>
					</main>
				</div>
			</div>

			<form id="woopilot-bale-test-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:none;">
				<?php wp_nonce_field( 'woopilot_bale_test_connection' ); ?>
				<input type="hidden" name="action" value="woopilot_bale_test_connection">
			</form>

			<form id="woopilot-bale-webhook-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:none;">
				<?php wp_nonce_field( 'woopilot_bale_set_webhook' ); ?>
				<input type="hidden" name="action" value="woopilot_bale_set_webhook">
			</form>
		</div>
		<?php
	}

	private function get_tabs(): array {
		return array(
			'bot'           => array(
				'label' => __( 'تنظیمات ربات', 'woopilot-bale' ),
				'icon'  => 'dashicons-admin-generic',
			),
			'notifications' => array(
				'label' => __( 'اعلان‌ها', 'woopilot-bale' ),
				'icon'  => 'dashicons-megaphone',
			),
			'reports'       => array(
				'label' => __( 'گزارش فروش', 'woopilot-bale' ),
				'icon'  => 'dashicons-chart-bar',
			),
			'direct_sales'  => array(
				'label' => __( 'فروش مستقیم', 'woopilot-bale' ),
				'icon'  => 'dashicons-store',
			),
			'login_customizer' => array(
				'label' => __( 'سفارشی‌سازی لاگین', 'woopilot-bale' ),
				'icon'  => 'dashicons-admin-appearance',
			),
			'templates'     => array(
				'label' => __( 'قالب پیام‌ها', 'woopilot-bale' ),
				'icon'  => 'dashicons-editor-alignright',
			),
			'automation'    => array(
				'label' => __( 'اتوماسیون', 'woopilot-bale' ),
				'icon'  => 'dashicons-controls-repeat',
			),
			'inventory'     => array(
				'label' => __( 'موجودی کالا', 'woopilot-bale' ),
				'icon'  => 'dashicons-products',
			),
			'logs'          => array(
				'label' => __( 'لاگ‌ها', 'woopilot-bale' ),
				'icon'  => 'dashicons-list-view',
			),
			'advanced'      => array(
				'label' => __( 'پیشرفته', 'woopilot-bale' ),
				'icon'  => 'dashicons-admin-tools',
			),
		);
	}

	private function render_bot_tab(): void {
		?>
		<section class="woopilot-bale-section">
			<div class="woopilot-bale-section-title">
				<h2><?php echo esc_html__( 'تنظیمات اتصال به بله', 'woopilot-bale' ); ?></h2>
				<p><?php echo esc_html__( 'برای ارسال پیام، ابتدا توکن ربات، آیدی ربات و شناسه دریافت‌کنندگان را وارد کنید.', 'woopilot-bale' ); ?></p>
			</div>

			<?php
			$this->field_password(
				'woopilot_bale_bot_token',
				__( 'توکن ربات', 'woopilot-bale' ),
				__( 'توکن دریافتی از BotFather بله را وارد کنید.', 'woopilot-bale' )
			);

			$this->field_text(
				'woopilot_bale_bot_username',
				__( 'آیدی ربات بله', 'woopilot-bale' ),
				__( 'آیدی ربات را بدون @ وارد کنید. مثال: my_shop_bot', 'woopilot-bale' )
			);

			$this->field_text(
				'woopilot_bale_webhook_secret',
				__( 'Webhook Secret', 'woopilot-bale' ),
				__( 'یک کلید امنیتی برای اعتبارسنجی webhook بله وارد کنید. مثال: woopilot2026', 'woopilot-bale' )
			);

			$this->field_textarea(
				'woopilot_bale_admin_ids',
				__( 'شناسه مدیران', 'woopilot-bale' ),
				__( 'شناسه عددی مدیران را با ویرگول جدا کنید. مثال: 123456,987654', 'woopilot-bale' )
			);

			$this->field_text(
				'woopilot_bale_group_id',
				__( 'شناسه گروه یا کانال', 'woopilot-bale' ),
				__( 'در صورت نیاز، شناسه گروه یا کانال بله را برای دریافت اعلان‌های مدیریتی وارد کنید.', 'woopilot-bale' )
			);
			?>

			<div class="woopilot-bale-field">
				<div class="woopilot-bale-field-label">
					<strong><?php echo esc_html__( 'تست اتصال', 'woopilot-bale' ); ?></strong>
					<p><?php echo esc_html__( 'بعد از ذخیره توکن، اتصال ربات را بررسی کنید.', 'woopilot-bale' ); ?></p>
				</div>
				<div class="woopilot-bale-field-control">
					<a href="#" class="button button-secondary" onclick="document.getElementById('woopilot-bale-test-form').submit(); return false;">
						<?php echo esc_html__( 'تست اتصال به بله', 'woopilot-bale' ); ?>
					</a>
				</div>
			</div>
<div class="woopilot-bale-field">
				<div class="woopilot-bale-field-label">
					<strong><?php echo esc_html__( 'ثبت Webhook ربات', 'woopilot-bale' ); ?></strong>
					<p><?php echo esc_html__( 'بعد از ذخیره تنظیمات، Webhook ربات را روی همین سایت ثبت کنید.', 'woopilot-bale' ); ?></p>
				</div>
				<div class="woopilot-bale-field-control">
					<a href="#" class="button button-primary" onclick="document.getElementById('woopilot-bale-webhook-form').submit(); return false;">
						<?php echo esc_html__( 'ثبت Webhook ربات', 'woopilot-bale' ); ?>
					</a>
					<p class="description">
						<?php echo esc_html__( 'بعد از ثبت Webhook، با زدن /start در بله منوی ربات نمایش داده می‌شود.', 'woopilot-bale' ); ?>
					</p>
				</div>
			</div>
		</section>
		<?php
	}

	private function render_direct_sales_tab(): void {
		$buttons         = $this->get_direct_sales_default_buttons();
		$enabled_buttons = get_option( 'woopilot_bale_direct_sales_buttons', array_keys( $buttons ) );

		if ( ! is_array( $enabled_buttons ) ) {
			$enabled_buttons = array_keys( $buttons );
		}
		?>
		<section class="woopilot-bale-section">
			<div class="woopilot-bale-section-title">
				<h2><?php echo esc_html__( 'فروش مستقیم داخل بله', 'woopilot-bale' ); ?></h2>
				<p><?php echo esc_html__( 'منوی اصلی ربات، دکمه‌های فروشگاه و متن‌های پایه فروش مستقیم را مدیریت کنید.', 'woopilot-bale' ); ?></p>
			</div>

			<?php
			$this->field_switch(
				'woopilot_bale_direct_sales_enabled',
				__( 'فعال‌سازی فروش مستقیم', 'woopilot-bale' ),
				__( 'اگر فعال باشد، بعد از /start منوی فروشگاه داخل ربات بله نمایش داده می‌شود.', 'woopilot-bale' )
			);

			$this->field_template(
				'woopilot_bale_direct_sales_welcome_message',
				__( 'پیام خوش‌آمدگویی ربات', 'woopilot-bale' ),
				__( 'این پیام بعد از استارت ربات برای کاربر ارسال می‌شود.', 'woopilot-bale' )
			);

			$this->field_text(
				'woopilot_bale_direct_sales_welcome_image_url',
				__( 'تصویر پیام خوش‌آمدگویی', 'woopilot-bale' ),
				__( 'آدرس کامل تصویر را وارد کنید. اگر خالی باشد پیام خوش‌آمدگویی فقط به صورت متن ارسال می‌شود.', 'woopilot-bale' )
			);
			?>

			<div class="woopilot-bale-field woopilot-bale-field-template">
				<div class="woopilot-bale-field-label">
					<strong><?php echo esc_html__( 'دکمه‌های منوی ربات', 'woopilot-bale' ); ?></strong>
					<p><?php echo esc_html__( 'هر دکمه را فعال/غیرفعال کنید و متن نمایشی آن را تغییر دهید. ترتیب فعلاً مطابق همین لیست است.', 'woopilot-bale' ); ?></p>
				</div>

				<div class="woopilot-bale-field-control">
					<?php foreach ( $buttons as $key => $default_label ) : ?>
						<?php
						$custom_label = get_option( 'woopilot_bale_direct_sales_button_' . $key, $default_label );
						?>
						<div style="display:grid;grid-template-columns:160px 1fr;gap:12px;align-items:center;margin-bottom:12px;">
							<label>
								<input
									type="checkbox"
									name="woopilot_bale_direct_sales_buttons[]"
									value="<?php echo esc_attr( $key ); ?>"
									<?php checked( in_array( $key, $enabled_buttons, true ) ); ?>
								>
								<?php echo esc_html( $default_label ); ?>
							</label>

							<input
								type="text"
								name="<?php echo esc_attr( 'woopilot_bale_direct_sales_button_' . $key ); ?>"
								value="<?php echo esc_attr( $custom_label ); ?>"
								class="regular-text"
							>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="woopilot-bale-field woopilot-bale-field-template">
				<div class="woopilot-bale-field-label">
					<strong><?php echo esc_html__( 'بخش‌های فروشگاه داخل ربات', 'woopilot-bale' ); ?></strong>
					<p><?php echo esc_html__( 'وقتی کاربر دکمه فروشگاه را می‌زند، به جای نمایش همه محصولات، این سه دکمه inline نمایش داده می‌شود. متن دکمه‌ها و شناسه محصولات هر بخش قابل تنظیم است.', 'woopilot-bale' ); ?></p>
				</div>

				<div class="woopilot-bale-field-control">
					<p class="description" style="margin-bottom:12px;">
						<?php echo esc_html__( 'شناسه محصولات را با ویرگول جدا کنید. مثال: 123,456,789', 'woopilot-bale' ); ?>
					</p>

					<div style="margin-bottom:18px;">
						<label style="display:block;font-weight:700;margin-bottom:6px;">
							<?php echo esc_html__( 'متن قبل از دکمه‌های فروشگاه', 'woopilot-bale' ); ?>
						</label>
						<textarea
							name="woopilot_bale_direct_sales_shop_intro_text"
							rows="3"
							class="large-text"
						><?php echo esc_textarea( get_option( 'woopilot_bale_direct_sales_shop_intro_text', __( 'لطفاً یکی از بخش‌های فروشگاه را انتخاب کنید:', 'woopilot-bale' ) ) ); ?></textarea>
					</div>

					<div style="display:grid;grid-template-columns:220px 1fr;gap:140px;align-items:start;margin-bottom:14px;">
						<div>
							<label style="display:block;font-weight:700;margin-bottom:6px;">
								<?php echo esc_html__( 'عنوان دکمه اول', 'woopilot-bale' ); ?>
							</label>
							<input
								type="text"
								name="woopilot_bale_direct_sales_shop_section_1_label"
								value="<?php echo esc_attr( get_option( 'woopilot_bale_direct_sales_shop_section_1_label', __( 'پرطرفدارترین‌ها', 'woopilot-bale' ) ) ); ?>"
								class="regular-text"
							>
						</div>
						<div>
							<label style="display:block;font-weight:700;margin-bottom:6px;">
								<?php echo esc_html__( 'شناسه محصولات دکمه اول', 'woopilot-bale' ); ?>
							</label>
							<textarea
								name="woopilot_bale_direct_sales_shop_section_1_ids"
								rows="2"
								class="large-text"
								placeholder="123,456,789"
							><?php echo esc_textarea( get_option( 'woopilot_bale_direct_sales_shop_section_1_ids', '' ) ); ?></textarea>
						</div>
					</div>

					<div style="display:grid;grid-template-columns:220px 1fr;gap:140px;align-items:start;margin-bottom:14px;">
						<div>
							<label style="display:block;font-weight:700;margin-bottom:6px;">
								<?php echo esc_html__( 'عنوان دکمه دوم', 'woopilot-bale' ); ?>
							</label>
							<input
								type="text"
								name="woopilot_bale_direct_sales_shop_section_2_label"
								value="<?php echo esc_attr( get_option( 'woopilot_bale_direct_sales_shop_section_2_label', __( 'پرفروش‌ترین‌ها', 'woopilot-bale' ) ) ); ?>"
								class="regular-text"
							>
						</div>
						<div>
							<label style="display:block;font-weight:700;margin-bottom:6px;">
								<?php echo esc_html__( 'شناسه محصولات دکمه دوم', 'woopilot-bale' ); ?>
							</label>
							<textarea
								name="woopilot_bale_direct_sales_shop_section_2_ids"
								rows="2"
								class="large-text"
								placeholder="123,456,789"
							><?php echo esc_textarea( get_option( 'woopilot_bale_direct_sales_shop_section_2_ids', '' ) ); ?></textarea>
						</div>
					</div>

					<div style="display:grid;grid-template-columns:220px 1fr;gap:140px;align-items:start;margin-bottom:14px;">
						<div>
							<label style="display:block;font-weight:700;margin-bottom:6px;">
								<?php echo esc_html__( 'عنوان دکمه سوم', 'woopilot-bale' ); ?>
							</label>
							<input
								type="text"
								name="woopilot_bale_direct_sales_shop_section_3_label"
								value="<?php echo esc_attr( get_option( 'woopilot_bale_direct_sales_shop_section_3_label', __( 'تخفیفات ویژه', 'woopilot-bale' ) ) ); ?>"
								class="regular-text"
							>
						</div>
						<div>
							<label style="display:block;font-weight:700;margin-bottom:6px;">
								<?php echo esc_html__( 'شناسه محصولات دکمه سوم', 'woopilot-bale' ); ?>
							</label>
							<textarea
								name="woopilot_bale_direct_sales_shop_section_3_ids"
								rows="2"
								class="large-text"
								placeholder="123,456,789"
							><?php echo esc_textarea( get_option( 'woopilot_bale_direct_sales_shop_section_3_ids', '' ) ); ?></textarea>
						</div>
					</div>
				</div>
			</div>

			<?php
			$this->field_template(
				'woopilot_bale_direct_sales_about_text',
				__( 'متن درباره ما', 'woopilot-bale' ),
				__( 'وقتی کاربر روی دکمه درباره ما بزند، این متن ارسال می‌شود.', 'woopilot-bale' )
			);

			$this->field_template(
				'woopilot_bale_direct_sales_support_text',
				__( 'متن پشتیبانی', 'woopilot-bale' ),
				__( 'وقتی کاربر روی دکمه پشتیبانی بزند، این متن ارسال می‌شود.', 'woopilot-bale' )
			);
			?>

			<div class="woopilot-bale-empty" style="margin-top:16px;">
				<?php echo esc_html__( 'مرحله فعلی: ساخت منوی ربات. در فاز بعدی جستجوی محصول، دسته‌بندی‌ها، سبد خرید و ثبت سفارش ووکامرس اضافه می‌شود.', 'woopilot-bale' ); ?>
			</div>
		</section>
		<?php
	}

	private function render_login_customizer_tab(): void {
		$logo_id  = absint( get_option( 'woopilot_bale_auth_logo_id', 0 ) );
		$logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';

		$primary       = get_option( 'woopilot_bale_auth_primary_color', '#8bd957' );
		$primary_hover = get_option( 'woopilot_bale_auth_primary_hover_color', '#78ca45' );
		$text_color    = get_option( 'woopilot_bale_auth_text_color', '#111827' );
		$background    = get_option( 'woopilot_bale_auth_background_color', '#f7f9fc' );
		$card_color    = get_option( 'woopilot_bale_auth_card_color', '#ffffff' );
		$input_color   = get_option( 'woopilot_bale_auth_input_color', '#f8fafc' );
		$border_color  = get_option( 'woopilot_bale_auth_border_color', '#eef0f4' );

		$card_radius   = absint( get_option( 'woopilot_bale_auth_card_radius', 24 ) );
		$input_radius  = absint( get_option( 'woopilot_bale_auth_input_radius', 12 ) );
		$button_radius = absint( get_option( 'woopilot_bale_auth_button_radius', 12 ) );
		$card_width    = absint( get_option( 'woopilot_bale_auth_card_width', 430 ) );
		$card_padding  = absint( get_option( 'woopilot_bale_auth_card_padding', 36 ) );
		?>
		<section class="woopilot-bale-section">
			<div class="woopilot-bale-section-title">
				<h2><?php echo esc_html__( 'سفارشی‌سازی صفحه لاگین', 'woopilot-bale' ); ?></h2>
				<p><?php echo esc_html__( 'لوگو، رنگ‌ها، ردیوس‌ها و ظاهر فرم ورود با بله را مدیریت کنید.', 'woopilot-bale' ); ?></p>
			</div>

			<div class="woopilot-auth-grid">
				<div>
					<div class="woopilot-bale-field">
						<div class="woopilot-bale-field-label">
							<strong><?php echo esc_html__( 'لوگو', 'woopilot-bale' ); ?></strong>
							<p><?php echo esc_html__( 'لوگوی صفحه ورود را از کتابخانه رسانه وردپرس انتخاب کنید.', 'woopilot-bale' ); ?></p>
						</div>

						<div class="woopilot-bale-field-control">
							<input
								type="hidden"
								id="woopilot_bale_auth_logo_id"
								name="woopilot_bale_auth_logo_id"
								value="<?php echo esc_attr( $logo_id ); ?>"
							>

							<button type="button" class="button woopilot-upload-logo">
								<?php echo esc_html__( 'انتخاب لوگو', 'woopilot-bale' ); ?>
							</button>

							<button type="button" class="button woopilot-remove-logo">
								<?php echo esc_html__( 'حذف لوگو', 'woopilot-bale' ); ?>
							</button>

							<div class="woopilot-auth-logo-preview">
								<?php if ( $logo_url ) : ?>
									<img src="<?php echo esc_url( $logo_url ); ?>" alt="">
								<?php else : ?>
									<span><?php echo esc_html__( 'لوگویی انتخاب نشده است.', 'woopilot-bale' ); ?></span>
								<?php endif; ?>
							</div>
						</div>
					</div>

					<?php
					$this->field_text(
						'woopilot_bale_auth_title',
						__( 'عنوان فرم', 'woopilot-bale' ),
						__( 'عنوان اصلی فرم ورود. مثال: ورود / ثبت نام', 'woopilot-bale' )
					);

					$this->field_text(
						'woopilot_bale_auth_subtitle',
						__( 'توضیح فرم', 'woopilot-bale' ),
						__( 'متن کوتاه زیر عنوان فرم.', 'woopilot-bale' )
					);

					$this->field_text(
						'woopilot_bale_auth_footer',
						__( 'متن پایین فرم', 'woopilot-bale' ),
						__( 'متن راهنمای پایین کارت ورود.', 'woopilot-bale' )
					);
					?>

					<div class="woopilot-bale-field">
						<div class="woopilot-bale-field-label">
							<strong><?php echo esc_html__( 'رنگ اصلی', 'woopilot-bale' ); ?></strong>
						</div>
						<div class="woopilot-bale-field-control">
							<input type="text" class="woopilot-color-picker" name="woopilot_bale_auth_primary_color" value="<?php echo esc_attr( $primary ); ?>">
						</div>
					</div>

					<div class="woopilot-bale-field">
						<div class="woopilot-bale-field-label">
							<strong><?php echo esc_html__( 'رنگ هاور دکمه', 'woopilot-bale' ); ?></strong>
						</div>
						<div class="woopilot-bale-field-control">
							<input type="text" class="woopilot-color-picker" name="woopilot_bale_auth_primary_hover_color" value="<?php echo esc_attr( $primary_hover ); ?>">
						</div>
					</div>

					<div class="woopilot-bale-field">
						<div class="woopilot-bale-field-label">
							<strong><?php echo esc_html__( 'رنگ متن', 'woopilot-bale' ); ?></strong>
						</div>
						<div class="woopilot-bale-field-control">
							<input type="text" class="woopilot-color-picker" name="woopilot_bale_auth_text_color" value="<?php echo esc_attr( $text_color ); ?>">
						</div>
					</div>

					<div class="woopilot-bale-field">
						<div class="woopilot-bale-field-label">
							<strong><?php echo esc_html__( 'رنگ پس‌زمینه', 'woopilot-bale' ); ?></strong>
						</div>
						<div class="woopilot-bale-field-control">
							<input type="text" class="woopilot-color-picker" name="woopilot_bale_auth_background_color" value="<?php echo esc_attr( $background ); ?>">
						</div>
					</div>

					<div class="woopilot-bale-field">
						<div class="woopilot-bale-field-label">
							<strong><?php echo esc_html__( 'رنگ کارت', 'woopilot-bale' ); ?></strong>
						</div>
						<div class="woopilot-bale-field-control">
							<input type="text" class="woopilot-color-picker" name="woopilot_bale_auth_card_color" value="<?php echo esc_attr( $card_color ); ?>">
						</div>
					</div>

					<div class="woopilot-bale-field">
						<div class="woopilot-bale-field-label">
							<strong><?php echo esc_html__( 'رنگ فیلدها', 'woopilot-bale' ); ?></strong>
						</div>
						<div class="woopilot-bale-field-control">
							<input type="text" class="woopilot-color-picker" name="woopilot_bale_auth_input_color" value="<?php echo esc_attr( $input_color ); ?>">
						</div>
					</div>

					<div class="woopilot-bale-field">
						<div class="woopilot-bale-field-label">
							<strong><?php echo esc_html__( 'رنگ بوردر فیلدها', 'woopilot-bale' ); ?></strong>
						</div>
						<div class="woopilot-bale-field-control">
							<input type="text" class="woopilot-color-picker" name="woopilot_bale_auth_border_color" value="<?php echo esc_attr( $border_color ); ?>">
						</div>
					</div>

					<?php
					$this->field_number(
						'woopilot_bale_auth_card_width',
						__( 'عرض کارت ورود', 'woopilot-bale' ),
						__( 'عرض کارت لاگین بر حسب پیکسل.', 'woopilot-bale' ),
						320
					);

					$this->field_number(
						'woopilot_bale_auth_card_padding',
						__( 'فاصله داخلی کارت', 'woopilot-bale' ),
						__( 'Padding کارت بر حسب پیکسل.', 'woopilot-bale' ),
						12
					);

					$this->field_number(
						'woopilot_bale_auth_card_radius',
						__( 'گردی کارت', 'woopilot-bale' ),
						__( 'Border radius کارت بر حسب پیکسل.', 'woopilot-bale' ),
						0
					);

					$this->field_number(
						'woopilot_bale_auth_input_radius',
						__( 'گردی فیلدها', 'woopilot-bale' ),
						__( 'Border radius فیلدها بر حسب پیکسل.', 'woopilot-bale' ),
						0
					);

					$this->field_number(
						'woopilot_bale_auth_button_radius',
						__( 'گردی دکمه‌ها', 'woopilot-bale' ),
						__( 'Border radius دکمه‌ها بر حسب پیکسل.', 'woopilot-bale' ),
						0
					);
					?>
				</div>

				<div>
					<div
						class="woopilot-auth-preview"
						style="
							background: <?php echo esc_attr( $card_color ); ?>;
							border-radius: <?php echo esc_attr( $card_radius ); ?>px;
							max-width: <?php echo esc_attr( $card_width ); ?>px;
							padding: <?php echo esc_attr( $card_padding ); ?>px;
						"
					>
						<div class="woopilot-auth-preview-logo">
							<?php if ( $logo_url ) : ?>
								<img src="<?php echo esc_url( $logo_url ); ?>" alt="">
							<?php else : ?>
								<strong>WooPilot Bale</strong>
							<?php endif; ?>
						</div>

						<div class="woopilot-auth-preview-title" style="color:<?php echo esc_attr( $text_color ); ?>">
							<?php echo esc_html( get_option( 'woopilot_bale_auth_title', __( 'ورود / ثبت نام', 'woopilot-bale' ) ) ); ?>
						</div>

						<div class="woopilot-auth-preview-subtitle">
							<?php echo esc_html( get_option( 'woopilot_bale_auth_subtitle', __( 'برای ورود شماره موبایل خود را وارد کنید.', 'woopilot-bale' ) ) ); ?>
						</div>

						<input
							type="text"
							class="woopilot-auth-preview-input"
							placeholder="<?php echo esc_attr__( 'شماره موبایل', 'woopilot-bale' ); ?>"
							style="
								background: <?php echo esc_attr( $input_color ); ?>;
								border-color: <?php echo esc_attr( $border_color ); ?>;
								border-radius: <?php echo esc_attr( $input_radius ); ?>px;
							"
						>

						<button
							type="button"
							class="woopilot-auth-preview-button"
							style="
								background: <?php echo esc_attr( $primary ); ?>;
								border-radius: <?php echo esc_attr( $button_radius ); ?>px;
							"
						>
							<?php echo esc_html__( 'ادامه', 'woopilot-bale' ); ?>
						</button>
					</div>
				</div>
			</div>
		</section>
		<?php
	}


	private function render_notifications_tab(): void {
		?>
		<section class="woopilot-bale-section">
			<div class="woopilot-bale-section-title">
				<h2><?php echo esc_html__( 'تنظیمات اعلان‌ها', 'woopilot-bale' ); ?></h2>
				<p><?php echo esc_html__( 'مشخص کنید چه نوع پیام‌هایی برای مدیر و مشتری ارسال شود.', 'woopilot-bale' ); ?></p>
			</div>

			<?php
			$this->field_switch(
				'woopilot_bale_enable_admin_notifications',
				__( 'اعلان مدیر', 'woopilot-bale' ),
				__( 'ارسال پیام سفارش‌ها، پرداخت‌ها و هشدارهای فروشگاه برای مدیر فعال باشد.', 'woopilot-bale' )
			);

			$this->field_switch(
				'woopilot_bale_enable_customer_notifications',
				__( 'اعلان مشتری', 'woopilot-bale' ),
				__( 'ارسال پیام تایید سفارش و تغییر وضعیت سفارش برای مشتری فعال باشد.', 'woopilot-bale' )
			);

			$this->field_switch(
				'woopilot_bale_enable_sales_report_notification',
				__( 'اعلان گزارش فروش برای مدیر', 'woopilot-bale' ),
				__( 'اگر فعال باشد، گزارش فروش طبق بازه انتخابی هر شب فقط برای مدیران در بله ارسال می‌شود.', 'woopilot-bale' )
			);

			$this->field_select(
				'woopilot_bale_sales_report_period',
				__( 'بازه گزارش فروش ارسالی', 'woopilot-bale' ),
				array(
					'today'     => __( 'امروز', 'woopilot-bale' ),
					'yesterday' => __( 'دیروز', 'woopilot-bale' ),
					'week'      => __( '۷ روز اخیر', 'woopilot-bale' ),
					'month'     => __( 'ماه جاری', 'woopilot-bale' ),
				),
				__( 'این بازه برای گزارش خودکاری استفاده می‌شود که برای مدیر ارسال خواهد شد.', 'woopilot-bale' )
			);

			$this->field_text(
				'woopilot_bale_sales_report_send_time',
				__( 'زمان ارسال گزارش فروش', 'woopilot-bale' ),
				__( 'فرمت ۲۴ ساعته وارد کنید. مثال: 23:00', 'woopilot-bale' )
			);
			?>
		</section>
		<?php
	}

	private function render_reports_tab(): void {
		?>
		<section class="woopilot-bale-section">
			<div class="woopilot-bale-section-title">
				<h2><?php echo esc_html__( 'گزارش فروش', 'woopilot-bale' ); ?></h2>
				<p><?php echo esc_html__( 'همه سفارش‌ها را در بازه‌های مختلف بررسی کنید و وضعیت تکمیل‌شده و تکمیل‌نشده را جدا ببینید.', 'woopilot-bale' ); ?></p>
			</div>

			<div class="woopilot-sales-report">
				<div class="woopilot-sales-report-filters">
					<div>
						<label for="woopilot_report_period"><?php echo esc_html__( 'بازه گزارش', 'woopilot-bale' ); ?></label>
						<select id="woopilot_report_period">
							<option value="today"><?php echo esc_html__( 'امروز', 'woopilot-bale' ); ?></option>
							<option value="yesterday"><?php echo esc_html__( 'دیروز', 'woopilot-bale' ); ?></option>
							<option value="week"><?php echo esc_html__( '۷ روز اخیر', 'woopilot-bale' ); ?></option>
							<option value="month"><?php echo esc_html__( 'ماه جاری', 'woopilot-bale' ); ?></option>
							<option value="custom"><?php echo esc_html__( 'بازه دلخواه', 'woopilot-bale' ); ?></option>
						</select>
					</div>

					<div class="woopilot-custom-date-fields">
						<div>
							<label for="woopilot_report_date_from"><?php echo esc_html__( 'از تاریخ شمسی', 'woopilot-bale' ); ?></label>
							<input type="text" id="woopilot_report_date_from" class="woopilot-persian-datepicker" autocomplete="off" placeholder="1405/02/01">
						</div>

						<div>
							<label for="woopilot_report_date_to"><?php echo esc_html__( 'تا تاریخ شمسی', 'woopilot-bale' ); ?></label>
							<input type="text" id="woopilot_report_date_to" class="woopilot-persian-datepicker" autocomplete="off" placeholder="1405/02/17">
						</div>
					</div>

					<div>
						<label for="woopilot_report_sort_by"><?php echo esc_html__( 'مرتب‌سازی بر اساس', 'woopilot-bale' ); ?></label>
						<select id="woopilot_report_sort_by">
							<option value="date"><?php echo esc_html__( 'تاریخ', 'woopilot-bale' ); ?></option>
							<option value="total"><?php echo esc_html__( 'مبلغ فروش', 'woopilot-bale' ); ?></option>
							<option value="items_sold"><?php echo esc_html__( 'تعداد محصول', 'woopilot-bale' ); ?></option>
						</select>
					</div>

					<div>
						<label for="woopilot_report_sort_order"><?php echo esc_html__( 'ترتیب', 'woopilot-bale' ); ?></label>
						<select id="woopilot_report_sort_order">
							<option value="DESC"><?php echo esc_html__( 'نزولی', 'woopilot-bale' ); ?></option>
							<option value="ASC"><?php echo esc_html__( 'صعودی', 'woopilot-bale' ); ?></option>
						</select>
					</div>

					<div>
						<button type="button" class="button button-primary" id="woopilot-load-sales-report">
							<?php echo esc_html__( 'دریافت گزارش', 'woopilot-bale' ); ?>
						</button>
					</div>
				</div>

				<div id="woopilot-sales-report-loading">
					<?php echo esc_html__( 'در حال دریافت گزارش...', 'woopilot-bale' ); ?>
				</div>

				<div class="woopilot-report-cards">
					<div class="woopilot-report-card"><span><?php echo esc_html__( 'کل سفارش‌ها', 'woopilot-bale' ); ?></span><strong id="woopilot-report-total-orders">0</strong></div>
					<div class="woopilot-report-card"><span><?php echo esc_html__( 'تکمیل‌شده', 'woopilot-bale' ); ?></span><strong id="woopilot-report-completed-orders">0</strong></div>
					<div class="woopilot-report-card"><span><?php echo esc_html__( 'تکمیل‌نشده', 'woopilot-bale' ); ?></span><strong id="woopilot-report-incomplete-orders">0</strong></div>
					<div class="woopilot-report-card"><span><?php echo esc_html__( 'مبلغ کل سفارش‌ها', 'woopilot-bale' ); ?></span><strong id="woopilot-report-total-sales">0</strong></div>
					<div class="woopilot-report-card"><span><?php echo esc_html__( 'مبلغ سفارش‌های تکمیل‌شده', 'woopilot-bale' ); ?></span><strong id="woopilot-report-completed-sales">0</strong></div>
					<div class="woopilot-report-card"><span><?php echo esc_html__( 'محصولات فروخته‌شده', 'woopilot-bale' ); ?></span><strong id="woopilot-report-items-sold">0</strong></div>
					<div class="woopilot-report-card"><span><?php echo esc_html__( 'بازه گزارش', 'woopilot-bale' ); ?></span><strong id="woopilot-report-date-range">-</strong></div>
				</div>

				<div class="woopilot-sales-chart-wrap">
					<canvas id="woopilot-sales-chart" width="900" height="320"></canvas>
				</div>

				<div class="woopilot-sales-report-table-wrap">
					<table>
						<thead>
							<tr>
								<th><?php echo esc_html__( 'سفارش', 'woopilot-bale' ); ?></th>
								<th><?php echo esc_html__( 'تاریخ', 'woopilot-bale' ); ?></th>
								<th><?php echo esc_html__( 'مشتری', 'woopilot-bale' ); ?></th>
								<th><?php echo esc_html__( 'تعداد محصول', 'woopilot-bale' ); ?></th>
								<th><?php echo esc_html__( 'مبلغ', 'woopilot-bale' ); ?></th>
								<th><?php echo esc_html__( 'وضعیت', 'woopilot-bale' ); ?></th>
							</tr>
						</thead>
						<tbody id="woopilot-sales-report-table-body">
							<tr>
								<td colspan="6"><?php echo esc_html__( 'در حال دریافت اطلاعات...', 'woopilot-bale' ); ?></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</section>
		<?php
	}

	private function render_templates_tab(): void {
		?>
		<section class="woopilot-bale-section">
			<div class="woopilot-bale-section-title">
				<h2><?php echo esc_html__( 'قالب پیام‌ها', 'woopilot-bale' ); ?></h2>
				<p><?php echo esc_html__( 'متن پیام‌هایی که برای مدیر و مشتری ارسال می‌شوند را مدیریت کنید.', 'woopilot-bale' ); ?></p>
			</div>

			<div class="woopilot-bale-template-help">
				<strong><?php echo esc_html__( 'متغیرهای قابل استفاده:', 'woopilot-bale' ); ?></strong>
				<div class="woopilot-bale-tags">
					<code>{order_id}</code>
					<code>{order_number}</code>
					<code>{customer_name}</code>
					<code>{total_price}</code>
					<code>{order_status}</code>
					<code>{payment_method}</code>
					<code>{products}</code>
					<code>{address}</code>
					<code>{billing_phone}</code>
					<code>{billing_email}</code>
					<code>{site_name}</code>
				
					<code>{order_payment_url}</code>
					<code>{product_name}</code>
					<code>{product_id}</code>
					<code>{stock_quantity}</code>
					<code>{stock_threshold}</code></div>
			</div>

			<?php
			$this->field_template( 'woopilot_bale_template_admin_new_order', __( 'پیام سفارش جدید برای مدیر', 'woopilot-bale' ), __( 'وقتی سفارش جدید در فروشگاه ثبت می‌شود، این پیام برای مدیر ارسال می‌شود.', 'woopilot-bale' ) );
			$this->field_template( 'woopilot_bale_template_customer_order_confirmed', __( 'پیام تایید سفارش برای مشتری', 'woopilot-bale' ), __( 'بعد از ثبت سفارش، این پیام برای مشتری ارسال می‌شود.', 'woopilot-bale' ) );
			$this->field_template( 'woopilot_bale_template_payment_success', __( 'پیام پرداخت موفق', 'woopilot-bale' ), __( 'بعد از پرداخت موفق سفارش، این پیام برای مشتری ارسال می‌شود.', 'woopilot-bale' ) );
			$this->field_template( 'woopilot_bale_template_payment_failed', __( 'پیام پرداخت ناموفق', 'woopilot-bale' ), __( 'در صورت ناموفق بودن پرداخت، این پیام برای مشتری ارسال می‌شود.', 'woopilot-bale' ) );
			$this->field_template( 'woopilot_bale_template_payment_reminder', __( 'پیام یادآوری پرداخت', 'woopilot-bale' ), __( 'اگر سفارش پرداخت‌نشده باقی بماند، این پیام به مشتری ارسال می‌شود.', 'woopilot-bale' ) );
			$this->field_template( 'woopilot_bale_template_order_processing', __( 'پیام وضعیت در حال پردازش', 'woopilot-bale' ), __( 'وقتی وضعیت سفارش به در حال پردازش تغییر کند، این پیام ارسال می‌شود.', 'woopilot-bale' ) );
			$this->field_template( 'woopilot_bale_template_order_completed', __( 'پیام تکمیل سفارش', 'woopilot-bale' ), __( 'وقتی سفارش تکمیل شود، این پیام برای مشتری ارسال می‌شود.', 'woopilot-bale' ) );
			$this->field_template( 'woopilot_bale_template_order_cancelled', __( 'پیام لغو سفارش', 'woopilot-bale' ), __( 'وقتی سفارش لغو شود، این پیام برای مشتری ارسال می‌شود.', 'woopilot-bale' ) );
			$this->field_template( 'woopilot_bale_template_low_stock', __( 'پیام هشدار موجودی کم', 'woopilot-bale' ), __( 'وقتی موجودی محصول کمتر از حد هشدار شود، این پیام برای مدیر ارسال می‌شود.', 'woopilot-bale' ) );
			?>
		</section>
		<?php
	}

	private function render_automation_tab(): void {
		?>
		<section class="woopilot-bale-section">
			<div class="woopilot-bale-section-title">
				<h2><?php echo esc_html__( 'اتوماسیون پیام‌ها و ورود', 'woopilot-bale' ); ?></h2>
				<p><?php echo esc_html__( 'تنظیمات مربوط به ورود با بله، اتصال حساب بله و پیام‌های زمان‌بندی‌شده را مدیریت کنید.', 'woopilot-bale' ); ?></p>
			</div>

			<?php
			$this->field_switch( 'woopilot_bale_enable_otp_login', __( 'ورود با کد بله', 'woopilot-bale' ), __( 'اگر فعال باشد، کاربران می‌توانند با شماره موبایل و دریافت کد ورود در بله وارد حساب کاربری شوند.', 'woopilot-bale' ) );
			$this->field_switch( 'woopilot_bale_enable_bale_connect', __( 'اتصال حساب بله', 'woopilot-bale' ), __( 'اگر فعال باشد، صفحه اتصال حساب بله در حساب کاربری ووکامرس نمایش داده می‌شود.', 'woopilot-bale' ) );
			$this->field_number( 'woopilot_bale_payment_reminder_minutes', __( 'زمان یادآوری پرداخت', 'woopilot-bale' ), __( 'اگر سفارش بعد از این تعداد دقیقه پرداخت نشد، پیام یادآوری ارسال شود.', 'woopilot-bale' ), 1 );
			?>
		</section>
		<?php
	}

	private function render_inventory_tab(): void {
		?>
		<section class="woopilot-bale-section">
			<div class="woopilot-bale-section-title">
				<h2><?php echo esc_html__( 'هشدار موجودی کالا', 'woopilot-bale' ); ?></h2>
				<p><?php echo esc_html__( 'وقتی موجودی محصول کمتر از حد تعیین‌شده شد، به مدیر پیام ارسال می‌شود.', 'woopilot-bale' ); ?></p>
			</div>

			<?php
			$this->field_number(
				'woopilot_bale_low_stock_threshold',
				__( 'حداقل موجودی برای هشدار', 'woopilot-bale' ),
				__( 'مثلاً اگر مقدار ۵ باشد، موجودی کمتر یا مساوی ۵ باعث ارسال هشدار می‌شود.', 'woopilot-bale' ),
				0
			);
			?>
		</section>
		<?php
	}

	private function render_logs_tab(): void {
		?>
		<section class="woopilot-bale-section">
			<div class="woopilot-bale-section-title">
				<h2><?php echo esc_html__( 'لاگ‌ها و خطاها', 'woopilot-bale' ); ?></h2>
				<p><?php echo esc_html__( 'نمایش لاگ درخواست‌ها و پاسخ‌های API در مرحله سیستم لاگ تکمیل می‌شود.', 'woopilot-bale' ); ?></p>
			</div>

			<div class="woopilot-bale-empty">
				<?php echo esc_html__( 'هنوز لاگی برای نمایش وجود ندارد.', 'woopilot-bale' ); ?>
			</div>
		</section>
		<?php
	}

	private function render_advanced_tab(): void {
		?>
		<section class="woopilot-bale-section">
			<div class="woopilot-bale-section-title">
				<h2><?php echo esc_html__( 'تنظیمات پیشرفته', 'woopilot-bale' ); ?></h2>
				<p><?php echo esc_html__( 'تنظیمات مربوط به خطایابی، صف پیام‌ها و تلاش مجدد ارسال.', 'woopilot-bale' ); ?></p>
			</div>

			<?php
			$this->field_switch( 'woopilot_bale_debug_mode', __( 'حالت خطایابی', 'woopilot-bale' ), __( 'در صورت فعال بودن، جزئیات بیشتری از درخواست‌ها و خطاها ثبت می‌شود.', 'woopilot-bale' ) );
			$this->field_number( 'woopilot_bale_retry_limit', __( 'تعداد تلاش مجدد', 'woopilot-bale' ), __( 'اگر ارسال پیام ناموفق بود، سیستم حداکثر این تعداد بار دوباره تلاش می‌کند.', 'woopilot-bale' ), 0 );
			?>
		</section>
		<?php
	}

	private function field_text( string $name, string $label, string $description = '' ): void {
		$value = get_option( $name, '' );
		?>
		<div class="woopilot-bale-field">
			<div class="woopilot-bale-field-label">
				<strong><?php echo esc_html( $label ); ?></strong>
				<?php if ( $description ) : ?><p><?php echo esc_html( $description ); ?></p><?php endif; ?>
			</div>
			<div class="woopilot-bale-field-control">
				<input type="text" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text">
			</div>
		</div>
		<?php
	}

	private function field_password( string $name, string $label, string $description = '' ): void {
		$value = get_option( $name, '' );
		?>
		<div class="woopilot-bale-field">
			<div class="woopilot-bale-field-label">
				<strong><?php echo esc_html( $label ); ?></strong>
				<?php if ( $description ) : ?><p><?php echo esc_html( $description ); ?></p><?php endif; ?>
			</div>
			<div class="woopilot-bale-field-control">
				<input type="password" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text" autocomplete="off">
			</div>
		</div>
		<?php
	}

	private function field_textarea( string $name, string $label, string $description = '' ): void {
		$value = get_option( $name, '' );
		?>
		<div class="woopilot-bale-field">
			<div class="woopilot-bale-field-label">
				<strong><?php echo esc_html( $label ); ?></strong>
				<?php if ( $description ) : ?><p><?php echo esc_html( $description ); ?></p><?php endif; ?>
			</div>
			<div class="woopilot-bale-field-control">
				<textarea name="<?php echo esc_attr( $name ); ?>" rows="4" class="large-text"><?php echo esc_textarea( $value ); ?></textarea>
			</div>
		</div>
		<?php
	}

	private function field_template( string $name, string $label, string $description = '' ): void {
		$defaults = TemplateDefaults::all();
		$value    = get_option( $name, $defaults[ $name ] ?? '' );
		?>
		<div class="woopilot-bale-field woopilot-bale-field-template">
			<div class="woopilot-bale-field-label">
				<strong><?php echo esc_html( $label ); ?></strong>
				<?php if ( $description ) : ?><p><?php echo esc_html( $description ); ?></p><?php endif; ?>
			</div>
			<div class="woopilot-bale-field-control">
				<textarea name="<?php echo esc_attr( $name ); ?>" rows="8" class="large-text woopilot-bale-template-textarea"><?php echo esc_textarea( $value ); ?></textarea>
			</div>
		</div>
		<?php
	}

	private function field_number( string $name, string $label, string $description = '', int $min = 0 ): void {
		$value = absint( get_option( $name, 0 ) );
		?>
		<div class="woopilot-bale-field">
			<div class="woopilot-bale-field-label">
				<strong><?php echo esc_html( $label ); ?></strong>
				<?php if ( $description ) : ?><p><?php echo esc_html( $description ); ?></p><?php endif; ?>
			</div>
			<div class="woopilot-bale-field-control">
				<input type="number" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" min="<?php echo esc_attr( $min ); ?>" class="small-text">
			</div>
		</div>
		<?php
	}

	private function field_switch( string $name, string $label, string $description = '' ): void {
		$value = get_option( $name, 'no' );
		?>
		<div class="woopilot-bale-field">
			<div class="woopilot-bale-field-label">
				<strong><?php echo esc_html( $label ); ?></strong>
				<?php if ( $description ) : ?><p><?php echo esc_html( $description ); ?></p><?php endif; ?>
			</div>
			<div class="woopilot-bale-field-control">
				<label class="woopilot-bale-switch">
					<input type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="yes" <?php checked( $value, 'yes' ); ?>>
					<span></span>
				</label>
			</div>
		</div>
		<?php
	}

	private function field_select( string $name, string $label, array $options, string $description = '' ): void {
		$value = get_option( $name, '' );
		?>
		<div class="woopilot-bale-field">
			<div class="woopilot-bale-field-label">
				<strong><?php echo esc_html( $label ); ?></strong>
				<?php if ( $description ) : ?><p><?php echo esc_html( $description ); ?></p><?php endif; ?>
			</div>
			<div class="woopilot-bale-field-control">
				<select name="<?php echo esc_attr( $name ); ?>">
					<?php foreach ( $options as $option_value => $option_label ) : ?>
						<option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( $value, $option_value ); ?>>
							<?php echo esc_html( $option_label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
		<?php
	}

	private function render_notice(): void {
		$status  = isset( $_GET['woopilot_bale_test_status'] ) ? sanitize_text_field( wp_unslash( $_GET['woopilot_bale_test_status'] ) ) : '';
		$message = isset( $_GET['woopilot_bale_test_message'] ) ? sanitize_text_field( wp_unslash( $_GET['woopilot_bale_test_message'] ) ) : '';

		if ( empty( $status ) || empty( $message ) ) {
			return;
		}
		?>
		<div class="notice <?php echo 'success' === $status ? 'notice-success' : 'notice-error'; ?> is-dismissible woopilot-bale-notice">
			<p><?php echo esc_html( rawurldecode( $message ) ); ?></p>
		</div>
		<?php
	}

	private function get_direct_sales_default_buttons(): array {
		return array(
			'search_product' => __( '🔍 جستجوی محصول', 'woopilot-bale' ),
			'track_product'  => __( '📦 پیگیری محصول', 'woopilot-bale' ),
			'shop'           => __( '🛒 فروشگاه', 'woopilot-bale' ),
			'categories'     => __( '📂 دسته‌بندی‌ها', 'woopilot-bale' ),
			'my_account'     => __( '👤 حساب من', 'woopilot-bale' ),
			'my_orders'      => __( '🧾 سفارشات من', 'woopilot-bale' ),
			'sales'          => __( '🔥 حراجی‌ها', 'woopilot-bale' ),
			'login'          => __( '🔐 ورود به حساب کاربری', 'woopilot-bale' ),
			'about'          => __( 'ℹ️ درباره ما', 'woopilot-bale' ),
			'support'        => __( '☎️ پشتیبانی', 'woopilot-bale' ),
		);
	}

	private function get_current_sanitizing_option_name(): string {
		$filter = current_filter();

		if ( is_string( $filter ) && str_starts_with( $filter, 'sanitize_option_' ) ) {
			return str_replace( 'sanitize_option_', '', $filter );
		}

		return '';
	}

	private function get_submitted_tab(): string {
		if ( isset( $_POST['woopilot_bale_active_tab'] ) ) {
			return sanitize_key( wp_unslash( $_POST['woopilot_bale_active_tab'] ) );
		}

		return '';
	}

	private function should_preserve_missing_option( string $option_name ): bool {
		if ( '' === $option_name ) {
			return true;
		}

		if ( array_key_exists( $option_name, $_POST ) ) {
			return false;
		}

		$active_tab = $this->get_submitted_tab();

		if ( '' === $active_tab ) {
			return true;
		}

		$tab_options = $this->get_tab_options_map();

		if ( ! isset( $tab_options[ $active_tab ] ) ) {
			return true;
		}

		return ! in_array( $option_name, $tab_options[ $active_tab ], true );
	}

	private function get_tab_options_map(): array {
		$direct_sales_options = array(
			'woopilot_bale_direct_sales_enabled',
			'woopilot_bale_direct_sales_welcome_message',
			'woopilot_bale_direct_sales_welcome_image_url',
			'woopilot_bale_direct_sales_about_text',
			'woopilot_bale_direct_sales_support_text',
			'woopilot_bale_direct_sales_buttons',
			'woopilot_bale_direct_sales_shop_intro_text',
			'woopilot_bale_direct_sales_shop_section_1_label',
			'woopilot_bale_direct_sales_shop_section_1_ids',
			'woopilot_bale_direct_sales_shop_section_2_label',
			'woopilot_bale_direct_sales_shop_section_2_ids',
			'woopilot_bale_direct_sales_shop_section_3_label',
			'woopilot_bale_direct_sales_shop_section_3_ids',
		);

		foreach ( array_keys( $this->get_direct_sales_default_buttons() ) as $key ) {
			$direct_sales_options[] = 'woopilot_bale_direct_sales_button_' . $key;
		}

		return array(
			'bot'           => array(
				'woopilot_bale_bot_token',
				'woopilot_bale_bot_username',
				'woopilot_bale_webhook_secret',
				'woopilot_bale_admin_ids',
				'woopilot_bale_group_id',
			),
			'notifications' => array(
				'woopilot_bale_enable_admin_notifications',
				'woopilot_bale_enable_customer_notifications',
				'woopilot_bale_enable_sales_report_notification',
				'woopilot_bale_sales_report_period',
				'woopilot_bale_sales_report_send_time',
			),
			'direct_sales'  => $direct_sales_options,
			'login_customizer' => array(
				'woopilot_bale_auth_logo_id',
				'woopilot_bale_auth_title',
				'woopilot_bale_auth_subtitle',
				'woopilot_bale_auth_footer',
				'woopilot_bale_auth_primary_color',
				'woopilot_bale_auth_primary_hover_color',
				'woopilot_bale_auth_text_color',
				'woopilot_bale_auth_background_color',
				'woopilot_bale_auth_card_color',
				'woopilot_bale_auth_input_color',
				'woopilot_bale_auth_border_color',
				'woopilot_bale_auth_card_radius',
				'woopilot_bale_auth_input_radius',
				'woopilot_bale_auth_button_radius',
				'woopilot_bale_auth_card_width',
				'woopilot_bale_auth_card_padding',
			),
			'templates'     => array(
				'woopilot_bale_template_admin_new_order',
				'woopilot_bale_template_customer_order_confirmed',
				'woopilot_bale_template_payment_success',
				'woopilot_bale_template_payment_failed',
				'woopilot_bale_template_payment_reminder',
				'woopilot_bale_template_order_processing',
				'woopilot_bale_template_order_completed',
				'woopilot_bale_template_order_cancelled',
				'woopilot_bale_template_low_stock',
			),
			'automation'    => array(
				'woopilot_bale_enable_otp_login',
				'woopilot_bale_enable_bale_connect',
				'woopilot_bale_payment_reminder_minutes',
			),
			'inventory'     => array(
				'woopilot_bale_low_stock_threshold',
			),
			'advanced'      => array(
				'woopilot_bale_debug_mode',
				'woopilot_bale_retry_limit',
			),
		);
	}

	public function sanitize_text_preserve( $value ): string {
		$option_name = $this->get_current_sanitizing_option_name();

		if ( null === $value && $this->should_preserve_missing_option( $option_name ) ) {
			return (string) get_option( $option_name, '' );
		}

		return sanitize_text_field( (string) $value );
	}

	public function sanitize_bot_username( $value ): string {
		$option_name = $this->get_current_sanitizing_option_name();

		if ( null === $value && $this->should_preserve_missing_option( $option_name ) ) {
			return (string) get_option( $option_name, '' );
		}

		$value = sanitize_text_field( wp_unslash( (string) $value ) );
		$value = trim( $value );
		$value = ltrim( $value, '@' );

		return sanitize_user( $value, true );
	}

	public function sanitize_token( $value ): string {
		$option_name = $this->get_current_sanitizing_option_name();

		if ( null === $value && $this->should_preserve_missing_option( $option_name ) ) {
			return (string) get_option( $option_name, '' );
		}

		return sanitize_text_field( (string) $value );
	}

	public function sanitize_admin_ids( $value ): string {
		$option_name = $this->get_current_sanitizing_option_name();

		if ( null === $value && $this->should_preserve_missing_option( $option_name ) ) {
			return (string) get_option( $option_name, '' );
		}

		$value = sanitize_textarea_field( (string) $value );
		$ids   = array_filter( array_map( 'trim', explode( ',', $value ) ) );

		$ids = array_map(
			static function ( string $id ): string {
				return preg_replace( '/[^0-9\-]/', '', $id );
			},
			$ids
		);

		return implode( ',', array_filter( $ids ) );
	}

	public function sanitize_yes_no( $value ): string {
		$option_name = $this->get_current_sanitizing_option_name();

		if ( null === $value && $this->should_preserve_missing_option( $option_name ) ) {
			return (string) get_option( $option_name, 'no' );
		}

		return 'yes' === $value ? 'yes' : 'no';
	}

	public function sanitize_number( $value ): int {
		$option_name = $this->get_current_sanitizing_option_name();

		if ( null === $value && $this->should_preserve_missing_option( $option_name ) ) {
			return absint( get_option( $option_name, 0 ) );
		}

		return absint( $value );
	}

	public function sanitize_template( $value ): string {
		$option_name = $this->get_current_sanitizing_option_name();

		if ( null === $value && $this->should_preserve_missing_option( $option_name ) ) {
			return (string) get_option( $option_name, '' );
		}

		$value = wp_unslash( (string) $value );

		return wp_kses( $value, array() );
	}

	public function sanitize_direct_sales_buttons( $value ): array {
		$option_name = $this->get_current_sanitizing_option_name();

		if ( null === $value && $this->should_preserve_missing_option( $option_name ) ) {
			return (array) get_option(
				$option_name,
				array_keys( $this->get_direct_sales_default_buttons() )
			);
		}

		$allowed = array_keys( $this->get_direct_sales_default_buttons() );

		if ( ! is_array( $value ) ) {
			return array();
		}

		$value = array_map( 'sanitize_key', $value );

		return array_values( array_intersect( $value, $allowed ) );
	}

	public function sanitize_color( $value ): string {
		$option_name = $this->get_current_sanitizing_option_name();

		if ( null === $value && $this->should_preserve_missing_option( $option_name ) ) {
			return (string) get_option( $option_name, '#ffffff' );
		}

		$value = sanitize_hex_color( (string) $value );

		return $value ?: '#ffffff';
	}


	public function sanitize_product_ids( $value ): string {
		$option_name = $this->get_current_sanitizing_option_name();

		if ( null === $value && $this->should_preserve_missing_option( $option_name ) ) {
			return (string) get_option( $option_name, '' );
		}

		$value = sanitize_textarea_field( (string) $value );
		$ids   = preg_split( '/[\s,،]+/u', $value );

		if ( ! is_array( $ids ) ) {
			return '';
		}

		$ids = array_map(
			static function ( $id ): int {
				return absint( $id );
			},
			$ids
		);

		$ids = array_values( array_unique( array_filter( $ids ) ) );

		return implode( ',', $ids );
	}


	public function sanitize_url( $value ): string {
		$option_name = $this->get_current_sanitizing_option_name();

		if ( null === $value && $this->should_preserve_missing_option( $option_name ) ) {
			return (string) get_option( $option_name, '' );
		}

		return esc_url_raw( (string) $value );
	}


	public function sanitize_report_period( $value ): string {
		$option_name = $this->get_current_sanitizing_option_name();

		if ( null === $value && $this->should_preserve_missing_option( $option_name ) ) {
			return (string) get_option( $option_name, 'today' );
		}

		$value   = sanitize_key( (string) $value );
		$allowed = array( 'today', 'yesterday', 'week', 'month' );

		return in_array( $value, $allowed, true ) ? $value : 'today';
	}

	public function sanitize_time( $value ): string {
		$option_name = $this->get_current_sanitizing_option_name();

		if ( null === $value && $this->should_preserve_missing_option( $option_name ) ) {
			return (string) get_option( $option_name, '23:00' );
		}

		$value = sanitize_text_field( (string) $value );

		return preg_match( '/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $value ) ? $value : '23:00';
	}

	public function reschedule_sales_report_cron( $old_value = null, $value = null, $option = '' ): void {
		$hook = 'woopilot_bale_send_scheduled_sales_report';

		if ( function_exists( 'wp_clear_scheduled_hook' ) ) {
			wp_clear_scheduled_hook( $hook );
		}

		if ( 'yes' !== get_option( 'woopilot_bale_enable_sales_report_notification', 'no' ) ) {
			return;
		}

		if ( class_exists( '\WooPilot\Bale\Reports\ScheduledSalesReport' ) ) {
			$scheduled_report = new \WooPilot\Bale\Reports\ScheduledSalesReport();
			$scheduled_report->maybe_schedule();
		}
	}


	public function handle_set_webhook(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'شما اجازه دسترسی به این بخش را ندارید.', 'woopilot-bale' ) );
		}
	
		check_admin_referer( 'woopilot_bale_set_webhook' );
	
		$secret = trim( (string) get_option( 'woopilot_bale_webhook_secret', '' ) );
	
		if ( '' === $secret ) {
			$secret = wp_generate_password( 24, false, false );
			update_option( 'woopilot_bale_webhook_secret', $secret );
		}
	
		$webhook_url = add_query_arg(
			array(
				'secret' => $secret,
			),
			rest_url( 'woopilot-bale/v1/webhook' )
		);
	
		$api    = new BaleApi( get_option( 'woopilot_bale_bot_token', '' ) );
		$result = $api->set_webhook( $webhook_url );
	
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'                       => 'woopilot-bale',
					'tab'                        => 'bot',
					'woopilot_bale_test_status'  => ! empty( $result['success'] ) ? 'success' : 'error',
					'woopilot_bale_test_message' => rawurlencode(
						! empty( $result['success'] )
							? __( 'Webhook ربات با موفقیت ثبت شد.', 'woopilot-bale' )
							: ( $result['message'] ?? __( 'ثبت Webhook ناموفق بود.', 'woopilot-bale' ) )
					),
				),
				admin_url( 'admin.php' )
			)
		);
	
		exit;
	}
}
